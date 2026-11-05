<?php
/**
 * General helpers: config, URLs, escaping, CSRF, flash, formatting.
 */

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) { return $needle === '' || strpos($haystack, $needle) !== false; }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) { return strncmp($haystack, $needle, strlen($needle)) === 0; }
}

function cfg($key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------------- URLs ---------------- */

function is_https()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

/** Folder the site lives in, e.g. '' at the domain root or '/peakilo' in a subfolder. */
function base_path()
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($base === '.') {
            $base = '';
        }
    }
    return $base;
}

function request_path()
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $uri = rawurldecode($uri);
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = '/' . trim($uri, '/');
    if ($uri === '/index.php') {
        $uri = '/';
    }
    return $uri;
}

function url($path = '')
{
    $path = ltrim((string) $path, '/');
    return base_path() . '/' . $path;
}

function site_origin()
{
    $configured = rtrim((string) cfg('site_url', ''), '/');
    if ($configured !== '') {
        // Allow the configured host to include a sub folder.
        $parts = parse_url($configured);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
    return (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** Absolute URL, used for canonical tags, sitemap, OG images and campaign links. */
function abs_url($path = '')
{
    $configured = rtrim((string) cfg('site_url', ''), '/');
    if ($configured !== '') {
        return $configured . '/' . ltrim((string) $path, '/');
    }
    return site_origin() . url($path);
}

function asset($path)
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/** Public URL for an uploaded file path stored in the DB (e.g. uploads/creators/x.jpg). */
function upload_url($path)
{
    if (!$path) {
        return '';
    }
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    return url($path);
}

function redirect($path, $code = 302)
{
    if (!preg_match('#^https?://#', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path, true, $code);
    exit;
}

function back($fallback = '')
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function current_url_is($path)
{
    $p = request_path();
    $path = '/' . trim($path, '/');
    return $p === $path || ($path !== '/' && str_starts_with($p, $path . '/'));
}

/* ---------------- Requests ---------------- */

function is_post()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input($key, $default = '')
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function query($key, $default = '')
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function input_array($key)
{
    $v = $_POST[$key] ?? [];
    if (!is_array($v)) {
        return [];
    }
    return array_values(array_filter(array_map(function ($x) { return is_string($x) ? trim($x) : ''; }, $v), 'strlen'));
}

function to_int_or_null($v)
{
    $v = preg_replace('/[^\d]/', '', (string) $v);
    return $v === '' ? null : (int) $v;
}

function to_money_or_null($v)
{
    $v = preg_replace('/[^\d.]/', '', (string) $v);
    return $v === '' ? null : round((float) $v, 2);
}

function client_ip()
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** We never store raw IPs, only a salted hash for spam/rate limiting. */
function ip_hash()
{
    return hash('sha256', client_ip() . '|' . cfg('db.name', 'peakilo') . '|peakilo');
}

/* ---------------- Responses ---------------- */

function render($__view, array $__vars = [], array $__meta = [], $__layout = 'site')
{
    $__file = APP . '/views/' . $__view . '.php';
    if (!is_file($__file)) {
        throw new RuntimeException("View not found: $__view");
    }
    // Internal names are prefixed so view variables like $data never collide.
    extract($__vars, EXTR_SKIP);
    ob_start();
    include $__file;
    $content = ob_get_clean();
    if ($__layout === null) {
        echo $content;
        return;
    }
    $meta = seo_meta($__meta);
    include APP . '/views/partials/layout_' . $__layout . '.php';
}

function partial($name, array $data = [])
{
    extract($data, EXTR_SKIP);
    include APP . '/views/partials/' . $name . '.php';
}

function not_found()
{
    http_response_code(404);
    render('site/error', [
        'code'    => 404,
        'title'   => 'This page took a detour',
        'message' => 'The page you were looking for has moved or never existed. Try one of these instead.',
    ], ['title' => 'Page not found', 'robots' => 'noindex']);
    exit;
}

function json_out($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- CSRF + flash ---------------- */

function csrf_token()
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $sent = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_out(['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.'], 419);
        }
        render('site/error', [
            'code'    => 419,
            'title'   => 'Your session expired',
            'message' => 'For your security the form timed out. Go back, refresh the page and submit again.',
        ], ['title' => 'Session expired', 'robots' => 'noindex']);
        exit;
    }
}

function flash($type, $message)
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes()
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function with_old(array $data)
{
    $_SESSION['_old'] = $data;
}

function old($key, $default = '')
{
    static $old = null;
    if ($old === null) {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }
    return $old[$key] ?? $default;
}

/* ---------------- Settings ---------------- */

function settings_all($refresh = false)
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        try {
            foreach (all('SELECT skey, svalue FROM settings') as $row) {
                $cache[$row['skey']] = $row['svalue'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache;
}

function setting($key, $default = '')
{
    $all = settings_all();
    return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
}

function save_setting($key, $value)
{
    $exists = val('SELECT COUNT(*) FROM settings WHERE skey = ?', [$key]);
    if ($exists) {
        q('UPDATE settings SET svalue = ? WHERE skey = ?', [(string) $value, $key]);
    } else {
        q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, (string) $value]);
    }
}

/* ---------------- Formatting ---------------- */

function now()
{
    return date('Y-m-d H:i:s');
}

function slugify($text)
{
    $text = strtolower(trim((string) $text));
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? substr($text, 0, 90) : 'post-' . time();
}

function str_limit($text, $limit = 160)
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

/** 1234 -> 1.2K, 2400000 -> 2.4M */
function short_number($n)
{
    $n = (float) $n;
    if ($n >= 1000000000) return rtrim(rtrim(number_format($n / 1000000000, 1), '0'), '.') . 'B';
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    if ($n >= 1000) return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return (string) (int) $n;
}

/** Currency code => symbol, built from currency_catalog() in app/geo.php. */
function currencies()
{
    return array_map(function ($c) { return $c[1]; }, currency_catalog());
}

function money($amount, $currency = null)
{
    if ($amount === null || $amount === '') {
        return '';
    }
    $currency = $currency ?: setting('default_currency', 'USD');
    $symbols = currencies();
    $sym = $symbols[$currency] ?? ($currency . ' ');
    $amount = (float) $amount;
    $formatted = $amount >= 100000 ? short_number($amount) : number_format($amount, $amount == floor($amount) ? 0 : 2);
    return $sym . $formatted;
}

function fee_range($min, $max, $currency = null)
{
    if ($min === null && $max === null) {
        return 'Not shared';
    }
    if ($min !== null && $max !== null && (float) $min !== (float) $max) {
        return money($min, $currency) . ' - ' . money($max, $currency);
    }
    return money($min !== null ? $min : $max, $currency);
}

function nice_date($datetime, $format = 'M j, Y')
{
    if (!$datetime) {
        return '';
    }
    $t = strtotime($datetime);
    return $t ? date($format, $t) : '';
}

function time_ago($datetime)
{
    $t = strtotime((string) $datetime);
    if (!$t) {
        return '';
    }
    $diff = time() - $t;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $t);
}

function reading_time($html)
{
    $words = str_word_count(strip_tags((string) $html));
    return max(1, (int) ceil($words / 220));
}

function initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out ?: 'P';
}

function csv_list($value)
{
    if (is_array($value)) {
        return $value;
    }
    return array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));
}

/* ---------------- Domain lists ---------------- */

function niche_options()
{
    return ['Fashion', 'Beauty', 'Lifestyle', 'Travel', 'Food', 'Fitness', 'Health', 'Tech', 'Gaming', 'Finance', 'Education', 'Parenting', 'Comedy', 'Music', 'Art & Design', 'Sports', 'Automotive', 'Home & Decor', 'Pets', 'Business'];
}

function content_type_options()
{
    return ['Reels / Shorts', 'Stories', 'Feed posts', 'Long-form video', 'Live streams', 'UGC (no posting)', 'Podcasts', 'Blog / Articles', 'Event appearances'];
}

function platform_options()
{
    return [
        'instagram' => ['label' => 'Instagram', 'icon' => 'ph-instagram-logo', 'placeholder' => 'instagram.com/yourname', 'domains' => ['instagram.com', 'instagr.am'], 'views' => 'Avg. views per reel'],
        'youtube'   => ['label' => 'YouTube',   'icon' => 'ph-youtube-logo',   'placeholder' => 'youtube.com/@channel',   'domains' => ['youtube.com', 'youtu.be'], 'views' => 'Avg. views per video'],
        'tiktok'    => ['label' => 'TikTok',    'icon' => 'ph-tiktok-logo',    'placeholder' => 'tiktok.com/@yourname',   'domains' => ['tiktok.com'], 'views' => 'Avg. views per video'],
        'facebook'  => ['label' => 'Facebook',  'icon' => 'ph-facebook-logo',  'placeholder' => 'facebook.com/yourpage',  'domains' => ['facebook.com', 'fb.com', 'fb.me'], 'views' => 'Avg. reach per post'],
        'x'         => ['label' => 'X',         'icon' => 'ph-x-logo',         'placeholder' => 'x.com/yourname',         'domains' => ['x.com', 'twitter.com'], 'views' => 'Avg. impressions'],
        'snapchat'  => ['label' => 'Snapchat',  'icon' => 'ph-snapchat-logo',  'placeholder' => 'snapchat.com/add/yourname', 'domains' => ['snapchat.com'], 'views' => 'Avg. story views'],
        'linkedin'  => ['label' => 'LinkedIn',  'icon' => 'ph-linkedin-logo',  'placeholder' => 'linkedin.com/in/yourname', 'domains' => ['linkedin.com', 'lnkd.in'], 'views' => 'Avg. impressions per post'],
        'threads'   => ['label' => 'Threads',   'icon' => 'ph-threads-logo',   'placeholder' => 'threads.net/@yourname',  'domains' => ['threads.net', 'threads.com'], 'views' => 'Avg. views per post'],
    ];
}

/** Platforms switched on in Admin > Settings > Platforms (creator form, homepage, FAQ text). */
function default_platforms_csv()
{
    return 'instagram,youtube,facebook,snapchat,x';
}

function enabled_platforms()
{
    $all = platform_options();
    $keys = array_intersect(array_map('trim', explode(',', (string) setting('creator_platforms', default_platforms_csv()))), array_keys($all));
    if (!$keys) {
        $keys = ['instagram'];
    }
    return array_intersect_key($all, array_flip($keys));
}

function platform_enabled($key)
{
    return isset(enabled_platforms()[$key]);
}

/** "Instagram, YouTube and Facebook" from the switched-on platforms. */
function platform_list_text($limit = 0)
{
    $labels = array_values(array_map(function ($p) { return $p['label']; }, enabled_platforms()));
    if ($limit) $labels = array_slice($labels, 0, $limit);
    if (count($labels) < 2) return implode('', $labels);
    return implode(', ', array_slice($labels, 0, -1)) . ' and ' . end($labels);
}

/**
 * True when what the creator typed is a plain @handle, or a link on one of
 * the platform's own domains. Stops an Instagram link in the YouTube box.
 */
function profile_link_matches($platform, $value)
{
    $value = trim((string) $value);
    if ($value === '' || !preg_match('#[/.]#', ltrim($value, '@'))) {
        return true; // a bare handle
    }
    $host = parse_url(preg_match('#^https?://#i', $value) ? $value : 'https://' . $value, PHP_URL_HOST);
    $host = strtolower(preg_replace('/^(www\.|m\.|mobile\.)/', '', (string) $host));
    foreach (platform_options()[$platform]['domains'] ?? [] as $d) {
        if ($host === $d || str_ends_with_compat($host, '.' . $d)) {
            return true;
        }
    }
    return false;
}

function str_ends_with_compat($haystack, $needle)
{
    return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
}

/** Per-deliverable rates a creator can add to their rate card. */
function rate_card_options()
{
    return [
        'reel'  => ['Reel or short video', 'ph-film-strip'],
        'post'  => ['Feed post or carousel', 'ph-images'],
        'story' => ['Story set (3 frames)', 'ph-circles-three'],
        'video' => ['Long-form video', 'ph-youtube-logo'],
        'ugc'   => ['UGC video (no posting)', 'ph-video-camera'],
    ];
}

function audience_age_options()
{
    return ['13-17', '18-24', '25-34', '35-44', '45+'];
}

function audience_gender_options()
{
    return ['Mostly women', 'Balanced', 'Mostly men'];
}

/** Creator size band from total followers. */
function creator_tier($followers)
{
    $n = (int) $followers;
    if ($n >= 1000000) return ['key' => 'mega', 'label' => 'Mega', 'range' => '1M+'];
    if ($n >= 500000) return ['key' => 'macro', 'label' => 'Macro', 'range' => '500K-1M'];
    if ($n >= 100000) return ['key' => 'mid', 'label' => 'Mid-tier', 'range' => '100K-500K'];
    if ($n >= 10000) return ['key' => 'micro', 'label' => 'Micro', 'range' => '10K-100K'];
    return ['key' => 'nano', 'label' => 'Nano', 'range' => 'Under 10K'];
}

/** Public reference for a creator, e.g. PK1042. Safe to show to brands. */
function creator_code($id)
{
    return 'PK' . (1000 + (int) $id);
}

function creator_id_from_code($code)
{
    return preg_match('/^PK(\d{4,})$/i', trim((string) $code), $m) ? (int) $m[1] - 1000 : 0;
}

/** Decoded rate card: ['reel' => 5000.0, ...] with only known keys and positive numbers. */
function rate_card($json)
{
    $data = json_decode((string) $json, true);
    $out = [];
    foreach (array_keys(rate_card_options()) as $k) {
        if (isset($data[$k]) && is_numeric($data[$k]) && $data[$k] > 0) {
            $out[$k] = (float) $data[$k];
        }
    }
    return $out;
}

/** Build a clickable profile URL from whatever the creator typed. */
function social_url($platform, $value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }
    if (preg_match('#^(www\.)?[a-z0-9.-]+\.[a-z]{2,}/#i', $value)) {
        return 'https://' . $value;
    }
    $handle = ltrim($value, '@');
    switch ($platform) {
        case 'instagram': return 'https://instagram.com/' . rawurlencode($handle);
        case 'youtube':   return 'https://youtube.com/@' . rawurlencode($handle);
        case 'tiktok':    return 'https://www.tiktok.com/@' . rawurlencode($handle);
        case 'facebook':  return 'https://facebook.com/' . rawurlencode($handle);
        case 'x':         return 'https://x.com/' . rawurlencode($handle);
        case 'snapchat':  return 'https://www.snapchat.com/add/' . rawurlencode($handle);
        case 'linkedin':  return 'https://www.linkedin.com/in/' . rawurlencode($handle);
        case 'threads':   return 'https://www.threads.net/@' . rawurlencode($handle);
    }
    return '';
}

function status_label($status)
{
    $map = [
        'new' => 'New', 'approved' => 'Approved', 'on_hold' => 'On hold', 'rejected' => 'Rejected',
        'applied' => 'Applied', 'shortlisted' => 'Shortlisted', 'selected' => 'Selected',
        'active' => 'Active', 'paused' => 'Paused', 'closed' => 'Closed',
        'draft' => 'Draft', 'published' => 'Published',
    ];
    return $map[$status] ?? ucfirst((string) $status);
}

/* ---------------- HTML sanitising for blog posts ---------------- */

function sanitize_html($html)
{
    $html = (string) $html;
    if (trim($html) === '' || !class_exists('DOMDocument')) {
        return strip_tags($html, '<p><a><strong><b><em><i><u><h2><h3><h4><ul><ol><li><blockquote><img><figure><figcaption><br><hr><code><pre><table><thead><tbody><tr><th><td><span>');
    }
    $allowed = ['p', 'a', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'img', 'figure', 'figcaption', 'br', 'hr', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'div', 'iframe'];
    $allowedAttrs = ['href', 'src', 'alt', 'title', 'target', 'rel', 'width', 'height', 'loading', 'id', 'class', 'colspan', 'rowspan', 'allowfullscreen', 'frameborder'];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $nodes = iterator_to_array($xpath->query('//*'));
    foreach ($nodes as $node) {
        /** @var DOMElement $node */
        if ($node->getAttribute('id') === '__root' && $node->parentNode === $doc) {
            continue;
        }
        $tag = strtolower($node->nodeName);
        if (!in_array($tag, $allowed, true)) {
            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select'], true)) {
                $node->parentNode->removeChild($node);
            } else {
                while ($node->firstChild) {
                    $node->parentNode->insertBefore($node->firstChild, $node);
                }
                $node->parentNode->removeChild($node);
            }
            continue;
        }
        if ($tag === 'iframe') {
            $src = $node->getAttribute('src');
            if (!preg_match('#^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)/#', $src)) {
                $node->parentNode->removeChild($node);
                continue;
            }
        }
        foreach (iterator_to_array($node->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);
            if (!in_array($name, $allowedAttrs, true)) {
                $node->removeAttribute($attr->name);
                continue;
            }
            if (in_array($name, ['href', 'src'], true) && preg_match('#^\s*(javascript|data|vbscript):#i', $value)) {
                $node->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener');
        }
    }
    $root = $xpath->query('//div[@id="__root"]')->item(0);
    if (!$root) {
        return strip_tags($html);
    }
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

/** Adds ids to h2/h3 headings and returns [html, toc]. */
function build_toc($html)
{
    $toc = [];
    $used = [];
    $html = preg_replace_callback('#<(h2|h3)([^>]*)>(.*?)</\1>#is', function ($m) use (&$toc, &$used) {
        $text = trim(strip_tags($m[3]));
        if ($text === '') {
            return $m[0];
        }
        $id = slugify($text);
        $base = $id;
        $i = 2;
        while (isset($used[$id])) {
            $id = $base . '-' . $i++;
        }
        $used[$id] = true;
        $toc[] = ['level' => strtolower($m[1]), 'text' => $text, 'id' => $id];
        $attrs = preg_replace('/\sid="[^"]*"/i', '', $m[2]);
        return '<' . $m[1] . $attrs . ' id="' . e($id) . '">' . $m[3] . '</' . $m[1] . '>';
    }, (string) $html);
    return [$html, $toc];
}

/* ---------------- Mail ---------------- */

function send_mail($to, $subject, $body)
{
    if (!cfg('mail.enabled') || !$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $from = cfg('mail.from', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'peakilo.com'));
    $headers = [
        'From: Peakilo <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

/* ---------------- Pagination ---------------- */

function paginate($total, $perPage, $page)
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $page), $pages);
    return ['total' => (int) $total, 'per' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

/** Current query string with some keys replaced. */
function qs(array $replace = [])
{
    $q = array_merge($_GET, $replace);
    $q = array_filter($q, function ($v) { return $v !== '' && $v !== null; });
    return $q ? '?' . http_build_query($q) : '';
}

/* ---------------- Brand ---------------- */

/** The Peakilo chevron "P" mark as inline SVG. */
function mark_svg($class = 'logo-mark', $label = '')
{
    $aria = $label ? 'role="img" aria-label="' . e($label) . '"' : 'aria-hidden="true" focusable="false"';
    return '<svg class="' . e($class) . '" viewBox="0 0 100 90" ' . $aria . '><path d="M0 0H28L62 40L52 52H62L72 40L38 0H66L100 40L78 66H40L24 90H0L34 40Z"/></svg>';
}

function nav_items()
{
    return [
        'talent'   => 'Find talent',
        'services' => 'Services',
        'brands'   => 'For brands',
        'creators' => 'For creators',
        'blog'     => 'Blog',
        'about'    => 'About',
    ];
}

/** Parses follower counts like "45,000", "45k", "1.2M" into an integer. */
function parse_count($value)
{
    $v = strtolower(str_replace([',', ' '], '', trim((string) $value)));
    if ($v === '') {
        return null;
    }
    if (preg_match('/^([\d.]+)([km])?$/', $v, $m)) {
        $n = (float) $m[1];
        if (($m[2] ?? '') === 'k') $n *= 1000;
        if (($m[2] ?? '') === 'm') $n *= 1000000;
        return (int) round($n);
    }
    return to_int_or_null($v);
}

function country_options()
{
    return ['India', 'United States', 'United Kingdom', 'United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Canada', 'Australia', 'Singapore', 'Malaysia', 'Indonesia', 'Philippines', 'Thailand', 'Vietnam', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Nigeria', 'Kenya', 'South Africa', 'Egypt', 'Germany', 'France', 'Spain', 'Italy', 'Netherlands', 'Sweden', 'Ireland', 'Portugal', 'Poland', 'Turkey', 'Brazil', 'Mexico', 'Argentina', 'Colombia', 'Japan', 'South Korea', 'New Zealand'];
}
