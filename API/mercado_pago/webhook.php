<?php
require __DIR__ . "/../../conexao.php";

// Sempre retornar 200 ao Mercado Pago
http_response_code(200);

// Captura o JSON enviado
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Log (não interfere na resposta)
file_put_contents(__DIR__ . "/mp_webhook_log.txt",
    "[" . date("Y-m-d H:i:s") . "] RAW: " . $raw . "\n", FILE_APPEND
);

// Se não vier ID, não faz nada (mas não dá erro no MP)
if (!isset($data["data"]["id"])) {
    return; // silencioso, sem echo
}

$payment_id = $data["data"]["id"];

// Token
$token = "APP_USR-6484797286702843-111721-bbfdf572557f662f756cc887c3b2e200-1902528413";

// Consulta o pagamento
$ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$payment = json_decode($response, true);

// Se deu erro na consulta ou não é pagamento real → não quebra webhook
if (!isset($payment["status"])) {
    return; // silencioso
}

$status = $payment["status"];
$preference_id = $payment["order"]["id"] ?? null;

// Mensagem padrão
$msg = match ($status) {
    "approved"   => "Pagamento aprovado!",
    "pending"    => "Pagamento pendente.",
    "in_process" => "Pagamento em análise.",
    "rejected"   => "Pagamento recusado.",
    default      => "Status: $status"
};

// Salva no banco
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

// Fim → Mercado Pago recebe 200 OK sem corpo
