<?php
// mp_config.php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Caminho do autoload do Composer
require __DIR__ . '/../../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;

// 🔐 Coloca aqui **SEU ACCESS TOKEN DE TESTE (TEST-...)**
const MP_ACCESS_TOKEN = 'APP_USR-6484797286702843-111721-bbfdf572557f662f756cc887c3b2e200-1902528413';

// 🌐 URL base do seu projeto
// No seu caso tá em: http://localhost/a
const MP_BASE_URL = 'https://projetosetim.com.br/2025/php1/API/mercado_pago';

// Configura o SDK
MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

/**
 * Monta URL absoluta a partir da base
 */
function mp_base_url(string $path = ''): string
{
    $base = rtrim(MP_BASE_URL, '/');
    $path = ltrim($path, '/');

    return $path ? "$base/$path" : $base;
}

/**
 * Resposta JSON padronizada de erro
 */
function mp_json_error(string $message, int $httpCode = 400): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok'      => false,
        'error'   => $message,
        'time'    => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
