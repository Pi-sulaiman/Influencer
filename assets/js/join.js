/* Creator registration: multi-step form with live validation, smart
   profile-link paste, live preview card, profile strength meter, rate card,
   review step and a local draft so nothing is lost on refresh.
   Without JavaScript the form still works as one long page. */
(function () {
  'use strict';
  var form = document.querySelector('[data-join]');
  if (!form) return;

  var $ = function (s, r) { return (r || form).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || form).querySelectorAll(s)); };
  var doc$ = function (s) { return document.querySelector(s); };
  var toast = window.pkToast || function () {};
  var steps = $$('[data-jstep]');
  var navItems = Array.prototype.slice.call(document.querySelectorAll('[data-jsteps-nav] li'));
  var bar = doc$('.jsteps-bar span');
  var jnav = $('[data-jnav]');
  var count = $('[data-jcount]');
  var current = 0, reached = 0;
  var REVIEW = steps.length - 1;

  var PLATFORMS = {
    instagram: { label: 'Instagram', icon: 'ph-instagram-logo', url: 'https://instagram.com/' },
    youtube: { label: 'YouTube', icon: 'ph-youtube-logo', url: 'https://youtube.com/@' },
    tiktok: { label: 'TikTok', icon: 'ph-tiktok-logo', url: 'https://www.tiktok.com/@' },
    facebook: { label: 'Facebook', icon: 'ph-facebook-logo', url: 'https://facebook.com/' },
    x: { label: 'X', icon: 'ph-x-logo', url: 'https://x.com/' }
  };
  var SCALE = { USD: 1, INR: 85, EUR: 0.92, GBP: 0.8, AED: 3.7, AUD: 1.5, CAD: 1.37, SGD: 1.35, PKR: 280, BDT: 120, NGN: 1500 };

  var parseCount = function (v) {
    v = String(v || '').toLowerCase().replace(/[,\s]/g, '');
    if (v === '') return 0;
    var m = v.match(/^([\d.]+)([kmb])?$/);
    if (!m) return parseInt(v.replace(/\D/g, ''), 10) || 0;
    var n = parseFloat(m[1]);
    if (m[2] === 'k') n *= 1e3;
    if (m[2] === 'm') n *= 1e6;
    if (m[2] === 'b') n *= 1e9;
    return Math.round(n) || 0;
  };
  var num = function (v) { var n = parseFloat(String(v || '').replace(/[^\d.]/g, '')); return isNaN(n) ? NaN : n; };
  var compact = function (n) {
    if (n >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
    return String(n);
  };
  var money = function (n) { return n >= 1e5 ? compact(n) : Math.round(n).toLocaleString('en-US'); };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var tierOf = function (n) {
    if (n >= 1e6) return 'Mega';
    if (n >= 5e5) return 'Macro';
    if (n >= 1e5) return 'Mid-tier';
    if (n >= 1e4) return 'Micro';
    return n > 0 ? 'Nano' : '';
  };
  var val = function (name) {
    if (name === 'languages') {
      var ls = form.querySelector('[data-languages]');
      if (ls) return Array.prototype.filter.call(ls.options, function (o) { return o.selected; }).map(function (o) { return o.value; }).join(', ');
    }
    var el = form.elements[name]; return el ? String(el.value || '').trim() : '';
  };

  /* ---------- Profile links ---------- */
  var hostOf = function (v) {
    v = String(v || '').trim();
    if (!/[\/.]/.test(v.replace(/^@/, ''))) return '';
    try { return new URL(/^https?:\/\//i.test(v) ? v : 'https://' + v).hostname.toLowerCase().replace(/^(www|m|mobile)\./, ''); } catch (e) { return '?'; }
  };
  var domainsOf = function (ch) { return (ch.dataset.domains || '').split(','); };
  var platformForHost = function (host) {
    var found = null;
    $$('[data-channel]').forEach(function (ch) {
      domainsOf(ch).forEach(function (d) { if (host === d || host.slice(-d.length - 1) === '.' + d) found = ch; });
    });
    return found;
  };
  var linkMatches = function (ch, v) {
    var host = hostOf(v);
    if (!host) return true;
    return domainsOf(ch).some(function (d) { return host === d || host.slice(-d.length - 1) === '.' + d; });
  };
  var handleOf = function (v) {
    v = String(v || '').trim();
    if (!hostOf(v)) return v.replace(/^@/, '');
    try {
      var u = new URL(/^https?:\/\//i.test(v) ? v : 'https://' + v);
      var parts = u.pathname.split('/').filter(Boolean);
      if (!parts.length) return '';
      var p = parts[0] === 'channel' || parts[0] === 'c' || parts[0] === 'user' ? parts[1] || parts[0] : parts[0];
      return decodeURIComponent(p).replace(/^@/, '');
    } catch (e) { return ''; }
  };
  var profileUrl = function (key, v) {
    v = String(v || '').trim();
    if (/^https?:\/\//i.test(v)) return v;
    if (hostOf(v)) return 'https://' + v;
    return PLATFORMS[key].url + encodeURIComponent(v.replace(/^@/, ''));
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
      li.classList.toggle('is-done', n < current || (n <= reached && n !== current && stepOk(n)));
      li.classList.toggle('is-reachable', n <= reached);
      if (n === current) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
    });
    if (bar) bar.style.setProperty('--p', ((current + 1) / steps.length).toFixed(3));
    form.classList.toggle('is-first', current === 0);
    form.classList.toggle('is-last', current === steps.length - 1);
    if (count) count.textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
    if (current === REVIEW) buildReview();
    if (focus) {
      var top = form.getBoundingClientRect().top + window.pageYOffset - 150;
      if (window.pageYOffset > top) window.scrollTo({ top: top, behavior: 'smooth' });
      var first = steps[current].querySelector('input:not([type=hidden]):not([disabled]):not([type=file]):not([type=checkbox]):not([type=radio]), select, textarea');
      if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 250);
    }
  }

  function setError(field, msg) {
    var wrap = field.closest('.field') || field.closest('.channels') || field.closest('.check') || field;
    var holder = wrap.classList.contains('check') ? wrap.parentNode : wrap;
    var existing = holder.querySelector(':scope > .field-error[data-client]');
    if (!msg) {
      if (existing) existing.remove();
      if (!holder.querySelector(':scope > .field-error')) wrap.classList.remove('has-error');
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
    existing.innerHTML = '<i class="ph-bold ph-warning-circle"></i>' + esc(msg);
  }
  // Server errors are replaced by live checks once the person edits the field.
  var clearServerError = function (el) {
    var wrap = el.closest('.field');
    if (!wrap) return;
    var srv = wrap.querySelector(':scope > .field-error:not([data-client])');
    if (srv) { srv.remove(); wrap.classList.remove('has-error'); }
  };

  var feeRange = function () {
    var a = num($('[data-fee-min]').value), b = num($('[data-fee-max]').value);
    var rates = $$('[data-rate]').map(function (r) { return num(r.value); }).filter(function (n) { return n > 0; });
    if (isNaN(a) && rates.length) a = Math.min.apply(null, rates);
    if (isNaN(b) && rates.length) b = Math.max.apply(null, rates);
    return [a, b];
  };

  // Returns true when step i is complete. With report=true, marks the errors.
  function checkStep(i, report) {
    var ok = true;
    var check = function (cond, el, msg) { if (report) setError(el, cond ? '' : msg); if (!cond) ok = false; };
    if (i === 0) {
      var photo = $('[data-photo-input]');
      var serverPhotoOk = form.dataset.photoOptional === '1';
      check(val('full_name').length >= 2, form.elements.full_name, 'Please enter your full name.');
      check(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('email')), form.elements.email, 'Enter a valid email address.');
      check(val('phone') !== '', form.elements.phone, 'Add your WhatsApp number so we can send you briefs.');
      if (val('phone') !== '') check(/^[0-9+()\-\s.]{6,25}$/.test(val('phone')) && val('phone').replace(/\D/g, '').length >= 8, form.elements.phone, 'Enter a valid WhatsApp number with country code.');
      check(val('country') !== '', form.elements.country, 'Tell us which country you are based in.');
      check(photo.files.length > 0 || serverPhotoOk, photo, 'Please add a profile photo.');
    }
    if (i === 1) {
      var any = false;
      $$('[data-channel]').forEach(function (ch) {
        var link = ch.querySelector('[data-preview-handle]'), fol = ch.querySelector('[data-followers]');
        if (link.disabled || link.value.trim() === '') { if (report) { setError(link, ''); setError(fol, ''); } return; }
        any = true;
        var label = PLATFORMS[ch.dataset.channel].label;
        check(linkMatches(ch, link.value), link, 'That link does not look like a ' + label + ' profile.');
        check(parseCount(fol.value) > 0, fol, 'Add your ' + label + ' ' + (ch.dataset.channel === 'youtube' ? 'subscriber' : 'follower') + ' count.');
      });
      check(any, $('[data-channels]'), 'Add at least one social channel.');
    }
    if (i === 2) {
      check($$('[name="niches[]"]:checked').length > 0, $('[data-niches]'), 'Pick at least one niche.');
    }
    if (i === 3) {
      var r = feeRange();
      check(!isNaN(r[0]), $('[data-fee-min]'), 'Enter your minimum fee, or fill in the rate card.');
      check(!isNaN(r[1]) && (isNaN(r[0]) || r[1] >= r[0]), $('[data-fee-max]'), isNaN(r[1]) ? 'Enter your maximum fee.' : 'Maximum fee should be equal to or higher than the minimum.');
    }
    if (i === 4) {
      var terms = form.elements.consent_terms;
      check(terms.checked, terms.closest('.check'), 'Please agree to the terms and privacy policy.');
    }
    return ok;
  }
  var stepOk = function (i) { return checkStep(i, false); };

  function validate(i) {
    var ok = checkStep(i, true);
    if (!ok) {
      var firstErr = steps[i].querySelector('.has-error input:not([disabled]), .has-error select, .has-error textarea');
      if (firstErr) firstErr.focus({ preventScroll: false });
      var card = steps[i];
      card.classList.remove('shake'); void card.offsetWidth; card.classList.add('shake');
    }
    return ok;
  }

  $('[data-jnext]').addEventListener('click', function () { if (validate(current)) go(current + 1, true); });
  $('[data-jprev]').addEventListener('click', function () { go(current - 1, true); });
  navItems.forEach(function (li, n) {
    li.addEventListener('click', function () {
      if (n === current || (n > reached && n !== current + 1)) return;
      for (var k = current; k < n; k++) {
        if (!validate(k)) { if (k !== current) go(k, true); return; }
      }
      go(n, true);
    });
  });
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && !/checkbox|radio|file/.test(e.target.type) && current < steps.length - 1) {
      e.preventDefault();
      if (e.target.hasAttribute('data-smart-link')) { smartAdd(e.target); return; }
      if (validate(current)) go(current + 1, true);
    }
  });
  form.addEventListener('submit', function (e) {
    for (var i = 0; i < steps.length; i++) {
      if (!validate(i)) { e.preventDefault(); go(i, true); return; }
    }
    var btn = $('[data-submit]');
    btn.classList.add('is-loading');
    btn.innerHTML = '<span class="spinner" aria-hidden="true"></span> Sending your profile...';
    clearDraft();
  });

  // Re-check a field live once it has shown an error.
  form.addEventListener('input', function (e) {
    clearServerError(e.target);
    if (e.target.closest('.has-error')) checkStep(current, true);
  });

  /* ---------- Photo ---------- */
  var photoInput = $('[data-photo-input]');
  var dz = $('[data-dropzone]');
  var dzPrev = $('[data-photo-preview]');
  var pvPhoto = doc$('[data-pv-photo]');
  var pvInitials = doc$('[data-pv-initials]');
  var photoReady = false;
  function showPhoto(file) {
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { setError(photoInput, 'Please choose a JPG, PNG or WEBP image.'); photoInput.value = ''; return; }
    if (file.size > 5 * 1024 * 1024) { setError(photoInput, 'That image is over 5 MB. Please pick a smaller one.'); photoInput.value = ''; return; }
    clearServerError(photoInput);
    setError(photoInput, '');
    var src = URL.createObjectURL(file);
    dzPrev.innerHTML = '<img alt="">';
    dzPrev.firstChild.src = src;
    dz.classList.add('has-photo');
    if (pvPhoto) { pvPhoto.src = src; pvPhoto.hidden = false; pvInitials.hidden = true; }
    dz.querySelector('.dz-text b').textContent = file.name.length > 34 ? file.name.slice(0, 31) + '...' : file.name;
    dz.querySelector('.dz-text small').textContent = 'Looks good. Click to choose a different photo.';
    photoReady = true;
    updatePreview();
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
  function setChannel(ch, on) {
    var btn = ch.querySelector('[data-channel-toggle]');
    ch.classList.toggle('is-on', on);
    btn.setAttribute('aria-expanded', on ? 'true' : 'false');
    $$('input', ch).forEach(function (i) { i.disabled = !on; });
    if (!on) $$('.field-error[data-client]', ch).forEach(function (n) { n.remove(); });
  }
  $$('[data-channel]').forEach(function (ch) {
    setChannel(ch, ch.classList.contains('is-on'));
    ch.querySelector('[data-channel-toggle]').addEventListener('click', function () {
      var on = !ch.classList.contains('is-on');
      setChannel(ch, on);
      if (on) ch.querySelector('[data-preview-handle]').focus();
      refreshChannels();
      updatePreview();
    });
  });

  function refreshLinkHint(ch) {
    var link = ch.querySelector('[data-preview-handle]');
    var hint = ch.querySelector('[data-link-hint]');
    var v = link.value.trim();
    var key = ch.dataset.channel;
    var cls = 'link-hint', html = '';
    if (v && !link.disabled && !linkMatches(ch, v)) {
      var other = platformForHost(hostOf(v));
      cls = 'link-hint is-warn';
      html = '<i class="ph-bold ph-warning"></i>' + (other ? 'This is a ' + PLATFORMS[other.dataset.channel].label + ' link. <button type="button" class="link-btn" data-move-link>Move it there</button>' : 'This does not look like a ' + PLATFORMS[key].label + ' link.');
    } else if (v && !link.disabled) {
      var h = handleOf(v);
      cls = 'link-hint is-ok';
      html = '<i class="ph-fill ph-check-circle"></i>' + (h ? '@' + esc(h) : 'Link added') + ' <a href="' + esc(profileUrl(key, v)) + '" target="_blank" rel="noopener">Check it <i class="ph-bold ph-arrow-up-right"></i></a>';
    }
    // Only touch the DOM when the hint changes, so a click on it is never lost.
    if (hint.dataset.html !== html) { hint.innerHTML = html; hint.dataset.html = html; }
    hint.className = cls;
  }
  form.addEventListener('click', function (e) {
    var mv = e.target.closest('[data-move-link]');
    if (!mv) return;
    var ch = mv.closest('[data-channel]');
    var link = ch.querySelector('[data-preview-handle]');
    var target = platformForHost(hostOf(link.value));
    if (!target) return;
    setChannel(target, true);
    target.querySelector('[data-preview-handle]').value = link.value;
    link.value = '';
    if (!ch.querySelector('[data-followers]').value) setChannel(ch, false);
    refreshChannels();
    updatePreview();
    target.querySelector('[data-followers]').focus();
    toast('Moved to ' + PLATFORMS[target.dataset.channel].label);
  });

  function refreshCountHint(inp) {
    var hint = inp.closest('.field').querySelector('[data-count-hint]');
    if (!hint) return;
    var raw = inp.value.trim(), n = parseCount(raw);
    hint.textContent = raw && n && !/^\d+$/.test(raw.replace(/,/g, '')) ? '= ' + n.toLocaleString('en-US') : '';
  }

  function refreshChannels() {
    var total = 0, views = 0, active = [];
    $$('[data-channel]').forEach(function (ch) {
      var link = ch.querySelector('[data-preview-handle]');
      var f = parseCount(ch.querySelector('[data-followers]').value);
      var v = parseCount(ch.querySelector('[data-views]').value);
      var on = !link.disabled && link.value.trim() !== '';
      var sum = ch.querySelector('[data-channel-summary]');
      if (on) {
        active.push(ch.dataset.channel);
        total += f; views += v;
        sum.textContent = [f ? compact(f) + (ch.dataset.channel === 'youtube' ? ' subs' : ' followers') : '', v ? compact(v) + ' avg. views' : ''].filter(Boolean).join(' · ');
      } else sum.textContent = '';
      refreshLinkHint(ch);
    });
    $$('[data-count]').forEach(refreshCountHint);
    var tf = doc$('[data-total-followers]'), tv = doc$('[data-total-views]'), tt = doc$('[data-tier]');
    if (tf) tf.textContent = compact(total);
    if (tv) tv.textContent = views ? compact(views) : '-';
    if (tt) tt.textContent = tierOf(total) || '-';
    // Suggest the main platform: the biggest active channel.
    var primary = $('[data-primary]');
    if (primary && !primary.dataset.touched && active.length) {
      var best = active.slice().sort(function (a, b) {
        return parseCount(form.elements[b + '_followers'].value) - parseCount(form.elements[a + '_followers'].value);
      })[0];
      primary.value = best;
    }
    return { total: total, views: views, active: active };
  }
  $('[data-primary]').addEventListener('change', function () { this.dataset.touched = '1'; });
  form.addEventListener('input', function (e) {
    if (e.target.closest('[data-channel]') || e.target.hasAttribute('data-count')) refreshChannels();
  });

  /* ---------- Smart link paste ---------- */
  var smart = $('[data-smart-link]');
  function smartAdd(inp) {
    var v = inp.value.trim();
    if (!v) return;
    var ch = platformForHost(hostOf(v));
    if (!ch) { toast('We could not tell which platform that link is from'); return; }
    setChannel(ch, true);
    ch.querySelector('[data-preview-handle]').value = v;
    inp.value = '';
    refreshChannels();
    updatePreview();
    ch.classList.add('flash');
    setTimeout(function () { ch.classList.remove('flash'); }, 900);
    ch.querySelector('[data-followers]').focus();
    toast(PLATFORMS[ch.dataset.channel].label + ' added. Now add your follower count.');
  }
  if (smart) {
    smart.addEventListener('paste', function () { setTimeout(function () { smartAdd(smart); }, 0); });
    smart.addEventListener('change', function () { smartAdd(smart); });
  }

  /* ---------- Niches (max 5) ---------- */
  var nicheWrap = $('[data-niches]');
  var maxN = parseInt(nicheWrap.dataset.max || '5', 10);
  nicheWrap.addEventListener('change', function (e) {
    var checked = $$('input:checked', nicheWrap);
    if (checked.length > maxN) { e.target.checked = false; toast('You can pick up to ' + maxN + ' niches'); }
    nicheWrap.classList.toggle('is-maxed', $$('input:checked', nicheWrap).length >= maxN);
  });
  nicheWrap.classList.toggle('is-maxed', $$('input:checked', nicheWrap).length >= maxN);

  /* ---------- Fee dual range + rate card ---------- */
  var feeMin = $('[data-fee-min]'), feeMax = $('[data-fee-max]');
  var drMin = $('[data-dr-min]'), drMax = $('[data-dr-max]');
  var dr = $('[data-dual-range]');
  var cur = $('select[data-currency]');
  var feeTouched = feeMin.value !== '' || feeMax.value !== '';
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
    feeTouched = true;
    paint();
    clearServerError(feeMin); clearServerError(feeMax);
    updatePreview();
  };
  var fromInputs = function () {
    var a = num(feeMin.value), b = num(feeMax.value);
    if (!isNaN(a)) drMin.value = toPos(a);
    if (!isNaN(b)) drMax.value = toPos(b);
    paint();
  };
  drMin.addEventListener('input', function () { fromSliders('min'); });
  drMax.addEventListener('input', function () { fromSliders('max'); });
  feeMin.addEventListener('input', function () { feeTouched = true; fromInputs(); });
  feeMax.addEventListener('input', function () { feeTouched = true; fromInputs(); });
  $$('[data-rate]').forEach(function (r) {
    r.addEventListener('input', function () {
      if (feeTouched) return;
      var rates = $$('[data-rate]').map(function (x) { return num(x.value); }).filter(function (n) { return n > 0; });
      feeMin.value = rates.length ? Math.min.apply(null, rates) : '';
      feeMax.value = rates.length ? Math.max.apply(null, rates) : '';
      fromInputs();
    });
  });
  var applySymbol = function () {
    var sym = cur.options[cur.selectedIndex].dataset.symbol.trim();
    $$('[data-sym]').forEach(function (s) { s.textContent = sym; });
  };
  cur.addEventListener('change', function () { applySymbol(); fromInputs(); updatePreview(); });
  fromInputs();

  /* ---------- Counter ---------- */
  $$('[data-counter]').forEach(function (ta) {
    var out = doc$('[data-count-for="' + ta.id + '"]');
    var upd = function () { if (out) out.textContent = ta.value.length; };
    ta.addEventListener('input', upd);
    upd();
  });

  /* ---------- Live preview + strength ---------- */
  var pv = {
    name: doc$('[data-pv-name]'), loc: doc$('[data-pv-loc]'), niches: doc$('[data-pv-niches]'),
    followers: doc$('[data-pv-followers]'), views: doc$('[data-pv-views]'), er: doc$('[data-pv-er]'),
    fee: doc$('[data-pv-fee]'), platforms: doc$('[data-pv-platforms]'), tier: doc$('[data-pv-tier]')
  };
  var strength = { pct: doc$('[data-strength-pct]'), bar: doc$('[data-strength-bar]'), tip: doc$('[data-strength-tip]'), box: doc$('[data-strength]') };
  var lastPlatforms = '';
  var feeLabel = function () {
    var sym = cur.options[cur.selectedIndex].dataset.symbol;
    var r = feeRange(), a = r[0], b = r[1];
    if (!isNaN(a) && !isNaN(b) && b !== a) return sym + money(a) + ' - ' + money(b);
    if (!isNaN(a) || !isNaN(b)) return sym + money(isNaN(a) ? b : a);
    return 'Not set';
  };

  function updatePreview() {
    var name = val('full_name'), display = val('display_name');
    var shown = display || name;
    if (pv.name) pv.name.textContent = shown || 'Your name';
    if (pvInitials) {
      var ini = shown.split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p[0].toUpperCase(); }).join('');
      pvInitials.textContent = ini || 'P';
    }
    if (pv.loc) pv.loc.textContent = [val('city'), val('country')].filter(Boolean).join(', ') || 'Your city';
    var ns = $$('[name="niches[]"]:checked').map(function (i) { return i.value; });
    if (pv.niches) pv.niches.innerHTML = (ns.length ? ns : ['Your niches']).map(function (n) { return '<span class="tag">' + esc(n) + '</span>'; }).join('');

    var c = refreshChannels();
    if (pv.followers) pv.followers.textContent = compact(c.total);
    if (pv.views) pv.views.textContent = c.views ? compact(c.views) : '-';
    var er = num(val('engagement_rate'));
    if (pv.er) pv.er.textContent = isNaN(er) ? '-' : Math.min(100, er) + '%';
    var tier = tierOf(c.total);
    if (pv.tier) { pv.tier.hidden = !tier; pv.tier.textContent = tier; }
    var key = c.active.join(',');
    if (pv.platforms && key !== lastPlatforms) {
      pv.platforms.innerHTML = c.active.map(function (p) { return '<i class="ph-fill ' + PLATFORMS[p].icon + '" title="' + PLATFORMS[p].label + '"></i>'; }).join('');
      lastPlatforms = key;
    }
    if (pv.fee) pv.fee.textContent = feeLabel();

    // Profile strength: what an agency needs to pitch you confidently.
    var hasPhoto = photoReady || form.dataset.photoOptional === '1';
    var items = [
      [hasPhoto, 15, 'Add a clear profile photo.'],
      [name.length >= 2 && /@/.test(val('email')), 10, 'Add your name and email.'],
      [c.active.length > 0 && c.total > 0, 20, 'Add at least one channel with its follower count.'],
      [c.views > 0, 15, 'Add your average views. Brands look at this first.'],
      [ns.length > 0, 10, 'Pick your niches.'],
      [!isNaN(feeRange()[0]), 10, 'Set your fee range.'],
      [val('audience_country') !== '' || !!form.querySelector('[name=audience_age]:checked'), 8, 'Tell us where your audience is.'],
      [!isNaN(er) || parseCount(val('monthly_impressions')) > 0, 7, 'Add your engagement rate or monthly impressions.'],
      [val('bio').length >= 30, 5, 'Write a short bio.']
    ];
    var pct = 0, tip = '';
    items.forEach(function (it) { if (it[0]) pct += it[1]; else if (!tip) tip = it[2]; });
    if (strength.pct) {
      strength.pct.textContent = pct + '%';
      strength.bar.style.setProperty('--w', pct + '%');
      strength.box.dataset.level = pct >= 85 ? 'great' : pct >= 50 ? 'good' : 'low';
      strength.tip.textContent = tip || 'Your profile is ready for brands.';
    }
  }
  form.addEventListener('input', updatePreview);
  form.addEventListener('change', updatePreview);

  /* ---------- Review step ---------- */
  function buildReview() {
    var box = $('[data-review]');
    if (!box) return;
    var c = refreshChannels();
    var ns = $$('[name="niches[]"]:checked').map(function (i) { return i.value; });
    var types = $$('[name="content_types[]"]:checked').map(function (i) { return i.value; });
    var chans = c.active.map(function (p) {
      var f = parseCount(form.elements[p + '_followers'].value), v = parseCount(form.elements[p + '_avg_views'].value);
      return '<span class="rv-chan"><i class="ph-fill ' + PLATFORMS[p].icon + '"></i>' + esc(handleOf(form.elements[p].value) || PLATFORMS[p].label) + ' <b>' + compact(f) + '</b>' + (v ? ' <small>' + compact(v) + ' avg. views</small>' : '') + '</span>';
    }).join('');
    var age = form.querySelector('[name=audience_age]:checked'), gen = form.querySelector('[name=audience_gender]:checked');
    var aud = [val('audience_country'), age ? age.value : '', gen ? gen.value : ''].filter(Boolean).join(' · ');
    var rates = $$('[data-rate]').filter(function (r) { return num(r.value) > 0; }).map(function (r) {
      return esc(r.closest('.field').querySelector('label').textContent.trim()) + ': ' + esc(cur.options[cur.selectedIndex].dataset.symbol) + money(num(r.value));
    });
    var sec = function (step, title, rows) {
      return '<div class="rv-sec"><div class="rv-head"><b>' + title + '</b><button type="button" class="link-btn" data-goto="' + step + '"><i class="ph-bold ph-pencil-simple"></i>Edit</button></div><dl>' +
        rows.filter(function (r) { return r[1]; }).map(function (r) { return '<div><dt>' + r[0] + '</dt><dd>' + r[1] + '</dd></div>'; }).join('') + '</dl></div>';
    };
    box.innerHTML =
      sec(0, 'About you', [['Name', esc(val('full_name')) + (val('display_name') ? ' <small>(' + esc(val('display_name')) + ')</small>' : '')], ['Email', esc(val('email'))], ['Phone', esc(val('phone'))], ['Location', esc([val('city'), val('country')].filter(Boolean).join(', '))], ['Languages', esc(val('languages'))]]) +
      sec(1, 'Channels', [['Profiles', chans || '<span class="rv-miss">No channels yet</span>'], ['Total', compact(c.total) + ' followers' + (c.views ? ' · ' + compact(c.views) + ' avg. impressions per post' : '')], ['Engagement', val('engagement_rate') ? esc(val('engagement_rate')) + '%' : ''], ['Last 30 days', parseCount(val('monthly_impressions')) ? compact(parseCount(val('monthly_impressions'))) + ' impressions' : '']]) +
      sec(2, 'Audience and content', [['Niches', ns.map(esc).join(', ') || '<span class="rv-miss">None picked</span>'], ['Content', types.map(esc).join(', ')], ['Audience', esc(aud)], ['Brands', esc(val('past_brands'))]]) +
      sec(3, 'Rates', [['Fee range', esc(feeLabel())], ['Rate card', rates.join('<br>')], ['Included', esc(val('fee_notes'))], ['Product-only', form.elements.barter_ok.checked ? 'Open to it' : '']]);
  }
  form.addEventListener('click', function (e) {
    var g = e.target.closest('[data-goto]');
    if (g) go(parseInt(g.dataset.goto, 10), true);
  });

  /* ---------- Draft: saved in this browser only ---------- */
  var DRAFT = 'pk-join-draft' + (form.dataset.campaign ? '-' + form.dataset.campaign : '');
  var skip = /^(_token|company_site|photo|consent_terms|consent_feature|campaign_code)$/;
  var saveTimer;
  function saveDraft() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      var out = {};
      $$('input, select, textarea').forEach(function (el) {
        if (!el.name || skip.test(el.name) || el.type === 'range' || el.type === 'file') return;
        if (el.type === 'checkbox' || el.type === 'radio') {
          if (el.checked) (out[el.name] = out[el.name] || []).push(el.value);
        } else if (el.multiple) {
          var picked = Array.prototype.filter.call(el.options, function (o) { return o.selected; }).map(function (o) { return o.value; });
          if (picked.length) out[el.name] = picked;
        } else if (el.value !== '') out[el.name] = el.value;
      });
      out._on = $$('[data-channel].is-on').map(function (c) { return c.dataset.channel; });
      out._step = reached;
      try { localStorage.setItem(DRAFT, JSON.stringify(out)); } catch (err) {}
    }, 400);
  }
  function clearDraft() { try { localStorage.removeItem(DRAFT); } catch (err) {} }
  function restoreDraft() {
    if (form.dataset.posted === '1') return false;
    var d;
    try { d = JSON.parse(localStorage.getItem(DRAFT) || 'null'); } catch (err) { d = null; }
    if (!d || typeof d !== 'object') return false;
    var any = false;
    $$('input, select, textarea').forEach(function (el) {
      if (!el.name || skip.test(el.name) || el.type === 'range' || el.type === 'file' || !(el.name in d)) return;
      if (el.type === 'checkbox' || el.type === 'radio') el.checked = [].concat(d[el.name]).indexOf(el.value) !== -1;
      else if (el.multiple) Array.prototype.forEach.call(el.options, function (o) { o.selected = [].concat(d[el.name]).indexOf(o.value) !== -1; });
      else el.value = d[el.name];
      any = true;
    });
    $$('select').forEach(function (sel) { if (sel.__picker) sel.__picker.refresh(); });
    if (Array.isArray(d._on)) $$('[data-channel]').forEach(function (ch) { setChannel(ch, d._on.indexOf(ch.dataset.channel) !== -1); });
    if (any) {
      feeTouched = feeMin.value !== '' || feeMax.value !== '';
      applySymbol();
      fromInputs();
      reached = Math.min(REVIEW, parseInt(d._step, 10) || 0);
    }
    return any;
  }
  form.addEventListener('input', saveDraft);
  form.addEventListener('change', saveDraft);
  var draftNote = doc$('[data-draft-note]');
  var restored = restoreDraft();
  if (restored && draftNote) {
    draftNote.hidden = false;
    doc$('[data-draft-clear]').addEventListener('click', function () {
      clearDraft();
      window.location.reload();
    });
  }

  /* ---------- Currency follows the creator's country ---------- */
  var countrySel = $('[data-country]');
  var autoCur = false, curTouched = form.dataset.posted === '1' || restored;
  var setCur = function (code) {
    if (!code || cur.value === code || !Array.prototype.some.call(cur.options, function (o) { return o.value === code; })) return;
    autoCur = true;
    cur.value = code;
    cur.dispatchEvent(new Event('change', { bubbles: true }));
    if (cur.__picker) cur.__picker.refresh();
    autoCur = false;
  };
  cur.addEventListener('change', function () { if (!autoCur) curTouched = true; });
  if (countrySel) {
    countrySel.addEventListener('change', function () {
      var o = countrySel.options[countrySel.selectedIndex];
      if (!curTouched && o && o.dataset.currency) setCur(o.dataset.currency);
    });
  }
  // A fresh form starts in the visitor's currency (rupees in India).
  if (!curTouched && !form.dataset.campaign && window.Pk && window.Pk.currency) setCur(window.Pk.currency.get());

  /* ---------- Optional uploads: rate card image, insights screenshot ---------- */
  $$('[data-file-drop]').forEach(function (drop) {
    var input = drop.querySelector('[data-file-input]');
    var nameEl = drop.querySelector('[data-file-name]');
    var icon = drop.querySelector('.fd-ic');
    var original = nameEl.textContent, originalIcon = icon.innerHTML;
    input.addEventListener('change', function () {
      var f = input.files[0];
      setError(input, '');
      if (!f) { nameEl.textContent = original; icon.innerHTML = originalIcon; drop.classList.remove('has-file'); return; }
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { input.value = ''; setError(input, 'Please choose a JPG, PNG or WEBP image.'); return; }
      if (f.size > 5 * 1024 * 1024) { input.value = ''; setError(input, 'That image is over 5 MB. Please pick a smaller one.'); return; }
      nameEl.textContent = f.name.length > 38 ? f.name.slice(0, 35) + '...' : f.name;
      icon.innerHTML = '<img alt="">';
      icon.firstChild.src = URL.createObjectURL(f);
      drop.classList.add('has-file');
    });
  });

  /* ---------- Intro slide: campaign or network details first ---------- */
  var intro = doc$('[data-join-intro]');
  var startBtns = intro ? Array.prototype.slice.call(intro.querySelectorAll('[data-join-start]')) : [];
  var mainCol = form.closest('.join-main');
  if (intro && startBtns.length && mainCol) {
    var skipIntro = form.dataset.posted === '1' || restored || !!doc$('[data-join-errors]');
    var collapse = function () { intro.classList.add('is-collapsed'); };
    if (skipIntro) collapse(); else mainCol.classList.add('intro-mode');
    startBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        mainCol.classList.remove('intro-mode');
        collapse();
        go(0, false);
        var top = mainCol.getBoundingClientRect().top + window.pageYOffset - 90;
        if (window.Pk && window.Pk.lenis) window.Pk.lenis.scrollTo(top, { immediate: true }); else window.scrollTo(0, top);
        var first = form.elements.full_name;
        if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 200);
      });
    });
    intro.querySelectorAll('[data-intro-toggle]').forEach(function (t) {
      t.addEventListener('click', function () {
        var open = intro.classList.toggle('is-open');
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
        t.querySelector('span').textContent = open ? 'Hide details' : 'Show details';
      });
    });
  }

  /* ---------- Start ---------- */
  applySymbol();
  updatePreview();
  var startAt = 0;
  for (var s = 0; s < steps.length; s++) {
    if (steps[s].querySelector('.field-error, .has-error')) { startAt = s; break; }
  }
  if (doc$('[data-join-errors]')) reached = steps.length - 1;
  go(startAt, false);
})();
