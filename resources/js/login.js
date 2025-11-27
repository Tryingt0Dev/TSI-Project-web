

document.addEventListener('DOMContentLoaded', () => {
  // Elementos del formulario
  const form = document.getElementById('loginForm');
  const submitBtn = document.getElementById('submitBtn');
  const emailInput = document.getElementById('email');
  const pwdInput = document.getElementById('password');
  const emailError = document.getElementById('emailError');
  const pwdError = document.getElementById('passwordError');
  const togglePwd = document.getElementById('togglePwd');

  
  const searchInput = document.getElementById('busqueda');
  const searchResults = document.getElementById('searchResults');
  const searchClear = document.getElementById('searchClear');

  
  const catalogo = [
    "Cien años de soledad — Gabriel García Márquez",
    "Don Quijote de la Mancha — Miguel de Cervantes",
    "El principito — Antoine de Saint-Exupéry",
    "La ciudad y los perros — Mario Vargas Llosa",
    "Introducción a la programación — Autor Ejemplo",
    "Historia universal — Autor Historia",
    "JavaScript moderno — Autor JS"
  ];

  // ---------------------------
  // Validación simple de email
  function esEmailValido(email) {
    
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // Toggle ver/ocultar contraseña
  togglePwd?.addEventListener('click', () => {
    if (!pwdInput) return;
    const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
    pwdInput.setAttribute('type', type);
    togglePwd.textContent = type === 'text' ? '🙈' : '👁️';
  });

  // Manejo submit
  form?.addEventListener('submit', (ev) => {
    
    let ok = true;
    emailError.textContent = '';
    pwdError.textContent = '';

    if (!emailInput.value || !esEmailValido(emailInput.value.trim())) {
      emailError.textContent = 'Introduce un correo válido';
      ok = false;
    }
    if (!pwdInput.value || pwdInput.value.trim().length < 6) {
      pwdError.textContent = 'La contraseña debe tener al menos 6 caracteres';
      ok = false;
    }

    if (!ok) {
      ev.preventDefault();
      return;
    }

    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Ingresando...';

    
  });

  
  function filtrarCatalogo(q) {
    const term = q.trim().toLowerCase();
    if (!term) return [];
    return catalogo.filter(item => item.toLowerCase().includes(term)).slice(0, 6);
  }

  function mostrarResultados(items) {
    searchResults.innerHTML = '';
    if (!items.length) {
      searchResults.style.display = 'none';
      return;
    }
    items.forEach(item => {
      const li = document.createElement('li');
      li.textContent = item;
      li.tabIndex = 0;
      li.addEventListener('click', () => {
        searchInput.value = item;
        searchResults.style.display = 'none';
      });
      li.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') li.click();
      });
      searchResults.appendChild(li);
    });
    searchResults.style.display = 'block';
  }

  // Events
  if (searchInput) {
    let debounceTimer = null;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const q = e.target.value;
        if (!q.trim()) {
          searchResults.style.display = 'none';
          return;
        }
        const matches = filtrarCatalogo(q);
        mostrarResultados(matches);
      }, 200);
    });

    // limpiar búsqueda
    searchClear?.addEventListener('click', () => {
      searchInput.value = '';
      searchResults.style.display = 'none';
      searchInput.focus();
    });

    // click fuera cierra resultados
    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('.search-wrapper')) {
        searchResults.style.display = 'none';
      }
    });
  }
});
