// Alpha Preclinical static site behaviour (no dependencies).
// Hooks are data attributes written by tools/convert.py.
(function () {
  'use strict';

  // Mobile menu
  document.querySelectorAll('[data-menu]').forEach(function (btn) {
    var nav = btn.closest('.hdr') && btn.closest('.hdr').querySelector('.nav');
    if (!nav) return;
    btn.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
  });

  // Accordions (FAQ, open roles): one open at a time within a list
  document.querySelectorAll('[data-accordion]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = document.getElementById(btn.getAttribute('aria-controls'));
      var willOpen = btn.getAttribute('aria-expanded') !== 'true';
      var list = btn.closest('ul, ol, .faq-list');
      if (list) {
        list.querySelectorAll('[data-accordion][aria-expanded="true"]').forEach(function (other) {
          if (other === btn) return;
          other.setAttribute('aria-expanded', 'false');
          var p = document.getElementById(other.getAttribute('aria-controls'));
          if (p) p.hidden = true;
          var i = other.querySelector('.ico'); if (i) i.textContent = '+';
        });
      }
      btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      if (panel) panel.hidden = !willOpen;
      var ico = btn.querySelector('.ico'); if (ico) ico.textContent = willOpen ? '−' : '+';
    });
  });

  // Filters (publications, blog topics, careers): buttons filter [data-item] by [data-topic]
  document.querySelectorAll('[data-filter-group]').forEach(function (group) {
    var name = group.getAttribute('data-filter-group');
    var items = document.querySelectorAll('[data-item="' + name + '"]');
    var empty = document.querySelector('[data-empty="' + name + '"]');
    group.querySelectorAll('[data-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var value = btn.getAttribute('data-filter');
        group.querySelectorAll('[data-filter]').forEach(function (b) {
          b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });
        var shown = 0;
        items.forEach(function (el) {
          var match = value === 'All' || el.getAttribute('data-topic') === value;
          el.hidden = !match;
          if (match) shown++;
        });
        if (empty) empty.hidden = shown > 0;
      });
    });
  });

  // "Apply for this role" pre-selects the role in the application form
  document.querySelectorAll('[data-apply-role]').forEach(function (a) {
    a.addEventListener('click', function () {
      var select = document.querySelector('select[name="role"]');
      if (select) select.value = a.getAttribute('data-apply-role');
    });
  });

  // Demo forms: validate email, then show the confirmation. Replace with a real
  // form handler (Gravity Forms, Fluent Forms) when this moves to WordPress.
  document.querySelectorAll('form[data-demo-form]').forEach(function (form) {
    var wrap = form.parentElement;
    var done = wrap.querySelector('[data-done]');
    var email = form.querySelector('input[type="email"]');
    var err = email && document.getElementById(email.getAttribute('aria-describedby'));
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var ok = email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value);
      if (!ok) {
        if (email) { email.setAttribute('aria-invalid', 'true'); email.focus(); }
        if (err) err.hidden = false;
        return;
      }
      if (email) email.setAttribute('aria-invalid', 'false');
      if (err) err.hidden = true;
      if (done) {
        done.querySelectorAll('[data-email-out]').forEach(function (s) { s.textContent = email.value; });
        var role = form.querySelector('select[name="role"]');
        done.querySelectorAll('[data-role-out]').forEach(function (s) { s.textContent = role ? role.value : ''; });
        form.hidden = true;
        done.hidden = false;
        done.setAttribute('tabindex', '-1');
        done.focus();
      }
    });
    if (done) {
      var reset = done.querySelector('[data-reset]');
      if (reset) reset.addEventListener('click', function () {
        form.reset();
        done.hidden = true;
        form.hidden = false;
        if (email) email.focus();
      });
    }
  });
})();
