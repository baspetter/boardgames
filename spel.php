<?php
require_once __DIR__ . '/includes/partials.php';
require_once __DIR__ . '/includes/collections.php';
require_once __DIR__ . '/includes/games.php';

$userId = require_login();
$gameId = (int) ($_GET['id'] ?? 0);

$game = find_game($gameId);
if (!$game) {
    http_response_code(404);
    exit('Spel niet gevonden');
}

$owners = get_visible_owners($gameId, $userId);
$stmt = db()->prepare('SELECT id FROM collection_entries WHERE user_id = ? AND game_id = ?');
$stmt->execute([$userId, $gameId]);
$myEntry = $stmt->fetch();

$categories = json_col($game['categories']);
$mechanics = json_col($game['mechanics']);
$designers = json_col($game['designers']);
$artists = json_col($game['artists']);

$pageTitle = $game['name'] . ' - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<div class="game-detail">
  <div>
    <div class="game-detail-cover">
      <?php if ($game['image']): ?>
        <img src="<?= h($game['image']) ?>" alt="<?= h($game['name']) ?>">
      <?php else: ?>
        <div class="no-image" style="aspect-ratio:3/4;">Geen afbeelding</div>
      <?php endif; ?>
    </div>
    <div class="game-detail-actions">
      <?php if ($myEntry): ?>
        <button type="button" class="btn btn-secondary" data-action="remove-game" data-game-id="<?= $gameId ?>">Verwijder uit mijn collectie</button>
      <?php else: ?>
        <button type="button" class="btn btn-accent" data-action="add-to-collection" data-game-id="<?= $gameId ?>">+ Toevoegen aan mijn collectie</button>
      <?php endif; ?>

      <a class="btn btn-secondary" href="<?= h($game['how_to_play_url'] ?: youtube_search_url($game['name'])) ?>" target="_blank" rel="noreferrer">&#9654; How to play</a>

      <button type="button" class="btn btn-secondary" data-open-modal="edit-game-modal">Bewerken</button>

      <?php if ($game['bgg_id']): ?>
        <button type="button" class="btn btn-secondary" data-action="refresh-bgg" data-game-id="<?= $gameId ?>">&#8635; Update with BGG</button>
        <a class="btn" style="color:var(--text-dimmer);text-align:center;" href="https://boardgamegeek.com/boardgame/<?= (int) $game['bgg_id'] ?>" target="_blank" rel="noreferrer">Bekijk op BoardGameGeek</a>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <h1 style="margin-top:0;"><?= h($game['name']) ?></h1>
    <div class="game-meta-row">
      <?php if ($game['year_published']): ?><span><?= (int) $game['year_published'] ?></span><?php endif; ?>
      <?php if ($game['min_players'] && $game['max_players']): ?>
        <span><?= $game['min_players'] == $game['max_players'] ? h($game['min_players'] . ' spelers') : h($game['min_players'] . '–' . $game['max_players'] . ' spelers') ?></span>
      <?php endif; ?>
      <?php if ($game['best_players']): ?><span>beste met <?= (int) $game['best_players'] ?></span><?php endif; ?>
      <?php if ($game['playing_time']): ?><span><?= (int) $game['playing_time'] ?> min</span><?php endif; ?>
      <?php if ($game['min_age']): ?><span><?= (int) $game['min_age'] ?>+</span><?php endif; ?>
      <?php if ($game['weight']): ?><span>Complexiteit <?= h(number_format((float) $game['weight'], 1)) ?>/5</span><?php endif; ?>
      <?php if ($game['bgg_rating']): ?><span class="rating">&#9733; <?= h(number_format((float) $game['bgg_rating'], 1)) ?></span><?php endif; ?>
    </div>

    <?php if ($categories || $mechanics): ?>
      <div class="tags">
        <?php foreach ([...$categories, ...$mechanics] as $tag): ?>
          <span class="tag"><?= h($tag) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($game['description']): ?>
      <p style="margin-top:1.5rem;max-width:48rem;white-space:pre-line;color:var(--text-dim);"><?= h($game['description']) ?></p>
    <?php endif; ?>

    <?php if ($designers || $artists): ?>
      <div class="detail-columns">
        <?php if ($designers): ?>
          <div>
            <p class="section-label">Ontwerpers</p>
            <ul style="padding-left:1.1rem;margin:0;">
              <?php foreach ($designers as $d): ?>
                <li><a href="<?= h(bgg_designer_url($d['bggId'])) ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($d['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if ($artists): ?>
          <div>
            <p class="section-label">Illustratoren</p>
            <ul style="padding-left:1.1rem;margin:0;">
              <?php foreach ($artists as $a): ?>
                <li><a href="<?= h(bgg_artist_url($a['bggId'])) ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($a['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($owners): ?>
      <div style="margin-top:1.5rem;">
        <p class="section-label">In bezit van</p>
        <div class="tags">
          <?php foreach ($owners as $o): ?><span class="pill"><?= h($o['username']) ?></span><?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <p style="margin-top:2rem;"><a href="/" class="hint">&larr; Terug naar collectie</a></p>
  </div>
</div>

<?php require __DIR__ . '/includes/edit_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
