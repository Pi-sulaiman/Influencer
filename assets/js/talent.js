/* /talent: instant filtering and sorting of creator cards, with the URL kept
   in sync so a filtered list can be shared. Without JavaScript the form
   submits and the server filters instead. */
(function () {
  'use strict';
  var form = document.querySelector('[data-talent-filters]');
  var grid = document.querySelector('[data-talent-grid]');
  if (!form || !grid) return;
  var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-tcard]'));
  var countEl = document.querySelector('[data-talent-count]');
  var nounEl = document.querySelector('[data-talent-noun]');
  var empty = document.querySelector('[data-talent-empty]');
  var reset = document.querySelector('[data-talent-reset]');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  form.classList.add('is-live');

  // Remember the server's "recommended" order.
  cards.forEach(function (c, i) { c.dataset.rank = i; });

  var state = function () {
    var plat = form.querySelector('[name=platform]:checked');
    return {
      q: form.elements.q.value.trim().toLowerCase(),
      niche: form.elements.niche.value,
      tier: form.elements.tier.value,
      sort: form.elements.sort.value,
      platform: plat ? plat.value : ''
    };
  };

  function apply(push) {
    var s = state();
    // FLIP: remember where cards were before re-ordering.
    var before = new Map();
    if (!reduce) cards.forEach(function (c) { if (!c.hidden) before.set(c, c.getBoundingClientRect()); });

    var shown = cards.filter(function (c) {
      var ok = (!s.q || c.dataset.search.indexOf(s.q) !== -1)
        && (!s.niche || c.dataset.niches.split('|').indexOf(s.niche) !== -1)
        && (!s.tier || c.dataset.tier === s.tier)
        && (!s.platform || c.dataset.plats.split('|').indexOf(s.platform) !== -1);
      c.hidden = !ok;
      return ok;
    });
    var key = { reach: 'reach', views: 'views', er: 'er' }[s.sort];
    var sorted = cards.slice().sort(function (a, b) {
      if (key) return parseFloat(b.dataset[key]) - parseFloat(a.dataset[key]);
      if (s.sort === 'newest') return b.dataset.created < a.dataset.created ? -1 : 1;
      return a.dataset.rank - b.dataset.rank;
    });
    sorted.forEach(function (c) { grid.appendChild(c); });

    if (!reduce) {
      shown.forEach(function (c) {
        var a = before.get(c), b = c.getBoundingClientRect();
        if (!a) { c.animate([{ opacity: 0, transform: 'scale(.96)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: 'cubic-bezier(.16,1,.3,1)' }); return; }
        var dx = a.left - b.left, dy = a.top - b.top;
        if (dx || dy) c.animate([{ transform: 'translate(' + dx + 'px,' + dy + 'px)' }, { transform: 'none' }], { duration: 420, easing: 'cubic-bezier(.16,1,.3,1)' });
      });
    }

    countEl.textContent = shown.length;
    if (nounEl) nounEl.textContent = shown.length === 1 ? 'creator' : 'creators';
    var filtered = !!(s.q || s.niche || s.tier || s.platform);
    var tail = countEl.parentNode.lastChild;
    if (tail && tail.nodeType === 3) tail.textContent = filtered ? ' match your filters' : ' ready to book';
    if (reset) reset.hidden = !filtered;
    if (empty) empty.hidden = shown.length > 0;
    grid.hidden = shown.length === 0;

    if (push) {
      var params = new URLSearchParams();
      ['q', 'niche', 'tier', 'platform'].forEach(function (k) { if (s[k]) params.set(k, s[k]); });
      if (s.sort !== 'featured') params.set('sort', s.sort);
      var qs = params.toString();
      history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
    }
  }

  var t;
  form.addEventListener('input', function (e) {
    if (e.target.name === 'q') { clearTimeout(t); t = setTimeout(function () { apply(true); }, 160); }
  });
  form.addEventListener('change', function () { apply(true); });
  form.addEventListener('submit', function (e) { e.preventDefault(); apply(true); });
  var clear = function (e) {
    e.preventDefault();
    form.reset();
    form.elements.q.value = '';
    form.elements.niche.value = '';
    form.elements.tier.value = '';
    form.elements.sort.value = 'featured';
    var all = form.querySelector('[name=platform][value=""]');
    if (all) all.checked = true;
    apply(true);
  };
  if (reset) reset.addEventListener('click', clear);
  var resetBtn = document.querySelector('[data-talent-reset-btn]');
  if (resetBtn) resetBtn.addEventListener('click', clear);
})();
