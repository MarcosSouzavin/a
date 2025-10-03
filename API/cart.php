<?php
header('Content-Type: application/json');

$cartFile = __DIR__ . '/../cart.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    file_put_contents($cartFile, json_encode($data));
    echo json_encode(['status' => 'ok']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($cartFile)) {
        echo file_get_contents($cartFile);
    } else {
        echo '[]';
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
