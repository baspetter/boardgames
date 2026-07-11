<?php require_once __DIR__ . '/game-types.php'; ?>
<div id="add-game-modal" class="modal-backdrop hidden">
  <div class="modal">
    <div class="modal-head">
      <h2 style="margin:0;">Add game</h2>
      <button type="button" class="modal-close" data-close-modal>&times;</button>
    </div>

    <div class="tabs">
      <button type="button" class="tab active" data-tab="bgg">Via BoardGameGeek</button>
      <button type="button" class="tab" data-tab="manual">Manual</button>
    </div>

    <div id="tab-bgg" class="tab-panel">
      <input type="text" id="bgg-search-input" placeholder="Search for a board game on BoardGameGeek...">
      <div id="bgg-search-results" class="search-results"></div>

      <div class="divider"><hr>or<hr></div>

      <form id="bgg-link-form" style="display:flex;gap:0.5rem;">
        <input type="text" id="bgg-link-input" placeholder="Paste a BoardGameGeek link or ID...">
        <button type="submit" class="btn btn-secondary">Add</button>
      </form>
      <p id="bgg-link-error" class="error hidden"></p>
      <p class="hint">E.g. https://boardgamegeek.com/boardgame/13/catan</p>
    </div>

    <div id="tab-manual" class="tab-panel hidden">
      <form id="manual-game-form" class="form-stack" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Title *" required>
        <input type="url" name="image" placeholder="Cover art URL">
        <div class="divider"><hr>or<hr></div>
        <div>
          <label class="hint" style="display:block;margin-bottom:0.25rem;">Upload cover art</label>
          <input type="file" name="imageFile" accept="image/*">
        </div>
        <textarea name="description" placeholder="Description" rows="3"></textarea>
        <div class="field-row field-row-4">
          <input type="number" name="yearPublished" placeholder="Year" min="1000" max="3000">
          <input type="number" name="minPlayers" placeholder="Min. players" min="1">
          <input type="number" name="maxPlayers" placeholder="Max. players" min="1">
          <input type="text" name="bestPlayers" placeholder="Best player count (e.g. 3 or 2-4)" inputmode="numeric">
        </div>
        <div class="field-row">
          <input type="number" name="playingTime" placeholder="Playing time (min)" min="1">
          <input type="text" name="weight" placeholder="Complexity (1-5)" inputmode="decimal" pattern="[0-9]*\.?[0-9]*">
        </div>
        <div>
          <select name="gameType[]" multiple size="5">
            <?php foreach (GAME_TYPES as $type): ?>
              <option value="<?= h($type) ?>"><?= h($type) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="hint" style="margin-top:0.25rem;">Game type — Ctrl/Cmd-click to select multiple.</p>
        </div>
        <input type="url" name="howToPlayUrl" placeholder="How to play video link (YouTube, etc.)">
        <p id="manual-error" class="error hidden"></p>
        <button type="submit" class="btn btn-accent">Add game</button>
      </form>
    </div>
  </div>
</div>
