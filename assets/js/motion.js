/* Peakilo motion layer (GSAP + ScrollTrigger + Lenis), used on every public page.
   If the CDN scripts fail to load or the visitor prefers reduced motion,
   nothing here runs and the CSS fallbacks in site.css / motion.css stay active. */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var Pk = window.Pk = window.Pk || {};

  /* ---------- Split text into masked words (keeps text for SEO + screen readers) ---------- */
  Pk.splitWords = function (el, chars) {
    if (el.__split) return el.__split;
    var out = [];
    var walk = function (node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var parts = child.textContent.split(/(\s+)/);
          var frag = document.createDocumentFragment();
          parts.forEach(function (part) {
            if (part === '') return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
            var mask = document.createElement('span');
            mask.className = 'sw';
            var inner = document.createElement('span');
            if (chars) {
              part.split('').forEach(function (ch) {
                var c = document.createElement('span');
                c.className = 'sc';
                c.textContent = ch;
                inner.appendChild(c);
                out.push(c);
              });
            } else {
              inner.textContent = part;
              out.push(inner);
            }
            mask.appendChild(inner);
            frag.appendChild(mask);
          });
          child.parentNode.replaceChild(frag, child);
        } else if (child.nodeType === 1) {
          if (child.classList.contains('rotator') || child.hasAttribute('data-nosplit') || child.tagName === 'SVG' || child.tagName === 'svg') {
            var m = document.createElement('span');
            m.className = 'sw';
            var i = document.createElement('span');
            child.parentNode.insertBefore(m, child);
            m.appendChild(i);
            i.appendChild(child);
            out.push(i);
          } else {
            walk(child);
          }
        }
      });
    };
    walk(el);
    el.__split = out;
    return out;
  };

  if (!window.gsap || !window.ScrollTrigger || reduce) return;

  var gsap = window.gsap, ScrollTrigger = window.ScrollTrigger;
  gsap.registerPlugin(ScrollTrigger);
  document.documentElement.classList.add('gsap-on');
  Pk.gsap = true;

  /* ---------- Smooth scrolling on desktop ---------- */
  if (window.Lenis && fine) {
    var lenis = new window.Lenis({ lerp: 0.11, smoothWheel: true, wheelMultiplier: 1 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
    gsap.ticker.lagSmoothing(0);
    Pk.lenis = lenis;
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href*="#"]');
      if (!a || a.target === '_blank') return;
      var url = new URL(a.href, location.href);
      if (url.pathname !== location.pathname || !url.hash || url.hash === '#') return;
      var target = document.querySelector(url.hash);
      if (!target) return;
      e.preventDefault();
      lenis.scrollTo(target, { offset: -90, duration: 1.4 });
      history.replaceState(null, '', url.hash);
    });
  }

  /* ---------- Headings: masked word reveal ---------- */
  gsap.utils.toArray('[data-split]').forEach(function (el) {
    var words = Pk.splitWords(el);
    var tw = gsap.from(words, {
      yPercent: 115, rotate: 3, duration: 1.1, ease: 'expo.out', stagger: 0.055,
      scrollTrigger: {
        trigger: el, start: 'top 88%', once: true,
        onEnter: function () { setTimeout(function () { if (tw.progress() < 1) tw.progress(1); }, 2500); }
      }
    });
  });

  /* ---------- Image parallax inside rounded frames ---------- */
  var parallaxImgs = gsap.utils.toArray('[data-parallax-img], .split-media img, .cell-a img, .cell-photo img, .article-cover img, .aud-media img');
  parallaxImgs.forEach(function (img) {
    gsap.fromTo(img, { yPercent: -7, scale: 1.16 }, {
      yPercent: 7, scale: 1.16, ease: 'none',
      scrollTrigger: { trigger: img.parentNode, start: 'top bottom', end: 'bottom top', scrub: true }
    });
  });

  /* ---------- Clip-path reveal for media frames ---------- */
  gsap.utils.toArray('[data-clip], .split-media, .article-cover').forEach(function (el) {
    gsap.fromTo(el, { clipPath: 'inset(14% 10% 14% 10% round 32px)' }, {
      clipPath: 'inset(0% 0% 0% 0% round 20px)', duration: 1.5, ease: 'expo.out',
      scrollTrigger: { trigger: el, start: 'top 88%', once: true }
    });
  });

  /* ---------- Staggered card entrances ---------- */
  var batchTargets = gsap.utils.toArray('[data-bento] > .cell:not([data-reveal]), .niche-tile, .service-row, .value-cards article, .idea');
  if (batchTargets.length) gsap.set(batchTargets, { opacity: 0, y: 60 });
  if (batchTargets.length) ScrollTrigger.batch(batchTargets, {
    start: 'top 92%',
    once: true,
    onEnter: function (batch) {
      gsap.to(batch, { opacity: 1, y: 0, duration: 1, ease: 'expo.out', stagger: 0.07, overwrite: true, clearProps: 'transform' });
    }
  });

  /* ---------- 3D tilt on pointer for feature cards ---------- */
  if (fine) {
    gsap.utils.toArray('[data-tilt]').forEach(function (card) {
      var rx = gsap.quickTo(card, 'rotationX', { duration: 0.6, ease: 'power3.out' });
      var ry = gsap.quickTo(card, 'rotationY', { duration: 0.6, ease: 'power3.out' });
      gsap.set(card, { transformPerspective: 900 });
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        ry(((e.clientX - r.left) / r.width - 0.5) * 8);
        rx(-((e.clientY - r.top) / r.height - 0.5) * 8);
      });
      card.addEventListener('pointerleave', function () { rx(0); ry(0); });
    });
  }

  /* ---------- Marquee that reacts to scroll speed ---------- */
  gsap.utils.toArray('[data-marquee-track]').forEach(function (track) {
    var items = track.children;
    var half = items.length / 2;
    if (!half) return;
    var dist = items[half].offsetLeft - items[0].offsetLeft;
    var loop = gsap.to(track, { x: -dist, duration: 36, ease: 'none', repeat: -1 });
    loop.totalTime(loop.duration() * 200); // lets the loop run backwards without hitting the start
    var dir = 1;
    var skew = gsap.quickTo(track, 'skewX', { duration: 0.5, ease: 'power3.out' });
    ScrollTrigger.create({
      onUpdate: function (self) {
        var v = self.getVelocity();
        dir = v < 0 ? -1 : 1;
        gsap.to(loop, { timeScale: dir * Math.min(7, 1 + Math.abs(v) / 350), duration: 0.25, overwrite: true });
        gsap.to(loop, { timeScale: dir, duration: 1.4, delay: 0.25, ease: 'power2.out' });
        skew(gsap.utils.clamp(-8, 8, v / -300));
        clearTimeout(track.__t);
        track.__t = setTimeout(function () { skew(0); }, 140);
      }
    });
    var wrap = track.parentNode;
    wrap.addEventListener('pointerenter', function () { gsap.to(loop, { timeScale: 0.2 * dir, duration: 0.6, overwrite: true }); });
    wrap.addEventListener('pointerleave', function () { gsap.to(loop, { timeScale: dir, duration: 0.6, overwrite: true }); });
  });

  /* ---------- Big CTA grows into place ---------- */
  gsap.utils.toArray('[data-cta-scrub], .cta-block').forEach(function (cta) {
    gsap.fromTo(cta, { scale: 0.9, borderRadius: 64 }, {
      scale: 1, borderRadius: 32, ease: 'none',
      scrollTrigger: { trigger: cta, start: 'top bottom', end: 'top 45%', scrub: true }
    });
    var chevs = cta.querySelectorAll('.cta-chevs svg');
    if (chevs.length) {
      gsap.fromTo(chevs, { yPercent: 30 }, { yPercent: -30, ease: 'none', scrollTrigger: { trigger: cta, start: 'top bottom', end: 'bottom top', scrub: true } });
    }
  });

  /* ---------- Inner page hero chevron drifts on scroll ---------- */
  gsap.utils.toArray('.page-hero').forEach(function (hero) {
    var chev = hero.querySelector('.page-hero-chev');
    if (!chev) return;
    gsap.fromTo(chev, { xPercent: 20, opacity: 0 }, { xPercent: 0, opacity: 1, duration: 1.6, ease: 'expo.out' });
    gsap.to(chev, { yPercent: -70, rotate: -8, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
  });

  /* ---------- Footer wordmark slides in ---------- */
  var fw = document.querySelector('.footer-word');
  if (fw) {
    gsap.fromTo(fw.children, { xPercent: -12, opacity: 0 }, {
      xPercent: 0, opacity: 1, ease: 'none', stagger: 0.1,
      scrollTrigger: { trigger: fw, start: 'top bottom', end: 'bottom 85%', scrub: true }
    });
  }

  /* ---------- Stats lift as they count ---------- */
  gsap.utils.toArray('.stat-num').forEach(function (n) {
    gsap.fromTo(n, { yPercent: 40 }, { yPercent: 0, ease: 'none', scrollTrigger: { trigger: n, start: 'top bottom', end: 'top 60%', scrub: true } });
  });

  window.addEventListener('load', function () { ScrollTrigger.refresh(); });
})();
