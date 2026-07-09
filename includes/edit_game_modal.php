<?php
require_once __DIR__ . '/game-types.php';
/** @var array $game */
?>
<div id="edit-game-modal" class="modal-backdrop hidden">
  <div class="modal">
    <div class="modal-head">
      <h2 style="margin:0;">Spel bewerken</h2>
      <button type="button" class="modal-close" data-close-modal>&times;</button>
    </div>
    <form id="edit-game-form" class="form-stack">
      <input type="hidden" name="gameId" value="<?= (int) $game['id'] ?>">
      <input type="text" name="name" placeholder="Titel *" value="<?= h($game['name']) ?>" required>
      <input type="url" name="image" placeholder="Cover art URL (laat leeg om te behouden)">
      <textarea name="description" placeholder="Beschrijving" rows="3"><?= h($game['description'] ?? '') ?></textarea>
      <div class="field-row field-row-4">
        <input type="number" name="yearPublished" placeholder="Jaartal" min="1000" max="3000" value="<?= h((string) ($game['year_published'] ?? '')) ?>">
        <input type="number" name="minPlayers" placeholder="Min. spelers" min="1" value="<?= h((string) ($game['min_players'] ?? '')) ?>">
        <input type="number" name="maxPlayers" placeholder="Max. spelers" min="1" value="<?= h((string) ($game['max_players'] ?? '')) ?>">
        <input type="number" name="bestPlayers" placeholder="Beste aantal" min="1" value="<?= h((string) ($game['best_players'] ?? '')) ?>">
      </div>
      <div class="field-row">
        <input type="number" name="playingTime" placeholder="Speeltijd (min)" min="1" value="<?= h((string) ($game['playing_time'] ?? '')) ?>">
        <input type="number" name="weight" placeholder="Complexiteit (1-5)" min="1" max="5" step="0.01" value="<?= h((string) ($game['weight'] ?? '')) ?>">
      </div>
      <div>
        <select name="gameType[]" multiple size="5">
          <?php foreach (GAME_TYPES as $type): ?>
            <option value="<?= h($type) ?>"><?= h($type) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint" style="margin-top:0.25rem;">Type spel — Ctrl/Cmd-klik om meerdere te selecteren. Laat niets geselecteerd om bestaande categorieën/tags te behouden.</p>
      </div>
      <input type="url" name="howToPlayUrl" placeholder="How to play video-link" value="<?= h($game['how_to_play_url'] ?? '') ?>">
      <p id="edit-error" class="error hidden"></p>
      <button type="submit" class="btn btn-accent">Opslaan</button>
    </form>
    <p class="hint" style="margin-top:0.5rem;">Laat "Cover art URL" leeg om de huidige afbeelding te behouden.</p>
  </div>
</div>
