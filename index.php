<?php
require_once __DIR__ . '/includes/partials.php';

$userId = require_login();

$stmt = db()->prepare('SELECT * FROM games g JOIN collection_entries ce ON ce.game_id = g.id WHERE ce.user_id = ? ORDER BY ce.added_at DESC');
$stmt->execute([$userId]);
$games = $stmt->fetchAll();

$availableTypes = [];
foreach ($games as $g) {
    foreach (json_col($g['categories']) as $type) {
        $availableTypes[$type] = true;
    }
}
$availableTypes = array_keys($availableTypes);
sort($availableTypes);

$selectedType = trim((string) ($_GET['type'] ?? ''));
if ($selectedType !== '') {
    $games = array_values(array_filter(
        $games,
        fn($g) => in_array($selectedType, json_col($g['categories']), true)
    ));
}

$pageTitle = SITE_NAME;
$activeNav = 'collection';
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <h1 style="margin:0;">My collection</h1>
  <div style="display:flex;align-items:center;gap:0.75rem;">
    <?php if ($availableTypes): ?>
      <form method="get" id="type-filter-form">
        <select name="type" id="type-filter-select">
          <option value="">All types</option>
          <?php foreach ($availableTypes as $type): ?>
            <option value="<?= h($type) ?>" <?= $selectedType === $type ? 'selected' : '' ?>><?= h($type) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    <?php endif; ?>
    <button type="button" class="btn btn-accent" data-open-modal="add-game-modal">+ Add game</button>
  </div>
</div>

<?php render_game_grid($games, $selectedType !== '' ? "No games of type \"$selectedType\" in your collection." : "You haven't added any games yet. Click 'Add game' to get started."); ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
