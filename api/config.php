<?php
// Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// MySQL Veritabanı Bilgileri (InfinityFree Paneline Göre)
define('DB_HOST', 'sql103.infinityfree.com');
define('DB_USER', 'if0_42365509');
define('DB_PASS', 'NOImdfE4BIPde');
define('DB_NAME', 'if0_42365509_darkweb');

function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Veritabanı bağlantı hatası: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// JSON gövdesini almak için yardımcı fonksiyon
function json_body() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}
?>
