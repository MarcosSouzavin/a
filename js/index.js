// --- VARIÁVEIS GLOBAIS ---
let cart = [];
let menuItems = [];
let saldoUsuario = 0;
window.freteValor = 0;

// --- FUNÇÕES DO CARRINHO ---
async function getCart() {
    const cartData = localStorage.getItem('cart') || '[]';
    console.log('getCart: loaded cart data from localStorage:', cartData);
    return JSON.parse(cartData);
}

async function saveCart(c) {
    const cartData = JSON.stringify(c);
    console.log('saveCart: saving cart data to localStorage:', cartData);
    localStorage.setItem('cart', cartData);
    // Also save to session via API
    try {
        const response = await fetch('API/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: cartData
        });
        if (!response.ok) {
            console.error('Erro ao salvar carrinho na sessão');
        }
    } catch (e) {
        console.error('Erro ao salvar carrinho na sessão', e);
    }
}

// --- FUNÇÕES UTILITÁRIAS ---

/** Gera um identificador único para cada item do carrinho. */
function makeUid() {
    return 'uid_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
}

/** Formata um número para o padrão de moeda brasileiro (R$). */
function formatPrice(valor) {
    return Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** Calcula o preço total de um item no carrinho (base + adicionais) * quantidade. */
function calcItemTotal(item) {
    const base = Number(item.basePrice || 0);
    const extras = (item.adicionais || []).reduce((s, a) => s + Number(a.price || 0), 0);
    return (base + extras) * (item.quantity || 1);
}

/** Adiciona logs de ações do cliente no localStorage para depuração. */
function addClientLog(action, details) {
    try {
        const logs = JSON.parse(localStorage.getItem('clientLogs') || '[]');
        logs.push({
            time: new Date().toLocaleString(),
            action,
            details
        });
        localStorage.setItem('clientLogs', JSON.stringify(logs));
    } catch (e) {
        console.error("Erro ao salvar log do cliente:", e);
    }
}


// --- LÓGICA DO CARRINHO ---

/** Atualiza a interface do carrinho com os itens, totais e contadores. */
function updateCart() {
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) return;
    cartItems.innerHTML = '';
    let totalQuantity = 0;
    if (cart.length === 0) {
        cartItems.innerHTML = '<li class="cart-empty">Seu carrinho está vazio.</li>';
    } else {
        cart.forEach(item => {
            const li = document.createElement('li');
            li.className = 'cart-line';
            const lineTotal = calcItemTotal(item);
            totalQuantity += item.quantity;
            const itemImage = item.image ? `<img src="${item.image}" alt="${item.name}" class="cart-item-image" style="width:80px;height:80px;object-fit:cover;border-radius:12px;display:block;margin-bottom:8px;">` : '';
            const itemSize = item.size ? `<small>(${item.size})</small>` : '';
            const itemAdicionais = (item.adicionais && item.adicionais.length)
                ? `<div class="cart-extras">${item.adicionais.map(a => `<span class="extra-item">+ ${a.name}`).join('')}</div>`
                : '';
            const removerText = item.remover ? `<div class="cart-remover" style="font-size:0.95em;color:#555;margin-top:2px;">${item.remover}</div>` : '';
            li.innerHTML = `
                <div class="cart-line-left">
                    ${itemImage}
                    <div class="cart-item-details">
                        <strong>${item.name}</strong> ${itemSize}
                        ${removerText}
                        ${itemAdicionais}
                        <div class="cart-qty">
                            <button class="qty-minus" aria-label="Diminuir quantidade de ${item.name}">−</button>
                            <span>${item.quantity}</span>
                            <button class="qty-plus" aria-label="Aumentar quantidade de ${item.name}">+</button>
                        </div>
                    </div>
                </div>
                <div class="cart-line-right">
                    <div>R$ ${formatPrice(lineTotal)}</div>
                    <button class="remove-item" aria-label="Remover ${item.name} do carrinho">Remover</button>
                </div>
            `;
            li.querySelector('.qty-minus').addEventListener('click', () => window.adjustQuantity(item.uid, -1));
            li.querySelector('.qty-plus').addEventListener('click', () => window.adjustQuantity(item.uid, 1));
            li.querySelector('.remove-item').addEventListener('click', () => window.removeItem(item.uid));
            cartItems.appendChild(li);
        });
    }
    const countEl = document.querySelector('.cart-count');
    if (countEl) countEl.textContent = totalQuantity;
    atualizarTotalComSaldo();
}

/** Adiciona um item ao carrinho ou incrementa a quantidade se já existir um item idêntico. */
window.addToCart = async function(payload) {
    const item = {
        uid: makeUid(),
        productId: payload.id ?? null,
        name: payload.name,
        basePrice: Number(payload.basePrice || 0),
        size: payload.size || null,
        adicionais: (payload.adicionais || []).map(x => ({ id: x.id, price: Number(x.price || 0), name: x.name || '' })),
        quantity: Number(payload.quantity || 1),
        image: payload.image || null,
        remover: payload.remover || ''
    };

    const sameIndex = cart.findIndex(ci => {
        if (ci.productId === item.productId && ci.size === item.size) {
            const aIds = (ci.adicionais || []).map(x => x.id).sort().join('|');
            const bIds = (item.adicionais || []).map(x => x.id).sort().join('|');
            return aIds === bIds;
        }
        return false;
    });

    if (sameIndex >= 0) {
        cart[sameIndex].quantity += item.quantity;
    } else {
        cart.push(item);
    }
    await saveCart(cart);
    updateCart();
    window.toggleCart(true); // Abre o carrinho ao adicionar item
};

/** Remove um item do carrinho pelo seu UID. */
window.removeItem = async function(uid) {
    cart = cart.filter(item => item.uid !== uid);
    await saveCart(cart);
    updateCart();
};

/** Ajusta a quantidade de um item no carrinho. Remove se a quantidade for menor ou igual a zero. */
window.adjustQuantity = async function(uid, amount) {
    const item = cart.find(i => i.uid === uid);
    if (!item) return;
    
    item.quantity += amount;
    
    if (item.quantity <= 0) {
        await window.removeItem(uid);
    } else {
        await saveCart(cart);
        updateCart();
    }
};

/** Abre ou fecha a barra lateral do carrinho. */
window.toggleCart = function(forceOpen = false) {
    const cartElement = document.querySelector('.cart-sidebar');
    if (!cartElement) return;

    if (forceOpen === true) {
        atualizarSaldoUsuario(() => {
            cartElement.classList.add('active');
        });
    } else if (forceOpen === false) {
        cartElement.classList.remove('active');
    } else {
        // Alterna o estado
        if (cartElement.classList.contains('active')) {
            cartElement.classList.remove('active');
        } else {
            atualizarSaldoUsuario(() => {
                cartElement.classList.add('active');
            });
        }
    }
}


// --- LÓGICA DE SALDO DO USUÁRIO ---

/** Busca o saldo do usuário no servidor e atualiza a interface. */
function atualizarSaldoUsuario(callback) {
    fetch('balance/saldo.php?' + Date.now()) // Evita cache
        .then(response => response.ok ? response.json() : { saldo: '0,00' })
        .then(data => {
            let saldoStr = data.saldo || '0,00';
            let saldoNum = parseFloat(saldoStr.replace(/\./g, '').replace(',', '.')) || 0;
            saldoUsuario = saldoNum;
            
            const el = document.getElementById('saldoUsuario');
            if (el) el.textContent = formatPrice(saldoUsuario);
            
            atualizarTotalComSaldo();
            if (typeof callback === 'function') callback(saldoUsuario);
        })
        .catch(error => {
            console.error('Erro ao buscar saldo:', error);
            saldoUsuario = 0;
            const el = document.getElementById('saldoUsuario');
            if (el) el.textContent = '0,00';
            
            atualizarTotalComSaldo();
            if (typeof callback === 'function') callback(saldoUsuario);
        });
}

/** Recalcula e exibe o total do carrinho, aplicando o desconto do saldo se aplicável. */
function atualizarTotalComSaldo() {
    const usarSaldoEl = document.getElementById('usarSaldo');
    const usarSaldo = usarSaldoEl ? usarSaldoEl.checked : false;

    const subtotal = cart.reduce((total, item) => total + calcItemTotal(item), 0);
    const frete = window.freteValor || 0;
    const subtotalComFrete = subtotal + frete;
    const desconto = usarSaldo ? Math.min(saldoUsuario, subtotalComFrete) : 0;
    const totalFinal = subtotalComFrete - desconto;

    const el = document.getElementById('cartTotal');
    if (el) el.textContent = formatPrice(totalFinal);
}


// --- LÓGICA DO MENU E PRODUTOS ---

/** Busca a lista de produtos do servidor. */
function fetchProdutosJson() {
    return fetch('API/produtos.php?' + Date.now())
        .then(r => r.ok ? r.json() : { produtos: [], adicionais: [] })
        .then(data => Array.isArray(data.produtos) ? data.produtos : [])
        .catch(() => {
            console.error("Falha ao carregar produtos do servidor.");
            return [];
        });
}

/**
 * Renderiza uma lista de itens no container do menu.
 * @param {Array} itemsToRender - A lista de produtos a ser exibida.
 */
function renderMenuItems(itemsToRender) {
    const menuContainer = document.getElementById('menuContainer');
    if (!menuContainer) return;
    menuContainer.innerHTML = '';

    if (itemsToRender.length === 0) {
        menuContainer.innerHTML = '<p>Nenhum produto encontrado.</p>';
        return;
    }

    itemsToRender.forEach(p => {
        const card = document.createElement('article');
        card.className = 'menu-item';

        // Descrição - garante que sempre usa um valor válido
        const description = (p.descricao || p.description || '').toString();
        
        // Preço "A partir de"
        const basePrice = (p.sizes && p.sizes.length) 
            ? Math.min(...p.sizes.map(s => Number(s.price || 0))) 
            : (Number(p.basePrice || p.price || 0));
        const priceText = basePrice > 0 ? `A partir de R$ ${formatPrice(basePrice)}` : 'Consulte';

        card.innerHTML = `
            <div class="item-image" style="background-image: url('${p.image || ''}');"></div>
            <div class="item-content">
                <h3 class="item-title">${p.name}</h3>
                <p class="item-description">${description}</p>
                <div class="item-footer">
                    <div class="item-price">${priceText}</div>
                    <button class="add-to-cart" type="button">Escolher</button>
                </div>
            </div>
        `;
        
        card.querySelector('.add-to-cart').addEventListener('click', () => {
            // Se for bebida, não adiciona adicionais
            const produtoParaModal = { ...p };
            if (produtoParaModal.isDrink) {
                produtoParaModal.adicionais = [];
            } else {
                try {
                    if (window.localStorage.getItem('adicionais')) {
                        produtoParaModal.adicionais = JSON.parse(window.localStorage.getItem('adicionais'));
                    }
                } catch {}
            }
            window.openProductOptions(produtoParaModal);
        });

        menuContainer.appendChild(card);
    });
}


/** Carrega e inicializa o menu principal e as bebidas. */
async function initMenu() {
    let products = await fetchProdutosJson();
    if (!Array.isArray(products)) products = [];
    // Marca drinks e sucos para exibir corretamente no modal
    products.forEach(p => {
        if (String(p.id).startsWith('drink_')) p.isDrink = true;
        if (String(p.id).startsWith('suco_')) p.isSuco = true;
    });
    menuItems = products;
    renderMenuItems(menuItems);
}


// --- LÓGICA DO MODAL DE OPÇÕES ---

let currentProduct = null;
const modal = document.getElementById('productModal');
const modalTitle = document.getElementById('modalTitle');
const modalPrice = document.getElementById('modalPrice');
const sizesContainer = document.getElementById('sizesContainer');
const adicionaisContainer = document.getElementById('adicionaisContainer');
const modalForm = document.getElementById('modalForm');
const modalQty = document.getElementById('modalQty');

function updateModalPrice() {
    if (!currentProduct || !modalForm) return;
    
    const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
    const sizePrice = Number(sizeRadio?.dataset?.price || 0);
    
    const adicionaisChecked = Array.from(modalForm.querySelectorAll('input[name="adicional"]:checked'));
    const extrasPrice = adicionaisChecked.reduce((sum, cb) => sum + Number(cb.dataset.price || 0), 0);
    
    const qty = Number(modalQty.value || 1);
    const total = (sizePrice + extrasPrice) * qty;
    
    modalPrice.textContent = formatPrice(total);
}

window.openProductOptions = function (product) {
    if (!modal || !product) return;
    currentProduct = product;
    modalTitle.textContent = product.name || 'Produto';
    modalQty.value = 1;
    // Renderiza tamanhos
    sizesContainer.innerHTML = '';
    const rawSizes = product.sizes && product.sizes.length ? product.sizes : [{ name: 'Único', price: product.basePrice || product.price || 0 }];
    rawSizes.forEach((s, i) => {
        const name = s.name ?? 'Tamanho';
        const price = Number(s.price || 0);
        const label = document.createElement('label');
        label.className = 'option-item';
        label.innerHTML = `<input type="radio" name="size" value="${i}" data-price="${price}" ${i === 0 ? 'checked' : ''}> ${name} ${price > 0 ? `– R$ ${formatPrice(price)}` : ''}`;
        sizesContainer.appendChild(label);
    });
    // Renderiza adicionais (oculta se for bebida)
    adicionaisContainer.innerHTML = '';
    let showRemoveBox = false;
    if (product.isDrink) {
        adicionaisContainer.innerHTML = '<div class="muted">Bebida não possui adicionais.</div>';
    } else if (product.isSuco) {
        adicionaisContainer.innerHTML = '<div class="muted">Suco não possui adicionais.</div>';
        showRemoveBox = true;
    } else {
        const extras = product.adicionais || [];
        if (extras.length === 0) {
            adicionaisContainer.innerHTML = '<div class="muted">Nenhum adicional disponível.</div>';
        } else {
            extras.forEach((a) => {
                const price = Number(a.price || 0);
                const aid = a.id ?? a.name;
                const label = document.createElement('label');
                label.className = 'option-item';
                label.innerHTML = `<input type="checkbox" name="adicional" value="${aid}" data-price="${price}" data-name="${a.name || ''}"> ${a.name} ${price > 0 ? `(+R$ ${formatPrice(price)})` : ''}`;
                adicionaisContainer.appendChild(label);
            });
        }
        showRemoveBox = true;
    }
    // Adiciona caixa de texto para remoção de ingredientes
    if (showRemoveBox) {
        const removeInput = document.createElement('input');
        removeInput.type = 'text';
        removeInput.id = 'removeInput';
        removeInput.placeholder = 'Ex: sem cebola, sem gelo, sem açúcar';
        removeInput.style.width = '100%';
        removeInput.style.marginTop = '8px';
        removeInput.style.padding = '8px';
        removeInput.style.border = '1px solid #ccc';
        removeInput.style.borderRadius = '8px';
        removeInput.style.fontSize = '1rem';
        removeInput.style.background = '#fafafa';
        removeInput.style.boxSizing = 'border-box';
        adicionaisContainer.appendChild(removeInput);
    }
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    updateModalPrice();
};

window.closeProductModal = function () {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    currentProduct = null;
};


// --- LÓGICA DE FINALIZAÇÃO DA COMPRA ---

async function finalizarCompra() {
    if (cart.length === 0) {
        alert("Seu carrinho está vazio!");
        return;
    }

    const usarSaldo = document.getElementById('usarSaldo')?.checked || false;
    const subtotal = cart.reduce((total, item) => total + calcItemTotal(item), 0);
    const frete = window.freteValor || 0;
    const totalPedido = subtotal + frete;
    const valorPagoComSaldo = usarSaldo ? Math.min(totalPedido, saldoUsuario) : 0;
    const totalFinal = totalPedido - valorPagoComSaldo;

    let resumo = 'Pedido Finalizado!\n\nItens:\n';
    cart.forEach(item => {
        resumo += `- ${item.quantity}x ${item.name} ${item.size ? `(${item.size})` : ''}\n`;
    });
    resumo += `\nSubtotal: R$ ${formatPrice(subtotal)}`;
    if (frete > 0) {
        resumo += `\nFrete: R$ ${formatPrice(frete)}`;
    }
    if (usarSaldo && valorPagoComSaldo > 0) {
        resumo += `\nSaldo Utilizado: - R$ ${formatPrice(valorPagoComSaldo)}`;
    }
    resumo += `\n\nTotal a Pagar: R$ ${formatPrice(totalFinal)}`;

    // alert(resumo);
    addClientLog('Resumo do Pedido', { resumo });

    if (valorPagoComSaldo > 0) {
        fetch('balance/descontar_saldo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                valor: parseFloat(valorPagoComSaldo),
                descricao: 'Compra realizada no cardápio',
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Erro ao atualizar saldo: ' + data.message);
            }
            // Sempre atualiza o saldo na interface, mesmo se falhar
            atualizarSaldoUsuario();
        })
        .catch(error => {
            console.error('Erro ao descontar saldo:', error);
            atualizarSaldoUsuario();
        });
    }

    cart = [];
    await saveCart(cart);
    updateCart();
    toggleCart(false); // Fecha o carrinho
}

// --- INICIALIZAÇÃO E EVENT LISTENERS ---

document.addEventListener('DOMContentLoaded', () => {
    
    // Inicialização principal
    async function init() {
        await initMenu();
        // Limpa o carrinho a cada carregamento da página
        cart = [];
        await saveCart(cart);
        updateCart();
        atualizarSaldoUsuario();
    }

    init();
    
    // Listener do Modal de Produtos
    if (modal) {
        modal.querySelector('.close-button')?.addEventListener('click', closeProductModal);
        modal.querySelector('.modal-close')?.addEventListener('click', closeProductModal);
        sizesContainer?.addEventListener('change', updateModalPrice);
        adicionaisContainer?.addEventListener('change', updateModalPrice);
        modalQty?.addEventListener('input', updateModalPrice);

        modalForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!currentProduct) return;
            const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
            const sizeLabel = sizeRadio ? sizeRadio.parentElement.textContent.trim().split('–')[0].trim() : null;
            const sizePrice = Number(sizeRadio?.dataset?.price || 0);
            const adicionaisChecked = Array.from(modalForm.querySelectorAll('input[name="adicional"]:checked'))
                .map(cb => ({
                    id: cb.value,
                    price: Number(cb.dataset.price || 0),
                    name: cb.dataset.name || ''
                }));
            // Pega valor da caixa de remoção
            let remover = '';
            if (!currentProduct.isDrink) {
                const removeInput = document.getElementById('removeInput');
                if (removeInput) remover = removeInput.value.trim();
            }
            window.addToCart({
                id: currentProduct.id,
                name: currentProduct.name,
                image: currentProduct.image,
                basePrice: sizePrice,
                size: sizeLabel,
                adicionais: adicionaisChecked,
                quantity: Number(modalQty.value) || 1,
                remover
            });
            closeProductModal();
        });
    }

    // Listeners do Carrinho
    document.getElementById('usarSaldo')?.addEventListener('change', atualizarTotalComSaldo);
    document.querySelector('.cart-button')?.addEventListener('click', () => window.toggleCart(true));
    document.querySelector('.close-cart')?.addEventListener('click', () => window.toggleCart(false));
    document.getElementById('checkoutButton')?.addEventListener('click', finalizarCompra);
    
    // Listener da Barra de Pesquisa
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const filter = searchInput.value.toLowerCase().trim();
            const filteredItems = menuItems.filter(item =>
                item.name.toLowerCase().includes(filter) ||
                (item.description && item.description.toLowerCase().includes(filter)) ||
                (item.descricao && item.descricao.toLowerCase().includes(filter))
            );
            renderMenuItems(filteredItems);
        });
    }

    // Listener do Menu Hambúrguer (Mobile)
    const burger = document.querySelector('.menu-hamburguer');
    const nav = document.querySelector('.nav');
    if (burger && nav) {
        burger.addEventListener('click', (e) => {
            e.stopPropagation();
            nav.classList.toggle('active');
            const isActive = nav.classList.contains('active');
            burger.setAttribute('aria-expanded', isActive.toString());
            nav.setAttribute('aria-hidden', (!isActive).toString());
            document.body.style.overflow = isActive ? 'hidden' : '';
        });
    }

    // Listener para atualizar saldo ao focar na janela (útil ao voltar de outra aba)
    window.addEventListener('focus', atualizarSaldoUsuario);
});