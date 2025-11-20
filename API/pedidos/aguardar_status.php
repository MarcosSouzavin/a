<?php
// API/pedidos/atualizar_status.php
header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . "/../../conexap.php"; // volta 1 nível (API/) e carrega a conexão

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || empty($data['payment_id']) || empty($data['status'])) {
    echo json_encode([
        "ok" => false,
        "error" => "Dados inválidos"
    ]);
    exit;
}

$payment_id = $data['payment_id'];
$status     = $data['status'];
$mensagem   = $data['mensagem'] ?? null;

// Atualiza/insere na tabela pedidos_status
$stmt = $conn->prepare("
    INSERT INTO pedidos_status (payment_id, status, mensagem)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        mensagem = VALUES(mensagem),
        atualizado_em = CURRENT_TIMESTAMP
");
$stmt->bind_param("sss", $payment_id, $status, $mensagem);

if ($stmt->execute()) {
    echo json_encode([
        "ok" => true
    ]);
} else {
    echo json_encode([
        "ok" => false,
        "error" => $conn->error
    ]);
}
