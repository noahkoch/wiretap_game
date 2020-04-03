<?php

class Player {
  public $character_type;
  public $died;
  public $user_id;
  public $username;
  public $game_code;
  public $known_signals;
  public $unlock_attempt;
  public $blinded_until;
  public $muted_until;

  public function __construct($user_id, $game_code) {
    $query = DB::query("SELECT *, UNIX_TIMESTAMP(blinded_until) as blinded_until, UNIX_TIMESTAMP(muted_until) as muted_until FROM players INNER JOIN users ON players.user_id = users.user_id WHERE players.user_id = '{$user_id}' AND game_code = '{$game_code}';");
    if($query->num_rows == 1) {
      $row = $query->fetch_assoc();
      $this->user_id = $row['user_id'];
      $this->username = $row['username'];
      $this->game_code = $row['game_code'];
      $this->character_type = $row['character_type'];
      $this->died = $row['died'];
      $this->known_signals = $row['known_signals'];
      $this->unlock_attempt = $row['unlock_attempted'];

      $this->blinded_until = $row['blinded_until'];
      $this->muted_until = $row['muted_until'];
    }
  }

  public function exists() {
    return !!$this->user_id;
  }

  public function get_state() {
    if($this->died) { return "dead"; }
    if($this->blinded_until && $this->blinded_until >= time()) { return "blind"; }
    if($this->muted_until && $this->muted_until >= time()) { return "muted"; }

    return 'Alive and well';
  }

  public static function all_for_game($game_code) {
    return DB::query("SELECT * FROM players INNER JOIN users ON players.user_id = users.user_id WHERE game_code = '{$game_code}' ORDER BY users.user_id");
  }

  public static function join_game($user_id, $game_code) {
    DB::query("INSERT INTO players (user_id, game_code) VALUES ('{$user_id}', '{$game_code}')");
    return new Player($user_id, $game_code);
  }
}
