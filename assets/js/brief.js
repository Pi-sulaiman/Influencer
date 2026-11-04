/* Brand brief builder on /contact: four steps for brands, a simple form for
   creators and other enquiries, a live summary and a real match counter. */
(function () {
  'use strict';
  var form = document.querySelector('[data-brief]');
  if (!form) return;
  var $ = function (s, r) { return (r || form).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || form).querySelectorAll(s)); };
  var gsap = window.gsap;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var toast = window.pkToast || function () {};

  var steps = $$('[data-bstep]');
  var nav = $('[data-bnav]');
  var navItems = $$('[data-bsteps] li');
  var bar = $('.bsteps-bar span');
  var count = $('[data-bcount]');
  var summary = document.querySelector('[data-summary]');
  var pool = [];
  try { pool = JSON.parse(document.querySelector('[data-pool]').textContent); } catch (e) { pool = []; }
  var current = 0, reached = 0;

  var isBrand = function () { var t = $('input[name=inquiry_type]:checked'); return !t || t.value === 'brand'; };

  function setMode() {
    var brand = isBrand();
    form.classList.toggle('is-simple', !brand);
    form.classList.toggle('is-stepped', brand);
    nav.hidden = !brand;
    document.querySelectorAll('.brief-side [data-brand-only]').forEach(function (el) { el.hidden = !brand; });
    $('[data-details-title]').textContent = brand ? 'Where should we send your plan?' : 'How can we help?';
    $('[data-submit-label]').textContent = brand ? 'Send my brief' : 'Send message';
    $('[data-msg-label]').firstChild.textContent = brand ? 'Anything else we should know? ' : 'Your message ';
    if (brand) go(current, false);
  }

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
      if (window.pageYOffset > top) {
        if (window.Pk && window.Pk.lenis) window.Pk.lenis.scrollTo(top); else window.scrollTo({ top: top, behavior: 'smooth' });
      }
    }
  }

  function showError(holder, msg) {
    var old = holder.querySelector('.field-error[data-client]');
    if (old) old.parentNode.removeChild(old);
    holder.classList.toggle('has-error', !!msg);
    if (!msg) return;
    var p = document.createElement('p');
    p.className = 'field-error';
    p.setAttribute('data-client', '');
    p.innerHTML = '<i class="ph-bold ph-warning-circle"></i>' + msg;
    holder.parentNode.insertBefore(p, holder.nextSibling);
  }

  function validate(i) {
    var ok = true;
    if (i === 0) {
      var g = $('input[name=goal]:checked');
      showError($('.goal-grid'), g ? '' : 'Choose the main goal for this campaign.');
      ok = !!g;
    }
    if (i === 1) {
      var n = $$('input[name="niches[]"]:checked').length;
      showError($('[data-brief-niches]'), n ? '' : 'Pick at least one niche.');
      ok = n > 0;
    }
    if (i === 3 || !isBrand()) {
      var name = $('[name=name]'), email = $('[name=email]');
      var okName = name.value.trim() !== '';
      var okEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
      showError(name, okName ? '' : 'Please tell us your name.');
      showError(email, okEmail ? '' : 'Enter a valid email so we can reply.');
      ok = okName && okEmail;
      if (!isBrand()) {
        var msg = $('[name=message]');
        var okMsg = msg.value.trim().length >= 10;
        showError(msg, okMsg ? '' : 'Add a few details so we can help (10 characters minimum).');
        ok = ok && okMsg;
      }
    }
    if (!ok && gsap && !reduce) gsap.fromTo(steps[i] || form, { x: -8 }, { x: 0, duration: 0.5, ease: 'elastic.out(1, .3)' });
    return ok;
  }

  $('[data-bnext]').addEventListener('click', function () { if (validate(current)) go(current + 1, true); });
  $('[data-bprev]').addEventListener('click', function () { go(current - 1, true); });
  navItems.forEach(function (li, n) { li.addEventListener('click', function () { if (n <= reached) go(n, true); }); });
  $$('[data-type]').forEach(function (r) { r.addEventListener('change', setMode); });

  // Picking a goal moves straight to the next step.
  $$('input[name=goal]').forEach(function (r) {
    r.addEventListener('change', function () { showError($('.goal-grid'), ''); setTimeout(function () { if (current === 0) go(1, true); }, 260); });
  });

  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && isBrand() && current < steps.length - 1) {
      e.preventDefault();
      if (validate(current)) go(current + 1, true);
    }
  });
  form.addEventListener('submit', function (e) {
    if (isBrand()) {
      for (var i = 0; i < steps.length; i++) {
        if (!validate(i)) { e.preventDefault(); go(i, true); return; }
      }
    } else if (!validate(3)) { e.preventDefault(); return; }
    $('[data-brief-submit]').classList.add('is-loading');
  });

  /* Max 6 niches */
  var nicheWrap = $('[data-brief-niches]');
  nicheWrap.addEventListener('change', function (e) {
    var on = $$('input:checked', nicheWrap);
    if (on.length > 6) { e.target.checked = false; toast('You can pick up to 6 niches'); }
    nicheWrap.classList.toggle('is-maxed', $$('input:checked', nicheWrap).length >= 6);
    if ($$('input:checked', nicheWrap).length) showError(nicheWrap, '');
  });

  /* Live summary + real match counter */
  var shown = { v: 0 };
  var numEl = summary && summary.querySelector('[data-s-count]');
  function list(sel, labelAttr) {
    return $$(sel + ':checked').map(function (i) { return labelAttr ? i.getAttribute(labelAttr) : i.value; });
  }
  function updateSummary() {
    if (!summary) return;
    var goal = $('input[name=goal]:checked');
    var niches = list('input[name="niches[]"]');
    var plats = list('input[name="platforms[]"]', 'data-label');
    var platKeys = list('input[name="platforms[]"]');
    var budget = $('input[name=budget]:checked');
    var tl = $('input[name=timeline]:checked');
    summary.querySelector('[data-s-goal]').textContent = goal ? goal.getAttribute('data-label') : 'Not chosen yet';
    summary.querySelector('[data-s-niches]').textContent = niches.length ? niches.join(', ') : 'Not chosen yet';
    summary.querySelector('[data-s-platforms]').textContent = plats.length ? plats.join(', ') : 'Any';
    summary.querySelector('[data-s-budget]').textContent = budget ? budget.value : 'Not set';
    summary.querySelector('[data-s-timeline]').textContent = tl ? tl.value : 'Not set';

    var n = pool.filter(function (c) {
      var okN = !niches.length || c[0].some(function (x) { return niches.indexOf(x) > -1; });
      var okP = !platKeys.length || c[1].some(function (x) { return platKeys.indexOf(x) > -1; });
      return okN && okP;
    }).length;
    if (gsap && !reduce) {
      gsap.to(shown, { v: n, duration: 0.6, ease: 'power3.out', onUpdate: function () { numEl.textContent = Math.round(shown.v); } });
      gsap.fromTo(numEl, { scale: 1.25 }, { scale: 1, duration: 0.5, ease: 'back.out(3)' });
    } else {
      numEl.textContent = n;
    }
  }
  form.addEventListener('change', updateSummary);

  /* Start: jump to the first step with a server error */
  setMode();
  var startAt = 0;
  for (var s = 0; s < steps.length; s++) { if (steps[s].querySelector('.field-error, .has-error')) { startAt = s; break; } }
  if (document.querySelector('[data-brief-errors]')) reached = steps.length - 1;
  if (isBrand()) go(startAt, false);
  updateSummary();
})();
