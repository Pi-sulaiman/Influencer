/* Blog editor: lightweight rich text editing plus a live SEO checker.
   Content is sanitised again on the server before saving. */
(function () {
  'use strict';
  var form = document.querySelector('[data-post-form]');
  if (!form) return;
  var $ = function (s, r) { return (r || form).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || form).querySelectorAll(s)); };
  var toast = window.pkToast || function () {};
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  var area = $('[data-ed-area]');
  var source = $('[data-ed-source]');
  var input = $('[data-ed-input]');
  var fileInput = $('[data-ed-file]');
  var sourceMode = false;
  try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

  var html = function () { return sourceMode ? source.value : area.innerHTML; };
  var sync = function () { input.value = html(); analyse(); counts(); };

  /* ---------- Toolbar ---------- */
  var savedRange = null;
  var saveRange = function () {
    var sel = window.getSelection();
    if (sel.rangeCount && area.contains(sel.anchorNode)) savedRange = sel.getRangeAt(0).cloneRange();
  };
  var restoreRange = function () {
    area.focus();
    if (savedRange) { var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(savedRange); }
  };
  area.addEventListener('keyup', saveRange);
  area.addEventListener('mouseup', saveRange);
  area.addEventListener('input', sync);

  $$('[data-cmd]').forEach(function (btn) {
    btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
    btn.addEventListener('click', function () {
      var cmd = btn.dataset.cmd;
      if (cmd === 'source') return toggleSource(btn);
      if (sourceMode) { toast('Switch back from HTML mode to use the toolbar'); return; }
      restoreRange();
      if (cmd === 'link') {
        var url = prompt('Link address (https://... or /page)');
        if (!url) return;
        if (!/^(https?:\/\/|\/|mailto:|#)/i.test(url)) url = 'https://' + url;
        document.execCommand('createLink', false, url);
        if (/^https?:\/\//i.test(url) && url.indexOf(location.host) === -1) {
          var a = window.getSelection().anchorNode;
          a = a && (a.nodeType === 3 ? a.parentNode : a).closest('a');
          if (a) { a.target = '_blank'; a.rel = 'noopener'; }
        }
      } else if (cmd === 'image') {
        fileInput.click();
        return;
      } else if (cmd === 'youtube') {
        var yt = prompt('YouTube video URL');
        var m = yt && yt.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([\w-]{11})/);
        if (!m) { if (yt) toast('That does not look like a YouTube link'); return; }
        document.execCommand('insertHTML', false, '<p><iframe src="https://www.youtube-nocookie.com/embed/' + m[1] + '" title="YouTube video" loading="lazy" allowfullscreen></iframe></p><p><br></p>');
      } else if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, '<' + btn.dataset.val + '>');
      } else {
        document.execCommand(cmd, false, null);
      }
      sync();
    });
  });

  function toggleSource(btn) {
    if (!sourceMode) {
      source.value = area.innerHTML.replace(/<\/(p|h2|h3|h4|ul|ol|li|blockquote|figure)>/g, '</$1>\n');
      area.hidden = true; source.hidden = false; sourceMode = true; source.focus();
    } else {
      area.innerHTML = source.value;
      source.hidden = true; area.hidden = false; sourceMode = false; area.focus();
    }
    btn.classList.toggle('is-active', sourceMode);
    sync();
  }
  source.addEventListener('input', sync);

  /* ---------- Paste as clean text ---------- */
  area.addEventListener('paste', function (e) {
    var cd = e.clipboardData;
    if (!cd) return;
    var file = cd.files && cd.files[0];
    if (file && /^image\//.test(file.type)) { e.preventDefault(); upload(file); return; }
    var text = cd.getData('text/plain');
    if (!text) return;
    e.preventDefault();
    var safe = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    var paras = safe.split(/\n{2,}/).map(function (p) { return '<p>' + p.replace(/\n/g, '<br>') + '</p>'; }).join('');
    document.execCommand('insertHTML', false, paras.indexOf('</p><p>') > -1 ? paras : safe.replace(/\n/g, '<br>'));
  });

  /* ---------- Image upload ---------- */
  function upload(file) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('_token', csrf);
    toast('Uploading image...');
    fetch(form.action.replace(/admin\/posts.*$/, 'admin/upload'), { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) { toast(d.error || 'Upload failed'); return; }
        var alt = prompt('Describe this image (alt text helps SEO)') || '';
        restoreRange();
        document.execCommand('insertHTML', false, '<figure><img src="' + d.url + '" alt="' + alt.replace(/"/g, '&quot;') + '" loading="lazy"></figure><p><br></p>');
        sync();
        toast('Image added');
      })
      .catch(function () { toast('Upload failed. Check your connection.'); });
  }
  fileInput.addEventListener('change', function () { if (fileInput.files[0]) upload(fileInput.files[0]); fileInput.value = ''; });
  area.addEventListener('drop', function (e) {
    var f = e.dataTransfer && e.dataTransfer.files[0];
    if (f && /^image\//.test(f.type)) { e.preventDefault(); upload(f); }
  });

  /* ---------- Shortcuts ---------- */
  area.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); form.querySelector('button[value=draft]').click(); }
  });
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('submit', function () { input.value = html(); dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  /* ---------- Slug ---------- */
  var title = $('[data-seo-title]');
  var slug = $('[data-seo-slug]');
  var slugify = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 90); };
  var slugTouched = !slug.dataset.auto;
  slug.addEventListener('input', function () { slugTouched = true; analyse(); });
  title.addEventListener('input', function () { if (!slugTouched) slug.value = slugify(title.value); analyse(); });

  /* ---------- Counts ---------- */
  function textOf(h) { var d = document.createElement('div'); d.innerHTML = h; return (d.textContent || '').trim(); }
  function counts() {
    var words = textOf(html()).split(/\s+/).filter(Boolean).length;
    $('[data-ed-words]').textContent = words + ' word' + (words === 1 ? '' : 's');
    $('[data-ed-read]').textContent = Math.max(1, Math.ceil(words / 220)) + ' min read';
    return words;
  }

  /* ---------- SEO analysis ---------- */
  var kw = $('[data-seo-kw]'), mt = $('[data-seo-mt]'), md = $('[data-seo-md]'), alt = $('[data-seo-alt]');
  var serpTitle = $('[data-serp-title]'), serpDesc = $('[data-serp-desc]'), serpSlug = $('[data-serp-slug]');
  var checksEl = $('[data-seo-checks]');
  var siteName = ' | Peakilo';
  function meter(elCount, elMeter, len, lo, hi, max) {
    $(elCount).textContent = len + ' characters';
    var m = $(elMeter);
    m.style.setProperty('--w', Math.min(100, len / max * 100) + '%');
    m.style.setProperty('--c', len === 0 ? '#D9D0C7' : (len >= lo && len <= hi ? '#14804A' : '#C27803'));
  }
  function analyse() {
    var t = (mt.value.trim() || title.value.trim());
    var d = md.value.trim();
    var k = kw.value.trim().toLowerCase();
    var h = html();
    var text = textOf(h).toLowerCase();
    var words = text.split(/\s+/).filter(Boolean).length;
    var excerpt = ($('[name=excerpt]').value || '').trim();

    serpTitle.textContent = (t || 'Post title') + siteName;
    serpDesc.textContent = d || excerpt || text.slice(0, 155) || 'Add a meta description to control what Google shows here.';
    serpSlug.textContent = slug.value || 'post';
    meter('[data-mt-count]', '[data-mt-meter]', t.length, 30, 60, 70);
    meter('[data-md-count]', '[data-md-meter]', d.length, 120, 160, 180);

    var tmp = document.createElement('div');
    tmp.innerHTML = h;
    var h2s = tmp.querySelectorAll('h2').length;
    var imgs = tmp.querySelectorAll('img');
    var imgsNoAlt = Array.prototype.filter.call(imgs, function (i) { return !i.getAttribute('alt'); }).length;
    var links = Array.prototype.map.call(tmp.querySelectorAll('a[href]'), function (a) { return a.getAttribute('href'); });
    var internal = links.filter(function (l) { return /^\//.test(l) || l.indexOf(location.host) > -1; }).length;
    var firstP = (tmp.querySelector('p') || { textContent: '' }).textContent.toLowerCase();
    var kwCount = k ? text.split(k).length - 1 : 0;
    var density = k && words ? (kwCount * k.split(/\s+/).length / words * 100) : 0;
    var kwSlug = k ? slugify(k) : '';

    var checks = [
      [!!k, 'Set a focus keyword', 'Focus keyword is set'],
      [k && t.toLowerCase().indexOf(k) > -1, 'Put the focus keyword in the SEO title', 'Focus keyword is in the title'],
      [k && d.toLowerCase().indexOf(k) > -1, 'Use the focus keyword in the meta description', 'Focus keyword is in the meta description'],
      [k && (slug.value || '').indexOf(kwSlug) > -1, 'Include the focus keyword in the URL slug', 'Focus keyword is in the URL'],
      [k && firstP.indexOf(k) > -1, 'Mention the focus keyword in the first paragraph', 'Focus keyword appears in the first paragraph'],
      [t.length >= 30 && t.length <= 60, 'Keep the SEO title between 30 and 60 characters (now ' + t.length + ')', 'Title length is good'],
      [d.length >= 120 && d.length <= 160, 'Write a meta description of 120 to 160 characters (now ' + d.length + ')', 'Meta description length is good'],
      [words >= 600, 'Aim for at least 600 words (now ' + words + ')', 'Content length is good (' + words + ' words)'],
      [h2s >= 2, 'Break the post into sections with at least two H2 headings', 'Uses ' + h2s + ' H2 headings'],
      [internal >= 1, 'Add a link to another page on your site', 'Has internal links'],
      [($('[data-cover-drop] img') && alt.value.trim() !== '') || false, 'Add a cover image with alt text', 'Cover image has alt text'],
      [imgsNoAlt === 0, imgsNoAlt + ' image(s) in the post are missing alt text', 'All images have alt text'],
      [!k || density <= 3, 'Keyword density is high (' + density.toFixed(1) + '%). Write naturally.', 'Keyword density looks natural']
    ];
    var passed = 0;
    checksEl.innerHTML = '';
    checks.forEach(function (c) {
      var ok = !!c[0];
      if (ok) passed++;
      var li = document.createElement('li');
      li.className = ok ? 'ok' : 'warn';
      li.innerHTML = '<i class="ph-fill ' + (ok ? 'ph-check-circle' : 'ph-warning-circle') + '"></i><span></span>';
      li.querySelector('span').textContent = ok ? c[2] : c[1];
      checksEl.appendChild(li);
    });
    // Show failing checks first
    Array.prototype.slice.call(checksEl.children).sort(function (a, b) { return (a.className === 'ok') - (b.className === 'ok'); }).forEach(function (li) { checksEl.appendChild(li); });
    var score = Math.round(passed / checks.length * 100);
    var ring = $('[data-seo-ring]');
    ring.style.setProperty('--s', score);
    ring.style.setProperty('--c', score >= 80 ? '#14804A' : score >= 50 ? '#C27803' : '#B42318');
    $('[data-seo-score]').textContent = score;
    $('[data-seo-label]').textContent = score >= 80 ? 'Good' : score >= 50 ? 'Okay' : 'Needs work';
  }
  [kw, mt, md, alt, $('[name=excerpt]')].forEach(function (i) { i.addEventListener('input', analyse); });
  $$('[data-cover-input]').forEach(function (i) { i.addEventListener('change', function () { setTimeout(analyse, 50); }); });

  sync();
  dirty = false;
})();
