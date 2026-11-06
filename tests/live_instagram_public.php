<?php
// Optional live check: public data only, no site configuration or credentials.
define('ROOT', dirname(__DIR__));
$GLOBALS['config'] = ['social_metrics' => ['service_url' => '', 'service_token' => '']];
require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/social_metrics.php';
$identity = social_profile_identity('instagram', $argv[1] ?? 'chennaiipl');
$result = social_lookup($identity);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(($result['status'] ?? '') === 'ok' ? 0 : 1);
