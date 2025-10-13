<?php
session_start();
include '../conexao.php';

require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha - SquinaXV</title>
    <link rel="stylesheet" href="../css/recup.css">
    <link rel="icon" href="../img/stg.ico" type="image/x-icon">
</head>
<body>
<header class="header">
    <div class="container_2">
        <h1 class="logo">
            <img src="../img/stg.jpeg" alt="Delicias Gourmet" class="logo-image">
            SquinaXV
        </h1>
        <div class="button-container">
            <button type="button" class="back-button" onclick="history.back()">Voltar</button>
        </div>
    </div>
</header>

<div class="container">
    <h1 class="recup">Recuperar Senha</h1>

<?php
$msg = "";

// ------------------------------------------
// CONFIGURAÇÃO SMTP DIRETA (ajusta aqui!)
// ------------------------------------------
$smtpUser = "marcos2008campinas@gmail.com"; // teu e-mail do remetente
$smtpPass = "imbf awgk jszt buyx "; // senha de app do Gmail (não a senha normal)
$smtpHost = "smtp.gmail.com";
$smtpPort = 587;

// ------------------------------------------
// PEDIDO DE LINK POR E-MAIL
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $token = bin2hex(random_bytes(16));
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt2 = $pdo->prepare("INSERT INTO redefinir_senha (usuario_id, token, expiracao, usado) VALUES (?, ?, ?, 0)");
        $stmt2->execute([$usuario['id'], $token, $expiracao]);

        // link absoluto do servidor
       $link = "https://projetosetim.com.br/2025/php1/senhas/recup.php?token=" . $token;


        $logFile = __DIR__ . '/../storage/logs/smtp.log';
        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0755, true);
        }

        // Envio do e-mail
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;

            $mail->setFrom($smtpUser, 'SquinaXV');
            $mail->addAddress($email);
            $mail->isHTML(false);
            $mail->Subject = "Redefinição de senha - SquinaXV";
            $mail->Body = "Olá!\n\nRecebemos uma solicitação para redefinir sua senha.\n"
                        . "Acesse o link abaixo (válido por 1 hora):\n\n{$link}\n\n"
                        . "Caso não tenha sido você, ignore este e-mail.\n\nEquipe SquinaXV.";

            $mail->send();
            $msg = "<div class='success'>✅ Enviamos um email com instruções para redefinir sua senha. Verifique sua caixa de entrada ou spam.</div>";
        } catch (Exception $e) {
            file_put_contents($logFile, date('[Y-m-d H:i:s]') . " [ERRO] " . $e->getMessage() . "\n", FILE_APPEND);
            $msg = "<div class='error'>⚠️ Erro ao enviar o e-mail. Tente novamente ou contate o suporte.</div>";
        }
    } else {
        $msg = "<div class='error'>E-mail não encontrado.</div>";
    }
}

// ------------------------------------------
// REDEFINIÇÃO DE SENHA (via token)
// ------------------------------------------
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT usuario_id, expiracao, usado FROM redefinir_senha WHERE token = ?");
    $stmt->execute([$token]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        $msg = "<div class='error'>Token inválido.</div>";
    } elseif ($dados['usado']) {
        $msg = "<div class='error'>Token já foi utilizado.</div>";
    } elseif (strtotime($dados['expiracao']) < time()) {
        $msg = "<div class='error'>Token expirado. Solicite uma nova redefinição.</div>";
    } else {
        // redefinição
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
            $nova = $_POST['nova_senha'];
            $confirma = $_POST['confirmar_senha'];

            if ($nova !== $confirma) {
                $msg = "<div class='error'>As senhas não conferem.</div>";
            } else {
                $senha_hash = password_hash($nova, PASSWORD_DEFAULT);
                $stmt3 = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt3->execute([$senha_hash, $dados['usuario_id']]);
                $stmt4 = $pdo->prepare("UPDATE redefinir_senha SET usado = 1 WHERE token = ?");
                $stmt4->execute([$token]);
                $msg = "<div class='success'>✅ Senha redefinida com sucesso! Você já pode fazer login.</div>";
            }
        }
    }
}

// ------------------------------------------
// EXIBE MENSAGEM
// ------------------------------------------
if (!empty($msg)) echo $msg;

// ------------------------------------------
// FORMULÁRIOS (modo e-mail ou modo token)
// ------------------------------------------
if (isset($_GET['token']) && isset($dados) && !$dados['usado'] && strtotime($dados['expiracao']) > time()) {
?>
    <form method="POST" class="form">
        <label>Nova Senha:</label>
        <input type="password" name="nova_senha" required minlength="6">
        <label>Confirmar Senha:</label>
        <input type="password" name="confirmar_senha" required minlength="6">
        <button type="submit">Redefinir Senha</button>
    </form>
<?php
} elseif (!isset($_GET['token'])) {
?>
    <form method="POST" class="form">
        <label>Digite seu E-mail:</label>
        <input type="email" name="email" required>
        <button type="submit">Enviar Link de Redefinição</button>
    </form>
<?php
}
?>

<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f9f9f9;
  margin: 0;
}
.container {
  max-width: 400px;
  margin: 60px auto;
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
label { display:block; margin-top:10px; }
input {
  width:100%;
  padding:10px;
  border:1px solid #ccc;
  border-radius:6px;
}
button {
  margin-top:15px;
  background:#b3ab3a;
  color:white;
  border:none;
  padding:10px 15px;
  border-radius:6px;
  cursor:pointer;
}
.error { color:#c0392b; background:#f9e0e0; padding:8px; border-radius:6px; }
.success { color:#2d8a45; background:#e7f8ec; padding:8px; border-radius:6px; }
</style>
</div>
</body>
</html>
