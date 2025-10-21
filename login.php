<?php
session_start();

require_once 'conexao.php'; // este deve criar $pdo (PDO conectado)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['email'] ?? $_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $lembrar = isset($_POST['lembrar']);

    // tenta por email ou usuario
    $sql = "SELECT id, nome, usuario, email, senha FROM usuarios WHERE email = :login OR usuario = :login";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':login', $login);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'] ?? $user['nome'];
            $_SESSION['email'] = $user['email'];

            if ($lembrar) {
                $token = bin2hex(random_bytes(32));
                setcookie(
                    "remember_token",
                    $token,
                    time() + (86400 * 30),
                    "/",
                    "projetosetim.com.br",
                    true,
                    true
                );
                $update = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?");
                $update->execute([$token, $user['id']]);
            }

            header("Location: cliente.php");
            exit;
        } else {
            header('Location: login_index.php?error=' . urlencode("Senha incorreta!"));
            exit;
        }
    } else {
        header('Location: login_index.php?error=' . urlencode("Usuário não encontrado!"));
        exit;
    }
}
