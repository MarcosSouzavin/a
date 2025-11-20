<?php
// Recupera dados enviados pelo Mercado Pago
$paymentId = $_GET['payment_id'] ?? '---';
$status = $_GET['status'] ?? 'approved';
$preferenceId = $_GET['preference_id'] ?? '---';
$external = $_GET['external_reference'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Aprovado</title>
    <style>
        body {
            background: #f5f7ff;
            font-family: Arial, sans-serif;
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            height: 100vh;
        }
        .box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 0 20px rgba(0,0,0,.1);
            text-align: center;
        }
        h1 { color: #2ecc71; }
        p { color: #333; }
        .btn {
            background: #2ecc71;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>✔ Pagamento Aprovado!</h1>
    <p>Obrigado por seu pedido 😊</p>
    <p><b>ID do Pagamento:</b> <?= htmlspecialchars($paymentId) ?></p>
    <p><b>ID da Preferência:</b> <?= htmlspecialchars($preferenceId) ?></p>

    <a href="/API/pedidos?= urlencode($paymentId) ?>" class="btn">
        Acompanhar Pedido
    </a>
</div>

</body>
</html>
