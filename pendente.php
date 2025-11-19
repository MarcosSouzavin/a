<?php
$paymentId = $_GET['payment_id'] ?? '---';
$status = $_GET['status'] ?? 'pending';
$preferenceId = $_GET['preference_id'] ?? '---';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Pendente</title>
    <style>
        body {
            background: #fffbea;
            font-family: Arial, sans-serif;
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            height: 100vh;
        }
        .box {
            background: white;
            padding: 40px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,.1);
        }
        h1 { color: #f1c40f; }
    </style>
</head>
<body>

<div class="box">
    <h1>⏳ Pagamento Pendente</h1>
    <p>Aguardando confirmação do Mercado Pago.</p>
    <p><b>ID do Pagamento:</b> <?= htmlspecialchars($paymentId) ?></p>
    <p><b>ID da Preferência:</b> <?= htmlspecialchars($preferenceId) ?></p>

    <p>Assim que for aprovado, você será avisado!</p>
</div>

</body>
</html>
