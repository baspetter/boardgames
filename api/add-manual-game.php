<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/games.php';

$userId = require_login_json();
require_csrf();

$data = $_POST;
$name = trim($data['name'] ?? '');
if ($name === '') {
    json_response(['error' => 'Title is required'], 400);
}

$toIntOrNull = fn($v) => ($v !== null && $v !== '') ? (int) $v : null;
$toFloatOrNull = fn($v) => ($v !== null && $v !== '') ? (float) $v : null;
$toStringOrNull = fn($v) => ($v !== null && trim((string) $v) !== '') ? trim((string) $v) : null;

try {
    $gameId = create_manual_game([
        'name' => $name,
        'image' => $data['image'] ?? null,
        'imageUploadPath' => uploaded_image_tmp_path('imageFile'),
        'description' => $data['description'] ?? null,
        'yearPublished' => $toIntOrNull($data['yearPublished'] ?? null),
        'minPlayers' => $toIntOrNull($data['minPlayers'] ?? null),
        'maxPlayers' => $toIntOrNull($data['maxPlayers'] ?? null),
        'bestPlayers' => $toStringOrNull($data['bestPlayers'] ?? null),
        'playingTime' => $toIntOrNull($data['playingTime'] ?? null),
        'weight' => $toFloatOrNull($data['weight'] ?? null),
        'gameType' => $data['gameType'] ?? null,
        'howToPlayUrl' => $data['howToPlayUrl'] ?? null,
    ]);

    db()->prepare('INSERT INTO collection_entries (user_id, game_id) VALUES (?, ?)')
        ->execute([$userId, $gameId]);

    json_response(['ok' => true, 'gameId' => $gameId]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
