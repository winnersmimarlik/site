<?php
// Ortak yapılandırma: oturum, veri dosyası yolları ve yardımcı fonksiyonlar.
// Bu dosya diğer tüm api/*.php dosyaları tarafından en başta require edilir.

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: application/json; charset=utf-8');

define('DATA_DIR', __DIR__ . '/../data');
define('ADMIN_FILE', DATA_DIR . '/admin.json');
define('CONTENT_FILE', DATA_DIR . '/content.json');
define('MESSAGES_FILE', DATA_DIR . '/messages.json');
define('LOCKOUT_FILE', DATA_DIR . '/lockout.json');
define('RATELIMIT_FILE', DATA_DIR . '/ratelimit.json');

// Dosyadan JSON okur, yoksa/bozuksa $default döner.
function read_json($file, $default) {
    if (!file_exists($file)) return $default;
    $fp = fopen($file, 'r');
    if (!$fp) return $default;
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($raw, true);
    return ($data === null) ? $default : $data;
}

// JSON'u dosyaya güvenli şekilde (kilitli + atomik rename) yazar.
function write_json($file, $data) {
    $tmp = $file . '.tmp.' . uniqid();
    $fp = fopen($tmp, 'w');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
    fclose($fp);
    return rename($tmp, $file);
}

// Oturum admin değilse 401 döner ve script'i sonlandırır.
function require_admin() {
    if (empty($_SESSION['admin'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim. Lütfen giriş yapın.']);
        exit;
    }
}

// İstek gövdesini (JSON) diziye çevirir.
function json_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
