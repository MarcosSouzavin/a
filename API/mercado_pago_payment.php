<?php
// Endpoint para criar preferência de pagamento Mercado Pago (sandbox)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php'; // Ajuste conforme localização do autoload do composer

\MercadoPago\SDK::setAccessToken('TEST-ACCESS-TOKEN'); // Token de teste do Mercado Pago

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Método não permitido'));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Dados inválidos'));
    exit;
}

try {
    $preference = new \MercadoPago\Preference();

    $items = array();
    foreach ($input['items'] as $product) {
        $item = new \MercadoPago\Item();
        $item->title = isset($product['title']) ? $product['title'] : 'Produto';
        $item->quantity = isset($product['quantity']) ? $product['quantity'] : 1;
        $item->unit_price = isset($product['unit_price']) ? $product['unit_price'] : 0;
        $items[] = $item;
    }

    $preference->items = $items;

    // URLs de retorno (ajustar conforme ambiente)
    $preference->back_urls = array(
        "success" => "http://localhost/success.php",
        "failure" => "http://localhost/failure.php",
        "pending" => "http://localhost/pending.php"
    );
    $preference->auto_return = "approved";

    $preference->save();

    // Log da criação da preferência
    $logFile = __DIR__ . '/../storage/logs/mercado_pago.log';
    $logEntry = date('Y-m-d H:i:s') . " - Preferência criada: ID {$preference->id}, URL {$preference->init_point}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    echo json_encode(array('id' => $preference->id, 'init_point' => $preference->init_point));
} catch (Exception $e) {
    // Log do erro
    $logFile = __DIR__ . '/../storage/logs/mercado_pago.log';
    $logEntry = date('Y-m-d H:i:s') . " - Erro ao criar preferência: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    http_response_code(500);
    echo json_encode(array('error' => 'Erro ao criar preferência: ' . $e->getMessage()));
}
?>
