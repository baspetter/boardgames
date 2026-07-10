<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/games.php';

$userId = require_login_json();
require_csrf();

$data = json_body();

try {
    if (!empty($data['link'])) {
        $bggId = bgg_extract_id((string) $data['link']);
        if ($bggId === null) {
            json_response(['error' => 'Could not recognize a BGG ID in this link.'], 400);
        }
        $game = get_or_cache_game($bggId);
    } elseif (!empty($data['bggId'])) {
        $game = get_or_cache_game((int) $data['bggId']);
    } elseif (!empty($data['gameId'])) {
        $game = find_game((int) $data['gameId']);
        if (!$game) {
            json_response(['error' => 'Game not found'], 404);
        }
    } else {
        json_response(['error' => 'No game specified'], 400);
    }

    db()->prepare(
        'INSERT INTO collection_entries (user_id, game_id) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE user_id = user_id'
    )->execute([$userId, $game['id']]);

    json_response(['ok' => true, 'gameId' => $game['id']]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}
