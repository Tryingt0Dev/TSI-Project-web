(function () {
  const updateNavHeight = () => {
    const nav = document.querySelector('.navbar');
    if (!nav) return;
    const h = Math.ceil(nav.getBoundingClientRect().height);
    document.documentElement.style.setProperty('--nav-height', h + 'px');
  };

  // Ejecutar al iniciar y al cargar la página
  document.addEventListener('DOMContentLoaded', updateNavHeight);
  window.addEventListener('load', updateNavHeight);

  // Recalcular al redimensionar (debounce simple)
  let resizeTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateNavHeight, 120);
  });

  // Recalcular cuando se abra/cierre el collapse del navbar (Bootstrap events)
  document.addEventListener('shown.bs.collapse', updateNavHeight);
  document.addEventListener('hidden.bs.collapse', updateNavHeight);
})();