<?php
// Arquivo: register_index.php (Seu formulário de registro)
session_start();
$erro = '';
if (isset($_SESSION['erro'])) {
    $erro = $_SESSION['erro'];
    unset($_SESSION['erro']);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SquinaXV - Registro</title>
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="css/register.css" media="screen" />
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <script src="js/cpf-format.js"></script>
</head>
<body>
    <div class="logo-container2">
        <img src="img/stg.jpeg" alt="Logo Santa Gula">
    </div>
    
    <div class="login-box">
        <div class="header">
            <h1>SquinaXV</h1>
            <p>Sabor, Qualidade e Família<br>O melhor da região</p>
        </div>
        
       <form action="register.php" method="post" onsubmit="return validarSenha()">
            <div class="input-group">
                <input type="text" name="usuario" placeholder="Usuário" required>
            </div>

            <div class="input-group">  
                <input type="text" name="cpf" id="cpf" placeholder="CPF" maxlength="14" required 
                       title="Digite um CPF no formato: 000.000.000-00" />
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="text" name="telefone" placeholder="Telefone" required maxlength="15" id="telefone">
            </div>
          <div class="input-group password-container">
    <input type="password" name="senha" placeholder="Senha" id="senha" required>
    <span class="toggle-password" onclick="togglePassword()">👁️</span>
</div>
<span id="senha-erro" style="font-size:13px; display:block; margin-top:5px;"></span>


            <div class="links-group">
                <a href="login_index.php">Já tem uma conta? Faça login</a>
            </div>
            
            <button class="btn" type="submit">REGISTRAR</button>
        </form>

    
        <?php if (!empty($erro)): ?>
            <p style="color: <?php echo (strpos($erro, 'sucesso') !== false) ? 'green' : 'blue'; ?>">
                <?php echo htmlspecialchars($erro); ?>
            </p>
        <?php endif; ?>
    </div>
    <!-- VLibras Widget -->
<div vw class="enabled">
  <div vw-access-button class="active"></div>
  <div vw-plugin-wrapper>
    <div class="vw-plugin-top-wrapper"></div>
  </div>
</div>

<script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script>
  new window.VLibras.Widget('https://vlibras.gov.br/app');
</script>
<script>
function validarSenha() {
    const senha = document.getElementById("senha").value;
    const requisitoMsg = document.getElementById("senha-erro");

    const regra = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/;

    if (!regra.test(senha)) {
        requisitoMsg.style.color = "red";
        requisitoMsg.textContent = 
            "A senha deve ter mínimo 6 caracteres, contendo LETRA MAIÚSCULA, minúscula, número e caractere especial.";
        return false;
    }

    requisitoMsg.textContent = "";
    return true;
}

function togglePassword() {
    const campo = document.getElementById("senha");
    campo.type = campo.type === "password" ? "text" : "password";
}
</script>
</body>
</html>