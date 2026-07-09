<?php
require_once __DIR__ . '/../includes/functions.php';

$userId = require_login_json();
require_csrf();

$data = json_body();
$gameId = (int) ($data['gameId'] ?? 0);

db()->prepare('DELETE FROM collection_entries WHERE user_id = ? AND game_id = ?')
    ->execute([$userId, $gameId]);

json_response(['ok' => true]);
