document.addEventListener("DOMContentLoaded", function () {
    const cpfInput = document.querySelector('input[name="cpf"]');
  
    if (cpfInput) {
      cpfInput.addEventListener("input", function (e) {
        let value = e.target.value.replace(/\D/g, "");
  
        if (value.length > 11) value = value.slice(0, 11);
  
        let formatted = value;
        if (value.length > 9) {
          formatted = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
        } else if (value.length > 6) {
          formatted = value.replace(/(\d{3})(\d{3})(\d{1,3})/, "$1.$2.$3");
        } else if (value.length > 3) {
          formatted = value.replace(/(\d{3})(\d{1,3})/, "$1.$2");
        }
  
        e.target.value = formatted;
      });
    }
  });
  document.addEventListener("DOMContentLoaded", function () {
    const telInput = document.querySelector('input[name="telefone"]');
  
    if (telInput) {
      telInput.addEventListener("input", function (e) {
        let value = e.target.value.replace(/\D/g, "");
  
        if (value.length > 11) value = value.slice(0, 11);
  
        if (value.length <= 10) {
          value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
        } else {
          value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
        }
  
        e.target.value = value;
      });
    }
  });
  function togglePassword() {
    const senhaInput = document.getElementById("senha");
    const tipo = senhaInput.getAttribute("type");

    if (tipo === "password") {
        senhaInput.setAttribute("type", "text");
    } else {
        senhaInput.setAttribute("type", "password");
    }
}
  
  