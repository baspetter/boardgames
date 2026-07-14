<?php
require_once __DIR__ . '/includes/partials.php';
require_once __DIR__ . '/includes/bgg.php';

$userId = require_login();

$sortOptions = [
    'name' => 'g.name ASC',
    'rating' => '(g.bgg_rating IS NULL) ASC, g.bgg_rating DESC',
    'added' => 'we.added_at DESC',
];
$sort = $_GET['sort'] ?? 'name';
if (!isset($sortOptions[$sort])) {
    $sort = 'name';
}

$stmt = db()->prepare(
    "SELECT g.* FROM games g JOIN wishlist_entries we ON we.game_id = g.id
     WHERE we.user_id = ? ORDER BY {$sortOptions[$sort]}"
);
$stmt->execute([$userId]);
$games = $stmt->fetchAll();

$hotList = [];
$hotError = null;
try {
    $hotList = bgg_get_hot_list_cached();
} catch (Throwable $e) {
    $hotError = $e->getMessage();
}

$addTarget = 'wishlist';
$pageTitle = 'My wishlist - ' . SITE_NAME;
$activeNav = 'wishlist';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:flex-end;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <?php if ($games): ?>
    <form method="get" id="sort-form">
      <select name="sort" id="sort-select">
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>BGG rating (best first)</option>
        <option value="added" <?= $sort === 'added' ? 'selected' : '' ?>>Recently added</option>
      </select>
    </form>
  <?php endif; ?>
  <button type="button" class="btn btn-accent" data-open-modal="add-game-modal">+ Add to wishlist</button>
</div>

<?php render_game_grid($games, "Your wishlist is empty. Click 'Add to wishlist' for games you'd like to get.", 'wishlist'); ?>

<h2 style="margin-top:2.5rem;">Recommendations</h2>
<p class="hint" style="margin:0 0 1rem;">BoardGameGeek's trending "Hot Games" list, updated daily.</p>
<?php if ($hotError): ?>
  <p class="error"><?= h($hotError) ?></p>
<?php elseif (empty($hotList)): ?>
  <p class="empty-state">No recommendations found right now.</p>
<?php else: ?>
  <div class="game-grid">
    <?php foreach (array_slice($hotList, 0, 12) as $item): ?>
      <?php render_hot_game_card($item, 'wishlist'); ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
