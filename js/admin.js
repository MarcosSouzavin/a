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

async function fetchProdutosJson() {
    return fetch('API/produtos.php?' + Date.now())
        .then(r => r.ok ? r.json() : { produtos: [], adicionais: [] })
        .then(data => Array.isArray(data.produtos) ? data.produtos : [])
        .catch(() => []);
}

async function saveMenu() {
    // Inclui produtos, bebidas e adicionais no JSON salvo

    let drinks = JSON.parse(localStorage.getItem('drinks') || '[]');
    let sucos = JSON.parse(localStorage.getItem('sucos') || '[]');
    let adicionais = JSON.parse(localStorage.getItem('adicionais') || '[]');

    const drinkProducts = Array.isArray(drinks) ? drinks.map((d, idx) => ({
        id: `drink_${idx+1}`,
        name: d.name,
        image: d.image || '',
        descricao: '',
        sizes: [ { name: 'Único', price: d.price } ],
        adicionais: [] // bebidas nunca têm adicionais
    })) : [];

    const sucoProducts = Array.isArray(sucos) ? sucos.map((s, idx) => ({
        id: `suco_${idx+1}`,
        name: s.name,
        image: s.image || '',
        descricao: s.descricao || '',
        sizes: [ { name: 'Único', price: s.price } ],
        adicionais: [] // bebidas nunca têm adicionais
    })) : [];

    // Filter out drinks and sucos from menuItems to avoid duplicates
    const regularProducts = menuItems.filter(p => !String(p.id).startsWith('drink_') && !String(p.id).startsWith('suco_'));

    const allProducts = [...regularProducts, ...drinkProducts, ...sucoProducts];

    const payload = {
        produtos: allProducts,
        adicionais: adicionais
    };

    await fetch('API/produtos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    // Recarrega produtos do backend após salvar
    menuItems = await fetchProdutosJson();
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

document.addEventListener('DOMContentLoaded', () => {
    if (!isLoggedIn()) {
        showLogin();
    } else {
        setupAdminTabs();
    }
});

function setupAdminTabs() {
    const tabContent = document.getElementById('adminTabContent');
    const tabBtns = document.querySelectorAll('.tab-btn');
    let currentTab = 'produtos';
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

    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.onclick = () => {
            sessionStorage.removeItem('admin_logged');
            location.reload();
        };
    }

    async function renderTab(tab) {
        if (tab === 'produtos') {
            tabContent.innerHTML = '<h2>Produtos Cadastrados</h2>';
            menuItems = await fetchProdutosJson();
            if (!Array.isArray(menuItems) || menuItems.length === 0) {
                tabContent.innerHTML += '<em>Nenhum produto cadastrado.</em>';
                return;
            }
            menuItems.forEach(item => {
                tabContent.innerHTML += `
                    <div class="admin-item">
                        <h3>${item.name}</h3>
                        <img src="${item.image}" alt="${item.name}" style="max-width:80px;max-height:80px;">
                        <div>Descrição: ${item.descricao ? item.descricao : '<em>Sem descrição</em>'}</div>
                        <div>Pequena: R$ ${item.sizes[0].price.toFixed(2)} | Média: R$ ${item.sizes[1].price.toFixed(2)} | Grande: R$ ${item.sizes[2].price.toFixed(2)}</div>
                    </div>
                `;
            });
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
                menuItems = await fetchProdutosJson();
                if (menuItems.some(i => i.name.toLowerCase() === name.toLowerCase())) {
                    document.getElementById('addMsg').innerHTML = '<span style="color:red;">Produto já existe!</span>';
                    return;
                }
                const newId = menuItems.length ? Math.max(...menuItems.map(i => i.id)) + 1 : 1;
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
            menuItems = await fetchProdutosJson();
            tabContent.innerHTML = '<h2>Editar Produtos</h2>';
            if (!Array.isArray(menuItems) || menuItems.length === 0) {
                tabContent.innerHTML += '<em>Nenhum produto cadastrado.</em>';
                return;
            }
            menuItems.forEach((item, idx) => {
                tabContent.innerHTML += `
                    <div class="admin-item" data-idx="${idx}">
                        <h3>${item.name}</h3>
                        <img src="${item.image}" alt="${item.name}" style="max-width:80px;max-height:80px;">
                        <label>Imagem: <input type="text" value="${item.image}" class="img-input"></label>
                        <label>Descrição: <textarea class="desc-input" rows="2" style="width:100%;resize:vertical;">${item.descricao ? item.descricao : ''}</textarea></label>
                        <label>Pequena: <input type="number" value="${item.sizes[0].price}" class="price-input" data-size="0"></label>
                        <label>Média: <input type="number" value="${item.sizes[1].price}" class="price-input" data-size="1"></label>
                        <label>Grande: <input type="number" value="${item.sizes[2].price}" class="price-input" data-size="2"></label>
                        <button class="save-btn">Salvar</button>
                        <button class="delete-btn">Excluir</button>
                        <div class="editMsg"></div>
                    </div>
                `;
            });
            // Adicionar listeners de edição/exclusão
            setTimeout(() => {
                document.querySelectorAll('.admin-item').forEach((div, idx) => {
                    const imgInput = div.querySelector('.img-input');
                    const descInput = div.querySelector('.desc-input');
                    const priceInputs = div.querySelectorAll('.price-input');
                    const saveBtn = div.querySelector('.save-btn');
                    const deleteBtn = div.querySelector('.delete-btn');
                    const editMsg = div.querySelector('.editMsg');
                    saveBtn.onclick = async function() {
                        menuItems[idx].image = imgInput.value;
                        menuItems[idx].descricao = descInput.value;
                        priceInputs.forEach(input => {
                            const sizeIdx = input.dataset.size;
                            menuItems[idx].sizes[sizeIdx].price = Number(input.value);
                        });
                        await saveMenu();
                        addAdminLog('Editar produto', { id: menuItems[idx].id, image: imgInput.value, descricao: descInput.value, prices: Array.from(priceInputs).map(i=>i.value) });
                        editMsg.innerHTML = '<span style="color:green;">Produto salvo!</span>';
                        renderTab('editar');
                    };
                    deleteBtn.onclick = async function() {
                        if (confirm('Tem certeza que deseja excluir este produto?')) {
                            addAdminLog('Excluir produto', { id: menuItems[idx].id, name: menuItems[idx].name });
                            menuItems.splice(idx, 1);
                            await saveMenu();
                            renderTab('editar');
                        }
                    };
                });
            }, 100);
        } else if (tab === 'adicionais') {
            let adicionais = JSON.parse(localStorage.getItem('adicionais')) || [];
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
                    btn.onclick = function() {
                        const idx = this.dataset.idx;
                        addAdminLog('Excluir adicional', adicionais[idx]);
                        adicionais.splice(idx, 1);
                        localStorage.setItem('adicionais', JSON.stringify(adicionais));
                        renderAdicionais();
                    };
                });
            }
            renderAdicionais();
            document.getElementById('addAdicionalForm').onsubmit = function(e) {
                e.preventDefault();
                const name = document.getElementById('addAdicionalName').value.trim();
                const price = Number(document.getElementById('addAdicionalPrice').value);
                if (!name) return;
                adicionais.push({ name, price });
                localStorage.setItem('adicionais', JSON.stringify(adicionais));
                addAdminLog('Adicionar adicional', { name, price });
                renderAdicionais();
                this.reset();
            };
        } else if (tab === 'refrigerantes') {
            let drinks = JSON.parse(localStorage.getItem('drinks')) || [];
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
                drinks = JSON.parse(localStorage.getItem('drinks') || '[]');
                const list = document.getElementById('drinksList');
                list.innerHTML = '';
                drinks.forEach((drink, idx) => {
                    const div = document.createElement('div');
                    div.className = 'admin-item';
                    const imgUrl = typeof drink.image === 'string' ? drink.image : '';
                    div.innerHTML = `
                        ${imgUrl ? `<img src="${imgUrl}" alt="${drink.name}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:4px;vertical-align:middle;">` : ''}
                        <strong>${drink.name}</strong> - R$ ${drink.price.toFixed(2)}
                        <button class="delete-drink" data-idx="${idx}">Excluir</button>
                    `;
                    list.appendChild(div);
                });
                document.querySelectorAll('.delete-drink').forEach(btn => {
                    btn.onclick = async function() {
                        drinks = JSON.parse(localStorage.getItem('drinks') || '[]');
                        const idx = this.dataset.idx;
                        addAdminLog('Excluir refrigerante', drinks[idx]);
                        drinks.splice(idx, 1);
                        localStorage.setItem('drinks', JSON.stringify(drinks));
                        await saveMenu();
                        renderDrinks();
                    };
                });
            }
            renderDrinks();
            document.getElementById('addDrinkForm').onsubmit = async function(e) {
                e.preventDefault();
                drinks = JSON.parse(localStorage.getItem('drinks') || '[]');
                const name = document.getElementById('addDrinkName').value.trim();
                const image = document.getElementById('addDrinkImage').value.trim();
                const price = Number(document.getElementById('addDrinkPrice').value);
                if (!name) return;
                drinks.push({ name, price, image });
                localStorage.setItem('drinks', JSON.stringify(drinks));
                addAdminLog('Adicionar refrigerante', { name, price, image });
                await saveMenu();
                renderDrinks();
                this.reset();
            };
        } else if (tab === 'sucos') {
            let sucos = JSON.parse(localStorage.getItem('sucos')) || [];
            // If localStorage is empty, try to load from produtos.json
            if (sucos.length === 0) {
                menuItems.forEach(p => {
                    if (String(p.id).startsWith('suco_')) {
                        sucos.push({
                            name: p.name,
                            price: p.sizes && p.sizes.length ? p.sizes[0].price : 0,
                            image: p.image || '',
                            descricao: p.descricao || ''
                        });
                    }
                });
                localStorage.setItem('sucos', JSON.stringify(sucos));
            }
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
                sucos = JSON.parse(localStorage.getItem('sucos') || '[]');
                const list = document.getElementById('sucosList');
                list.innerHTML = '';
                sucos.forEach((suco, idx) => {
                    const div = document.createElement('div');
                    div.className = 'admin-item';
                    const imgUrl = typeof suco.image === 'string' ? suco.image : '';
                    div.innerHTML = `
                        ${imgUrl ? `<img src="${imgUrl}" alt="${imgUrl}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:4px;vertical-align:middle;">` : ''}
                        <strong>${suco.name}</strong> - R$ ${suco.price.toFixed(2)}
                        <div>Descrição: ${suco.descricao ? suco.descricao : '<em>Sem descrição</em>'}</div>
                        <button class="delete-suco" data-idx="${idx}">Excluir</button>
                    `;
                    list.appendChild(div);
                });
                document.querySelectorAll('.delete-suco').forEach(btn => {
                    btn.onclick = async function() {
                        sucos = JSON.parse(localStorage.getItem('sucos') || '[]');
                        const idx = this.dataset.idx;
                        addAdminLog('Excluir suco', sucos[idx]);
                        sucos.splice(idx, 1);
                        localStorage.setItem('sucos', JSON.stringify(sucos));
                        await saveMenu();
                        renderSucos();
                    };
                });
            }
            renderSucos();
            document.getElementById('addSucoForm').onsubmit = async function(e) {
                e.preventDefault();
                sucos = JSON.parse(localStorage.getItem('sucos') || '[]');
                const name = document.getElementById('addSucoName').value.trim();
                const image = document.getElementById('addSucoImage').value.trim();
                const descricao = document.getElementById('addSucoDesc').value.trim();
                const price = Number(document.getElementById('addSucoPrice').value);
                if (!name) return;
                sucos.push({ name, price, image, descricao });
                localStorage.setItem('sucos', JSON.stringify(sucos));
                addAdminLog('Adicionar suco', { name, price, image, descricao });
                await saveMenu();
                renderSucos();
                this.reset();
            };
        }
    }
}