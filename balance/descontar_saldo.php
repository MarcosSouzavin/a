<?php
session_start();
require_once '../conexao.php';
require_once 'function_balance.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['valor']) || $data['valor'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor inválido.']);
    exit;
}

try {
    descontarSaldo($pdo, $usuario_id, $data['valor'], $data['descricao'] ?? 'Compra');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}