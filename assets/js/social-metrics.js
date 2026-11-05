(function () {
  'use strict';
  var form = document.querySelector('[data-join]');
  if (!form || !window.fetch || !window.AbortController) return;
  var supported = ['instagram', 'youtube', 'tiktok'];
  var states = [];
  // PHP sessions serialize requests; queue lookups so each gets its full time budget.
  var queue = Promise.resolve();
  function notify(field) { field.dispatchEvent(new Event('input', { bubbles: true })); }
  form.querySelectorAll('[data-channel]').forEach(function (channel) {
    var link = channel.querySelector('[data-preview-handle]');
    var button = channel.querySelector('[data-fetch-metrics]');
    var status = channel.querySelector('[data-metrics-status]');
    var wrapper = channel.querySelector('[data-lookup-url]');
    if (!button || !status || !wrapper) return;
    var fields = [channel.querySelector('[data-followers]'), channel.querySelector('[data-views]')];
    var state = { version: 0, handle: link.value.trim(), timer: null, controller: null, pending: false };
    states.push(state);
    var available = supported.indexOf(channel.dataset.channel) !== -1;

    function syncButton() {
      button.disabled = link.disabled || !link.value.trim() || state.pending || !available;
      button.textContent = state.pending ? 'Fetching…' : 'Fetch stats';
      if (!available) status.textContent = 'Enter your stats manually for this platform. Automatic lookup is not available yet.';
    }
    function invalidate() {
      state.version++;
      if (state.controller) state.controller.abort();
      clearTimeout(state.timer);
      state.pending = false;
      status.textContent = '';
    }
    function changed() {
      var value = link.value.trim();
      if (value !== state.handle) {
        invalidate();
        // Counts belong to the old profile; never carry them to a new handle.
        fields.forEach(function (field) { field.value = ''; notify(field); });
        state.handle = value;
      }
      syncButton();
      clearTimeout(state.timer);
      if (value && !link.disabled && available) state.timer = setTimeout(function () { fetchStats(false); }, 800);
    }
    function fetchStats(force) {
      clearTimeout(state.timer);
      if (!available || link.disabled || !link.value.trim() || state.pending) return;
      var value = link.value.trim();
      var version = ++state.version;
      var before = fields.map(function (field) { return field.value; });
      state.pending = true;
      status.textContent = 'Looking up public stats… You can continue filling out the form.';
      syncButton();
      queue = queue.catch(function () {}).then(async function () {
        if (version !== state.version || link.disabled) return;
        state.controller = new AbortController();
        var timeout = setTimeout(function () { state.controller.abort(); }, 32000);
        try {
          var payload = new URLSearchParams();
          payload.set('_token', form.elements._token.value);
          payload.set('platform', channel.dataset.channel);
          payload.set('profile', value);
          var response = await fetch(wrapper.dataset.lookupUrl, {
            method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' },
            body: payload, signal: state.controller.signal
          });
          var result = await response.json();
          if (version !== state.version || link.disabled || link.value.trim() !== value) return;
          if (!response.ok) throw new Error(result.message || result.error || 'Lookup failed. Enter your stats manually.');
          var values = [result.followers, result.average_views];
          var retainedManual = false;
          fields.forEach(function (field, i) {
            if (values[i] !== null && values[i] !== undefined && field.value === before[i] && (force || before[i] === '')) {
              field.value = String(values[i]);
              notify(field);
            } else if (field.value !== '' && values[i] !== null && values[i] !== undefined && field.value !== String(values[i])) retainedManual = true;
          });
          var message = result.message || 'Stats are unavailable. You can enter them manually.';
          if (result.sample_size > 0) message += ' Average based on ' + result.sample_size + ' videos. ' + (result.sample_description || '');
          if (result.fetched_at) message += ' Checked ' + new Date(result.fetched_at).toLocaleString() + (result.cached ? ' (cached).' : '.');
          if (result.followers === null) message += ' Follower count unavailable.';
          if (result.average_views === null) message += ' Average views unavailable.';
          if (retainedManual) message += ' Your manually entered numbers were kept.';
          status.textContent = message;
        } catch (error) {
          if (version === state.version) status.textContent = error.name === 'AbortError'
            ? 'Lookup timed out. Enter stats manually or try again.' : error.message;
        } finally {
          clearTimeout(timeout);
          if (version === state.version) { state.pending = false; state.controller = null; syncButton(); }
        }
      });
    }
    link.addEventListener('input', changed);
    button.addEventListener('click', function () { fetchStats(true); });
    channel.querySelector('[data-channel-toggle]').addEventListener('click', function () {
      if (link.disabled) invalidate();
      else if (link.value.trim() && !fields[0].value) fetchStats(false);
      syncButton();
    });
    syncButton();
    if (!link.disabled && link.value.trim() && !fields[0].value && available) fetchStats(false);
  });

  // Finish pending fetches before the existing form validation/submission runs.
  form.addEventListener('submit', function (event) {
    if (!states.some(function (state) { return state.pending; })) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    var submitter = event.submitter;
    if (window.pkToast) window.pkToast('Finishing your profile stats lookup…');
    queue.finally(function () {
      if (form.requestSubmit) form.requestSubmit(submitter || undefined);
    });
  }, true);
})();
