<?php
require __DIR__ . "/../conexao.php"; // ajuste o caminho conforme seu sistema

// Recebe o corpo bruto
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Log básico opcional
file_put_contents(__DIR__ . "/webhook_log.txt", date("Y-m-d H:i:s") . " - " . $input . "\n\n", FILE_APPEND);

// Se não veio nada, encerra
if (!$data || !isset($data["data"]["id"])) {
    http_response_code(200);
    exit("Webhook recebido, mas sem data.id");
}

$payment_id = $data["data"]["id"]; // ID do pagamento
$token = "SEU_ACCESS_TOKEN_PRODUCAO_AQUI"; // 🔥 use o token de produção

// === CONSULTA O PAGAMENTO NA API DO MERCADO PAGO ===
$ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$payment = json_decode($response, true);

// Erro inesperado
if (!isset($payment["status"])) {
    http_response_code(200);
    exit("Pagamento não encontrado na API");
}

$status = $payment["status"];        // approved / pending / rejected
$preference_id = $payment["order"]["id"] ?? null;
$method = $payment["payment_method"]["type"] ?? null;

// === Mensagens automáticas (exibidas no acompanhamento) ===
$msg = match ($status) {
    "approved" => "Pagamento aprovado! Seu pedido está sendo preparado.",
    "pending"  => "Pagamento ainda não confirmado. Aguardando compensação.",
    "in_process" => "Pagamento em análise.",
    "rejected" => "Pagamento recusado. Você pode tentar novamente.",
    default => "Status atualizado: $status"
};

// === SALVA OU ATUALIZA A TABELA pedidos_status ===
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

// Opcional: salvar também o preference_id em tabela separada
if ($preference_id) {
    $stmt2 = $conn->prepare("
        INSERT INTO pedidos_status (payment_id, status, mensagem)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE atualizado_em = CURRENT_TIMESTAMP
    ");
    $stmt2->bind_param("sss", $preference_id, $status, $msg);
    $stmt2->execute();
}

http_response_code(200);
echo "OK";
