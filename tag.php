<?php
require_once __DIR__ . '/includes/partials.php';
require_once __DIR__ . '/includes/games.php';

$userId = require_login();
$tag = trim($_GET['name'] ?? '');
if ($tag === '') {
    http_response_code(404);
    exit('Tag not found');
}

$ownedGames = get_tag_games_in_collection($userId, $tag);
$discoverGames = get_uncollected_games_by_tag($userId, $tag);

$pageTitle = $tag . ' - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<h1 style="margin:0 0 1.5rem;">Tag: <?= h($tag) ?></h1>

<?php render_game_grid($ownedGames, 'No one in your collection or playgroups has a game tagged "' . $tag . '" yet.'); ?>

<h2 style="margin-top:2.5rem;">More "<?= h($tag) ?>" games to discover</h2>
<p class="hint" style="margin:0 0 1rem;">Other games tagged "<?= h($tag) ?>" already known to My Game Circle that aren't in your collection or your playgroups' yet, sorted by BGG rating.</p>
<?php if ($discoverGames): ?>
  <div class="game-grid">
    <?php foreach ($discoverGames as $g): ?>
      <?php render_game_card($g); ?>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p class="empty-state">Nothing else tagged "<?= h($tag) ?>" yet.</p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
