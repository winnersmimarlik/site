<?php
require __DIR__ . '/config.php';

$defaultContent = [
    'categories' => [
        ['id' => 'ic-mimarlik', 'code' => 'A-01', 'name' => 'İç Mimarlık', 'desc' => 'Mekan planlama, 3B tasarım ve malzeme seçimi.'],
        ['id' => 'tadilat', 'code' => 'A-02', 'name' => 'Tadilat', 'desc' => 'Komple veya kısmi yenileme, tesisat ve yapısal işler.'],
        ['id' => 'dekorasyon', 'code' => 'A-03', 'name' => 'Dekorasyon', 'desc' => 'Aydınlatma, tekstil ve aksesuar ile kimlik kazandırma.'],
        ['id' => 'mobilya', 'code' => 'A-04', 'name' => 'Mobilya', 'desc' => 'Ölçüye özel üretim ve uygulamalı mobilya çözümleri.'],
    ],
    'site' => [
        'phone' => '+90 232 000 00 00',
        'email' => 'info@winnersmimarlik.com',
        'address' => 'Uluönder Caddesi No:6/A, Karabağlar, İzmir',
    ],
    'gallery' => new stdClass(),
];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $data = read_json(CONTENT_FILE, $defaultContent);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    require_admin();
    $body = json_body();
    $action = $body['action'] ?? '';

    $data = read_json(CONTENT_FILE, $defaultContent);
    if (!is_array($data)) $data = (array)$data;
    if (!isset($data['gallery']) || !is_array($data['gallery'])) $data['gallery'] = [];
    if (!isset($data['categories'])) $data['categories'] = $defaultContent['categories'];
    if (!isset($data['site'])) $data['site'] = $defaultContent['site'];

    switch ($action) {
        case 'set_categories':
            if (!isset($body['value']) || !is_array($body['value'])) {
                echo json_encode(['success' => false, 'error' => 'Geçersiz kategori verisi.']);
                exit;
            }
            $data['categories'] = $body['value'];
            break;

        case 'set_site':
            if (!isset($body['value']) || !is_array($body['value'])) {
                echo json_encode(['success' => false, 'error' => 'Geçersiz site verisi.']);
                exit;
            }
            $data['site'] = [
                'phone' => mb_substr((string)($body['value']['phone'] ?? $data['site']['phone']), 0, 40),
                'email' => mb_substr((string)($body['value']['email'] ?? $data['site']['email']), 0, 120),
                'address' => mb_substr((string)($body['value']['address'] ?? $data['site']['address']), 0, 160),
            ];
            break;

        case 'set_gallery':
            $catId = $body['catId'] ?? '';
            if ($catId === '' || !isset($body['value']) || !is_array($body['value'])) {
                echo json_encode(['success' => false, 'error' => 'Geçersiz galeri verisi.']);
                exit;
            }
            $data['gallery'][$catId] = $body['value'];
            break;

        case 'delete_gallery':
            $catId = $body['catId'] ?? '';
            unset($data['gallery'][$catId]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Geçersiz işlem.']);
            exit;
    }

    write_json(CONTENT_FILE, $data);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Yöntem desteklenmiyor.']);
