<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Checkout - SquinaXV</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="icon" href="img/stg.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>

<body class="checkout-page">
    <header class="header">
        <div class="container">
            <h1 class="logo">
                <img src="img/stg.jpeg" alt="Delicias Gourmet" class="logo-image" />
                SquinaXV
            </h1>
            <div class="menu-hamburguer">☰</div>
            <nav class="nav">
                <a href="javascript:history.back()" class="nav-link">Voltar</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Checkout</h2>
        <div id="cartSummary">
            <h3>Resumo do Pedido</h3>
            <ul id="cartItemsList"></ul>
            <p>Total: R$ <span id="cartTotalAmount">0.00</span></p>
        </div>

        <div id="paymentTabContent"></div>
    </main>

    <div id="paymentResult"></div>

    <script src="js/payment.js"></script>
    <script>
        async function loadCartSummary() {
            const cartItemsList = document.getElementById('cartItemsList');
            const cartTotalAmount = document.getElementById('cartTotalAmount');
            cartItemsList.innerHTML = '';
            let total = 0;
            let cart = [];
            try {
                const response = await fetch('API/cart.php');
                cart = await response.json();
            } catch (e) {
                console.error('Erro ao carregar carrinho', e);
                cart = [];
            }
            if (cart.length === 0) {
                cartItemsList.innerHTML = '<li>Carrinho vazio.</li>';
                cartTotalAmount.textContent = '0.00';
                return;
            }
            cart.forEach(item => {
                const basePrice = Number(item.basePrice || 0);
                const extras = (item.adicionais || []).reduce((s, a) => s + Number(a.price || 0), 0);
                const itemTotal = (basePrice + extras) * item.quantity;
                const li = document.createElement('li');
                li.textContent = `${item.name} - Quantidade: ${item.quantity} - R$ ${itemTotal.toFixed(2)}`;
                cartItemsList.appendChild(li);
                total += itemTotal;
            });
            const frete = parseFloat(localStorage.getItem('frete') || 0);
            if (frete > 0) {
                const freteLi = document.createElement('li');
                freteLi.textContent = `Frete - R$ ${frete.toFixed(2)}`;
                cartItemsList.appendChild(freteLi);
                total += frete;
            }
            cartTotalAmount.textContent = total.toFixed(2);
        }

        async function getCartItemsForPayment() {
            let cart = [];
            try {
                const response = await fetch('API/cart.php');
                cart = await response.json();
            } catch (e) {
                console.error('Erro ao carregar carrinho', e);
                cart = [];
            }
            const items = cart.map(item => {
                const basePrice = Number(item.basePrice || 0);
                const extras = (item.adicionais || []).reduce((s, a) => s + Number(a.price || 0), 0);
                return {
                    title: item.name,
                    quantity: item.quantity,
                    unit_price: basePrice + extras
                };
            });
            const frete = parseFloat(localStorage.getItem('frete') || 0);
            if (frete > 0) {
                items.push({
                    title: 'Frete',
                    quantity: 1,
                    unit_price: frete
                });
            }
            return items;
        }



        async function criarPreferenciaMercadoPago(items) {
            try {
                const response = await fetch('API/mercado_pago_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items })
                });
                if (!response.ok) {
                    throw new Error('Erro ao criar preferência de pagamento');
                }
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Erro:', error);
                return null;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadCartSummary();
            montarAbaPagamento();
        });

        // Atualizar resumo quando frete for calculado
        const bc = new BroadcastChannel('frete-update');
        bc.addEventListener('message', function(e) {
            loadCartSummary();
        });
    </script>
</body>

</html>
