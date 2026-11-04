/* Brand shortlist: creators a visitor hearts on /talent, kept in this browser
   only. A floating tray shows them on every page and sends them to the
   brief form as one quote request. Exposes window.PkShortlist. */
(function () {
  'use strict';
  var KEY = 'pk-shortlist';
  var MAX = 20;
  var tray = document.querySelector('[data-sl-tray]');
  var base = (tray && tray.getAttribute('data-base')) || '/';
  var toast = function (m) { if (window.pkToast) window.pkToast(m); };
  var esc = function (s) { return String(s || '').replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  var read = function () {
    try { var v = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(v) ? v.filter(function (x) { return x && /^PK\d+$/.test(x.code); }) : []; } catch (e) { return []; }
  };
  var list = read();
  var write = function () { try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} };
  var has = function (code) { return list.some(function (x) { return x.code === code; }); };

  var api = {
    get: function () { return list.slice(); },
    has: has,
    add: function (item) {
      if (has(item.code)) return true;
      if (list.length >= MAX) { toast('Your shortlist can hold ' + MAX + ' creators'); return false; }
      list.push({ code: item.code, name: item.name, photo: item.photo, meta: item.meta });
      write(); render();
      return true;
    },
    remove: function (code) { list = list.filter(function (x) { return x.code !== code; }); write(); render(); },
    clear: function () { list = []; write(); render(); },
    requestUrl: function () { return base + 'contact?type=brand&creators=' + list.map(function (x) { return x.code; }).join(','); }
  };
  window.PkShortlist = api;

  /* Buttons: any [data-shortlist] element toggles its creator. */
  function syncButtons() {
    document.querySelectorAll('[data-shortlist]').forEach(function (b) {
      var on = has(b.getAttribute('data-code'));
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      var label = b.querySelector('[data-sl-label]');
      if (label) {
        if (!label.dataset.off) label.dataset.off = label.textContent;
        label.textContent = on ? 'Shortlisted' : label.dataset.off;
      }
      var icon = b.querySelector('i');
      if (icon && b.classList.contains('tc-add')) icon.className = 'ph-bold ' + (on ? 'ph-check' : 'ph-plus');
      if (icon && b.classList.contains('tc-save')) icon.className = (on ? 'ph-fill' : 'ph-bold') + ' ph-heart';
    });
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-shortlist]');
    if (!b) return;
    e.preventDefault();
    var code = b.getAttribute('data-code'), name = b.getAttribute('data-name');
    if (has(code)) { api.remove(code); toast(name + ' removed from your shortlist'); return; }
    if (api.add({ code: code, name: name, photo: b.getAttribute('data-photo'), meta: b.getAttribute('data-meta') })) {
      toast(name + ' added to your shortlist');
      fly(b);
    }
  });

  // A small photo flies from the card to the tray.
  function fly(from) {
    if (!tray || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var card = from.closest('[data-tcard]') || from.closest('.tp-grid');
    var img = card && card.querySelector('img');
    var target = tray.querySelector('[data-sl-faces]');
    if (!img || !target) return;
    var a = img.getBoundingClientRect(), b = target.getBoundingClientRect();
    var ghost = img.cloneNode();
    ghost.className = 'sl-ghost';
    ghost.style.cssText = 'left:' + (a.left + a.width / 2 - 30) + 'px;top:' + (a.top + a.height / 2 - 30) + 'px';
    document.body.appendChild(ghost);
    requestAnimationFrame(function () {
      ghost.style.transform = 'translate(' + (b.left - a.left - a.width / 2 + 40) + 'px,' + (b.top - a.top - a.height / 2 + 10) + 'px) scale(.35)';
      ghost.style.opacity = '0.2';
    });
    setTimeout(function () { ghost.remove(); tray.classList.remove('bump'); void tray.offsetWidth; tray.classList.add('bump'); }, 650);
  }

  /* Tray */
  function render() {
    syncButtons();
    var onContact = document.body.classList.contains('page-contact') || !!document.querySelector('[data-picked]');
    if (tray) {
      var n = list.length;
      tray.hidden = n === 0 || onContact;
      document.body.classList.toggle('has-sl-tray', !tray.hidden);
      tray.querySelector('[data-sl-count]').textContent = n + ' creator' + (n === 1 ? '' : 's');
      tray.querySelector('[data-sl-faces]').innerHTML = list.slice(-4).map(function (x) {
        return '<img src="' + esc(x.photo) + '" alt="">';
      }).join('') + (n > 4 ? '<span>+' + (n - 4) + '</span>' : '');
      tray.querySelector('[data-sl-request]').href = api.requestUrl();
      tray.querySelector('[data-sl-items]').innerHTML = list.map(function (x) {
        return '<li><img src="' + esc(x.photo) + '" alt=""><a href="' + esc(base + 'talent/' + x.code) + '"><b>' + esc(x.name) + '</b><small>' + esc(x.meta) + '</small></a>' +
          '<button type="button" class="icon-btn" data-sl-remove="' + esc(x.code) + '" aria-label="Remove ' + esc(x.name) + '"><i class="ph-bold ph-x"></i></button></li>';
      }).join('');
    }
    // Brief form without picked creators: offer to add the shortlist.
    var offer = document.querySelector('[data-sl-offer]');
    if (offer) {
      offer.hidden = list.length === 0;
      var oc = offer.querySelector('[data-sl-offer-count]');
      if (oc) oc.textContent = list.length + ' creator' + (list.length === 1 ? '' : 's');
      var ol = offer.querySelector('[data-sl-offer-link]');
      if (ol) ol.href = api.requestUrl();
    }
  }

  if (tray) {
    var panel = tray.querySelector('[data-sl-panel]');
    var toggle = tray.querySelector('[data-sl-toggle]');
    var setOpen = function (open) {
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      tray.classList.toggle('is-open', open);
    };
    toggle.addEventListener('click', function () { setOpen(panel.hidden); });
    tray.addEventListener('click', function (e) {
      var r = e.target.closest('[data-sl-remove]');
      if (r) { api.remove(r.getAttribute('data-sl-remove')); if (!list.length) setOpen(false); }
      if (e.target.closest('[data-sl-clearall]')) { api.clear(); setOpen(false); toast('Shortlist cleared'); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { setOpen(false); toggle.focus(); } });
    document.addEventListener('click', function (e) { if (!panel.hidden && !tray.contains(e.target)) setOpen(false); });
  }

  // Sent a quote request: the shortlist has done its job.
  if (document.querySelector('[data-sl-clear-on-load]')) { list = []; write(); }

  // Another tab changed the shortlist.
  window.addEventListener('storage', function (e) { if (e.key === KEY) { list = read(); render(); } });
  render();
})();
