<?php
// Central config, loaded from environment variables (set in .env, loaded below)
// or with sane local defaults. Never commit real secrets — .env is gitignored.

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

load_env(__DIR__ . '/../.env');

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'boardgames'));
define('DB_USER', env('DB_USER', 'boardgames'));
define('DB_PASS', env('DB_PASS', ''));

define('BGG_API_TOKEN', env('BGG_API_TOKEN', ''));
define('SITE_NAME', 'My Game Circle');

// Absolute filesystem path to /uploads and its public URL path (relative to site root).
define('UPLOADS_DIR', __DIR__ . '/../uploads/covers');
define('UPLOADS_URL', '/uploads/covers');
