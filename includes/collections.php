<?php
require_once __DIR__ . '/db.php';

/** Users (within playgroups shared with $userId) who own $gameId. */
function get_visible_owners(int $gameId, int $userId): array
{
    $stmt = db()->prepare(
        'SELECT DISTINCT u.id, u.username
         FROM collection_entries ce
         JOIN users u ON u.id = ce.user_id
         WHERE ce.game_id = :game_id
           AND ce.user_id IN (
             SELECT pgm2.user_id
             FROM play_group_members pgm1
             JOIN play_group_members pgm2 ON pgm2.play_group_id = pgm1.play_group_id
             WHERE pgm1.user_id = :user_id
           )'
    );
    $stmt->execute(['game_id' => $gameId, 'user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * All games owned by anyone in $playGroupId, each with its list of owners
 * (username + id) within that group.
 *
 * @return array<int, array{game: array, owners: array}>
 */
function get_playgroup_collection(int $playGroupId): array
{
    $stmt = db()->prepare(
        'SELECT g.*, u.id AS owner_id, u.username AS owner_username
         FROM collection_entries ce
         JOIN games g ON g.id = ce.game_id
         JOIN users u ON u.id = ce.user_id
         JOIN play_group_members pgm ON pgm.user_id = ce.user_id
         WHERE pgm.play_group_id = ? AND g.expansion_of IS NULL
         ORDER BY ce.added_at DESC'
    );
    $stmt->execute([$playGroupId]);

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

    return array_values($byGame);
}
