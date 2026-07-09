<?php
require_once __DIR__ . '/includes/partials.php';

$userId = require_login();

$stmt = db()->prepare('SELECT * FROM games g JOIN collection_entries ce ON ce.game_id = g.id WHERE ce.user_id = ? ORDER BY ce.added_at DESC');
$stmt->execute([$userId]);
$games = $stmt->fetchAll();

$pageTitle = SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
  <h1 style="margin:0;">Mijn collectie</h1>
  <button type="button" class="btn btn-accent" data-open-modal="add-game-modal">+ Spel toevoegen</button>
</div>

<?php render_game_grid($games, "Je hebt nog geen spellen toegevoegd. Klik op 'Spel toevoegen' om te starten."); ?>

<?php require __DIR__ . '/includes/add_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
