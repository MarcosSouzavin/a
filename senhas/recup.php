<?php
session_start();

include '../conexao.php';

// Carrega o autoload do Composer
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Dependências não instaladas. Rode no terminal: composer require phpmailer/phpmailer');
}
require $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Em produção, configure as variáveis de ambiente SMTP_USER e SMTP_PASS no painel do servidor, .htaccess ou painel de hospedagem.
// Nunca coloque as credenciais diretamente no código!
// Exemplo para Apache (.htaccess na raiz):
// SetEnv SMTP_USER seu@email.com
// SetEnv SMTP_PASS sua_senha_de_app
// No código, apenas use getenv como abaixo:
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
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
     <button type="button" class="back-button" onclick="history.back()" aria-label="Voltar à página anterior">Voltar</button>
</div>
    </header>
<div class="container">
    <h1 class="recup">Recuperar Senha</h1>

<?php

$msg = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

  
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $token = bin2hex(random_bytes(16));
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt2 = $pdo->prepare("INSERT INTO redefinir_senha (usuario_id, token, expiracao, usado) VALUES (?, ?, ?, 0)");
        $stmt2->execute([$usuario['id'], $token, $expiracao]);

        $link = "http://localhost/proyecto/senhas/recup.php?token=" . $token;

        $logFile = __DIR__ . '/../storage/logs/smtp.log';
        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0755, true);
        }

        // pega credenciais (trim para evitar espaços)
    $smtpUser = trim(getenv('SMTP_USER') ?: '');
    $smtpPass = trim(getenv('SMTP_PASS') ?: '');

        if (empty($smtpUser) || empty($smtpPass)) {
            $msg = "<div class='error'>Credenciais SMTP não definidas. Defina SMTP_USER e SMTP_PASS e reinicie o Apache.</div>";
        } else {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                // grava debug no arquivo
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) use ($logFile) {
                    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " [$level] $str\n", FILE_APPEND);
                };

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // remover em produção
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
                $mail->setFrom($smtpUser, 'SquinaXV');
                $mail->addAddress($email);

                $mail->isHTML(false);
                $mail->Subject = "Alterar senha - SquinaXV";
                $mail->Body    = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha. caso não tenha sido você, desconsidere! 
                Acesse:\n\n{$link}\n\nEste link expira em 1 hora.";
                $mail->AltBody = strip_tags($mail->Body);

                $mail->send();
                $msg = "<div class='success'>Enviamos um email com instruções para redefinir sua senha.</div>";
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                // registra o erro e o token no log, mas NÃO mostra o token ao usuário
                file_put_contents($logFile,
                    date('[Y-m-d H:i:s]') . " [ERROR] " . $e->getMessage()
                    . " | Token: {$token} | Link: {$link}\n",
                    FILE_APPEND
                );

                $msg = "<div class='error'>Falha ao enviar o email. Verifique sua caixa de spam ou contate o suporte.</div>";
            } catch (\Exception $e) {
                // registra exceção genérica e token, mas NÃO exibe nada sensível ao usuário
                file_put_contents($logFile,
                    date('[Y-m-d H:i:s]') . " [EX] " . $e->getMessage()
                    . " | Token: {$token} | Link: {$link}\n",
                    FILE_APPEND
                );

                $msg = "<div class='error'>Erro inesperado. Por favor, contate o suporte.</div>";
            }
        }
    } else {
        $msg = "<div class='error'>Email não encontrado.</div>";
    }
}


if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT usuario_id, expiracao, usado FROM redefinir_senha WHERE token = ?");
    $stmt->execute([$token]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        $msg = "<div class='error'>Token inválido.</div>";
    } elseif ($dados['usado']) {
        $msg = "<div class='error'>Token já foi usado.</div>";
    } elseif (strtotime($dados['expiracao']) < time()) {
        $msg = "<div class='error'>Token expirado.</div>";
    } else {
   
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
            $nova_senha = $_POST['nova_senha'];
            $confirmar_senha = $_POST['confirmar_senha'];

            if ($nova_senha !== $confirmar_senha) {
                $msg = "<div class='error'>As senhas não conferem.</div>";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt3 = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt3->execute([$senha_hash, $dados['usuario_id']]);

                $stmt4 = $pdo->prepare("UPDATE redefinir_senha SET usado = 1 WHERE token = ?");
                $stmt4->execute([$token]);

                $msg = "<div class='success'>Senha redefinida com sucesso!</div>";
            }
        }
    }
}


if (!empty($msg)) {
    echo $msg;
}


if (isset($_GET['token']) && isset($dados) && !$dados['usado'] && strtotime($dados['expiracao']) > time()) {
    ?>
    <form method="POST" class="form">
        <label>Nova Senha:</label>
        <input type="password" name="nova_senha" required>
        <label>Confirmar Senha:</label>
        <input type="password" name="confirmar_senha" required>
        <button type="submit">Redefinir Senha</button>
    </form>
    <?php
} elseif (!isset($_GET['token'])) {
    ?>
    <form method="POST" class="form">
        <label>Email:</label>
        <input type="email" name="email" required>
        <button type="submit">Enviar Link de Redefinição</button>
    </form>
    <?php
}
?>

</div>
</body>
</html>
