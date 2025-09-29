<?php
// API de recuperação de senha
header('Content-Type: application/json');
require_once '../vendor/autoload.php';
require_once '../conexao.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuração do Gmail para produção
$smtpUser = getenv('SMTP_USER'); // Defina no ambiente do servidor
$smtpPass = getenv('SMTP_PASS'); // Defina no ambiente do servidor

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = isset($data['email']) ? trim($data['email']) : '';
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Email é obrigatório.']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['error' => 'Email não encontrado.']);
        exit;
    }
    $token = bin2hex(random_bytes(16));
    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt2 = $pdo->prepare('INSERT INTO redefinir_senha (usuario_id, token, expiracao, usado) VALUES (?, ?, ?, 0)');
    $stmt2->execute([$usuario['id'], $token, $expiracao]);
    $link = 'https://' . $_SERVER['HTTP_HOST'] . '/senhas/recup.php?token=' . $token;
    // Envia e-mail
    try {
    $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom($smtpUser, 'SquinaXV');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de senha - SquinaXV';
        $mail->Body = 'Olá,<br>Recebemos uma solicitação para redefinir sua senha.<br>Acesse: <a href="' . $link . '">' . $link . '</a><br>Este link expira em 1 hora.';
        $mail->AltBody = strip_tags($mail->Body);
        $mail->send();
        echo json_encode(['success' => 'Email enviado com instruções.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao enviar email: ' . $mail->ErrorInfo]);
    }
    exit;
}
// Endpoint para redefinir senha
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = isset($data['token']) ? trim($data['token']) : '';
    $nova_senha = isset($data['nova_senha']) ? $data['nova_senha'] : '';
    $confirmar_senha = isset($data['confirmar_senha']) ? $data['confirmar_senha'] : '';
    if (!$token || !$nova_senha || !$confirmar_senha) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados obrigatórios ausentes.']);
        exit;
    }
    if ($nova_senha !== $confirmar_senha) {
        http_response_code(400);
        echo json_encode(['error' => 'As senhas não conferem.']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT usuario_id, expiracao, usado FROM redefinir_senha WHERE token = ?');
    $stmt->execute([$token]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dados) {
        http_response_code(404);
        echo json_encode(['error' => 'Token inválido.']);
        exit;
    }
    if ($dados['usado'] || strtotime($dados['expiracao']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'Token expirado ou já usado.']);
        exit;
    }
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $stmt3 = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
    $stmt3->execute([$senha_hash, $dados['usuario_id']]);
    $stmt4 = $pdo->prepare('UPDATE redefinir_senha SET usado = 1 WHERE token = ?');
    $stmt4->execute([$token]);
    echo json_encode(['success' => 'Senha redefinida com sucesso!']);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Método não permitido.']);
