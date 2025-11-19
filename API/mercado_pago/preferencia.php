<?php
declare(strict_types=1);

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

require __DIR__ . "/mp_config.php"; // carrega SDK, token, MP_BASE_URL, mp_json_error, mp_base_url

header('Content-Type: application/json; charset=UTF-8');

/**
 * Lê o corpo JSON da requisição de forma segura
 */
function mp_read_body(): array
{
    $raw = file_get_contents("php://input");

    if ($raw === false || $raw === '') {
        mp_json_error("Corpo da requisição vazio.", 400);
    }

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        mp_json_error("JSON inválido: " . json_last_error_msg(), 400);
    }

    return $data;
}

try {
    // ========= 1) LER DADOS DO FRONT =========
    $body = mp_read_body();

    if (!isset($body['produtos']) || !is_array($body['produtos']) || count($body['produtos']) === 0) {
        mp_json_error("Dados inválidos recebidos. Nenhum produto encontrado.", 400);
    }

    // ========= 2) MONTAR ITENS =========
    $items = [];

    foreach ($body['produtos'] as $p) {
        $nome  = $p['name'] ?? $p['nome'] ?? 'Produto';
        $qtd   = (int)($p['quantity'] ?? $p['quantidade'] ?? 1);
        $preco = (float)($p['basePrice'] ?? $p['preco'] ?? $p['price'] ?? 0);

        if ($qtd <= 0) {
            continue;
        }

        // Somar adicionais, se existirem
        if (!empty($p['adicionais']) && is_array($p['adicionais'])) {
            foreach ($p['adicionais'] as $ad) {
                $preco += (float)($ad['price'] ?? $ad['preco'] ?? 0);
            }
        }

        if ($preco < 0) {
            $preco = 0;
        }

        $items[] = [
            "title"       => $nome,
            "quantity"    => $qtd,
            "unit_price"  => $preco,
            "currency_id" => "BRL",
        ];
    }

    if (empty($items)) {
        mp_json_error("Nenhum item válido encontrado no carrinho.", 400);
    }

    // ========= 3) FRETE COMO ITEM SEPARADO =========
    $frete = isset($body["frete"]) ? (float)$body["frete"] : 0;
    if ($frete > 0) {
        $items[] = [
            "title"       => "Frete",
            "quantity"    => 1,
            "unit_price"  => $frete,
            "currency_id" => "BRL",
        ];
    }

    // ========= 4) OBSERVAÇÕES / DESCRIÇÃO =========
    $descricao = "Pedido via SquinaXV";
    if (!empty($body["observacoes"])) {
        $descricao .= " | Obs: " . substr((string)$body["observacoes"], 0, 255);
    }

    // ========= 5) URLs DE RETORNO =========
    // Essas rotas precisam existir no teu servidor:
    //   /API/mercado_pago/sucesso.php
    //   /API/mercado_pago/falha.php
    //   /API/mercado_pago/pendente.php
    $back_urls = [
        "success" => MP_BASE_URL . "/API/mercado_pago/sucesso.php",
        "failure" => MP_BASE_URL . "/API/mercado_pago/falha.php",
        "pending" => MP_BASE_URL . "/API/mercado_pago/pendente.php",
    ];

    // ========= 6) METADADOS =========
    $metadados = [
        "usuario_id" => $body["usuario_id"] ?? null,
        "entrega"    => $body["tipoEntrega"] ?? "entrega",
        "pagamento"  => $body["pagamento"] ?? "pix",
        "endereco"   => $body["endereco"] ?? null,
        "observacoes"=> $body["observacoes"] ?? null,
    ];

    // ========= 7) CRIAR PREFERÊNCIA =========
    $client = new PreferenceClient();

    $preferenciaPayload = [
        "items"        => $items,
        "metadata"     => $metadados,
        "back_urls"    => $back_urls,
        "auto_return"  => "approved", // cartão/boleto: volta automático após aprovado
        "notification_url" => mp_base_url("API/mercado_pago/webhook.php"),
        // opcional: usar external_reference para amarrar ao ID do pedido na sua base
        // "external_reference" => (string)$idPedido,
    ];

    $preference = $client->create($preferenciaPayload);

    // ========= 8) RESPOSTA PARA O FRONT =========
    // JS vai usar:
    //  - data.init_point   -> redirecionar para Checkout Pro
    //  - data.id           -> preference_id (pra tela aguardando PIX)
    echo json_encode([
        "ok"         => true,
        "init_point" => $preference->init_point,
        "id"         => $preference->id,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (MPApiException $e) {
    mp_json_error(
        "API MERCADO PAGO: " . json_encode($e->getApiResponse()->getContent(), JSON_UNESCAPED_UNICODE),
        500
    );
} catch (Throwable $e) {
    mp_json_error("Erro interno: " . $e->getMessage(), 500);
}
