<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

// Importa as classes corretamente
use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

// Testa o SDK
SDK::setAccessToken("TEST-8463743141229895-111115-6d5fe7e0fdfda24f28f043b78683fee6-2982510408"); // coloca teu token de teste aqui

echo "<h3>✅ Mercado Pago SDK carregado com sucesso!</h3>";

// Cria uma preferência de teste (só pra validar)
$item = new Item();
$item->title = "Pizza de teste";
$item->quantity = 1;
$item->unit_price = 10.00;

$preference = new Preference();
$preference->items = [$item];
$preference->save();

echo "<pre>";
print_r([
  "preference_id" => $preference->id ?? null,
  "sandbox_init_point" => $preference->sandbox_init_point ?? null
]);
echo "</pre>";


?>