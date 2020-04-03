<head>
  <title>Wiretap Game</title>
  <script
			  src="https://code.jquery.com/jquery-3.4.1.min.js"
			  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
			  crossorigin="anonymous"></script>
  <style type="text/css">
    table {
      width: 100%;
    } 

    tr:nth-child(2n) td{
      background: #f7f7f7;
    }

    td.reached, tr:nth-child(2n) td.reached{
      background: green;
    }

    td.not-reached, tr:nth-child(2n) td.not-reached {
      background: orange;
    }

    tr.dq td.not-reached, tr.dq:nth-child(2n) td.not-reached {
      background: red;
    }

    td.dq {
      color: red; 
    }

    td.finished {
      color: green; 
    }
  </style>

  <script type="text/javascript">
    $(document).ready(function(){
      setInterval(
        function(){
          $.get(
            '/game/game_state.php?code=ENKD',
            function(response) {
              $('.game-table').html(response);
            }
          )
        },
        2000
      )
    })
  </script>
</head>

<body>
  <?php
    require "../../functions.php";
    $game_code = $_GET['code'];

    $game      = new Game($game_code);

    if(!$game->code) {
      header('Location: /');
      return;
    }

    $user      = current_user()->user_id;

    if(!$user) {
      header('Location: /');
      return;
    }

    $is_owner  = $user == $game->owner;
    $player    = new Player(current_user()->user_id, $game_code);

    if(isset($_POST['join'])) {
      $player = Player::join_game(current_user()->user_id, $game_code);
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['start_game']) && $is_owner) {
      $game->start();
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['name_override']) && $is_owner) {
      $user = new User(array('user_id' => $_POST['user_id'], 'username' => $_POST['name_override']));
      $user->override_name($_POST['name_override']);
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['secret_code']) && $player->unlock_attempt == null) {
      $game->open_lock($player, $_POST['secret_code']);
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['reassign_players']) && $is_owner) {
      $game->assign_players();
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['veto_kill'])) {
      $game->veto('kill');
      header("Location: /game/play.php?code={$game_code}");
      return;
    }
    if(isset($_POST['veto_blind'])) {
      $game->veto('blind');
      header("Location: /game/play.php?code={$game_code}");
      return;
    }
    if(isset($_POST['veto_mute'])) {
      $game->veto('mute');
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['kill'])) {
      $game->kill($_POST['user_id']);
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['blind'])) {
      $game->blind($_POST['user_id']);
      header("Location: /game/play.php?code={$game_code}");
      return;
    }

    if(isset($_POST['mute'])) {
      $game->mute($_POST['user_id']);
      header("Location: /game/play.php?code={$game_code}");
      return;
    }
  ?>

  <form method="POST">
    <input type="submit" name="reassign_players" value="Re-assign players" >
  </form>
  <h1>Wiretap</h1>
  <h2><?= current_user()->username; ?></h2>
  <?php if(!$game->has_started): ?>
    <?php if($is_owner || $player->exists()): ?>
      <?php $players_query = Player::all_for_game($game_code); ?>
      Waiting for game to start -- Invite others with code "<?= $game_code; ?>".
      <?php if($is_owner): ?>
        <br><i>Once at least 4 players have joined, we can kick this thing off.</i>
        <?php if($players_query->num_rows >= 4): ?>
          <form method="POST">
            <input type="submit" name="start_game" value="Start the Game">
          </form>
        <?php endif; ?>
      <?php endif; ?>
      <ul>
        <?php while($row = $players_query->fetch_assoc()): ?>
          <li>
            <?php if($row['name_override']): ?>
              <?= $row['username'] . " (" . $row['name_override'] . ")"; ?>
            <?php else: ?>
              <?= $row['username']; ?>
            <?php endif; ?>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <form method="POST">
        <input type="submit" name="join" value="Join the game">
      </form>
    <?php endif; ?>
    <?php return; ?>
  <?php endif; ?>


  <div class="game-table">
    <?php include('game_state.php'); ?>
  </div>


  <?php if($player->exists()): ?>
    <b><?= "You are a " . $player->character_type; ?></b>
    <p>
      Sender and Receiver are Allies, everyone else is an Enemy.
    </p>
  
    <?php if($player->character_type == 'sender'): ?>
       Secret code: <?= $game->secret_code; ?>
       Hint code: <?= $game->hint_code; ?>
    <?php elseif($player->character_type == 'receiver'): ?>
       Hint code: <?= $game->hint_code; ?>
    <?php endif; ?>

    <br><br>

    <b>Known Signals</b>
    <i>Not all players will have signals. If you have signals, use them to figure out who is who.</i>
    <p><?= $player->known_signals; ?></p>   

    <br><br>

    <b>Attempt Unlock</b>
    <?php if($player->unlock_attempt): ?>
      <?= $player->unlock_attempt; ?>
    <?php else: ?>
      <i>Remeber, you only get one shot</i>
      <p>
        <form method="POST">
          <input type="text" name="secret_code" placeholder="5 digit number">
          <input type="submit" name="unlock_attempt" value="Attempt Unlock">
        </form>
      </p>
    <?php endif; ?>
  <?php else: ?>
    <b>This game has already started, but you can follow along.</b>
  <?php endif; ?>

  <hr>
  <br>
  <b>Rules</b>

</body>
