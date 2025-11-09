<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

 $host = 'localhost';
    $dbname = 'u557720587_2025_php01'; 
  $dbUser = 'u557720587_2025_php01';   
  $dbPass = 'Mtec@php1';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$email = $_SESSION['email'];
$message = '';
$error = '';

// carrega dados atuais
$sql = "SELECT cpf, usuario, telefone, email FROM usuarios WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);
$dadosUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dadosUsuario) {
    echo "Usuário não encontrado!";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsuario = trim($_POST['usuario'] ?? '');
    $newEmail   = trim($_POST['email'] ?? '');
    $newTelefone= trim($_POST['telefone'] ?? '');

    if ($newUsuario === '' || $newEmail === '' ) {
        $error = 'Nome e email são obrigatórios.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } else {
        if ($newEmail !== $email) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $check->execute([':email' => $newEmail]);
            if ($check->fetchColumn() > 0) {
                $error = 'E-mail já cadastrado por outro usuário.';
            }
        }
    }

    if ($error === '') {
        try {
            $update = $pdo->prepare("UPDATE usuarios SET usuario = :usuario, email = :newemail, telefone = :telefone WHERE email = :oldemail");
            $update->execute([
                ':usuario' => $newUsuario,
                ':newemail' => $newEmail,
                ':telefone' => $newTelefone,
                ':oldemail' => $email
            ]);

            if ($newEmail !== $email) {
                $_SESSION['email'] = $newEmail;
                $email = $newEmail;
            }

            $message = 'Dados atualizados com sucesso.';
            // recarrega dados para a exibição do formulário
            $stmt = $pdo->prepare("SELECT cpf, usuario, telefone, email FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $dadosUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Editar usuário</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Added external stylesheet -->
    <link rel="stylesheet" href="../css/edit_user.css">
</head>
<body>
    <div class="edit-container">
        <header class="edit-header">
            <h2>Editar usuário</h2>
        </header>

        <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="post" action="edit_user.php" class="edit-form">
            <div class="field">
                <label class="label">CPF</label>
                <div class="value"><?php echo htmlspecialchars($dadosUsuario['cpf']); ?></div>
            </div>

            <div class="field">
                <label class="label">Nome</label>
                <input type="text" name="usuario" value="<?php echo htmlspecialchars($dadosUsuario['usuario']); ?>" required>
            </div>

            <div class="field">
                <label class="label">Telefone</label>
                <input type="text" name="telefone" value="<?php echo htmlspecialchars($dadosUsuario['telefone']); ?>">
            </div>

            <div class="field">
                <label class="label">E-mail</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($dadosUsuario['email']); ?>" required>
            </div>

            <div class="actions">
                <button type="submit" class="btn primary">Salvar</button>
                <a href="../dados.php" class="btn link">Voltar</a>
                <!-- Botão adicional para trocar senha -->
                <a href="../senhas/recup.php" class="btn link" style="margin-left:6px;">Trocar senha</a>
            </div>
        </form>
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

</body>
</html>