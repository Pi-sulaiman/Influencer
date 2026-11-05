# Automatic social profile metrics

The PHP creator form calls `POST /api/social-profile` with its existing CSRF token.
PHP validates and normalizes the profile, applies per-IP and global rate limits,
and calls a private Python WSGI service. The browser never receives the service
secret or Instagram session. No paid data provider is required.

## Implemented scope

| Platform | Adapter | Followers/subscribers | Average views |
|---|---|---|---|
| Instagram | Instaloader | When profile access succeeds | Latest 12 videos among up to 36 returned feed posts, only available view counts |
| YouTube | yt-dlp | Public channel subscriber count, which can be rounded | Up to 12 entries from the Videos tab; excludes Shorts/live streams |
| TikTok | yt-dlp | Only if the extractor returns `channel_follower_count` | Up to 12 returned profile videos with view counts |
| Facebook, X, Snapchat, LinkedIn, Threads | Manual entry | Manual | Manual |

This is **not** a promise that every platform returns every metric. Instagram
frequently requires a logged-in session, and all three platforms can block data
center IPs, challenge logins or change their endpoints. Missing data stays null;
real zero values count. Lookup failure never prevents joining. Private Instagram
media is not collected even when the service account follows that profile.

For Instagram, play counts are used only if no view counts were returned; the
sample description identifies which counter was used. YouTube tab view counts
can be rounded public display values, so its average is approximate.

Each average divides the sum of the available view counts by the number of
videos with a count, up to 12. It is a snapshot of lifetime views on that sample,
not monthly views. Pinned/feed ordering and missing posts can affect the sample.
The UI displays the sample count and scope. No estimated impressions are generated.
Monthly impressions, demographics and engagement remain creator-supplied insights.
The legacy `avg_impressions` database field remains for compatibility and stores
the sum of channel averages; it must not be interpreted as actual impressions or
a weighted average across platforms. Some older manually entered channel fields
represent reach/impressions, so the aggregate is labelled “Combined channel averages”.

## cPanel deployment for peakilo.com

Your cPanel lists **Setup Python App**, **SSH Access**, and **Cron Jobs**. Only the
Python app is needed; Node.js and Chromium are not required for these adapters.

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
late responses cannot overwrite a different handle or edits made during a lookup.

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
python -m unittest discover -s services/social-metrics -p "test_*.py" -v
node --check assets/js/social-metrics.js
```

Tests use fixtures for deterministic behavior and never need real account secrets.
Local verification on 5 November 2026 passed 59 PHP assertions, 10 Python tests,
and a Chrome test covering automatic fill, edits during lookup, cancelled requests,
CSRF rejection, PHP-to-service transport, caching and saved registration provenance.
Public live checks returned YouTube subscribers and an average from four available
view counts among 12 videos, Instagram followers without video counts, and a TikTok
timeout. Instagram media access and TikTok therefore remain unverified from the
hosting environment. Do not describe those metrics as operational until tested there.

The adapter versions are pinned in `requirements.txt`; periodically update them in
a test environment and repeat known-profile checks from the hosting IP.

References: [Instaloader structures](https://instaloader.github.io/module/structures.html),
[Instaloader login/session guidance](https://instaloader.github.io/basic-usage.html#instagram-login),
[yt-dlp embedding and metadata](https://github.com/yt-dlp/yt-dlp#embedding-yt-dlp).
