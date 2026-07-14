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

/** @return array<string, string> shop label => search URL for $gameName, for "where to buy" links. */
function shop_search_urls(string $gameName): array
{
    return [
        'Bol.com' => 'https://www.bol.com/nl/nl/s/?searchtext=' . urlencode($gameName),
        'Amazon.nl' => 'https://www.amazon.nl/s?k=' . urlencode($gameName),
        'Google Shopping' => 'https://www.google.com/search?tbm=shop&q=' . urlencode($gameName),
    ];
}

/** Canonical top-level nav pages: key => [href, label]. Shared by the site nav and the game-detail "from" breadcrumb. */
function nav_links(): array
{
    return [
        'collection' => ['/', 'My collection'],
        'wishlist' => ['/wishlist.php', 'My wishlist'],
        'playgroups' => ['/playgroups.php', 'Our playgroup'],
        'gamenights' => ['/gamenights.php', 'Our gamenights'],
    ];
}

/** Validates a "from" breadcrumb query value against nav_links() keys; returns null if not recognized. */
function valid_nav_from(?string $from): ?string
{
    return $from !== null && isset(nav_links()[$from]) ? $from : null;
}

/** Builds a /game.php?id=X link, carrying forward an optional "from" breadcrumb context. */
function game_url(int $gameId, ?string $from = null): string
{
    $url = '/game.php?id=' . $gameId;
    if (valid_nav_from($from) !== null) {
        $url .= '&from=' . urlencode($from);
    }
    return $url;
}

function bgg_designer_url(int $id): string
{
    return 'https://boardgamegeek.com/boardgamedesigner/' . $id;
}

function bgg_artist_url(int $id): string
{
    return 'https://boardgamegeek.com/boardgameartist/' . $id;
}

function bgg_publisher_url(int $id): string
{
    return 'https://boardgamegeek.com/boardgamepublisher/' . $id;
}
