<?php
// Applies any not-yet-applied sql/NNN_*.sql migration against the database
// configured in .env. Safe to run repeatedly — applied migrations are
// tracked in a schema_migrations table. Run via SSH after a git pull:
//   php bin/migrate.php
require_once __DIR__ . '/../includes/functions.php';

// Migrations that predate this tool and were already applied by hand (per
// the README's old manual "mysql ... < sql/xyz.sql" process) before this
// file existed. Marked as applied without re-running them on first use,
// since replaying some of them against the current schema doesn't make
// sense (e.g. a migration that modifies a column an even older migration
// later dropped).
const PRE_EXISTING_MIGRATIONS = [
    '002_add_user_accent_color.sql',
    '003_best_players_range.sql',
    '004_add_tagline.sql',
    '005_drop_best_players.sql',
    '006_add_category_order.sql',
    '007_drop_category_order.sql',
    '008_add_primary_category.sql',
    '009_add_publishers.sql',
    '010_add_expansions.sql',
    '011_add_wishlist.sql',
];

$tableExisted = (bool) db()->query("SHOW TABLES LIKE 'schema_migrations'")->fetch();

db()->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

if (!$tableExisted) {
    $stmt = db()->prepare('INSERT IGNORE INTO schema_migrations (filename) VALUES (?)');
    foreach (PRE_EXISTING_MIGRATIONS as $filename) {
        $stmt->execute([$filename]);
    }
}

$applied = array_column(db()->query('SELECT filename FROM schema_migrations')->fetchAll(), 'filename');

$files = glob(__DIR__ . '/../sql/*.sql');
sort($files, SORT_NATURAL);

// MySQL error codes meaning "this change already exists" — e.g. a fresh
// install's schema.sql already includes it. Safe to skip.
$alreadyAppliedCodes = [1050, 1060, 1061, 1091];

$ran = 0;
foreach ($files as $path) {
    $filename = basename($path);
    if ($filename === 'schema.sql' || in_array($filename, $applied, true)) {
        continue;
    }

    $statements = array_filter(array_map('trim', explode(';', file_get_contents($path))));
    foreach ($statements as $statement) {
        try {
            db()->exec($statement);
        } catch (PDOException $e) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            if (!in_array($code, $alreadyAppliedCodes, true)) {
                throw $e;
            }
            echo "  ($filename already applied, skipping)\n";
        }
    }

    $stmt = db()->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
    $stmt->execute([$filename]);
    echo "Applied: $filename\n";
    $ran++;
}

echo $ran > 0 ? "Done — $ran migration(s) applied.\n" : "Already up to date, nothing to do.\n";
