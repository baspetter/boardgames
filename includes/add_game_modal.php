<?php require_once __DIR__ . '/game-types.php'; ?>
<div id="add-game-modal" class="modal-backdrop hidden">
  <div class="modal">
    <div class="modal-head">
      <h2 style="margin:0;">Spel toevoegen</h2>
      <button type="button" class="modal-close" data-close-modal>&times;</button>
    </div>

    <div class="tabs">
      <button type="button" class="tab active" data-tab="bgg">Via BoardGameGeek</button>
      <button type="button" class="tab" data-tab="manual">Handmatig</button>
    </div>

    <div id="tab-bgg" class="tab-panel">
      <input type="text" id="bgg-search-input" placeholder="Zoek een bordspel op BoardGameGeek...">
      <div id="bgg-search-results" class="search-results"></div>

      <div class="divider"><hr>of<hr></div>

      <form id="bgg-link-form" style="display:flex;gap:0.5rem;">
        <input type="text" id="bgg-link-input" placeholder="Plak een BoardGameGeek-link of ID...">
        <button type="submit" class="btn btn-secondary">Toevoegen</button>
      </form>
      <p id="bgg-link-error" class="error hidden"></p>
      <p class="hint">Bijv. https://boardgamegeek.com/boardgame/13/catan</p>
    </div>

    <div id="tab-manual" class="tab-panel hidden">
      <form id="manual-game-form" class="form-stack" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Titel *" required>
        <input type="url" name="image" placeholder="Cover art URL">
        <div class="divider"><hr>of<hr></div>
        <div>
          <label class="hint" style="display:block;margin-bottom:0.25rem;">Cover art uploaden</label>
          <input type="file" name="imageFile" accept="image/*">
        </div>
        <textarea name="description" placeholder="Beschrijving" rows="3"></textarea>
        <div class="field-row field-row-4">
          <input type="number" name="yearPublished" placeholder="Jaartal" min="1000" max="3000">
          <input type="number" name="minPlayers" placeholder="Min. spelers" min="1">
          <input type="number" name="maxPlayers" placeholder="Max. spelers" min="1">
          <input type="number" name="bestPlayers" placeholder="Beste aantal" min="1">
        </div>
        <div class="field-row">
          <input type="number" name="playingTime" placeholder="Speeltijd (min)" min="1">
          <input type="number" name="weight" placeholder="Complexiteit (1-5)" min="1" max="5" step="0.01">
        </div>
        <div>
          <select name="gameType[]" multiple size="5">
            <?php foreach (GAME_TYPES as $type): ?>
              <option value="<?= h($type) ?>"><?= h($type) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="hint" style="margin-top:0.25rem;">Type spel — Ctrl/Cmd-klik om meerdere te selecteren.</p>
        </div>
        <input type="url" name="howToPlayUrl" placeholder="How to play video-link (YouTube, etc.)">
        <p id="manual-error" class="error hidden"></p>
        <button type="submit" class="btn btn-accent">Spel toevoegen</button>
      </form>
    </div>
  </div>
</div>
