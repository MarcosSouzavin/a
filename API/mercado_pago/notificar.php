<?php
include 'mp_sdk.php';
include '../../conexao.php';

$access_token = "TEST-43f69931-7659-4ce3-8ad9-9ca7d1f7d44c";
$data = json_decode(file_get_contents("php://input"), true);

// Log simples pra depuração
file_put_contents(__DIR__ . "/log_notificacoes.txt", date('[Y-m-d H:i:s]') . " " . json_encode($data) . "\n", FILE_APPEND);

if (!isset($data['data']['id'])) exit;

$payment_id = $data['data']['id'];
$info = mp_consultar_pagamento($access_token, $payment_id);

$status = $info['status'] ?? 'unknown';
$preference_id = $info['order']['id'] ?? null;

if ($preference_id) {
    $stmt = $pdo->prepare("UPDATE pagamentos SET status = ?, atualizado_em = NOW() WHERE mp_preference_id = ?");
    $stmt->execute([$status, $preference_id]);
}
?>
