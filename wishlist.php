<?php
require_once __DIR__ . '/includes/partials.php';

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
     WHERE we.user_id = ? AND g.expansion_of IS NULL ORDER BY {$sortOptions[$sort]}"
);
$stmt->execute([$userId]);
$games = $stmt->fetchAll();

$addTarget = 'wishlist';
$pageTitle = 'Wishlist - ' . SITE_NAME;
$activeNav = 'wishlist';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <h1 style="margin:0;">Wishlist</h1>
  <div style="display:flex;align-items:center;gap:0.75rem;">
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
</div>

<?php render_game_grid($games, "Your wishlist is empty. Click 'Add to wishlist' for games you'd like to get."); ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
