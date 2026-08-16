<?php
require __DIR__ . '/config.php';

echo json_encode(['loggedIn' => !empty($_SESSION['admin'])]);
