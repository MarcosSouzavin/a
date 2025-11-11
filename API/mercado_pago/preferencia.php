<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../vendor/autoload.php'; // CONFIRMA esse caminho!
MercadoPago\SDK::setAccessToken("TEST-8463743141229895-111115-6d5fe7e0fdfda24f28f043b78683fee6-2982510408");


// Lê o JSON do carrinho vindo do front
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || empty($data["produtos"])) {
  http_response_code(400);
  echo json_encode(["error" => "Carrinho vazio ou inválido"]);
  exit;
}

// Monta itens
$items = [];
foreach ($data["produtos"] as $p) {
  $nome = $p["name"] ?? $p["nome"] ?? "Produto";
  $qtd = (int)($p["quantity"] ?? $p["quantidade"] ?? 1);
  $precoBase = (float)($p["basePrice"] ?? $p["preco"] ?? $p["price"] ?? 0);

  // Soma de adicionais selecionados
  $somaExtras = 0.0;
  if (!empty($p["adicionais"]) && is_array($p["adicionais"])) {
    foreach ($p["adicionais"] as $a) {
      $somaExtras += (float)($a["price"] ?? $a["preco"] ?? 0);
    }
  }

  $precoFinal = round($precoBase + $somaExtras, 2); // importante arredondar em centavos

  $item = new MercadoPago\Item();
  $item->title = $nome;
  $item->quantity = $qtd;
  $item->unit_price = $precoFinal;
  $item->currency_id = "BRL";
  $items[] = $item;
}

// Frete (se aplicável) — você já soma no total do front.
// Se quiser discriminar o frete como item separado, descomente:
/*
$frete = (float)($data["frete"] ?? 0);
if ($frete > 0) {
  $itemFrete = new MercadoPago\Item();
  $itemFrete->title = "Frete";
  $itemFrete->quantity = 1;
  $itemFrete->unit_price = round($frete, 2);
  $itemFrete->currency_id = "BRL";
  $items[] = $itemFrete;
}
*/

$preference = new MercadoPago\Preference();
$preference->items = $items;

// ID único do pedido (use o ID real do seu sistema, se já existir)
$pedidoId = $data["order_id"] ?? uniqid("PEDIDO_");
$preference->external_reference = $pedidoId;

// URLs de retorno (pós-checkout)
$preference->back_urls = [
  "success" => base_url("sucesso.php"),
  "failure" => base_url("falha.php"),
  "pending" => base_url("pendente.php"),
];
$preference->auto_return = "approved";

// Webhook para receber mudanças de status
$preference->notification_url = base_url("API/mercado_pago/webhook.php");

// Metadados úteis
$preference->metadata = [
  "usuario_id"  => $data["usuario_id"] ?? null,
  "tipoEntrega" => $data["tipoEntrega"] ?? null,
  "pagamento"   => $data["pagamento"] ?? null,
  "endereco"    => $data["endereco"] ?? null,
  "observacoes" => $data["observacoes"] ?? null,
  "frete"       => $data["frete"] ?? 0,
  "total"       => $data["total"] ?? 0
];

$preference->save();

// Em sandbox, sempre use o sandbox_init_point (link de teste)
echo json_encode([
  "preference_id" => $preference->id,
  "init_point"    => $preference->sandbox_init_point,
  "pedido_id"     => $pedidoId
]);
