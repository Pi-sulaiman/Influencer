<?php
/**
 * Peakilo configuration
 * ---------------------------------------------------------------
 * 1. In cPanel > MySQL Databases, create a database + user and add
 *    the user to the database with ALL PRIVILEGES.
 * 2. Put those details in the 'db' block below.
 * 3. Visit https://peakilo.com/install once to create the tables
 *    and your first admin account.
 *
 * For local testing you can create config.local.php (see README).
 * Anything in config.local.php overrides the values here.
 */
return [
    // 'production' hides PHP errors from visitors. Use 'local' while developing.
    'app_env'  => 'production',

    // Full public URL with no trailing slash. Used for canonical links,
    // the sitemap and social previews. Leave '' to auto-detect.
    'site_url' => 'https://peakilo.com',

    'timezone' => 'UTC',

    'db' => [
        'driver'      => 'mysql',          // 'mysql' on cPanel, 'sqlite' for quick local testing
        'host'        => 'localhost',
        'port'        => 3306,
        'name'        => 'cpaneluser_peakilo',
        'user'        => 'cpaneluser_peakilo',
        'pass'        => 'CHANGE_ME',
        'sqlite_path' => __DIR__ . '/storage/database.sqlite',
    ],

    // Email alerts for new influencer registrations and contact messages.
    // Sent with PHP mail(), which works on most cPanel hosts.
    'mail' => [
        'enabled' => true,
        'from'    => 'no-reply@peakilo.com',
    ],

    // Max size for uploaded photos, in megabytes.
    'upload_max_mb' => 5,

    // Private Python lookup service. See docs/social-metrics.md for cPanel setup.
    // Keep the shared secret in config.local.php or environment variables.
    'social_metrics' => [
        'service_url' => getenv('SOCIAL_METRICS_URL') ?: '',
        'service_token' => getenv('SOCIAL_METRICS_TOKEN') ?: '',
    ],
];
