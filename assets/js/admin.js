/* Peakilo admin interactions. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var csrf = ($('meta[name="csrf-token"]') || {}).content || '';

  /* ---------- Toast ---------- */
  var tt;
  function toast(msg) {
    var t = $('[data-toast]');
    if (!t) return;
    t.querySelector('span').textContent = msg;
    t.classList.add('is-on');
    clearTimeout(tt);
    tt = setTimeout(function () { t.classList.remove('is-on'); }, 2600);
  }
  window.pkToast = toast;

  /* ---------- Sidebar (mobile) ---------- */
  var sbBtn = $('[data-sb-toggle]');
  if (sbBtn) {
    sbBtn.addEventListener('click', function () { document.body.classList.toggle('sb-open'); });
    var scrim = $('[data-scrim]');
    if (scrim) scrim.addEventListener('click', function () { document.body.classList.remove('sb-open'); });
  }

  /* ---------- Copy ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var text = btn.getAttribute('data-copy');
    var done = function () { toast(btn.getAttribute('data-copied') || 'Copied to clipboard'); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done);
    else { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
  });

  /* ---------- Confirm ---------- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var submitter = e.submitter;
    var msg = (submitter && submitter.getAttribute('data-confirm')) || f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  }, true);

  /* ---------- Filters toggle ---------- */
  var ft = $('[data-toggle-filters]');
  if (ft) {
    ft.addEventListener('click', function () {
      var more = $('[data-more-filters]');
      more.hidden = !more.hidden;
      ft.setAttribute('aria-expanded', more.hidden ? 'false' : 'true');
    });
  }

  /* ---------- Bulk select ---------- */
  var bulk = $('[data-bulk]');
  if (bulk) {
    var all = $('[data-check-all]', bulk);
    var rows = $$('[data-row-check]', bulk);
    var count = $('[data-selected-count]', bulk);
    var apply = $('[data-bulk-apply]', bulk);
    var action = bulk.querySelector('select[name=action]');
    var sync = function () {
      var n = rows.filter(function (r) { return r.checked; }).length;
      count.textContent = n ? n + ' selected' : 'Select all';
      apply.disabled = !n || !action.value;
      all.checked = n && n === rows.length;
      all.indeterminate = n > 0 && n < rows.length;
    };
    all.addEventListener('change', function () { rows.forEach(function (r) { r.checked = all.checked; }); sync(); });
    rows.forEach(function (r) { r.addEventListener('change', sync); });
    action.addEventListener('change', sync);
    bulk.addEventListener('submit', function (e) {
      if (action.value === 'delete' && !confirm('Delete the selected influencers permanently? Their photos and applications are removed too.')) e.preventDefault();
    });
    sync();
  }

  /* ---------- Application status (inline) ---------- */
  $$('[data-app-status]').forEach(function (form) {
    var sel = form.querySelector('select');
    var paint = function () { sel.className = 'select app-status badge no-dot b-' + sel.value; };
    paint();
    sel.addEventListener('change', function () {
      paint();
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { toast(d.ok ? 'Moved to ' + d.label : (d.error || 'Could not update')); })
        .catch(function () { form.submit(); });
    });
  });

  /* ---------- Campaign form ---------- */
  var cf = $('[data-campaign-form]');
  if (cf) {
    var redirectField = $('[data-redirect-field]', cf);
    $$('input[name=mode]', cf).forEach(function (r) {
      r.addEventListener('change', function () { redirectField.hidden = r.value !== 'url' || !r.checked; if (r.value === 'url' && r.checked) redirectField.querySelector('input').focus(); });
    });
    var src = $('[data-code-source]', cf), code = $('[data-code-input]', cf);
    if (src && code && code.dataset.auto) {
      var touched = code.value !== '';
      code.addEventListener('input', function () { touched = true; });
      src.addEventListener('input', function () {
        if (touched) return;
        code.value = src.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 36);
      });
    }
  }

  /* ---------- Cover image preview ---------- */
  $$('[data-cover-drop]').forEach(function (drop) {
    var input = $('[data-cover-input]', drop);
    input.addEventListener('change', function () {
      var f = input.files[0];
      if (!f) return;
      var img = drop.querySelector('img') || drop.insertBefore(document.createElement('img'), drop.firstChild);
      img.src = URL.createObjectURL(f);
      img.alt = '';
    });
  });

  /* ---------- Category edit ---------- */
  var catForm = $('[data-cat-form]');
  if (catForm) {
    var reset = $('[data-cat-reset]', catForm);
    $$('[data-edit-cat]').forEach(function (b) {
      b.addEventListener('click', function () {
        var d = JSON.parse(b.getAttribute('data-edit-cat'));
        $('[data-cat-id]', catForm).value = d.id;
        catForm.name.value = d.name;
        catForm.slug.value = d.slug;
        catForm.description.value = d.description || '';
        $('[data-cat-heading]', catForm).textContent = 'Edit category';
        $('[data-cat-submit]', catForm).textContent = 'Save category';
        reset.hidden = false;
        catForm.name.focus();
      });
    });
    reset.addEventListener('click', function () {
      catForm.reset();
      $('[data-cat-id]', catForm).value = '';
      $('[data-cat-heading]', catForm).textContent = 'Add a category';
      $('[data-cat-submit]', catForm).textContent = 'Add category';
      reset.hidden = true;
    });
  }

  /* ---------- Charts (SVG, no library) ---------- */
  var NS = 'http://www.w3.org/2000/svg';
  function el(name, attrs, parent) {
    var n = document.createElementNS(NS, name);
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (parent) parent.appendChild(n);
    return n;
  }
  function drawChart(box) {
    var cfg;
    try { cfg = JSON.parse(box.getAttribute('data-chart')); } catch (e) { return; }
    box.innerHTML = '';
    var W = box.clientWidth || 600, H = box.clientHeight || 240;
    var pad = { l: 32, r: 10, t: 12, b: 26 };
    var iw = W - pad.l - pad.r, ih = H - pad.t - pad.b;
    var n = cfg.labels.length;
    var max = 1;
    cfg.series.forEach(function (s) { s.data.forEach(function (v) { if (v > max) max = v; }); });
    var step = Math.max(1, Math.ceil(max / 4));
    max = step * 4;
    var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: W, height: H, role: 'img', 'aria-label': cfg.series.map(function (s) { return s.name; }).join(' and ') + ' chart' }, box);
    var defs = el('defs', {}, svg);
    var grad = el('linearGradient', { id: 'adminGrad', x1: 0, x2: 0, y1: 0, y2: 1 }, defs);
    el('stop', { offset: '0', 'stop-color': '#EF3B2A', 'stop-opacity': '.22' }, grad);
    el('stop', { offset: '1', 'stop-color': '#EF3B2A', 'stop-opacity': '0' }, grad);
    for (var g = 0; g <= 4; g++) {
      var y = pad.t + ih - (g / 4) * ih;
      el('line', { x1: pad.l, x2: W - pad.r, y1: y, y2: y, class: 'grid-line' }, svg);
      var tl = el('text', { x: pad.l - 8, y: y + 4, 'text-anchor': 'end', class: 'axis-label' }, svg);
      tl.textContent = step * g;
    }
    var x = function (i) { return pad.l + (n === 1 ? iw / 2 : (i / (n - 1)) * iw); };
    var yv = function (v) { return pad.t + ih - (v / max) * ih; };
    var every = Math.ceil(n / 7);
    cfg.labels.forEach(function (lab, i) {
      if (i % every !== 0 && i !== n - 1) return;
      var t = el('text', { x: x(i), y: H - 6, 'text-anchor': 'middle', class: 'axis-label' }, svg);
      t.textContent = lab;
    });
    cfg.series.forEach(function (s, si) {
      var d = s.data.map(function (v, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + yv(v).toFixed(1); }).join(' ');
      if (si === 0) el('path', { d: d + ' L' + x(n - 1) + ' ' + (pad.t + ih) + ' L' + x(0) + ' ' + (pad.t + ih) + ' Z', class: 'area' }, svg);
      var p = el('path', { d: d, class: s.style === 'dashed' ? 'line-2' : 'line' }, svg);
      if (s.style !== 'dashed' && p.getTotalLength && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var len = p.getTotalLength();
        p.style.strokeDasharray = len;
        p.style.strokeDashoffset = len;
        p.getBoundingClientRect();
        p.style.transition = 'stroke-dashoffset 1.4s cubic-bezier(.16,1,.3,1)';
        p.style.strokeDashoffset = 0;
      }
    });
    var hl = el('line', { y1: pad.t, y2: pad.t + ih, class: 'hover-line', opacity: 0 }, svg);
    var dot = el('circle', { r: 5, class: 'dot', opacity: 0 }, svg);
    var tip = document.createElement('div');
    tip.className = 'chart-tip';
    box.appendChild(tip);
    svg.addEventListener('pointermove', function (e) {
      var r = svg.getBoundingClientRect();
      var px = (e.clientX - r.left) * (W / r.width);
      var i = Math.max(0, Math.min(n - 1, Math.round((px - pad.l) / (iw / Math.max(1, n - 1)))));
      hl.setAttribute('x1', x(i)); hl.setAttribute('x2', x(i)); hl.setAttribute('opacity', 1);
      dot.setAttribute('cx', x(i)); dot.setAttribute('cy', yv(cfg.series[0].data[i])); dot.setAttribute('opacity', 1);
      tip.innerHTML = '<b>' + cfg.labels[i] + '</b><br>' + cfg.series.map(function (s) { return s.name + ': <b>' + s.data[i] + '</b>'; }).join('<br>');
      tip.style.left = (x(i) / W * r.width) + 'px';
      tip.style.top = (yv(cfg.series[0].data[i]) / H * r.height) + 'px';
      tip.style.opacity = 1;
    });
    svg.addEventListener('pointerleave', function () { hl.setAttribute('opacity', 0); dot.setAttribute('opacity', 0); tip.style.opacity = 0; });
  }
  var charts = $$('[data-chart]');
  charts.forEach(drawChart);
  if (charts.length && 'ResizeObserver' in window) {
    var lastW = new WeakMap();
    var ro = new ResizeObserver(function (entries) {
      entries.forEach(function (en) {
        var w = Math.round(en.contentRect.width);
        if (lastW.get(en.target) !== w) { lastW.set(en.target, w); drawChart(en.target); }
      });
    });
    charts.forEach(function (c) { lastW.set(c, c.clientWidth); ro.observe(c); });
  }

  window.pkCsrf = csrf;
})();
