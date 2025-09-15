<?php
session_start();
require_once '../conexao.php';
require_once 'function_balance.php';

//chk de login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}
$nome_usuario = $_SESSION['usuario'] ?? 'Usuário';
$usuario_id = $_SESSION['usuario_id'];

if (isset($_POST['adicionar'])) {
    $valor = floatval($_POST['valor']);
    $descricao = $_POST['descricao'] ?? 'Crédito manual';
    if ($valor > 0) {
        adicionarSaldo($pdo, $usuario_id, $valor, $descricao);
    }
}

// 👉 RETIRAR SALDO
if (isset($_POST['retirar'])) {
    $valor = floatval($_POST['valor']);
    $descricao = $_POST['descricao'] ?? 'Compra';
    if ($valor > 0) {
        descontarSaldo($pdo, $usuario_id, $valor, $descricao);
    }
}

$saldoAtual = buscarSaldo($pdo, $usuario_id);

$stmt = $pdo->prepare("SELECT * FROM saldos_usuarios WHERE usuario_id = :usuario_id ORDER BY data DESC");
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../balance/balance.css">
    <title>Saldo do Usuário</title>
</head>
<body>
<div class="container">
<h2>Olá, <?= htmlspecialchars($nome_usuario) ?></h2>
    <a href="../cliente.php?refreshSaldo=1" class="voltar">← Voltar</a>
    <div class="saldo">
        Saldo atual: <strong>R$ <?= number_format($saldoAtual, 2, ',', '.') ?></strong>
    </div>

    <form method="post">
        <input type="number" step="0.01" name="valor" placeholder="Valor" required>
        <input type="text" name="descricao" placeholder="Descrição (opcional)">
        <button type="submit" name="adicionar" class="add">Adicionar Saldo</button>
        <button type="submit" name="retirar" class="remove">Retirar Saldo</button>
    </form>

    <h3>Histórico de Transações</h3>
    <table>
        <tr>
            <th>Data</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Descrição</th>
        </tr>
        <?php if (empty($registros)): ?>
            <tr>
                <td colspan="4">Nenhuma transação encontrada.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($registros as $row): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($row['data'])) ?></td>
                    <td class="<?= $row['tipo'] ?>"><?= ucfirst($row['tipo']) ?></td>
                    <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($row['descricao']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>
</body>
</html>