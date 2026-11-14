/* «Бильярд Старт» — admin enhancements. The admin works without JS; this only improves it. */
(function () {
  'use strict';
  var DAY = 1440, POLL = 30000;

  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  // Timeline: position bars/hour marks from minutes (CSP-safe: CSSOM, no inline style attributes).
  function layout(root) {
    $$('[data-tl-left]', root).forEach(function (el) {
      el.style.left = (Number(el.dataset.tlLeft) / DAY * 100) + '%';
      if (el.dataset.tlWidth) el.style.width = (Number(el.dataset.tlWidth) / DAY * 100) + '%';
    });
  }

  // Confirmation for destructive actions.
  document.addEventListener('submit', function (e) {
    var msg = e.target && e.target.dataset ? e.target.dataset.confirm : null;
    if (msg && !window.confirm(msg)) e.preventDefault();
  }, true);

  // Date pickers that apply immediately.
  $$('[data-autosubmit]').forEach(function (inp) {
    inp.addEventListener('change', function () { if (inp.value) inp.form.submit(); });
  });

  // Phone mask +996 (XXX) XXX-XXX
  function digits(v) {
    var i = v.indexOf('+996');
    if (i !== -1) v = v.slice(0, i) + v.slice(i + 4);
    var d = v.replace(/\D/g, '');
    if (i === -1 && d.length > 9) {
      if (d.indexOf('996') === 0) d = d.slice(3);
      else if (d.charAt(0) === '0') d = d.slice(1);
    }
    return d.slice(0, 9);
  }
  function mask(v) {
    var d = digits(v), o = '+996';
    if (d.length > 0) o += ' (' + d.slice(0, 3);
    if (d.length >= 3) o += ')';
    if (d.length > 3) o += ' ' + d.slice(3, 6);
    if (d.length > 6) o += '-' + d.slice(6, 9);
    return o;
  }
  $$('[data-phone-mask]').forEach(function (inp) {
    inp.addEventListener('input', function () { inp.value = mask(inp.value); });
  });

  // Live refresh of the #live region every 30 s (statuses, remaining time, lists).
  var live = document.getElementById('live');
  layout(document);
  if (live && window.fetch && window.DOMParser) {
    setInterval(function () {
      if (document.visibilityState !== 'visible') return;
      if (live.contains(document.activeElement) && document.activeElement !== document.body) return;
      fetch(window.location.href, { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) {
          if (r.redirected && /login\.php/.test(r.url)) { window.location.reload(); return null; }
          return r.ok ? r.text() : null;
        })
        .then(function (html) {
          if (!html) return;
          var fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('live');
          if (!fresh) return;
          live.innerHTML = fresh.innerHTML;
          layout(live);
        })
        .catch(function () { /* offline: try again on the next tick */ });
    }, POLL);
  }
})();
