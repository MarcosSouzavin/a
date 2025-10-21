// === FUNÇÕES UTILITÁRIAS ===
document.addEventListener("DOMContentLoaded", () => {
  console.log("✨ UI Controller inicializado");

  // ===== MENU MOBILE =====
  const menuHamburguer = document.querySelector(".menu-hamburguer");
  const nav = document.querySelector(".nav");

  if (menuHamburguer && nav) {
    menuHamburguer.addEventListener("click", (e) => {
      e.stopPropagation();
      const isActive = nav.classList.toggle("active");
      menuHamburguer.classList.toggle("open", isActive);

      // Fecha o carrinho se estiver aberto
      const sidebar = document.querySelector(".cart-sidebar");
      if (sidebar?.classList.contains("active")) {
        sidebar.classList.remove("active");
        document.querySelector(".cart-backdrop")?.classList.remove("active");
      }
    });

    nav.addEventListener("click", (e) => {
      if (e.target.classList.contains("nav-link")) {
        nav.classList.remove("active");
        menuHamburguer.classList.remove("open");
      }
    });
  }

  // ===== CARRINHO =====
  const cartButton = document.querySelector(".cart-button");
  const cartSidebar = document.querySelector(".cart-sidebar");
  const backdrop = document.querySelector(".cart-backdrop");
  const closeCartButton = document.querySelector(".close-cart");

  window.toggleCart = function (forceOpen = false) {
  const sidebar = document.querySelector(".cart-sidebar");
  const backdrop = document.querySelector(".cart-backdrop");
  const menuHamburguer = document.querySelector(".menu-hamburguer");
  const nav = document.querySelector(".nav");

  if (!sidebar) return;

  // Decide o estado final (aberto ou fechado)
  let isOpen;
  if (forceOpen === true) isOpen = true;
  else if (forceOpen === false) isOpen = false;
  else isOpen = !sidebar.classList.contains("active");

  // Fecha o menu mobile se estiver aberto
  nav?.classList.remove("active");
  menuHamburguer?.classList.remove("open");

  // Aplica o estado ao carrinho
  sidebar.classList.toggle("active", isOpen);
  backdrop?.classList.toggle("active", isOpen);

  console.log("🛒 Carrinho agora:", isOpen ? "aberto" : "fechado");
};


  cartButton?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleCart(true);
  });

  closeCartButton?.addEventListener("click", () => toggleCart(false));
  backdrop?.addEventListener("click", () => toggleCart(false));

  // Fecha tudo ao clicar fora
  document.addEventListener("click", (e) => {
    const insideCart = cartSidebar?.contains(e.target);
    const insideMenu = nav?.contains(e.target);
    const insideBtn = menuHamburguer?.contains(e.target) || cartButton?.contains(e.target);

    if (!insideCart && !insideMenu && !insideBtn) {
      cartSidebar?.classList.remove("active");
      nav?.classList.remove("active");
      backdrop?.classList.remove("active");
      menuHamburguer?.classList.remove("open");
    }
  });

  // Fecha com ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      cartSidebar?.classList.remove("active");
      nav?.classList.remove("active");
      backdrop?.classList.remove("active");
      menuHamburguer?.classList.remove("open");
    }
  });
});