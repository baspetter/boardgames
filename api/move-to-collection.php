<?php
require_once __DIR__ . '/../includes/functions.php';

$userId = require_login_json();
require_csrf();

$data = json_body();
$gameId = (int) ($data['gameId'] ?? 0);

$pdo = db();
$pdo->beginTransaction();
$pdo->prepare(
    'INSERT INTO collection_entries (user_id, game_id) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE user_id = user_id'
)->execute([$userId, $gameId]);
$pdo->prepare('DELETE FROM wishlist_entries WHERE user_id = ? AND game_id = ?')
    ->execute([$userId, $gameId]);
$pdo->commit();

json_response(['ok' => true]);
