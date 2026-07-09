<?php
// One-off setup script: creates the first invite code if none exists yet.
// Run via SSH: php bin/seed.php
require_once __DIR__ . '/../includes/functions.php';

$existing = db()->query('SELECT code FROM invite_codes LIMIT 1')->fetch();
if ($existing) {
    echo "Invite code already exists: {$existing['code']}\n";
    exit(0);
}

$code = getenv('SEED_INVITE_CODE') ?: generate_code();
$maxUses = (int) (getenv('SEED_INVITE_MAX_USES') ?: 20);

$stmt = db()->prepare('INSERT INTO invite_codes (code, max_uses) VALUES (?, ?)');
$stmt->execute([$code, $maxUses]);

echo "Created invite code: $code\n";
echo "Share this code with friends so they can register.\n";
