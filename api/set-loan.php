<?php
require_once __DIR__ . '/../includes/functions.php';

$userId = require_login_json();
require_csrf();

$data = json_body();
$gameId = (int) ($data['gameId'] ?? 0);
$loanedTo = trim((string) ($data['loanedTo'] ?? ''));

if ($loanedTo === '') {
    db()->prepare('UPDATE collection_entries SET loaned_to = NULL, loaned_at = NULL WHERE user_id = ? AND game_id = ?')
        ->execute([$userId, $gameId]);
} else {
    db()->prepare('UPDATE collection_entries SET loaned_to = ?, loaned_at = NOW() WHERE user_id = ? AND game_id = ?')
        ->execute([$loanedTo, $userId, $gameId]);
}

json_response(['ok' => true]);
