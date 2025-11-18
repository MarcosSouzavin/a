<?php
$status = $_GET['status'] ?? "erro";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pagamento não concluído</title>
<style>
body { background:#f6f6f6; font-family:sans-serif; text-align:center; padding-top:80px; }
.card { background:white; width:350px; margin:auto; padding:25px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
h1 { color:#e74c3c; }
</style>
</head>
<body>

<div class="card">
    <h1>Pagamento recusado ❌</h1>
    <p><strong>Status:</strong> <?= htmlspecialchars($status) ?></p>
    <p>Tente novamente ou escolha outra forma de pagamento.</p>
    <a href="index.html">Voltar ao cardápio</a>
</div>

</body>
</html>
