<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/games.php';

require_login_json();
require_csrf();

$data = json_body();
$gameId = (int) ($data['gameId'] ?? 0);

$bggId = !empty($data['link']) ? bgg_extract_id((string) $data['link']) : null;
if ($bggId === null) {
    json_response(['error' => 'Could not recognize a BGG ID in this link.'], 400);
}

try {
    link_game_to_bgg($gameId, $bggId);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
