// Dados do menu (incluindo adicionais, mesmo que vazios)
let menuItems = [];
try {
    menuItems = JSON.parse(localStorage.getItem('menuItems')) || [];
    if (!Array.isArray(menuItems) || menuItems.length === 0) throw 0;
} catch {
    // fallback para produtos fixos se localStorage estiver vazio
    menuItems = [
        {
            id: 1,
            name: '5 queijos',
            description: 'Molho de tomate especial, mussarela ralada, provolone, parmessão, catupiry, cheddar, oregano e azeitonas',
            image: 'img/pizzas/5_queijos.png',
            sizes: [
                { name: 'Pequena', price: 37.00 },
                { name: 'Média', price: 78.50 },
                { name: 'Grande', price: 105.00 }
            ],
            adicionais: [
                { id: 'bacon', name: 'Bacon', price: 4.00 },
                { id: 'catupiry', name: 'Catupiry', price: 5.00 }
            ]
        },
        {
            id: 2,
            name: '3 Porquinhos',
            description: 'Molho de tomate especial, mussarela, lombo canadense, calabresa fatiada, bacon, oregano e azeitonas',
            image: 'img/pizzas/3_porcos.png',
            sizes: [
                { name: 'Pequena', price: 37.00 },
                { name: 'Média', price: 78.50 },
                { name: 'Grande', price: 105.00 }
            ],
            adicionais: [
                { id: 'bacon', name: 'Bacon', price: 4.00 },
                { id: 'azeitonas', name: 'Azeitonas', price: 2.00 }
            ]
        },
        {
            id: 3,
            name: 'Bauru',
            description: 'Molho de tomate especial, mussarela ralada, coxão mole em tiras, tomate em rodelas, orégano e azeitonas',
            image: 'img/pizzas/bauru.png',
            sizes: [
                { name: 'Pequena', price: 40.00 },
                { name: 'Média', price: 78.50 },
                { name: 'Grande', price: 105.00 }
            ],
            adicionais: [
                { id: 'extra_carne', name: 'Extra Carne', price: 6.00 }
            ]
        },
        {
            id: 4,
            name: 'Brocolis Especial',
            description: 'Molho de tomate especial, mussarela ralada, Brocolis refogado, bacon em cubos, catupiry, alho frito, orégano e azeitonas',
            image: 'img/pizzas/brocolis.png',
            sizes: [
                { name: 'Pequena', price: 40.00 },
                { name: 'Média', price: 78.50 },
                { name: 'Grande', price: 105.00 }
            ],
            adicionais: [] // Sem adicionais, para exemplo
        },
        {
            id: 5,
            name: 'California',
            description: 'Molho de tomate especial, mussarela ralada, calabresa fatiada, abacaxi cortado, catupiry, orégano e azeitonas',
            image: 'img/pizzas/california.png',
            sizes: [
                { name: 'Pequena', price: 40.00 },
                { name: 'Média', price: 78.50 },
                { name: 'Grande', price: 105.00 }
            ],
            adicionais: [] // Sem adicionais, para exemplo
        },
    ];
}

let cart = [];
let saldoUsuario = 0; // Inicializa o saldo como zero
let _cartIdCounter = 1;

// --- FUNÇÕES DO SISTEMA ---

function makeUid() {
    return 'c' + (_cartIdCounter++);
}

function calcItemTotal(item) {
    const extras = (item.adicionais || []).reduce((s, a) => s + (a.price || 0), 0);
    const single = (item.basePrice || 0) + extras;
    return single * (item.quantity || 1);
}

function formatPrice(v) {
    return Number(v).toFixed(2).replace('.', ',');
}

// --- LÓGICA DO CARRINHO ---

function updateCart() {
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) return;
    cartItems.innerHTML = '';
    let total = 0;

    cart.forEach(item => {
        const li = document.createElement('li');
        li.className = 'cart-line';
        const lineTotal = calcItemTotal(item);
        total += lineTotal;

        li.innerHTML = `
            <div class="cart-line-left">
                <strong>${item.name}</strong> ${item.size ? `<small>(${item.size})</small>` : ''}
                ${(item.adicionais && item.adicionais.length) ? `<div class="cart-extras">${item.adicionais.map(a => `<span class="extra-item">+ ${a.name} R$ ${formatPrice(a.price)}</span>`).join('')}</div>` : ''}
                <div class="cart-qty">Quantidade: <button class="qty-minus">−</button> ${item.quantity} <button class="qty-plus">+</button></div>
            </div>
            <div class="cart-line-right">
                <div>R$ ${formatPrice(lineTotal)}</div>
                <div><button class="remove-item">Remover</button></div>
            </div>
        `;
        // Adiciona listeners após inserir no DOM
        setTimeout(() => {
            li.querySelector('.qty-minus').addEventListener('click', () => adjustQuantity(item.uid, -1));
            li.querySelector('.qty-plus').addEventListener('click', () => adjustQuantity(item.uid, 1));
            li.querySelector('.remove-item').addEventListener('click', () => removeItem(item.uid));
        }, 0);

        cartItems.appendChild(li);
    });

    const countEl = document.querySelector('.cart-count');
    if (countEl) countEl.textContent = cart.reduce((acc, i) => acc + i.quantity, 0);

    atualizarTotalComSaldo();
}

window.removeItem = function(uid) {
    cart = cart.filter(item => item.uid !== uid);
    updateCart();
};

window.adjustQuantity = function(uid, amount) {
    const item = cart.find(i => i.uid === uid);
    if (!item) return;
    item.quantity += amount;
    if (item.quantity <= 0) {
        removeItem(uid);
    } else {
        updateCart();
    }
};

window.addToCart = function(payload) {
    const item = {
        uid: makeUid(),
        productId: payload.id ?? null,
        name: payload.name,
        basePrice: Number(payload.basePrice || 0),
        size: payload.size || null,
        adicionais: (payload.adicionais || []).map(x => ({ id: x.id, price: Number(x.price || 0), name: x.name || '' })),
        quantity: Number(payload.quantity || 1)
    };

    const sameIndex = cart.findIndex(ci => {
        if (ci.productId && item.productId && ci.productId === item.productId && ci.size === item.size) {
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
    updateCart();
    toggleCart(); 
};




function setSaldoUsuario(valor) {
    saldoUsuario = Number(valor) || 0;
    const el = document.getElementById('saldoUsuario');
    if (el) el.textContent = formatPrice(saldoUsuario);
    atualizarTotalComSaldo();
}

function atualizarTotalComSaldo() {
    const usarSaldoEl = document.getElementById('usarSaldo');
    const usarSaldo = !!(usarSaldoEl && usarSaldoEl.checked);
    
    const s = Number(saldoUsuario) || 0;
    const t = cart.reduce((total, item) => total + calcItemTotal(item), 0);
    
    // Calcula o desconto real a ser aplicado, não podendo ser maior que o total
    const desconto = usarSaldo ? Math.min(s, t) : 0;
    
    const totalFinal = t - desconto;
    
    const el = document.getElementById('cartTotal');
    if (el) el.textContent = formatPrice(totalFinal);
}

// --- LÓGICA DO MENU ---

function initMenu() {
    const menuContainer = document.getElementById('menuContainer');
    if (!menuContainer) return;
    menuContainer.innerHTML = '';
    
    menuItems.forEach(p => {
        const card = document.createElement('article');
        card.className = 'menu-item';

        const img = document.createElement('div');
        img.className = 'item-image';
        img.style.backgroundImage = `url(${p.image})`;

        const content = document.createElement('div');
        content.className = 'item-content';

        const title = document.createElement('h3');
        title.className = 'item-title';
        title.textContent = p.name;

        const desc = document.createElement('p');
        desc.className = 'item-description';
        desc.textContent = p.description;

        const footer = document.createElement('div');
        footer.className = 'item-footer';

        const basePrice = (p.sizes && p.sizes.length) ? Math.min(...p.sizes.map(s => Number(s.price || 0))) : 0;

        const price = document.createElement('div');
        price.className = 'item-price';
        price.textContent = `A partir de R$ ${formatPrice(basePrice)}`;

        const btn = document.createElement('button');
        btn.className = 'add-to-cart';
        btn.type = 'button';
        btn.textContent = 'Escolher opções';
        
        btn.addEventListener('click', () => {
            window.openProductOptions(p);
        });

        footer.appendChild(price);
        footer.appendChild(btn);

        content.appendChild(title);
        content.appendChild(desc);
        content.appendChild(footer);

        card.appendChild(img);
        card.appendChild(content);

        menuContainer.appendChild(card);
    });
}

function renderMenu() {
    const menuContainer = document.getElementById('menuContainer');
    menuContainer.innerHTML = '';
    menuItems.forEach(item => {
        const card = document.createElement('div');
        card.className = 'menu-card';
        card.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <h3>${item.name}</h3>
            <button class="btn" onclick="openProductOptions(menuItems.find(i=>i.id==${item.id}))">Escolher opções</button>
        `;
        menuContainer.appendChild(card);
    });
}


// --- LÓGICA DO MODAL DE OPÇÕES (integrado aqui) ---

const modal = document.getElementById('productModal');
const modalTitle = document.getElementById('modalTitle');
const modalPrice = document.getElementById('modalPrice');
const sizesContainer = document.getElementById('sizesContainer');
const adicionaisContainer = document.getElementById('adicionaisContainer');
const modalForm = document.getElementById('modalForm');
const modalQty = document.getElementById('modalQty');
let currentProduct = null;

function updateModalPrice() {
    if (!currentProduct) return;
    const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
    const sizePrice = Number(sizeRadio?.dataset?.price || 0);
    const adicionaisChecked = Array.from(modalForm.querySelectorAll('input[name="adicional"]:checked'))
        .map(cb => Number(cb.dataset.price || 0));
    const extras = adicionaisChecked.reduce((s, n) => s + n, 0);
    const qty = Number(modalQty.value || 1);
    const total = (sizePrice + extras) * qty;
    modalPrice.textContent = formatPrice(total);
}

window.openProductOptions = function (product) {
    currentProduct = product || {};
    modalTitle.textContent = product.name || 'Produto';
    modalQty.value = 1;

    sizesContainer.innerHTML = '';
    const rawSizes = product.sizes && product.sizes.length ? product.sizes : (product.price ? [{ name: 'Único', price: product.price }] : [{ name: 'Único', price: 0 }]);
    rawSizes.forEach((s, i) => {
        const name = (typeof s === 'string') ? s : (s.name ?? 'Tamanho');
        const price = (typeof s === 'string') ? (Number(product.price || 0)) : Number(s.price || 0);
        const label = document.createElement('label');
        label.className = 'option-item';
        label.innerHTML = `<input type="radio" name="size" value="${i}" data-price="${price}" ${i === 0 ? 'checked' : ''}> ${name} ${Number(price) ? `– R$ ${formatPrice(price)}` : ''}`;
        sizesContainer.appendChild(label);
    });

    adicionaisContainer.innerHTML = '';
    const extras = product.adicionais && product.adicionais.length ? product.adicionais : [];
    if (extras.length === 0) {
        adicionaisContainer.innerHTML = '<div class="muted">Nenhum adicional disponível</div>';
    } else {
        extras.forEach((a) => {
            const price = Number(a.price || 0);
            const label = document.createElement('label');
            label.className = 'option-item';
            const aid = a.id ?? a.name;
            label.innerHTML = `<input type="checkbox" name="adicional" value="${aid}" data-price="${price}" data-name="${(a.name||'') }"> ${a.name} ${price ? `(+R$ ${formatPrice(price)})` : ''}`;
            adicionaisContainer.appendChild(label);
        });
    }

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(updateModalPrice, 20);
};

window.closeProductModal = function () {
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    currentProduct = null;
};

// --- CONTROLE DE VISIBILIDADE DO CARRINHO ---

window.toggleCart = function() {
    const cartElement = document.querySelector('.cart-sidebar');
    if (cartElement) {
        if (!cartElement.classList.contains('active')) {
            atualizarSaldoUsuario(() => {
                cartElement.classList.add('active');
            });
        } else {
            cartElement.classList.remove('active');
        }
    }
}


function finalizarCompra() {
    if (cart.length === 0) {
        alert("Seu carrinho está vazio!");
        return;
    }

    atualizarSaldoUsuario(saldoAtualizado => {
        const usarSaldo = document.getElementById('usarSaldo').checked;
        const totalPedido = cart.reduce((total, item) => total + calcItemTotal(item), 0);
        const valorPagoComSaldo = usarSaldo ? Math.min(totalPedido, saldoAtualizado) : 0;
        const totalFinal = totalPedido - valorPagoComSaldo;

        let resumo = 'Pedido Finalizado!\n\nItens:\n';
        cart.forEach(item => {
            resumo += `- ${item.quantity}x ${item.name} (${item.size})\n`;
        });
        resumo += `\nSubtotal: R$ ${formatPrice(totalPedido)}`;
        if (usarSaldo && valorPagoComSaldo > 0) {
            resumo += `\nSaldo Utilizado: - R$ ${formatPrice(valorPagoComSaldo)}`;
        }
        resumo += `\n\nTotal a Pagar: R$ ${formatPrice(totalFinal)}`;

        alert(resumo);

        // Atualiza o saldo no servidor
        if (valorPagoComSaldo > 0) {
            fetch('balance/descontar_saldo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    valor: parseFloat(valorPagoComSaldo),
                    descricao: 'Compra realizada',
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    atualizarSaldoUsuario(() => {
                        // Força atualização do saldo na tela
                        const el = document.getElementById('saldoUsuario');
                        if (el && typeof data.saldo_depois !== 'undefined') {
                            el.textContent = Number(data.saldo_depois).toFixed(2).replace('.', ',');
                        }
                    });
                } else {
                    alert('Erro ao atualizar saldo: ' + data.message);
                }
            })
            .catch(error => console.error('Erro ao descontar saldo:', error));
        }

        cart = [];
        updateCart();
        toggleCart();
    });
}


document.addEventListener('DOMContentLoaded', () => {
    if (modal) {
        modal.querySelector('.close-button')?.addEventListener('click', closeProductModal);
        modal.querySelector('.modal-close')?.addEventListener('click', closeProductModal);
    }
    if (sizesContainer) sizesContainer.addEventListener('change', updateModalPrice);
    if (adicionaisContainer) adicionaisContainer.addEventListener('change', updateModalPrice);
    if (modalQty) modalQty.addEventListener('input', updateModalPrice);
    
    if (modalForm) {
        modalForm.addEventListener('submit', function (e) {
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

            const qty = Number(modalQty.value) || 1;

            const payload = {
                id: currentProduct.id,
                name: currentProduct.name,
                basePrice: sizePrice,
                size: sizeLabel,
                adicionais: adicionaisChecked,
                quantity: qty
            };

            window.addToCart(payload);
            closeProductModal();
        });
    }

    const usarSaldoEl = document.getElementById('usarSaldo');
    if (usarSaldoEl) usarSaldoEl.addEventListener('change', () => {
        atualizarSaldoUsuario();
    });


    const cartButton = document.querySelector('.cart-button');
    if (cartButton) {
        cartButton.addEventListener('click', () => {
            document.querySelector('.cart-sidebar').classList.add('active');
        });
    }

    const closeCartButton = document.querySelector('.close-cart');
    if (closeCartButton) {
        closeCartButton.addEventListener('click', () => {
            document.querySelector('.cart-sidebar').classList.remove('active');
        });
    }
    
    const checkoutButton = document.getElementById('checkoutButton');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', finalizarCompra);
    }

    initMenu();
    updateCart();
    atualizarSaldoUsuario();
});


function addClientLog(action, details) {
    const logs = JSON.parse(localStorage.getItem('clientLogs') || '[]');
    logs.push({
        time: new Date().toLocaleString(),
        action,
        details
    });
    localStorage.setItem('clientLogs', JSON.stringify(logs));
}

// Função para atualizar o saldo no carrinho
function atualizarSaldoUsuario(callback) {
    fetch('balance/saldo.php?' + Date.now()) // Adiciona timestamp para evitar cache
        .then(response => response.json())
        .then(data => {
            let saldoStr = data.saldo;
            let saldoNum = 0;
            if (saldoStr) {
                saldoNum = parseFloat(saldoStr.replace('.', '').replace(',', '.'));
            }
            saldoUsuario = saldoNum;
            const el = document.getElementById('saldoUsuario');
            if (el) el.textContent = saldoNum.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            atualizarTotalComSaldo();
            if (typeof callback === 'function') callback(saldoUsuario);
        })
        .catch(error => {
            const el = document.getElementById('saldoUsuario');
            if (el) el.textContent = '0,00';
            saldoUsuario = 0;
            atualizarTotalComSaldo();
            if (typeof callback === 'function') callback(saldoUsuario);
        });
}

// Atualiza saldo ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    atualizarSaldoUsuario();
});

// Atualiza saldo ao voltar para a página principal ou ao abrir o carrinho
window.addEventListener('focus', () => {
    atualizarSaldoUsuario();
});

// Finalizar compra: busca saldo antes de calcular/descontar
function finalizarCompra() {
    if (cart.length === 0) {
        alert("Seu carrinho está vazio!");
        return;
    }
        const usarSaldo = document.getElementById('usarSaldo').checked;
        const totalPedido = cart.reduce((total, item) => total + calcItemTotal(item), 0);
        const valorPagoComSaldo = usarSaldo ? Math.min(totalPedido, saldoUsuario) : 0;
        const totalFinal = totalPedido - valorPagoComSaldo;
        let resumo = 'Pedido Finalizado!\n\nItens:\n';
        cart.forEach(item => {
            resumo += `- ${item.quantity}x ${item.name} (${item.size})\n`;
        });
        resumo += `\nSubtotal: R$ ${formatPrice(totalPedido)}`;
        if (usarSaldo && valorPagoComSaldo > 0) {
            resumo += `\nSaldo Utilizado: - R$ ${formatPrice(valorPagoComSaldo)}`;
        }
        resumo += `\n\nTotal a Pagar: R$ ${formatPrice(totalFinal)}`;
        alert(resumo);
        addClientLog('Resumo do Pedido', { resumo });
        if (valorPagoComSaldo > 0) {
            fetch('balance/descontar_saldo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    valor: parseFloat(valorPagoComSaldo),
                    descricao: 'Compra realizada',
                }),
            })
            .then(response => response.json())
            .then(data => {
                atualizarSaldoUsuario();
                if (!data.success) {
                    alert('Erro ao atualizar saldo: ' + data.message);
                }
            })
            .catch(error => {
                atualizarSaldoUsuario();
                console.error('Erro ao descontar saldo:', error);
            });
        } else {
            atualizarSaldoUsuario();
        }
        cart = [];
        updateCart();
        window.toggleCart();
}
document.addEventListener('DOMContentLoaded', function () {
  const burger = document.querySelector('.menu-hamburguer');
  const nav = document.querySelector('.nav');

  if (!burger || !nav) return; 

  if (!nav.id) nav.id = 'main-nav';
  burger.setAttribute('role', 'button');
  burger.setAttribute('aria-controls', nav.id);
  burger.setAttribute('aria-expanded', 'false');
  nav.setAttribute('aria-hidden', 'true');

  // Ícones simples (pode trocar por innerHTML com SVG se preferir)
  const ICON_OPEN = '☰';
  const ICON_CLOSE = '✕';

  // garante ícone inicial
  if (!burger.dataset.iconInitialized) {
    burger.innerHTML = ICON_OPEN;
    burger.dataset.iconInitialized = '1';
  }

  function openNav() {
    nav.classList.add('active');
    burger.classList.add('open');
    burger.setAttribute('aria-expanded', 'true');
    nav.setAttribute('aria-hidden', 'false');
    // bloquear scroll do fundo (útil no mobile)
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    burger.innerHTML = ICON_CLOSE;
  }

  function closeNav() {
    nav.classList.remove('active');
    burger.classList.remove('open');
    burger.setAttribute('aria-expanded', 'false');
    // No desktop o nav é visível por CSS; aria-hidden fica false em desktop via onResize()
    nav.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    burger.innerHTML = ICON_OPEN;
  }

  function toggleNav() {
    if (nav.classList.contains('active')) closeNav();
    else openNav();
  }

  // clique no hambúrguer
  burger.addEventListener('click', function (e) {
    e.stopPropagation();
    toggleNav();
  });

  // clicar fora do nav fecha (útil no mobile)
  document.addEventListener('click', function (e) {
    if (!nav.classList.contains('active')) return;
    if (!nav.contains(e.target) && e.target !== burger) closeNav();
  });

  // fechar com Esc
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && nav.classList.contains('active')) closeNav();
  });

  // fechar ao clicar em link do menu (âncoras)
  nav.addEventListener('click', function (e) {
    if (e.target.matches('a')) closeNav();
  });

  // ajustar comportamento no resize: em desktop nav deve ficar visível (não bloqueado)
  function onResize() {
    if (window.innerWidth > 768) {
      // garantir nav visível por CSS; remove estado mobile
      nav.classList.remove('active');
      burger.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
      nav.setAttribute('aria-hidden', 'false'); // desktop: não escondido
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      burger.innerHTML = ICON_OPEN;
    } else {
      // mobile: aria-hidden reflete estado atual
      nav.setAttribute('aria-hidden', nav.classList.contains('active') ? 'false' : 'true');
    }
  }
  window.addEventListener('resize', onResize);
  onResize();
});
