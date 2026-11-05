<?php
// Isolated tests: never read or modify the site's configured database/cache.
$workspace = dirname(__DIR__);
define('ROOT', sys_get_temp_dir() . '/peakilo-social-' . bin2hex(random_bytes(6)));
mkdir(ROOT . '/storage', 0700, true);
$GLOBALS['config'] = ['social_metrics' => ['service_url' => '', 'service_token' => ''], 'db' => ['driver' => 'sqlite', 'sqlite_path' => ':memory:']];
require $workspace . '/app/helpers.php';
require $workspace . '/app/db.php';
require $workspace . '/app/schema.php';
require $workspace . '/app/social_metrics.php';
$passed = 0;
function check($condition, $message) {
    global $passed;
    if (!$condition) throw new RuntimeException($message);
    $passed++;
}
try {
    $id = social_profile_identity('instagram', 'https://www.instagram.com/Some.Name/?igsh=123');
    check($id === social_profile_identity('instagram', '@some.name'), 'Canonical Instagram identity');
    check(profile_link_matches('instagram', 'some.name'), 'Dotted bare handles accepted');
    check(social_profile_identity('youtube', 'youtube.com/@Example')['handle'] === 'Example', 'YouTube handle');
    check(social_profile_identity('youtube', 'https://youtube.com/channel/UC_x5XG1OV2P6uZZ5FSM9Ttw')['kind'] === 'channel', 'YouTube channel ID');
    check(social_profile_identity('linkedin', 'linkedin.com/company/example')['kind'] === 'company', 'Company profile');
    foreach (['https://instagram.com.evil.test/name', 'https://instagram.com@localhost/name', 'http://127.0.0.1/',
              'https://instagram.com:8080/name', 'file:///etc/passwd', 'instagram.com/p/123',
              'instagram.com/name/extra', 'instagram.com/%2e%2e%2fsecrets', 'instagram.com/', "a\nb", ''] as $bad) {
        $rejected = false;
        try { social_profile_identity('instagram', $bad); } catch (InvalidArgumentException $e) { $rejected = true; }
        check($rejected, 'Reject unsafe/non-profile input: ' . $bad);
    }
    check(social_number(null) === null && social_number(0) === 0 && social_number(-1) === null, 'Null, zero and negative counts');
    check(social_number(2000000001) === null && social_number(INF) === null, 'Counts bounded for existing INT columns');
    $first = social_lookup($id);
    check($first['status'] === 'unconfigured' && !$first['cached'], 'Graceful unconfigured service');
    check(social_lookup($id)['cached'], 'Repeated lookup uses cache');
    for ($i = 0; $i < 30; $i++) check(social_rate_limit('test'), 'Within IP limit');
    check(!social_rate_limit('test'), 'IP limit enforced across calls');
    check(social_rate_limit('another-ip'), 'Separate IP bucket');
    $_SESSION = ['social_lookups' => ['instagram' => [
        'identity' => $id, 'followers' => 1000, 'average_views' => 0,
        'sample_size' => 2, 'fetched_at' => gmdate('c'), 'source' => 'fixture',
    ]]];
    $data = ['instagram' => 'some.name', 'instagram_followers' => '1K', 'instagram_avg_views' => '0'];
    $metadata = social_submission_metadata($data, ['instagram' => []]);
    check($metadata['instagram']['followers_source'] === 'fetched', 'Fetched provenance from server session');
    check($metadata['instagram']['views_source'] === 'fetched', 'Zero-view fetched provenance retained');
    $data['instagram_followers'] = '2000';
    check(social_submission_metadata($data, ['instagram' => []])['instagram']['followers_source'] === 'manual', 'Edited count is manual');
    $data['instagram'] = 'different';
    check(!isset(social_submission_metadata($data, ['instagram' => []])['instagram']['lookup']), 'Different profile cannot reuse lookup');
    $data['instagram'] = '';
    check(social_submission_metadata($data, ['instagram' => []]) === [], 'Removed channel clears metadata');
    check(social_submission_metadata($data, [], ['youtube' => ['followers_source' => 'manual']])['youtube']['followers_source'] === 'manual', 'Disabled channel retained');
    db()->exec('CREATE TABLE influencers (id INTEGER PRIMARY KEY)');
    foreach (schema_migrations()[10] as $sql) db()->exec($sql);
    insert('influencers', ['id' => 1, 'social_metrics' => json_encode($metadata)]);
    check(json_decode(one('SELECT * FROM influencers WHERE id = 1')['social_metrics'], true) === $metadata, 'SQLite migration and provenance roundtrip');
    echo "PASS: $passed PHP assertions\n";
} finally {
    // All paths below were created in this test's unique temp directory.
    foreach (glob(ROOT . '/storage/social/*.json') as $file) unlink($file);
    if (is_dir(ROOT . '/storage/social')) rmdir(ROOT . '/storage/social');
    rmdir(ROOT . '/storage'); rmdir(ROOT);
}
