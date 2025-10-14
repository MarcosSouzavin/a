<?php
include 'mp_sdk.php';
include '../../conexao.php';
header('Content-Type: application/json');

// 🔐 Token de teste Mercado Pago
$access_token = "TEST-43f69931-7659-4ce3-8ad9-9ca7d1f7d44c";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['produtos'])) {
    echo json_encode(["error" => "Pedido inválido."]);
    exit;
}

// ---------------------------
// Monta lista de produtos
// ---------------------------
$items = [];
foreach ($input['produtos'] as $p) {
    $nome = $p['nome'] ?? $p['name'] ?? 'Produto';
    $qtd = intval($p['quantidade'] ?? 1);
    $preco = floatval($p['preco'] ?? $p['price'] ?? 0);
    $adicionais = 0;

    if (!empty($p['adicionais'])) {
        foreach ($p['adicionais'] as $a) {
            $adicionais += floatval($a['preco'] ?? $a['price'] ?? 0);
        }
    }

    $items[] = [
        "title" => $nome,
        "quantity" => $qtd,
        "currency_id" => "BRL",
        "unit_price" => $preco + $adicionais
    ];
}

$total = floatval($input['total'] ?? 0);
$usuario_id = $input['usuario_id'] ?? null;
$endereco = $input['endereco'] ?? '';
$frete = floatval($input['frete'] ?? 0);
$tipoEntrega = $input['tipoEntrega'] ?? 'entrega';

// ---------------------------
// Cria preferência no MP
// ---------------------------
$dados_preferencia = [
    "items" => $items,
    "payer" => [
        "name" => "Cliente",
        "email" => "comprador_teste@example.com"
    ],
    "back_urls" => [
        "success" => "https://projetosetim.com.br/2025/php1/pagamento_sucesso.php",
        "failure" => "https://projetosetim.com.br/2025/php1/pagamento_falha.php",
        "pending" => "https://projetosetim.com.br/2025/php1/pagamento_pendente.php"
    ],
    "auto_return" => "approved",
    "binary_mode" => true,
    "notification_url" => "https://projetosetim.com.br/2025/php1/API/mercado_pago/notificar.php"
];

$resposta = mp_criar_preferencia($access_token, $dados_preferencia);


if (!empty($resposta['init_point'])) {
    echo json_encode([
        "init_point" => $resposta['init_point'],
        "id" => $resposta['id']
    ]);
} else {
    echo json_encode(["error" => $resposta]);
}
?>
