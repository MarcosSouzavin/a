<?php
$paymentId = $_GET['payment_id'] ?? '---';
$status = $_GET['status'] ?? 'failure';
$preferenceId = $_GET['preference_id'] ?? '---';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falha no Pagamento</title>
    <style>
        body {
            background: #fff5f5;
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
        h1 { color: #e74c3c; }
        .btn {
            background: #e74c3c;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>✖ Pagamento Não Concluído</h1>
    <p>Tivemos um problema ao processar o pagamento.</p>
    <p><b>ID da Preferência:</b> <?= htmlspecialchars($preferenceId) ?></p>

    <a href="/checkout.php" class="btn">Tentar Novamente</a>
</div>

</body>
</html>
