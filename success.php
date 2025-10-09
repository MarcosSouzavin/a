<?php
session_start();
// Clear the cart after successful payment
$_SESSION['cart'] = [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Aprovado - SquinaXV</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="img/stg.ico" type="image/x-icon" />
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">
                <img src="img/stg.jpeg" alt="Delicias Gourmet" class="logo-image" />
                SquinaXV
            </h1>
        </div>
    </header>
    <main class="container">
        <h2>Pagamento Aprovado!</h2>
        <p>Seu pedido foi processado com sucesso. Obrigado por comprar conosco!</p>
        <p>Você receberá uma confirmação por e-mail em breve.</p>
        <a href="cliente.php">Voltar ao Início</a>
    </main>
</body>
</html>
