/* Creator registration: multi-step form, live preview card, photo
   drag and drop, channel toggles and a dual fee range slider.
   Without JavaScript the form still works as one long page. */
(function () {
  'use strict';
  var form = document.querySelector('[data-join]');
  if (!form) return;

  var $ = function (s, r) { return (r || form).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || form).querySelectorAll(s)); };
  var toast = window.pkToast || function () {};
  var steps = $$('[data-jstep]');
  var navItems = Array.prototype.slice.call(document.querySelectorAll('[data-jsteps-nav] li'));
  var bar = document.querySelector('.jsteps-bar span');
  var jnav = $('[data-jnav]');
  var count = $('[data-jcount]');
  var current = 0, reached = 0;

  var SCALE = { USD: 1, INR: 85, EUR: 0.92, GBP: 0.8, AED: 3.7, AUD: 1.5, CAD: 1.37, SGD: 1.35, PKR: 280, BDT: 120, NGN: 1500 };
  var parseCount = function (v) {
    v = String(v || '').toLowerCase().replace(/[,\s]/g, '');
    var m = v.match(/^([\d.]+)([km])?$/);
    if (!m) return parseInt(v.replace(/\D/g, ''), 10) || 0;
    var n = parseFloat(m[1]);
    if (m[2] === 'k') n *= 1e3;
    if (m[2] === 'm') n *= 1e6;
    return Math.round(n);
  };
  var compact = function (n) {
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
    return String(n);
  };
  var money = function (n) {
    if (n >= 1e5) return compact(n);
    return Math.round(n).toLocaleString('en-US');
  };

  /* ---------- Steps ---------- */
  form.classList.add('is-stepped');
  jnav.hidden = false;

  function go(i, focus) {
    current = Math.max(0, Math.min(steps.length - 1, i));
    reached = Math.max(reached, current);
    steps.forEach(function (s, n) { s.classList.toggle('is-current', n === current); });
    navItems.forEach(function (li, n) {
      li.classList.toggle('is-current', n === current);
      li.classList.toggle('is-done', n < current);
      li.style.cursor = n <= reached ? 'pointer' : '';
    });
    if (bar) bar.style.setProperty('--p', ((current + 1) / steps.length).toFixed(3));
    form.classList.toggle('is-first', current === 0);
    form.classList.toggle('is-last', current === steps.length - 1);
    if (count) count.textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
    if (focus) {
      var top = form.getBoundingClientRect().top + window.pageYOffset - 110;
      if (window.pageYOffset > top) window.scrollTo({ top: top, behavior: 'smooth' });
      var first = steps[current].querySelector('input:not([type=hidden]):not([disabled]), select, textarea');
      if (first && first.type !== 'file') setTimeout(function () { first.focus({ preventScroll: true }); }, 250);
    }
  }

  function setError(field, msg) {
    var wrap = field.closest('.field') || field.closest('.channels') || field.closest('.check') || field;
    var holder = wrap.classList.contains('check') ? wrap.parentNode : wrap;
    var existing = holder.querySelector(':scope > .field-error[data-client]');
    if (!msg) {
      if (existing) existing.remove();
      wrap.classList.remove('has-error');
      return;
    }
    wrap.classList.add('has-error');
    if (!existing) {
      existing = document.createElement('p');
      existing.className = 'field-error';
      existing.setAttribute('data-client', '');
      if (wrap.classList.contains('check')) wrap.insertAdjacentElement('afterend', existing);
      else holder.appendChild(existing);
    }
    existing.innerHTML = '<i class="ph-bold ph-warning-circle"></i>' + msg;
  }

  function validate(i) {
    var ok = true;
    var step = steps[i];
    var check = function (cond, el, msg) { setError(el, cond ? '' : msg); if (!cond) ok = false; };
    if (i === 0) {
      var name = $('[name=full_name]'), email = $('[name=email]'), country = $('[name=country]'), photo = $('[data-photo-input]');
      check(name.value.trim().length >= 2, name, 'Please enter your full name.');
      check(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim()), email, 'Enter a valid email address.');
      check(country.value.trim() !== '', country, 'Tell us which country you are based in.');
      var hasServerPhotoOk = !photo.closest('.field').querySelector('.field-error:not([data-client])') && form.dataset.photoOptional === '1';
      check(photo.files.length > 0 || hasServerPhotoOk, photo, 'Please add a profile photo.');
    }
    if (i === 1) {
      var any = $$('[data-channel]').some(function (c) { var inp = c.querySelector('[data-preview-handle]'); return !inp.disabled && inp.value.trim() !== ''; });
      check(any, $('[data-channels]'), 'Add at least one social channel.');
    }
    if (i === 2) {
      var niches = $$('[name="niches[]"]:checked').length;
      check(niches > 0, $('[data-niches]'), 'Pick at least one niche.');
      var mn = $('[data-fee-min]'), mx = $('[data-fee-max]');
      var a = parseFloat(mn.value.replace(/[^\d.]/g, '')), b = parseFloat(mx.value.replace(/[^\d.]/g, ''));
      check(!isNaN(a), mn, 'Enter your minimum fee.');
      check(!isNaN(b) && (isNaN(a) || b >= a), mx, isNaN(b) ? 'Enter your maximum fee.' : 'Maximum fee should be equal to or higher than the minimum.');
    }
    if (i === 3) {
      var terms = $('[name=consent_terms]');
      check(terms.checked, terms.closest('.check'), 'Please agree to the terms and privacy policy.');
    }
    if (!ok) {
      var firstErr = step.querySelector('.has-error input, .has-error select, .has-error textarea, .has-error');
      if (firstErr && firstErr.focus) firstErr.focus({ preventScroll: false });
    }
    return ok;
  }

  $('[data-jnext]').addEventListener('click', function () { if (validate(current)) go(current + 1, true); });
  $('[data-jprev]').addEventListener('click', function () { go(current - 1, true); });
  navItems.forEach(function (li, n) {
    li.addEventListener('click', function () { if (n <= reached && n !== current) go(n, true); });
  });
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'checkbox' && current < steps.length - 1) {
      e.preventDefault();
      if (validate(current)) go(current + 1, true);
    }
  });
  form.addEventListener('submit', function (e) {
    for (var i = 0; i < steps.length; i++) {
      if (!validate(i)) { e.preventDefault(); go(i, true); return; }
    }
    var btn = $('[data-submit]');
    btn.classList.add('is-loading');
    btn.innerHTML = 'Sending your profile...';
  });

  // Live clearing of client-side errors.
  form.addEventListener('input', function (e) {
    var wrap = e.target.closest('.has-error');
    if (wrap && wrap.querySelector('.field-error[data-client]')) setError(e.target, '');
  });

  /* ---------- Photo ---------- */
  var photoInput = $('[data-photo-input]');
  var dz = $('[data-dropzone]');
  var dzPrev = $('[data-photo-preview]');
  var pvPhoto = document.querySelector('[data-pv-photo]');
  var pvInitials = document.querySelector('[data-pv-initials]');
  function showPhoto(file) {
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { setError(photoInput, 'Please choose a JPG, PNG or WEBP image.'); photoInput.value = ''; return; }
    if (file.size > 5 * 1024 * 1024) { setError(photoInput, 'That image is over 5 MB. Please pick a smaller one.'); photoInput.value = ''; return; }
    setError(photoInput, '');
    var src = URL.createObjectURL(file);
    dzPrev.innerHTML = '<img alt="">';
    dzPrev.firstChild.src = src;
    if (pvPhoto) { pvPhoto.src = src; pvPhoto.hidden = false; pvInitials.hidden = true; }
    dz.querySelector('.dz-text b').textContent = file.name.length > 34 ? file.name.slice(0, 31) + '...' : file.name;
  }
  photoInput.addEventListener('change', function () { showPhoto(photoInput.files[0]); });
  ['dragenter', 'dragover'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('is-over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { dz.addEventListener(ev, function () { dz.classList.remove('is-over'); }); });
  dz.addEventListener('drop', function (e) {
    e.preventDefault();
    if (e.dataTransfer.files.length) {
      try { photoInput.files = e.dataTransfer.files; } catch (err) {}
      showPhoto(e.dataTransfer.files[0]);
    }
  });

  /* ---------- Channels ---------- */
  $$('[data-channel]').forEach(function (ch) {
    var btn = ch.querySelector('[data-channel-toggle]');
    var inputs = $$('input', ch);
    var set = function (on) {
      ch.classList.toggle('is-on', on);
      btn.setAttribute('aria-expanded', on ? 'true' : 'false');
      inputs.forEach(function (i) { i.disabled = !on; });
    };
    set(ch.classList.contains('is-on'));
    btn.addEventListener('click', function () {
      var on = !ch.classList.contains('is-on');
      set(on);
      if (on) inputs[0].focus();
      updatePreview();
    });
  });

  /* ---------- Niches (max 5) ---------- */
  var nicheWrap = $('[data-niches]');
  var maxN = parseInt(nicheWrap.dataset.max || '5', 10);
  nicheWrap.addEventListener('change', function (e) {
    var checked = $$('input:checked', nicheWrap);
    if (checked.length > maxN) { e.target.checked = false; toast('You can pick up to ' + maxN + ' niches'); }
    nicheWrap.classList.toggle('is-maxed', $$('input:checked', nicheWrap).length >= maxN);
  });
  nicheWrap.classList.toggle('is-maxed', $$('input:checked', nicheWrap).length >= maxN);

  /* ---------- Fee dual range ---------- */
  var feeMin = $('[data-fee-min]'), feeMax = $('[data-fee-max]');
  var drMin = $('[data-dr-min]'), drMax = $('[data-dr-max]');
  var dr = $('[data-dual-range]');
  var cur = $('[data-currency]');
  var scaleMax = function () { return 20000 * (SCALE[cur.value] || 1); };
  var niceRound = function (v) {
    if (v < 100) return Math.round(v / 5) * 5;
    if (v < 1000) return Math.round(v / 10) * 10;
    if (v < 10000) return Math.round(v / 50) * 50;
    return Math.round(v / 500) * 500;
  };
  var toVal = function (t) { return niceRound(scaleMax() * Math.pow(t / 100, 2.2)); };
  var toPos = function (v) { return Math.round(100 * Math.pow(Math.min(1, Math.max(0, v / scaleMax())), 1 / 2.2)); };
  var paint = function () {
    dr.style.setProperty('--a', drMin.value + '%');
    dr.style.setProperty('--b', drMax.value + '%');
  };
  var fromSliders = function (which) {
    var a = parseInt(drMin.value, 10), b = parseInt(drMax.value, 10);
    if (a > b) { if (which === 'min') drMax.value = a; else drMin.value = b; }
    feeMin.value = toVal(drMin.value);
    feeMax.value = toVal(drMax.value);
    paint();
    updatePreview();
  };
  var fromInputs = function () {
    var a = parseFloat(feeMin.value.replace(/[^\d.]/g, '')), b = parseFloat(feeMax.value.replace(/[^\d.]/g, ''));
    if (!isNaN(a)) drMin.value = toPos(a);
    if (!isNaN(b)) drMax.value = toPos(b);
    paint();
  };
  drMin.addEventListener('input', function () { fromSliders('min'); });
  drMax.addEventListener('input', function () { fromSliders('max'); });
  feeMin.addEventListener('input', fromInputs);
  feeMax.addEventListener('input', fromInputs);
  cur.addEventListener('change', function () {
    var sym = cur.options[cur.selectedIndex].dataset.symbol.trim();
    $$('[data-sym]').forEach(function (s) { s.textContent = sym; });
    fromInputs();
    updatePreview();
  });
  fromInputs();

  /* ---------- Counter ---------- */
  $$('[data-counter]').forEach(function (ta) {
    var out = document.querySelector('[data-count-for="' + ta.id + '"]');
    var upd = function () { if (out) out.textContent = ta.value.length; };
    ta.addEventListener('input', upd);
    upd();
  });

  /* ---------- Live preview ---------- */
  var icons = { instagram: 'ph-instagram-logo', youtube: 'ph-youtube-logo', tiktok: 'ph-tiktok-logo', facebook: 'ph-facebook-logo', x: 'ph-x-logo' };
  var pv = {
    name: document.querySelector('[data-pv-name]'),
    loc: document.querySelector('[data-pv-loc]'),
    niches: document.querySelector('[data-pv-niches]'),
    followers: document.querySelector('[data-pv-followers]'),
    fee: document.querySelector('[data-pv-fee]'),
    platforms: document.querySelector('[data-pv-platforms]')
  };
  var lastPlatforms = '';
  function updatePreview() {
    if (!pv.name) return;
    var name = $('[name=full_name]').value.trim();
    pv.name.textContent = name || 'Your name';
    if (pvInitials) {
      var ini = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p[0].toUpperCase(); }).join('');
      pvInitials.textContent = ini || 'P';
    }
    var city = $('[name=city]').value.trim(), country = $('[name=country]').value.trim();
    pv.loc.textContent = [city, country].filter(Boolean).join(', ') || 'Your city';

    var ns = $$('[name="niches[]"]:checked').map(function (i) { return i.value; });
    pv.niches.innerHTML = '';
    (ns.length ? ns : ['Your niches']).forEach(function (n) {
      var t = document.createElement('span'); t.className = 'tag'; t.textContent = n; pv.niches.appendChild(t);
    });

    var total = 0, plats = [];
    $$('[data-channel]').forEach(function (ch) {
      var handle = ch.querySelector('[data-preview-handle]');
      var fol = ch.querySelector('[data-followers]');
      if (!handle.disabled && handle.value.trim() !== '') {
        plats.push(ch.dataset.channel);
        total += parseCount(fol.value);
      }
    });
    pv.followers.textContent = compact(total);
    var key = plats.join(',');
    if (key !== lastPlatforms) {
      pv.platforms.innerHTML = plats.map(function (p) { return '<i class="ph-fill ' + icons[p] + '"></i>'; }).join('');
      lastPlatforms = key;
    }

    var sym = cur.options[cur.selectedIndex].dataset.symbol;
    var a = parseFloat(feeMin.value.replace(/[^\d.]/g, '')), b = parseFloat(feeMax.value.replace(/[^\d.]/g, ''));
    if (!isNaN(a) && !isNaN(b) && b !== a) pv.fee.textContent = sym + money(a) + ' - ' + money(b);
    else if (!isNaN(a) || !isNaN(b)) pv.fee.textContent = sym + money(isNaN(a) ? b : a);
    else pv.fee.textContent = 'Not set';
  }
  form.addEventListener('input', updatePreview);
  form.addEventListener('change', updatePreview);
  updatePreview();

  /* ---------- Start on the first step with a server error ---------- */
  var startAt = 0;
  for (var s = 0; s < steps.length; s++) {
    if (steps[s].querySelector('.field-error, .has-error')) { startAt = s; break; }
  }
  go(startAt, false);
  if (document.querySelector('[data-join-errors]')) {
    reached = steps.length - 1;
    go(startAt, false);
  }
})();
