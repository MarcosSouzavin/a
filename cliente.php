<?php
session_start();
require_once 'conexao.php';
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario'] = $user['nome'];
    }
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario_id'])) {
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
            <input type="text" id="searchInput" placeholder="Pesquisar Delícias">
        </div>
    </div>
</div>

<main class="container">
    <div class="menu-grid" id="menuContainer">
        <!-- produtos renderizados via JS -->
    </div>
</main>

<!-- Backdrop para mobile -->
<div class="cart-backdrop"></div>

<!-- Botão flutuante do carrinho -->
<div class="cart-floating">
    <button class="cart-button">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count">0</span>
    </button>
</div>

<!-- Carrinho lateral -->
<aside class="cart-sidebar">
    <div class="cart-header">
        <h2>Seu Pedido</h2>
        <button class="close-cart" aria-label="Fechar carrinho">&times;</button>
    </div>

    <ul class="cart-items" id="cartItems"></ul>

    <div class="cart-total">
        Total: R$<span id="cartTotal">0.00</span>
    </div>

    <!-- Endereço e frete -->
    <div class="frete-container">
        <h3>Informe seu Endereço</h3>
        <form id="enderecoForm">
            <label for="endereco">Endereço Completo:</label>
            <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro, cidade, estado" required>
            <button type="submit">Confirmar Endereço</button>
        </form>
        <div id="enderecoResultado"></div>
    </div>

    <button id="checkoutButton" class="checkout-button">Finalizar Compra</button>
</aside>

<!-- Modal de produto -->
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

<script src="js/product-options.js"></script>
<script src="js/index.js"></script>

<script>
// ----------------------------
// FRETE
// ----------------------------
document.getElementById('enderecoForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const endereco = document.getElementById('endereco').value.trim();

    if (!endereco) return alert("Informe um endereço válido.");

    try {
        const resp = await fetch('API/endereco.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ endereco })
        });
        const data = await resp.json();

        if (data.erro) return alert(data.erro);

        document.getElementById('enderecoResultado').innerHTML = `
            <p>Endereço: ${data.endereco}</p>
            <p>Valor do Frete: R$ ${data.frete.toFixed(2)}</p>
        `;

        localStorage.setItem('endereco', data.endereco);
        localStorage.setItem('frete', data.frete);
        window.freteValor = data.frete;
    } catch (err) {
        console.error('Erro ao buscar frete:', err);
    }
});


async function saveCart() {
    try {
        let cartItems = [];
        if (typeof cart !== 'undefined' && Array.isArray(cart)) {
            cartItems = cart;
        } else {
            const saved = localStorage.getItem('cart');
            cartItems = saved ? JSON.parse(saved) : [];
        }

        if (!cartItems.length) {
            alert('Seu carrinho está vazio!');
            return false;
        }

        const frete = parseFloat(localStorage.getItem('frete') || 0);
        const endereco = localStorage.getItem('endereco') || '';

        const total = cartItems.reduce((acc, item) => {
            const base = parseFloat(item.preco || item.price || item.basePrice || 0);
            const qtd = parseFloat(item.quantidade || item.qty || item.quantity || 1);
            const extras = Array.isArray(item.adicionais)
                ? item.adicionais.reduce((s, a) => s + parseFloat(a.preco || a.price || 0), 0)
                : 0;
            return acc + (base + extras) * qtd;
        }, 0) + frete;

        const pedido = {
            produtos: cartItems,
            frete,
            endereco,
            total,
            usuario_id: <?php echo json_encode($_SESSION['usuario_id']); ?>,
            usuario_nome: <?php echo json_encode($_SESSION['usuario']); ?>
        };

        localStorage.setItem('pedidoCheckout', JSON.stringify(pedido));
        console.log('Pedido salvo:', pedido);
        return true;
    } catch (error) {
        console.error('Erro ao salvar carrinho:', error);
        alert('Não foi possível salvar o carrinho.');
        return false;
    }
}

document.getElementById('checkoutButton').addEventListener('click', async (e) => {
    e.preventDefault();
    const ok = await saveCart();
    if (ok) window.location.href = 'checkout.php';
});
</script>

</body>
</html>
