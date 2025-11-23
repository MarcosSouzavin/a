<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SquinaXV - Login</title>
    <script>
        localStorage.removeItem('cart');
    </script>
    <script src="js/login.js"></script>
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="css/estilo.css" media="screen" /> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/antijingoist/opendyslexic/opendyslexic.css">
</head>
<body>
    <!-- WIDGET ACESSIBILIDADE VENDRALY -->
<div id="acess-widget-button">
    ⚙️
</div>

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