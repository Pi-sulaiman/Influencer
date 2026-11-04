<?php
/**
 * Loads config, opens the session and wires up helpers + controllers.
 */
define('ROOT', dirname(__DIR__));
define('APP', __DIR__);

$config = require ROOT . '/config.php';
if (is_file(ROOT . '/config.local.php')) {
    $config = array_replace_recursive($config, require ROOT . '/config.local.php');
}
$GLOBALS['config'] = $config;

date_default_timezone_set($config['timezone'] ?? 'UTC');

if (($config['app_env'] ?? 'production') === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

require APP . '/helpers.php';
require APP . '/db.php';
require APP . '/auth.php';
require APP . '/seo.php';
require APP . '/upload.php';
require APP . '/schema.php';
require APP . '/niches.php';
require APP . '/content.php';
require APP . '/geo.php';

foreach (glob(APP . '/controllers/*.php') as $controller) {
    require $controller;
}

// Session (used for CSRF tokens, flash messages and admin login)
$secure = is_https();
session_name('pk_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => base_path() . '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

/**
 * Minimal router. Patterns use {name} placeholders.
 */
function dispatch(array $routes)
{
    $path = request_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'HEAD') {
        $method = 'GET';
    }

    // Send people to the installer until it has run.
    if (!is_installed() && $path !== '/install') {
        redirect('install');
    }
    if (is_installed()) {
        run_migrations();
    }

    $allowedForPath = false;
    foreach ($routes as $route) {
        list($methods, $pattern, $handler) = $route;
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (!preg_match($regex, $path, $m)) {
            continue;
        }
        $allowedForPath = true;
        if (!in_array($method, explode('|', $methods), true)) {
            continue;
        }
        $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
        if ($method === 'POST') {
            verify_csrf();
        }
        try {
            call_user_func($handler, $params);
        } catch (Throwable $e) {
            handle_exception($e);
        }
        return;
    }

    if ($allowedForPath) {
        http_response_code(405);
        echo 'Method not allowed';
        return;
    }
    not_found();
}

function handle_exception(Throwable $e)
{
    error_log('[peakilo] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (cfg('app_env') === 'local') {
        echo '<pre style="padding:24px;font:14px/1.5 monospace;white-space:pre-wrap">'
            . e(get_class($e) . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
        return;
    }
    render('site/error', [
        'code'    => 500,
        'title'   => 'Something went wrong',
        'message' => 'We hit a snag loading this page. Please try again in a moment.',
    ], ['title' => 'Something went wrong', 'robots' => 'noindex']);
}
