<?php
require __DIR__ . '/../../vendor/autoload.php';

// 🔐 Coloque aqui seu Access Token de TESTE primeiro
$MP_ACCESS_TOKEN = "TEST-APP_USR-8463743141229895-111115-6d5fe7e0fdfda24f28f043b78683fee6-2982510408";

// (opcional) URL base pública para o webhook e back_urls
// Em desenvolvimento local, use um túnel tipo ngrok para expor: https://seu-subdominio.ngrok.app
$BASE_URL = "http://localhost/a/";

MercadoPago\SDK::setAccessToken($MP_ACCESS_TOKEN);

// Função util para montar URL absoluta
function base_url($path) {
  global $BASE_URL;
  return rtrim($BASE_URL, "/") . "/" . ltrim($path, "/");
}
