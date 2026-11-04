/* Homepage: hero intro, niche matchmaker, 3D creator wall and the pinned
   horizontal process section. Works without GSAP (static, CSS fallback). */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var gsap = window.gsap;
  var hasGsap = !!(gsap && window.ScrollTrigger && !reduce);

  var hero = $('[data-hero]');
  var wall = $('[data-wall]');
  var tilt = $('[data-wall-tilt]');
  var loops = [];

  /* =========================================================
     Matchmaker: pick a niche + platform, see real counts,
     highlight matching creators on the wall, then submit to
     the brief builder with the choices pre-filled.
     ========================================================= */
  var mm = $('[data-matchmaker]');
  if (mm) {
    var data = {};
    try { data = JSON.parse($('[data-mm-data]', mm).textContent); } catch (e) { data = {}; }
    var native = $('[data-mm-native]', mm);
    var trigger = $('[data-mm-trigger]', mm);
    var pop = $('[data-mm-pop]', mm);
    var label = $('[data-mm-label]', mm);
    var icon = $('[data-mm-icon]', mm);
    var countEl = $('[data-mm-count]', mm);
    var opts = $$('.mm-opt', pop);
    var shownCount = { v: 0 };
    trigger.hidden = false;

    var state = { niche: '', platform: '' };

    // On phones the list opens as a bottom sheet (moved to <body> so no parent
    // can clip it), with a backdrop and a close button.
    var phone = window.matchMedia('(max-width: 640px)');
    var home = pop.parentNode;
    var backdrop = document.createElement('div');
    backdrop.className = 'mm-backdrop';
    backdrop.hidden = true;
    document.body.appendChild(backdrop);
    var head = document.createElement('div');
    head.className = 'mm-sheet-head';
    head.innerHTML = '<span class="mm-grab" aria-hidden="true"></span><b>Choose a niche</b><button type="button" class="mm-sheet-close" aria-label="Close"><i class="ph-bold ph-x"></i></button>';
    pop.insertBefore(head, pop.firstChild);
    var sheet = false;

    var openPop = function () {
      sheet = phone.matches;
      pop.classList.toggle('is-sheet', sheet);
      pop.classList.remove('is-up');
      if (sheet) {
        document.body.appendChild(pop);
        backdrop.hidden = false;
        document.documentElement.classList.add('sheet-open');
      }
      pop.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      if (!sheet) {
        // Desktop: open upwards when there is not enough room below the trigger.
        var tr = trigger.getBoundingClientRect();
        var below = window.innerHeight - tr.bottom;
        if (below < Math.min(pop.scrollHeight, 440) + 20 && tr.top > below) pop.classList.add('is-up');
      }
      var sel = opts.filter(function (o) { return o.getAttribute('aria-selected') === 'true'; })[0] || opts[0];
      sel.focus({ preventScroll: true });
      if (hasGsap) {
        if (sheet) {
          gsap.fromTo(pop, { yPercent: 100 }, { yPercent: 0, duration: 0.45, ease: 'power3.out' });
          gsap.fromTo(backdrop, { opacity: 0 }, { opacity: 1, duration: 0.3 });
        } else {
          gsap.fromTo(pop, { opacity: 0, y: -8, scale: 0.97 }, { opacity: 1, y: 0, scale: 1, duration: 0.35, ease: 'power3.out' });
        }
        gsap.fromTo(opts, { opacity: 0, y: 6 }, { opacity: 1, y: 0, duration: 0.3, stagger: 0.012, ease: 'power2.out' });
      }
    };
    var closePop = function (focusBack) {
      if (pop.hidden) return;
      trigger.setAttribute('aria-expanded', 'false');
      var done = function () {
        pop.hidden = true;
        if (sheet) {
          home.appendChild(pop);
          backdrop.hidden = true;
          pop.classList.remove('is-sheet');
          gsap && gsap.set && gsap.set(pop, { clearProps: 'transform' });
          document.documentElement.classList.remove('sheet-open');
        }
      };
      if (hasGsap && sheet) {
        gsap.to(pop, { yPercent: 100, duration: 0.3, ease: 'power2.in', onComplete: done });
        gsap.to(backdrop, { opacity: 0, duration: 0.25 });
      } else if (hasGsap) {
        gsap.to(pop, { opacity: 0, y: -6, duration: 0.18, ease: 'power2.in', onComplete: done });
      } else {
        done();
      }
      if (focusBack) trigger.focus({ preventScroll: true });
    };
    trigger.addEventListener('click', function () { pop.hidden ? openPop() : closePop(true); });
    backdrop.addEventListener('click', function () { closePop(true); });
    head.querySelector('.mm-sheet-close').addEventListener('click', function () { closePop(true); });
    document.addEventListener('click', function (e) { if (!mm.contains(e.target) && !pop.contains(e.target) && e.target !== backdrop) closePop(false); });
    pop.addEventListener('keydown', function (e) {
      var i = opts.indexOf(document.activeElement);
      var cols = window.innerWidth <= 560 ? 2 : 3;
      var move = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: cols, ArrowUp: -cols }[e.key];
      if (move) { e.preventDefault(); opts[Math.max(0, Math.min(opts.length - 1, i + move))].focus(); }
      if (e.key === 'Escape') { e.preventDefault(); closePop(true); }
      if (e.key === 'Tab') closePop(false);
    });
    opts.forEach(function (o) {
      o.addEventListener('click', function () {
        opts.forEach(function (x) { x.setAttribute('aria-selected', x === o ? 'true' : 'false'); });
        state.niche = o.dataset.value;
        native.value = state.niche;
        label.textContent = state.niche || 'any niche';
        icon.className = 'ph-bold ' + o.dataset.icon;
        if (hasGsap) gsap.fromTo([label, icon], { yPercent: 60, opacity: 0 }, { yPercent: 0, opacity: 1, duration: 0.4, ease: 'back.out(2)' });
        closePop(true);
        update();
      });
    });
    $$('input[name=platform]', mm).forEach(function (r) {
      r.addEventListener('change', function () { state.platform = r.value; update(); });
    });

    var countFor = function () {
      var n = state.niche, p = state.platform;
      if (!n && !p) return data.approved || 0;
      if (!n) return (data.platformTotals || {})[p] || 0;
      var c = (data.counts || {})[n];
      if (!c) return 0;
      return p ? (c[p] || 0) : (c.any || 0);
    };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    var browse = document.querySelector('[data-mm-browse]');
    var syncBrowse = function () {
      if (!browse) return;
      var q = [];
      if (state.niche) q.push('niche=' + encodeURIComponent(state.niche));
      if (state.platform) q.push('platform=' + encodeURIComponent(state.platform));
      browse.href = browse.getAttribute('data-base') + (q.length ? '?' + q.join('&') : '');
    };
    var update = function () {
      syncBrowse();
      var n = state.niche, p = state.platform;
      var pl = p ? (data.platforms || {})[p] : '';
      var count = countFor();
      var nicheTxt = n ? esc(n) + ' ' : '';
      var platTxt = pl ? ' on ' + esc(pl) : '';
      var custom = data.mode === 'custom';
      if (custom && (n || p)) {
        // Display-number mode: no per-niche counts, just a clear promise.
        countEl.innerHTML = 'Vetted <span class="accent">' + nicheTxt + 'creators' + platTxt + '</span> ready for your brief';
        filterWall(n, p);
        return;
      }
      if (custom) count = data.approved || 0;
      var render = function (v) {
        if (custom && count > 0) {
          countEl.innerHTML = '<b>' + Math.round(v).toLocaleString('en-US') + '+</b> vetted creators ready to match';
        } else if (count > 0) {
          countEl.innerHTML = '<b>' + Math.round(v).toLocaleString('en-US') + '</b> vetted ' + nicheTxt + 'creator' + (count === 1 ? '' : 's') + (pl ? platTxt : (n ? ' in our network' : ' ready to match'));
        } else if (n || p) {
          countEl.innerHTML = 'We will scout <span class="accent">' + nicheTxt + 'creators' + platTxt + '</span> for your brief';
        } else {
          countEl.textContent = 'Pick a niche and we will build your shortlist';
        }
      };
      if (hasGsap && count > 0) {
        gsap.to(shownCount, { v: count, duration: 0.7, ease: 'power3.out', onUpdate: function () { render(shownCount.v); } });
      } else {
        shownCount.v = count;
        render(count);
      }
      filterWall(n, p);
    };

    // Reflect the starting count so the tween begins from it.
    shownCount.v = data.approved || 0;
  }

  /* ---------- Wall filtering ---------- */
  function filterWall(niche, platform) {
    if (!wall) return;
    var active = !!(niche || platform);
    wall.classList.toggle('is-filtering', active);
    $$('.wcard', wall).forEach(function (card) {
      var ns = (card.dataset.niches || '').split('|');
      var ps = card.dataset.plats || '';
      var okN = !niche || ns.indexOf(niche) > -1;
      var okP = !platform || ps === '*' || ps.split('|').indexOf(platform) > -1;
      card.classList.toggle('is-match', active && okN && okP);
    });
    loops.forEach(function (l) { if (hasGsap) gsap.to(l, { timeScale: active ? 0.3 : 1, duration: 0.8 }); });
  }

  /* =========================================================
     Hero intro + creator wall motion
     ========================================================= */
  if (hasGsap && hero) {
    var title = $('[data-hero-title]', hero);
    var chars = [];
    $$('.line', title).forEach(function (line) { chars = chars.concat(window.Pk && window.Pk.splitWords ? window.Pk.splitWords(line, true) : []); });
    var chev = $('[data-hero-chev] path', hero);
    var len = chev && chev.getTotalLength ? chev.getTotalLength() : 0;
    if (len) gsap.set(chev, { strokeDasharray: len, strokeDashoffset: len, fillOpacity: 0 });

    var tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
    if (len) {
      tl.to(chev, { strokeDashoffset: 0, duration: 2.4, ease: 'power2.inOut' }, 0)
        .to(chev, { fillOpacity: 1, duration: 1.2, ease: 'power1.out' }, 1.6);
    }
    tl.from('.hero2-glow', { scale: 0.4, opacity: 0, duration: 2, stagger: 0.2 }, 0)
      .from(chars, { yPercent: 118, rotate: 10, duration: 1.15, stagger: 0.022 }, 0.15)
      .from($$('[data-hero-in]', hero), { y: 28, opacity: 0, duration: 1, stagger: 0.09 }, 0.45)
      .from($$('[data-wall-col]', hero), { y: 180, opacity: 0, duration: 1.8, stagger: 0.14 }, 0.2);
    // Safety net: on a slow or throttled device never leave the hero half revealed.
    setTimeout(function () { if (tl.progress() < 1) tl.progress(1); }, 3200);

    // Infinite columns, each at its own speed and direction.
    $$('[data-wall-col]', hero).forEach(function (col) {
      var strip = $('[data-wall-strip]', col);
      var dur = parseFloat(getComputedStyle(col).getPropertyValue('--dur')) || 50;
      var down = col.dataset.dir === 'down';
      var build = function () {
        var dist = strip.scrollHeight / 2 + 8;
        var t = gsap.fromTo(strip, { y: down ? -dist : 0 }, { y: down ? 0 : -dist, duration: dur, ease: 'none', repeat: -1 });
        loops.push(t);
      };
      build();
    });

    // Slow the wall while the visitor hovers it.
    if (wall) {
      wall.addEventListener('pointerenter', function () { loops.forEach(function (l) { gsap.to(l, { timeScale: 0.25, duration: 0.6 }); }); });
      wall.addEventListener('pointerleave', function () {
        var active = wall.classList.contains('is-filtering');
        loops.forEach(function (l) { gsap.to(l, { timeScale: active ? 0.3 : 1, duration: 0.6 }); });
      });
    }

    // Pointer tilt for the 3D wall.
    if (tilt) {
      gsap.set(tilt, { rotationX: 16, rotationY: -18, rotationZ: 9 });
      if (fine) {
        var rX = gsap.quickTo(tilt, 'rotationX', { duration: 1.1, ease: 'power3.out' });
        var rY = gsap.quickTo(tilt, 'rotationY', { duration: 1.1, ease: 'power3.out' });
        hero.addEventListener('pointermove', function (e) {
          var r = hero.getBoundingClientRect();
          var x = (e.clientX - r.left) / r.width - 0.5;
          var y = (e.clientY - r.top) / r.height - 0.5;
          rY(-18 + x * 14);
          rX(16 - y * 10);
        });
        hero.addEventListener('pointerleave', function () { rY(-18); rX(16); });
      }
    }

    // Scroll away (desktop only): wall lifts and zooms, copy drifts up.
    // On phones the copy sits above the wall, so it must stay fully visible.
    if (fine && window.innerWidth > 960) {
      gsap.to(wall, { yPercent: -12, scale: 1.08, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
      gsap.to($('[data-hero-copy]', hero), { yPercent: -18, opacity: 0.25, ease: 'none', scrollTrigger: { trigger: hero, start: '30% top', end: 'bottom top', scrub: true } });
      gsap.to($('[data-hero-chev]', hero), { yPercent: -60, rotate: -6, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
    }
  } else if (wall) {
    // No GSAP: CSS keyframes keep the wall moving (see motion.css).
    document.documentElement.classList.remove('gsap-on');
  }

  /* =========================================================
     Brands: campaign planner (example figures)
     ========================================================= */
  var planner = $('[data-planner]');
  if (planner) {
    var GOALS = {
      awareness: { reach: 0.34, er: 0.03, weeks: 3, shape: [0.18, 0.26, 0.35, 0.44, 0.52, 0.6, 0.68, 0.76, 0.84] },
      launch:    { reach: 0.30, er: 0.04, weeks: 2, shape: [0.12, 0.18, 0.3, 0.82, 0.96, 0.72, 0.58, 0.52, 0.5] },
      sales:     { reach: 0.26, er: 0.05, weeks: 4, shape: [0.08, 0.12, 0.18, 0.27, 0.38, 0.5, 0.64, 0.8, 0.97] }
    };
    var SIZES = { nano: { count: 24, followers: 6000 }, micro: { count: 10, followers: 45000 }, macro: { count: 2, followers: 800000 } };
    var pstate = { goal: 'launch', size: 'micro' };
    var shape = GOALS.launch.shape.slice();
    var shown = { reach: 0, eng: 0 };
    var areaEl = $('[data-pl-area]', planner), lineEl = $('[data-pl-line]', planner), dotEl = $('[data-pl-dot]', planner), peakEl = $('[data-pl-peak]', planner);
    var avs = $$('[data-av]', planner);
    var fmt = function (n) { return n >= 1e6 ? (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M' : n >= 1e3 ? Math.round(n / 1e3) + 'K' : String(Math.round(n)); };
    var draw = function () {
      // Inner padding keeps the first/last point (and the peak dot) inside the card.
      var W = 300, H = 96, PX = 14, pts = shape.map(function (v, i) { return [PX + i / (shape.length - 1) * (W - PX * 2), H - 10 - v * (H - 30)]; });
      var d = pts.map(function (p, i) {
        if (!i) return 'M' + p[0].toFixed(1) + ' ' + p[1].toFixed(1);
        var prev = pts[i - 1], cx = (prev[0] + p[0]) / 2;
        return 'C' + cx.toFixed(1) + ' ' + prev[1].toFixed(1) + ' ' + cx.toFixed(1) + ' ' + p[1].toFixed(1) + ' ' + p[0].toFixed(1) + ' ' + p[1].toFixed(1);
      }).join(' ');
      lineEl.setAttribute('d', d);
      areaEl.setAttribute('d', d + ' L' + W + ' ' + H + ' L0 ' + H + ' Z');
      var mi = shape.indexOf(Math.max.apply(null, shape));
      dotEl.setAttribute('cx', pts[mi][0]);
      dotEl.setAttribute('cy', pts[mi][1]);
      var lx = pts[mi][0] / W * 100, ly = pts[mi][1] / H * 100;
      // The SVG sits 8px in from the sides and 10px from the top of the card.
      peakEl.style.left = 'calc(8px + (100% - 16px) * ' + (lx / 100).toFixed(4) + ')';
      peakEl.style.top = 'calc(10px + (100% - 10px) * ' + (ly / 100).toFixed(4) + ')';
      // Near the right edge the label sits to the left of the dot, near the
      // left edge to the right, otherwise centred above it.
      peakEl.classList.toggle('pk-left', lx > 72);
      peakEl.classList.toggle('pk-right', lx < 22);
    };
    var renderPlan = function (animate) {
      var g = GOALS[pstate.goal], s = SIZES[pstate.size];
      var reach = s.count * s.followers * g.reach, eng = reach * g.er;
      $('[data-pl="creators"]', planner).textContent = s.count;
      $('[data-pl="weeks"]', planner).textContent = g.weeks;
      avs.forEach(function (a, i) { a.classList.toggle('is-on', i < Math.min(5, s.count)); });
      var target = g.shape;
      if (hasGsap && animate) {
        var from = shape.slice(), o = { t: 0 };
        gsap.to(o, { t: 1, duration: 0.8, ease: 'power3.inOut', onUpdate: function () {
          shape = from.map(function (v, i) { return v + (target[i] - v) * o.t; });
          draw();
        } });
        gsap.to(shown, { reach: reach, eng: eng, duration: 0.8, ease: 'power3.out', onUpdate: function () {
          $('[data-pl="reach"]', planner).textContent = fmt(shown.reach);
          $('[data-pl="eng"]', planner).textContent = fmt(shown.eng);
        } });
        gsap.fromTo(avs.filter(function (a) { return a.classList.contains('is-on'); }), { scale: 0.6 }, { scale: 1, duration: 0.5, stagger: 0.05, ease: 'back.out(2.4)' });
      } else {
        shape = target.slice();
        draw();
        if (!hasGsap) {
          $('[data-pl="reach"]', planner).textContent = fmt(reach);
          $('[data-pl="eng"]', planner).textContent = fmt(eng);
        }
      }
    };
    $$('[data-pl-group]', planner).forEach(function (grp) {
      var key = grp.getAttribute('data-pl-group');
      $$('button', grp).forEach(function (b) {
        b.addEventListener('click', function () {
          $$('button', grp).forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
          pstate[key] = b.getAttribute('data-val');
          renderPlan(true);
        });
      });
    });
    renderPlan(false);
    // Count up the first time the planner scrolls into view.
    if (hasGsap) {
      shown.reach = 0; shown.eng = 0;
      window.ScrollTrigger.create({ trigger: planner, start: 'top 85%', once: true, onEnter: function () { renderPlan(true); } });
    }
  }

  /* =========================================================
     Creators: earnings guide (rough guide in the visitor's currency)
     ========================================================= */
  var earn = $('[data-earn]');
  if (earn) {
    var range = $('[data-earn-range]', earn);
    var outF = $('[data-earn-f]', earn), outR = $('[data-earn-out]', earn), bar = $('[data-earn-bar]', earn);
    var estate = { niche: 1.15, type: 10 };
    // Local market factor: creator rates in South Asia are lower than in USD markets.
    var MARKET = { INR: 0.4, PKR: 0.35, BDT: 0.35, LKR: 0.35, NPR: 0.35 };
    var followersAt = function (t) { return Math.round(1000 * Math.pow(10, t / 100 * 3.3)); };
    var cur = function () { return window.Pk && window.Pk.currency ? window.Pk.currency : null; };
    var renderEarn = function () {
      var f = followersAt(parseFloat(range.value));
      outF.textContent = f >= 1e6 ? (f / 1e6).toFixed(1).replace(/\.0$/, '') + 'M' : f >= 1e3 ? Math.round(f / 1e3) + 'K' : f;
      range.style.setProperty('--fill', range.value + '%');
      var c = cur(), code = c ? c.get() : 'USD', factor = MARKET[code] || 1;
      var base = f / 1000 * estate.type * estate.niche * factor;
      var low = Math.max(20, base * 0.75), high = Math.max(40, base * 1.35);
      if (c) {
        outR.textContent = c.compact(c.nice(c.fromUSD(low))) + ' - ' + c.compact(c.nice(c.fromUSD(high)));
      } else {
        outR.textContent = '$' + Math.round(low) + ' - $' + Math.round(high);
      }
      bar.style.width = Math.min(100, 12 + parseFloat(range.value) * 0.88) + '%';
    };
    range.addEventListener('input', renderEarn);
    $$('[data-earn-group]', earn).forEach(function (grp) {
      var key = grp.getAttribute('data-earn-group');
      $$('button', grp).forEach(function (b) {
        b.addEventListener('click', function () {
          $$('button', grp).forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
          estate[key] = parseFloat(b.getAttribute('data-val'));
          renderEarn();
          if (hasGsap) gsap.fromTo(outR, { scale: 0.92, opacity: 0.4 }, { scale: 1, opacity: 1, duration: 0.4, ease: 'back.out(2)' });
        });
      });
    });
    document.addEventListener('pk:currency', renderEarn);
    renderEarn();
    // Payment steps light up one after another while the widget is visible.
    var steps = $$('[data-earn-steps] li', earn), si = 0, stepTimer = null;
    var tickSteps = function () {
      steps.forEach(function (s, i) { s.classList.toggle('is-done', i <= si); });
      si = (si + 1) % (steps.length + 1);
    };
    if ('IntersectionObserver' in window && !reduce) {
      new IntersectionObserver(function (en) {
        if (en[0].isIntersecting && !stepTimer) { tickSteps(); stepTimer = setInterval(tickSteps, 1100); }
        if (!en[0].isIntersecting && stepTimer) { clearInterval(stepTimer); stepTimer = null; }
      }).observe(earn);
    } else {
      steps.forEach(function (s) { s.classList.add('is-done'); });
    }
  }

  /* ---------- Niche grid: first 4 on phones, "Show all" reveals the rest ---------- */
  var moreBtn = $('[data-niche-more]');
  if (moreBtn) {
    var ngrid = $('[data-niche-grid][data-collapse]');
    moreBtn.addEventListener('click', function () {
      var open = !ngrid.classList.contains('is-open');
      ngrid.classList.toggle('is-open', open);
      moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      moreBtn.querySelector('span').textContent = moreBtn.getAttribute(open ? 'data-less' : 'data-more');
      var extra = $$('.niche-tile', ngrid).slice(4);
      if (open) {
        if (hasGsap) {
          gsap.fromTo(extra, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.5, stagger: 0.03, ease: 'power3.out', clearProps: 'transform' });
        } else {
          extra.forEach(function (t) { t.style.opacity = 1; });
        }
      } else {
        var top = ngrid.getBoundingClientRect().top + window.pageYOffset - 100;
        window.scrollTo({ top: top, behavior: reduce ? 'auto' : 'smooth' });
      }
      if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    });
  }

  /* ---------- Report chart tooltip ---------- */
  var rep = $('[data-rep]');
  if (rep) {
    var tip = $('[data-rep-tip]', rep);
    $$('[data-rep-hits] span', rep).forEach(function (hit) {
      var show = function () {
        tip.innerHTML = '<b>' + hit.dataset.label + '</b>Reach ' + hit.dataset.reach + ' &middot; Clicks ' + hit.dataset.clicks;
        tip.style.left = hit.dataset.x + '%';
        tip.style.top = hit.dataset.y + '%';
        tip.hidden = false;
      };
      hit.addEventListener('pointerenter', show);
      hit.addEventListener('click', show);
      hit.addEventListener('pointerleave', function () { tip.hidden = true; });
    });
  }

  /* ---------- Platform mix donut ---------- */
  var mix = $('[data-mix]');
  if (mix) {
    var cell = mix.closest('.cell-mix');
    var valEl = $('[data-mix-val]', mix), nameEl = $('[data-mix-name]', mix);
    var segs = $$('[data-seg]', mix), items = $$('[data-seg-key]', cell);
    var hl = function (k) {
      mix.classList.toggle('is-hl', k !== null);
      segs.forEach(function (s) { s.classList.toggle('is-hl', s.dataset.seg === String(k)); });
      items.forEach(function (li) { li.classList.toggle('is-hl', li.dataset.segKey === String(k)); });
      var li = k === null ? items[0] : items[k];
      valEl.textContent = li.dataset.val;
      nameEl.textContent = li.dataset.name;
    };
    items.forEach(function (li, k) {
      li.addEventListener('pointerenter', function () { hl(k); });
      li.addEventListener('click', function () { hl(k); });
    });
    segs.forEach(function (s, k) { s.addEventListener('pointerenter', function () { hl(k); }); });
    cell.addEventListener('pointerleave', function () { hl(null); });
  }

  /* =========================================================
     Pinned horizontal "five moves" section (desktop and phones)
     ========================================================= */
  var hs = $('[data-hscroll]');
  if (hs && hasGsap) {
    var mmq = gsap.matchMedia();
    mmq.add('(min-width: 0px)', function () {
      document.documentElement.classList.add('gsap-hs');
      var track = $('[data-hs-track]', hs);
      var bar = $('[data-hs-bar]', hs);
      var dist = function () { return Math.max(0, track.scrollWidth - document.documentElement.clientWidth); };
      var tween = gsap.to(track, {
        x: function () { return -dist(); },
        ease: 'none',
        scrollTrigger: {
          trigger: hs, start: 'top top', end: function () { return '+=' + dist(); },
          pin: true, scrub: 1, invalidateOnRefresh: true, anticipatePin: 1,
          onUpdate: function (self) { if (bar) bar.style.transform = 'scaleX(' + self.progress.toFixed(3) + ')'; }
        }
      });
      $$('.hs-card', hs).forEach(function (card) {
        var ic = $('.hs-ic', card);
        gsap.fromTo(ic, { rotate: -35, scale: 0.6 }, { rotate: 0, scale: 1, ease: 'none', scrollTrigger: { trigger: card, containerAnimation: tween, start: 'left 95%', end: 'left 45%', scrub: true } });
        gsap.fromTo(card, { opacity: 0.35, y: 40 }, { opacity: 1, y: 0, ease: 'none', scrollTrigger: { trigger: card, containerAnimation: tween, start: 'left 100%', end: 'left 65%', scrub: true } });
      });
      window.ScrollTrigger.refresh();
      return function () { document.documentElement.classList.remove('gsap-hs'); };
    });
  }
})();
