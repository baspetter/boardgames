<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bgg.php';
require_once __DIR__ . '/image.php';
require_once __DIR__ . '/functions.php';

const GAME_CACHE_TTL_DAYS = 30;

function find_game_by_bgg_id(int $bggId): ?array
{
    $stmt = db()->prepare('SELECT * FROM games WHERE bgg_id = ?');
    $stmt->execute([$bggId]);
    $game = $stmt->fetch();
    return $game ?: null;
}

function find_game(int $gameId): ?array
{
    $stmt = db()->prepare('SELECT * FROM games WHERE id = ?');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    return $game ?: null;
}

/**
 * Augments a list of {bggId,name} link stubs (e.g. a game's expansions) with
 * whether we already have that game cached locally, and whether $userId
 * already owns it — so the UI can link internally and hide "+ Add" for it.
 * @param array<int, array{bggId:int,name:string}> $links
 */
function resolve_expansion_links(array $links, int $userId): array
{
    $resolved = [];
    foreach ($links as $link) {
        $localGame = find_game_by_bgg_id((int) $link['bggId']);
        $owned = false;
        if ($localGame) {
            $stmt = db()->prepare('SELECT 1 FROM collection_entries WHERE user_id = ? AND game_id = ?');
            $stmt->execute([$userId, $localGame['id']]);
            $owned = (bool) $stmt->fetchColumn();
        }
        $resolved[] = [
            'bggId' => (int) $link['bggId'],
            'name' => $link['name'],
            'localGameId' => $localGame['id'] ?? null,
            'owned' => $owned,
        ];
    }
    return $resolved;
}

/** Fetches a game from cache, or from BGG (+ locally optimizes its image) if stale/missing. */
function get_or_cache_game(int $bggId): array
{
    $existing = find_game_by_bgg_id($bggId);
    if ($existing) {
        $ageDays = (time() - strtotime($existing['cached_at'])) / 86400;
        if ($ageDays < GAME_CACHE_TTL_DAYS) {
            return $existing;
        }
    }

    $details = bgg_get_thing($bggId);
    $gameId = $existing ? (int) $existing['id'] : null;
    return upsert_bgg_game($gameId, $details);
}

function refresh_game_from_bgg(int $gameId): array
{
    $game = find_game($gameId);
    if (!$game || $game['bgg_id'] === null) {
        throw new Exception('This game has no BGG link to refresh from.');
    }
    $details = bgg_get_thing((int) $game['bgg_id']);
    return upsert_bgg_game($gameId, $details);
}

/** Links an existing (typically manually-added) game to a BGG entry, filling in its data. */
function link_game_to_bgg(int $gameId, int $bggId): array
{
    $game = find_game($gameId);
    if (!$game) {
        throw new Exception('Game not found.');
    }
    if ($game['bgg_id'] !== null) {
        throw new Exception('This game is already linked to BoardGameGeek.');
    }
    $existing = find_game_by_bgg_id($bggId);
    if ($existing) {
        throw new Exception('That BoardGameGeek game is already in the system as "' . $existing['name'] . '".');
    }
    $details = bgg_get_thing($bggId);
    return upsert_bgg_game($gameId, $details);
}

function upsert_bgg_game(?int $gameId, array $details): array
{
    $images = $details['image'] ? optimize_and_store_image($details['image'], $gameId ?? 0) : ['image' => null, 'thumbnail' => null];

    $fields = [
        'bgg_id' => $details['bggId'],
        'is_manual' => 0,
        'name' => $details['name'],
        'year_published' => $details['yearPublished'],
        'image' => $images['image'],
        'thumbnail' => $images['thumbnail'],
        'description' => $details['description'],
        'min_players' => $details['minPlayers'],
        'max_players' => $details['maxPlayers'],
        'playing_time' => $details['playingTime'],
        'min_play_time' => $details['minPlayTime'],
        'max_play_time' => $details['maxPlayTime'],
        'min_age' => $details['minAge'],
        'weight' => $details['weight'],
        'bgg_rating' => $details['bggRating'],
        'bgg_rank' => $details['bggRank'],
        'categories' => json_encode($details['categories']),
        'mechanics' => json_encode($details['mechanics']),
        'designers' => json_encode($details['designers']),
        'artists' => json_encode($details['artists']),
        'publishers' => json_encode($details['publishers']),
        'expansions' => json_encode($details['expansions']),
        'expansion_of' => $details['expansionOf'] !== null ? json_encode($details['expansionOf']) : null,
        'cached_at' => date('Y-m-d H:i:s'),
    ];

    $pdo = db();
    if ($gameId) {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
        $stmt = $pdo->prepare("UPDATE games SET $set WHERE id = :id");
        $stmt->execute([...$fields, 'id' => $gameId]);
    } else {
        // Only set the tagline on first import — never overwrite a user's own
        // edit to it on a later "Update with BGG" refresh.
        $fields['tagline'] = $details['tagline'] ?? null;
        $cols = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
        $stmt = $pdo->prepare("INSERT INTO games ($cols) VALUES ($placeholders)");
        $stmt->execute($fields);
        $gameId = (int) $pdo->lastInsertId();
    }

    // The image was optimized before we had a final gameId for a brand new
    // game (filenames just need to be unique, not tied to the id), so no
    // follow-up needed either way.
    return find_game($gameId);
}

/** Parses a comma-separated names string (e.g. "Klaus Teuber, Uwe Rosenberg") into [{"name":...}, ...]. */
function parse_names_list(string $raw): array
{
    $names = array_filter(array_map('trim', explode(',', $raw)), fn($n) => $n !== '');
    return array_values(array_map(fn($n) => ['name' => $n], $names));
}

/** @param array{name:string,image?:?string,imageUploadPath?:?string,description?:?string,tagline?:?string,yearPublished?:?int,minPlayers?:?int,maxPlayers?:?int,playingTime?:?int,weight?:?float,bggRating?:?float,gameType?:?string[],primaryCategory?:?string,designers?:?string,artists?:?string,howToPlayUrl?:?string} $input */
function create_manual_game(array $input): int
{
    if (!empty($input['imageUploadPath'])) {
        $images = optimize_and_store_uploaded_image($input['imageUploadPath'], 0);
    } elseif (!empty($input['image'])) {
        $images = optimize_and_store_image($input['image'], 0);
    } else {
        $images = ['image' => null, 'thumbnail' => null];
    }

    $categories = !empty($input['gameType']) ? array_values((array) $input['gameType']) : [];
    $designers = !empty($input['designers']) ? parse_names_list($input['designers']) : [];
    $artists = !empty($input['artists']) ? parse_names_list($input['artists']) : [];
    $publishers = !empty($input['publishers']) ? parse_names_list($input['publishers']) : [];
    $primaryCategory = (!empty($input['primaryCategory']) && in_array($input['primaryCategory'], $categories, true))
        ? $input['primaryCategory']
        : null;

    $stmt = db()->prepare(
        'INSERT INTO games (is_manual, name, year_published, image, thumbnail, description, tagline,
            min_players, max_players, playing_time, weight, bgg_rating, categories, primary_category, designers, artists, publishers, how_to_play_url)
         VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $input['name'],
        $input['yearPublished'] ?? null,
        $images['image'],
        $images['thumbnail'],
        $input['description'] ?? null,
        $input['tagline'] ?? null,
        $input['minPlayers'] ?? null,
        $input['maxPlayers'] ?? null,
        $input['playingTime'] ?? null,
        $input['weight'] ?? null,
        $input['bggRating'] ?? null,
        json_encode($categories),
        $primaryCategory,
        json_encode($designers),
        json_encode($artists),
        json_encode($publishers),
        $input['howToPlayUrl'] ?? null,
    ]);

    return (int) db()->lastInsertId();
}

/** Partial update — only provided (non-null) keys are changed. */
function update_game(int $gameId, array $input): void
{
    $columnMap = [
        'name' => 'name',
        'description' => 'description',
        'tagline' => 'tagline',
        'yearPublished' => 'year_published',
        'minPlayers' => 'min_players',
        'maxPlayers' => 'max_players',
        'playingTime' => 'playing_time',
        'weight' => 'weight',
        'bggRating' => 'bgg_rating',
        'howToPlayUrl' => 'how_to_play_url',
    ];

    $set = [];
    $params = [];
    foreach ($columnMap as $inputKey => $column) {
        if (array_key_exists($inputKey, $input) && $input[$inputKey] !== null && $input[$inputKey] !== '') {
            $set[] = "$column = ?";
            $params[] = $input[$inputKey];
        }
    }

    if (!empty($input['gameType'])) {
        $set[] = 'categories = ?';
        $params[] = json_encode(array_values((array) $input['gameType']));
    }

    if (!empty($input['primaryCategory'])) {
        if (!empty($input['gameType'])) {
            $finalCategories = array_values((array) $input['gameType']);
        } else {
            $stmt = db()->prepare('SELECT categories FROM games WHERE id = ?');
            $stmt->execute([$gameId]);
            $finalCategories = json_col($stmt->fetchColumn());
        }
        if (in_array($input['primaryCategory'], $finalCategories, true)) {
            $set[] = 'primary_category = ?';
            $params[] = $input['primaryCategory'];
        }
    }

    if (array_key_exists('designers', $input) && $input['designers'] !== null) {
        $set[] = 'designers = ?';
        $params[] = json_encode(parse_names_list($input['designers']));
    }

    if (array_key_exists('artists', $input) && $input['artists'] !== null) {
        $set[] = 'artists = ?';
        $params[] = json_encode(parse_names_list($input['artists']));
    }

    if (array_key_exists('publishers', $input) && $input['publishers'] !== null) {
        $set[] = 'publishers = ?';
        $params[] = json_encode(parse_names_list($input['publishers']));
    }

    if (!empty($input['imageUploadPath'])) {
        $images = optimize_and_store_uploaded_image($input['imageUploadPath'], $gameId);
        if ($images['image']) {
            $set[] = 'image = ?';
            $params[] = $images['image'];
            $set[] = 'thumbnail = ?';
            $params[] = $images['thumbnail'];
        }
    } elseif (!empty($input['image'])) {
        $images = optimize_and_store_image($input['image'], $gameId);
        if ($images['image']) {
            $set[] = 'image = ?';
            $params[] = $images['image'];
            $set[] = 'thumbnail = ?';
            $params[] = $images['thumbnail'];
        }
    }

    if (empty($set)) {
        return;
    }

    $params[] = $gameId;
    $sql = 'UPDATE games SET ' . implode(', ', $set) . ' WHERE id = ?';
    db()->prepare($sql)->execute($params);
}

/** Games (excluding $excludeGameId) from $userId's own collection, ranked by shared category/mechanic tags with $tags. */
function get_similar_games_in_collection(int $userId, int $excludeGameId, array $tags, int $limit = 6): array
{
    if (empty($tags)) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT g.* FROM collection_entries ce
         JOIN games g ON g.id = ce.game_id
         WHERE ce.user_id = ? AND g.id != ? AND g.expansion_of IS NULL'
    );
    $stmt->execute([$userId, $excludeGameId]);
    return rank_games_by_tag_overlap($stmt->fetchAll(), $tags, $limit);
}

/** Games (excluding $excludeGameId and anything already in $userId's own collection) owned by $userId's playgroup-mates, ranked by shared tags. */
function get_similar_games_in_playgroups(int $userId, int $excludeGameId, array $tags, int $limit = 6): array
{
    if (empty($tags)) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT DISTINCT g.* FROM collection_entries ce
         JOIN games g ON g.id = ce.game_id
         WHERE ce.user_id IN (
           SELECT pgm2.user_id FROM play_group_members pgm1
           JOIN play_group_members pgm2 ON pgm2.play_group_id = pgm1.play_group_id
           WHERE pgm1.user_id = ?
         )
         AND ce.user_id != ?
         AND g.id != ?
         AND g.expansion_of IS NULL
         AND g.id NOT IN (SELECT game_id FROM collection_entries WHERE user_id = ?)'
    );
    $stmt->execute([$userId, $userId, $excludeGameId, $userId]);
    return rank_games_by_tag_overlap($stmt->fetchAll(), $tags, $limit);
}

/** Sorts $games by number of tags (case-insensitive) they share with $tags, dropping non-matches. */
function rank_games_by_tag_overlap(array $games, array $tags, int $limit): array
{
    $tagSet = array_map('mb_strtolower', $tags);
    $scored = [];
    foreach ($games as $g) {
        $gameTags = array_map('mb_strtolower', array_merge(json_col($g['categories']), json_col($g['mechanics'])));
        $overlap = count(array_intersect($tagSet, $gameTags));
        if ($overlap > 0) {
            $scored[] = ['game' => $g, 'overlap' => $overlap];
        }
    }
    usort($scored, fn($a, $b) => $b['overlap'] <=> $a['overlap'] ?: ($b['game']['bgg_rating'] <=> $a['game']['bgg_rating']));
    return array_slice(array_column($scored, 'game'), 0, $limit);
}
