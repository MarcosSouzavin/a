<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - SquinaXV</title>
<link rel="icon" href="img/stg.ico" type="image/x-icon">

<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background-color: #f6f6f6;
  margin: 0;
  padding: 0;
}
.checkout-container {
  max-width: 900px;
  margin: 40px auto;
  background: white;
  padding: 30px;
  border-radius: 14px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
h1, h2 { text-align: center; color: #333; }
ul { list-style: none; padding: 0; }
li {
  background: #fafafa;
  margin-bottom: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
}
.produto-info { display: flex; align-items: center; gap: 10px; }
.produto-info img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
.produto-detalhes { display: flex; flex-direction: column; }
.adicionais-list {
  margin: 5px 0 0 10px;
  padding: 0;
  list-style: none;
  font-size: 0.9em;
  color: #666;
}
.total, .frete, .endereco, .pagamento, .retirada { margin-top: 15px; font-size: 1.1em; }
.total strong { color: #b3ab3a; }
.btn-pagar {
  display: block;
  width: 100%;
  background: #b3ab3a;
  color: white;
  border: none;
  padding: 15px;
  margin-top: 25px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1.1em;
  transition: background 0.3s;
}
.btn-pagar:hover { background: #9c942e; }
.radio-group {
  display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;
}
.radio-group label {
  background: #eee; padding: 10px 15px; border-radius: 8px;
  cursor: pointer; transition: all 0.2s;
}
.radio-group input { display: none; }
.radio-group input:checked + label { background: #b3ab3a; color: white; }
textarea {
  width: 100%; height: 80px; margin-top: 10px;
  border-radius: 8px; padding: 10px; resize: none; border: 1px solid #ccc;
}
.btn-voltar {
  display: block; text-align: center; margin-top: 20px;
  color: #555; text-decoration: none;
}
.btn-voltar:hover { text-decoration: underline; }
.empty {
  text-align: center; color: #888; padding: 40px; font-size: 1.2em;
}
</style>
</head>

<body>

<!-- 🔥 IMPORTANTE: Este elemento é OBRIGATÓRIO -->
<div class="checkout-container" id="checkoutResumo">
  <h1>Carregando pedido...</h1>
</div>

<a href="javascript:history.back()" class="btn-voltar">← Voltar para o menu</a>

<script>
document.addEventListener("DOMContentLoaded", async () => {

    const cartKey = "cart";

    // Carrega carrinho salvo
    let carrinho = [];
    try {
        const data = localStorage.getItem(cartKey);
        if (data) carrinho = JSON.parse(data);
    } catch (e) {
        carrinho = [];
    }

    // Se carrinho estiver vazio
    if (!Array.isArray(carrinho) || carrinho.length === 0) {
        document.getElementById("checkoutResumo").innerHTML =
            '<div class="empty">Seu carrinho está vazio.<br><a href="index.html">Voltar ao cardápio</a></div>';
        return;
    }

    let subtotal = 0;
    let html = `<h2>Resumo do Pedido</h2><ul>`;

    carrinho.forEach(item => {
        const nome = item.name ?? item.nome ?? "Produto";
        const qtd = Number(item.quantity ?? item.quantidade ?? 1);
        const precoBase = Number(item.basePrice ?? item.preco ?? item.price ?? 0);
        const imagem = item.image ?? "img/default.png";

        let somaExtras = 0;
        let htmlExtras = "";

        if (Array.isArray(item.adicionais)) {
            htmlExtras += `<ul class="adicionais-list">`;
            item.adicionais.forEach(add => {
                const precoAd = Number(add.price ?? add.preco ?? 0);
                somaExtras += precoAd;
                htmlExtras += `<li>+ ${add.name ?? add.nome} — R$ ${precoAd.toFixed(2)}</li>`;
            });
            htmlExtras += `</ul>`;
        }

        const subtotalItem = (precoBase + somaExtras) * qtd;
        subtotal += subtotalItem;

        html += `
            <li>
                <div class="produto-info">
                    <img src="${imagem}">
                    <div class="produto-detalhes">
                        <strong>${nome}</strong> x${qtd}
                        ${htmlExtras}
                    </div>
                </div>
                <span>R$ ${subtotalItem.toFixed(2)}</span>
            </li>
        `;
    });

    const pagamento = document.querySelector('input[name="pagamento"]:checked').value;

const resp = await fetch("API/mercado_pago/preferencia.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(pedidoFinal)
});

const data = await resp.json();

if (!data.ok) {
  alert("Erro ao criar pagamento");
  console.error(data);
  return;
}

// Se for PIX → manda pra tela de aguardo PIX usando o preference_id
if (pagamento === "pix") {
  window.location.href = "API/mercado_pago/aguardando_pix.php?id=" + encodeURIComponent(data.id);
} else {
  // Cartão / débito / boleto → redireciona pro Checkout Pro normal
  window.location.href = data.init_point;
}


    const freteValor = 5.00;

    html += `
        </ul>

        <h3>Entrega ou Retirada</h3>
        <div class="radio-group">
            <input type="radio" name="tipoEntrega" id="entrega" value="entrega" checked>
            <label for="entrega">Entrega</label>

            <input type="radio" name="tipoEntrega" id="retirada" value="retirada">
            <label for="retirada">Retirada no local</label>
        </div>

        <h3>Endereço de Entrega</h3>
        <input type="text" id="endereco" placeholder="Rua, número, bairro..." style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;">

        <h3>Confira o pedido!</h3>
        <div class="radio-group">
            <input type="radio" name="pagamento" id="pix" value="pix" checked><label for="pix">Proseguir</label>
        </div>

        <h3>Observações</h3>
        <textarea id="observacoes" style="width:100%;height:80px;"></textarea>

        <p class="frete" id="freteInfo">Frete: R$ ${freteValor.toFixed(2)}</p>
        <p class="total"><strong>Total: R$ <span id="valorTotal">0.00</span></strong></p>

        <button id="btnPagar" class="btn-pagar">Confirmar e Pagar</button>
    `;

    document.getElementById("checkoutResumo").innerHTML = html;

    // FUNÇÃO PARA CALCULAR TOTAL
    function recalcularTotal() {
        const tipo = document.querySelector("input[name='tipoEntrega']:checked").value;
        const frete = (tipo === "entrega") ? freteValor : 0;
        document.getElementById("freteInfo").textContent = `Frete: R$ ${frete.toFixed(2)}`;
        const total = subtotal + frete;
        document.getElementById("valorTotal").textContent = total.toFixed(2);
        return frete;
    }

    recalcularTotal();

    document.querySelectorAll("input[name='tipoEntrega']").forEach(r => {
        r.addEventListener("change", () => {
            document.getElementById("endereco").disabled = (r.value === "retirada");
            recalcularTotal();
        });
    });

    // BOTÃO PAGAR
   document.getElementById("btnPagar").addEventListener("click", async () => {

    const freteCalc = recalcularTotal();
    const totalCalc = subtotal + freteCalc;

    const pedidoFinal = {
        produtos: carrinho,
        tipoEntrega: document.querySelector("input[name='tipoEntrega']:checked").value,
        pagamento: document.querySelector("input[name='pagamento']:checked").value,
        endereco: document.getElementById("endereco").value,
        observacoes: document.getElementById("observacoes").value,
        frete: freteCalc,
        total: totalCalc,
        usuario_id: <?php echo json_encode($_SESSION['usuario_id'] ?? null); ?>
    };

    console.log("📦 Enviando pedido:", pedidoFinal);

    try {
        const resp = await fetch("API/mercado_pago/preferencia.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(pedidoFinal)
        });

        const data = await resp.json();
        console.log("🔵 Resposta MP:", data);

        if (data.init_point) {
            window.location.href = data.init_point;
        } else {
            alert("Erro ao iniciar pagamento.");
            console.error(data);
        }

    } catch (err) {
        console.error("Erro ao enviar:", err);
        alert("Falha ao iniciar pagamento.");
    }
});

});
</script>


</body>
</html>
