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

    var openPop = function () {
      pop.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      // Open upwards when there is not enough room below the trigger.
      pop.classList.remove('is-up');
      var tr = trigger.getBoundingClientRect();
      var below = window.innerHeight - tr.bottom;
      if (below < Math.min(pop.scrollHeight, 440) + 20 && tr.top > below) pop.classList.add('is-up');
      var sel = opts.filter(function (o) { return o.getAttribute('aria-selected') === 'true'; })[0] || opts[0];
      sel.focus();
      if (hasGsap) {
        gsap.fromTo(pop, { opacity: 0, y: -8, scale: 0.97 }, { opacity: 1, y: 0, scale: 1, duration: 0.35, ease: 'power3.out' });
        gsap.fromTo(opts, { opacity: 0, y: 6 }, { opacity: 1, y: 0, duration: 0.3, stagger: 0.012, ease: 'power2.out' });
      }
    };
    var closePop = function (focusBack) {
      if (pop.hidden) return;
      trigger.setAttribute('aria-expanded', 'false');
      var done = function () { pop.hidden = true; };
      if (hasGsap) gsap.to(pop, { opacity: 0, y: -6, duration: 0.18, ease: 'power2.in', onComplete: done });
      else done();
      if (focusBack) trigger.focus();
    };
    trigger.addEventListener('click', function () { pop.hidden ? openPop() : closePop(true); });
    document.addEventListener('click', function (e) { if (!mm.contains(e.target)) closePop(false); });
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

    // Scroll away: wall lifts and zooms, copy drifts up.
    gsap.to(wall, { yPercent: -12, scale: 1.08, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
    gsap.to($('[data-hero-copy]', hero), { yPercent: -18, opacity: 0.25, ease: 'none', scrollTrigger: { trigger: hero, start: '30% top', end: 'bottom top', scrub: true } });
    gsap.to($('[data-hero-chev]', hero), { yPercent: -60, rotate: -6, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
  } else if (wall) {
    // No GSAP: CSS keyframes keep the wall moving (see motion.css).
    document.documentElement.classList.remove('gsap-on');
  }

  /* =========================================================
     Pinned horizontal "five moves" section (desktop only)
     ========================================================= */
  var hs = $('[data-hscroll]');
  if (hs && hasGsap) {
    var mmq = gsap.matchMedia();
    mmq.add('(min-width: 961px)', function () {
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
