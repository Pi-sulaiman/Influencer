/* Peakilo public site interactions. Vanilla JS, no dependencies.
   Every effect is skipped or simplified for prefers-reduced-motion. */
(function () {
  'use strict';

  var doc = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- Toast ---------- */
  var toastTimer;
  function toast(msg) {
    var t = $('[data-toast]');
    if (!t) return;
    t.querySelector('span').textContent = msg;
    t.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('is-on'); }, 3200);
  }
  window.pkToast = toast;
  var Pk = window.Pk = window.Pk || {};

  /* ---------- Currency (INR first, auto-detected for visitors abroad) ---------- */
  var GEO = window.PK_GEO || { currencies: { USD: ['US Dollar', '$'] }, rates: { USD: 1 }, def: 'USD', auto: false };
  var TZ_CUR = {
    'Asia/Kolkata': 'INR', 'Asia/Calcutta': 'INR', 'Asia/Dubai': 'AED', 'Asia/Riyadh': 'SAR', 'Asia/Qatar': 'QAR', 'Asia/Kuwait': 'KWD',
    'Asia/Muscat': 'OMR', 'Asia/Bahrain': 'BHD', 'Asia/Singapore': 'SGD', 'Asia/Kuala_Lumpur': 'MYR', 'Asia/Jakarta': 'IDR', 'Asia/Manila': 'PHP',
    'Asia/Bangkok': 'THB', 'Asia/Ho_Chi_Minh': 'VND', 'Asia/Karachi': 'PKR', 'Asia/Dhaka': 'BDT', 'Asia/Colombo': 'LKR', 'Asia/Kathmandu': 'NPR',
    'Asia/Tokyo': 'JPY', 'Asia/Seoul': 'KRW', 'Asia/Shanghai': 'CNY', 'Asia/Hong_Kong': 'HKD', 'Asia/Jerusalem': 'ILS',
    'Europe/London': 'GBP', 'Europe/Zurich': 'CHF', 'Europe/Stockholm': 'SEK', 'Europe/Oslo': 'NOK', 'Europe/Copenhagen': 'DKK',
    'Europe/Warsaw': 'PLN', 'Europe/Prague': 'CZK', 'Europe/Istanbul': 'TRY', 'Africa/Lagos': 'NGN', 'Africa/Nairobi': 'KES',
    'Africa/Johannesburg': 'ZAR', 'Africa/Cairo': 'EGP', 'America/Sao_Paulo': 'BRL', 'America/Mexico_City': 'MXN',
    'America/Toronto': 'CAD', 'America/Vancouver': 'CAD', 'America/Edmonton': 'CAD', 'America/Winnipeg': 'CAD', 'America/Halifax': 'CAD',
    'Pacific/Auckland': 'NZD'
  };
  var detectCurrency = function () {
    try { var saved = localStorage.getItem('pk-cur'); if (saved && GEO.currencies[saved]) return saved; } catch (e) {}
    if (GEO.auto && window.Intl) {
      var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
      if (TZ_CUR[tz]) return TZ_CUR[tz];
      var region = tz.split('/')[0];
      if (region === 'Europe') return 'EUR';
      if (region === 'America') return 'USD';
      if (region === 'Australia') return 'AUD';
    }
    return GEO.currencies[GEO.def] ? GEO.def : 'USD';
  };
  var currentCur = detectCurrency();
  var symbolOf = function (c) { return (GEO.currencies[c] || [c, c + ' '])[1]; };
  var trim1 = function (v) { return (Math.round(v * 10) / 10).toString().replace(/\.0$/, ''); };
  Pk.currency = {
    list: GEO.currencies,
    get: function () { return currentCur; },
    set: function (c, quiet) {
      if (!GEO.currencies[c] || c === currentCur) return;
      currentCur = c;
      try { localStorage.setItem('pk-cur', c); } catch (e) {}
      $$('select[data-currency-switch]').forEach(function (s) { s.value = c; if (s.__picker) s.__picker.refresh(); });
      if (!quiet) toast('Prices now shown in ' + GEO.currencies[c][0]);
      document.dispatchEvent(new CustomEvent('pk:currency', { detail: c }));
    },
    symbol: symbolOf,
    fromUSD: function (usd, c) { return usd * (GEO.rates[c || currentCur] || 1); },
    nice: function (n) { if (n <= 0) return 0; var mag = Math.pow(10, Math.floor(Math.log10(n)) - 1); return Math.round(n / mag) * mag; },
    compact: function (n, c) {
      c = c || currentCur;
      var s = symbolOf(c);
      if (c === 'INR') { if (n >= 1e7) return s + trim1(n / 1e7) + 'Cr'; if (n >= 1e5) return s + trim1(n / 1e5) + 'L'; }
      if (n >= 1e9) return s + trim1(n / 1e9) + 'B';
      if (n >= 1e6) return s + trim1(n / 1e6) + 'M';
      if (n >= 1e3) return s + trim1(n / 1e3) + 'K';
      return s + (n < 10 ? n.toFixed(2).replace(/\.00$/, '') : Math.round(n).toLocaleString('en-US'));
    },
    full: function (n, c) { c = c || currentCur; return symbolOf(c) + Math.round(n).toLocaleString(c === 'INR' ? 'en-IN' : 'en-US'); },
    usd: function (usd, nice) { var v = Pk.currency.fromUSD(usd); return Pk.currency.compact(nice ? Pk.currency.nice(v) : v); }
  };
  var paintMoney = function () {
    $$('[data-usd]').forEach(function (el) { el.textContent = Pk.currency.usd(parseFloat(el.getAttribute('data-usd')), true); });
  };
  document.addEventListener('pk:currency', paintMoney);

  /* ---------- Themed picker (replaces native selects: country, language, currency) ----------
     Works for single and multiple selects. On phones the list opens as a
     bottom sheet attached to <body>, so no parent can clip it. */
  var phoneMQ = window.matchMedia('(max-width: 640px)');
  var pickBackdrop = null;
  Pk.enhancePicker = function (select) {
    if (select.__picker) return select.__picker;
    var multi = select.multiple;
    var wrap = document.createElement('div');
    wrap.className = 'pick' + (select.classList.contains('pick-sm') ? ' pick-sm' : '') + (multi ? ' pick-multi' : '');
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('pick-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pick-btn';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');
    btn.innerHTML = '<span class="pick-label"><b></b><small></small></span><i class="ph-bold ph-caret-down pick-caret" aria-hidden="true"></i>';
    var titleText = select.getAttribute('data-picker-title') || '';
    if (select.id) {
      btn.id = select.id + '-btn';
      var lab = document.querySelector('label[for="' + select.id + '"]');
      if (lab) { lab.htmlFor = btn.id; if (!titleText) titleText = lab.childNodes[0] ? lab.childNodes[0].textContent.trim() : ''; }
    }
    if (select.getAttribute('aria-label')) btn.setAttribute('aria-label', select.getAttribute('aria-label'));
    wrap.appendChild(btn);
    var pop = document.createElement('div');
    pop.className = 'pick-pop' + (multi ? ' is-multi' : '');
    pop.hidden = true;
    pop.setAttribute('data-lenis-prevent', '');
    var head = '<div class="pick-sheet-head"><span class="mm-grab" aria-hidden="true"></span><b></b><button type="button" class="mm-sheet-close" aria-label="Close"><i class="ph-bold ph-x"></i></button></div>';
    var searchable = select.hasAttribute('data-picker-search') || select.options.length > 12;
    pop.innerHTML = head + (searchable ? '<div class="pick-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="text" placeholder="' + (select.getAttribute('data-picker-placeholder') || 'Search') + '" aria-label="Search options"></div>' : '');
    pop.querySelector('.pick-sheet-head b').textContent = titleText || 'Choose';
    var list = document.createElement('div');
    list.className = 'pick-list';
    list.setAttribute('role', 'listbox');
    if (multi) list.setAttribute('aria-multiselectable', 'true');
    pop.appendChild(list);
    if (multi) {
      var done = document.createElement('div');
      done.className = 'pick-done';
      done.innerHTML = '<button type="button" class="btn btn-dark btn-block">Done</button>';
      pop.appendChild(done);
      done.firstChild.addEventListener('click', function () { close(true); });
    }
    wrap.appendChild(pop);
    var opts = Array.prototype.map.call(select.options, function (o) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'pick-opt';
      b.setAttribute('role', 'option');
      b.dataset.value = o.value;
      b.dataset.search = (o.textContent + ' ' + (o.dataset.sub || '') + ' ' + (o.dataset.code || '')).toLowerCase();
      if (o.value === '' && (o.disabled || o.hasAttribute('data-placeholder'))) { b.dataset.ph = '1'; b.hidden = true; }
      b.innerHTML = '<span></span>' + (o.dataset.sub ? '<small></small>' : '') + (multi ? '<i class="ph-bold ph-check pick-tick" aria-hidden="true"></i>' : '');
      b.firstChild.textContent = o.textContent;
      if (o.dataset.sub) b.querySelector('small').textContent = o.dataset.sub;
      list.appendChild(b);
      b.addEventListener('click', function () {
        if (multi) {
          o.selected = !o.selected;
          refresh();
          select.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
          choose(o.value);
          close(true);
        }
      });
      return b;
    });
    var refresh = function () {
      var label = btn.querySelector('b'), small = btn.querySelector('small');
      if (multi) {
        var picked = Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; }).map(function (o) { return o.textContent; });
        label.textContent = picked.length ? picked.slice(0, 3).join(', ') + (picked.length > 3 ? ' +' + (picked.length - 3) : '') : (select.getAttribute('data-placeholder') || 'Choose');
        small.textContent = '';
        btn.classList.toggle('is-empty', !picked.length);
        opts.forEach(function (b, i) { b.setAttribute('aria-selected', select.options[i].selected ? 'true' : 'false'); });
        return;
      }
      var o = select.options[select.selectedIndex];
      label.textContent = o ? o.textContent : '';
      small.textContent = o && o.dataset.sub && o.value !== '' && !select.classList.contains('pick-sm') ? o.dataset.sub : '';
      btn.classList.toggle('is-empty', !o || o.value === '');
      opts.forEach(function (b) { b.setAttribute('aria-selected', b.dataset.value === select.value && b.dataset.value !== '' ? 'true' : 'false'); });
    };
    var choose = function (v) {
      if (select.value === v) return;
      select.value = v;
      refresh();
      select.dispatchEvent(new Event('change', { bubbles: true }));
    };
    var sheet = false;
    var onDoc = function (e) { if (!wrap.contains(e.target) && !pop.contains(e.target)) close(false); };
    var open = function () {
      document.querySelectorAll('.pick.is-open').forEach(function (p) { if (p !== wrap && p.__close) p.__close(false); });
      sheet = phoneMQ.matches;
      if (sheet) {
        if (!pickBackdrop) {
          pickBackdrop = document.createElement('div');
          pickBackdrop.className = 'mm-backdrop';
          pickBackdrop.hidden = true;
          document.body.appendChild(pickBackdrop);
        }
        pickBackdrop.hidden = false;
        pickBackdrop.onclick = function () { close(true); };
        pop.classList.add('is-sheet');
        document.body.appendChild(pop);
        doc.classList.add('sheet-open');
      }
      pop.hidden = false;
      wrap.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      if (!sheet) {
        var r = btn.getBoundingClientRect();
        wrap.classList.toggle('is-up', window.innerHeight - r.bottom < 320 && r.top > window.innerHeight - r.bottom);
      }
      var input = pop.querySelector('.pick-search input');
      var sel = list.querySelector('[aria-selected="true"]');
      if (sel) sel.scrollIntoView({ block: 'nearest' });
      (sheet ? (sel || opts.filter(function (b) { return !b.hidden; })[0]) : (input || sel || opts[0])).focus({ preventScroll: true });
      if (window.gsap && !reduce) {
        if (sheet) window.gsap.fromTo(pop, { yPercent: 100 }, { yPercent: 0, duration: 0.4, ease: 'power3.out' });
        else window.gsap.fromTo(pop, { opacity: 0, y: -6 }, { opacity: 1, y: 0, duration: 0.25, ease: 'power2.out' });
      }
      document.addEventListener('mousedown', onDoc);
    };
    var close = function (focusBtn) {
      if (pop.hidden) return;
      pop.hidden = true;
      wrap.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('mousedown', onDoc);
      if (sheet) {
        pop.classList.remove('is-sheet');
        wrap.appendChild(pop);
        if (pickBackdrop) pickBackdrop.hidden = true;
        doc.classList.remove('sheet-open');
        if (window.gsap) window.gsap.set(pop, { clearProps: 'transform' });
      }
      var input = pop.querySelector('.pick-search input');
      if (input) { input.value = ''; filter(''); }
      if (focusBtn) btn.focus({ preventScroll: true });
    };
    pop.querySelector('.mm-sheet-close').addEventListener('click', function () { close(true); });
    wrap.__close = close;
    var filter = function (q) {
      q = q.toLowerCase().trim();
      opts.forEach(function (b) { b.hidden = b.dataset.ph === '1' || (q !== '' && b.dataset.search.indexOf(q) === -1); });
    };
    btn.addEventListener('click', function () { pop.hidden ? open() : close(true); });
    btn.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); } });
    pop.addEventListener('keydown', function (e) {
      var visible = opts.filter(function (b) { return !b.hidden; });
      var i = visible.indexOf(document.activeElement);
      if (e.key === 'ArrowDown') { e.preventDefault(); (visible[i + 1] || visible[0]).focus(); }
      if (e.key === 'ArrowUp') { e.preventDefault(); (visible[i - 1] || visible[visible.length - 1]).focus(); }
      if (e.key === 'Escape') { e.preventDefault(); close(true); }
      if (e.key === 'Enter' && document.activeElement.tagName === 'INPUT' && visible[0]) { e.preventDefault(); visible[0].click(); }
      if (e.key === 'Tab') close(false);
    });
    var input = pop.querySelector('.pick-search input');
    if (input) input.addEventListener('input', function () { filter(input.value); });
    select.addEventListener('change', refresh);
    refresh();
    select.__picker = { refresh: refresh, open: open, close: close, button: btn };
    return select.__picker;
  };
  $$('select[data-picker]').forEach(Pk.enhancePicker);

  /* Currency switchers anywhere on the page */
  $$('select[data-currency-switch]').forEach(function (s) {
    s.value = currentCur;
    if (s.__picker) s.__picker.refresh();
    s.addEventListener('change', function () { Pk.currency.set(s.value); });
  });
  paintMoney();

  /* ---------- Theme ---------- */
  $$('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var next = doc.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      doc.setAttribute('data-theme', next);
      try { localStorage.setItem('pk-theme', next); } catch (e) {}
    });
  });

  /* ---------- Nav: solid after leaving the top ---------- */
  var nav = $('[data-nav]');
  var sentinel = $('#top-sentinel');
  if (nav && sentinel && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      nav.classList.toggle('is-scrolled', !entries[0].isIntersecting);
    }).observe(sentinel);
  }

  /* ---------- Mobile menu ---------- */
  var burger = $('[data-burger]');
  var menu = $('[data-mobile-menu]');
  if (burger && menu) {
    var setMenu = function (open) {
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      menu.classList.toggle('is-open', open);
      menu.setAttribute('aria-hidden', open ? 'false' : 'true');
      document.body.style.overflow = open ? 'hidden' : '';
      if (nav) nav.classList.toggle('is-scrolled', open || nav.classList.contains('is-scrolled'));
    };
    burger.addEventListener('click', function () { setMenu(burger.getAttribute('aria-expanded') !== 'true'); });
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });
  }

  /* ---------- Count up ---------- */
  function formatNum(n, decimals) {
    return decimals ? n.toFixed(decimals) : Math.round(n).toLocaleString('en-US');
  }
  function countUp(el) {
    if (el.dataset.counted) return;
    el.dataset.counted = '1';
    var target = parseFloat(el.dataset.count) || 0;
    var decimals = parseInt(el.dataset.decimals || '0', 10);
    if (reduce) { el.textContent = formatNum(target, decimals); return; }
    var start = null, dur = 1600;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / dur);
      var eased = 1 - Math.pow(1 - p, 4);
      el.textContent = formatNum(target * eased, decimals);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* ---------- Reveal + in-view triggers ---------- */
  var revealEls = $$('[data-reveal]');
  var countEls = $$('[data-count]');
  var chartEls = $$('[data-chart]');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        el.classList.add('is-in');
        if (el.hasAttribute('data-count')) countUp(el);
        if (el.hasAttribute('data-chart')) el.classList.add('is-drawn');
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    revealEls.concat(countEls, chartEls).forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-in'); });
    countEls.forEach(countUp);
    chartEls.forEach(function (el) { el.classList.add('is-drawn'); });
  }

  /* ---------- Hero word rotator ---------- */
  $$('[data-rotator]').forEach(function (wrap) {
    var words = wrap.dataset.rotator.split(',');
    if (reduce || words.length < 2) return;
    var i = 0;
    var current = wrap.querySelector('span');
    // Measure each word so the box (and the period after it) glides to the right width.
    var probe = document.createElement('span');
    probe.setAttribute('aria-hidden', 'true');
    probe.style.cssText = 'position:absolute;visibility:hidden;white-space:nowrap;left:-9999px;top:0';
    wrap.parentNode.appendChild(probe);
    var setWidth = function (w) {
      probe.textContent = w;
      wrap.style.width = Math.ceil(probe.getBoundingClientRect().width) + 'px';
    };
    var ready = function () { setWidth(words[i]); };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(ready); else ready();
    window.addEventListener('resize', ready);
    setInterval(function () {
      if (document.hidden) return;
      i = (i + 1) % words.length;
      var next = document.createElement('span');
      next.textContent = words[i];
      next.className = 'in-wait';
      wrap.appendChild(next);
      current.classList.add('out');
      setWidth(words[i]);
      var old = current;
      requestAnimationFrame(function () { requestAnimationFrame(function () { next.className = ''; }); });
      setTimeout(function () { old.remove(); }, 800);
      current = next;
      wrap.setAttribute('aria-label', words[i]);
    }, 2600);
  });

  /* ---------- Hero pointer parallax ---------- */
  var scene = $('[data-parallax]');
  if (scene && finePointer && !reduce) {
    var layers = $$('[data-depth]', scene);
    var hero = scene.closest('section') || scene;
    var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;
    var loop = function () {
      cx += (tx - cx) * 0.08;
      cy += (ty - cy) * 0.08;
      layers.forEach(function (l) {
        var d = parseFloat(l.dataset.depth);
        l.style.transform = 'translate3d(' + (cx * d).toFixed(2) + 'px,' + (cy * d).toFixed(2) + 'px,0) rotateX(' + (-cy * d * 0.08).toFixed(2) + 'deg) rotateY(' + (cx * d * 0.08).toFixed(2) + 'deg)';
      });
      if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001) raf = requestAnimationFrame(loop);
      else raf = null;
    };
    layers.forEach(function (l) { l.style.transition = 'none'; });
    hero.addEventListener('pointermove', function (e) {
      var r = hero.getBoundingClientRect();
      tx = ((e.clientX - r.left) / r.width - 0.5) * 2;
      ty = ((e.clientY - r.top) / r.height - 0.5) * 2;
      if (!raf) raf = requestAnimationFrame(loop);
    });
    hero.addEventListener('pointerleave', function () { tx = 0; ty = 0; if (!raf) raf = requestAnimationFrame(loop); });
  }

  /* ---------- Magnetic buttons ---------- */
  if (finePointer && !reduce) {
    $$('[data-magnetic]').forEach(function (btn) {
      btn.addEventListener('pointermove', function (e) {
        var r = btn.getBoundingClientRect();
        var x = (e.clientX - r.left - r.width / 2) * 0.22;
        var y = (e.clientY - r.top - r.height / 2) * 0.32;
        btn.style.transform = 'translate(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px)';
      });
      btn.addEventListener('pointerleave', function () { btn.style.transform = ''; });
    });
  }

  /* ---------- Spotlight cards ---------- */
  if (finePointer) {
    $$('[data-spotlight]').forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        card.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });
  }

  /* ---------- Audience switch (accessible tabs) ---------- */
  $$('[data-switch]').forEach(function (sw) {
    var tabs = $$('[role="tab"]', sw);
    function select(idx, focus) {
      tabs.forEach(function (t, i) {
        var on = i === idx;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        var panel = document.getElementById(t.getAttribute('aria-controls'));
        if (!panel) return;
        if (on) {
          panel.hidden = false;
          panel.classList.add('is-in');
          panel.classList.remove('is-entering');
          void panel.offsetWidth;
          panel.classList.add('is-entering');
        } else {
          panel.hidden = true;
        }
      });
      sw.setAttribute('data-active', String(idx));
      if (focus) tabs[idx].focus();
    }
    // A tap anywhere on either half of the switch selects that side, so
    // nobody has to hit the exact words on a small phone screen.
    sw.addEventListener('click', function (e) {
      var tab = e.target.closest('[role="tab"]');
      if (tab) { select(tabs.indexOf(tab)); return; }
      var r = sw.getBoundingClientRect();
      select(e.clientX < r.left + r.width / 2 ? 0 : 1);
    });
    tabs.forEach(function (t, i) {
      t.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
          e.preventDefault();
          select((i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length, true);
        }
      });
    });
  });

  /* ---------- Process steps progress ---------- */
  var steps = $('[data-steps]');
  if (steps && 'IntersectionObserver' in window) {
    var stepEls = $$('[data-step]', steps);
    var activeMax = -1;
    var sio = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var idx = stepEls.indexOf(entry.target);
        if (entry.isIntersecting) {
          activeMax = Math.max(activeMax, idx);
        } else if (entry.boundingClientRect.top > 0 && idx <= activeMax) {
          // scrolled back up past this step
          activeMax = idx - 1;
        }
      });
      stepEls.forEach(function (s, i) { s.classList.toggle('is-active', i <= activeMax); });
      var pct = stepEls.length > 1 ? Math.max(0, activeMax) / (stepEls.length - 1) : 1;
      steps.style.setProperty('--progress', activeMax < 0 ? 0 : pct.toFixed(3));
    }, { rootMargin: '0px 0px -45% 0px', threshold: 0 });
    stepEls.forEach(function (s) { sio.observe(s); });
  } else if (steps) {
    $$('[data-step]', steps).forEach(function (s) { s.classList.add('is-active'); });
  }

  /* ---------- Creator carousel (drag + buttons) ---------- */
  $$('[data-carousel]').forEach(function (car) {
    var section = car.closest('section');
    var prev = section && $('[data-carousel-prev]', section);
    var next = section && $('[data-carousel-next]', section);
    var cards = $$('.creator-card', car);
    var stepSize = function () { return cards[0] ? cards[0].getBoundingClientRect().width + 18 : 300; };
    if (prev) prev.addEventListener('click', function () { car.scrollBy({ left: -stepSize(), behavior: reduce ? 'auto' : 'smooth' }); });
    if (next) next.addEventListener('click', function () { car.scrollBy({ left: stepSize(), behavior: reduce ? 'auto' : 'smooth' }); });
    car.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); car.scrollBy({ left: stepSize(), behavior: 'smooth' }); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); car.scrollBy({ left: -stepSize(), behavior: 'smooth' }); }
    });

    if ('IntersectionObserver' in window && cards.length && prev && next) {
      var edge = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.target === cards[0]) prev.disabled = en.intersectionRatio > 0.95;
          if (en.target === cards[cards.length - 1]) next.disabled = en.intersectionRatio > 0.95;
        });
      }, { root: car, threshold: [0, 0.95, 1] });
      edge.observe(cards[0]);
      edge.observe(cards[cards.length - 1]);
    }

    // Mouse drag (touch uses native scrolling)
    var down = false, startX = 0, startLeft = 0, moved = 0;
    car.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; moved = 0; startX = e.clientX; startLeft = car.scrollLeft;
    });
    window.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      moved = Math.max(moved, Math.abs(dx));
      if (moved > 4) car.classList.add('is-dragging');
      car.scrollLeft = startLeft - dx;
    });
    window.addEventListener('pointerup', function () {
      if (!down) return;
      down = false;
      car.classList.remove('is-dragging');
    });
    // A drag should not count as a click on the card under the pointer.
    car.addEventListener('click', function (e) {
      if (moved > 4) { e.preventDefault(); e.stopPropagation(); moved = 0; }
    }, true);
    car.addEventListener('dragstart', function (e) { if (e.target.tagName === 'IMG') e.preventDefault(); });
  });

  /* ---------- Campaign estimator ---------- */
  var est = $('[data-estimator]');
  if (est) {
    var TIERS = {
      nano:  { fee: 150,   followers: 6000,    er: 0.055, reach: 0.35 },
      micro: { fee: 700,   followers: 45000,   er: 0.035, reach: 0.30 },
      mid:   { fee: 3000,  followers: 250000,  er: 0.022, reach: 0.25 },
      macro: { fee: 12000, followers: 1000000, er: 0.015, reach: 0.22 }
    };
    var PLATFORMS = {
      instagram: { fee: 1,    reach: 1,    er: 1 },
      tiktok:    { fee: 0.85, reach: 1.35, er: 1.25 },
      youtube:   { fee: 1.8,  reach: 0.9,  er: 0.8 },
      facebook:  { fee: 0.7,  reach: 0.9,  er: 0.7 },
      snapchat:  { fee: 0.75, reach: 1.1,  er: 0.9 },
      x:         { fee: 0.6,  reach: 0.8,  er: 0.6 },
      linkedin:  { fee: 1.4,  reach: 0.6,  er: 0.9 },
      threads:   { fee: 0.6,  reach: 0.7,  er: 0.8 }
    };
    var budgetIn = $('[data-est-budget]', est);
    var budgetOut = $('[data-est-budget-out]', est);
    var outs = {};
    $$('[data-est]', est).forEach(function (el) { outs[el.dataset.est] = el; });
    var shown = { reach: 0, creators: 0, engagements: 0, cpm: 0 };
    var tweenRaf;

    var compact = function (n) {
      if (n >= 1e6) return (n / 1e6).toFixed(n >= 1e7 ? 0 : 1).replace(/\.0$/, '') + 'M';
      if (n >= 1e3) return (n / 1e3).toFixed(n >= 1e5 ? 0 : 1).replace(/\.0$/, '') + 'K';
      return String(Math.round(n));
    };
    var render = function (v) {
      outs.reach.textContent = compact(v.reach);
      outs.creators.textContent = Math.round(v.creators).toLocaleString('en-US');
      outs.engagements.textContent = compact(v.engagements);
      outs.cpm.textContent = Pk.currency.compact(Pk.currency.fromUSD(v.cpm));
    };
    var calc = function () {
      var budget = parseInt(budgetIn.value, 10);
      var tier = TIERS[($('input[name="tier"]:checked', est) || {}).value || 'micro'];
      var plat = PLATFORMS[($('input[name="platform_est"]:checked', est) || {}).value || 'instagram'];
      var fee = tier.fee * plat.fee;
      var creatorsExact = budget / fee;
      var reach = creatorsExact * tier.followers * tier.reach * plat.reach;
      var target = {
        reach: reach,
        creators: Math.max(1, Math.floor(creatorsExact)),
        engagements: reach * tier.er * plat.er,
        cpm: budget / reach * 1000
      };
      budgetOut.textContent = Pk.currency.compact(Pk.currency.nice(Pk.currency.fromUSD(budget)));
      budgetIn.style.setProperty('--fill', ((budget - budgetIn.min) / (budgetIn.max - budgetIn.min) * 100) + '%');

      cancelAnimationFrame(tweenRaf);
      if (reduce) { shown = target; render(shown); return; }
      var from = Object.assign({}, shown), t0 = null;
      var tick = function (ts) {
        if (!t0) t0 = ts;
        var p = Math.min(1, (ts - t0) / 450), k = 1 - Math.pow(1 - p, 3);
        Object.keys(target).forEach(function (key) { shown[key] = from[key] + (target[key] - from[key]) * k; });
        render(shown);
        if (p < 1) tweenRaf = requestAnimationFrame(tick);
      };
      tweenRaf = requestAnimationFrame(tick);
    };
    est.addEventListener('input', calc);
    est.addEventListener('change', calc);
    document.addEventListener('pk:currency', calc);
    calc();
  }

  /* ---------- Testimonials ---------- */
  var quotes = $('[data-quotes]');
  if (quotes) {
    var items = $$('[data-quote]', quotes);
    var dotsWrap = $('[data-quote-dots]', quotes);
    var dots = $$('button', dotsWrap);
    var idx = 0, timer = null, DUR = 7000;
    var show = function (n) {
      idx = (n + items.length) % items.length;
      items.forEach(function (q, i) {
        q.classList.toggle('is-active', i === idx);
        q.setAttribute('aria-hidden', i === idx ? 'false' : 'true');
      });
      dots.forEach(function (d, i) {
        d.classList.remove('is-active');
        if (i === idx) { void d.offsetWidth; d.classList.add('is-active'); }
      });
      restart();
    };
    var restart = function () {
      clearTimeout(timer);
      if (reduce) return;
      timer = setTimeout(function () { show(idx + 1); }, DUR);
    };
    dotsWrap.style.setProperty('--dur', DUR + 'ms');
    dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); }); });
    var p = $('[data-quote-prev]', quotes), n = $('[data-quote-next]', quotes);
    if (p) p.addEventListener('click', function () { show(idx - 1); });
    if (n) n.addEventListener('click', function () { show(idx + 1); });
    var pause = function () { clearTimeout(timer); dotsWrap.classList.add('is-paused'); };
    var resume = function () { dotsWrap.classList.remove('is-paused'); show(idx); };
    quotes.addEventListener('pointerenter', pause);
    quotes.addEventListener('pointerleave', resume);
    quotes.addEventListener('focusin', pause);
    quotes.addEventListener('focusout', resume);
    if (reduce) dots.forEach(function (d) { d.classList.remove('is-active'); });
    restart();
  }

  /* ---------- FAQ: one open at a time ---------- */
  $$('[data-faq]').forEach(function (list) {
    var items = $$('details', list);
    items.forEach(function (d) {
      d.addEventListener('toggle', function () {
        if (!d.open) return;
        items.forEach(function (o) { if (o !== d) o.open = false; });
      });
    });
  });

  /* ---------- Newsletter ---------- */
  $$('[data-subscribe]').forEach(function (form) {
    var msg = form.parentNode.querySelector('[data-subscribe-msg]');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = form.email.value.trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        if (msg) msg.textContent = 'Please enter a valid email address.';
        form.email.focus();
        return;
      }
      var btn = form.querySelector('button');
      btn.classList.add('is-loading');
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (msg) msg.textContent = data.message || '';
          if (data.ok) { form.reset(); toast(data.message); }
        })
        .catch(function () { if (msg) msg.textContent = 'Something went wrong. Please try again.'; })
        .then(function () { btn.classList.remove('is-loading'); });
    });
  });

  /* ---------- Blog: table of contents highlight ---------- */
  var toc = $('[data-toc]');
  if (toc && 'IntersectionObserver' in window) {
    var links = $$('a', toc);
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var heads = Object.keys(map).map(function (id) { return document.getElementById(id); }).filter(Boolean);
    var tio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          links.forEach(function (a) { a.classList.remove('is-active'); });
          var a = map[en.target.id];
          if (a) a.classList.add('is-active');
        }
      });
    }, { rootMargin: '-15% 0px -75% 0px' });
    heads.forEach(function (h) { tio.observe(h); });
  }

  /* ---------- Copy link buttons ---------- */
  $$('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy') || location.href;
      var done = function () { toast(btn.getAttribute('data-copied') || 'Link copied'); };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, function () {});
      else { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
    });
  });

  /* ---------- Native share ---------- */
  $$('[data-share]').forEach(function (btn) {
    if (!navigator.share) { btn.hidden = true; return; }
    btn.addEventListener('click', function () {
      navigator.share({ title: document.title, url: location.href }).catch(function () {});
    });
  });
})();
