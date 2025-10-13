<?php
/**
 * SDK simplificado do Mercado Pago (versão WinSCP-ready)
 * Permite criar preferências e consultar pagamentos sem composer
 */

function mp_criar_preferencia($access_token, $dados) {
    $url = "https://api.mercadopago.com/checkout/preferences";
    return mp_post($url, $access_token, $dados);
}

function mp_consultar_pagamento($access_token, $id) {
    $url = "https://api.mercadopago.com/v1/payments/" . urlencode($id);
    return mp_get($url, $access_token);
}

function mp_post($url, $access_token, $dados) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function mp_get($url, $access_token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
?>
