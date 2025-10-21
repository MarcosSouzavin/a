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
<link rel="stylesheet" href="css/styles.css">
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

<div class="checkout-container" id="checkoutResumo">
  <h1>Carregando pedido...</h1>
</div>

<a href="javascript:history.back()" class="btn-voltar">← Voltar para o menu</a>

<script>
document.addEventListener("DOMContentLoaded", async () => {
  // 🔥 Usa chave unificada
  const cartKey = "cart";

  const pedidoJSON = localStorage.getItem("pedidoCheckout");
  const carrinhoJSON = localStorage.getItem(cartKey);

  // 🔍 Prioriza o carrinho real salvo
  let pedido = null;
  if (carrinhoJSON) {
    pedido = { produtos: JSON.parse(carrinhoJSON) };
  } else if (pedidoJSON) {
    pedido = JSON.parse(pedidoJSON);
  }

  if (!pedido || !pedido.produtos || pedido.produtos.length === 0) {
    document.getElementById("checkoutResumo").innerHTML =
      '<div class="empty">Seu carrinho está vazio.<br><a href="index.html">Voltar ao cardápio</a></div>';
    return;
  }

  // --- monta lista de produtos ---
  let html = `<h2>Resumo do Pedido</h2><ul>`;
  let subtotal = 0;

  pedido.produtos.forEach(p => {
    const nome = p.name || p.nome || "Produto";
    const qtd = Number(p.quantity ?? p.quantidade ?? 1);
    const precoBase = Number(p.basePrice || p.preco || p.price || 0);
    const tamanho = p.size || p.tamanho || "";
    const imagem = p.image || "img/default.png";

    // ⚙️ Usa apenas adicionais selecionados (não todos)
    const adicionaisSelecionados = Array.isArray(p.adicionais)
      ? p.adicionais.filter(a => a && (a.price || a.preco))
      : [];

    let somaExtras = 0;
    let htmlExtras = "";

    if (adicionaisSelecionados.length > 0) {
      htmlExtras += `<ul class="adicionais-list">`;
      adicionaisSelecionados.forEach(a => {
        const nomeAd = a.name || a.nome || "Adicional";
        const precoAd = Number(a.price || a.preco || 0);
        somaExtras += precoAd;
        htmlExtras += `<li>+ ${nomeAd} — R$ ${precoAd.toFixed(2)}</li>`;
      });
      htmlExtras += `</ul>`;
    }

    const subtotalItem = (precoBase + somaExtras) * qtd;
    subtotal += subtotalItem;

    html += `
      <li>
        <div class="produto-info">
          <img src="${imagem}" alt="${nome}">
          <div class="produto-detalhes">
            <strong>${nome}</strong> ${tamanho ? `(${tamanho})` : ""} x${qtd}
            ${htmlExtras}
          </div>
        </div>
        <span>R$ ${subtotalItem.toFixed(2)}</span>
      </li>
    `;
  });

  // --- frete fixo ---
  const freteValor = 5.00;
  const enderecoSalvo = localStorage.getItem("endereco") || "";

  html += `
    </ul>

    <div class="retirada">
      <h3>Entrega ou Retirada</h3>
      <div class="radio-group">
        <input type="radio" id="entrega" name="tipoEntrega" value="entrega" checked>
        <label for="entrega">Entrega</label>
        <input type="radio" id="retirada" name="tipoEntrega" value="retirada">
        <label for="retirada">Retirada no local</label>
      </div>
    </div>

    <div class="endereco">
      <h3>Endereço de Entrega</h3>
      <input type="text" id="endereco" placeholder="Rua, número, bairro, cidade" 
             value="${enderecoSalvo}" 
             style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;">
    </div>

    <div class="pagamento">
      <h3>Forma de Pagamento</h3>
      <div class="radio-group">
        <input type="radio" id="pix" name="pagamento" value="pix" checked><label for="pix">PIX</label>
        <input type="radio" id="credito" name="pagamento" value="credito"><label for="credito">Crédito</label>
        <input type="radio" id="debito" name="pagamento" value="debito"><label for="debito">Débito</label>
        <input type="radio" id="dinheiro" name="pagamento" value="dinheiro"><label for="dinheiro">Dinheiro</label>
      </div>
    </div>

    <div class="observacoes">
      <h3>Observações</h3>
      <textarea id="observacoes" placeholder="Ex: sem cebola, entregar no portão..."></textarea>
    </div>

    <p class="frete" id="freteInfo">Frete: R$ ${freteValor.toFixed(2)}</p>
    <p class="total"><strong>Total: R$ <span id="valorTotal">0.00</span></strong></p>

    <button class="btn-pagar" id="btnPagar">Confirmar e Pagar</button>
  `;

  document.getElementById("checkoutResumo").innerHTML = html;

  // --- recalcular total ---
  function recalcularTotal() {
    const tipo = document.querySelector('input[name="tipoEntrega"]:checked').value;
    const freteAtual = (tipo === "entrega") ? freteValor : 0;
    document.getElementById("freteInfo").textContent = `Frete: R$ ${freteAtual.toFixed(2)}`;
    const total = subtotal + freteAtual;
    document.getElementById("valorTotal").textContent = total.toFixed(2);
    return total;
  }

  recalcularTotal();

  document.querySelectorAll('input[name="tipoEntrega"]').forEach(el => {
    el.addEventListener("change", () => {
      const tipo = document.querySelector('input[name="tipoEntrega"]:checked').value;
      document.getElementById("endereco").disabled = (tipo === "retirada");
      recalcularTotal();
    });
  });

  // --- botão pagar ---
  document.getElementById("btnPagar").addEventListener("click", async () => {
    const tipoEntrega = document.querySelector('input[name="tipoEntrega"]:checked').value;
    const pagamento = document.querySelector('input[name="pagamento"]:checked').value;
    const endereco = document.getElementById("endereco").value;
    const observacoes = document.getElementById("observacoes").value;
    const totalFinal = recalcularTotal();

    const pedidoFinal = {
      ...pedido,
      tipoEntrega,
      pagamento,
      endereco,
      observacoes,
      total: totalFinal,
      frete: (tipoEntrega === "entrega") ? freteValor : 0,
      usuario_id: <?php echo json_encode($_SESSION['usuario_id'] ?? null); ?>
    };

    try {
      const resp = await fetch("API/mercado_pago/preferencia.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(pedidoFinal)
      });
      const data = await resp.json();
      if (data.init_point) {
        window.location.href = data.init_point;
      } else {
        alert("Erro ao criar pagamento.");
        console.error(data);
      }
    } catch (err) {
      console.error("Erro ao enviar pedido:", err);
      alert("Falha ao iniciar pagamento.");
    }
  });
});
</script>

</body>
</html>
