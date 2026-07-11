<?php
require_once __DIR__ . '/includes/partials.php';

$userId = require_login();

$stmt = db()->prepare('SELECT * FROM games g JOIN collection_entries ce ON ce.game_id = g.id WHERE ce.user_id = ? ORDER BY ce.added_at DESC');
$stmt->execute([$userId]);
$games = $stmt->fetchAll();

$gamesByType = [];
$uncategorized = [];
foreach ($games as $g) {
    $types = json_col($g['categories']);
    if (empty($types)) {
        $uncategorized[] = $g;
        continue;
    }
    foreach ($types as $type) {
        $gamesByType[$type][] = $g;
    }
}
ksort($gamesByType, SORT_NATURAL | SORT_FLAG_CASE);

$pageTitle = SITE_NAME;
$activeNav = 'collection';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
  <h1 style="margin:0;">My collection</h1>
  <button type="button" class="btn btn-accent" data-open-modal="add-game-modal">+ Add game</button>
</div>

<?php if (empty($games)): ?>
  <p class="empty-state">You haven't added any games yet. Click 'Add game' to get started.</p>
<?php else: ?>
  <?php foreach ($gamesByType as $type => $typeGames): ?>
    <h2 class="collection-section-title"><?= h($type) ?></h2>
    <?php render_game_grid($typeGames); ?>
  <?php endforeach; ?>
  <?php if ($uncategorized): ?>
    <h2 class="collection-section-title">Uncategorized</h2>
    <?php render_game_grid($uncategorized); ?>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
