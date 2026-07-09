<?php
require_once __DIR__ . '/db.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function current_user_id(): ?int
{
    start_session();
    return $_SESSION['user_id'] ?? null;
}

function current_username(): ?string
{
    start_session();
    return $_SESSION['username'] ?? null;
}

function require_login(): int
{
    $userId = current_user_id();
    if ($userId === null) {
        $callback = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?callback=' . $callback);
        exit;
    }
    return $userId;
}

function login_user(int $userId, string $username): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function create_user(string $email, string $username, string $password, int $inviteCodeId): int
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, username, password_hash, invite_code_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $username, $hash, $inviteCodeId]);
        $userId = (int) $pdo->lastInsertId();

        $pdo->prepare('UPDATE invite_codes SET uses_count = uses_count + 1 WHERE id = ?')
            ->execute([$inviteCodeId]);

        $pdo->commit();
        return $userId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Validates an invite code for registration. Returns the invite_codes row id
 * on success, or null (with $error set) on failure.
 */
function validate_invite_code(string $code, ?string &$error): ?int
{
    $stmt = db()->prepare('SELECT * FROM invite_codes WHERE code = ?');
    $stmt->execute([$code]);
    $invite = $stmt->fetch();

    if (!$invite) {
        $error = 'Ongeldige uitnodigingscode';
        return null;
    }
    if ($invite['expires_at'] !== null && strtotime($invite['expires_at']) < time()) {
        $error = 'Uitnodigingscode is verlopen';
        return null;
    }
    if ((int) $invite['uses_count'] >= (int) $invite['max_uses']) {
        $error = 'Uitnodigingscode is al gebruikt';
        return null;
    }

    return (int) $invite['id'];
}
