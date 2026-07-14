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

$stmt = db()->prepare('SELECT id FROM wishlist_entries WHERE user_id = ? AND game_id = ?');
$stmt->execute([$userId, $gameId]);
$myWishlistEntry = $stmt->fetch();

$categories = json_col($game['categories']);
$mechanics = json_col($game['mechanics']);
$designers = json_col($game['designers']);
$artists = json_col($game['artists']);
$publishers = json_col($game['publishers']);
$expansions = resolve_expansion_links(json_col($game['expansions']), $userId);
$expansionOfLink = json_col($game['expansion_of'] ?? null);
$expansionOf = $expansionOfLink ? resolve_expansion_links([$expansionOfLink], $userId)[0] : null;

$similarTags = array_merge($categories, $mechanics);
$similarOwn = get_similar_games_in_collection($userId, $gameId, $similarTags);
$similarGroup = get_similar_games_in_playgroups($userId, $gameId, $similarTags);

$from = valid_nav_from($_GET['from'] ?? null);
$navLinks = nav_links();
$fromLabel = $from !== null ? $navLinks[$from][1] : null;
$navTitle = $fromLabel !== null ? $fromLabel . ' - ' . $game['name'] : $game['name'];
$pageTitle = $navTitle . ' - ' . SITE_NAME;
$activeNav = $from;
require __DIR__ . '/includes/header.php';
?>
<div class="game-main-row">
  <div>
    <div class="game-hero-cover">
      <?php if ($game['image']): ?>
        <img src="<?= h($game['image']) ?>" alt="<?= h($game['name']) ?>">
      <?php else: ?>
        <div class="no-image" style="aspect-ratio:3/4;">No image</div>
      <?php endif; ?>
      <button type="button" class="edit-icon-btn" data-open-modal="edit-game-modal" title="Edit game" aria-label="Edit game">&#9998;</button>
    </div>
    <div class="game-detail-actions">
      <?php if ($myEntry): ?>
        <button type="button" class="btn btn-secondary" data-action="remove-game" data-game-id="<?= $gameId ?>">Remove from my collection</button>
      <?php elseif ($myWishlistEntry): ?>
        <button type="button" class="btn btn-accent" data-action="move-to-collection" data-game-id="<?= $gameId ?>">+ Move to my collection</button>
        <button type="button" class="btn btn-secondary" data-action="remove-from-wishlist" data-game-id="<?= $gameId ?>">Remove from wishlist</button>
        <div class="buy-links">
          <span class="hint">Where to buy:</span>
          <a href="<?= h(google_shopping_search_url($game['name'])) ?>" target="_blank" rel="noreferrer">Google Shopping</a>
        </div>
      <?php else: ?>
        <button type="button" class="btn btn-accent" data-action="add-to-collection" data-game-id="<?= $gameId ?>">+ Add to my collection</button>
        <button type="button" class="btn btn-secondary" data-action="add-to-wishlist" data-game-id="<?= $gameId ?>">+ Add to wishlist</button>
      <?php endif; ?>

      <a class="btn btn-secondary" href="<?= h($game['how_to_play_url'] ?: youtube_search_url($game['name'])) ?>" target="_blank" rel="noreferrer">&#9654; How to play</a>

      <?php if ($game['bgg_id']): ?>
        <button type="button" class="btn btn-secondary" data-action="refresh-bgg" data-game-id="<?= $gameId ?>">&#8635; Update with BGG</button>
        <a class="btn" style="color:var(--text-dimmer);text-align:center;" href="https://boardgamegeek.com/boardgame/<?= (int) $game['bgg_id'] ?>" target="_blank" rel="noreferrer">View on BoardGameGeek</a>
      <?php else: ?>
        <form id="link-bgg-form" data-game-id="<?= $gameId ?>" style="display:flex;gap:0.5rem;">
          <input type="text" id="link-bgg-input" placeholder="Paste a BoardGameGeek link or ID...">
          <button type="submit" class="btn btn-secondary">Link to BGG</button>
        </form>
        <p id="link-bgg-error" class="error hidden"></p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <h1 style="margin:0;"><?= h($game['name']) ?></h1>
    <?php if ($expansionOf): ?>
      <p class="hint" style="margin:0.2rem 0 0;">
        Expansion of
        <?php if ($expansionOf['localGameId']): ?>
          <a href="<?= h(game_url((int) $expansionOf['localGameId'], $from)) ?>" style="color:var(--accent);"><?= h($expansionOf['name']) ?></a>
        <?php else: ?>
          <a href="https://boardgamegeek.com/boardgame/<?= (int) $expansionOf['bggId'] ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($expansionOf['name']) ?></a>
        <?php endif; ?>
      </p>
    <?php endif; ?>
    <?php if ($game['tagline']): ?>
      <p class="game-tagline"><?= h($game['tagline']) ?></p>
    <?php endif; ?>

    <div class="stat-chips">
      <?php if ($game['year_published']): ?>
        <div class="stat-chip">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          <div><span class="stat-value"><?= (int) $game['year_published'] ?></span><span class="stat-label">Year</span></div>
        </div>
      <?php endif; ?>
      <?php if ($game['min_players'] && $game['max_players']): ?>
        <div class="stat-chip">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <div>
            <span class="stat-value"><?= $game['min_players'] == $game['max_players'] ? h($game['min_players']) : h($game['min_players'] . '–' . $game['max_players']) ?></span>
            <span class="stat-label">Players</span>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($game['playing_time']): ?>
        <div class="stat-chip">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          <div><span class="stat-value"><?= (int) $game['playing_time'] ?> min</span><span class="stat-label">Time</span></div>
        </div>
      <?php endif; ?>
      <?php if ($game['min_age']): ?>
        <div class="stat-chip">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <div><span class="stat-value"><?= (int) $game['min_age'] ?>+</span><span class="stat-label">Age</span></div>
        </div>
      <?php endif; ?>
      <?php if ($game['weight']): ?>
        <div class="stat-chip">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="14"></line><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line></svg>
          <div><span class="stat-value"><?= h(number_format((float) $game['weight'], 2)) ?>/5</span><span class="stat-label">Weight</span></div>
        </div>
      <?php endif; ?>
      <?php if ($game['bgg_rating']): ?>
        <div class="stat-chip">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
          <div><span class="stat-value"><?= h(number_format((float) $game['bgg_rating'], 1)) ?></span><span class="stat-label">BGG rating</span></div>
        </div>
      <?php endif; ?>
    </div>

    <?php render_tag_chips('Categories', $categories); ?>
    <?php render_tag_chips('Mechanics', $mechanics); ?>

    <?php if ($game['description']): ?>
      <p class="game-description"><?= h($game['description']) ?></p>
    <?php endif; ?>

    <?php if ($owners): ?>
      <div style="margin-top:1.5rem;">
        <p class="section-label">Owned by</p>
        <div class="tags">
          <?php foreach ($owners as $o): ?><span class="pill"><?= h($o['username']) ?></span><?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($designers || $artists || $publishers): ?>
  <div class="detail-columns">
    <?php if ($designers): ?>
      <div>
        <p class="section-label">Designers</p>
        <ul style="padding-left:1.1rem;margin:0;">
          <?php foreach ($designers as $d): ?>
            <?php if (!empty($d['bggId'])): ?>
              <li><a href="<?= h(bgg_designer_url($d['bggId'])) ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($d['name']) ?></a></li>
            <?php else: ?>
              <li><?= h($d['name']) ?></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if ($artists): ?>
      <div>
        <p class="section-label">Artists</p>
        <ul style="padding-left:1.1rem;margin:0;">
          <?php foreach ($artists as $a): ?>
            <?php if (!empty($a['bggId'])): ?>
              <li><a href="<?= h(bgg_artist_url($a['bggId'])) ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($a['name']) ?></a></li>
            <?php else: ?>
              <li><?= h($a['name']) ?></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if ($publishers): ?>
      <div>
        <p class="section-label">Publishers</p>
        <ul style="padding-left:1.1rem;margin:0;">
          <?php foreach ($publishers as $p): ?>
            <?php if (!empty($p['bggId'])): ?>
              <li><a href="<?= h(bgg_publisher_url($p['bggId'])) ?>" target="_blank" rel="noreferrer" style="color:var(--accent);"><?= h($p['name']) ?></a></li>
            <?php else: ?>
              <li><?= h($p['name']) ?></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($expansions): ?>
  <h2 style="margin-top:2.5rem;">Expansions</h2>
  <ul class="expansions-list">
    <?php foreach ($expansions as $exp): ?>
      <li class="expansions-list-item">
        <?php if ($exp['localGameId']): ?>
          <a href="<?= h(game_url((int) $exp['localGameId'], $from)) ?>"><?= h($exp['name']) ?></a>
        <?php else: ?>
          <a href="https://boardgamegeek.com/boardgame/<?= (int) $exp['bggId'] ?>" target="_blank" rel="noreferrer"><?= h($exp['name']) ?></a>
        <?php endif; ?>
        <?php if (!$exp['owned']): ?>
          <button type="button" class="expansion-add-btn" data-bgg-id="<?= (int) $exp['bggId'] ?>">+ Add</button>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if ($similarOwn): ?>
  <h2 style="margin-top:2.5rem;">Similar in your collection</h2>
  <?php render_similar_game_cards($similarOwn, $from); ?>
<?php endif; ?>

<?php if ($similarGroup): ?>
  <h2 style="margin-top:2.5rem;">Similar in your playgroups</h2>
  <?php render_similar_game_cards($similarGroup, $from); ?>
<?php endif; ?>

<?php if ($from !== null): ?>
  <p style="margin-top:2rem;"><a href="<?= h($navLinks[$from][0]) ?>" class="hint">&larr; Back to <?= h($navLinks[$from][1]) ?></a></p>
<?php else: ?>
  <p style="margin-top:2rem;"><a href="/" class="hint">&larr; Back to collection</a></p>
<?php endif; ?>

<?php require __DIR__ . '/includes/edit_game_modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
