<?php
require __DIR__ . "conexao.php"; // ajuste para o caminho correto da sua conexão

$pid = $_GET['pid'] ?? null;

if (!$pid) {
    die("ID do pedido inválido.");
}

// Consulta o pedido no banco
$stmt = $conn->prepare("SELECT status, mensagem, atualizado_em FROM pedidos_status WHERE payment_id = ? ORDER BY atualizado_em DESC LIMIT 1");
$stmt->bind_param("s", $pid);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Pedido</title>
    <style>
        body {
            background: #f4f6fa;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #fff;
            width: 90%;
            max-width: 420px;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.1);
            text-align: center;
        }
        h1 {
            margin-bottom: 5px;
            color: #333;
        }
        .status-box {
            background: #eef2ff;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        .status-text {
            font-size: 22px;
            font-weight: bold;
        }
        .time {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .msg {
            color: #444;
            margin-top: 10px;
            font-size: 16px;
        }
        .refresh {
            background: #4c6ef5;
            color: #fff;
            padding: 12px 18px;
            border-radius: 10px;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
    </style>

    <script>
        // Auto-atualização a cada 5s
        setInterval(() => {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");

                    document.querySelector(".status-text").innerHTML =
                        doc.querySelector(".status-text").innerHTML;

                    document.querySelector(".time").innerHTML =
                        doc.querySelector(".time").innerHTML;

                    if (doc.querySelector(".msg"))
                        document.querySelector(".msg").innerHTML =
                            doc.querySelector(".msg").innerHTML;
                });
        }, 5000);
    </script>

</head>
<body>

<div class="container">

    <h1>Acompanhar Pedido</h1>
    <p>ID: <b><?= htmlspecialchars($pid) ?></b></p>

    <div class="status-box">
        <div class="status-text">
            <?= $pedido ? strtoupper($pedido["status"]) : "AGUARDANDO PAGAMENTO" ?>
        </div>

        <div class="time">
            <?= $pedido ? "Atualizado em: " . $pedido["atualizado_em"] : "Ainda sem atualização" ?>
        </div>

        <?php if (!empty($pedido["mensagem"])): ?>
            <div class="msg"><?= htmlspecialchars($pedido["mensagem"]) ?></div>
        <?php endif; ?>
    </div>

    <a href="" class="refresh">Atualizar Agora</a>

</div>

</body>
</html>
