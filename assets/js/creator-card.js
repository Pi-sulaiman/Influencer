/* Thank-you page: the branded Peakilo creator card (drawn on a canvas so it
   can be downloaded or shared as a PNG) and the "finish setting up" checklist
   that leads creators to the WhatsApp community and Instagram. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* =========================================================
     Finish-setup checklist (remembered in this browser)
     ========================================================= */
  var setup = $('[data-setup]');
  var KEY = 'pk-setup-' + (setup ? setup.getAttribute('data-key') : 'guest');
  var saved = {};
  try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { saved = {}; }
  var list = $('.setup-list');
  var total = list ? parseInt(list.getAttribute('data-setup-total'), 10) || 1 : 1;

  function markTask(name) {
    var li = $('[data-task="' + name + '"]');
    if (!li || li.classList.contains('is-done')) return;
    li.classList.add('is-done', 'just-done');
    setTimeout(function () { li.classList.remove('just-done'); }, 900);
    saved[name] = 1;
    try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) {}
    refreshRing();
  }
  function refreshRing() {
    var done = $$('.setup-list > li.is-done').length;
    var ring = $('[data-setup-ring]');
    if (ring) ring.style.setProperty('--p', (done / total).toFixed(3));
    var count = $('[data-setup-count]');
    if (count) count.textContent = done + '/' + total;
    var all = done >= total;
    var msg = $('[data-setup-done]');
    if (msg) msg.hidden = !all;
    if (setup) setup.classList.toggle('all-done', all);
    var sub = $('[data-setup-sub]');
    if (sub) sub.textContent = all ? 'You will hear about new campaigns first.' : (total - done === 1 ? 'One tap left.' : 'A couple of taps so you never miss a paid campaign.');
  }
  Object.keys(saved).forEach(function (k) { var li = $('[data-task="' + k + '"]'); if (li) li.classList.add('is-done'); });
  $$('[data-task-link]').forEach(function (a) {
    a.addEventListener('click', function () {
      var li = a.closest('[data-task]');
      // The other app opens in a new tab; tick the step when they come back.
      if (li) setTimeout(function () { markTask(li.getAttribute('data-task')); }, 600);
    });
  });
  refreshRing();

  /* =========================================================
     Creator card
     ========================================================= */
  var root = $('[data-cc]');
  if (!root) return;
  var data;
  try { data = JSON.parse($('[data-cc-data]', root).textContent); } catch (e) { return; }
  var canvas = $('[data-cc-canvas]', root);
  var ctx = canvas.getContext('2d');
  var loading = $('[data-cc-loading]', root);
  var state = { theme: 'red', format: 'post', rates: false };
  var FONT = 'Satoshi, "Segoe UI", system-ui, sans-serif';
  var MARK = 'M0 0H28L62 40L52 52H62L72 40L38 0H66L100 40L78 66H40L24 90H0L34 40Z';

  var THEMES = {
    red:   { a: '#EF3B2A', b: '#FF7A5C', text: '#FFFFFF', sub: 'rgba(255,255,255,.82)', panel: 'rgba(255,255,255,.14)', line: 'rgba(255,255,255,.28)', pill: 'rgba(255,255,255,.2)', pillText: '#FFFFFF', mark: 'rgba(255,255,255,.11)', logo: '#FFFFFF', badge: '#10131C', badgeText: '#FFFFFF', empty: 'rgba(16,19,28,.18)' },
    ink:   { a: '#10131C', b: '#262C3D', text: '#FFFFFF', sub: 'rgba(255,255,255,.7)', panel: 'rgba(255,255,255,.06)', line: 'rgba(255,255,255,.12)', pill: 'rgba(239,59,42,.2)', pillText: '#FFAE92', mark: 'rgba(239,59,42,.18)', logo: '#EF3B2A', badge: '#EF3B2A', badgeText: '#FFFFFF', empty: 'rgba(255,255,255,.06)' },
    cream: { a: '#FAF6F2', b: '#FADDD2', text: '#10131C', sub: 'rgba(16,19,28,.62)', panel: '#FFFFFF', line: 'rgba(16,19,28,.08)', pill: 'rgba(239,59,42,.1)', pillText: '#C2261A', mark: 'rgba(239,59,42,.09)', logo: '#EF3B2A', badge: '#10131C', badgeText: '#FFFFFF', empty: 'rgba(239,59,42,.1)' }
  };

  var compact = function (n) {
    n = +n || 0;
    if (n >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
    return String(n);
  };

  // Phosphor icon glyphs, read from the icon font the page already loads.
  var glyphs = {};
  function readGlyph(cls) {
    if (glyphs[cls]) return glyphs[cls];
    var i = document.createElement('i');
    i.className = 'ph-fill ' + cls;
    i.style.cssText = 'position:absolute;left:-9999px;top:0';
    document.body.appendChild(i);
    var cs = getComputedStyle(i, '::before');
    var g = { ch: (cs.content || '').replace(/^["']|["']$/g, ''), family: getComputedStyle(i).fontFamily };
    i.remove();
    glyphs[cls] = g;
    return g;
  }

  function rr(x, y, w, h, r) {
    ctx.beginPath();
    if (ctx.roundRect) { ctx.roundRect(x, y, w, h, r); return; }
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }
  function fit(text, weight, max, min, width) {
    var size = max;
    ctx.font = weight + ' ' + size + 'px ' + FONT;
    while (size > min && ctx.measureText(text).width > width) { size -= 2; ctx.font = weight + ' ' + size + 'px ' + FONT; }
    if (ctx.measureText(text).width > width) {
      while (text.length > 3 && ctx.measureText(text + '…').width > width) text = text.slice(0, -1);
      text += '…';
    }
    return { text: text, size: size };
  }
  function mark(x, y, scale, color) {
    ctx.save();
    ctx.translate(x, y);
    ctx.scale(scale, scale);
    ctx.fillStyle = color;
    ctx.fill(new Path2D(MARK));
    ctx.restore();
  }
  function icon(cls, x, y, size, color) {
    var g = readGlyph(cls);
    if (!g.ch) return 0;
    ctx.font = size + 'px ' + g.family;
    ctx.fillStyle = color;
    ctx.textBaseline = 'middle';
    ctx.fillText(g.ch, x, y);
    var w = ctx.measureText(g.ch).width;
    ctx.textBaseline = 'alphabetic';
    return w;
  }
  function pill(text, x, y, h, bg, fg, size, weight) {
    ctx.font = (weight || 700) + ' ' + size + 'px ' + FONT;
    var w = ctx.measureText(text).width + h * 0.9;
    rr(x, y, w, h, h / 2);
    ctx.fillStyle = bg;
    ctx.fill();
    ctx.fillStyle = fg;
    ctx.textBaseline = 'middle';
    ctx.fillText(text, x + h * 0.45, y + h / 2 + 1);
    ctx.textBaseline = 'alphabetic';
    return w;
  }

  var photo = null;
  function loadPhoto() {
    return new Promise(function (resolve) {
      if (!data.photo) return resolve(null);
      var img = new Image();
      try { if (new URL(data.photo, location.href).origin !== location.origin) img.crossOrigin = 'anonymous'; } catch (e) {}
      img.onload = function () { resolve(img); };
      img.onerror = function () { resolve(null); };
      img.src = data.photo;
    });
  }
  function loadFonts() {
    if (!document.fonts || !document.fonts.load) return Promise.resolve();
    var wanted = ['900 80px Satoshi', '700 30px Satoshi', '500 30px Satoshi'];
    ['ph-instagram-logo'].concat((data.channels || []).map(function (c) { return c.icon; })).forEach(function (cls) {
      var g = readGlyph(cls);
      if (g.family) wanted.push('40px ' + g.family);
    });
    var all = Promise.all(wanted.map(function (f) { return document.fonts.load(f).catch(function () {}); }));
    // Never wait forever on a slow font server.
    return Promise.race([all, new Promise(function (r) { setTimeout(r, 2500); })]);
  }

  /* ---------- The 1080 x 1350 card ---------- */
  function drawCard(W, H) {
    var t = THEMES[state.theme], P = 64;
    var g = ctx.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, t.a);
    g.addColorStop(1, t.b);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, W, H);
    // Big brand chevron in the corner.
    mark(W - 560, H - 470, 7.2, t.mark);

    // Header: logo and creator code
    mark(P, 66, 0.56, t.logo);
    ctx.fillStyle = t.text;
    ctx.font = '900 46px ' + FONT;
    ctx.fillText('peakilo', P + 72, 110);
    ctx.font = '700 22px ' + FONT;
    var codeText = 'CREATOR  ·  ' + data.code;
    var cw = ctx.measureText(codeText).width + 40;
    rr(W - P - cw, 70, cw, 48, 24);
    ctx.fillStyle = t.pill;
    ctx.fill();
    ctx.fillStyle = t.pillText;
    ctx.textBaseline = 'middle';
    ctx.fillText(codeText, W - P - cw + 20, 95);
    ctx.textBaseline = 'alphabetic';

    // Photo
    var px = P, py = 160, pw = W - P * 2, ph = 640;
    ctx.save();
    rr(px, py, pw, ph, 44);
    ctx.clip();
    if (photo) {
      var s = Math.max(pw / photo.width, ph / photo.height);
      var dw = photo.width * s, dh = photo.height * s;
      ctx.drawImage(photo, px + (pw - dw) / 2, py + (ph - dh) / 2.6, dw, dh);
    } else {
      ctx.fillStyle = t.empty;
      ctx.fillRect(px, py, pw, ph);
      ctx.fillStyle = t.text;
      ctx.globalAlpha = 0.9;
      ctx.font = '900 260px ' + FONT;
      ctx.textAlign = 'center';
      ctx.fillText(data.initials || 'P', W / 2, py + ph / 2 + 60);
      ctx.textAlign = 'left';
      ctx.globalAlpha = 1;
    }
    var og = ctx.createLinearGradient(0, py + ph * 0.42, 0, py + ph);
    og.addColorStop(0, 'rgba(16,19,28,0)');
    og.addColorStop(1, 'rgba(16,19,28,.82)');
    ctx.fillStyle = og;
    ctx.fillRect(px, py, pw, ph);
    ctx.restore();

    // Tier badge on the photo
    if (data.tier) pill(data.tier + ' creator', px + 30, py + 30, 54, t.badge, t.badgeText, 24, 800);

    // Name and handle over the photo
    var name = fit(data.name || 'Creator', 900, 92, 50, pw - 64);
    ctx.fillStyle = '#FFFFFF';
    ctx.font = '900 ' + name.size + 'px ' + FONT;
    ctx.fillText(name.text, px + 32, py + ph - 92);
    var subParts = [];
    if (data.handle) subParts.push('@' + data.handle);
    var loc = [data.city, data.country].filter(Boolean).join(', ');
    if (loc) subParts.push(loc);
    var sub = fit(subParts.join('  ·  '), 600, 32, 22, pw - 64 - (data.primaryIcon ? 46 : 0));
    var sx = px + 32;
    if (data.primaryIcon && data.handle) sx += icon(data.primaryIcon, sx, py + ph - 52, 34, '#FFFFFF') + 10;
    ctx.fillStyle = 'rgba(255,255,255,.88)';
    ctx.font = '600 ' + sub.size + 'px ' + FONT;
    ctx.fillText(sub.text, sx, py + ph - 42);

    // Niches
    var nx = P, ny = py + ph + 30;
    (data.niches || []).forEach(function (n) {
      if (nx > W - P - 140) return;
      nx += pill(n, nx, ny, 58, t.pill, t.pillText, 26, 700) + 12;
    });

    // Stats panel
    var sy = ny + 88, sh = 168;
    rr(P, sy, W - P * 2, sh, 34);
    ctx.fillStyle = t.panel;
    ctx.fill();
    ctx.strokeStyle = t.line;
    ctx.lineWidth = 2;
    ctx.stroke();
    var stats = [[compact(data.followers), 'Followers']];
    stats.push(data.views ? [compact(data.views), 'Avg. views'] : [String((data.channels || []).length || 1), (data.channels || []).length === 1 ? 'Platform' : 'Platforms']);
    stats.push(data.er !== null && data.er !== undefined ? [(Math.round(data.er * 10) / 10) + '%', 'Engagement'] : [data.languages && data.languages.length ? data.languages[0] : (data.tier || '-'), data.languages && data.languages.length ? 'Language' : 'Size']);
    var colW = (W - P * 2) / 3;
    stats.forEach(function (st, i) {
      var cx = P + colW * i + colW / 2;
      ctx.textAlign = 'center';
      ctx.fillStyle = t.text;
      var v = fit(st[0], 900, 66, 36, colW - 30);
      ctx.font = '900 ' + v.size + 'px ' + FONT;
      ctx.fillText(v.text, cx, sy + 92);
      ctx.fillStyle = t.sub;
      ctx.font = '600 24px ' + FONT;
      ctx.fillText(st[1], cx, sy + 134);
      ctx.textAlign = 'left';
      if (i) { ctx.fillStyle = t.line; ctx.fillRect(P + colW * i, sy + 36, 2, sh - 72); }
    });

    // Per-platform followers, or the rate card when rates are shown
    var row = sy + sh + 58;
    if (state.rates && data.rates && data.rates.length) {
      var line = data.rates.slice(0, 3).map(function (r) { return r[0].replace(/ \(.*\)$/, '') + ' ' + r[1]; }).join('   ·   ');
      var rl = fit(line, 700, 30, 20, W - P * 2);
      ctx.fillStyle = t.text;
      ctx.font = '700 ' + rl.size + 'px ' + FONT;
      ctx.fillText(rl.text, P, row + 12);
    } else {
      var chans = data.channels || [];
      var slot = (W - P * 2) / Math.max(chans.length, 1);
      chans.forEach(function (c, i) {
        var x = P + slot * i;
        var w = icon(c.icon, x, row, 40, t.text);
        ctx.fillStyle = t.text;
        ctx.font = '800 32px ' + FONT;
        ctx.fillText(compact(c.followers), x + w + 12, row + 12);
      });
    }

    // Footer: rates or booking line, website
    var fy = H - 66;
    ctx.fillStyle = t.text;
    if (state.rates && data.feeFrom) {
      ctx.font = '600 24px ' + FONT;
      ctx.fillStyle = t.sub;
      ctx.fillText('RATES', P, fy - 40);
      ctx.fillStyle = t.text;
      var r = fit(data.feeFrom + (data.feeTo ? ' - ' + data.feeTo : '') + ' per post', 900, 40, 26, W * 0.6);
      ctx.font = '900 ' + r.size + 'px ' + FONT;
      ctx.fillText(r.text, P, fy);
    } else {
      ctx.font = '600 24px ' + FONT;
      ctx.fillStyle = t.sub;
      ctx.fillText('BRAND COLLABS', P, fy - 40);
      ctx.fillStyle = t.text;
      ctx.font = '900 38px ' + FONT;
      ctx.fillText('Book me via Peakilo', P, fy);
    }
    ctx.textAlign = 'right';
    ctx.fillStyle = t.sub;
    ctx.font = '700 28px ' + FONT;
    ctx.fillText(data.site || 'peakilo.com', W - P, fy);
    ctx.textAlign = 'left';
  }

  /* ---------- Story: 1080 x 1920 with the card in the middle ---------- */
  var post = document.createElement('canvas');
  function render() {
    var t = THEMES[state.theme];
    post.width = 1080; post.height = 1350;
    var main = ctx;
    ctx = post.getContext('2d');
    drawCard(1080, 1350);
    ctx = main;
    if (state.format === 'post') {
      canvas.width = 1080; canvas.height = 1350;
      ctx.drawImage(post, 0, 0);
    } else {
      canvas.width = 1080; canvas.height = 1920;
      var g = ctx.createLinearGradient(0, 0, 0, 1920);
      g.addColorStop(0, t.a);
      g.addColorStop(1, t.b);
      ctx.fillStyle = g;
      ctx.fillRect(0, 0, 1080, 1920);
      mark(-260, 1180, 7.6, t.mark);
      ctx.fillStyle = t.sub;
      ctx.font = '700 40px ' + FONT;
      ctx.textAlign = 'center';
      ctx.fillText('I just joined the', 540, 196);
      ctx.fillStyle = t.text;
      ctx.font = '900 70px ' + FONT;
      ctx.fillText('Peakilo creator network', 540, 276);
      var cw = 900, ch = 1125, cx = 90, cy = 350;
      ctx.save();
      ctx.shadowColor = 'rgba(16,19,28,.35)';
      ctx.shadowBlur = 60;
      ctx.shadowOffsetY = 24;
      rr(cx, cy, cw, ch, 48);
      ctx.fillStyle = t.a;
      ctx.fill();
      ctx.restore();
      ctx.save();
      rr(cx, cy, cw, ch, 48);
      ctx.clip();
      ctx.drawImage(post, cx, cy, cw, ch);
      ctx.restore();
      ctx.fillStyle = t.text;
      ctx.font = '800 40px ' + FONT;
      ctx.fillText('Brands, book me on Peakilo', 540, 1620);
      ctx.fillStyle = t.sub;
      ctx.font = '700 34px ' + FONT;
      ctx.fillText('Creators, join free at ' + (data.site || 'peakilo.com') + '/join', 540, 1680);
      ctx.textAlign = 'left';
    }
    root.classList.toggle('is-story', state.format === 'story');
  }

  /* ---------- Controls ---------- */
  $$('[data-cc-group]', root).forEach(function (grp) {
    var key = grp.getAttribute('data-cc-group');
    $$('button', grp).forEach(function (b) {
      b.addEventListener('click', function () {
        $$('button', grp).forEach(function (x) { x.setAttribute('aria-checked', x === b ? 'true' : 'false'); });
        state[key] = b.getAttribute('data-val');
        render();
      });
    });
  });
  var ratesToggle = $('[data-cc-rates]', root);
  if (ratesToggle) ratesToggle.addEventListener('change', function () { state.rates = ratesToggle.checked; render(); });

  var fileName = function () {
    var slug = String(data.name || 'creator').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'creator';
    return 'peakilo-' + slug + (state.format === 'story' ? '-story' : '-card') + '.png';
  };
  var toBlob = function () {
    return new Promise(function (resolve, reject) {
      try { canvas.toBlob(function (b) { b ? resolve(b) : reject(new Error('empty')); }, 'image/png'); } catch (e) { reject(e); }
    });
  };
  var toast = window.pkToast || function () {};
  function download() {
    toBlob().then(function (blob) {
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = fileName();
      document.body.appendChild(a);
      a.click();
      setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
      markTask('card');
      toast('Card saved. Post it and tag us!');
    }).catch(function () { toast('Could not save the card. Try a long press on it instead.'); });
  }
  $$('[data-cc-download]').forEach(function (b) { b.addEventListener('click', download); });

  var shareBtn = $('[data-cc-share]', root);
  if (shareBtn && navigator.canShare && window.File) {
    try {
      if (navigator.canShare({ files: [new File([new Blob(['x'], { type: 'image/png' })], 'x.png', { type: 'image/png' })] })) shareBtn.hidden = false;
    } catch (e) {}
    shareBtn.addEventListener('click', function () {
      toBlob().then(function (blob) {
        var file = new File([blob], fileName(), { type: 'image/png' });
        return navigator.share({ files: [file], title: 'My Peakilo creator card', text: 'I just joined the Peakilo creator network.' });
      }).then(function () { markTask('card'); }).catch(function () {});
    });
  }

  Promise.all([loadFonts(), loadPhoto()]).then(function (r) {
    photo = r[1];
    render();
    if (loading) loading.hidden = true;
    root.classList.add('is-ready');
  });
})();
