<?php
require_once __DIR__ . '/includes/partials.php';
require_once __DIR__ . '/includes/collections.php';
require_once __DIR__ . '/includes/games.php';

$userId = require_login();
$gameId = (int) ($_GET['id'] ?? 0);

$game = find_game($gameId);
if (!$game) {
    http_response_code(404);
    exit('Game not found');
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
        <div class="no-image" style="aspect-ratio:3/4;">No image</div>
      <?php endif; ?>
      <button type="button" class="edit-icon-btn" data-open-modal="edit-game-modal" title="Edit game" aria-label="Edit game">&#9998;</button>
    </div>
    <div class="game-detail-actions">
      <?php if (!$myEntry): ?>
        <button type="button" class="btn btn-accent" data-action="add-to-collection" data-game-id="<?= $gameId ?>">+ Add to my collection</button>
      <?php endif; ?>

      <a class="btn btn-secondary" href="<?= h($game['how_to_play_url'] ?: youtube_search_url($game['name'])) ?>" target="_blank" rel="noreferrer">&#9654; How to play</a>

      <?php if ($game['bgg_id']): ?>
        <button type="button" class="btn btn-secondary" data-action="refresh-bgg" data-game-id="<?= $gameId ?>">&#8635; Update with BGG</button>
        <a class="btn" style="color:var(--text-dimmer);text-align:center;" href="https://boardgamegeek.com/boardgame/<?= (int) $game['bgg_id'] ?>" target="_blank" rel="noreferrer">View on BoardGameGeek</a>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <h1 style="margin-top:0;"><?= h($game['name']) ?></h1>
    <div class="game-meta-row">
      <?php if ($game['year_published']): ?><span><?= (int) $game['year_published'] ?></span><?php endif; ?>
      <?php if ($game['min_players'] && $game['max_players']): ?>
        <span><?= $game['min_players'] == $game['max_players'] ? h($game['min_players'] . ' players') : h($game['min_players'] . '–' . $game['max_players'] . ' players') ?></span>
      <?php endif; ?>
      <?php if ($game['best_players']): ?><span>best with <?= (int) $game['best_players'] ?></span><?php endif; ?>
      <?php if ($game['playing_time']): ?><span><?= (int) $game['playing_time'] ?> min</span><?php endif; ?>
      <?php if ($game['min_age']): ?><span><?= (int) $game['min_age'] ?>+</span><?php endif; ?>
      <?php if ($game['weight']): ?><span>Complexity <?= h(number_format((float) $game['weight'], 2)) ?>/5</span><?php endif; ?>
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

    <?php $embedUrl = youtube_embed_url($game['how_to_play_url']); ?>
    <?php if ($embedUrl): ?>
      <div class="video-embed">
        <iframe src="<?= h($embedUrl) ?>" title="How to play video" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
      </div>
    <?php endif; ?>

    <?php if ($designers || $artists): ?>
      <div class="detail-columns">
        <?php if ($designers): ?>
          <div>
            <p class="section-label">Designers</p>
            <ul style="padding-left:1.1rem;margin:0;">
              <?php foreach ($designers as $d): ?>
                <li><a href="<?= h(bgg_designer_url($d['bggId'])) ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($d['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if ($artists): ?>
          <div>
            <p class="section-label">Artists</p>
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
        <p class="section-label">Owned by</p>
        <div class="tags">
          <?php foreach ($owners as $o): ?><span class="pill"><?= h($o['username']) ?></span><?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <p style="margin-top:2rem;"><a href="/" class="hint">&larr; Back to collection</a></p>
  </div>
</div>

<?php require __DIR__ . '/includes/edit_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
