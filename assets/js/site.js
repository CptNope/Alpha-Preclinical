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

  // Filters (publications, blog topics, careers): buttons filter [data-item] by [data-topic].
  // A visually hidden status line announces the result count to screen readers (WCAG 4.1.3).
  document.querySelectorAll('[data-filter-group]').forEach(function (group) {
    var name = group.getAttribute('data-filter-group');
    var noun = group.getAttribute('data-noun') || 'item';
    var items = document.querySelectorAll('[data-item="' + name + '"]');
    var empty = document.querySelector('[data-empty="' + name + '"]');
    var status = document.createElement('p');
    status.className = 'sr-only';
    status.setAttribute('role', 'status');
    group.parentNode.insertBefore(status, group.nextSibling);
    group.querySelectorAll('[data-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var value = btn.getAttribute('data-filter');
        group.querySelectorAll('[data-filter]').forEach(function (b) {
          b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });
        var shown = 0, count = 0;
        items.forEach(function (el) {
          var match = value === 'All' || el.getAttribute('data-topic') === value;
          el.hidden = !match;
          if (match) {
            shown++;
            // publication groups contain several papers; count the papers
            var inner = el.querySelectorAll('.paper').length;
            count += inner || 1;
          }
        });
        if (empty) empty.hidden = shown > 0;
        var label = value === 'All' ? 'all topics' : value;
        status.textContent = 'Showing ' + count + ' ' + noun + (count === 1 ? '' : 's') + ' in ' + label + '.';
      });
    });
  });

  // Pause / play the hero wave animation (WCAG 2.2.2). Starts paused when the
  // visitor's system asks for reduced motion.
  document.querySelectorAll('[data-motion]').forEach(function (btn) {
    var strata = btn.parentNode.querySelector('.strata');
    if (!strata) return;
    function set(paused) {
      strata.classList.toggle('is-paused', paused);
      btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
      btn.textContent = paused ? 'Play animation' : 'Pause animation';
    }
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    set(!!reduce);
    btn.addEventListener('click', function () { set(!strata.classList.contains('is-paused')); });
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

  // Quote links can preselect the study type: contact.html?study=PK%2FPD#form
  (function () {
    var select = document.querySelector('select[name="study_type"]');
    if (!select || !window.URLSearchParams) return;
    var want = new URLSearchParams(window.location.search).get('study');
    if (!want) return;
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].text === want) { select.selectedIndex = i; break; }
    }
  })();

  // Motion: scroll reveals, count-ups and contour lines that draw in.
  // Everything here is decorative and skipped entirely when the visitor
  // prefers reduced motion, or the browser lacks IntersectionObserver.
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduce || !('IntersectionObserver' in window)) return;
  var root = document.documentElement;
  root.classList.add('has-motion');

  var REVEAL = [
    '.sec-head', '.models > li', '.svc-tile', '.step', '.mid-cta', '.pubs > div', '.paper-list > li',
    '.people > div', '.team > *', '.faq > div', '.band', '.cta .wrap', '.val', '.leader', '.facts > *',
    '.story > *', '.photo', '.person', '.group-photo', 'article.svc', '.end', '.crew', '.perk', '.post',
    '.feature', '.group', '.research .paper', '.author', '.summary', '.inline-cta', '.map-card', '.building'
  ].join(',');
  var items = Array.prototype.slice.call(document.querySelectorAll('main ' + REVEAL.split(',').join(', main ')));
  items.forEach(function (el) {
    el.classList.add('reveal');
    // stagger siblings in the same row or list
    var i = 0, prev = el.previousElementSibling;
    while (prev && i < 4) { if (prev.classList.contains('reveal')) i++; prev = prev.previousElementSibling; }
    el.style.setProperty('--d', (i * 0.08) + 's');
  });

  // Decorative lines draw from left to right
  document.querySelectorAll('.band-lines path, .mid-lines path, .cta > svg path, .contour path').forEach(function (p) {
    p.setAttribute('pathLength', '1');
    p.classList.add('draw');
  });

  function countUp(el) {
    var end = parseInt(el.getAttribute('data-count'), 10), t0 = null, dur = 1200;
    if (!end) return;
    el.setAttribute('aria-hidden', 'true');
    var label = el.closest('dt') || el.parentNode;
    if (label && !label.hasAttribute('aria-label')) label.setAttribute('aria-label', label.textContent.trim());
    function step(t) {
      if (t0 === null) t0 = t;
      var k = Math.min(1, (t - t0) / dur), eased = 1 - Math.pow(1 - k, 3);
      el.textContent = Math.round(end * eased);
      if (k < 1) requestAnimationFrame(step);
    }
    el.textContent = '0';
    requestAnimationFrame(step);
  }

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var el = e.target;
      el.classList.add('is-in');
      if (el.hasAttribute('data-count')) countUp(el);
      io.unobserve(el);
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

  items.forEach(function (el) { io.observe(el); });
  document.querySelectorAll('[data-count]').forEach(function (el) { io.observe(el); });
  document.querySelectorAll('.band, .mid-cta, .cta').forEach(function (el) { if (items.indexOf(el) < 0) io.observe(el); });

  // Never leave keyboard focus on something still invisible
  document.addEventListener('focusin', function (e) {
    var r = e.target.closest && e.target.closest('.reveal');
    if (r) r.classList.add('is-in');
  });
})();
