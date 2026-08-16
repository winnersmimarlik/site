<?php
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    require_admin();
    $messages = read_json(MESSAGES_FILE, []);
    echo json_encode($messages, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $body = json_body();
    $action = $body['action'] ?? 'add';

    if ($action === 'add') {
        // IP başına basit hız sınırlama: 15 saniyede 1 gönderim
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $now = time();
        $rl = read_json(RATELIMIT_FILE, []);
        if (isset($rl[$ip]) && ($now - $rl[$ip]) < 15) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Lütfen birkaç saniye bekleyip tekrar deneyin.']);
            exit;
        }
        $rl[$ip] = $now;
        foreach ($rl as $k => $v) {
            if (($now - $v) > 3600) unset($rl[$k]);
        }
        write_json(RATELIMIT_FILE, $rl);

        $name = trim(mb_substr((string)($body['name'] ?? ''), 0, 80));
        $phone = trim(mb_substr((string)($body['phone'] ?? ''), 0, 30));
        $address = trim(mb_substr((string)($body['address'] ?? ''), 0, 200));
        $email = trim(mb_substr((string)($body['email'] ?? ''), 0, 120));
        $message = trim(mb_substr((string)($body['message'] ?? ''), 0, 1000));

        if ($name === '' || $phone === '' || $address === '' || $message === '') {
            echo json_encode(['success' => false, 'error' => 'Lütfen zorunlu alanları doldurun.']);
            exit;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Geçerli bir e-posta girin.']);
            exit;
        }

        $messages = read_json(MESSAGES_FILE, []);
        $newEntry = [
            'id' => bin2hex(random_bytes(6)),
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'email' => $email,
            'message' => $message,
            'date' => date('c'),
        ];
        array_unshift($messages, $newEntry); // en yeni mesaj listenin başında görünsün
        write_json(MESSAGES_FILE, $messages);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        require_admin();
        $id = $body['id'] ?? '';
        $messages = read_json(MESSAGES_FILE, []);
        $messages = array_values(array_filter($messages, function ($m) use ($id) {
            return ($m['id'] ?? null) !== $id;
        }));
        write_json(MESSAGES_FILE, $messages);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Geçersiz işlem.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Yöntem desteklenmiyor.']);
