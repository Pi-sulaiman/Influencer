"""Exercise the actual PHP form with deterministic, intercepted profile responses."""
import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
with sync_playwright() as p:
    browser = p.chromium.launch(executable_path=os.environ.get('CHROME_PATH', r'C:\Program Files\Google\Chrome\Application\chrome.exe'), headless=True)
    page = browser.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    # External fonts/images/analytics must not affect deterministic local checks.
    page.route('https://**/*', lambda route: route.abort())
    page.goto('http://127.0.0.1:8897/join', wait_until='networkidle')
    assert page.locator('[data-join]').count() == 1
    page.evaluate("document.querySelectorAll('[data-jstep]').forEach(e => { e.hidden = false; e.style.display = 'block'; }); document.querySelector('.join-main').classList.remove('intro-mode');")
    requests = []

    def reply(route):
        from urllib.parse import parse_qs
        payload = parse_qs(route.request.post_data)
        requests.append(payload)
        if payload['profile'][0] == 'unavailable':
            result = {'status': 'unavailable', 'followers': None, 'average_views': None, 'message': 'Stats unavailable. Enter them manually.'}
        else:
            result = {'status': 'ok', 'followers': 12000, 'average_views': 250,
                      'sample_size': 2, 'sample_description': 'Two fixture videos.',
                      'fetched_at': '2026-11-05T12:00:00Z', 'message': 'Public stats fetched.'}
        route.fulfill(status=200, content_type='application/json', body=json.dumps(result))

    page.route('**/api/social-profile', reply)
    channel = page.locator('[data-channel="instagram"]')
    channel.locator('[data-channel-toggle]').click()
    channel.locator('[data-preview-handle]').fill('some.name')
    page.wait_for_function("document.querySelector('[name=instagram_followers]').value === '12000'")
    assert channel.locator('[data-views]').input_value() == '250'
    assert '2 videos' in channel.locator('[data-metrics-status]').inner_text()
    assert page.locator('[data-total-followers]').inner_text() == '12K'
    assert requests[0]['_token'][0]
    assert 'does not look like' not in channel.locator('[data-link-hint]').inner_text()
    channel.locator('[data-preview-handle]').fill('unavailable')
    assert channel.locator('[data-followers]').input_value() == ''
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-metrics-status]').textContent.includes('Stats unavailable')")
    assert channel.locator('[data-followers]').input_value() == ''
    channel.locator('[data-followers]').fill('0')
    # Smart paste must trigger lookup, not just fill the link visually.
    page.locator('[data-smart-link]').fill('https://youtube.com/@Example')
    page.locator('[data-smart-link]').press('Enter')
    page.wait_for_function("document.querySelector('[name=youtube_followers]').value === '12000'")
    assert page.locator('[data-total-followers]').inner_text() == '12K'
    # Unsupported channels retain a useful manual-entry path without hitting the backend.
    facebook = page.locator('[data-channel=facebook]')
    facebook.locator('[data-channel-toggle]').click()
    facebook.locator('[data-preview-handle]').fill('example')
    assert facebook.locator('[data-fetch-metrics]').is_disabled()
    assert 'manually' in facebook.locator('[data-metrics-status]').inner_text()
    # Delayed response must not overwrite a manual edit made while the lookup runs.
    page.unroute('**/api/social-profile', reply)
    page.route('**/api/social-profile', lambda route: route.fulfill(status=200, content_type='application/json', body=json.dumps({
        'status': 'ok', 'followers': 9000, 'average_views': 200, 'sample_size': 2, 'message': 'Fetched'})))
    page.evaluate("""() => {
      const original = window.fetch;
      window.fetch = (...args) => original(...args).then(response => new Promise(resolve => setTimeout(() => resolve(response), 700)));
    }""")
    channel.locator('[data-preview-handle]').fill('new.profile')
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-fetch-metrics]').textContent === 'Fetching…'")
    channel.locator('[data-followers]').fill('777')
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-metrics-status]').textContent.includes('manually entered numbers were kept')")
    assert channel.locator('[data-followers]').input_value() == '777'
    # Removing a channel cancels its pending response.
    channel.locator('[data-preview-handle]').fill('cancel.profile')
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-fetch-metrics]').textContent === 'Fetching…'")
    channel.locator('[data-channel-toggle]').click()
    page.wait_for_timeout(1100)
    assert channel.locator('[data-followers]').input_value() == ''
    assert channel.locator('[data-preview-handle]').is_disabled()
    page.screenshot(path=str(ROOT / 'storage' / 'social-browser-test.png'), full_page=True)
    assert not errors, errors
    # Real endpoint and PHP transport (APIRequestContext bypasses page.route fixtures).
    token = page.locator('[data-join] [name=_token]').input_value()
    endpoint = 'http://127.0.0.1:8897/api/social-profile'
    bad = page.request.post(endpoint, form={'_token': 'wrong', 'platform': 'instagram', 'profile': 'example'}, headers={'Accept': 'application/json'})
    assert bad.status == 419
    invalid = page.request.post(endpoint, form={'_token': token, 'platform': 'instagram', 'profile': 'https://127.0.0.1/'})
    assert invalid.status == 422, (invalid.status, invalid.text()[:1000])
    actual = page.request.post(endpoint, form={'_token': token, 'platform': 'instagram', 'profile': 'fixture.profile'})
    assert actual.status == 200, actual.text()
    assert actual.json()['followers'] == 1500
    cached = page.request.post(endpoint, form={'_token': token, 'platform': 'instagram', 'profile': '@fixture.profile'})
    assert cached.json()['cached'] is True
    # Exercise the real registration handler; fixture creator already has a photo.
    niche = page.locator('[name="niches[]"]').first.get_attribute('value')
    response = page.request.post('http://127.0.0.1:8897/join', form={
        '_token': token, 'full_name': 'Test Creator', 'email': 'social-test@example.test',
        'phone': '+919999999999', 'country': 'India', 'state': 'Kerala', 'city': 'Kochi',
        'instagram': 'fixture.profile', 'instagram_followers': '1500', 'instagram_avg_views': '25',
        'fee_min': '100', 'fee_max': '200', 'currency': 'INR', 'consent_terms': '1', 'niches[]': niche,
    })
    assert response.text() == 'Saved', response.text()[:2000]
    import sqlite3
    with sqlite3.connect(ROOT / 'storage' / 'social-test-browser.sqlite') as connection:
        row = connection.execute("SELECT total_followers, instagram_avg_views, social_metrics FROM influencers WHERE email = 'social-test@example.test'").fetchone()
        assert row[:2] == (1500, 25), row
        assert json.loads(row[2])['instagram']['followers_source'] == 'fetched'
    browser.close()
print('PASS: browser autofill, dotted handles, smart paste, manual fallback, race handling, totals, CSRF, transport, cache and saved registration provenance')
