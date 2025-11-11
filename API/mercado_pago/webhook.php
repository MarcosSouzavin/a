<?php
require __DIR__ . '/mp_config.php';

// Recebe notificação do MP
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// É normal receber diferentes tipos (payment, merchant_order)
if (!empty($data["type"]) && $data["type"] === "payment" && !empty($data["data"]["id"])) {
  $paymentId = $data["data"]["id"];

  // Busca o pagamento para confirmar status
  $payment = MercadoPago\Payment::find_by_id($paymentId);

  $pedidoId = $payment->external_reference ?? "";
  $status   = $payment->status ?? "";
  $valor    = $payment->transaction_amount ?? 0;
  $metodo   = $payment->payment_method_id ?? "";

  // TODO: atualizar o status no seu banco:
  // ex.: UPDATE pedidos SET status_pagamento=?, mp_payment_id=?, mp_metodo=? WHERE codigo=?
  //      valores: $status, $paymentId, $metodo, $pedidoId

  // Log simples para testes
  file_put_contents(__DIR__ . "/pagamentos.log",
    sprintf("%s | pedido=%s | payment=%s | status=%s | valor=%.2f | metodo=%s\r\n",
      date('Y-m-d H:i:s'), $pedidoId, $paymentId, $status, $valor, $metodo
    ),
    FILE_APPEND
  );
}

// O MP exige 200 OK pra não reenviar em loop
http_response_code(200);
