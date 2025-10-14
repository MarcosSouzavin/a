<?php
/**
 * SDK simplificado do Mercado Pago (sem composer)
 * Usado para criar preferências e consultar pagamentos.
 */

function mp_post($url, $access_token, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function mp_get($url, $access_token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function mp_criar_preferencia($access_token, $data) {
    $url = "https://api.mercadopago.com/checkout/preferences";
    return mp_post($url, $access_token, $data);
}

function mp_consultar_pagamento($access_token, $id) {
    $url = "https://api.mercadopago.com/v1/payments/" . urlencode($id);
    return mp_get($url, $access_token);
}
?>
