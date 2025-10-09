<?php
session_start();
// Do not clear the cart on failure, so user can try again
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Falhou - SquinaXV</title>
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
        <h2>Pagamento Falhou</h2>
        <p>Houve um problema com o processamento do seu pagamento. Por favor, tente novamente.</p>
        <a href="checkout.php">Voltar ao Checkout</a>
    </main>
</body>
</html>
