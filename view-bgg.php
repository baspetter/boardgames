<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/games.php';

require_login();
$bggId = (int) ($_GET['bggId'] ?? 0);
if ($bggId <= 0) {
    http_response_code(400);
    exit('Invalid BGG id');
}

try {
    $game = get_or_cache_game($bggId);
    redirect(game_url((int) $game['id'], $_GET['from'] ?? null));
} catch (Throwable $e) {
    http_response_code(502);
    exit('Could not load this game from BoardGameGeek: ' . h($e->getMessage()));
}
