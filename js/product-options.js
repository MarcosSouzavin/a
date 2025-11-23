(function () {
    const modal = document.getElementById('productModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalPrice = document.getElementById('modalPrice');
    const sizesContainer = document.getElementById('sizesContainer');
    const adicionaisContainer = document.getElementById('adicionaisContainer');
    const modalForm = document.getElementById('modalForm');
    const modalQty = document.getElementById('modalQty');

    const modalDescription = document.getElementById('modalDescription');
    const modalIngredients = document.getElementById('modalIngredients');

    let currentProduct = null;

    function formatPrice(v) {
        return Number(v).toFixed(2).replace('.', ',');
    }

    function updateModalPrice() {
        if (!currentProduct) return;

        const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
        const sizePrice = Number(sizeRadio?.dataset?.price || 0);

        const adicionaisChecked = Array.from(
            modalForm.querySelectorAll('input[name="adicional"]:checked')
        ).map(cb => Number(cb.dataset.price || 0));

        const extras = adicionaisChecked.reduce((s, n) => s + n, 0);
        const qty = Number(modalQty.value || 1);
        const total = (sizePrice + extras) * qty;

        modalPrice.textContent = formatPrice(total);
    }

    // =====================================================
    //     ABRIR O MODAL COM O PRODUTO
    // =====================================================
    window.openProductOptions = function (product) {
        currentProduct = product || {};

        modalTitle.textContent = product.name || 'Produto';

        // Descrição removida (não usa mais)
        modalDescription.textContent = "";

        // =====================================================
        //      DESCRIÇÃO = INGREDIENTES (como lista)
        // =====================================================
        let ingr = product.descricao || ""; // <<<<< AQUI DO JEITO QUE VOCÊ MANDOU

        if (!ingr || ingr.trim() === "" || ingr.length < 2) {
            modalIngredients.innerHTML = `
                <div style="opacity:0.6;font-size:14px;margin-bottom:10px;">
                    (Sem descrição)
                </div>
            `;
        } else {
            const lista = ingr
                .split(',')
                .map(i => `<li>${i.trim()}</li>`)
                .join('');

            modalIngredients.innerHTML = `
                <strong>Ingredientes:</strong>
                <ul class="ing-list">${lista}</ul>
            `;
        }

        modalQty.value = 1;

        // =====================================================
        //              MONTAR TAMANHOS
        // =====================================================
        sizesContainer.innerHTML = '';
        const rawSizes = product.sizes && product.sizes.length
            ? product.sizes
            : (product.price ? [{ name: 'Único', price: product.price }] : [{ name: 'Único', price: 0 }]);

        rawSizes.forEach((s, i) => {
            const name = typeof s === 'string' ? s : (s.name ?? 'Tamanho');
            const price = typeof s === 'string' ? Number(product.price || 0) : Number(s.price || 0);

            const label = document.createElement('label');
            label.className = 'option-item';
            label.innerHTML = `
                <input type="radio" name="size" value="${i}" data-price="${price}" ${i === 0 ? 'checked' : ''}>
                ${name} ${price ? `– R$ ${formatPrice(price)}` : ''}
            `;
            sizesContainer.appendChild(label);
        });

        // =====================================================
        //              MONTAR ADICIONAIS
        // =====================================================
        adicionaisContainer.innerHTML = '';
        const extras = product.adicionais && product.adicionais.length ? product.adicionais : [];
        const drinks = product.refrigerantes && product.refrigerantes.length ? product.refrigerantes : [];

        if (extras.length === 0 && drinks.length === 0) {
            adicionaisContainer.innerHTML = '<div class="muted">Nenhum adicional disponível</div>';
        } else {

            // Adicionais normais
            extras.forEach(a => {
                const price = Number(a.price || 0);
                const label = document.createElement('label');
                label.className = 'option-item';

                const aid = a.id ?? a.name;

                label.innerHTML = `
                    <input type="checkbox" name="adicional" value="${aid}" data-price="${price}" data-name="${a.name}">
                    ${a.name} ${price ? `(+R$ ${formatPrice(price)})` : ''}
                `;
                adicionaisContainer.appendChild(label);
            });

            // Drinks (adicionados como itens separados no carrinho)
            if (drinks.length > 0) {
                const drinksTitle = document.createElement('div');
                drinksTitle.className = 'muted';
                drinksTitle.style.marginTop = '10px';
                drinksTitle.textContent = 'Refrigerantes:';
                adicionaisContainer.appendChild(drinksTitle);

                drinks.forEach((d, idx) => {
                    const price = Number(d.price || 0);

                    let imgHtml = d.image
                        ? `<img src="${d.image}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:4px;">`
                        : '';

                    const label = document.createElement('label');
                    label.className = 'option-item';
                    label.style.display = 'flex';
                    label.style.alignItems = 'center';

                    label.innerHTML = `
                        ${imgHtml}
                        <input type="checkbox" name="adicional" value="drink_${idx}" data-price="${price}" data-name="${d.name}">
                        ${d.name} ${price ? `(+R$ ${formatPrice(price)})` : ''}
                    `;

                    adicionaisContainer.appendChild(label);
                });
            }
        }

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        setTimeout(updateModalPrice, 20);
    };

    // =====================================================
    //        FECHAR O MODAL
    // =====================================================
    window.closeProductModal = function () {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        currentProduct = null;
    };

    // Eventos
    sizesContainer.addEventListener('change', updateModalPrice);
    adicionaisContainer.addEventListener('change', updateModalPrice);
    modalQty.addEventListener('input', updateModalPrice);

    // =====================================================
    //         ADICIONAR AO CARRINHO
    // =====================================================
    modalForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentProduct) return;

        const sizeRadio = modalForm.querySelector('input[name="size"]:checked');
        const sizeObj = {
            index: Number(sizeRadio?.value || 0),
            price: Number(sizeRadio?.dataset?.price || 0)
        };

        const sizeLabel = (() => {
            const s = currentProduct.sizes && currentProduct.sizes[sizeObj.index];
            return typeof s === 'string' ? s : (s?.name || 'Único');
        })();

        const adicionaisChecked = Array.from(
            modalForm.querySelectorAll('input[name="adicional"]:checked')
        ).map(cb => ({
            id: cb.value,
            price: Number(cb.dataset.price || 0),
            name: cb.dataset.name || '',
            isDrink: cb.value.startsWith('drink_'),
            image: cb.closest('label')?.querySelector('img')?.src || ''
        }));

        const qty = Number(modalQty.value) || 1;

        // Se tem drinks → virar itens separados
        if (adicionaisChecked.some(a => a.isDrink)) {

            adicionaisChecked.forEach(a => {
                if (a.isDrink) {
                    const drinkPayload = {
                        id: a.id,
                        name: a.name,
                        basePrice: a.price,
                        size: null,
                        adicionais: [],
                        quantity: 1,
                        image: a.image
                    };

                    window.addToCart
                        ? addToCart(drinkPayload)
                        : document.dispatchEvent(new CustomEvent('productAdd', { detail: drinkPayload }));
                }
            });

            const onlyAdicionais = adicionaisChecked.filter(a => !a.isDrink);

            const payload = {
                id: currentProduct.id,
                name: currentProduct.name,
                basePrice: sizeObj.price,
                size: sizeLabel,
                adicionais: onlyAdicionais,
                quantity: qty
            };

            window.addToCart
                ? addToCart(payload)
                : document.dispatchEvent(new CustomEvent('productAdd', { detail: payload }));

        } else {

            const payload = {
                id: currentProduct.id,
                name: currentProduct.name,
                basePrice: sizeObj.price,
                size: sizeLabel,
                adicionais: adicionaisChecked,
                quantity: qty
            };

            window.addToCart
                ? addToCart(payload)
                : document.dispatchEvent(new CustomEvent('productAdd', { detail: payload }));
        }

        closeProductModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeProductModal();
        }
    });

})();
