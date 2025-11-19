<?php
// Página exibida quando o pagamento é aprovado
// O Mercado Pago envia via GET alguns dados importantes:
// - payment_id
// - external_reference
// - status
// - merchant_order_id

$payment_id = $_GET['payment_id'] ?? null;
$status = $_GET['status'] ?? null;
$order_id = $_GET['merchant_order_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pedido aprovado - SquinaXV</title>
<style>
body { background:#f6f6f6; font-family:sans-serif; text-align:center; padding-top:80px; }
.card { background:white; width:350px; margin:auto; padding:25px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { color:#2ecc71; }
</style>
</head>
<body>

<div class="card">
    <h1>Pagamento Aprovado! 🎉</h1>
    <p>Obrigado por comprar conosco.</p>

    <p><strong>ID do Pagamento:</strong> <?= htmlspecialchars($payment_id) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($status) ?></p>

    <a href="index.html">Voltar ao cardápio</a>
</div>

</body>
</html>
