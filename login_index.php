<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SquinaXV - Login</title>
    <script>
        // Limpa o carrinho ao carregar a página de login
        localStorage.removeItem('cart');
    </script>
    <script src="js/login.js"></script>
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="css/estilo.css" media="screen" /> 
</head>
<body>
    <div class="logo-container">
        <img src="img/stg.jpeg" alt="Logo SquinaXV" >
    </div>
    <div class="login-box">
        <div class="header">
            <h1>SquinaXV</h1>
            <p>Sabor, Qualidade e Familia<br>O melhor da região</p>
        </div>
    <form action="login.php" method="post">
        <div class="input-group">
            <input type="text" name="usuario" placeholder="Usuário" required>
        </div>
        <div class="input-group password-container">
            <input type="password" name="senha" placeholder="Senha" id="senha" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
            <?php
      if(isset($_GET['error'])) {
    
          echo '<p class="error">' . htmlspecialchars($_GET['error']) . '</p>';
      }
    ?>
        </div>
        <a href="senhas/recup.php">Esqueceu senha?</a>
        <br>
        <a href="register_index.php">Não tenho conta.</a>
        <br>
        <button class="btn" type="submit">ENTRAR</button>
    </form>
    <?php if (!empty($erro)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($erro); ?></p>
    <?php endif; ?>
    </div>
</body>
</html>