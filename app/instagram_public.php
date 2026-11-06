<?php
/** Public Reels-page metadata. No Graph API token, cookies or Python service. */
function instagram_public_result($html, $handle)
{
    $base = ['followers' => null, 'average_views' => null, 'average_likes' => null,
        'average_comments' => null, 'sample_size' => 0, 'likes_sample_size' => 0,
        'comments_sample_size' => 0, 'posts_scanned' => 0, 'view_metric' => 'plays',
        'source' => 'Instagram public Reels page', 'sample_description' => '',
        'fetched_at' => gmdate('c'), 'impressions' => null];
    $users = [];
    // Parse JSON only. Never execute page scripts or trust unrelated profile suggestions.
    $walk = function ($value, $depth = 0) use (&$walk, &$users) {
        if (!is_array($value) || $depth > 80) return;
        if (isset($value['xig_user_by_username']) && is_array($value['xig_user_by_username'])) {
            $users[] = $value['xig_user_by_username'];
        }
        foreach ($value as $child) if (is_array($child)) $walk($child, $depth + 1);
    };
    preg_match_all('#<script\b(?=[^>]*\btype=["\']application/json["\'])[^>]*>(.*?)</script>#is', (string) $html, $scripts);
    foreach ($scripts[1] as $script) {
        $data = json_decode($script, true, 128);
        if (is_array($data)) $walk($data);
    }
    $profile = null;
    foreach ($users as $user) {
        if (strtolower((string) ($user['username'] ?? '')) === strtolower($handle) && !empty($user['pk'])) {
            $profile = $user; break;
        }
    }
    if ($profile === null) return $base + ['status' => 'unavailable', 'message' => 'Instagram did not share this public profile. Check the username or try again later.'];
    $base['followers'] = social_number($profile['follower_count'] ?? null);
    $reels = [];
    if (empty($profile['is_private'])) {
        foreach ($users as $user) {
            if ((string) ($user['pk'] ?? '') !== (string) $profile['pk']) continue;
            foreach (array_slice($user['polaris_clips_connection']['edges'] ?? [], 0, 36) as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node)) continue;
                $id = (string) ($node['pk'] ?? $node['id'] ?? '');
                if ($id === '' || isset($reels[$id])) continue;
                $base['posts_scanned']++;
                // Pinned hits can distort an average of the account's recent work.
                if (!empty($node['clips_tab_pinned_user_ids'])) continue;
                $owner = $node['user']['pk'] ?? null;
                if ($owner !== null && (string) $owner !== (string) $profile['pk']) continue;
                $reels[$id] = $node;
                if (count($reels) >= 12) break 2;
            }
        }
    }
    $views = []; $likes = []; $comments = [];
    foreach ($reels as $reel) {
        if (empty($reel['like_and_view_counts_disabled'])) {
            $v = social_number($reel['play_count'] ?? null);
            $l = social_number($reel['like_count'] ?? null);
            if ($v !== null) $views[] = $v;
            if ($l !== null) $likes[] = $l;
        }
        $c = social_number($reel['comment_count'] ?? null);
        if ($c !== null) $comments[] = $c;
    }
    foreach (['views' => $views, 'likes' => $likes, 'comments' => $comments] as $key => $values) {
        $base['average_' . $key] = $values ? (int) round(array_sum($values) / count($values)) : null;
    }
    $base['sample_size'] = count($views);
    $base['likes_sample_size'] = count($likes);
    $base['comments_sample_size'] = count($comments);
    $base['sample_description'] = 'Up to 12 unpinned Reels returned by the public profile grid. Video numbers are plays, not impressions; unavailable counts are excluded.';
    $base['status'] = $base['followers'] !== null && $base['average_views'] !== null ? 'ok'
        : ($base['followers'] !== null || $base['average_views'] !== null ? 'partial' : 'unavailable');
    $base['message'] = $base['status'] === 'unavailable' ? 'Instagram did not share public counts. You can enter them manually.' : 'Fetched Instagram stats are locked. You can enter any unavailable numbers manually.';
    return $base;
}

function instagram_public_lookup(array $identity)
{
    $handle = $identity['handle'] ?? '';
    if (($identity['platform'] ?? '') !== 'instagram' || !preg_match('/^[A-Za-z0-9_.]{1,30}$/', $handle)) {
        throw new InvalidArgumentException('Enter a valid Instagram username.');
    }
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL is required');
    // Construct the URL ourselves: visitor input can never select a different host.
    $curl = curl_init('https://www.instagram.com/' . rawurlencode(strtolower($handle)) . '/reels/');
    $body = '';
    curl_setopt_array($curl, [
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 18,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: text/html', 'Accept-Language: en-US,en;q=0.9',
            'Sec-Fetch-Dest: document', 'Sec-Fetch-Mode: navigate', 'Sec-Fetch-Site: none'],
        CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body) {
            if (strlen($body) + strlen($chunk) > 8 * 1024 * 1024) return 0;
            $body .= $chunk; return strlen($chunk);
        },
    ]);
    $ok = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($ok === false || $status !== 200) {
        // No cookies/challenge bypass. A blocked request stays unavailable, not zero.
        error_log('[peakilo] Instagram public page unavailable (HTTP ' . $status . ')');
        return ['status' => 'unavailable', 'followers' => null, 'average_views' => null,
            'average_likes' => null, 'sample_size' => 0, 'impressions' => null,
            'message' => 'Instagram could not be reached or limited this lookup. Try again later, or enter your stats manually.'];
    }
    return instagram_public_result($body, $handle);
}
