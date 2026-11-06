<?php
require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/social_metrics.php';
$passed = 0;
function verify_instagram($condition, $description) {
    global $passed;
    if (!$condition) throw new RuntimeException($description);
    $passed++;
}
function instagram_fixture($profile, $nodes, $gridPk = '123') {
    $edges = array_map(function ($node) { return ['node' => $node]; }, $nodes);
    return '<script type="application/json" data-sjs>' . json_encode(['require' => [[['__bbox' => ['result' => ['data' => [
        'xig_user_by_username' => $profile,
    ]]]]]]]) . '</script><script data-sjs type="application/json">' . json_encode(['data' => [
        'xig_user_by_username' => ['pk' => $gridPk, 'polaris_clips_connection' => ['edges' => $edges]],
    ]]) . '</script>';
}
$profile = ['pk' => '123', 'username' => 'chennaiipl', 'follower_count' => 1000, 'is_private' => false];
$posts = [
    ['pk' => 'pin', 'play_count' => 99999, 'like_count' => 99999, 'clips_tab_pinned_user_ids' => ['123']],
    ['pk' => 'one', 'play_count' => 0, 'like_count' => 0, 'comment_count' => 0],
    ['pk' => 'two', 'play_count' => 100, 'like_count' => 20, 'comment_count' => 10],
    ['pk' => 'two', 'play_count' => 9000, 'like_count' => 9000],
    ['pk' => 'hidden', 'play_count' => 4000, 'like_count' => 1000, 'like_and_view_counts_disabled' => true],
    ['pk' => 'missing', 'play_count' => null, 'like_count' => null],
    ['pk' => 'other', 'user' => ['pk' => '456'], 'play_count' => 9999, 'like_count' => 9999],
];
$out = instagram_public_result(instagram_fixture($profile, $posts), 'chennaiipl');
verify_instagram($out['followers'] === 1000, 'Target profile followers');
verify_instagram($out['average_views'] === 50 && $out['sample_size'] === 2, 'Zero included; pins, hidden, duplicates and other owners excluded');
verify_instagram($out['average_likes'] === 10 && $out['likes_sample_size'] === 2, 'Average likes excludes unknowns');
verify_instagram($out['average_comments'] === 5, 'Average comments');
verify_instagram($out['view_metric'] === 'plays' && $out['impressions'] === null, 'Plays never become impressions');
verify_instagram($out['status'] === 'ok', 'Successful complete result');
verify_instagram(instagram_public_result(instagram_fixture($profile, $posts), 'someone_else')['followers'] === null, 'Reject wrong username');
verify_instagram(instagram_public_result(instagram_fixture($profile, $posts, '456'), 'chennaiipl')['sample_size'] === 0, 'Reject other profile grid');
$profile['is_private'] = true;
verify_instagram(instagram_public_result(instagram_fixture($profile, $posts), 'chennaiipl')['sample_size'] === 0, 'Private media excluded');
$profile['is_private'] = false;
verify_instagram(instagram_public_result(instagram_fixture($profile, []), 'chennaiipl')['status'] === 'partial', 'Follower-only result retained');
$posts = [];
for ($i = 0; $i < 20; $i++) $posts[] = ['pk' => 'p' . $i, 'play_count' => $i * 100];
$out = instagram_public_result(instagram_fixture($profile, $posts), 'chennaiipl');
verify_instagram($out['sample_size'] === 12 && $out['average_views'] === 550, 'Sample capped at twelve returned Reels');
verify_instagram(instagram_public_result('<html>Login required</html>', 'chennaiipl')['status'] === 'unavailable', 'Login wall is not empty successful data');
verify_instagram(instagram_public_result('<script type="application/json">invalid</script>', 'chennaiipl')['followers'] === null, 'Malformed JSON ignored');
verify_instagram(social_profile_identity('instagram', 'https://www.instagram.com/chennaiipl/')['handle'] === 'chennaiipl', 'Reported URL normalized');
echo "PASS: $passed Instagram public-page assertions\n";
