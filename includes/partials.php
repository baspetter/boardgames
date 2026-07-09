<?php
require_once __DIR__ . '/functions.php';

/** @param array $game DB row (games table). @param array $owners [{id, username}] */
function render_game_card(array $game, array $owners = []): void
{
    $cover = $game['thumbnail'] ?: $game['image'];
    $rating = $game['bgg_rating'] ?? null;
    ?>
    <a class="game-card" href="/spel.php?id=<?= (int) $game['id'] ?>">
      <?php if ($cover): ?>
        <img src="<?= h($cover) ?>" alt="<?= h($game['name']) ?>" loading="lazy">
      <?php else: ?>
        <div class="no-image">Geen afbeelding</div>
      <?php endif; ?>
      <?php if ($rating !== null): ?>
        <span class="rating-badge"><?= h(number_format((float) $rating, 1)) ?></span>
      <?php endif; ?>
      <div class="meta">
        <p class="title"><?= h($game['name']) ?></p>
        <div class="sub">
          <span><?= h((string) ($game['year_published'] ?? '')) ?></span>
          <?php if ($owners): ?>
            <span><?= h(implode(', ', array_map(fn($o) => $o['username'], $owners))) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php
}

/**
 * @param array<int, array{game?: array, owners?: array}> $entries either raw
 *        game rows, or {game, owners} pairs (as returned by
 *        get_playgroup_collection()).
 */
function render_game_grid(array $entries, string $emptyMessage = 'Nog geen spellen toegevoegd.'): void
{
    if (empty($entries)) {
        echo '<p class="empty-state">' . h($emptyMessage) . '</p>';
        return;
    }
    echo '<div class="game-grid">';
    foreach ($entries as $entry) {
        if (isset($entry['game'])) {
            render_game_card($entry['game'], $entry['owners'] ?? []);
        } else {
            render_game_card($entry);
        }
    }
    echo '</div>';
}
