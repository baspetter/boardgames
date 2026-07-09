<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/games.php';

require_login_json();
require_csrf();

$data = json_body();
$gameId = (int) ($data['gameId'] ?? 0);

try {
    refresh_game_from_bgg($gameId);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
