<?php
// Dedicated local test router. Configured database, mail and uploads are never used.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($path, '/assets/') === 0) return false;
$sessionDir = dirname(__DIR__) . '/storage/social-test-sessions';
if (!is_dir($sessionDir)) mkdir($sessionDir, 0700, true);
ini_set('session.save_path', $sessionDir);
require dirname(__DIR__) . '/app/bootstrap.php';
$GLOBALS['config']['db'] = ['driver' => 'sqlite', 'sqlite_path' => dirname(__DIR__) . '/storage/social-test-browser.sqlite'];
$GLOBALS['config']['site_url'] = 'http://127.0.0.1:8897';
$GLOBALS['config']['mail']['enabled'] = false;
$GLOBALS['config']['social_metrics'] = ['service_url' => 'http://127.0.0.1:8766', 'service_token' => str_repeat('t', 32)];
if (!val("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='influencers'")) {
    install_schema();
    save_setting('demo_mode', '0');
    save_setting('creator_platforms', 'instagram,youtube,tiktok,facebook,x,snapchat,linkedin,threads');
    run_migrations();
    insert('influencers', ['full_name' => 'Test Creator', 'email' => 'social-test@example.test',
        'photo' => 'test-existing-photo.jpg', 'created_at' => now(), 'updated_at' => now()]);
}
if ($path === '/join') { if (is_post()) verify_csrf(); join_form(); return; }
if ($path === '/join/thanks') { echo 'Saved'; return; }
if ($path === '/api/social-profile') { verify_csrf(); social_profile_lookup(); return; }
http_response_code(404);
