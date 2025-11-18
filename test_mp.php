<?php
require __DIR__ . '/vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

MercadoPagoConfig::setAccessToken("TEST-6484797286702843-111721-ae840b1dfc7bea47361a674589b9fc6a-1902528413");

// Forçar mostrar erros reais
error_reporting(E_ALL);
ini_set('display_errors', 1);

$client = new PreferenceClient();

try {

    $preference = $client->create([
        "items" => [
            [
                "title" => "Pizza Teste",
                "quantity" => 1,
                "unit_price" => 49.90,
                "currency_id" => "BRL"
            ]
        ]
    ]);

    echo "<h2>Preferência criada!</h2>";
    echo "<pre>";
    print_r($preference);
    echo "</pre>";

} catch (\MercadoPago\Exceptions\MPApiException $e) {

    echo "<h2 style='color:red'>❌ ERRO DETALHADO DA API:</h2>";

    echo "<pre>";
    echo "STATUS CODE:\n";
    print_r($e->getApiResponse()->getStatusCode());

    echo "\n\nJSON COMPLETO:\n";
    print_r($e->getApiResponse()->getContent());

    echo "</pre>";

} catch (Throwable $e) {

    echo "<h2 style='color:red'>❌ ERRO PHP:</h2>";
    echo "<pre>";
    echo $e->getMessage();
    echo "</pre>";

}
