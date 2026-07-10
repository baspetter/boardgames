<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function get_user_playgroups(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT pg.*, COUNT(pgm2.id) AS member_count
         FROM play_groups pg
         JOIN play_group_members pgm ON pgm.play_group_id = pg.id AND pgm.user_id = ?
         JOIN play_group_members pgm2 ON pgm2.play_group_id = pg.id
         GROUP BY pg.id
         ORDER BY pg.created_at ASC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_playgroup(int $playGroupId): ?array
{
    $stmt = db()->prepare('SELECT * FROM play_groups WHERE id = ?');
    $stmt->execute([$playGroupId]);
    $group = $stmt->fetch();
    return $group ?: null;
}

function get_playgroup_members(int $playGroupId): array
{
    $stmt = db()->prepare(
        'SELECT pgm.*, u.username FROM play_group_members pgm
         JOIN users u ON u.id = pgm.user_id
         WHERE pgm.play_group_id = ?
         ORDER BY pgm.joined_at ASC'
    );
    $stmt->execute([$playGroupId]);
    return $stmt->fetchAll();
}

function get_membership(int $userId, int $playGroupId): ?array
{
    $stmt = db()->prepare('SELECT * FROM play_group_members WHERE user_id = ? AND play_group_id = ?');
    $stmt->execute([$userId, $playGroupId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_playgroup(string $name, int $userId): int
{
    $pdo = db();
    do {
        $code = generate_code(3);
        $stmt = $pdo->prepare('SELECT id FROM play_groups WHERE invite_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO play_groups (name, invite_code, created_by_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $code, $userId]);
        $groupId = (int) $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO play_group_members (user_id, play_group_id, role) VALUES (?, ?, "OWNER")')
            ->execute([$userId, $groupId]);

        $pdo->commit();
        return $groupId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** @return int the joined play_group_id */
function join_playgroup(string $inviteCode, int $userId): int
{
    $stmt = db()->prepare('SELECT * FROM play_groups WHERE invite_code = ?');
    $stmt->execute([strtoupper($inviteCode)]);
    $group = $stmt->fetch();
    if (!$group) {
        throw new Exception('Invalid group code');
    }

    if (get_membership($userId, (int) $group['id'])) {
        throw new Exception('You are already a member of this group');
    }

    db()->prepare('INSERT INTO play_group_members (user_id, play_group_id, role) VALUES (?, ?, "MEMBER")')
        ->execute([$userId, $group['id']]);

    return (int) $group['id'];
}

function regenerate_playgroup_code(int $playGroupId): string
{
    $pdo = db();
    do {
        $code = generate_code(3);
        $stmt = $pdo->prepare('SELECT id FROM play_groups WHERE invite_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    $pdo->prepare('UPDATE play_groups SET invite_code = ? WHERE id = ?')->execute([$code, $playGroupId]);
    return $code;
}
