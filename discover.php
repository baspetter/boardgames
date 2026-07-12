<?php
require_once __DIR__ . '/includes/partials.php';
require_once __DIR__ . '/includes/bgg.php';

require_login();

$hotList = [];
$error = null;
try {
    $hotList = bgg_get_hot_list_cached();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Discover - ' . SITE_NAME;
$activeNav = 'discover';
require __DIR__ . '/includes/header.php';
?>
<h1 style="margin:0 0 0.35rem;">Discover</h1>
<p class="hint" style="margin:0 0 1.5rem;">BoardGameGeek's trending "Hot Games" list, updated daily.</p>

<?php if ($error): ?>
  <p class="error"><?= h($error) ?></p>
<?php elseif (empty($hotList)): ?>
  <p class="empty-state">No hot games found right now.</p>
<?php else: ?>
  <div class="game-grid">
    <?php foreach ($hotList as $item): ?>
      <?php render_hot_game_card($item); ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
