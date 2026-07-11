<?php
require_once __DIR__ . '/auth.php';

/** Escapes a string for safe HTML output. */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_login_json(): int
{
    $userId = current_user_id();
    if ($userId === null) {
        json_response(['error' => 'Not logged in'], 401);
    }
    return $userId;
}

function require_csrf(): void
{
    start_session();
    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        json_response(['error' => 'Invalid request (CSRF token mismatch), please reload the page.'], 403);
    }
}

/** Reads a JSON request body into an associative array. */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function generate_code(int $bytes = 4): string
{
    return strtoupper(bin2hex(random_bytes($bytes)));
}

/** Validates a "#rrggbb" hex color string. */
function is_valid_hex_color(string $value): bool
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
}

/** Decodes a JSON column value (categories/mechanics/designers/artists) safely. */
function json_col(mixed $value): array
{
    if (is_array($value)) {
        return $value;
    }
    if (is_string($value) && $value !== '') {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

function youtube_search_url(string $gameName): string
{
    return 'https://www.youtube.com/results?search_query=' . urlencode($gameName . ' how to play');
}

function bgg_designer_url(int $id): string
{
    return 'https://boardgamegeek.com/boardgamedesigner/' . $id;
}

function bgg_artist_url(int $id): string
{
    return 'https://boardgamegeek.com/boardgameartist/' . $id;
}
