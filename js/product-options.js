// filepath: c:\wamp64\www\proyecto\js\product-options.js
(function () {
    const modal = document.getElementById('productModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalPrice = document.getElementById('modalPrice');
    const sizesContainer = document.getElementById('sizesContainer');
    const adicionaisContainer = document.getElementById('adicionaisContainer');
    const modalForm = document.getElementById('modalForm');
    const modalQty = document.getElementById('modalQty');

    let currentProduct = null;

    function formatPrice(v) {
        return Number(v).toFixed(2).replace('.', ',');
    }

    function updateModalPrice() {
        if (!currentProduct) return;
        // size price
        const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
        const sizePrice = Number(sizeRadio?.dataset?.price || 0);
        // adicionais
        const adicionaisChecked = Array.from(modalForm.querySelectorAll('input[name="adicional"]:checked'))
            .map(cb => Number(cb.dataset.price || 0));
        const extras = adicionaisChecked.reduce((s, n) => s + n, 0);
        const qty = Number(modalQty.value || 1);
        const total = (sizePrice + extras) * qty;
        modalPrice.textContent = formatPrice(total);
    }

    window.openProductOptions = function (product) {
        // product: { id, name, price (optional), sizes: [{name,price}] or ['P','M'], adicionais: [{id,name,price}, ...] }
        currentProduct = product || {};
        modalTitle.textContent = product.name || 'Produto';
        modalQty.value = 1;

        // montar tamanhos (radio)
        sizesContainer.innerHTML = '';
        const rawSizes = product.sizes && product.sizes.length ? product.sizes : (product.price ? [{ name: 'Único', price: product.price }] : [{ name: 'Único', price: 0 }]);
        rawSizes.forEach((s, i) => {
            // aceitar tanto string quanto objeto {name,price}
            const name = (typeof s === 'string') ? s : (s.name ?? 'Tamanho');
            const price = (typeof s === 'string') ? (Number(product.price || 0)) : Number(s.price || 0);
            const id = `size_${i}`;
            const label = document.createElement('label');
            label.className = 'option-item';
            label.innerHTML = `<input type="radio" name="size" value="${i}" data-price="${price}" ${i === 0 ? 'checked' : ''}> ${name} ${Number(price) ? `– R$ ${formatPrice(price)}` : ''}`;
            sizesContainer.appendChild(label);
        });

        // montar adicionais (checkbox)
        adicionaisContainer.innerHTML = '';
        const extras = product.adicionais && product.adicionais.length ? product.adicionais : [];
        if (extras.length === 0) {
            adicionaisContainer.innerHTML = '<div class="muted">Nenhum adicional disponível</div>';
        } else {
            extras.forEach((a) => {
                const price = Number(a.price || 0);
                const label = document.createElement('label');
                label.className = 'option-item';
                // value use id if exists, fallback to name
                const aid = a.id ?? a.name;
                label.innerHTML = `<input type="checkbox" name="adicional" value="${aid}" data-price="${price}" data-name="${(a.name||'') }"> ${a.name} ${price ? `(+R$ ${formatPrice(price)})` : ''}`;
                adicionaisContainer.appendChild(label);
            });
        }

        // atualizar preço inicial
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        // small delay to ensure inputs present
        setTimeout(updateModalPrice, 20);
    };

    window.closeProductModal = function () {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        currentProduct = null;
    };

    // atualizar preço quando muda seleção
    sizesContainer.addEventListener('change', updateModalPrice);
    adicionaisContainer.addEventListener('change', updateModalPrice);
    modalQty.addEventListener('input', updateModalPrice);

    modalForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentProduct) return;

        const sizeIndex = modalForm.querySelector('input[name="size"]:checked')?.value;
        const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
        const sizeObj = (sizeRadio) ? { index: Number(sizeIndex), price: Number(sizeRadio.dataset.price || 0) } : { price: Number(currentProduct.price || 0) };
        const sizeLabel = (() => {
            const s = currentProduct.sizes && currentProduct.sizes[sizeObj.index];
            return (typeof s === 'string') ? s : (s && s.name) || (sizeObj.index !== undefined ? `Tamanho ${sizeObj.index}` : null);
        })();

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
            basePrice: Number(sizeObj.price || 0),
            size: sizeLabel,
            adicionais: adicionaisChecked,
            quantity: qty
        };

        // se existir função global addToCart, use-a; senão dispare evento customizado
        if (typeof window.addToCart === 'function') {
            window.addToCart(payload);
        } else {
            document.dispatchEvent(new CustomEvent('productAdd', { detail: payload }));
        }

        closeProductModal();
    });

    // fechar com ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeProductModal();
    });
})();