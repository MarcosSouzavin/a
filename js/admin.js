const ADMIN_PASSWORD = 'p'; 

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

let menuItems = JSON.parse(localStorage.getItem('menuItems')) || [
    {
        id: 1,
        name: '5 queijos',
        image: 'img/pizzas/5_queijos.png',
        descricao: 'Pizza de 5 queijos acompanha mussarela, parmesão, provolone, gorgonzola e catupiry.',
        sizes: [
            { name: 'Pequena', price: 37.00 },
            { name: 'Média', price: 78.50 },
            { name: 'Grande', price: 105.00 }
        ]
    },
];

function saveMenu() {
    localStorage.setItem('menuItems', JSON.stringify(menuItems));
}

function renderAdmin() {
    const container = document.getElementById('adminContainer');
    container.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;">Painel Admin</h2>
            <button id="logoutBtn" class="admin-logout-btn">Encerrar Sessão</button>
        </div>
        <div class="admin-sections">
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
            <hr>
            <form id="addDrinkForm" class="admin-form">
                <h2>Adicionar Refrigerante</h2>
                <label>Nome: <input type="text" id="addDrinkName" required></label>
                <label>Preço: <input type="number" id="addDrinkPrice" required></label>
                <button type="submit">Adicionar</button>
            </form>
            <div id="drinksList"></div>
            <hr>
            <form id="addAdicionalForm" class="admin-form">
                <h2>Adicionar Adicional</h2>
                <label>Nome: <input type="text" id="addAdicionalName" required></label>
                <label>Preço: <input type="number" id="addAdicionalPrice" required></label>
                <button type="submit">Adicionar</button>
            </form>
            <div id="adicionaisList"></div>
        </div>
    `;
    // Botão de logout
    setTimeout(() => {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.onclick = function() {
                sessionStorage.removeItem('admin_logged');
                location.reload();
            };
        }
    }, 0);
    // Carregar dados extras
    let drinks = JSON.parse(localStorage.getItem('drinks')) || [];
    let adicionais = JSON.parse(localStorage.getItem('adicionais')) || [];

    // Renderizar lista de refrigerantes
    function renderDrinks() {
        const list = document.getElementById('drinksList');
        list.innerHTML = '<h3>Refrigerantes</h3>';
        drinks.forEach((drink, idx) => {
            const div = document.createElement('div');
            div.className = 'admin-item';
            div.innerHTML = `
                <strong>${drink.name}</strong> - R$ ${drink.price.toFixed(2)}
                <button class="delete-drink" data-idx="${idx}">Excluir</button>
            `;
            list.appendChild(div);
        });
        document.querySelectorAll('.delete-drink').forEach(btn => {
            btn.onclick = function() {
                const idx = this.dataset.idx;
                addAdminLog('Excluir refrigerante', drinks[idx]);
                drinks.splice(idx, 1);
                localStorage.setItem('drinks', JSON.stringify(drinks));
                renderDrinks();
            };
        });
    }
    renderDrinks();

    function renderAdicionais() {
        const list = document.getElementById('adicionaisList');
        list.innerHTML = '<h3>Adicionais</h3>';
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

    // Adicionar refrigerante
    document.getElementById('addDrinkForm').onsubmit = function(e) {
        e.preventDefault();
        const name = document.getElementById('addDrinkName').value.trim();
        const price = Number(document.getElementById('addDrinkPrice').value);
        if (!name) return;
        drinks.push({ name, price });
        localStorage.setItem('drinks', JSON.stringify(drinks));
        addAdminLog('Adicionar refrigerante', { name, price });
        renderDrinks();
        this.reset();
    };

    // Adicionar adicional
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

    menuItems.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = 'admin-item';
        div.innerHTML = `
            <h3>${item.name}</h3>
            <img src="${item.image}" alt="${item.name}">
            <label>Imagem: <input type="text" value="${item.image}" data-idx="${idx}" class="img-input"></label>
            <label>Descrição: <textarea data-idx="${idx}" class="desc-input" rows="2" style="width:100%;resize:vertical;">${item.descricao ? item.descricao : ''}</textarea></label>
            <label>Pequena: <input type="number" value="${item.sizes[0].price}" data-idx="${idx}" data-size="0" class="price-input"></label>
            <label>Média: <input type="number" value="${item.sizes[1].price}" data-idx="${idx}" data-size="1" class="price-input"></label>
            <label>Grande: <input type="number" value="${item.sizes[2].price}" data-idx="${idx}" data-size="2" class="price-input"></label>
            <button class="save-btn" data-idx="${idx}">Salvar</button>
            <button class="delete-btn" data-idx="${idx}">Excluir</button>
        `;
        container.appendChild(div);
    });

    // Adicionar produto
    document.getElementById('addProductForm').onsubmit = function(e) {
        e.preventDefault();
        const name = document.getElementById('addName').value.trim();
        const image = document.getElementById('addImage').value.trim();
        const descricao = document.getElementById('addDesc').value.trim();
        const p = Number(document.getElementById('addP').value);
        const m = Number(document.getElementById('addM').value);
        const g = Number(document.getElementById('addG').value);
        if (!name || !image || !descricao) return;
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
        saveMenu();
        renderAdmin();
    };

    // Salvar edição
    document.querySelectorAll('.save-btn').forEach(btn => {
        btn.onclick = function() {
            const idx = this.dataset.idx;
            const imgInput = document.querySelector(`.img-input[data-idx="${idx}"]`);
            const descInput = document.querySelector(`.desc-input[data-idx="${idx}"]`);
            const priceInputs = document.querySelectorAll(`.price-input[data-idx="${idx}"]`);
            menuItems[idx].image = imgInput.value;
            menuItems[idx].descricao = descInput.value;
            priceInputs.forEach(input => {
                const sizeIdx = input.dataset.size;
                menuItems[idx].sizes[sizeIdx].price = Number(input.value);
            });
            saveMenu();
            addAdminLog('Editar produto', { id: menuItems[idx].id, image: imgInput.value, descricao: descInput.value, prices: Array.from(priceInputs).map(i=>i.value) });
            alert('Produto salvo!');
            renderAdmin();
        };
    });

    // Excluir produto
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.onclick = function() {
            const idx = this.dataset.idx;
            if (confirm('Tem certeza que deseja excluir este produto?')) {
                addAdminLog('Excluir produto', { id: menuItems[idx].id, name: menuItems[idx].name });
                menuItems.splice(idx, 1);
                saveMenu();
                renderAdmin();
            }
        };
    });
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
        renderAdmin();
    }
});