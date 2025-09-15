<?php
session_start();
require_once '../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['saldo' => 0]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$saldo = $stmt->fetchColumn();

echo json_encode(['saldo' => number_format($saldo ?: 0, 2, ',', '.')]);
?>