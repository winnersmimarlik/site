<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Yöntem desteklenmiyor.']);
    exit;
}

$body = json_body();
$username = isset($body['username']) ? trim((string)$body['username']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$now = time();

// IP başına başarısız deneme kilidi (kaba kuvvet saldırılarını yavaşlatmak için)
$lockout = read_json(LOCKOUT_FILE, []);
foreach ($lockout as $k => $v) {
    if (($v['lockedUntil'] ?? 0) < $now && ($now - ($v['lastAttempt'] ?? 0)) > 3600) {
        unset($lockout[$k]);
    }
}

if (isset($lockout[$ip]) && ($lockout[$ip]['lockedUntil'] ?? 0) > $now) {
    $wait = $lockout[$ip]['lockedUntil'] - $now;
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => "Çok fazla başarısız deneme. $wait saniye sonra tekrar deneyin."]);
    exit;
}

$admin = read_json(ADMIN_FILE, null);
$ok = false;
if ($admin && isset($admin['username'], $admin['hash']) && $username !== '') {
    if (hash_equals($admin['username'], $username) && password_verify($password, $admin['hash'])) {
        $ok = true;
    }
}

if ($ok) {
    unset($lockout[$ip]);
    write_json(LOCKOUT_FILE, $lockout);
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    echo json_encode(['success' => true]);
} else {
    $entry = $lockout[$ip] ?? ['fails' => 0, 'lockedUntil' => 0, 'lastAttempt' => 0];
    $entry['fails'] = ($entry['fails'] ?? 0) + 1;
    $entry['lastAttempt'] = $now;
    if ($entry['fails'] >= 5) {
        $entry['lockedUntil'] = $now + 30;
        $entry['fails'] = 0;
    }
    $lockout[$ip] = $entry;
    write_json(LOCKOUT_FILE, $lockout);
    echo json_encode(['success' => false, 'error' => 'Kullanıcı adı veya şifre hatalı.']);
}
