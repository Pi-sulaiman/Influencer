# Automatic social profile metrics

The PHP creator form calls `POST /api/social-profile` with its existing CSRF token.
PHP validates and normalizes the profile and applies per-IP and global rate limits.
**Instagram now reads the public Reels page directly in PHP**, without a Meta API
token, login, Python service, or paid provider. Other platforms keep their existing
private Python WSGI service. The browser never receives a service secret.

## Instagram fix: 6 November 2026

`app/instagram_public.php` reads structured JSON embedded in
`https://www.instagram.com/USERNAME/reels/`. The regular profile feed and individual
post endpoints previously used by the Python adapter did not return these counts.
The Reels grid does return play/like/comment counts for the profile tested.

A live PHP check of `chennaiipl` returned 23,267,479 followers, 6,128,120 average
plays, 322,333 average likes and 2,847 average comments across 8 eligible Reels.
These are a dated snapshot, not fixed expected values. Up to 12 returned unpinned
Reels are sampled; pins, duplicate items, unrelated owners, hidden counts and null
counts are excluded. Zero remains a valid count. This is not a guarantee of the
latest 12 chronological Reels, nor of consistent access from every hosting IP.

Deploy the complete application changes, including the new `app/instagram_public.php`,
`app/social_metrics.php`, `index.php`, the join/social/admin controllers, join/admin
templates, `assets/js/social-metrics.js`, and `assets/css/join.css`. Instagram
no longer uses the Meta Business Discovery branch or the Python service. Existing
YouTube routing is unchanged. The Instagram cache namespace was changed so old
“unconfigured” results do not mask the new adapter.

Average likes appear beside the fetched stats and in the saved admin lookup
metadata. Instagram impressions remain unavailable. Login walls, rate limits and
HTML/schema changes produce a manual-entry fallback rather than invented counts.
Validate from the cPanel hosting IP after deployment:

```sh
php tests/instagram_public_test.php
php tests/live_instagram_public.php chennaiipl
```

## Implemented scope

| Platform | Adapter | Followers/subscribers | Average views |
|---|---|---|---|
| Instagram | Direct PHP public Reels page | When profile access succeeds | Up to 12 returned unpinned Reels with public play counts; likes/comments also calculated |
| YouTube | yt-dlp | Public channel subscriber count, which can be rounded | Up to 12 entries from the Videos tab; excludes Shorts/live streams |
| TikTok | yt-dlp | Only if the extractor returns `channel_follower_count` | Up to 12 returned profile videos with view counts |
| Facebook, X, Snapchat, LinkedIn, Threads | Manual entry | Manual | Manual |

This is **not** a promise that every platform returns every metric. Instagram
can restrict anonymous access, and all three platforms can block data
center IPs, challenge logins or change their endpoints. Missing data stays null;
real zero values count. Lookup failure never prevents joining. Private Instagram
media is not collected even when the service account follows that profile.

Instagram averages use the Reels grid's `play_count`, not impressions. YouTube tab view counts
can be rounded public display values, so its average is approximate.

Each average divides the sum of the available view counts by the number of
videos with a count, up to 12. It is a snapshot of lifetime views on that sample,
not monthly views. Pinned/feed ordering and missing posts can affect the sample.
The form displays the sample count and scope. Fetched followers and average views
are read-only; missing metrics remain editable, including when a lookup fails.
On submission PHP restores matching fetched values from the visitor's session,
so browser field tampering cannot change those numbers. Results expire after 24 hours.
A successful response replaces provisional manual entries even if typed during lookup.

Admin Dashboard and individual creator profiles show an **impressions planning estimate**
for Instagram and YouTube only. Formula: `average views × platform multiplier`.
The multiplier defaults to **1.0**, a views-based proxy, not an empirically validated
impressions conversion. Admins may change each assumption (0.1–100) on the dashboard.
Actual impressions cannot be inferred reliably from public views. Do not use this proxy
as measured reach, a monthly total, guaranteed campaign delivery, or YouTube thumbnail impressions.
YouTube distinguishes views and thumbnail impressions in its
[metrics documentation](https://support.google.com/youtube/answer/9314486).

The separate percentage is `average views ÷ followers × 100`, as requested.
It can exceed 100% and is unavailable when followers are zero or missing.
Missing views yield no estimate, while a real zero yields zero. Dashboard totals sum
one average video per eligible profile; the dashboard percentage divides summed views
by summed followers from matched profiles with positive followers, not the mean of percentages.
Manual inputs are included and identified in profile provenance; demo profiles are excluded.

Calculations use saved metrics and the current assumptions at admin page load, so existing
profiles work without migration and changing a multiplier updates their estimates immediately.
No estimated values are returned by the public lookup API, form, or creator card. The old
monthly impressions input is removed from registration; previously stored monthly values
are preserved. Demographics and engagement remain creator-supplied insights.
The legacy `avg_impressions` database field remains for compatibility and stores
the sum of channel averages; it must not be interpreted as actual impressions or
a weighted average across platforms. Some older manually entered channel fields
represent reach/impressions, so the aggregate is labelled “Combined channel averages”.

## cPanel deployment for peakilo.com

Your cPanel lists **Setup Python App**, **SSH Access**, and **Cron Jobs**.
The steps below apply to the Python service for YouTube/TikTok and the legacy
Instagram Python adapter. The current PHP Instagram lookup needs only PHP cURL
and HTTPS access to Instagram; it does not require these Python setup steps.

1. Upload the PHP changes to the existing website. The next installed-site request
   runs migration 10, adding the nullable `influencers.social_metrics` TEXT field.
   Existing profiles are retained. `storage/` must remain writable by PHP.
2. Upload the contents of `services/social-metrics/` to
   **`/home1/peakilo/social-metrics`**, outside `public_html`. Do not upload `.venv`,
   `__pycache__` or local test files. Apache rules also block `/services/` in case
   the project is uploaded as a whole.
3. Open **Setup Python App → Create Application**. Select **Python 3.10 or newer**
   (prefer 3.12+ when available). Application root: `social-metrics`.
   Choose an HTTPS app URL, for example `https://peakilo.com/social-service`.
   Startup file: `passenger_wsgi.py`. Entry point: `application`.
4. Use cPanel’s displayed virtualenv activation command in SSH, then run:

   ```sh
   cd /home1/peakilo/social-metrics
   python -m pip install -r requirements.txt
   python -c "import secrets; print(secrets.token_hex(32))"
   ```

5. Save the generated secret privately. Add `SOCIAL_METRICS_TOKEN` as a Python app
   environment variable. Use the same secret in the existing PHP `config.local.php`
   return array (merge this block; do not replace your database configuration):

   ```php
   'social_metrics' => [
       'service_url' => 'https://peakilo.com/social-service',
       'service_token' => 'YOUR_GENERATED_SECRET',
   ],
   ```

   The exact URL must serve Passenger directly without an HTTP redirect. PHP
   refuses redirects and verifies TLS. A missing secret/URL gives a manual-entry
   message instead of pretending that the lookup worked.
6. **Instagram login:** create a session interactively in SSH using
   `python -m instaloader --login YOUR_SERVICE_USERNAME`, completing any account
   challenge in Instagram. If the server login is blocked, generate the session on
   a trusted computer and transfer it privately. Configure the Python app variables:

   ```text
   INSTAGRAM_LOGIN=YOUR_SERVICE_USERNAME
   INSTAGRAM_SESSION_FILE=/home1/peakilo/private/session-YOUR_SERVICE_USERNAME
   ```

   Use the actual session file path reported by Instaloader; move it into your
   private directory if needed. Restrict its permissions (`chmod 600`). Never
   place it in `public_html` or Git, and never ask creators to paste passwords into
   the registration form. Anonymous lookup is attempted if no session is configured.
7. Restart the Python app. Confirm PHP’s cURL extension is enabled in
   **Select PHP Version**. Open `/join`, select Instagram, and paste a known public
   handle. Repeat for YouTube. TikTok appears when enabled under Admin → Settings →
   Platforms. Test from this hosting IP; local success does not prove hosting access.

The Python app uses one short-lived child process per lookup with a 23-second
deadline. PHP waits up to 28 seconds. If the host disallows subprocesses or kills
requests sooner, the form falls back to manual entry. Confirm this with the host
before treating live lookup as operational. No background daemon or cron is needed.

Optional `YOUTUBE_COOKIE_FILE` and `TIKTOK_COOKIE_FILE` variables accept private,
administrator-managed Netscape cookie files if upstream access requires a session.
The service copies these to a temporary file so concurrent lookups cannot overwrite
the original. Browser execution is not used and protected video playback is not
required; only profile/feed metadata is requested.

## Storage, freshness and provenance

Successful results are cached in `storage/social/` for one hour; negative results
for one minute. Rate limits are 30 lookups/hour/IP and 300/hour/site. The JSON rate
map is file-locked across PHP workers. Old cache entries are pruned on cache writes.
Counts are fetched when a handle is entered or “Fetch stats” is clicked, not on
every page view or on a recurring schedule. Editing a handle clears the old counts;
late responses cannot overwrite a different handle. A successful lookup replaces manual
entries for the matching profile and locks only the metrics returned. Failed retries
clear the previous server-side lookup for that platform, enabling manual fallback.

Registration stores server-side lookup metadata with the creator: source, time,
sample size and original values, plus separate fetched/manual flags for followers
and average views. Admin profile details display that provenance. Posted metadata
is not trusted. A fetched public profile is **not proof that the submitter owns it**.

After configuration changes, a previously unconfigured result can remain cached
for up to 60 seconds. Refresh the page after that interval. Do not expose the cache,
session files, cookie files, environment variables or service token to browsers.

## Verification and maintenance

```sh
php tests/social_metrics_test.php
php tests/instagram_public_test.php
python tests/run_social_browser.py
python -m unittest discover -s services/social-metrics -p "test_*.py" -v
node --check assets/js/social-metrics.js
```

Tests use fixtures for deterministic behavior and never need real account secrets.
Local verification on 6 November 2026 passed 69 PHP metrics assertions, 14 Instagram
parser assertions, and Chrome checks for read-only fetched metrics, partial/failed
manual fallback, changed handles, cancelled requests, forged submitted counts,
validation re-render, saved provenance, and admin formulas, assumption validation,
authentication and CSRF. Earlier Python adapter verification passed 10 tests.
Earlier public live checks returned YouTube subscribers and an average from four available
view counts among 12 videos, Instagram followers without video counts, and a TikTok
timeout. The subsequent direct-PHP Instagram success is recorded above. Instagram
media access and TikTok remain unverified from the cPanel hosting environment.

The adapter versions are pinned in `requirements.txt`; periodically update them in
a test environment and repeat known-profile checks from the hosting IP.

References: [Instaloader structures](https://instaloader.github.io/module/structures.html),
[Instaloader login/session guidance](https://instaloader.github.io/basic-usage.html#instagram-login),
[yt-dlp embedding and metadata](https://github.com/yt-dlp/yt-dlp#embedding-yt-dlp).
