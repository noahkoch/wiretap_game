<?php
  require "../../functions.php";
  $game_code = $_GET['code'];

  $game      = new Game($game_code);

  if(!$game->code) {
    header('Location: /');
    return;
  }

  $players = Player::all_for_game($game_code);
?>

<i>Last Updated <?= strftime('%l:%m:%S', time())?></i>
<?php if($game->unlocked_by): ?>
  <h3 style="background: green; color: white; padding: 5px">Lock State: Unlocked by <?= $game->unlocked_by; ?></h3>
<?php else: ?>
  <h3 style="background: red; padding: 5px">Lock State: Locked</h3>
<?php endif; ?>

<b>Veto</b>
<?php if(!$game->veto_used): ?>
  <?php if($game->kill_used): ?>
    <form method="POST">
      <input type="submit" name="veto_kill" value="Veto Kill" >
    </form>
  <?php endif; ?>

  <?php if($game->mute_used): ?>
    <form method="POST">
      <input type="submit" name="veto_mute" value="Veto Mute" >
    </form>
  <?php endif; ?>

  <?php if($game->blind_used): ?>
    <form method="POST">
      <input type="submit" name="veto_blind" value="Veto Blind" >
    </form>
  <?php endif; ?>
<?php endif; ?>
<table>
  <thead>
    <tr>
      <th>Player</th>
      <th>Unlock Attempt</th>
      <th>State</th>
    </tr>
  </thead>
  <tbody>
    <?php while($player_sql = $players->fetch_assoc()): ?>
      <tr>
        <?php $p = new Player($player_sql['user_id'], $game_code); ?>
        <td><?= $p->username; ?></td>
        <td><?= $p->unlock_attempt; ?></td>
        <td><?= $p->get_state(); ?></td>
        <td>
          <?php if(!$game->kill_used): ?>
            <form method="POST">
              <input type="hidden" name="user_id" value="<?= $p->user_id; ?>" >
              <input type="submit" name="kill" value="Kill" >
            </form>
          <?php endif; ?>

          <?php if(!$game->mute_used): ?>
            <form method="POST">
              <input type="hidden" name="user_id" value="<?= $p->user_id; ?>" >
              <input type="submit" name="mute" value="Mute" >
            </form>
          <?php endif; ?>

          <?php if(!$game->blind_used): ?>
            <form method="POST">
              <input type="hidden" name="user_id" value="<?= $p->user_id; ?>" >
              <input type="submit" name="blind" value="Blind" >
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
