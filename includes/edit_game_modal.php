<?php
require_once __DIR__ . '/game-types.php';
/** @var array $game */
/** @var array|false|null $myEntry */
?>
<div id="edit-game-modal" class="modal-backdrop hidden">
  <div class="modal">
    <div class="modal-head">
      <h2 style="margin:0;">Edit game</h2>
      <button type="button" class="modal-close" data-close-modal>&times;</button>
    </div>
    <form id="edit-game-form" class="form-stack" enctype="multipart/form-data">
      <input type="hidden" name="gameId" value="<?= (int) $game['id'] ?>">
      <input type="text" name="name" placeholder="Title *" value="<?= h($game['name']) ?>" required>
      <input type="url" name="image" placeholder="Cover art URL (leave empty to keep current)">
      <div class="divider"><hr>or<hr></div>
      <div>
        <label class="hint" style="display:block;margin-bottom:0.25rem;">Upload new cover art</label>
        <input type="file" name="imageFile" accept="image/*">
      </div>
      <textarea name="description" placeholder="Description" rows="3"><?= h($game['description'] ?? '') ?></textarea>
      <div class="field-row field-row-4">
        <div><label class="field-label">Year</label><input type="number" name="yearPublished" placeholder="Year" min="1000" max="3000" value="<?= h((string) ($game['year_published'] ?? '')) ?>"></div>
        <div><label class="field-label">Min. players</label><input type="number" name="minPlayers" placeholder="Min. players" min="1" value="<?= h((string) ($game['min_players'] ?? '')) ?>"></div>
        <div><label class="field-label">Max. players</label><input type="number" name="maxPlayers" placeholder="Max. players" min="1" value="<?= h((string) ($game['max_players'] ?? '')) ?>"></div>
        <div><label class="field-label">Best player count</label><input type="text" name="bestPlayers" placeholder="e.g. 3 or 2-4" inputmode="numeric" value="<?= h((string) ($game['best_players'] ?? '')) ?>"></div>
      </div>
      <div class="field-row">
        <div><label class="field-label">Playing time (min)</label><input type="number" name="playingTime" placeholder="Playing time (min)" min="1" value="<?= h((string) ($game['playing_time'] ?? '')) ?>"></div>
        <div><label class="field-label">Complexity (1-5)</label><input type="text" name="weight" placeholder="Complexity (1-5)" inputmode="decimal" pattern="[0-9]*\.?[0-9]*" value="<?= h((string) ($game['weight'] ?? '')) ?>"></div>
      </div>
      <div>
        <select name="gameType[]" multiple size="5">
          <?php foreach (GAME_TYPES as $type): ?>
            <option value="<?= h($type) ?>"><?= h($type) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint" style="margin-top:0.25rem;">Game type — Ctrl/Cmd-click to select multiple. Leave nothing selected to keep existing categories/tags.</p>
      </div>
      <input type="url" name="howToPlayUrl" placeholder="How to play video link" value="<?= h($game['how_to_play_url'] ?? '') ?>">
      <p id="edit-error" class="error hidden"></p>
      <button type="submit" class="btn btn-accent">Save</button>
    </form>
    <p class="hint" style="margin-top:0.5rem;">Leave "Cover art URL" empty to keep the current image.</p>

    <?php if ($myEntry): ?>
      <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border);">
        <button type="button" class="btn btn-secondary" data-action="remove-game" data-game-id="<?= (int) $game['id'] ?>">Remove from my collection</button>
      </div>
    <?php endif; ?>
  </div>
</div>
