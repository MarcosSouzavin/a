const ADMIN_PASSWORD = 'g';

function isLoggedIn() {
    return sessionStorage.getItem('admin_logged') === '1';
}

function showLogin() {
    document.body.innerHTML = `
        <div class="admin-login-bg">
            <div class="admin-login-box">
                <h2>Login Admin</h2>
                <input type="password" id="adminPass" placeholder="Senha de administrador">
                <button id="loginBtn">Entrar</button>
                <div id="loginMsg" class="admin-login-msg"></div>
            </div>
        </div>
    `;
    document.getElementById('loginBtn').onclick = () => {
        const pass = document.getElementById('adminPass').value;
        if (pass === ADMIN_PASSWORD) {
            sessionStorage.setItem('admin_logged', '1');
            location.reload();
        } else {
            document.getElementById('loginMsg').textContent = 'Senha incorreta!';
        }
    };
}

let menuItems = [];
let drinks = [];
let sucos = [];
let adicionais = [];

async function fetchProdutosJson() {
    // Try API endpoint first; if it returns empty or fails, fall back to local sys/produtos.json
    try {
        const res = await fetch('API/produtos.php?' + Date.now());
        if (res.ok) {
            const data = await res.json();
            // If data has produtos array or is non-empty, return it
            if (data) {
                if (Array.isArray(data) && data.length > 0) return { produtos: data, adicionais: [] };
                if (data.produtos && Array.isArray(data.produtos) && data.produtos.length >= 0) return data;
            }
        }
    } catch (e) {
        console.log('fetchProdutosJson: API call failed', e);
    }
    // Fallback: try loading sys/produtos.json directly
    try {
        const res2 = await fetch('sys/produtos.json?' + Date.now());
        if (res2.ok) {
            const data2 = await res2.json();
            if (Array.isArray(data2)) return { produtos: data2, adicionais: [] };
            if (data2 && data2.produtos) return data2;
        }
    } catch (e) {
        console.log('fetchProdutosJson: fallback fetch failed', e);
    }
    return { produtos: [], adicionais: [] };
}

function mapToSimpleDrinks(products) {
    return products.filter(p => String(p.id).startsWith('drink_')).map(p => ({
        name: p.name,
        image: p.image || '',
        price: p.sizes && p.sizes[0] ? p.sizes[0].price || 0 : 0
    }));
}

function mapToSimpleSucos(products) {
    return products.filter(p => String(p.id).startsWith('suco_')).map(p => ({
        name: p.name,
        image: p.image || '',
        descricao: p.descricao || '',
        price: p.sizes && p.sizes[0] ? p.sizes[0].price || 0 : 0
    }));
}

async function saveMenu() {
    const drinkProducts = Array.isArray(drinks) ? drinks.map((d, idx) => ({
        id: `drink_${idx + 1}`,
        name: d.name,
        image: d.image || '',
        descricao: '',
        sizes: [{ name: 'Único', price: d.price }],
        adicionais: []
    })) : [];

    const sucoProducts = Array.isArray(sucos) ? sucos.map((s, idx) => ({
        id: `suco_${idx + 1}`,
        name: s.name,
        image: s.image || '',
        descricao: s.descricao || '',
        sizes: [{ name: 'Único', price: s.price }],
        adicionais: []
    })) : [];

    const regularProducts = menuItems.filter(p => !String(p.id).startsWith('drink_') && !String(p.id).startsWith('suco_'));

    const allProducts = [...regularProducts, ...drinkProducts, ...sucoProducts];

    const payload = {
        produtos: allProducts,
        adicionais: adicionais
    };

    console.log('saveMenu payload:', payload);

    try {
        const response = await fetch('API/produtos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (!response.ok) {
            console.error('Erro ao salvar produtos:', response.statusText);
            alert('Erro ao salvar produtos no servidor.');
            return;
        }
        // Recarrega produtos do backend após salvar
        const data = await fetchProdutosJson();
        menuItems = data.produtos || [];
        adicionais = data.adicionais || [];
        drinks = mapToSimpleDrinks(menuItems);
        sucos = mapToSimpleSucos(menuItems);
        sessionStorage.setItem('menuData', JSON.stringify(data));
        renderTab(currentTab);
    } catch (error) {
        console.error('Erro na requisição saveMenu:', error);
        alert('Erro na comunicação com o servidor.');
    }
}

function addAdminLog(action, details) {
    const logs = JSON.parse(localStorage.getItem('adminLogs') || '[]');
    logs.push({
        time: new Date().toLocaleString(),
        action,
        details
    });
    localStorage.setItem('adminLogs', JSON.stringify(logs));
}

function montarAbaPagamento() {
    const container = document.getElementById('adminTabContent');
    container.innerHTML = `
        <h2>Configurações de Pagamento</h2>
        <p>Aqui você pode configurar as opções de pagamento, como chaves do Mercado Pago.</p>
        <label>Access Token do Mercado Pago: <input type="text" id="mpAccessToken" placeholder="APP_USR-..."></label>
        <button id="saveMpSettings">Salvar</button>
        <div id="mpMsg"></div>
    `;
    // Load existing token if any
    const savedToken = localStorage.getItem('mpAccessToken');
    if (savedToken) {
        document.getElementById('mpAccessToken').value = savedToken;
    }
    document.getElementById('saveMpSettings').onclick = () => {
        const token = document.getElementById('mpAccessToken').value.trim();
        localStorage.setItem('mpAccessToken', token);
        document.getElementById('mpMsg').innerHTML = '<span style="color:green;">Token salvo!</span>';
        addAdminLog('Salvar token Mercado Pago', { token: token ? '***' : '' });
    };
}

let currentTab = 'produtos';
let productSearchQuery = '';

// global fallback handler so inline oninput can always call the filter
window.__adminSearchInput = function(val) {
    productSearchQuery = val;
    console.log('admin search input (global):', val);
    const c = document.getElementById('adminTabContent');
    if (c) filterAdminItems(c, val);
};

function normalizeForSearch(str) {
    if (!str) return '';
    // remove accents/diacritics and lowercase for more robust matching
    return String(str).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

let adminSearchDelegationInstalled = false;

function filterAdminItems(container) {
    // prefer reading the actual search input text from the DOM (prodSearch or editSearch)
    let rawQuery = productSearchQuery || '';
    try {
        const inputEl = container.querySelector('#prodSearch') || container.querySelector('#editSearch') || container.querySelector('input[type="search"]');
        if (inputEl && typeof inputEl.value === 'string') rawQuery = inputEl.value;
    } catch (e) {
        // ignore
    }
    const q = normalizeForSearch(rawQuery || '');
    const items = Array.from(container.querySelectorAll('.admin-item'));
    let visible = 0;
    items.forEach(item => {
        // use the whole visible text of the item for matching (more robust across layouts)
        const allText = normalizeForSearch(item.textContent || item.innerText || '');
        const match = !q || allText.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    // show or hide no-results message
    let noEl = container.querySelector('.no-results-msg');
    if (!noEl) {
        noEl = document.createElement('div');
        noEl.className = 'no-results-msg';
        noEl.innerHTML = '<em>Nenhum produto encontrado para a pesquisa.</em>';
        noEl.style.display = 'none';
        container.appendChild(noEl);
    }
    noEl.style.display = visible === 0 ? '' : 'none';
    console.log('filterAdminItems', { query: rawQuery, totalItems: items.length, visible });
    return visible;
}

document.addEventListener('DOMContentLoaded', async () => {
    if (!isLoggedIn()) {
        showLogin();
    } else {
        let data;
        if (sessionStorage.getItem('menuData')) {
            data = JSON.parse(sessionStorage.getItem('menuData'));
        } else {
            data = await fetchProdutosJson();
            sessionStorage.setItem('menuData', JSON.stringify(data));
        }
        menuItems = data.produtos || [];
        // Alteração: Atribuir IDs numéricos se null ou inválido para evitar problemas com IDs NaN
        let idCounter = 1;
        menuItems.forEach(item => {
            if (!item.id || (typeof item.id !== 'number' && typeof item.id !== 'string')) {
                item.id = idCounter++;
            } else if (typeof item.id === 'number' && isNaN(item.id)) {
                item.id = idCounter++;
            }
        });
        adicionais = data.adicionais || [];
        drinks = mapToSimpleDrinks(menuItems);
        sucos = mapToSimpleSucos(menuItems);
        if (adicionais.length === 0) {
            adicionais = [
                { name: 'Queijo', price: 2 },
                { name: 'Bacon', price: 3 },
                { name: 'Cebola', price: 1 },
                { name: 'Azeitona', price: 1.5 }
            ];
            await saveMenu();
        }
        setupAdminTabs();

        // Setup payment tab button click handler here to ensure DOM is ready
        const paymentTabBtn = document.getElementById('paymentTabBtn');
        if (paymentTabBtn) {
            paymentTabBtn.onclick = () => {
                const adminTabContent = document.getElementById('adminTabContent');
                adminTabContent.innerHTML = '';
                montarAbaPagamento();
            };
        }
    }
});

function setupAdminTabs() {
    const tabContent = document.getElementById('adminTabContent');
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.onclick = () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTab = btn.dataset.tab;
            renderTab(currentTab);
        };
    });
    tabBtns[0].classList.add('active');
    renderTab(currentTab);

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.onclick = () => {
            sessionStorage.removeItem('admin_logged');
            location.reload();
        };
    }

    // install delegated input listener once to ensure filtering always triggers
    if (!adminSearchDelegationInstalled) {
        document.addEventListener('input', function(e) {
            const target = e.target;
            if (!target) return;
            if (target.id === 'prodSearch' || target.id === 'editSearch') {
                productSearchQuery = target.value || '';
                const tabContent = document.getElementById('adminTabContent');
                if (tabContent) filterAdminItems(tabContent);
            }
        });
        adminSearchDelegationInstalled = true;
    }
}

async function renderTab(tab) {
    const tabContent = document.getElementById('adminTabContent');
    if (tab === 'produtos') {
        tabContent.innerHTML = '<h2>Produtos Cadastrados</h2>';
        tabContent.innerHTML += `
            <label style="display:block;margin:10px 0;">Pesquisar: <input type="search" id="prodSearch" placeholder="Digite nome do produto" style="width:100%;padding:6px;" value="${productSearchQuery}"></label>
        `;
        const prodSearchEl = tabContent.querySelector('#prodSearch');
        prodSearchEl.addEventListener('input', function() {
            productSearchQuery = this.value;
            console.log('admin search input:', productSearchQuery);
            filterAdminItems(tabContent);
        });
        if (!Array.isArray(menuItems) || menuItems.length === 0) {
            tabContent.innerHTML += '<em>Nenhum produto cadastrado.</em>';
            return;
        }
        // Alteração: Mostrar apenas pizzas (produtos regulares), excluindo bebidas e sucos, ajustando exibição de preços para itens com um ou múltiplos tamanhos
        const regularProducts = menuItems.filter(p => !String(p.id).startsWith('drink_') && !String(p.id).startsWith('suco_'));
        regularProducts.forEach(item => {
            let priceDisplay = '';
            if (item.sizes && item.sizes.length > 0) {
                if (item.sizes.length === 1) {
                    priceDisplay = `Preço: R$ ${item.sizes[0].price.toFixed(2)}`;
                } else {
                    priceDisplay = item.sizes.map(s => `${s.name}: R$ ${s.price.toFixed(2)}`).join(' | ');
                }
            }
            const adicionaisDisplay = item.adicionais && item.adicionais.length > 0 ? item.adicionais.map(a => a.name).join(', ') : 'Nenhum';
            tabContent.innerHTML += `
                <div class="admin-item">
                    <h3>${item.name}</h3>
                    <img src="${item.image}" alt="${item.name}" style="max-width:80px;max-height:80px;">
                    <div>Descrição: ${item.descricao ? item.descricao : '<em>Sem descrição</em>'}</div>
                    <div>${priceDisplay}</div>
                    <div>Adicionais: ${adicionaisDisplay}</div>
                </div>
            `;
        });
        // apply DOM filter (keeps focus while typing)
        filterAdminItems(tabContent);
    } else if (tab === 'adicionar') {
        tabContent.innerHTML = `
            <form id="addProductForm" class="admin-form">
                <h2>Adicionar Produto</h2>
                <label>Nome: <input type="text" id="addName" required></label>
                <label>Imagem (URL): <input type="text" id="addImage" required></label>
                <label>Descrição: <textarea id="addDesc" rows="2" style="width:100%;resize:vertical;" required></textarea></label>
                <label>Preço Pequena: <input type="number" id="addP" required></label>
                <label>Preço Média: <input type="number" id="addM" required></label>
                <label>Preço Grande: <input type="number" id="addG" required></label>
                <button type="submit">Adicionar</button>
            </form>
            <div id="addMsg"></div>
        `;
        document.getElementById('addProductForm').onsubmit = async function(e) {
            e.preventDefault();
            const name = document.getElementById('addName').value.trim();
            const image = document.getElementById('addImage').value.trim();
            const descricao = document.getElementById('addDesc').value.trim();
            const p = Number(document.getElementById('addP').value);
            const m = Number(document.getElementById('addM').value);
            const g = Number(document.getElementById('addG').value);
            if (menuItems.some(i => i.name.toLowerCase() === name.toLowerCase())) {
                document.getElementById('addMsg').innerHTML = '<span style="color:red;">Produto já existe!</span>';
                return;
            }
            // Alteração: Calcular novo ID corretamente, filtrando apenas IDs numéricos válidos para evitar NaN
            const existingIds = menuItems.map(i => i.id).filter(id => typeof id === 'number' && !isNaN(id));
            const maxId = existingIds.length ? Math.max(...existingIds) : 0;
            const newId = maxId + 1;
            addAdminLog('Adicionar produto', { name, image, descricao, p, m, g });
            menuItems.push({
                id: newId,
                name,
                image,
                descricao,
                sizes: [
                    { name: 'Pequena', price: p },
                    { name: 'Média', price: m },
                    { name: 'Grande', price: g }
                ]
            });
            await saveMenu();
            document.getElementById('addMsg').innerHTML = '<span style="color:green;">Produto adicionado!</span>';
            this.reset();
        };
    } else if (tab === 'editar') {
        tabContent.innerHTML = '<h2>Editar Produtos</h2>';
        if (!Array.isArray(menuItems) || menuItems.length === 0) {
            tabContent.innerHTML += '<em>Nenhum produto cadastrado.</em>';
            return;
        }
        // Alteração: Mostrar apenas pizzas (produtos regulares) para edição, excluindo bebidas e sucos
        tabContent.innerHTML += `
            <label style="display:block;margin:10px 0;">Pesquisar: <input type="search" id="editSearch" placeholder="Pesquisar produto para editar" style="width:100%;padding:6px;" value="${productSearchQuery}"></label>
        `;
        const editSearchEl = tabContent.querySelector('#editSearch');
        editSearchEl.addEventListener('input', function() {
            productSearchQuery = this.value;
            console.log('admin edit search input:', productSearchQuery);
            filterAdminItems(tabContent);
        });

        const regularProducts = menuItems.filter(p => !String(p.id).startsWith('drink_') && !String(p.id).startsWith('suco_'));
        regularProducts.forEach((item, idx) => {
            let sizeLabels = '';
            item.sizes.forEach((size, sidx) => {
                sizeLabels += `<label>${size.name}: <input type="number" value="${size.price}" class="price-input" data-size="${sidx}"></label>`;
            });
            let adicionaisCheckboxes = '<div>Adicionais:</div>';
            adicionais.forEach((ad, adidx) => {
                const checked = item.adicionais && item.adicionais.some(a => a.name === ad.name) ? 'checked' : '';
                adicionaisCheckboxes += `<label><input type="checkbox" class="adicional-checkbox" data-adname="${ad.name}" ${checked}> ${ad.name} (R$ ${ad.price.toFixed(2)})</label>`;
            });
            tabContent.innerHTML += `
                <div class="admin-item" data-idx="${idx}">
                    <h3>${item.name}</h3>
                    <img src="${item.image}" alt="${item.name}" style="max-width:80px;max-height:80px;">

                    <label>Nome: 
                        <input type="text" value="${item.name}" class="name-input">
                    </label>

                    <label>Imagem: 
                        <input type="text" value="${item.image}" class="img-input">
                    </label>

                    <label>Descrição: 
                        <textarea class="desc-input" rows="2" style="width:100%;resize:vertical;">${item.descricao ? item.descricao : ''}</textarea>
                    </label>

                    ${sizeLabels}
                    ${adicionaisCheckboxes}
                    <button class="save-btn">Salvar</button>
                    <button class="delete-btn">Excluir</button>
                    <div class="editMsg"></div>
                </div>
            `;
        });
        // apply DOM filter (keeps focus while typing)
        filterAdminItems(tabContent);
        // Adicionar listeners de edição/exclusão
        setTimeout(() => {
            document.querySelectorAll('.admin-item').forEach((div, idx) => {
                const nameInput = div.querySelector('.name-input');
                const imgInput = div.querySelector('.img-input');
                const descInput = div.querySelector('.desc-input');
                const priceInputs = div.querySelectorAll('.price-input');
                const saveBtn = div.querySelector('.save-btn');
                const deleteBtn = div.querySelector('.delete-btn');
                const editMsg = div.querySelector('.editMsg');
                saveBtn.onclick = async function() {
                    // Atualizar o índice correto no array menuItems, considerando filtro regularProducts
                    const productId = regularProducts[idx].id;
                    const menuIdx = menuItems.findIndex(p => p.id === productId);
                    if (menuIdx === -1) {
                        alert('Produto não encontrado para salvar.');
                        return;
                    }
                    menuItems[menuIdx].name = nameInput.value.trim();
                    menuItems[menuIdx].image = imgInput.value.trim();
                    menuItems[menuIdx].descricao = descInput.value;
                    priceInputs.forEach(input => {
                        const sizeIdx = input.dataset.size;
                        menuItems[menuIdx].sizes[sizeIdx].price = Number(input.value);
                    });
                    const adicionalCheckboxes = div.querySelectorAll('.adicional-checkbox');
                    const selectedAdicionais = Array.from(adicionalCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => adicionais.find(a => a.name === cb.dataset.adname));
                    menuItems[menuIdx].adicionais = selectedAdicionais;
                    await saveMenu();
                    addAdminLog('Editar produto', { 
                        id: menuItems[menuIdx].id, 
                        name: menuItems[menuIdx].name,
                        image: imgInput.value, 
                        descricao: descInput.value, 
                        prices: Array.from(priceInputs).map(i => i.value), 
                        adicionais: selectedAdicionais.map(a => a.name) 
                    });
                    editMsg.innerHTML = '<span style="color:green;">Produto salvo!</span>';
                    renderTab('editar');
                };
                deleteBtn.onclick = async function() {
                    if (confirm('Tem certeza que deseja excluir este produto?')) {
                        const productId = regularProducts[idx].id;
                        const productName = regularProducts[idx].name;
                        const menuIdx = menuItems.findIndex(p => p.id === productId);
                        if (menuIdx !== -1) {
                            addAdminLog('Excluir produto', { id: productId, name: productName });
                            menuItems.splice(menuIdx, 1);
                            await saveMenu();
                            renderTab('editar'); // Re-render the edit tab to reflect the deletion
                        } else {
                            alert('Produto não encontrado para exclusão.');
                        }
                    }
                };
            });
        }, 100);
    } else if (tab === 'adicionais') {
        tabContent.innerHTML = '<h2>Adicionais</h2>';
        tabContent.innerHTML += `
            <form id="addAdicionalForm" class="admin-form">
                <label>Nome: <input type="text" id="addAdicionalName" required></label>
                <label>Preço: <input type="number" id="addAdicionalPrice" required></label>
                <button type="submit">Adicionar</button>
            </form>
            <div id="adicionaisList"></div>
        `;
        function renderAdicionais() {
            const list = document.getElementById('adicionaisList');
            list.innerHTML = '';
            adicionais.forEach((ad, idx) => {
                const div = document.createElement('div');
                div.className = 'admin-item';
                div.innerHTML = `
                    <strong>${ad.name}</strong> - R$ ${ad.price.toFixed(2)}
                    <button class="delete-adicional" data-idx="${idx}">Excluir</button>
                `;
                list.appendChild(div);
            });
            document.querySelectorAll('.delete-adicional').forEach(btn => {
                btn.onclick = async function() {
                    const idx = this.dataset.idx;
                    addAdminLog('Excluir adicional', adicionais[idx]);
                    adicionais.splice(idx, 1);
                    await saveMenu();
                    renderAdicionais();
                };
            });
        }
        renderAdicionais();
        document.getElementById('addAdicionalForm').onsubmit = async function(e) {
            e.preventDefault();
            const name = document.getElementById('addAdicionalName').value.trim();
            const price = Number(document.getElementById('addAdicionalPrice').value);
            if (!name) return;
            adicionais.push({ name, price });
            addAdminLog('Adicionar adicional', { name, price });
            await saveMenu();
            renderAdicionais();
            this.reset();
        };
    } else if (tab === 'refrigerantes') {
        tabContent.innerHTML = '<h2>Refrigerantes</h2>';
        tabContent.innerHTML += `
            <form id="addDrinkForm" class="admin-form">
                <label>Nome: <input type="text" id="addDrinkName" required></label>
                <label>Imagem (URL): <input type="text" id="addDrinkImage" placeholder="Link ou diretório"></label>
                <label>Preço: <input type="number" id="addDrinkPrice" required></label>
                <button type="submit">Adicionar</button>
            </form>
            <div id="drinksList"></div>
        `;
        function renderDrinks() {
            const list = document.getElementById('drinksList');
            list.innerHTML = '';
            drinks.forEach((drink, idx) => {
                const div = document.createElement('div');
                div.className = 'admin-item';
                const imgUrl = typeof drink.image === 'string' ? drink.image : '';
                div.innerHTML = `
                    ${imgUrl ? `<img src="${imgUrl}" alt="${drink.name}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:4px;vertical-align:middle;">` : ''}
                    <label>Nome: <input type="text" value="${drink.name}" class="name-input"></label>
                    <label>Imagem: <input type="text" value="${drink.image || ''}" class="img-input"></label>
                    <label>Preço: <input type="number" value="${drink.price}" class="price-input"></label>
                    <button class="save-drink" data-idx="${idx}">Salvar</button>
                    <button class="delete-drink" data-idx="${idx}">Excluir</button>
                    <div class="drinkMsg"></div>
                `;
                list.appendChild(div);
            });
            document.querySelectorAll('.save-drink').forEach(btn => {
                btn.onclick = async function() {
                    const idx = this.dataset.idx;
                    const div = this.parentElement;
                    const nameInput = div.querySelector('.name-input');
                    const imgInput = div.querySelector('.img-input');
                    const priceInput = div.querySelector('.price-input');
                    const msgDiv = div.querySelector('.drinkMsg');
                    drinks[idx].name = nameInput.value.trim();
                    drinks[idx].image = imgInput.value.trim();
                    drinks[idx].price = Number(priceInput.value);
                    await saveMenu();
                    addAdminLog('Editar refrigerante', { idx, name: drinks[idx].name, image: drinks[idx].image, price: drinks[idx].price });
                    msgDiv.innerHTML = '<span style="color:green;">Refrigerante salvo!</span>';
                    renderDrinks();
                };
            });
            document.querySelectorAll('.delete-drink').forEach(btn => {
                btn.onclick = async function() {
                    const idx = this.dataset.idx;
                    addAdminLog('Excluir refrigerante', drinks[idx]);
                    drinks.splice(idx, 1);
                    await saveMenu();
                    renderDrinks();
                };
            });
        }
        renderDrinks();
        document.getElementById('addDrinkForm').onsubmit = async function(e) {
            e.preventDefault();
            const name = document.getElementById('addDrinkName').value.trim();
            const image = document.getElementById('addDrinkImage').value.trim();
            const price = Number(document.getElementById('addDrinkPrice').value);
            if (!name) return;
            drinks.push({ name, price, image });
            addAdminLog('Adicionar refrigerante', { name, price, image });
            await saveMenu();
            renderDrinks();
            this.reset();
        };
    } else if (tab === 'sucos') {
        tabContent.innerHTML = '<h2>Sucos</h2>';
        tabContent.innerHTML += `
            <form id="addSucoForm" class="admin-form">
                <label>Nome: <input type="text" id="addSucoName" required></label>
                <label>Imagem (URL): <input type="text" id="addSucoImage" placeholder="Link ou diretório"></label>
                <label>Descrição: <textarea id="addSucoDesc" rows="2" style="width:100%;resize:vertical;" placeholder="Ex: sem gelo, sem açúcar"></textarea></label>
                <label>Preço: <input type="number" id="addSucoPrice" required></label>
                <button type="submit">Adicionar</button>
            </form>
            <div id="sucosList"></div>
        `;
        function renderSucos() {
            const list = document.getElementById('sucosList');
            list.innerHTML = '';
            sucos.forEach((suco, idx) => {
                const div = document.createElement('div');
                div.className = 'admin-item';
                const imgUrl = typeof suco.image === 'string' ? suco.image : '';
                div.innerHTML = `
                    ${imgUrl ? `<img src="${imgUrl}" alt="${suco.name}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:4px;vertical-align:middle;">` : ''}
                    <label>Nome: <input type="text" value="${suco.name}" class="name-input"></label>
                    <label>Imagem: <input type="text" value="${suco.image || ''}" class="img-input"></label>
                    <label>Descrição: <textarea class="desc-input" rows="2" style="width:100%;resize:vertical;">${suco.descricao ? suco.descricao : ''}</textarea></label>
                    <label>Preço: <input type="number" value="${suco.price}" class="price-input"></label>
                    <button class="save-suco" data-idx="${idx}">Salvar</button>
                    <button class="delete-suco" data-idx="${idx}">Excluir</button>
                    <div class="sucoMsg"></div>
                `;
                list.appendChild(div);
            });
            document.querySelectorAll('.save-suco').forEach(btn => {
                btn.onclick = async function() {
                    const idx = this.dataset.idx;
                    const div = this.parentElement;
                    const nameInput = div.querySelector('.name-input');
                    const imgInput = div.querySelector('.img-input');
                    const descInput = div.querySelector('.desc-input');
                    const priceInput = div.querySelector('.price-input');
                    const msgDiv = div.querySelector('.sucoMsg');
                    sucos[idx].name = nameInput.value.trim();
                    sucos[idx].image = imgInput.value.trim();
                    sucos[idx].descricao = descInput.value.trim();
                    sucos[idx].price = Number(priceInput.value);
                    await saveMenu();
                    addAdminLog('Editar suco', { idx, name: sucos[idx].name, image: sucos[idx].image, descricao: sucos[idx].descricao, price: sucos[idx].price });
                    msgDiv.innerHTML = '<span style="color:green;">Suco salvo!</span>';
                    renderSucos();
                };
            });
            document.querySelectorAll('.delete-suco').forEach(btn => {
                btn.onclick = async function() {
                    const idx = this.dataset.idx;
                    addAdminLog('Excluir suco', sucos[idx]);
                    sucos.splice(idx, 1);
                    await saveMenu();
                    renderSucos();
                };
            });
        }
        renderSucos();
        document.getElementById('addSucoForm').onsubmit = async function(e) {
            e.preventDefault();
            const name = document.getElementById('addSucoName').value.trim();
            const image = document.getElementById('addSucoImage').value.trim();
            const descricao = document.getElementById('addSucoDesc').value.trim();
            const price = Number(document.getElementById('addSucoPrice').value);
            if (!name) return;
            sucos.push({ name, price, image, descricao });
            addAdminLog('Adicionar suco', { name, price, image, descricao });
            await saveMenu();
            renderSucos();
            this.reset();
        };
    }
}
