<?php
$status = $_GET['status'] ?? "pending";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pagamento pendente</title>
<style>
body { background:#f6f6f6; font-family:sans-serif; text-align:center; padding-top:80px; }
.card { background:white; width:350px; margin:auto; padding:25px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { color:#f1c40f; }
</style>
</head>
<body>

<div class="card">
    <h1>Pagamento Pendente ⚠️</h1>
    <p>Aguardando processamento do Mercado Pago.</p>
    <p>Status: <?= htmlspecialchars($status) ?></p>
    <a href="index.html">Voltar ao cardápio</a>
</div>

</body>
</html>
