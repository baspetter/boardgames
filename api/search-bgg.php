<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bgg.php';

require_login_json();

$query = trim($_GET['q'] ?? '');
if (mb_strlen($query) < 2) {
    json_response([]);
}

try {
    $results = bgg_search($query);
    usort($results, fn($a, $b) => ($b['yearPublished'] ?? 0) <=> ($a['yearPublished'] ?? 0));
    json_response(array_slice($results, 0, 20));
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 502);
}
