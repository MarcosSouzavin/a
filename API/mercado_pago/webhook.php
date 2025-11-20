<?php
require __DIR__ . "/../../conexao.php";

// Mercado Pago exige SEMPRE 200 OK sem texto
http_response_code(200);

// Captura o JSON enviado pelo Mercado Pago
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Log (não interfere no 200)
file_put_contents(__DIR__ . "/mp_webhook_log.txt",
    "[" . date("Y-m-d H:i:s") . "] RAW: " . $raw . "\n", FILE_APPEND
);

// Se não vier ID, ignora (não pode dar erro)
if (!isset($data["data"]["id"])) {
    return;
}

$payment_id = $data["data"]["id"];

// Token de produção
$token = "APP_USR-6484797286702843-111721-bbfdf572557f662f756cc887c3b2e200-1902528413";

// Consulta a API do Mercado Pago
$ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$payment = json_decode($response, true);

// Se não tiver status, ignora (acontece nos testes do painel)
if (!isset($payment["status"])) {
    return;
}

$status = $payment["status"];
$preference_id = $payment["order"]["id"] ?? null;

// Mensagem para o acompanhamento
$msg = match ($status) {
    "approved"   => "Pagamento aprovado!",
    "pending"    => "Pagamento pendente.",
    "in_process" => "Pagamento em análise.",
    "rejected"   => "Pagamento recusado.",
    default      => "Status: $status"
};

// === SALVAR NO BANCO COM PDO ===

// Verificar se já existe
$check = $conn->prepare("SELECT id FROM pedidos_status WHERE payment_id = ?");
$check->execute([$payment_id]);

if ($check->rowCount() > 0) {
    // Atualiza
    $update = $conn->prepare("
        UPDATE pedidos_status 
        SET status = ?, mensagem = ?, atualizado_em = NOW()
        WHERE payment_id = ?
    ");
    $update->execute([$status, $msg, $payment_id]);

} else {
    // Insere
    $insert = $conn->prepare("
        INSERT INTO pedidos_status (payment_id, status, mensagem)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$payment_id, $status, $msg]);
}

// Opcional: salvar preference_id também, se quiser:
if ($preference_id) {
    $check2 = $conn->prepare("SELECT id FROM pedidos_status WHERE payment_id = ?");
    $check2->execute([$preference_id]);

    if ($check2->rowCount() > 0) {
        $update2 = $conn->prepare("
            UPDATE pedidos_status 
            SET atualizado_em = NOW()
            WHERE payment_id = ?
        ");
        $update2->execute([$preference_id]);
    } else {
        $insert2 = $conn->prepare("
            INSERT INTO pedidos_status (payment_id, status, mensagem)
            VALUES (?, ?, ?)
        ");
        $insert2->execute([$preference_id, $status, $msg]);
    }
}

// Final silencioso → Mercado Pago recebe 200 OK
return;
