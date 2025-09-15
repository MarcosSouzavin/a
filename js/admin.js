const ADMIN_PASSWORD = 'p'; 

function isLoggedIn() {
    return sessionStorage.getItem('admin_logged') === '1';
}

function showLogin() {
    document.body.innerHTML = `
        <div class="admin-login-box">
            <h2>Login Admin</h2>
            <input type="password" id="adminPass" placeholder="Senha de administrador">
            <br>
            <button id="loginBtn">Entrar</button>
            <div id="loginMsg" style="color:#c0392b;margin-top:10px;"></div>
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
        <form id="addProductForm">
            <h2>Adicionar Produto</h2>
            <label>Nome: <input type="text" id="addName" required></label>
            <label>Imagem (URL): <input type="text" id="addImage" required></label>
            <label>Preço Pequena: <input type="number" id="addP" required></label>
            <label>Preço Média: <input type="number" id="addM" required></label>
            <label>Preço Grande: <input type="number" id="addG" required></label>
            <button type="submit">Adicionar</button>
        </form>
        <hr>
    `;

    menuItems.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = 'admin-item';
        div.innerHTML = `
            <h3>${item.name}</h3>
            <img src="${item.image}" alt="${item.name}">
            <label>Imagem: <input type="text" value="${item.image}" data-idx="${idx}" class="img-input"></label>
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
        const p = Number(document.getElementById('addP').value);
        const m = Number(document.getElementById('addM').value);
        const g = Number(document.getElementById('addG').value);
        if (!name || !image) return;
        const newId = menuItems.length ? Math.max(...menuItems.map(i => i.id)) + 1 : 1;
        addAdminLog('Adicionar produto', { name, image, p, m, g });
        menuItems.push({
            id: newId,
            name,
            image,
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
            const priceInputs = document.querySelectorAll(`.price-input[data-idx="${idx}"]`);
            menuItems[idx].image = imgInput.value;
            priceInputs.forEach(input => {
                const sizeIdx = input.dataset.size;
                menuItems[idx].sizes[sizeIdx].price = Number(input.value);
            });
            saveMenu();
            addAdminLog('Editar produto', { id: menuItems[idx].id, image: imgInput.value, prices: Array.from(priceInputs).map(i=>i.value) });
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