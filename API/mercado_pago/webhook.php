<?php
require __DIR__ . "/../../conexao.php"; // AJUSTE O CAMINHO CERTO AQUI

http_response_code(200);
header("Content-Type: text/plain; charset=utf-8");

// Captura o corpo bruto enviado pelo Mercado Pago
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Log para debug
file_put_contents(__DIR__ . "/mp_webhook_log.txt",
    "[" . date("Y-m-d H:i:s") . "] RAW: " . $raw . "\n", FILE_APPEND
);

// Se não vier nada, encerra
if (!$data || !isset($data["data"]["id"])) {
    echo "Sem data.id";
    exit;
}

$payment_id = $data["data"]["id"];

// TOKEN DE PRODUÇÃO
$token = "APP_USR-6484797286702843-111721-bbfdf572557f662f756cc887c3b2e200-1902528413";

// Consulta o pagamento na API do Mercado Pago
$ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$payment = json_decode($response, true);

// Se der erro
if (!isset($payment["status"])) {
    echo "Pagamento não encontrado";
    exit;
}

$status = $payment["status"];
$preference_id = $payment["order"]["id"] ?? null;

$msg = match ($status) {
    "approved" => "Pagamento aprovado!",
    "pending" => "Pagamento pendente.",
    "in_process" => "Pagamento em análise.",
    "rejected" => "Pagamento recusado.",
    default => "Status atualizado: $status"
};

// Salva na tabela pedidos_status
$stmt = $conn->prepare("
    INSERT INTO pedidos_status (payment_id, status, mensagem)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        mensagem = VALUES(mensagem),
        atualizado_em = CURRENT_TIMESTAMP
");
$stmt->bind_param("sss", $payment_id, $status, $msg);
$stmt->execute();

echo "OK";
