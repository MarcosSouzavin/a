<?php
session_start();
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario_id'])) {
    // Se não estiver logado corretamente, redireciona para login
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SquinaXV - Pedidos</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="img/stg.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <header class="header">
        <div class="container">
            <h1 class="logo">
                <img src="img/stg.jpeg" alt="Delicias Gourmet" class="logo-image">
                SquinaXV
            </h1>
            <div class="menu-hamburguer">☰</div>
            <nav class="nav">
               <a href="balance/historic_balance.php" class="nav-link">Saldo</a>
                <a href="#ofertas" class="nav-link">Ofertas</a>
                <a href="#contacto" class="nav-link">Contato</a>
                <a href="dados.php" class="nav-link">Meus Dados</a>
                <a href="logout.php" class="nav-link active">Sair</a>
            </nav>
        </div>
    </header>

    <div class="search-bar">
        <div class="container">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Pesquisar Delicias">
            </div>
        </div>
    </div>

    <main class="container">
        <div class="menu-grid" id="menuContainer">
            <!-- produtos serão renderizados aqui pelo js/index.js -->
        </div>
    </main>

    <div class="cart-floating">
        <button class="cart-button">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-count">0</span>
        </button>
    </div>

    <aside class="cart-sidebar" id="cartSidebar" aria-hidden="true">
        <div class="cart-header">
            <h2>Seu Pedido</h2>
            <button class="close-cart" onclick="toggleCart()" aria-label="Fechar carrinho">&times;</button>
        </div>

        <ul class="cart-items" id="cartItems"></ul>

        <div class="cart-saldo">
            Saldo disponível: R$<span id="saldoUsuario">0.00</span><br>
            <label>
                <input type="checkbox" id="usarSaldo" onchange="atualizarTotalComSaldo()">
                Usar saldo na compra
            </label>
        </div>

        <div class="cart-total">
            Total a pagar: R$<span id="cartTotal">0.00</span>
        </div>

        <!-- Formulário de cálculo de frete dentro do carrinho -->
        <div class="frete-container">
            <h3>Calcular Frete</h3>
            <form id="freteForm">
                <label for="cepDestino">Digite seu CEP:</label>
                <input type="text" id="cepDestino" name="cepDestino" placeholder="00000-000" required>
                <button type="submit">Calcular</button>
            </form>
            <div id="freteResultado"></div>
        </div>

        <button id="checkoutButton" class="checkout-button">Finalizar Compra</button>
    </aside>

    <!-- modal de opções (tamanho + adicionais) -->
    <div id="productModal" class="modal hidden" aria-hidden="true">
        <div class="modal-backdrop" onclick="closeProductModal()"></div>
        <div class="modal-card" role="dialog" aria-modal="true">
            <header class="modal-header">
                <h3 id="modalTitle">Produto</h3>
                <button class="modal-close" onclick="closeProductModal()" aria-label="Fechar">×</button>
            </header>

            <form id="modalForm" class="modal-body">
                <div class="modal-row">
                    <div class="modal-price">R$ <span id="modalPrice">0.00</span></div>
                </div>

                <div class="modal-row">
                    <label class="modal-label">Tamanho</label>
                    <div id="sizesContainer" class="options-list"></div>
                </div>

                <div class="modal-row">
                    <label class="modal-label">Adicionais</label>
                    <div id="adicionaisContainer" class="options-list"></div>
                </div>

                <div class="modal-row">
                    <label class="modal-label">Quantidade</label>
                    <input type="number" id="modalQty" min="1" value="1">
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn primary">Adicionar ao pedido</button>
                    <button type="button" class="btn link" onclick="closeProductModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- carregar primeiro o módulo do modal, depois o index (usa o modal) -->
    <script src="js/product-options.js"></script>
    <script src="js/index.js"></script>
     <script>
    // Atualiza saldo ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof atualizarSaldoUsuario === 'function') {
            atualizarSaldoUsuario();
        }
    });

    // Atualiza saldo sempre que a página ganha foco (ao voltar da página de saldo)
    window.addEventListener('focus', function() {
        if (typeof atualizarSaldoUsuario === 'function') {
            atualizarSaldoUsuario();
        }
    });

    document.getElementById('freteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const cepDestino = document.getElementById('cepDestino').value;

        fetch('API/frete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ cepDestino })
        })
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                document.getElementById('freteResultado').innerText = data.erro;
            } else {
                // Atualiza o resultado do frete no carrinho
                document.getElementById('freteResultado').innerHTML = `
                    <p>CEP Destino: ${data.cepDestino}</p>
                    <p>Valor do Frete: R$ ${data.valorFrete.toFixed(2)}</p>
                    <p>Prazo de Entrega: ${data.prazoEntrega} horas</p>
                    <p>Endereço: ${data.endereco.logradouro}, ${data.endereco.bairro}, ${data.endereco.localidade} - ${data.endereco.uf}</p>
                `;

                // Atualiza o total do carrinho com o valor do frete
                const cartTotalElement = document.getElementById('cartTotal');
                const cartTotal = parseFloat(cartTotalElement.textContent.replace(',', '.')) || 0;
                const totalComFrete = cartTotal + data.valorFrete;
                cartTotalElement.textContent = totalComFrete.toFixed(2).replace('.', ',');
            }
        })
        .catch(error => console.error('Erro:', error));
    });
    </script>
</body>

</html>
