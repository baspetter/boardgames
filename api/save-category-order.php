<?php
require_once __DIR__ . '/../includes/functions.php';

$userId = require_login_json();
require_csrf();

$data = json_body();
$order = $data['order'] ?? null;
if (!is_array($order)) {
    json_response(['error' => 'Invalid order'], 400);
}

$order = array_values(array_filter(array_map('strval', $order), fn($v) => $v !== ''));

db()->prepare('UPDATE users SET category_order = ? WHERE id = ?')
    ->execute([json_encode($order), $userId]);

json_response(['ok' => true]);
