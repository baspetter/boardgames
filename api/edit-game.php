<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/games.php';

require_login_json();
require_csrf();

$data = json_body();
$gameId = (int) ($data['gameId'] ?? 0);
if (!$gameId || !find_game($gameId)) {
    json_response(['error' => 'Spel niet gevonden'], 404);
}

$toIntOrNull = fn($v) => ($v !== null && $v !== '') ? (int) $v : null;
$toFloatOrNull = fn($v) => ($v !== null && $v !== '') ? (float) $v : null;

try {
    update_game($gameId, [
        'name' => $data['name'] ?? null,
        'image' => $data['image'] ?? null,
        'description' => $data['description'] ?? null,
        'yearPublished' => $toIntOrNull($data['yearPublished'] ?? null),
        'minPlayers' => $toIntOrNull($data['minPlayers'] ?? null),
        'maxPlayers' => $toIntOrNull($data['maxPlayers'] ?? null),
        'bestPlayers' => $toIntOrNull($data['bestPlayers'] ?? null),
        'playingTime' => $toIntOrNull($data['playingTime'] ?? null),
        'weight' => $toFloatOrNull($data['weight'] ?? null),
        'gameType' => $data['gameType'] ?? null,
        'howToPlayUrl' => $data['howToPlayUrl'] ?? null,
    ]);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
