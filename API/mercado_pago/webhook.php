<?php
require __DIR__ . "/../conexao.php"; // conexão ao banco

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["data"]["id"])) {
    http_response_code(400);
    exit("Webhook inválido");
}

$payment_id = $data["data"]["id"];

// Consulta o status real no MP
$token = "SEU_ACCESS_TOKEN_PRODUCAO_AQUI"; // produção
$ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($response["status"], $response["order"]["id"])) {
    http_response_code(400);
    exit("Erro ao consultar pagamento");
}

$status = $response["status"];
$preference_id = $response["order"]["id"]; // preference_id

// Salva no banco
$stmt = $pdo->prepare("
    INSERT INTO pagamentos_pix (preference_id, status)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status)
");
$stmt->execute([$preference_id, $status]);

http_response_code(200);
echo "OK";
