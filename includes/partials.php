<?php
require_once __DIR__ . '/functions.php';

/** @param array $game DB row (games table). @param array $owners [{id, username}] */
function render_game_card(array $game, array $owners = []): void
{
    $cover = $game['thumbnail'] ?: $game['image'];
    $rating = $game['bgg_rating'] ?? null;
    $ownerNames = $owners ? implode(', ', array_map(fn($o) => $o['username'], $owners)) : '';
    $categories = json_col($game['categories'] ?? null);
    $primaryCategory = $game['primary_category'] ?? null;
    if (!$primaryCategory || !in_array($primaryCategory, $categories, true)) {
        $primaryCategory = $categories[0] ?? null;
    }
    ?>
    <a class="game-card" href="/game.php?id=<?= (int) $game['id'] ?>">
      <?php if ($cover): ?>
        <img src="<?= h($cover) ?>" alt="<?= h($game['name']) ?>" loading="lazy">
      <?php else: ?>
        <div class="no-image">No image</div>
      <?php endif; ?>
      <?php if ($rating !== null): ?>
        <span class="rating-badge"><?= h(number_format((float) $rating, 1)) ?></span>
      <?php endif; ?>
      <div class="card-overlay">
        <p class="card-title"><?= h($game['name']) ?><?php if ($game['year_published']): ?> <span class="card-title-year">(<?= h($game['year_published']) ?>)</span><?php endif; ?></p>
        <?php if ($primaryCategory): ?>
          <p class="card-category"><?= h($primaryCategory) ?></p>
        <?php endif; ?>
        <?php if ($ownerNames): ?>
          <p class="card-sub"><?= h($ownerNames) ?></p>
        <?php endif; ?>
      </div>
    </a>
    <?php
}

/**
 * @param array<int, array{game?: array, owners?: array}> $entries either raw
 *        game rows, or {game, owners} pairs (as returned by
 *        get_playgroup_collection()).
 */
function render_game_grid(array $entries, string $emptyMessage = 'No games added yet.'): void
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

/**
 * A "Discover" grid card for a BGG hot-list item — not necessarily cached
 * locally yet, so it links through view-bgg.php instead of a local game id.
 * @param array{bggId:int, rank:int, name:string, yearPublished:?int, thumbnail:?string} $item
 */
function render_hot_game_card(array $item): void
{
    ?>
    <a class="game-card" href="/view-bgg.php?bggId=<?= (int) $item['bggId'] ?>">
      <?php if ($item['thumbnail']): ?>
        <img src="<?= h($item['thumbnail']) ?>" alt="<?= h($item['name']) ?>" loading="lazy">
      <?php else: ?>
        <div class="no-image">No image</div>
      <?php endif; ?>
      <span class="rating-badge">#<?= (int) $item['rank'] ?></span>
      <div class="card-overlay">
        <p class="card-title"><?= h($item['name']) ?><?php if ($item['yearPublished']): ?> <span class="card-title-year">(<?= h($item['yearPublished']) ?>)</span><?php endif; ?></p>
      </div>
    </a>
    <?php
}

/** A labeled row of tag chips (categories/mechanics), collapsed behind a "+N more" toggle past $visibleLimit. */
function render_tag_chips(string $label, array $tags, int $visibleLimit = 6): void
{
    if (empty($tags)) {
        return;
    }
    $icon = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2.5 12.5V2.5h10L20.59 10.6a2 2 0 0 1 0 2.82Z"></path><circle cx="7.5" cy="7.5" r="1.5"></circle></svg>';
    $extraCount = max(0, count($tags) - $visibleLimit);

    echo '<p class="section-label" style="margin-top:0.75rem;">' . h($label) . '</p>';
    echo '<div class="tag-chips">';
    foreach ($tags as $i => $tag) {
        $class = $i >= $visibleLimit ? 'tag-chip tag-chip-extra hidden' : 'tag-chip';
        echo '<div class="' . $class . '">' . $icon . h($tag) . '</div>';
    }
    if ($extraCount > 0) {
        echo '<button type="button" class="tag-chip tag-chip-toggle">+' . $extraCount . ' more</button>';
    }
    echo '</div>';
}

/** "Similar games" cards: cover + title + one-line tagline, in a responsive grid. */
function render_similar_game_cards(array $games): void
{
    echo '<div class="similar-cards">';
    foreach ($games as $g) {
        $cover = $g['thumbnail'] ?: $g['image'];
        echo '<a class="similar-card" href="/game.php?id=' . (int) $g['id'] . '">';
        if ($cover) {
            echo '<img src="' . h($cover) . '" alt="" loading="lazy">';
        } else {
            echo '<div class="no-image"></div>';
        }
        echo '<div class="similar-card-text"><p class="similar-card-title">' . h($g['name']) . '</p>';
        if (!empty($g['tagline'])) {
            echo '<p class="similar-card-tagline">' . h($g['tagline']) . '</p>';
        }
        echo '</div></a>';
    }
    echo '</div>';
}
