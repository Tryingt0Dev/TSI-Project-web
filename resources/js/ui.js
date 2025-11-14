document.addEventListener('DOMContentLoaded', function() {
  // Tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

  // Confirm delete: elements with .btn-confirm and data-action (url) + data-method (POST/DELETE)
  document.querySelectorAll('.btn-confirm').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const title = btn.dataset.title || '¿Estás seguro?';
      const text  = btn.dataset.text || 'Esta acción no se puede deshacer.';
      const url   = btn.dataset.action;
      const method= btn.dataset.method || 'POST';

      document.getElementById('confirmTitle').textContent = title;
      document.getElementById('confirmText').textContent = text;

      const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
      confirmModal.show();

      const yes = document.getElementById('confirmYes');
      const handler = function() {
        // Crear y enviar formulario dinámico
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
          const input = document.createElement('input');
          input.type = 'hidden'; input.name = '_token'; input.value = token; form.appendChild(input);
        }
        if (method.toUpperCase() !== 'POST') {
          const _m = document.createElement('input');
          _m.type='hidden'; _m.name='_method'; _m.value=method; form.appendChild(_m);
        }
        document.body.appendChild(form);
        form.submit();
      };

      yes.addEventListener('click', handler, { once: true });
    });
  });

  // Initialize toasts
  document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t).show());
});
