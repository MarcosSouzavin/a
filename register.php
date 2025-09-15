<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register_index.php");
    exit();
}

$usuario = trim($_POST['usuario']);
$cpf = trim($_POST['cpf']);
$email = trim($_POST['email']);
$telefone = trim($_POST['telefone']);
$senha = trim($_POST['senha']);

// CPF chk
$sql_check_cpf = "SELECT COUNT(*) FROM usuarios WHERE cpf = :cpf";
$stmt_check_cpf = $pdo->prepare($sql_check_cpf);
$stmt_check_cpf->bindParam(':cpf', $cpf);
$stmt_check_cpf->execute();
$cpf_exists = $stmt_check_cpf->fetchColumn();

if ($cpf_exists > 0) {
    $_SESSION['erro'] = "Erro: CPF já cadastrado!";
    header("Location: register_index.php");
    exit();
}

$sql_check_email = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
$stmt_check_email = $pdo->prepare($sql_check_email);
$stmt_check_email->bindParam(':email', $email);
$stmt_check_email->execute();
$email_exists = $stmt_check_email->fetchColumn();

if ($email_exists > 0) {
    $_SESSION['erro'] = "Erro: E-mail já cadastrado!";
    header("Location: register_index.php");
    exit();
}

// Tel chk
$sql_check_telefone = "SELECT COUNT(*) FROM usuarios WHERE telefone = :telefone";
$stmt_check_telefone = $pdo->prepare($sql_check_telefone);
$stmt_check_telefone->bindParam(':telefone', $telefone);
$stmt_check_telefone->execute();
$telefone_exists = $stmt_check_telefone->fetchColumn();

if ($telefone_exists > 0) {
    $_SESSION['erro'] = "Erro: Telefone já cadastrado!";
    header("Location: register_index.php");
    exit();
}
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
// send form
$sql_insert = "INSERT INTO usuarios (usuario, cpf, email, telefone, senha) VALUES (:usuario, :cpf, :email, :telefone, :senha)";
$stmt_insert = $pdo->prepare($sql_insert);
$stmt_insert->bindParam(':usuario', $usuario);
$stmt_insert->bindParam(':cpf', $cpf);
$stmt_insert->bindParam(':email', $email);
$stmt_insert->bindParam(':telefone', $telefone);
$stmt_insert->bindParam(':senha', $senha_hash);

if ($stmt_insert->execute()) {
    $_SESSION['erro'] = "Registro realizado com sucesso! Faça login.";
    header("Location: login_index.php");
    exit();
} else {
    $_SESSION['erro'] = "Erro ao registrar usuário. Tente novamente.";
    header("Location: register_index.php");
    exit();
}