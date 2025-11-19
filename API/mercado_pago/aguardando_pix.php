<?php

$pref = $_GET["id"] ?? null;

if (!$pref) {
    die("Pagamento inválido");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Aguardando pagamento PIX</title>
<style>
body {
    font-family: Arial;
    background: #fafafa;
    text-align: center;
    padding: 50px;
}
.box {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    max-width: 500px;
    margin: auto;
}
h2 { color: #444; }
.status {
    margin-top: 20px;
    font-size: 22px;
    color: #666;
}
</style>
</head>
<body>

<div class="box">
    <h2>Aguardando confirmação do PIX…</h2>
    <p>Assim que o pagamento for confirmado, você será redirecionado automaticamente.</p>

    <div class="status" id="statusMsg">Status atual: <b>Aguardando…</b></div>
</div>

<script>
setInterval(() => {
    fetch("verificar_status.php?id=<?php echo $pref; ?>")
        .then(r => r.json())
        .then(data => {
            if (data.status === "approved") {
                window.location.href = "sucesso.php";
            }
            if (data.status === "rejected") {
                window.location.href = "falha.php";
            }
            if (data.status === "pending") {
                document.getElementById("statusMsg").innerHTML =
                    "Status atual: <b>Pendente…</b>";
            }
        });
}, 3000); // 3 segundos
</script>

</body>
</html>
