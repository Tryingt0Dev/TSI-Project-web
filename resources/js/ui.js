// resources/js/ui.js
// UI helpers: tooltips, toasts, confirm delete (fetch + fallback)
// Mejoras: evita listeners duplicados, maneja modal ausente y fallback por formulario

document.addEventListener('DOMContentLoaded', function () {
  // Tooltips
  try {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
  } catch (e) {
    console.warn('ui.js: error inicializando tooltips', e);
  }

  // Toasts auto-show
  document.querySelectorAll('.toast').forEach(function (t) {
    try { new bootstrap.Toast(t).show(); } catch (e) { /* ignore */ }
  });

  // Helper: get CSRF token from meta or existing input
  function getCsrfToken() {
    var meta = document.head.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');

    var inpt = document.querySelector('input[name="_token"]');
    if (inpt) return inpt.value;

    return null;
  }

  // Fallback submit by creating a form (for browsers without fetch or when needed)
  function submitFormFallback(action, method) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.style.display = 'none';

    var token = getCsrfToken();
    if (token) {
      var tokenInput = document.createElement('input');
      tokenInput.type = 'hidden';
      tokenInput.name = '_token';
      tokenInput.value = token;
      form.appendChild(tokenInput);
    }

    if (method && method.toUpperCase() !== 'POST') {
      var m = document.createElement('input');
      m.type = 'hidden';
      m.name = '_method';
      m.value = method.toUpperCase();
      form.appendChild(m);
    }

    document.body.appendChild(form);
    form.submit();
  }

  // Send request using fetch with _method override (Laravel friendly)
  function sendRequestFetch(action, method) {
    var token = getCsrfToken();
    if (!token) {
      console.warn('ui.js: CSRF token not found, using fallback form submit');
      submitFormFallback(action, method);
      return;
    }

    // Prepare body as urlencoded string: _method=DELETE
    var body = '_method=' + encodeURIComponent(method.toUpperCase());

    fetch(action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html, application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body,
      credentials: 'same-origin'
    }).then(function (res) {
      console.log('ui.js: fetch response status', res.status);

      // If OK, try parse JSON, otherwise assume redirect/HTML and reload
      if (res.ok) {
        var ct = res.headers.get('content-type') || '';
        if (ct.indexOf('application/json') !== -1) {
          return res.json().then(function (json) {
            if (json.redirect) {
              window.location.href = json.redirect;
            } else {
              window.location.reload();
            }
          }).catch(function () {
            window.location.reload();
          });
        } else {
          window.location.reload();
        }
      } else if (res.status === 302) {
        window.location.reload();
      } else if (res.status === 419) {
        alert('CSRF token inválido o expirado (419). Vuelve a iniciar sesión o recarga la página.');
      } else if (res.status === 403) {
        alert('Acceso denegado (403). Verifica permisos.');
      } else {
        console.warn('ui.js: fetch returned status', res.status, ' — falling back to form submit');
        submitFormFallback(action, method);
      }
    }).catch(function (err) {
      console.error('ui.js: fetch error', err);
      submitFormFallback(action, method);
    });
  }

  // Utility to safely get modal elements and avoid crashes
  function getConfirmModalElements() {
    var modalEl = document.getElementById('confirmModal');
    if (!modalEl) return null;

    var titleEl = document.getElementById('confirmTitle');
    var textEl = document.getElementById('confirmText');
    var yesBtn = document.getElementById('confirmYes');

    if (!yesBtn) {
      console.warn('ui.js: confirm modal found but #confirmYes missing');
    }

    return { modalEl: modalEl, titleEl: titleEl, textEl: textEl, yesBtn: yesBtn };
  }

  // Keep track of the currently attached "yes" handler so we can remove it if needed.
  var currentYesHandler = null;

  // Delegated click handler for .btn-confirm (works for dynamic elements)
  document.addEventListener('click', function (e) {
    var el = e.target;
    var btn = el.closest ? el.closest('.btn-confirm') : null;
    if (!btn) return;

    e.preventDefault();

    var url = btn.getAttribute('data-action');
    var method = btn.getAttribute('data-method') || 'DELETE';
    var text = btn.getAttribute('data-text') || '¿Estás seguro?';
    var title = btn.getAttribute('data-title') || 'Confirmar';

    if (!url) {
      console.warn('ui.js: btn-confirm missing data-action');
      return;
    }

    var modalObj = getConfirmModalElements();

    if (modalObj && modalObj.modalEl && modalObj.yesBtn) {
      // fill modal text
      if (modalObj.titleEl) modalObj.titleEl.textContent = title;
      if (modalObj.textEl) modalObj.textEl.textContent = text;

      // show modal
      var bsModal;
      try {
        bsModal = new bootstrap.Modal(modalObj.modalEl);
        bsModal.show();
      } catch (err) {
        console.warn('ui.js: no se pudo mostrar modal, fallback a confirm()', err);
        if (!window.confirm(text)) return;
        sendRequestFetch(url, method);
        return;
      }

      // remove previous handler if any
      if (currentYesHandler && modalObj.yesBtn) {
        modalObj.yesBtn.removeEventListener('click', currentYesHandler);
        currentYesHandler = null;
      }

      // define handler
      currentYesHandler = function () {
        // send request via fetch (with fallback inside)
        sendRequestFetch(url, method);
        try { bsModal.hide(); } catch (err) { /* ignore */ }
      };

      // attach once
      modalObj.yesBtn.addEventListener('click', currentYesHandler, { once: true });
      return;
    }

    // If modal not present, fallback to window.confirm
    if (!window.confirm(text)) return;
    sendRequestFetch(url, method);
  });

});
