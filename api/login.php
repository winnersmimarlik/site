<?php
require __DIR__ . '/config.php';

// Oturumun başladığından emin olun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$body = json_body();
$username = isset($body['username']) ? trim((string)$body['username']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$now = time();

// --- Kaba Kuvvet Koruması ---
$lockout = read_json(LOCKOUT_FILE, []);

// Süresi dolan kilitleri temizle
foreach ($lockout as $k => $v) {
    if (($v['lockedUntil'] ?? 0) < $now) {
        unset($lockout[$k]);
    }
}

if (isset($lockout[$ip]) && ($lockout[$ip]['lockedUntil'] ?? 0) > $now) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many attempts. Please try again later.']);
    exit;
}

// --- Giriş Kontrolü ---
$admin = read_json(ADMIN_FILE, null);
$loginSuccess = false;

if ($admin && isset($admin['username'], $admin['hash']) && !empty($username)) {
    // hash_equals zamanlama saldırılarını (timing attacks) engeller
    if (hash_equals($admin['username'], $username) && password_verify($password, $admin['hash'])) {
        $loginSuccess = true;
    }
}

if ($loginSuccess) {
    // Başarılı girişte IP'yi kilitleme listesinden temizle
    unset($lockout[$ip]);
    write_json(LOCKOUT_FILE, $lockout);
    
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    
    echo json_encode(['success' => true]);
} else {
    // --- Başarısız Giriş İşlemi ---
    $entry = $lockout[$ip] ?? ['fails' => 0, 'lockedUntil' => 0];
    $entry['fails']++;
    
    // 5 başarısız denemede 30 saniye kilit
    if ($entry['fails'] >= 5) {
        $entry['lockedUntil'] = $now + 30;
        $entry['fails'] = 0; // Kilit süresi bittikten sonra tekrar hak ver
    }
    
    $lockout[$ip] = $entry;
    write_json(LOCKOUT_FILE, $lockout);
    
    // Kullanıcıya spesifik bilgi verme (Kullanıcı adı veya şifre yanlış)
    echo json_encode(['success' => false, 'error' => 'Invalid credentials.']);
}
