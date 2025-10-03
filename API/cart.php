<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    $_SESSION['cart'] = $data;
    echo json_encode(['status' => 'ok']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($_SESSION['cart'] ?? []);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
