<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.html"); 
    exit();
}

if(true){
  $host = 'localhost';
  $dbname = 'users'; 
  $dbUser = 'admin';   
  $dbPass = 'admin';
}else{
  $host = 'https://auth-db1206.hstgr.io/';
  $dbname = 'u557720587_2025_php01'; 
  $dbUser = 'u557720587_2025_php01';   
  $dbPass = 'Mtec@php1';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$email = $_SESSION['email'];

$sql = "SELECT cpf, usuario, telefone, email FROM usuarios WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $dadosUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "Usuário não encontrado!";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SquinaXV - Dados</title>
      <link rel="stylesheet" href="css/dates.css">
    <style>
        .btn-edit {
            display:inline-block;
            padding:8px 14px;
            background:#3a86ff;
            color:#fff;
            text-decoration:none;
            border-radius:4px;
        }
    </style>
</head>
<body>
    <div class="logo">
        <h1 class="header">
            <img src="img/stg.jpeg" alt="Delicias Gourmet" class="logo-image">
            SquinaXV
        </h1>
    </div>
    
    <div class="container">
        <h1>Seja bem-vindo <?php echo htmlspecialchars($dadosUsuario['usuario']); ?></h1>

        <!-- Mantém exibição dos dados, mas remove formulário de edição daqui -->
        <table>
            <tr><th>CPF</th><td><?php echo htmlspecialchars($dadosUsuario['cpf']); ?></td></tr>
            <tr><th>Nome</th><td><?php echo htmlspecialchars($dadosUsuario['usuario']); ?></td></tr>
            <tr><th>Telefone</th><td><?php echo htmlspecialchars($dadosUsuario['telefone']); ?></td></tr>
            <tr><th>E-mail</th><td><?php echo htmlspecialchars($dadosUsuario['email']); ?></td></tr>
        </table>

        <div style="margin-top:20px;">
            <a href="user_edit/edit_user.php" class="btn-edit">Editar usuário</a>
            <a class="logout" style="margin-left:10px; text-decoration: none;" href="logout_users.php">Voltar</a>
        </div>
    </div>
</body>
</html>