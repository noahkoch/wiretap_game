<?php

class Game {
  public $code;
  public $completed;
  public $has_started;
  public $veto_used;
  public $blind_used;
  public $kill_used;
  public $mute_used;
  public $secret_code;
  public $hint_code;
  public $unlocked_by;

  const SIGNALS = [
    'Scratch chin',
    'Blink twice, pause, blink twice',
    'Bite lower lip',
    'Bite upper lip',
    'Rest face on hand',
    'Show/stick out tongue',
    'Pick up a random object'
  ];

  public function __construct($code) {
    $query = DB::query("SELECT * FROM games WHERE game_code = '{$code}';");
    if($query->num_rows > 0) {
      $row = $query->fetch_assoc();

      $this->code = $code;
      $this->has_started = $row['has_started'];
      $this->owner = $row['owner'];

      $this->secret_code = $row['secret_code'];
      $this->hint_code = $row['hint_code'];

      $this->veto_used = $row['veto_used'];
      $this->kill_used = $row['kill_used'];
      $this->mute_used = $row['mute_used'];
      $this->blind_used = $row['blind_used'];

      $this->unlocked_by = $row['unlocked_by'];
    }
  }

  public static function create_new_game($user_id) {
    $code = Game::generate_unique_game_code();
    $secret_code = Game::generate_secret_code();
    $secret_code_str = str_split($secret_code);
    $hint_code = intval("{$secret_code_str[rand(0,1)]}{$secret_code_str[rand(2,4)]}");

    if(DB::query("INSERT INTO games (owner, game_code, secret_code, hint_code) VALUES ('{$user_id}', '{$code}', '{$secret_code}', '{$hint_code}');")){
      Player::join_game($user_id, $code);
      return new Game($code);
    } else {
      return new Game(null);
    }
  }

  public static function generate_secret_code() {
    return rand(123, 99999);
  }

  public static function generate_unique_game_code() {
    $length = 4;
    $game_code = null;

    while($game_code == null) {
      $game_code = substr(str_shuffle("ABCDEFGHJKMNPQRSTUVWXYZ"), 0, $length);

      if(DB::query("SELECT * FROM games WHERE game_code = '{$game_code}'")->num_rows == 1) {
        $game_code = null;
      }
    }

    return $game_code;
  }

  public function start() {
    $this->assign_players();
    DB::query("UPDATE games SET has_started = TRUE WHERE game_code = '{$this->code}'");
  }

  public function assign_players() {
    DB::query("UPDATE players SET character_type = null WHERE game_code = '{$this->code}'");

    $query = DB::query("SELECT * FROM players WHERE game_code = '{$this->code}' ORDER BY rand()");

    $sender_assigned = false;
    $receiver_assigned = false;

    $game_signal_keys = array_rand(Game::SIGNALS, 2);
    $game_signals = array(Game::SIGNALS[$game_signal_keys[0]], Game::SIGNALS[$game_signal_keys[1]]);
    $user_signals = array();

    while($row = $query->fetch_assoc()) {
      if (!$sender_assigned) {
        $assigned_players['sender'][] = $row['user_id'];
        $user_signals[$row['user_id']] = join("<br>", $game_signals);
        $sender_assigned = true;
      } else if (!$receiver_assigned) {
        $assigned_players['receiver'][] = $row['user_id'];
        $user_signals[$row['user_id']] = join("<br>", $game_signals);
        $receiver_assigned = true;
      } else {
        $assigned_players['enemy'][] = $row['user_id'];
        $user_signals[$row['user_id']] = array_shift($game_signals);
      }
    }

    foreach($assigned_players as $character_type => $users) {
      foreach($users as $user_id) {
        $signals = $user_signals[$user_id];
        DB::query("UPDATE players SET died = false, blinded_until = null, muted_until = null, character_type = '{$character_type}', known_signals = '{$signals}' WHERE game_code = '{$this->code}' AND user_id = '{$user_id}'");
      }
    }
  }

  public function open_lock($player, $secret_code) {
    DB::query(
      "UPDATE players SET unlock_attempted = '{$secret_code}' WHERE game_code = '{$this->code}' AND user_id = '{$player->user_id}'"
    );

    if($player->character_type == 'sender') {
      return;
    }

    if(strval($this->secret_code) == strval($secret_code)) {
      DB::query(
        "UPDATE games SET unlocked_by = '{$player->username}', completed = TRUE WHERE game_code = '{$this->code}'"
      );
    }
  }

  public function kill($user_id) {
    if($game->kill_used) {
      return;
    }

    DB::query(
      "UPDATE players SET died = TRUE WHERE game_code = '{$this->code}' AND user_id = '{$user_id}'"
    );

    DB::query(
      "UPDATE games SET kill_used = TRUE WHERE game_code = '{$this->code}'"
    );
  }

  public function blind($user_id) {
    if($game->blind_used) {
      return;
    }

    DB::query(
      "UPDATE players SET blinded_until = now() + 90 WHERE game_code = '{$this->code}' AND user_id = '{$user_id}'"
    );

    DB::query(
      "UPDATE games SET blind_used = TRUE WHERE game_code = '{$this->code}'"
    );
  }

  public function mute($user_id) {
    if($game->mute_used) {
      return;
    }

    DB::query(
      "UPDATE players SET muted_until = now() + 90 WHERE game_code = '{$this->code}' AND user_id = '{$user_id}'"
    );

    DB::query(
      "UPDATE games SET mute_used = TRUE WHERE game_code = '{$this->code}'"
    );
  }

  public function veto($decision) {
    if($game->veto_used) {
      return;
    }

    switch($decision) {
    case 'kill':
      DB::query(
        "UPDATE players SET died = FALSE WHERE game_code = '{$this->code}'"
      );
      break;
    case 'mute':
      DB::query(
        "UPDATE players SET muted_until = null WHERE game_code = '{$this->code}'"
      );
      break;
    case 'blind':
      DB::query(
        "UPDATE players SET blinded_until = null WHERE game_code = '{$this->code}'"
      );
      break;
    }

    DB::query(
      "UPDATE games SET veto_used = TRUE WHERE game_code = '{$this->code}'"
    );
  }
}
