<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Acompanhar Pedido</title>

<style>
body {
    font-family: Arial;
    background: #f4f4f4;
    padding: 20px;
}

.painel {
    max-width: 500px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px #0001;
}

.status-etapa {
    display: flex;
    align-items: center;
    margin: 15px 0;
}

.bola {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #bbb;
    margin-right: 10px;
    transition: .3s;
}

.bola.ativa {
    background: green;
    transform: scale(1.2);
}

.etapa-texto {
    font-size: 16px;
}

</style>
</head>

<body>
    <div class="painel">
        <h2>Seu Pedido</h2>
        <p>Número do pedido: <b id="pedidoId"></b></p>

        <div class="status-etapa">
            <div class="bola" id="s1"></div>
            <div class="etapa-texto">Pedido recebido</div>
        </div>

        <div class="status-etapa">
            <div class="bola" id="s2"></div>
            <div class="etapa-texto">Preparando</div>
        </div>

        <div class="status-etapa">
            <div class="bola" id="s3"></div>
            <div class="etapa-texto">Pronto para retirar / Entrega</div>
        </div>

        <div class="status-etapa">
            <div class="bola" id="s4"></div>
            <div class="etapa-texto">Saiu para entrega</div>
        </div>

        <div class="status-etapa">
            <div class="bola" id="s5"></div>
            <div class="etapa-texto">Entregue</div>
        </div>
    </div>

<script>
const pedidoId = new URLSearchParams(location.search).get("pedido");
document.getElementById("pedidoId").textContent = pedidoId;

function atualizarStatus() {
    fetch("API/pedidos/aguardar_status.php?pedido_id=" + pedidoId)
    .then(res => res.json())
    .then(data => {
        marcarEtapas(data.status);
    });
}

function marcarEtapas(status) {
    const etapas = {
        "recebido": 1,
        "preparando": 2,
        "pronto": 3,
        "saiu_para_entrega": 4,
        "entregue": 5
    };

    let passo = etapas[status] ?? 1;

    for (let i = 1; i <= 5; i++) {
        document.getElementById("s" + i).classList.remove("ativa");
        if (i <= passo) document.getElementById("s" + i).classList.add("ativa");
    }
}

// Atualiza a cada 4 segundos
setInterval(atualizarStatus, 4000);
atualizarStatus();
</script>
</body>
</html>
