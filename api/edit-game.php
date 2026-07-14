<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/games.php';

require_login_json();
require_csrf();

$data = $_POST;
$gameId = (int) ($data['gameId'] ?? 0);
if (!$gameId || !find_game($gameId)) {
    json_response(['error' => 'Game not found'], 404);
}

$toIntOrNull = fn($v) => ($v !== null && $v !== '') ? (int) $v : null;
$toFloatOrNull = fn($v) => ($v !== null && $v !== '') ? (float) $v : null;
$toStringOrNull = fn($v) => ($v !== null && trim((string) $v) !== '') ? trim((string) $v) : null;

try {
    update_game($gameId, [
        'name' => $data['name'] ?? null,
        'image' => $data['image'] ?? null,
        'imageUploadPath' => uploaded_image_tmp_path('imageFile'),
        'description' => $data['description'] ?? null,
        'tagline' => $toStringOrNull($data['tagline'] ?? null),
        'yearPublished' => $toIntOrNull($data['yearPublished'] ?? null),
        'minPlayers' => $toIntOrNull($data['minPlayers'] ?? null),
        'maxPlayers' => $toIntOrNull($data['maxPlayers'] ?? null),
        'playingTime' => $toIntOrNull($data['playingTime'] ?? null),
        'weight' => $toFloatOrNull($data['weight'] ?? null),
        'bggRating' => $toFloatOrNull($data['bggRating'] ?? null),
        'gameType' => $data['gameType'] ?? null,
        'primaryCategory' => $toStringOrNull($data['primaryCategory'] ?? null),
        'designers' => $data['designers'] ?? null,
        'artists' => $data['artists'] ?? null,
        'publishers' => $data['publishers'] ?? null,
        'howToPlayUrl' => $data['howToPlayUrl'] ?? null,
        'showInCollection' => isset($data['showInCollection']) ? 1 : 0,
    ]);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
