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

$stmt = db()->prepare('SELECT category_order FROM users WHERE id = ?');
$stmt->execute([$userId]);
$savedOrder = json_col($stmt->fetchColumn());

$orderedTypes = array_values(array_intersect($savedOrder, array_keys($gamesByType)));
$remainingTypes = array_diff(array_keys($gamesByType), $orderedTypes);
$orderedTypes = [...$orderedTypes, ...$remainingTypes];

$pageTitle = SITE_NAME;
$activeNav = 'collection';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <h1 style="margin:0;">My collection</h1>
  <div style="display:flex;gap:0.75rem;">
    <?php if (count($orderedTypes) > 1): ?>
      <button type="button" class="btn btn-secondary" data-open-modal="reorder-categories-modal">Reorder categories</button>
    <?php endif; ?>
    <button type="button" class="btn btn-accent" data-open-modal="add-game-modal">+ Add game</button>
  </div>
</div>

<?php if (empty($games)): ?>
  <p class="empty-state">You haven't added any games yet. Click 'Add game' to get started.</p>
<?php else: ?>
  <?php foreach ($orderedTypes as $type): ?>
    <h2 class="collection-section-title"><?= h($type) ?></h2>
    <?php render_game_grid($gamesByType[$type]); ?>
  <?php endforeach; ?>
  <?php if ($uncategorized): ?>
    <h2 class="collection-section-title">Uncategorized</h2>
    <?php render_game_grid($uncategorized); ?>
  <?php endif; ?>
<?php endif; ?>

<?php if (count($orderedTypes) > 1): ?>
  <div id="reorder-categories-modal" class="modal-backdrop hidden">
    <div class="modal">
      <div class="modal-head">
        <h2 style="margin:0;">Reorder categories</h2>
        <button type="button" class="modal-close" data-close-modal>&times;</button>
      </div>
      <p class="hint" style="margin-top:0;">Drag to set the order sections appear in on your collection page.</p>
      <ul id="category-order-list" class="category-order-list">
        <?php foreach ($orderedTypes as $type): ?>
          <li draggable="true" data-type="<?= h($type) ?>">
            <span class="drag-handle" aria-hidden="true">&#8942;&#8942;</span>
            <?= h($type) ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <p id="category-order-error" class="error hidden"></p>
      <button type="button" id="save-category-order" class="btn btn-accent">Save order</button>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
