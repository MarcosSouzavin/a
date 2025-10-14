<?php
$status = $_GET['status'] ?? 'desconhecido';
$pedido = $_GET['pedido'] ?? 'sem_id';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pagamento - SquinaXV</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background:#fafafa; text-align:center; padding-top:50px; }
h1 { color:#333; }
p { color:#555; }
a { display:inline-block; margin-top:20px; padding:10px 20px; background:#b3ab3a; color:#fff; text-decoration:none; border-radius:6px; }
</style>
</head>
<body>
<h1>Status do pagamento: <?= htmlspecialchars($status) ?></h1>
<p>Referência do pedido: <?= htmlspecialchars($pedido) ?></p>
<a href="javascript:history.back()" class="btn-voltar">Voltar para o menu</a>
</body>
</html>
