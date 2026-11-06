<?php
require_once __DIR__ . '/instagram_public.php';
/** Social lookups: fixed upstream, bounded requests, cached public metrics. */
function social_profile_identity($platform, $input)
{
    if (!isset(platform_options()[$platform]) || !is_string($input) || strlen($input) > 300) {
        throw new InvalidArgumentException('Choose a supported platform and enter a profile link or handle.');
    }
    $value = trim($input);
    $kind = 'handle';
    $isUrl = strpos($value, '/') !== false || preg_match('/^[a-z][a-z0-9+.-]*:/i', $value);
    foreach (platform_options()[$platform]['domains'] as $domain) {
        if (preg_match('#^(?:(?:www|m|mobile)\.)?' . preg_quote($domain, '#') . '(?:/|$)#i', $value)) $isUrl = true;
    }
    if ($isUrl) {
        $url = parse_url(preg_match('#^https?://#i', $value) ? $value : 'https://' . $value);
        $host = strtolower(preg_replace('/^(www|m|mobile)\./i', '', $url['host'] ?? ''));
        if (!$url || !in_array($url['scheme'] ?? '', ['https', 'http'], true) || isset($url['user']) || isset($url['pass']) || isset($url['port'])
            || !in_array($host, platform_options()[$platform]['domains'], true)) {
            throw new InvalidArgumentException('Use a profile link on the selected platform.');
        }
        $parts = explode('/', trim(rawurldecode($url['path'] ?? ''), '/'));
        if ($platform === 'youtube' && count($parts) === 2 && in_array($parts[0], ['channel', 'user', 'c'], true)) {
            $kind = $parts[0]; $value = $parts[1];
        } elseif ($platform === 'linkedin' && count($parts) === 2 && in_array($parts[0], ['in', 'company'], true)) {
            $kind = $parts[0]; $value = $parts[1];
        } elseif ($platform === 'snapchat' && count($parts) === 2 && in_array($parts[0], ['add', 'p'], true)) {
            $kind = $parts[0]; $value = $parts[1];
        } elseif (count($parts) === 1 && !in_array($host, ['youtu.be', 'lnkd.in', 'fb.me'], true)) {
            $value = $parts[0];
        } else {
            throw new InvalidArgumentException('Use a profile URL, not a post, video or shortened link.');
        }
    }
    $value = ltrim($value, '@');
    $pattern = $platform === 'youtube' ? '/^[\p{L}\p{N}_.-]{1,100}$/u' : '/^[a-zA-Z0-9_.-]{1,100}$/';
    if ($value === '' || !preg_match($pattern, $value) || in_array(strtolower($value), ['p', 'reel', 'reels', 'stories', 'explore', 'accounts', 'watch', 'shorts', 'share', 'login', 'profile.php'], true)
        || ($platform === 'instagram' && (!preg_match('/^[a-zA-Z0-9_.]{1,30}$/', $value)))
        || ($platform === 'youtube' && $kind === 'channel' && !preg_match('/^UC[a-zA-Z0-9_-]{22}$/', $value))) {
        throw new InvalidArgumentException('Enter a valid profile handle or channel URL.');
    }
    // Channel IDs and legacy YouTube identifiers can be case-sensitive.
    if ($platform !== 'youtube') $value = strtolower($value);
    return ['platform' => $platform, 'handle' => $value, 'kind' => $kind];
}

function social_cache_dir()
{
    $dir = ROOT . '/storage/social';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Cache unavailable');
    return $dir;
}

function social_cache_key(array $identity)
{
    $provider = $identity['platform'] === 'instagram' ? 'instagram-public-reels-v1' : social_service_settings()['url'];
    return hash('sha256', json_encode($identity) . '|' . $provider);
}

/** One locked, bounded rate-limit map shared by all PHP workers, not just a browser session. */
function social_rate_limit($visitor)
{
    $file = fopen(social_cache_dir() . '/limits.json', 'c+');
    if (!$file || !flock($file, LOCK_EX)) throw new RuntimeException('Rate limiter unavailable');
    try {
        $state = json_decode(stream_get_contents($file), true) ?: [];
        $time = time();
        foreach ($state as $key => $entry) if (($entry['until'] ?? 0) <= $time) unset($state[$key]);
        $keys = ['ip:' . $visitor => 30, 'global' => 300];
        foreach ($keys as $key => $limit) {
            if (($state[$key]['count'] ?? 0) >= $limit) return false;
        }
        foreach ($keys as $key => $limit) {
            $state[$key] = ['count' => ($state[$key]['count'] ?? 0) + 1, 'until' => $state[$key]['until'] ?? $time + 3600];
        }
        rewind($file); ftruncate($file, 0); fwrite($file, json_encode($state)); fflush($file);
        return true;
    } finally { flock($file, LOCK_UN); fclose($file); }
}

function social_number($number)
{
    return is_numeric($number) && !is_bool($number) && is_finite((float) $number) && $number >= 0 && $number <= 2000000000 ? (int) round((float) $number) : null;
}

/**
 * Lookup service address and secret. config.php / config.local.php win; on the
 * cPanel server the secret can instead live in a private file one level above
 * the website (/home1/peakilo/private/social-metrics.token), created by
 * services/social-metrics/make_secret.py. Then the service URL defaults to
 * this site's /social-service/ path. Without that file nothing is configured.
 */
function social_service_settings()
{
    $url = (string) cfg('social_metrics.service_url', '');
    $token = (string) cfg('social_metrics.service_token', '');
    if ($token === '') {
        $file = dirname(ROOT) . '/private/social-metrics.token';
        if (is_readable($file)) {
            $token = trim((string) file_get_contents($file));
            if ($url === '' && strlen($token) >= 32) $url = abs_url('social-service/');
        }
    }
    return ['url' => $url, 'token' => $token];
}

function social_service_request(array $identity)
{
    $settings = social_service_settings();
    $url = $settings['url'];
    $token = $settings['token'];
    if ($url === '' || strlen($token) < 32) {
        return ['status' => 'unconfigured', 'message' => 'Automatic lookup is not connected yet. You can enter your stats below.'];
    }
    $parts = parse_url($url);
    $local = in_array($parts['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true);
    if (!$parts || isset($parts['user']) || isset($parts['pass']) || (($parts['scheme'] ?? '') !== 'https' && !(($parts['scheme'] ?? '') === 'http' && $local))) {
        throw new RuntimeException('Invalid metrics service configuration');
    }
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL is required');
    // The URL is administrator configuration. No URL supplied by a visitor is fetched.
    $curl = curl_init($url);
    $body = '';
    curl_setopt_array($curl, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($identity),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 28,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body) {
            if (strlen($body) + strlen($chunk) > 65536) return 0;
            $body .= $chunk; return strlen($chunk);
        },
    ]);
    $ok = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($ok === false || $status !== 200) throw new RuntimeException('Metrics service unavailable');
    $result = json_decode($body, true);
    if (!is_array($result) || !in_array($result['status'] ?? '', ['ok', 'partial', 'unavailable', 'unsupported'], true)) throw new RuntimeException('Invalid metrics response');
    // Return only the documented contract, never upstream errors or session cookies.
    $out = [
        'status' => $result['status'], 'followers' => social_number($result['followers'] ?? null),
        'average_views' => social_number($result['average_views'] ?? null),
        'sample_size' => min(12, max(0, (int) ($result['sample_size'] ?? 0))),
        'posts_scanned' => min(36, max(0, (int) ($result['posts_scanned'] ?? 0))),
        'source' => mb_substr((string) ($result['source'] ?? ''), 0, 100),
        'sample_description' => mb_substr((string) ($result['sample_description'] ?? ''), 0, 250),
        'fetched_at' => gmdate('c'), 'impressions' => null,
    ];
    if ($out['sample_size'] === 0) $out['average_views'] = null;
    if (in_array($out['status'], ['unavailable', 'unsupported'], true)) {
        $out['followers'] = $out['average_views'] = null;
        $out['sample_size'] = 0;
    }
    $out['message'] = $out['status'] === 'unsupported'
        ? 'Automatic lookup is not available for this platform yet. Enter your stats below.'
        : ($out['followers'] === null && $out['average_views'] === null
            ? 'We could not get stats from this profile right now. Add them manually, or try again later.'
            : 'Fetched public stats are locked. You can enter any unavailable numbers manually.');
    return $out;
}

function social_lookup(array $identity)
{
    $key = social_cache_key($identity);
    $path = social_cache_dir() . '/' . $key . '.json';
    $cached = is_file($path) ? json_decode(file_get_contents($path), true) : null;
    if (is_array($cached) && ($cached['expires'] ?? 0) > time()) return $cached['result'] + ['cached' => true];
    // Instagram: Meta's official Business Discovery API when it is connected
    // (Admin > Settings > Instagram), otherwise its public Reels page.
    // Other platforms use the lookup service.
    if ($identity['platform'] === 'instagram') {
        $result = meta_business_discovery($identity);
        if ($result === null) $result = instagram_public_lookup($identity);
    } else {
        $result = social_service_request($identity);
    }
    $result['identity'] = $identity;
    // Negative cache dampens repeated requests when a platform blocks the server.
    $ttl = in_array($result['status'], ['ok', 'partial'], true) ? 3600 : 60;
    file_put_contents($path, json_encode(['expires' => time() + $ttl, 'result' => $result]), LOCK_EX);
    // Only stale entries are pruned; the global limit bounds creation to 300/hour.
    foreach (glob(social_cache_dir() . '/*.json') as $file) {
        if (basename($file) !== 'limits.json' && filemtime($file) < time() - 86400) unlink($file);
    }
    return $result + ['cached' => false];
}

/** Only the matching, recent server-side result can lock submitted metrics. */
function social_trusted_lookup($platform, $profile)
{
    if (!$profile) return null;
    try { $identity = social_profile_identity($platform, $profile); }
    catch (InvalidArgumentException $e) { return null; }
    $result = $_SESSION['social_lookups'][$platform] ?? null;
    return is_array($result) && ($result['identity'] ?? null) === $identity
        && strtotime($result['fetched_at'] ?? '') > time() - 86400 ? $result : null;
}

function social_enforce_fetched_values(array $data, array $platforms)
{
    foreach ($platforms as $platform => $unused) {
        $result = social_trusted_lookup($platform, $data[$platform] ?? '');
        foreach (['followers' => '_followers', 'average_views' => '_avg_views'] as $metric => $suffix) {
            $value = social_number($result[$metric] ?? null);
            if ($value !== null) $data[$platform . $suffix] = (string) $value;
        }
    }
    return $data;
}

/** Admin-only planning assumptions, never sent in the public lookup response. */
function social_impression_multiplier($platform)
{
    $value = setting('impression_multiplier_' . $platform, '1');
    return is_numeric($value) && $value >= 0.1 && $value <= 100 ? (float) $value : 1.0;
}

function social_admin_projection($views, $followers, $multiplier)
{
    $views = is_numeric($views) && is_finite((float) $views) && $views >= 0 ? (float) $views : null;
    $followers = is_numeric($followers) && is_finite((float) $followers) && $followers > 0 ? (float) $followers : null;
    return [
        'estimated_impressions' => $views === null ? null : round($views * $multiplier),
        // Can exceed 100%; views include repeat plays and non-followers.
        'views_followers_percent' => $views === null || $followers === null ? null : round($views / $followers * 100, 2),
    ];
}

/** Metadata comes from this visitor's server-side lookup, never posted JSON. */
function social_submission_metadata(array $data, array $platforms, array $previous = [])
{
    $metadata = array_diff_key($previous, $platforms);
    foreach ($platforms as $platform => $unused) {
        if (empty($data[$platform])) continue;
        $result = $_SESSION['social_lookups'][$platform] ?? null;
        try { $identity = social_profile_identity($platform, $data[$platform]); }
        catch (InvalidArgumentException $e) { continue; }
        $entry = ['identity' => $identity, 'followers_source' => 'manual', 'views_source' => 'manual'];
        if ($result && ($result['identity'] ?? null) === $identity && strtotime($result['fetched_at'] ?? '') > time() - 86400) {
            foreach (['followers' => '_followers', 'average_views' => '_avg_views'] as $metric => $suffix) {
                $sourceKey = $metric === 'followers' ? 'followers_source' : 'views_source';
                if ($result[$metric] !== null && parse_count($data[$platform . $suffix]) === $result[$metric]) $entry[$sourceKey] = 'fetched';
            }
            $entry['lookup'] = $result;
        }
        $metadata[$platform] = $entry;
    }
    return $metadata;
}

/* ---------------- Instagram via Meta's Business Discovery API ----------------
 * Works for Instagram business and creator accounts. Needs Peakilo's own
 * Instagram professional account ID and an access token (Admin > Settings >
 * Instagram). Returns null when not connected or when Meta rejects the token,
 * so the caller can fall back to the lookup service.
 */
function meta_business_discovery(array $identity)
{
    $igUser = preg_replace('/\D/', '', (string) setting('meta_ig_user_id'));
    $token = trim((string) setting('meta_access_token'));
    if ($igUser === '' || $token === '' || ($identity['kind'] ?? 'handle') !== 'handle') return null;
    $handle = (string) ($identity['handle'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.]{1,30}$/', $handle)) return null;
    if (!function_exists('curl_init')) return null;

    $fields = 'business_discovery.username(' . $handle . '){followers_count,media_count,'
        . 'media.limit(30){media_type,media_product_type,like_count,comments_count,view_count,timestamp}}';
    $url = 'https://graph.facebook.com/v25.0/' . $igUser . '?' . http_build_query(['fields' => $fields, 'access_token' => $token]);
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $data = is_string($body) ? json_decode($body, true) : null;
    return meta_discovery_result(is_array($data) ? $data : [], $status);
}

/** Turns a Graph API response into the lookup result (kept separate so it can be tested). */
function meta_discovery_result(array $data, $status)
{
    $base = ['followers' => null, 'average_views' => null, 'average_likes' => null, 'average_comments' => null,
        'engagement_rate' => null, 'sample_size' => 0, 'likes_sample_size' => 0, 'comments_sample_size' => 0, 'posts_scanned' => 0,
        'view_metric' => 'views', 'source' => 'Instagram Graph API (Business Discovery)', 'sample_description' => '',
        'fetched_at' => gmdate('c'), 'impressions' => null];
    if (isset($data['error'])) {
        $code = (int) ($data['error']['code'] ?? 0);
        // 190 = expired or invalid token, 4/17/32/613 = rate limits: let the lookup service try.
        if (in_array($code, [190, 4, 17, 32, 613], true) || $status >= 500) {
            error_log('[peakilo] Instagram Business Discovery unavailable (code ' . $code . ')');
            return null;
        }
        // Usually a personal account or a username that does not exist.
        return $base + ['status' => 'unavailable',
            'message' => 'Instagram only shares stats for business and creator accounts. Add yours manually.'];
    }
    $bd = $data['business_discovery'] ?? null;
    if (!is_array($bd)) return null;
    $out = $base;
    $out['followers'] = social_number($bd['followers_count'] ?? null);
    $media = array_slice(array_values(array_filter((array) ($bd['media']['data'] ?? []), 'is_array')), 0, 30);
    $out['posts_scanned'] = count($media);
    // Average Reel views from the latest 12 videos that have a view count.
    $views = [];
    foreach ($media as $m) {
        $isVideo = ($m['media_product_type'] ?? '') === 'REELS' || ($m['media_type'] ?? '') === 'VIDEO';
        if ($isVideo && isset($m['view_count']) && is_numeric($m['view_count'])) $views[] = (int) $m['view_count'];
        if (count($views) >= 12) break;
    }
    if ($views) {
        $out['average_views'] = (int) round(array_sum($views) / count($views));
        $out['sample_size'] = count($views);
    }
    // Engagement: average likes + comments on the latest 12 posts, against followers.
    $recent = array_slice($media, 0, 12);
    $likes = []; $comments = [];
    foreach ($recent as $m) {
        if (isset($m['like_count']) && is_numeric($m['like_count'])) $likes[] = (int) $m['like_count'];
        if (isset($m['comments_count']) && is_numeric($m['comments_count'])) $comments[] = (int) $m['comments_count'];
    }
    if ($likes) { $out['average_likes'] = (int) round(array_sum($likes) / count($likes)); $out['likes_sample_size'] = count($likes); }
    if ($comments) { $out['average_comments'] = (int) round(array_sum($comments) / count($comments)); $out['comments_sample_size'] = count($comments); }
    if ($recent && $out['followers']) {
        $eng = array_sum($likes) + array_sum($comments);
        $out['engagement_rate'] = round($eng / count($recent) / $out['followers'] * 100, 2);
    }
    $out['sample_description'] = $views
        ? 'Average of Instagram\'s public view count on the latest ' . count($views) . ' Reels (paid and organic). Engagement from the latest ' . count($recent) . ' posts.'
        : 'No recent Reels with a public view count.';
    $out['status'] = $out['followers'] !== null && $out['average_views'] !== null ? 'ok' : ($out['followers'] !== null ? 'partial' : 'unavailable');
    $out['message'] = $out['status'] === 'unavailable'
        ? 'We could not get stats from this profile right now. Add them manually, or try again later.'
        : 'Filled in from Instagram. Edit them if needed.';
    return $out;
}
