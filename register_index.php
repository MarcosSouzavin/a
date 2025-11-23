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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/antijingoist/opendyslexic/opendyslexic.css">
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="css/register.css" media="screen" />
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <script src="js/cpf-format.js"></script>
</head>
<body>
  <!-- Barra de Acessibilidade Vendraly/SquinaXV -->
  <!-- WIDGET ACESSIBILIDADE VENDRALY -->
<div id="acess-widget-button">
    ⚙️
</div>
<div id="reading-mask"></div>
<div id="acess-panel">
    <h3>Acessibilidade</h3>

    <button onclick="fonteMenor()">A-</button>
    <button onclick="fonteNormal()">A</button>
    <button onclick="fonteMaior()">A+</button>
    <hr>

    <button onclick="alternarContraste()">Contraste</button>
    <button id="btnDys">Disléxico</button>
    <button id="btnDaltonismo">Daltonismo</button>
    <hr>
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
<script>
// Abrir painel
const btnWidget = document.getElementById("acess-widget-button");
const painel = document.getElementById("acess-panel");

btnWidget.addEventListener("click", () => {
    painel.classList.toggle("open");
});

// Fonte
let tamanhoBase = 100;
function aplicarFonte() {
  document.documentElement.style.fontSize = tamanhoBase + "%";
  localStorage.setItem("tamanhoFonte", tamanhoBase);
}
function fonteMenor() { if (tamanhoBase > 70) { tamanhoBase -= 10; aplicarFonte(); } }
function fonteNormal() { tamanhoBase = 100; aplicarFonte(); }
function fonteMaior() { if (tamanhoBase < 160) { tamanhoBase += 10; aplicarFonte(); } }

// Contraste
function alternarContraste() {
  document.body.classList.toggle("alto-contraste");
  localStorage.setItem("altoContraste", document.body.classList.contains("alto-contraste") ? "1" : "0");
}

// Dyslexic
document.getElementById("btnDys").onclick = () => {
    document.body.classList.toggle("opendyslexic");
    localStorage.setItem("opendyslexic", document.body.classList.contains("opendyslexic") ? "1" : "0");
};

// Daltonismo
document.getElementById("btnDaltonismo").onclick = () => {
    document.body.classList.toggle("daltonismo");
    localStorage.setItem("daltonismo", document.body.classList.contains("daltonismo") ? "1" : "0");
};

// Máscara leitura
document.getElementById("btnMascara").onclick = () => {
    document.body.classList.toggle("mascara-leitura");
    localStorage.setItem("mascaraLeitura", document.body.classList.contains("mascara-leitura") ? "1" : "0");
};
document.addEventListener("mousemove", function(e) {
  const mask = document.getElementById("reading-mask");
  if (!mask || !document.body.classList.contains("mascara-leitura")) return;
  mask.style.top = e.clientY - 60 + "px";
});

// Cursor
document.getElementById("btnCursor").onclick = () => {
    document.body.classList.toggle("cursor-grande");
    localStorage.setItem("cursorGrande", document.body.classList.contains("cursor-grande") ? "1" : "0");
};

// Modo leitura
document.getElementById("btnLeitura").onclick = () => {
    document.body.classList.toggle("modo-leitura");
    localStorage.setItem("modoLeitura", document.body.classList.contains("modo-leitura") ? "1" : "0");
};

// Carregar preferências
(function() {
  let fs = localStorage.getItem("tamanhoFonte"); 
  if (fs) { tamanhoBase = parseInt(fs); aplicarFonte(); }

  if (localStorage.getItem("altoContraste")==="1") document.body.classList.add("alto-contraste");
  if (localStorage.getItem("opendyslexic")==="1") document.body.classList.add("opendyslexic");
  if (localStorage.getItem("daltonismo")==="1") document.body.classList.add("daltonismo");
})();
</script>
</body>
</html>