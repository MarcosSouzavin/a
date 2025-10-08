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
            <div id="enderecoDisplay"></div>
            <div id="retiradaLevarOption" style="margin: 10px 0;">
                <label><input type="radio" name="retiradaLevar" value="levar" checked> Levar (Entrega)</label>
                <label style="margin-left: 20px;"><input type="radio" name="retiradaLevar" value="retirar"> Retirar (Sem frete)</label>
            </div>
            <div id="horarioOption" style="margin: 10px 0;">
                <label for="horarioSelect">Horário de Entrega/Retirada:</label>
                <select id="horarioSelect" style="margin-left: 10px;">
                    <option value="">Selecione um horário</option>
                    <option value="10:00">10:00</option>
                    <option value="10:30">10:30</option>
                    <option value="11:00">11:00</option>
                    <option value="11:30">11:30</option>
                    <option value="12:00">12:00</option>
                    <option value="12:30">12:30</option>
                    <option value="13:00">13:00</option>
                    <option value="13:30">13:30</option>
                    <option value="14:00">14:00</option>
                    <option value="14:30">14:30</option>
                    <option value="15:00">15:00</option>
                    <option value="15:30">15:30</option>
                    <option value="16:00">16:00</option>
                    <option value="16:30">16:30</option>
                    <option value="17:00">17:00</option>
                    <option value="17:30">17:30</option>
                    <option value="18:00">18:00</option>
                    <option value="18:30">18:30</option>
                    <option value="19:00">19:00</option>
                    <option value="19:30">19:30</option>
                    <option value="20:00">20:00</option>
                    <option value="20:30">20:30</option>
                    <option value="21:00">21:00</option>
                    <option value="21:30">21:30</option>
                    <option value="22:00">22:00</option>
                </select>
            </div>
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
            let cart;
            try {
                const response = await fetch('API/cart.php');
                cart = await response.json();
                if (!Array.isArray(cart)) {
                    throw new Error('Cart data is not an array');
                }
            } catch (e) {
                console.error('loadCartSummary: error loading cart data from API, falling back to localStorage:', e);
                cart = JSON.parse(localStorage.getItem('cart') || '[]');
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
            // Check retirada or levar option
            const retiradaLevar = document.querySelector('input[name="retiradaLevar"]:checked')?.value || 'levar';
            if (retiradaLevar === 'levar' && frete > 0) {
                const freteLi = document.createElement('li');
                freteLi.textContent = `Frete - R$ ${frete.toFixed(2)}`;
                cartItemsList.appendChild(freteLi);
                total += frete;
            }
            const enderecoDisplay = document.getElementById('enderecoDisplay');
            // Load address from localStorage or use default
            const endereco = localStorage.getItem('endereco') || "Rua Exemplo, 123, Bairro Central, Cidade Exemplo, Estado Exemplo, CEP 12345-678";
            enderecoDisplay.innerHTML = `<p><strong>Endereço de Entrega:</strong> ${endereco}</p>`;
            cartTotalAmount.textContent = total.toFixed(2);
        }

        async function getCartItemsForPayment() {
            let cart;
            try {
                const response = await fetch('API/cart.php');
                cart = await response.json();
            } catch (e) {
                cart = JSON.parse(localStorage.getItem('cart') || '[]');
            }
            const retiradaLevar = document.querySelector('input[name="retiradaLevar"]:checked')?.value || 'levar';
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
            if (retiradaLevar === 'levar' && frete > 0) {
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

            // Load saved retiradaLevar option
            const savedOption = localStorage.getItem('retiradaLevar') || 'levar';
            const radio = document.querySelector(`input[name="retiradaLevar"][value="${savedOption}"]`);
            if (radio) radio.checked = true;

            // Load saved horario option
            const savedHorario = localStorage.getItem('horarioEntrega') || '';
            const horarioSelect = document.getElementById('horarioSelect');
            if (horarioSelect) horarioSelect.value = savedHorario;

            // Listen for changes on retiradaLevar option
            document.querySelectorAll('input[name="retiradaLevar"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    localStorage.setItem('retiradaLevar', this.value);
                    loadCartSummary();
                });
            });

            // Listen for changes on horario option
            if (horarioSelect) {
                horarioSelect.addEventListener('change', function() {
                    localStorage.setItem('horarioEntrega', this.value);
                });
            }
        });

        // Atualizar resumo quando frete for calculado
        const bc = new BroadcastChannel('frete-update');
        bc.addEventListener('message', function(e) {
            loadCartSummary();
        });
    </script>
</body>

</html>
