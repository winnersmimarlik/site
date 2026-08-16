<?php
require __DIR__ . '/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Yöntem desteklenmiyor.']);
    exit;
}

$body = json_body();
$current = isset($body['current']) ? (string)$body['current'] : '';
$new = isset($body['new']) ? (string)$body['new'] : '';

if (strlen($new) < 8) {
    echo json_encode(['success' => false, 'error' => 'Yeni şifre en az 8 karakter olmalı.']);
    exit;
}

$admin = read_json(ADMIN_FILE, null);
if (!$admin || !isset($admin['hash']) || !password_verify($current, $admin['hash'])) {
    echo json_encode(['success' => false, 'error' => 'Mevcut şifre hatalı.']);
    exit;
}

$admin['hash'] = password_hash($new, PASSWORD_BCRYPT);
write_json(ADMIN_FILE, $admin);
echo json_encode(['success' => true]);
