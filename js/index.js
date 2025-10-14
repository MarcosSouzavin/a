
let cart = [];
let menuItems = [];
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
  const data = localStorage.getItem("cart");
  const parsed = data ? JSON.parse(data) : [];
  console.log("%cgetCart:", "color: #4caf50", parsed);
  return Array.isArray(parsed) ? parsed : [];
}


async function saveCart(c) {
  if (!Array.isArray(c) || c.length === 0) {
    console.warn("%csaveCart abortado → carrinho vazio, não será salvo", "color: #ff9800");
    return;
  }

  try {
    const json = JSON.stringify(c);
    localStorage.setItem("cart", json);
    console.log(`%csaveCart: ${c.length} item(s) salvo(s)`, "color: #2196f3");

    await fetch("API/cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: json,
    });
  } catch (e) {
    console.error("Erro ao salvar carrinho:", e);
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

      const adicionais = item.adicionais?.map(a => `+ ${a.name}`).join(", ") || "";

      li.innerHTML = `
        <div class="cart-line-left">
          <img src="${item.image || "img/default.png"}" style="width:70px;height:70px;border-radius:10px;margin-right:8px;object-fit:cover;">
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
}

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
  const sidebar = document.querySelector(".cart-sidebar");
  if (!sidebar) return;
  if (forceOpen === true) sidebar.classList.add("active");
  else if (forceOpen === false) sidebar.classList.remove("active");
  else sidebar.classList.toggle("active");
};

async function fetchProdutos() {
  try {
    const r = await fetch("API/produtos.php?" + Date.now());
    const j = await r.json();
    return j.produtos || [];
  } catch {
    console.error("Falha ao carregar produtos.");
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

  sizesContainer.innerHTML = "";
  const sizes = product.sizes?.length
    ? product.sizes
    : [{ name: "Único", price: product.price || 0 }];
  sizes.forEach((s, i) => {
    sizesContainer.innerHTML += `
      <label><input type="radio" name="size" value="${s.name}" data-price="${s.price}" ${i === 0 ? "checked" : ""}> ${s.name} - R$ ${formatPrice(s.price)}</label>
    `;
  });

  adicionaisContainer.innerHTML = "";
  if (Array.isArray(product.adicionais)) {
    product.adicionais.forEach((a) => {
      adicionaisContainer.innerHTML += `
        <label><input type="checkbox" name="adicional" data-id="${a.id}" data-price="${a.price}" data-name="${a.name}"> ${a.name} (+R$ ${formatPrice(a.price)})</label>
      `;
    });
  }

  function updatePrice() {
    const size = modal.querySelector('input[name="size"]:checked');
    const sizePrice = Number(size?.dataset.price || 0);
    const adicionais = Array.from(modal.querySelectorAll('input[name="adicional"]:checked')).map(a => Number(a.dataset.price || 0));
    const extras = adicionais.reduce((s, n) => s + n, 0);
    modalPrice.textContent = formatPrice((sizePrice + extras) * Number(qty.value));
  }

  modal.addEventListener("change", updatePrice);
  qty.addEventListener("input", updatePrice);
  updatePrice();

  const form = document.getElementById("modalForm");
  form.onsubmit = (e) => {
    e.preventDefault();
    const size = modal.querySelector('input[name="size"]:checked');
    const adicionais = Array.from(modal.querySelectorAll('input[name="adicional"]:checked')).map((a) => ({
      id: a.dataset.id,
      name: a.dataset.name,
      price: Number(a.dataset.price),
    }));

    window.addToCart({
      id: product.id,
      name: product.name,
      image: product.image,
      basePrice: Number(size?.dataset.price || 0),
      size: size?.value || "",
      adicionais,
      quantity: Number(qty.value),
    });

    modal.classList.add("hidden");
  };
}

function closeProductModal() {
  document.getElementById("productModal").classList.add("hidden");
}

document.addEventListener("DOMContentLoaded", async () => {
  cart = await getCart();
  updateCart();

  menuItems = await fetchProdutos();
  renderMenu(menuItems);

  document.getElementById("searchInput").addEventListener("input", (e) => {
    const term = e.target.value.toLowerCase();
    const filtered = menuItems.filter((i) =>
      i.name.toLowerCase().includes(term)
    );
    renderMenu(filtered);
  });
});
