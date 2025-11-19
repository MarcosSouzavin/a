<?php
declare(strict_types=1);

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

require __DIR__ . "/mp_config.php"; // carrega token + funções

header('Content-Type: application/json; charset=UTF-8');

try {
    // Lê JSON enviado pelo front
    $body = json_decode(file_get_contents("php://input"), true);

    if (!$body || !isset($body['produtos'])) {
        mp_json_error("Dados inválidos recebidos. Nenhum produto encontrado.", 400);
    }

    // ===== Montar items da preferência =====
    $items = [];
    foreach ($body['produtos'] as $p) {
        $nome  = $p['name'] ?? $p['nome'] ?? 'Produto';
        $qtd   = (int)($p['quantity'] ?? $p['quantidade'] ?? 1);
        $preco = (float)($p['basePrice'] ?? $p['preco'] ?? $p['price'] ?? 0);

        // adicionais (somatório)
        if (!empty($p['adicionais']) && is_array($p['adicionais'])) {
            foreach ($p['adicionais'] as $ad) {
                $preco += (float)($ad['price'] ?? $ad['preco'] ?? 0);
            }
        }

        if ($qtd <= 0) continue;

        $items[] = [
            "title" => $nome,
            "quantity" => $qtd,
            "unit_price" => $preco,
            "currency_id" => "BRL"
        ];
    }

    if (empty($items)) {
        mp_json_error("Nenhum item válido encontrado no carrinho.", 400);
    }

    // ===== Frete =====
    $frete = isset($body["frete"]) ? (float)$body["frete"] : 0;
    if ($frete > 0) {
        $items[] = [
            "title" => "Frete",
            "quantity" => 1,
            "unit_price" => $frete,
            "currency_id" => "BRL"
        ];
    }

    // ===== Observações =====
    $descricao = "Pedido via SquinaXV";
    if (!empty($body["observacoes"])) {
        $descricao .= " | Obs: " . $body["observacoes"];
    }

    // ===== URLs de retorno =====
    $back_urls = [
    "success" => MP_BASE_URL . "/sucesso.php",
    "failure" => MP_BASE_URL . "/falha.php",
    "pending" => MP_BASE_URL . "/pendente.php"
    ];


    // ===== Criar preferência =====
    $client = new PreferenceClient();


if (!empty($body["pagamento"])) {
    if ($body["pagamento"] === "pix") {
        $payment_methods = [
            "default_payment_method_id" => "pix",
            "excluded_payment_types" => []
        ];
    } elseif ($body["pagamento"] === "debito") {
        $payment_methods = [
            "default_payment_method_id" => "debit_card",
            "excluded_payment_types" => []
        ];
    } elseif ($body["pagamento"] === "credito") {
        $payment_methods = [
            "default_payment_method_id" => "credit_card",
            "excluded_payment_types" => []
        ];
    }
}

$preference = $client->create([
    "items" => $items,
    "metadata" => [
        "usuario_id" => $body["usuario_id"] ?? null,
        "entrega"    => $body["tipoEntrega"] ?? "entrega",
        "pagamento"  => $body["pagamento"] ?? "pix"
    ],
    // Remover qualquer configuração de método padrão
    // O Mercado Pago decide automaticamente
    "back_urls" => $back_urls
]);


    // ===== Retorno para o JS =====
    echo json_encode([
        "ok" => true,
        "init_point" => $preference->init_point,
        "id" => $preference->id
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (MPApiException $e) {
    // Erro da API do MP → mostrar resposta real
    mp_json_error("API MERCADO PAGO: " . json_encode($e->getApiResponse()->getContent(), JSON_UNESCAPED_UNICODE), 500);
} catch (Exception $e) {
    // Erro genérico
    mp_json_error("Erro interno: " . $e->getMessage(), 500);
}
