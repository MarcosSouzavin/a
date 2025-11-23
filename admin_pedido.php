<?php
require 'conexao.php';
$pedido = $_GET['pedido_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO pedidos_status (pedido_id, status) VALUES (?, ?)");
    $stmt->execute([$pedido, $status]);

    echo "Status atualizado!";
}
?>

<form method="post">
    <h3>Atualizar status do pedido <?= $pedido ?></h3>

    <select name="status">
        <option value="recebido">Recebido</option>
        <option value="preparando">Preparando</option>
        <option value="pronto">Pronto</option>
        <option value="saiu_para_entrega">Saiu para entrega</option>
        <option value="entregue">Entregue</option>
    </select>
    <button type="submit">Salvar</button>
</form>
