<?php
require_once __DIR__ . '/includes/partials.php';

$userId = require_login();

$sortOptions = [
    'name' => 'g.name ASC',
    'rating' => '(g.bgg_rating IS NULL) ASC, g.bgg_rating DESC',
    'added' => 'ce.added_at DESC',
];
$sort = $_GET['sort'] ?? 'name';
if (!isset($sortOptions[$sort])) {
    $sort = 'name';
}

$stmt = db()->prepare(
    "SELECT g.* FROM games g JOIN collection_entries ce ON ce.game_id = g.id
     WHERE ce.user_id = ? AND (g.expansion_of IS NULL OR g.show_in_collection = 1) ORDER BY {$sortOptions[$sort]}"
);
$stmt->execute([$userId]);
$games = $stmt->fetchAll();

$pageTitle = SITE_NAME;
$activeNav = 'collection';
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
  <button type="button" class="btn btn-accent" data-open-modal="add-game-modal">+ Add game</button>
</div>

<?php render_game_grid($games, "You haven't added any games yet. Click 'Add game' to get started.", 'collection'); ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
