<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bgg.php';
require_once __DIR__ . '/image.php';

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
        throw new Exception('Dit spel heeft geen BGG-koppeling om te verversen.');
    }
    $details = bgg_get_thing((int) $game['bgg_id']);
    return upsert_bgg_game($gameId, $details);
}

function upsert_bgg_game(?int $gameId, array $details): array
{
    $images = $details['image'] ? optimize_and_store_image($details['image'], $gameId ?? 0) : ['image' => null, 'thumbnail' => null];

    $fields = [
        'name' => $details['name'],
        'year_published' => $details['yearPublished'],
        'image' => $images['image'],
        'thumbnail' => $images['thumbnail'],
        'description' => $details['description'],
        'min_players' => $details['minPlayers'],
        'max_players' => $details['maxPlayers'],
        'best_players' => $details['bestPlayers'],
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
        'cached_at' => date('Y-m-d H:i:s'),
    ];

    $pdo = db();
    if ($gameId) {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
        $stmt = $pdo->prepare("UPDATE games SET $set WHERE id = :id");
        $stmt->execute([...$fields, 'id' => $gameId]);
    } else {
        $fields['bgg_id'] = $details['bggId'];
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

/** @param array{name:string,image?:?string,description?:?string,yearPublished?:?int,minPlayers?:?int,maxPlayers?:?int,bestPlayers?:?int,playingTime?:?int,weight?:?float,gameType?:?string[],howToPlayUrl?:?string} $input */
function create_manual_game(array $input): int
{
    $images = !empty($input['image']) ? optimize_and_store_image($input['image'], 0) : ['image' => null, 'thumbnail' => null];

    $categories = !empty($input['gameType']) ? array_values((array) $input['gameType']) : [];

    $stmt = db()->prepare(
        'INSERT INTO games (is_manual, name, year_published, image, thumbnail, description,
            min_players, max_players, best_players, playing_time, weight, categories, how_to_play_url)
         VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $input['name'],
        $input['yearPublished'] ?? null,
        $images['image'],
        $images['thumbnail'],
        $input['description'] ?? null,
        $input['minPlayers'] ?? null,
        $input['maxPlayers'] ?? null,
        $input['bestPlayers'] ?? null,
        $input['playingTime'] ?? null,
        $input['weight'] ?? null,
        json_encode($categories),
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
        'yearPublished' => 'year_published',
        'minPlayers' => 'min_players',
        'maxPlayers' => 'max_players',
        'bestPlayers' => 'best_players',
        'playingTime' => 'playing_time',
        'weight' => 'weight',
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

    if (!empty($input['image'])) {
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
