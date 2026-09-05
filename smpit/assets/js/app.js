document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.querySelector('[data-menu-toggle]');
  var nav = document.querySelector('[data-main-nav]');
  if (toggle && nav) toggle.addEventListener('click', function () { var open = nav.classList.toggle('open'); toggle.classList.toggle('open', open); toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); });
  document.querySelectorAll('[data-nav-dropdown]').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      var parent = trigger.closest('.nav-dropdown');
      var willOpen = !parent.classList.contains('open');
      document.querySelectorAll('.nav-dropdown.open').forEach(function (item) { item.classList.remove('open'); var button = item.querySelector('[data-nav-dropdown]'); if (button) button.setAttribute('aria-expanded', 'false'); });
      parent.classList.toggle('open', willOpen);
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (event) { if (!event.target.closest('.nav-dropdown')) document.querySelectorAll('.nav-dropdown.open').forEach(function (item) { item.classList.remove('open'); var button = item.querySelector('[data-nav-dropdown]'); if (button) button.setAttribute('aria-expanded', 'false'); }); });
  var dialog = document.querySelector('[data-lightbox-dialog]');
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
  var current = 0;
  function show(index) { if (!dialog || !buttons.length) return; current = (index + buttons.length) % buttons.length; var button = buttons[current]; dialog.querySelector('img').src = button.dataset.lightbox; dialog.querySelector('img').alt = button.dataset.title || ''; dialog.querySelector('[data-lightbox-title]').textContent = button.dataset.title || ''; dialog.hidden = false; document.body.classList.add('modal-open'); }
  function close() { if (!dialog) return; dialog.hidden = true; document.body.classList.remove('modal-open'); }
  buttons.forEach(function (button, index) { button.addEventListener('click', function () { show(index); }); });
  if (dialog) { dialog.querySelector('[data-lightbox-close]').addEventListener('click', close); dialog.querySelector('[data-lightbox-prev]').addEventListener('click', function () { show(current - 1); }); dialog.querySelector('[data-lightbox-next]').addEventListener('click', function () { show(current + 1); }); dialog.addEventListener('click', function (event) { if (event.target === dialog) close(); }); }
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { document.querySelectorAll('.nav-dropdown.open').forEach(function (item) { item.classList.remove('open'); }); if (dialog && !dialog.hidden) close(); } if (!dialog || dialog.hidden) return; if (event.key === 'ArrowLeft') show(current - 1); if (event.key === 'ArrowRight') show(current + 1); });
});
