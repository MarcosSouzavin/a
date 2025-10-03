er// Arquivo js/payment.js
// Integração da aba de pagamento com Mercado Pago

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

function montarAbaPagamento() {
    const container = document.getElementById('paymentTabContent');
    container.innerHTML = `
        <h2>Pagamento</h2>
        <div id="paymentMethods">
        <label><input type="radio" name="paymentMethod" value="cash"> Dinheiro (Entrega ou Retirada)</label>
        <label><input type="radio" name="paymentMethod" value="pix"> Pix</label>
        <label><input type="radio" name="paymentMethod" value="credit_card" checked> Cartão de Crédito</label>
        <label><input type="radio" name="paymentMethod" value="debit_card"> Cartão de Débito</label>
        </div>
        <div id="cardDetails">
            <label for="cardNumber">Número do Cartão:</label>
            <input type="text" id="cardNumber" name="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" />
            <label for="cardExpiry">Validade (MM/AA):</label>
            <input type="text" id="cardExpiry" name="cardExpiry" placeholder="MM/AA" maxlength="5" />
            <label for="cardCVC">CVC:</label>
            <input type="text" id="cardCVC" name="cardCVC" placeholder="123" maxlength="4" />
        </div>
        <button id="payButton">Pagar</button>
        <div id="paymentResult"></div>
    `;

    const cardDetails = container.querySelector('#cardDetails');
    cardDetails.style.display = 'none';

    container.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'credit_card' || radio.value === 'debit_card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
    });

    document.getElementById('payButton').onclick = async () => {
        const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        const items = getCartItemsForPayment();

        if (selectedMethod === 'cash') {
            // Pagamento em dinheiro: finalizar pedido localmente
            alert('Pedido finalizado! Por favor, prepare o pagamento em dinheiro na entrega ou retirada.');
            document.getElementById('paymentResult').textContent = 'Pedido finalizado para pagamento em dinheiro.';
            // Aqui você pode implementar lógica para salvar o pedido no backend, se necessário
            return;
        }

        if (selectedMethod === 'pix') {
            // Pagamento via Pix: criar preferência e mostrar QR code ou link
            const pref = await criarPreferenciaMercadoPago(items);
            if (pref && pref.init_point) {
                window.open(pref.init_point, '_blank');
                document.getElementById('paymentResult').textContent = 'Redirecionando para o Mercado Pago (Pix)...';
            } else {
                document.getElementById('paymentResult').textContent = 'Erro ao iniciar pagamento via Pix.';
            }
            return;
        }

        if (selectedMethod === 'credit_card' || selectedMethod === 'debit_card') {
            const cardNumber = document.getElementById('cardNumber').value.trim();
            const cardExpiry = document.getElementById('cardExpiry').value.trim();
            const cardCVC = document.getElementById('cardCVC').value.trim();

            if (!cardNumber || !cardExpiry || !cardCVC) {
                alert('Por favor, preencha todos os dados do cartão.');
                return;
            }
            // Validação adicional pode ser adicionada aqui
        }

        // Para cartão de crédito e débito, usar Mercado Pago normalmente
        const pref = await criarPreferenciaMercadoPago(items);
        if (pref && pref.init_point) {
            window.open(pref.init_point, '_blank');
            document.getElementById('paymentResult').textContent = 'Redirecionando para o Mercado Pago...';
        } else {
            document.getElementById('paymentResult').textContent = 'Erro ao iniciar pagamento.';
        }
    };
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
    return cart.map(item => {
        const basePrice = Number(item.basePrice || 0);
        const extras = (item.adicionais || []).reduce((s, a) => s + Number(a.price || 0), 0);
        return {
            title: item.name,
            quantity: item.quantity,
            unit_price: basePrice + extras
        };
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Supondo que você tenha um botão ou aba para pagamento
    const paymentTabBtn = document.getElementById('paymentTabBtn');
    if (paymentTabBtn) {
        paymentTabBtn.onclick = () => {
            montarAbaPagamento();
        };
    }
});
