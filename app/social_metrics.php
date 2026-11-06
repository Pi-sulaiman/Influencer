<?php
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
    return hash('sha256', json_encode($identity) . '|' . social_service_settings()['url']);
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
            : 'Filled in from your public profile. Edit them if needed.');
    return $out;
}

function social_lookup(array $identity)
{
    $key = social_cache_key($identity);
    $path = social_cache_dir() . '/' . $key . '.json';
    $cached = is_file($path) ? json_decode(file_get_contents($path), true) : null;
    if (is_array($cached) && ($cached['expires'] ?? 0) > time()) return $cached['result'] + ['cached' => true];
    $result = social_service_request($identity);
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
