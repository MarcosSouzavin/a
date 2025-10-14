<?php
header('Content-Type: application/json; charset=utf-8');

// === CONFIGURAÇÕES MERCADO PAGO ===
$ACCESS_TOKEN = "TEST-43f69931-7659-4ce3-8ad9-9ca7d1f7d44c";
$BACK_URL = "https://projetosetim.com.br/2025/php1/checkout_success.php";

// === RECEBE DADOS DO PEDIDO ===
$input = file_get_contents("php://input");
$pedido = json_decode($input, true);

if (!$pedido || empty($pedido['produtos'])) {
    http_response_code(400);
    echo json_encode(["error" => "Pedido inválido ou vazio."]);
    exit;
}

// === MONTA ITENS ===
$items = [];
foreach ($pedido['produtos'] as $p) {
    $nome = $p['nome'] ?? $p['name'] ?? 'Produto';
    $qtd = (float)($p['quantidade'] ?? $p['qty'] ?? $p['quantity'] ?? 1);
    $preco = (float)($p['preco'] ?? $p['price'] ?? $p['basePrice'] ?? 0);
    $somaAdicionais = 0;

    if (!empty($p['adicionais']) && is_array($p['adicionais'])) {
        foreach ($p['adicionais'] as $a) {
            $somaAdicionais += (float)($a['preco'] ?? $a['price'] ?? 0);
        }
    }

    $unit = $preco + $somaAdicionais;
    $items[] = [
        "title" => $nome,
        "quantity" => $qtd,
        "unit_price" => round($unit, 2),
        "currency_id" => "BRL"
    ];
}

// === FRETE (fixo se existir) ===
if (!empty($pedido['frete']) && $pedido['frete'] > 0) {
    $items[] = [
        "title" => "Frete",
        "quantity" => 1,
        "unit_price" => (float)$pedido['frete'],
        "currency_id" => "BRL"
    ];
}

// === DADOS DO PAGAMENTO ===
$pagamento = $pedido['pagamento'] ?? 'pix';
$emailComprador = "cliente@teste.com";
$external_ref = "PED_" . time();

// === MONTA PREFERÊNCIA ===
$data = [
    "items" => $items,
    "payer" => [
        "email" => $emailComprador,
    ],
    "external_reference" => $external_ref,
    "back_urls" => [
        "success" => $BACK_URL . "?status=success&pedido=" . $external_ref,
        "failure" => $BACK_URL . "?status=failure&pedido=" . $external_ref,
        "pending" => $BACK_URL . "?status=pending&pedido=" . $external_ref
    ],
    "auto_return" => "approved",
    "payment_methods" => [
        "excluded_payment_types" => [],
        "installments" => 12
    ]
];

// === ENVIA PARA MERCADO PAGO ===
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.mercadopago.com/checkout/preferences",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . $ACCESS_TOKEN
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// === TRATA RESPOSTA ===
if ($error) {
    http_response_code(500);
    echo json_encode(["error" => "Erro cURL: $error"]);
    exit;
}

$result = json_decode($response, true);

if ($http >= 200 && $http < 300 && isset($result['init_point'])) {
    echo json_encode([
        "success" => true,
        "init_point" => $result['init_point'],
        "id" => $result['id'],
        "pedido_id" => $external_ref
    ]);
} else {
    http_response_code($http);
    echo json_encode([
        "error" => "Falha ao criar preferência no Mercado Pago.",
        "http" => $http,
        "response" => $result
    ]);
}
?>
