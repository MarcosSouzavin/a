<?php
header("Content-Type: application/json");
require '../conexao.php'; // sua conexão PDO

if (!isset($_GET['pedido_id'])) {
    echo json_encode(["erro" => "pedido_id não informado"]);
    exit;
}

$pedido = intval($_GET['pedido_id']);

$stmt = $pdo->prepare("SELECT status, atualizado_em FROM pedidos_status 
                      WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$pedido]);
$dado = $stmt->fetch(PDO::FETCH_ASSOC);

if ($dado) {
    echo json_encode($dado);
} else {
    echo json_encode(["status" => "aguardando"]);
}
