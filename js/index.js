window.cart = window.cart || [];
window.menuItems = window.menuItems || [];
window.cartKey = "cart"; // chave unificada para todos
window.freteValor = 0;

function formatPrice(valor) {
  return Number(valor).toLocaleString("pt-BR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function makeUid() {
  return "uid_" + Math.random().toString(36).substr(2, 9) + "_" + Date.now();
}

async function getCart() {
  const data = localStorage.getItem(cartKey);
  const parsed = data ? JSON.parse(data) : [];
  console.log("%cgetCart (" + cartKey + "):", "color: #4caf50", parsed);
  return Array.isArray(parsed) ? parsed : [];
}

async function saveCart(c) {
  const json = JSON.stringify(c || []);
  localStorage.setItem(cartKey, json);
  console.log("%csaveCart (" + cartKey + "): " + c.length + " item(s)", "color: #2196f3");

  try {
    await fetch("API/cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: json,
    });
  } catch (e) {
    console.error("Erro ao salvar no servidor:", e);
  }
}

function calcItemTotal(item) {
  const base = Number(item.basePrice || 0);
  const extras = (item.adicionais || []).reduce(
    (s, a) => s + Number(a.price || 0),
    0
  );
  return (base + extras) * (item.quantity || 1);
}

// === ATUALIZA CARRINHO ===
function updateCart() {
  const list = document.getElementById("cartItems");
  if (!list) return;

  list.innerHTML = "";
  if (cart.length === 0) {
    list.innerHTML = '<li class="cart-empty">Seu carrinho está vazio.</li>';
  } else {
    cart.forEach((item) => {
      const li = document.createElement("li");
      const totalItem = calcItemTotal(item);
      li.className = "cart-line";

      const adicionais = item.adicionais
        ?.map((a) => `+ ${a.name}`)
        .join(", ") || "";

      li.innerHTML = `
        <div class="cart-line-left">
          <img src="${item.image || "img/default.png"}"
               style="width:70px;height:70px;border-radius:10px;margin-right:8px;object-fit:cover;">
          <div>
            <strong>${item.name}</strong> ${item.size ? `(${item.size})` : ""}<br>
            ${adicionais ? `<small>${adicionais}</small><br>` : ""}
            <div class="cart-qty">
              <button class="qty-minus">−</button>
              <span>${item.quantity}</span>
              <button class="qty-plus">+</button>
            </div>
          </div>
        </div>
        <div class="cart-line-right">
          R$ ${formatPrice(totalItem)}<br>
          <button class="remove-item">Remover</button>
        </div>
      `;

      li.querySelector(".qty-minus").onclick = () => adjustQuantity(item.uid, -1);
      li.querySelector(".qty-plus").onclick = () => adjustQuantity(item.uid, 1);
      li.querySelector(".remove-item").onclick = () => removeItem(item.uid);

      list.appendChild(li);
    });
  }

  const total = cart.reduce((s, i) => s + calcItemTotal(i), 0);
  const totalEl = document.getElementById("cartTotal");
  if (totalEl) totalEl.textContent = formatPrice(total);

  // Atualizar contador do carrinho flutuante
  updateCartCounter();
}

// === ATUALIZAR CONTADOR DO CARRINHO ===
function updateCartCounter() {
  const counter = document.querySelector(".cart-count");
  if (counter) {
    const totalItems = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
    counter.textContent = totalItems;
  }
}

// === ADICIONAR AO CARRINHO ===
window.addToCart = async function (payload) {
  const item = {
    uid: makeUid(),
    productId: payload.id ?? null,
    name: payload.name,
    basePrice: Number(payload.basePrice || 0),
    size: payload.size || null,
    adicionais: (payload.adicionais || []).map((x) => ({
      id: x.id,
      price: Number(x.price || 0),
      name: x.name || "",
    })),
    quantity: Number(payload.quantity || 1),
    image: payload.image || null,
  };

  const existing = cart.findIndex(
    (i) =>
      i.productId === item.productId &&
      i.size === item.size &&
      JSON.stringify(i.adicionais) === JSON.stringify(item.adicionais)
  );

  if (existing >= 0) {
    cart[existing].quantity += item.quantity;
  } else {
    cart.push(item);
  }

  await saveCart(cart);
  updateCart();
  toggleCart(true);
};

// === REMOVER / ALTERAR ===
window.removeItem = async function (uid) {
  cart = cart.filter((i) => i.uid !== uid);
  await saveCart(cart);
  updateCart();
};

window.adjustQuantity = async function (uid, amount) {
  const item = cart.find((i) => i.uid === uid);
  if (!item) return;
  item.quantity += amount;
  if (item.quantity <= 0) {
    await removeItem(uid);
  } else {
    await saveCart(cart);
    updateCart();
  }
};

window.toggleCart = function (forceOpen = false) {
  console.log("toggleCart called with forceOpen:", forceOpen);
  const sidebar = document.querySelector(".cart-sidebar");
  const backdrop = document.querySelector(".cart-backdrop");
  console.log("sidebar element:", sidebar);
  if (!sidebar) return;
  if (forceOpen === true) {
    sidebar.classList.add("active");
    if (backdrop) backdrop.classList.add("active");
    console.log("Added active class");
  } else if (forceOpen === false) {
    sidebar.classList.remove("active");
    if (backdrop) backdrop.classList.remove("active");
    console.log("Removed active class");
  } else {
    sidebar.classList.toggle("active");
    if (backdrop) backdrop.classList.toggle("active");
    console.log("Toggled active class, now:", sidebar.classList.contains("active"));
  }
};

// === CARREGAR PRODUTOS ===
async function fetchProdutos() {
  try {
    const r = await fetch("API/produtos.php?" + Date.now());
    const j = await r.json();
    // compatível com retorno simples ou com {produtos: []}
    if (Array.isArray(j)) return j;
    if (Array.isArray(j.produtos)) return j.produtos;
    console.warn("Formato inesperado de produtos:", j);
    return [];
  } catch (err) {
    console.error("Erro ao carregar produtos:", err);
    return [];
  }
}

function renderMenu(items) {
  const container = document.getElementById("menuContainer");
  if (!container) return;
  container.innerHTML = "";

  if (!items.length) {
    container.innerHTML = "<p>Nenhum produto encontrado.</p>";
    return;
  }

  items.forEach((p) => {
    const basePrice = p.sizes?.length
      ? Math.min(...p.sizes.map((s) => Number(s.price || 0)))
      : Number(p.price || 0);

    const card = document.createElement("article");
    card.className = "menu-item";
    card.innerHTML = `
      <div class="item-image" style="background-image:url('${p.image || ""}')"></div>
      <div class="item-content">
        <h3>${p.name}</h3>
        <p>${p.description || ""}</p>
        <div class="item-footer">
          <div class="item-price">A partir de R$ ${formatPrice(basePrice)}</div>
          <button class="add-to-cart">Escolher</button>
        </div>
      </div>
    `;

    card.querySelector(".add-to-cart").onclick = () => openProductOptions(p);
    container.appendChild(card);
  });
}

// === MODAL DE PRODUTOS ===
let currentProduct = null;
function openProductOptions(product) {
  currentProduct = product;
  const modal = document.getElementById("productModal");
  const modalTitle = document.getElementById("modalTitle");
  const modalPrice = document.getElementById("modalPrice");
  const sizesContainer = document.getElementById("sizesContainer");
  const adicionaisContainer = document.getElementById("adicionaisContainer");
  const qty = document.getElementById("modalQty");

  modalTitle.textContent = product.name;
  modal.classList.remove("hidden");

  // --- tamanhos ---
  sizesContainer.innerHTML = "";
  const sizes = Array.isArray(product.sizes) && product.sizes.length
    ? product.sizes
    : [{ name: "Único", price: product.price || 0 }];
  sizes.forEach((s, i) => {
    sizesContainer.innerHTML += `
      <label><input type="radio" name="size" value="${s.name}" data-price="${s.price}" ${i === 0 ? "checked" : ""}> ${s.name} - R$ ${formatPrice(s.price)}</label>
    `;
  });

  // --- adicionais específicos ---
  adicionaisContainer.innerHTML = "";
  if (Array.isArray(product.adicionais) && product.adicionais.length) {
    product.adicionais.forEach((a) => {
      adicionaisContainer.innerHTML += `
        <label><input type="checkbox" name="adicional" data-id="${a.id}" data-price="${a.price}" data-name="${a.name}"> ${a.name} (+R$ ${formatPrice(a.price)})</label>
      `;
    });
  } else {
    adicionaisContainer.innerHTML = `<p class="muted">Nenhum adicional disponível.</p>`;
  }

  // --- caixa de observação ---
  const removeBox = document.createElement("input");
  removeBox.type = "text";
  removeBox.id = "removeInput";
  removeBox.placeholder = "Ex: sem cebola, sem azeitona...";
  removeBox.style =
    "width:100%;padding:8px;margin-top:8px;border:1px solid #ccc;border-radius:8px;";
  adicionaisContainer.appendChild(removeBox);

  // --- atualizar preço ---
  function updatePrice() {
    const size = modal.querySelector('input[name="size"]:checked');
    const sizePrice = Number(size?.dataset.price || 0);
    const adicionaisSelecionados = Array.from(
      modal.querySelectorAll('input[name="adicional"]:checked')
    ).map((a) => Number(a.dataset.price || 0));
    const extras = adicionaisSelecionados.reduce((s, n) => s + n, 0);
    modalPrice.textContent = formatPrice((sizePrice + extras) * Number(qty.value));
  }
  modal.addEventListener("change", updatePrice);
  qty.addEventListener("input", updatePrice);
  updatePrice();

  // --- submit ---
  const form = document.getElementById("modalForm");
  form.onsubmit = (e) => {
    e.preventDefault();
    const size = modal.querySelector('input[name="size"]:checked');
    const adicionaisSelecionados = Array.from(
      modal.querySelectorAll('input[name="adicional"]:checked')
    ).map((a) => ({
      id: a.dataset.id,
      name: a.dataset.name,
      price: Number(a.dataset.price),
    }));
    const remover = document.getElementById("removeInput")?.value || "";

    window.addToCart({
      id: product.id,
      name: product.name,
      image: product.image,
      basePrice: Number(size?.dataset.price || 0),
      size: size?.value || "",
      adicionais: adicionaisSelecionados,
      quantity: Number(qty.value),
      remover,
    });

    modal.classList.add("hidden");
  };
}

function closeProductModal() {
  document.getElementById("productModal").classList.add("hidden");
}

// === INICIALIZAÇÃO ===
document.addEventListener("DOMContentLoaded", async () => {
  cart = [];
  await saveCart(cart);
  updateCart();

  menuItems = await fetchProdutos();
  renderMenu(menuItems);

  // filtro de busca
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      const term = e.target.value.toLowerCase();
      const filtered = menuItems.filter((i) =>
        i.name.toLowerCase().includes(term)
      );
      renderMenu(filtered);
    });
  }

  // Inicializar contador do carrinho
  updateCartCounter();


  // Fechar carrinho ao clicar fora
 document.addEventListener('click', (e) => {
  const sidebar = document.querySelector('.cart-sidebar');
  const button = document.querySelector('.cart-button');
  const modal = document.querySelector('#productModal');


  if (
    sidebar?.contains(e.target) ||
    button?.contains(e.target) ||
    e.target.closest('.cart-backdrop') ||
    modal?.classList.contains('active')
  ) {
    return;
  }

  // Se o carrinho estiver aberto e o clique for fora → fecha
  if (sidebar && sidebar.classList.contains('active')) {
    toggleCart(false);
    console.log('🧱 Clique fora: fechando carrinho');
  }
});


  // Fechar carrinho ao pressionar ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      toggleCart(false);
    }
  });

  // Menu hamburguer mobile
  const menuHamburguer = document.querySelector('.menu-hamburguer');
  const nav = document.querySelector('.nav');
  if (menuHamburguer && nav) {
    menuHamburguer.addEventListener('click', () => {
      nav.classList.toggle('active');
      menuHamburguer.classList.toggle('open');
    }); 

    // Fechar menu ao clicar em um link
    nav.addEventListener('click', (e) => {
      if (e.target.classList.contains('nav-link')) {
        nav.classList.remove('active');
        menuHamburguer.classList.remove('open');
      }
    });
  }
});
