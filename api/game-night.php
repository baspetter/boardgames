<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/playgroups.php';

$userId = require_login_json();
require_csrf();

$data = json_body();
$playGroupId = (int) ($data['playGroupId'] ?? 0);
$presentUserIds = array_map('intval', $data['presentUserIds'] ?? []);

if (!get_membership($userId, $playGroupId)) {
    json_response(['error' => 'No access to this group'], 403);
}

$members = get_playgroup_members($playGroupId);
$groupMemberIds = array_map(fn($m) => (int) $m['user_id'], $members);
$validPresentIds = array_values(array_intersect($presentUserIds, $groupMemberIds));
$playerCount = count($validPresentIds);

if ($playerCount === 0) {
    json_response(['playerCount' => 0, 'suggestions' => []]);
}

$placeholders = implode(',', array_fill(0, $playerCount, '?'));
$stmt = db()->prepare(
    "SELECT g.*, u.id AS owner_id, u.username AS owner_username
     FROM collection_entries ce
     JOIN games g ON g.id = ce.game_id
     JOIN users u ON u.id = ce.user_id
     WHERE ce.user_id IN ($placeholders) AND g.expansion_of IS NULL"
);
$stmt->execute($validPresentIds);

$byGame = [];
foreach ($stmt->fetchAll() as $row) {
    $gameId = (int) $row['id'];
    if (!isset($byGame[$gameId])) {
        $gameFields = $row;
        unset($gameFields['owner_id'], $gameFields['owner_username']);
        $byGame[$gameId] = ['game' => $gameFields, 'owners' => []];
    }
    $byGame[$gameId]['owners'][] = ['id' => $row['owner_id'], 'username' => $row['owner_username']];
}

$suggestions = array_values(array_filter($byGame, function ($entry) use ($playerCount) {
    $min = $entry['game']['min_players'] ?? 1;
    $max = $entry['game']['max_players'] ?? PHP_INT_MAX;
    return $playerCount >= $min && $playerCount <= $max;
}));

usort($suggestions, fn($a, $b) => ($b['game']['bgg_rating'] ?? 0) <=> ($a['game']['bgg_rating'] ?? 0));

json_response(['playerCount' => $playerCount, 'suggestions' => $suggestions]);
