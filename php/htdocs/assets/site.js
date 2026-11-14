/* «Бильярд Старт» — public site. Vanilla JS, no dependencies. */
(function () {
  'use strict';

  var body = document.body;
  var BASE = body.dataset.base || '/';
  var WA = body.dataset.wa || '996506056556';
  var SERVER_TODAY = body.dataset.today;
  var SLOT = 30, LAST_START = 24 * 60 - SLOT, MIN_H = 1, MAX_H = 5, POLL = 30000, PAST_GRACE = 25;
  var OFFSET = 6 * 3600 * 1000; // Asia/Bishkek = UTC+6, no DST

  /* ------------------------------ helpers ------------------------------ */
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function localParts(ms) {
    var d = new Date(ms + OFFSET);
    return { date: d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()), minutes: d.getUTCHours() * 60 + d.getUTCMinutes() };
  }
  function toUtc(date, minutes) {
    var p = date.split('-').map(Number);
    return Date.UTC(p[0], p[1] - 1, p[2]) + minutes * 60000 - OFFSET;
  }
  function addDays(date, n) { return localParts(toUtc(date, 12 * 60) + n * 86400000).date; }
  function today() { var t = localParts(Date.now()).date; return t > SERVER_TODAY ? t : SERVER_TODAY; }
  function minutesFrom(date, ms) { return Math.round((ms - toUtc(date, 0)) / 60000); }
  function label(m) { m = ((m % 1440) + 1440) % 1440; return pad(Math.floor(m / 60)) + ':' + pad(m % 60); }
  function fmtTime(iso) { return label(localParts(Date.parse(iso)).minutes); }
  var WD = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
  var MON = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
  function human(date) {
    var p = date.split('-').map(Number);
    return p[2] + ' ' + MON[p[1] - 1] + ', ' + WD[new Date(Date.UTC(p[0], p[1] - 1, p[2])).getUTCDay()];
  }
  function waLink(text) { return 'https://wa.me/' + WA + (text ? '?text=' + encodeURIComponent(text) : ''); }
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  /* ------------------------------ phone mask ------------------------------ */
  function phoneDigits(input) {
    var s = input, idx = s.indexOf('+996');
    if (idx !== -1) s = s.slice(0, idx) + s.slice(idx + 4);
    var d = s.replace(/\D/g, '');
    if (idx === -1 && d.length > 9) { if (d.indexOf('996') === 0) d = d.slice(3); else if (d[0] === '0') d = d.slice(1); }
    return d.slice(0, 9);
  }
  function phoneMask(input) {
    var d = phoneDigits(input), out = '+996';
    if (d.length > 0) out += ' (' + d.slice(0, 3);
    if (d.length >= 3) out += ')';
    if (d.length > 3) out += ' ' + d.slice(3, 6);
    if (d.length > 6) out += '-' + d.slice(6, 9);
    return out;
  }
  $$('[data-phone-mask]').forEach(function (inp) {
    inp.addEventListener('input', function () { inp.value = phoneMask(inp.value); });
    inp.addEventListener('focus', function () { requestAnimationFrame(function () { inp.setSelectionRange(inp.value.length, inp.value.length); }); });
  });

  /* ------------------------------ reveal on scroll ------------------------------ */
  var reveals = $$('.reveal');
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var d = Number(en.target.dataset.delay || 0);
        if (d) en.target.style.transitionDelay = (d * 60) + 'ms';
        en.target.classList.add('is-visible');
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -60px 0px' });
    reveals.forEach(function (r) { io.observe(r); });
  } else {
    reveals.forEach(function (r) { r.classList.add('is-visible'); });
  }

  /* ------------------------------ polling helper ------------------------------ */
  function poll(urlFn, onData) {
    var timer = null, ctrl = null;
    function load() {
      var url = urlFn();
      if (!url) return;
      if (ctrl) ctrl.abort();
      ctrl = 'AbortController' in window ? new AbortController() : null;
      fetch(url, { cache: 'no-store', credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(onData)
        .catch(function (e) { if (e.name !== 'AbortError') onData(null); });
    }
    function start() { clearInterval(timer); load(); timer = setInterval(load, POLL); }
    document.addEventListener('visibilitychange', function () {
      clearInterval(timer);
      if (document.visibilityState === 'visible') start();
    });
    start();
    return { reload: load };
  }

  /* ------------------------------ live table statuses ------------------------------ */
  var BADGE = {
    free: ['bg-emerald-400/10 border-emerald-400/20 text-emerald-300', 'bg-emerald-400'],
    busy: ['bg-red-500/10 border-red-500/25 text-red-300', 'bg-red-500 animate-pulse-dot'],
    soon: ['bg-amber-400/10 border-amber-400/25 text-amber-200', 'bg-amber-400 animate-pulse-dot']
  };
  function statusLabel(t) {
    if (t.state === 'busy' && t.busyUntil) return 'Занят до ' + fmtTime(t.busyUntil);
    if (t.state === 'soon' && t.nextStart) return 'Бронь с ' + fmtTime(t.nextStart);
    return 'Свободен';
  }
  var freeCountEl = $('#free-count');
  if ($('#tables-list')) {
    poll(function () { return BASE + 'api/status.php'; }, function (data) {
      if (!data || !data.tables) {
        if (freeCountEl && !$('#tables-list [data-status-for] span')) freeCountEl.textContent = 'Статусы временно недоступны';
        return;
      }
      var free = 0;
      data.tables.forEach(function (t) {
        if (t.state !== 'busy') free++;
        var holder = $('[data-status-for="' + t.id + '"]');
        if (!holder) return;
        var b = BADGE[t.state] || BADGE.free;
        var pill = el('span', 'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold ' + b[0]);
        var dot = el('span', 'h-2 w-2 rounded-full ' + b[1]);
        dot.setAttribute('aria-hidden', 'true');
        pill.appendChild(dot);
        pill.appendChild(document.createTextNode(statusLabel(t)));
        holder.replaceChildren(pill);
      });
      if (freeCountEl) {
        freeCountEl.replaceChildren(document.createTextNode('Сейчас свободно: '), el('span', 'font-bold text-white', String(free)), document.createTextNode(' из ' + data.tables.length));
      }
    });
  }

  /* ------------------------------ booking form ------------------------------ */
  var form = $('#booking-form');
  if (form) {
    var state = { table: 't1', capacity: 6, date: today(), start: null, duration: 2, busy: [] };
    var slotsBox = $('#bf-slots'), dateInp = $('#bf-date'), guestsInp = $('#bf-guests');

    dateInp.value = state.date;
    dateInp.min = today();
    dateInp.max = addDays(today(), 60);

    function setChecked(container, attr, value) {
      $$('[' + attr + ']', container).forEach(function (b) { b.setAttribute('aria-checked', String(b.getAttribute(attr) === String(value))); });
    }
    function busyMinutes() {
      return state.busy.filter(function (b) { return b.tableId === state.table; })
        .map(function (b) { return [minutesFrom(state.date, Date.parse(b.start)), minutesFrom(state.date, Date.parse(b.end))]; });
    }
    function overlaps(busy, s, e) { return busy.some(function (r) { return s < r[1] && e > r[0]; }); }
    function nowMin() {
      var t = today();
      if (state.date === t) return minutesFrom(state.date, Date.now());
      return state.date < t ? Infinity : -Infinity;
    }

    function render() {
      var busy = busyMinutes(), nm = nowMin(), free = 0;
      var disabledFor = function (s) { return s < nm - PAST_GRACE || overlaps(busy, s, s + state.duration * 60); };
      if (state.start !== null && disabledFor(state.start)) state.start = null;

      slotsBox.replaceChildren();
      for (var s = 0; s <= LAST_START; s += SLOT) {
        var dis = disabledFor(s), taken = overlaps(busy, s, s + SLOT);
        if (!dis) free++;
        var b = el('button', 'chip tabular-nums', label(s));
        b.type = 'button';
        b.setAttribute('role', 'radio');
        b.setAttribute('aria-checked', String(state.start === s));
        b.setAttribute('aria-label', label(s) + (taken ? ', занято' : dis ? ', недоступно' : ''));
        if (taken) b.title = 'Занято';
        b.disabled = dis;
        b.dataset.slot = String(s);
        slotsBox.appendChild(b);
      }
      $('#bf-free').textContent = String(free);
      $('#bf-no-slots').classList.toggle('hidden', free !== 0);

      $$('[data-duration]').forEach(function (b) {
        var d = Number(b.dataset.duration);
        b.disabled = state.start !== null && overlaps(busy, state.start, state.start + d * 60);
      });
      setChecked($('#bf-durations'), 'data-duration', state.duration);
      setChecked($('#bf-tables'), 'data-table', state.table);

      guestsInp.max = String(state.capacity);
      $('#bf-capacity').textContent = String(state.capacity);
      if (Number(guestsInp.value) > state.capacity) guestsInp.value = String(state.capacity);
      $('[data-guests="-1"]').disabled = Number(guestsInp.value) <= 1;
      $('[data-guests="1"]').disabled = Number(guestsInp.value) >= state.capacity;

      $('#bf-date-human').textContent = human(state.date);
      var tableName = $('[data-table="' + state.table + '"]').textContent;
      $('#bf-summary-title').textContent = tableName + ' · ' + human(state.date);
      $('#bf-summary-time').textContent = state.start !== null ? label(state.start) + ' – ' + label(state.start + state.duration * 60) : '—:— – —:—';
    }

    var avail = poll(function () { return BASE + 'api/availability.php?date=' + encodeURIComponent(state.date); }, function (data) {
      if (data && data.busy) { state.busy = data.busy; render(); }
    });
    setInterval(render, 30000); // past slots expire as time goes on

    $('#bf-tables').addEventListener('click', function (e) {
      var b = e.target.closest('[data-table]');
      if (!b) return;
      state.table = b.dataset.table;
      state.capacity = Number(b.dataset.capacity);
      render();
    });
    $('#bf-durations').addEventListener('click', function (e) {
      var b = e.target.closest('[data-duration]');
      if (!b || b.disabled) return;
      state.duration = Number(b.dataset.duration);
      render();
    });
    slotsBox.addEventListener('click', function (e) {
      var b = e.target.closest('[data-slot]');
      if (!b || b.disabled) return;
      state.start = Number(b.dataset.slot);
      showError('startMinutes', null);
      render();
    });
    dateInp.addEventListener('change', function () {
      if (!dateInp.value) return;
      state.date = dateInp.value;
      state.busy = [];
      render();
      avail.reload();
    });
    $$('[data-guests]').forEach(function (b) {
      b.addEventListener('click', function () {
        var v = Math.max(1, Math.min(state.capacity, Number(guestsInp.value || 1) + Number(b.dataset.guests)));
        guestsInp.value = String(v);
        render();
      });
    });
    guestsInp.addEventListener('change', function () {
      guestsInp.value = String(Math.max(1, Math.min(state.capacity, parseInt(guestsInp.value, 10) || 1)));
      render();
    });

    // Table cards in the "Наши столы" section pre-select a table here.
    $$('[data-pick-table]').forEach(function (card) {
      card.addEventListener('click', function () {
        var b = $('[data-table="' + card.dataset.pickTable + '"]');
        if (b) b.click();
        $('#booking').scrollIntoView({ behavior: 'smooth' });
      });
    });

    function showError(field, msg, root) {
      var p = $('[data-error-for="' + field + '"]', root || form);
      if (!p) return;
      p.textContent = msg || '';
      p.classList.toggle('hidden', !msg);
      var input = $('[name="' + field + '"]', root || form);
      if (input) { if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid'); }
    }
    function clearErrors() { $$('[data-error-for]', form).forEach(function (p) { showError(p.dataset.errorFor, null); }); formError(null); }
    function formError(msg) { var p = $('#bf-form-error'); p.textContent = msg || ''; p.classList.toggle('hidden', !msg); }

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      clearErrors();
      var name = $('#bf-name').value.trim(), phone = $('#bf-phone').value, guests = Number(guestsInp.value);
      var errs = {};
      if (name.length < 2) errs.name = 'Укажите имя';
      if (phoneDigits(phone).length !== 9) errs.phone = 'Телефон в формате +996 XXX XXX XXX';
      if (state.start === null) errs.startMinutes = 'Выберите время начала';
      if (guests < 1 || guests > state.capacity) errs.guests = 'От 1 до ' + state.capacity + ' гостей';
      var keys = Object.keys(errs);
      if (keys.length) {
        keys.forEach(function (k) { showError(k, errs[k]); });
        var first = keys[0] === 'startMinutes' ? $('#bf-start-legend') : $('[name="' + keys[0] + '"]', form);
        if (first) first.focus();
        return;
      }
      var btn = $('#bf-submit');
      btn.disabled = true;
      btn.textContent = 'Сохраняем…';
      var payload = { name: name, phone: phone, tableId: state.table, date: state.date, startMinutes: state.start, durationHours: state.duration, guests: guests, website: $('#bf-website').value };
      var comment = $('#bf-comment').value.trim();
      if (comment) payload.comment = comment;
      fetch(BASE + 'api/booking.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, ok: r.ok, json: j }; }); })
        .then(function (res) {
          if (!res.ok || !res.json.ok) {
            var f = res.json.fields || {};
            Object.keys(f).forEach(function (k) { showError(k, f[k]); });
            formError(res.json.error || 'Не удалось создать бронь. Попробуйте ещё раз.');
            if (res.status === 409) avail.reload();
            return;
          }
          $('#success-id').textContent = String(res.json.id);
          $('#success-wa').href = res.json.waUrl;
          form.classList.add('hidden');
          var ok = $('#booking-success');
          ok.classList.remove('hidden');
          ok.classList.add('flex');
          ok.scrollIntoView({ behavior: 'smooth', block: 'center' });
          avail.reload();
          setTimeout(function () { window.location.href = res.json.waUrl; }, 900);
        })
        .catch(function () { formError('Нет соединения с сервером. Проверьте интернет.'); })
        .then(function () { btn.disabled = false; btn.textContent = 'Забронировать и отправить в WhatsApp'; });
    });

    $('#booking-again').addEventListener('click', function () {
      $('#booking-success').classList.add('hidden');
      $('#booking-success').classList.remove('flex');
      form.classList.remove('hidden');
      state.start = null;
      $('#bf-comment').value = '';
      render();
    });

    render();
  }

  /* ------------------------------ quick contact ------------------------------ */
  var quick = $('#quick-form');
  if (quick) {
    quick.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var name = $('#qc-name').value.replace(/[<>]/g, '').trim();
      var phone = $('#qc-phone').value;
      var msg = $('#qc-message').value.replace(/[<>]/g, '').trim();
      var errs = {};
      if (name.length < 2) errs.name = 'Укажите имя';
      else if (!/^[\p{L}\p{M}\s'’.\-]+$/u.test(name)) errs.name = 'Имя может содержать только буквы';
      if (phoneDigits(phone).length !== 9) errs.phone = 'Телефон в формате +996 XXX XXX XXX';
      if (msg.length < 2) errs.message = 'Напишите сообщение';
      ['name', 'phone', 'message'].forEach(function (k) {
        var p = $('[data-error-for="' + k + '"]', quick);
        p.textContent = errs[k] || '';
        p.classList.toggle('hidden', !errs[k]);
        var inp = $('#qc-' + k);
        if (errs[k]) inp.setAttribute('aria-invalid', 'true'); else inp.removeAttribute('aria-invalid');
      });
      var first = Object.keys(errs)[0];
      if (first) { $('#qc-' + first).focus(); return; }
      var text = 'Здравствуйте! Меня зовут ' + name + '.\nТелефон: +996' + phoneDigits(phone) + '\n\n' + msg;
      var w = window.open(waLink(text), '_blank');
      if (w) w.opener = null; else window.location.href = waLink(text);
    });
  }

  /* ------------------------------ sticky mobile CTA ------------------------------ */
  var cta = $('#mobile-cta'), bookingSec = $('#booking');
  if (cta) {
    var inBooking = false;
    var update = function () {
      var show = window.scrollY > window.innerHeight * 0.8 && !inBooking;
      cta.classList.toggle('translate-y-full', !show);
      cta.setAttribute('aria-hidden', String(!show));
      $$('a', cta).forEach(function (a) { a.tabIndex = show ? 0 : -1; });
    };
    if (bookingSec && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { inBooking = en[0].isIntersecting; update(); }, { threshold: 0.05 }).observe(bookingSec);
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
  }
})();
