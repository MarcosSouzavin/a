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
    return fetch('API/produtos.php?' + Date.now())
        .then(r => r.ok ? r.json() : { produtos: [], adicionais: [] })
        .catch(() => ({ produtos: [], adicionais: [] }));
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
        drinks = menuItems.filter(p => String(p.id).startsWith('drink_'));
        sucos = menuItems.filter(p => String(p.id).startsWith('suco_'));
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

let currentTab = 'produtos';

document.addEventListener('DOMContentLoaded', async () => {
    if (!isLoggedIn()) {
        showLogin();
    } else {
        const data = await fetchProdutosJson();
        menuItems = data.produtos || [];
        adicionais = data.adicionais || [];
        drinks = menuItems.filter(p => String(p.id).startsWith('drink_'));
        sucos = menuItems.filter(p => String(p.id).startsWith('suco_'));
        setupAdminTabs();
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
}

async function renderTab(tab) {
    const tabContent = document.getElementById('adminTabContent');
    if (tab === 'produtos') {
        tabContent.innerHTML = '<h2>Produtos Cadastrados</h2>';
        if (!Array.isArray(menuItems) || menuItems.length === 0) {
            tabContent.innerHTML += '<em>Nenhum produto cadastrado.</em>';
            return;
        }
        menuItems.filter(p => !String(p.id).startsWith('drink_') && !String(p.id).startsWith('suco_')).forEach(item => {
            tabContent.innerHTML += `
                <div class="admin-item">
                    <h3>${item.name}</h3>
                    <img src="${item.image}" alt="${item.name}" style="max-width:80px;max-height:80px;">
                    <div>Descrição: ${item.descricao ? item.descricao : '<em>Sem descrição</em>'}</div>
                    <div>Pequena: R$ ${item.sizes[0].price.toFixed(2)} | Média: R$ ${item.sizes[1].price.toFixed(2)} | Grande: R$ ${item.sizes[2].price.toFixed(2)}</div>
                </div>
            `;
        });
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
                    <strong>${drink.name}</strong> - R$ ${drink.price.toFixed(2)}
                    <button class="delete-drink" data-idx="${idx}">Excluir</button>
                `;
                list.appendChild(div);
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
                    ${imgUrl ? `<img src="${imgUrl}" alt="${imgUrl}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:4px;vertical-align:middle;">` : ''}
                    <strong>${suco.name}</strong> - R$ ${suco.price.toFixed(2)}
                    <div>Descrição: ${suco.descricao ? suco.descricao : '<em>Sem descrição</em>'}</div>
                    <button class="delete-suco" data-idx="${idx}">Excluir</button>
                `;
                list.appendChild(div);
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
