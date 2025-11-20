<?php
// painel_pedidos.php
// Painel interno para acompanhar e alterar status dos pedidos

require __DIR__ . "/API/db.php"; // AJUSTE se seu db.php estiver em outro lugar

// Busca últimos pedidos
$sql = "SELECT payment_id, status, mensagem, atualizado_em 
        FROM pedidos_status 
        ORDER BY atualizado_em DESC 
        LIMIT 50";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Pedidos - Interno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }
        header {
            background: #111;
            color: #fff;
            padding: 15px 20px;
        }
        header h1 {
            margin: 0;
            font-size: 20px;
        }
        .container {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 12px rgba(0,0,0,.08);
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        th {
            background: #f0f0f0;
            text-align: left;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .status-select {
            padding: 5px 8px;
            border-radius: 6px;
        }
        .msg-input {
            width: 100%;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 13px;
        }
        .btn-save {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            background: #2ecc71;
            color: #fff;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-save:disabled {
            opacity: .6;
            cursor: default;
        }
        .small {
            font-size: 12px;
            color: #666;
        }
        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: #111;
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s;
        }
        .toast.show {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>
<body>

<header>
    <h1>Painel de Pedidos - Interno</h1>
</header>

<div class="container">
    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Status</th>
                <th>Mensagem</th>
                <th>Última atualização</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $paymentId = htmlspecialchars($row['payment_id']);
                    $status = $row['status'] ?: 'pending';
                    $mensagem = $row['mensagem'] ?? '';
                ?>
                <tr>
                    <td><?= $paymentId ?></td>
                    <td>
                        <select class="status-select" data-id="<?= $paymentId ?>">
                            <option value="pending"      <?= $status=='pending'?'selected':'' ?>>Pagamento pendente</option>
                            <option value="approved"     <?= $status=='approved'?'selected':'' ?>>Pagamento aprovado</option>
                            <option value="in_process"   <?= $status=='in_process'?'selected':'' ?>>Pagamento em análise</option>
                            <option value="rejected"     <?= $status=='rejected'?'selected':'' ?>>Pagamento recusado</option>
                            <option value="em_preparo"   <?= $status=='em_preparo'?'selected':'' ?>>Em preparo</option>
                            <option value="saiu_entrega" <?= $status=='saiu_entrega'?'selected':'' ?>>Saiu para entrega</option>
                            <option value="concluido"    <?= $status=='concluido'?'selected':'' ?>>Concluído</option>
                            <option value="cancelado"    <?= $status=='cancelado'?'selected':'' ?>>Cancelado</option>
                        </select>
                    </td>
                    <td>
                        <input type="text"
                               class="msg-input"
                               data-msg-for="<?= $paymentId ?>"
                               value="<?= htmlspecialchars($mensagem) ?>"
                               placeholder="Ex: Pedido saiu para entrega, previsão 20min">
                    </td>
                    <td class="small">
                        <?= htmlspecialchars($row['atualizado_em']) ?>
                    </td>
                    <td>
                        <button class="btn-save" data-id="<?= $paymentId ?>">Salvar</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">Nenhum pedido encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="toast" id="toast"></div>

<script>
function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

document.querySelectorAll('.btn-save').forEach(btn => {
    btn.addEventListener('click', async () => {
        const paymentId = btn.dataset.id;
        const select = document.querySelector('.status-select[data-id="'+paymentId+'"]');
        const msgInput = document.querySelector('.msg-input[data-msg-for="'+paymentId+'"]');

        const status = select.value;
        const mensagem = msgInput.value;

        btn.disabled = true;
        btn.textContent = 'Salvando...';

        try {
            const resp = await fetch('API/pedidos/atualizar_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    payment_id: paymentId,
                    status: status,
                    mensagem: mensagem
                })
            });

            const data = await resp.json();
            if (data.ok) {
                showToast('Status atualizado com sucesso!');
            } else {
                console.error(data);
                showToast('Erro ao atualizar status.');
            }
        } catch (e) {
            console.error(e);
            showToast('Falha na requisição.');
        }

        btn.disabled = false;
        btn.textContent = 'Salvar';
    });
});
</script>

</body>
</html>
