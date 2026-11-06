"""Exercise the actual PHP form with deterministic, intercepted profile responses."""
import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('SOCIAL_TEST_BASE_URL', 'http://127.0.0.1:8897')
with sync_playwright() as p:
    browser = p.chromium.launch(executable_path=os.environ.get('CHROME_PATH', r'C:\Program Files\Google\Chrome\Application\chrome.exe'), headless=True)
    page = browser.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    # External fonts/images/analytics must not affect deterministic local checks.
    page.route('https://**/*', lambda route: route.abort())
    page.goto(BASE + '/join', wait_until='networkidle')
    assert page.locator('[data-join]').count() == 1
    page.evaluate("document.querySelectorAll('[data-jstep]').forEach(e => { e.hidden = false; e.style.display = 'block'; }); document.querySelector('.join-main').classList.remove('intro-mode');")
    requests = []

    def reply(route):
        from urllib.parse import parse_qs
        payload = parse_qs(route.request.post_data)
        requests.append(payload)
        if payload['profile'][0] == 'unavailable':
            result = {'status': 'unavailable', 'followers': None, 'average_views': None, 'message': 'Stats unavailable. Enter them manually.'}
        elif payload['profile'][0] == 'partial':
            result = {'status': 'partial', 'followers': 0, 'average_views': None, 'message': 'Partial stats.'}
        else:
            result = {'status': 'ok', 'followers': 12000, 'average_views': 250,
                      'average_likes': 75, 'likes_sample_size': 2,
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
    assert 'Avg. likes: 75' in channel.locator('[data-metrics-status]').inner_text()
    assert not channel.locator('[data-followers]').is_editable()
    assert not channel.locator('[data-views]').is_editable()
    assert page.locator('[name=monthly_impressions]').count() == 0
    assert page.locator('[data-total-followers]').inner_text() == '12K'
    assert requests[0]['_token'][0]
    assert 'does not look like' not in channel.locator('[data-link-hint]').inner_text()
    channel.locator('[data-preview-handle]').fill('unavailable')
    assert channel.locator('[data-followers]').input_value() == ''
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-metrics-status]').textContent.includes('Stats unavailable')")
    assert channel.locator('[data-followers]').input_value() == ''
    channel.locator('[data-followers]').fill('0')
    channel.locator('[data-views]').fill('77')
    channel.locator('[data-preview-handle]').fill('partial')
    page.wait_for_function("document.querySelector('[name=instagram_followers]').readOnly")
    assert channel.locator('[data-followers]').input_value() == '0'
    assert channel.locator('[data-views]').is_editable()
    channel.locator('[data-views]').fill('100')
    # Smart paste must trigger lookup, not just fill the link visually.
    page.locator('[data-smart-link]').fill('https://youtube.com/@Example')
    page.locator('[data-smart-link]').press('Enter')
    page.wait_for_function("document.querySelector('[name=youtube_followers]').value === '12000'")
    assert page.locator('[data-total-followers]').inner_text() == '12K'
    assert not page.locator('[name=youtube_followers]').is_editable()
    assert not page.locator('[name=youtube_avg_views]').is_editable()
    # Unsupported channels retain a useful manual-entry path without hitting the backend.
    facebook = page.locator('[data-channel=facebook]')
    facebook.locator('[data-channel-toggle]').click()
    facebook.locator('[data-preview-handle]').fill('example')
    assert facebook.locator('[data-fetch-metrics]').is_disabled()
    assert 'manually' in facebook.locator('[data-metrics-status]').inner_text()
    # A successful delayed lookup replaces provisional manual entries and locks them.
    page.unroute('**/api/social-profile', reply)
    page.route('**/api/social-profile', lambda route: route.fulfill(status=503, content_type='application/json', body=json.dumps({'message': 'Lookup failed. Enter manually.'})))
    channel.locator('[data-fetch-metrics]').click()
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-metrics-status]').textContent.includes('Lookup failed')")
    assert channel.locator('[data-followers]').is_editable()
    assert channel.locator('[data-views]').is_editable()
    page.unroute('**/api/social-profile')
    page.route('**/api/social-profile', lambda route: route.fulfill(status=200, content_type='application/json', body=json.dumps({
        'status': 'ok', 'followers': 9000, 'average_views': 200, 'sample_size': 2, 'message': 'Fetched'})))
    page.evaluate("""() => {
      const original = window.fetch;
      window.fetch = (...args) => original(...args).then(response => new Promise(resolve => setTimeout(() => resolve(response), 700)));
    }""")
    channel.locator('[data-preview-handle]').fill('new.profile')
    page.wait_for_function("document.querySelector('[data-channel=instagram] [data-fetch-metrics]').textContent === 'Fetching…'")
    channel.locator('[data-followers]').fill('777')
    page.wait_for_function("document.querySelector('[name=instagram_followers]').readOnly")
    assert channel.locator('[data-followers]').input_value() == '9000'
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
    endpoint = BASE + '/api/social-profile'
    bad = page.request.post(endpoint, form={'_token': 'wrong', 'platform': 'instagram', 'profile': 'example'}, headers={'Accept': 'application/json'})
    assert bad.status == 419
    invalid = page.request.post(endpoint, form={'_token': token, 'platform': 'instagram', 'profile': 'https://127.0.0.1/'})
    assert invalid.status == 422, (invalid.status, invalid.text()[:1000])
    actual = page.request.post(endpoint, form={'_token': token, 'platform': 'youtube', 'profile': 'fixture.profile'})
    assert actual.status == 200, actual.text()
    assert actual.json()['followers'] == 1500
    assert 'estimated_impressions' not in actual.json()
    assert 'views_followers_percent' not in actual.json()
    cached = page.request.post(endpoint, form={'_token': token, 'platform': 'youtube', 'profile': '@fixture.profile'})
    assert cached.json()['cached'] is True
    # Exercise the real registration handler; fixture creator already has a photo.
    niche = page.locator('[name="niches[]"]').first.get_attribute('value')
    submission = {
        '_token': token, 'full_name': 'Test Creator', 'email': 'social-test@example.test',
        'phone': '+919999999999', 'country': 'India', 'state': 'Kerala', 'city': 'Kochi',
        'youtube': 'fixture.profile', 'youtube_followers': '999999', 'youtube_avg_views': '999999',
        'fee_min': '100', 'fee_max': '200', 'currency': 'INR', 'consent_terms': '1', 'niches[]': niche,
    }
    invalid_submission = page.request.post(BASE + '/join', form=dict(submission, full_name=''))
    import re
    assert re.search(r'<input[^>]+name="youtube_followers"[^>]+value="1500"[^>]+readonly', invalid_submission.text())
    response = page.request.post(BASE + '/join', form=submission)
    assert response.text() == 'Saved', response.text()[:2000]
    import sqlite3
    with sqlite3.connect(os.environ.get('SOCIAL_TEST_DB', str(ROOT / 'storage' / 'social-test-browser.sqlite'))) as connection:
        row = connection.execute("SELECT total_followers, youtube_avg_views, social_metrics FROM influencers WHERE email = 'social-test@example.test'").fetchone()
        assert row[:2] == (1500, 25), row
        assert json.loads(row[2])['youtube']['followers_source'] == 'fetched'
        creator_id = connection.execute("SELECT id FROM influencers WHERE email = 'social-test@example.test'").fetchone()[0]
    # Real failed lookup permits manually supplied metrics on registration.
    failed = page.request.post(endpoint, form={'_token': token, 'platform': 'youtube', 'profile': 'unavailable'})
    assert failed.json()['status'] == 'unavailable', failed.text()
    manual = page.request.post(BASE + '/join', form=dict(submission, youtube='unavailable', youtube_followers='100', youtube_avg_views='10'))
    assert manual.text() == 'Saved', manual.text()[:1000]
    with sqlite3.connect(os.environ['SOCIAL_TEST_DB']) as connection:
        row = connection.execute("SELECT total_followers, youtube_avg_views, social_metrics FROM influencers WHERE id = ?", (creator_id,)).fetchone()
        assert row[:2] == (100, 10)
        assert json.loads(row[2])['youtube']['views_source'] == 'manual'
    # Estimates and editable assumptions are confined to authenticated admin views.
    anonymous = page.request.get(BASE + '/admin', max_redirects=0)
    assert anonymous.status == 302 and '/admin/login' in anonymous.headers['location']
    denied = page.request.post(BASE + '/admin/impression-model', form={'_token': token, 'instagram_multiplier': '2', 'youtube_multiplier': '2'}, max_redirects=0)
    assert denied.status == 302 and '/admin/login' in denied.headers['location']
    page.request.get(BASE + '/test/admin-login')
    page.goto(BASE + '/admin', wait_until='networkidle')
    estimates = page.locator('#impression-estimates')
    youtube_row = estimates.locator('tbody tr').filter(has_text='YouTube')
    assert youtube_row.locator('td').all_text_contents() == ['YouTube', '1', '10', '10', '10.00%']
    assert estimates.locator('tbody tr').filter(has_text='Instagram').locator('td').all_text_contents() == ['Instagram', '0', '—', '—', '—']
    admin_token = estimates.locator('[name=_token]').input_value()
    csrf_fail = page.request.post(BASE + '/admin/impression-model', form={'_token': 'wrong', 'instagram_multiplier': '2', 'youtube_multiplier': '2'}, headers={'Accept': 'application/json'})
    assert csrf_fail.status == 419
    page.request.post(BASE + '/admin/impression-model', form={'_token': admin_token, 'instagram_multiplier': '1.2', 'youtube_multiplier': '2'})
    page.reload(wait_until='networkidle')
    assert youtube_row.locator('td').all_text_contents() == ['YouTube', '1', '10', '20', '10.00%']
    page.request.post(BASE + '/admin/impression-model', form={'_token': admin_token, 'instagram_multiplier': '1.2', 'youtube_multiplier': '0'})
    page.reload(wait_until='networkidle')
    assert page.locator('[name=youtube_multiplier]').input_value() == '2'
    page.screenshot(path=str(ROOT / 'storage' / 'social-admin-test.png'), full_page=True)
    page.goto(BASE + f'/admin/influencers/{creator_id}', wait_until='networkidle')
    assert 'Estimated impressions per video (planning): 20' in page.locator('body').inner_text()
    assert 'Views ÷ followers: 10.00%' in page.locator('body').inner_text()
    assert not errors, errors
    browser.close()
print('PASS: browser metric locks, partial/failed fallback, race handling, server tamper protection, validation re-render, admin formulas/settings/auth/CSRF and saved provenance')
