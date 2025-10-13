<?php
include 'mp_sdk.php';
include '../../conexao.php';
header('Content-Type: application/json');

// ✅ CHAVE DE ACESSO DO MERCADO PAGO (usa a tua)
$access_token = "TEST-xxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // substitui aqui

// Recebe o pedido
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['produtos'])) {
    echo json_encode(["error" => "Pedido inválido."]);
    exit;
}

// Monta os itens
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

// Dados extras
$total = floatval($input['total'] ?? 0);
$usuario_id = $input['usuario_id'] ?? null;
$endereco = $input['endereco'] ?? '';
$frete = floatval($input['frete'] ?? 0);

// Cria a preferência
$dados_preferencia = [
    "items" => $items,
    "back_urls" => [
        "success" => "https://projetosetim.com.br/2025/php1/payment_return.php",
        "failure" => "https://projetosetim.com.br/2025/php1/pagamento_falha.php",
        "pending" => "https://projetosetim.com.br/2025/php1/pagamento_pendente.php"
    ],
    "auto_return" => "approved",
    "binary_mode" => true,
    "notification_url" => "https://projetosetim.com.br/2025/php1/API/mercado_pago/notificar.php"
];

$resposta = mp_criar_preferencia($access_token, $dados_preferencia);

if (!empty($resposta['init_point'])) {
    echo json_encode(["init_point" => $resposta['init_point'], "id" => $resposta['id']]);
} else {
    echo json_encode(["error" => $resposta]);
}
?>
