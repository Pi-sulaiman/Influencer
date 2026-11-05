<?php
/**
 * Campaign links (/c/{code}) and the influencer registration form (/join).
 *
 * Campaign "mode" decides what a campaign link does:
 *   form    -> shows the registration form with the campaign brief
 *   landing -> remembers the campaign and sends the visitor to the landing page
 *   url     -> remembers the campaign and redirects to a custom URL
 */

function find_campaign($code)
{
    if (!$code) {
        return null;
    }
    return one('SELECT * FROM campaigns WHERE code = ?', [(string) $code]);
}

function campaign_link($params)
{
    $campaign = find_campaign($params['code']);
    if (!$campaign) {
        not_found();
    }

    // Count one visit per browser session per campaign.
    $key = 'visited_c' . $campaign['id'];
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = 1;
        q('UPDATE campaigns SET visits = visits + 1 WHERE id = ?', [$campaign['id']]);
        insert('campaign_visits', [
            'campaign_id' => $campaign['id'],
            'visited_at' => now(),
            'referrer' => mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 250),
            'ip_hash' => ip_hash(),
        ]);
    }

    if ($campaign['status'] !== 'active') {
        render('join/closed', ['campaign' => $campaign], ['title' => 'Campaign closed', 'robots' => 'noindex']);
        return;
    }

    setcookie('pk_ref', $campaign['code'], [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => base_path() . '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if ($campaign['mode'] === 'landing') {
        redirect('?ref=' . rawurlencode($campaign['code']));
    }
    if ($campaign['mode'] === 'url' && $campaign['redirect_url']) {
        $target = $campaign['redirect_url'];
        if (!preg_match('#^https?://#i', $target)) {
            $target = url($target);
        }
        header('Location: ' . $target, true, 302);
        exit;
    }
    redirect('join?c=' . rawurlencode($campaign['code']));
}

function join_fields()
{
    $fields = ['full_name', 'display_name', 'email', 'phone', 'city', 'state', 'country', 'gender', 'languages', 'primary_platform',
        'website', 'engagement_rate', 'monthly_impressions', 'audience_country', 'audience_age', 'audience_gender', 'past_brands',
        'fee_min', 'fee_max', 'currency', 'fee_notes', 'bio', 'pitch'];
    foreach (array_keys(platform_options()) as $p) {
        array_push($fields, $p, $p . '_followers', $p . '_avg_views');
    }
    foreach (array_keys(rate_card_options()) as $k) {
        $fields[] = 'rate_' . $k;
    }
    return $fields;
}

function join_form()
{
    $code = query('c') ?: (string) input('campaign_code') ?: ($_COOKIE['pk_ref'] ?? '');
    $campaign = find_campaign($code);
    if ($campaign && $campaign['status'] !== 'active') {
        if (query('c')) {
            render('join/closed', ['campaign' => $campaign], ['title' => 'Campaign closed', 'robots' => 'noindex']);
            return;
        }
        $campaign = null;
    }

    $errors = [];
    $data = array_fill_keys(join_fields(), '');
    $data['currency'] = $campaign['currency'] ?? setting('default_currency', 'USD');
    $data['niches'] = [];
    $data['content_types'] = [];
    $data['consent_terms'] = '';
    $data['consent_feature'] = '';
    $data['barter_ok'] = '';

    if (is_post()) {
        foreach (join_fields() as $f) {
            $raw = input($f);
            $data[$f] = is_array($raw) ? '' : mb_substr((string) $raw, 0, in_array($f, ['bio', 'pitch'], true) ? 1500 : 190);
        }
        $langs = array_values(array_intersect(input_array('languages'), language_options()));
        if ($langs) $data['languages'] = implode(', ', array_slice($langs, 0, 10));
        $data['email'] = strtolower($data['email']);
        $data['niches'] = array_slice(array_intersect(input_array('niches'), niche_options()), 0, 5);
        $data['content_types'] = array_intersect(input_array('content_types'), content_type_options());
        $data['consent_terms'] = input('consent_terms') ? '1' : '';
        $data['consent_feature'] = input('consent_feature') ? '1' : '';
        $data['barter_ok'] = input('barter_ok') ? '1' : '';
        if (!in_array($data['audience_age'], audience_age_options(), true)) $data['audience_age'] = '';
        if (!in_array($data['audience_gender'], audience_gender_options(), true)) $data['audience_gender'] = '';
        if (!in_array($data['gender'], ['Female', 'Male', 'Non-binary', 'Other'], true)) $data['gender'] = '';

        // Honeypot: bots fill hidden fields. Pretend success.
        if (input('company_site') !== '') {
            redirect('join/thanks');
        }

        if (mb_strlen($data['full_name']) < 2) $errors['full_name'] = 'Please enter your full name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if ($data['phone'] === '') $errors['phone'] = 'Add your WhatsApp number so we can send you briefs.';
        elseif (!preg_match('/^[0-9+()\-\s.]{6,25}$/', $data['phone']) || strlen(preg_replace('/\D/', '', $data['phone'])) < 8) $errors['phone'] = 'Enter a valid WhatsApp number with country code.';
        if ($data['country'] === '') $errors['country'] = 'Tell us which country you are based in.';
        // State and city are required: brands book by location.
        $states = states_for($data['country']);
        if ($data['state'] === '') $errors['state'] = 'Choose your state.';
        elseif ($states && !in_array($data['state'], $states, true)) $errors['state'] = 'Choose your state from the list.';
        $data['city'] = normalize_city($data['city'], $data['state'], $data['country']);
        if (mb_strlen($data['city']) < 2) $errors['city'] = 'Tell us which city you are in.';

        // Only platforms switched on in Admin > Settings > Platforms are on the form.
        $platforms = enabled_platforms();
        $hasChannel = false;
        foreach ($platforms as $p => $meta) {
            if ($data[$p] === '') continue;
            $hasChannel = true;
            if (!profile_link_matches($p, $data[$p])) $errors[$p] = 'That link does not look like a ' . $meta['label'] . ' profile.';
            // Unavailable/hidden stats must not block registration. Zero is a valid count.
            foreach (['_followers', '_avg_views'] as $suffix) {
                if ($data[$p . $suffix] !== '' && (!preg_match('/^\d+(?:\.\d+)?[km]?$/i', str_replace([',', ' '], '', $data[$p . $suffix])) || parse_count($data[$p . $suffix]) > 2000000000)) {
                    $errors[$p . $suffix] = 'Enter a valid non-negative count, or leave it blank.';
                }
            }
        }
        if (!$hasChannel) $errors['channels'] = 'Add at least one social channel.';
        if (!$data['niches']) $errors['niches'] = 'Pick at least one niche.';

        // Rate card (optional). When the min/max range is left empty it is
        // filled from the lowest and highest rate on the card.
        $rates = [];
        foreach (array_keys(rate_card_options()) as $k) {
            $v = to_money_or_null($data['rate_' . $k]);
            if ($v !== null && $v > 0) $rates[$k] = $v;
        }
        $feeMin = to_money_or_null($data['fee_min']);
        $feeMax = to_money_or_null($data['fee_max']);
        if ($rates && $feeMin === null) $feeMin = min($rates);
        if ($rates && $feeMax === null) $feeMax = max($rates);
        if ($feeMin === null) $errors['fee_min'] = 'Enter your minimum fee.';
        if ($feeMax === null) $errors['fee_max'] = 'Enter your maximum fee.';
        if ($feeMin !== null && $feeMax !== null && $feeMax < $feeMin) $errors['fee_max'] = 'Maximum fee should be equal to or higher than the minimum.';
        if (!array_key_exists($data['currency'], currencies())) $data['currency'] = setting('default_currency', 'USD');
        if (!$data['consent_terms']) $errors['consent_terms'] = 'Please agree to the terms and privacy policy.';

        $existing = $data['email'] !== '' ? one('SELECT * FROM influencers WHERE email = ?', [$data['email']]) : null;
        $hasUpload = isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (!$hasUpload && !($existing && $existing['photo'])) {
            $errors['photo'] = 'Please add a profile photo.';
        }

        // Simple rate limit: 10 submissions per hour per visitor.
        $recent = (int) val('SELECT COUNT(*) FROM influencers WHERE ip_hash = ? AND updated_at > ?', [ip_hash(), date('Y-m-d H:i:s', time() - 3600)]);
        if ($recent >= 10) $errors['form'] = 'Too many submissions from your connection. Please try again later.';

        $photo = null;
        if (!$errors && $hasUpload) {
            try {
                $photo = save_image_upload($_FILES['photo'], 'creators', 900);
            } catch (RuntimeException $e) {
                $errors['photo'] = $e->getMessage();
            }
        }
        $extraUploads = [];
        foreach (['rate_card_image' => 'ratecards', 'insights_image' => 'insights'] as $field => $folder) {
            $sent = isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if (!$errors && $sent) {
                try {
                    $extraUploads[$field] = save_image_upload($_FILES[$field], $folder, 1800);
                } catch (RuntimeException $e) {
                    $errors[$field] = $e->getMessage();
                }
            }
        }

        if (!$errors) {
            $followers = 0;
            $row = [
                'full_name' => $data['full_name'],
                'display_name' => $data['display_name'] ?: null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'city' => $data['city'] ?: null,
                'state' => $data['state'] ?: null,
                'country' => $data['country'],
                'gender' => $data['gender'] ?: null,
                'languages' => $data['languages'] ?: null,
                'primary_platform' => array_key_exists($data['primary_platform'], $platforms) ? $data['primary_platform'] : null,
                'website' => $data['website'] ?: null,
                'engagement_rate' => $data['engagement_rate'] !== '' ? min(100, (float) str_replace(',', '.', preg_replace('/[^\d.,]/', '', $data['engagement_rate']))) : null,
                'niches' => implode(', ', $data['niches']),
                'content_types' => implode(', ', $data['content_types']) ?: null,
                'fee_min' => $feeMin,
                'fee_max' => $feeMax,
                'currency' => $data['currency'],
                'fee_notes' => $data['fee_notes'] ?: null,
                'rate_card' => $rates ? json_encode($rates) : null,
                'barter_ok' => $data['barter_ok'] ? 1 : 0,
                'monthly_impressions' => parse_count($data['monthly_impressions']),
                'audience_country' => $data['audience_country'] ?: null,
                'audience_age' => $data['audience_age'] ?: null,
                'audience_gender' => $data['audience_gender'] ?: null,
                'past_brands' => $data['past_brands'] !== '' ? mb_substr($data['past_brands'], 0, 500) : null,
                'bio' => $data['bio'] ?: null,
                'consent_feature' => $data['consent_feature'] ? 1 : 0,
                'ip_hash' => ip_hash(),
                'updated_at' => now(),
            ];
            $impressions = 0;
            // Platforms that are switched off keep whatever the creator saved before.
            foreach (array_diff_key(platform_options(), $platforms) as $p => $meta) {
                if ($existing && !empty($existing[$p])) {
                    $followers += (int) $existing[$p . '_followers'];
                    $impressions += (int) ($existing[$p . '_avg_views'] ?? 0);
                }
            }
            foreach (array_keys($platforms) as $p) {
                $on = $data[$p] !== '';
                $row[$p] = $on ? $data[$p] : null;
                $count = $on ? parse_count($data[$p . '_followers']) : null;
                $views = $on ? parse_count($data[$p . '_avg_views']) : null;
                $row[$p . '_followers'] = $count;
                $row[$p . '_avg_views'] = $views;
                $followers += (int) $count;
                $impressions += (int) $views;
            }
            $row['total_followers'] = $followers;
            // Legacy column: sum of per-channel averages, NOT measured impressions.
            $row['avg_impressions'] = $impressions ?: null;
            $previousMetrics = json_decode($existing['social_metrics'] ?? '{}', true) ?: [];
            $row['social_metrics'] = json_encode(social_submission_metadata($data, $platforms, $previousMetrics));
            if (!$row['primary_platform']) {
                // The biggest channel becomes the main platform.
                $best = 0;
                foreach (array_keys($platforms) as $p) {
                    if ($row[$p] && (int) $row[$p . '_followers'] >= $best) { $best = (int) $row[$p . '_followers']; $row['primary_platform'] = $p; }
                }
            }
            if ($photo) {
                $row['photo'] = $photo;
            }
            foreach ($extraUploads as $field => $path) {
                $row[$field] = $path;
            }

            if ($existing) {
                if (!$row['consent_feature']) {
                    $row['featured'] = 0;
                }
                // No passwords, so a repeat submission with the same email updates
                // the profile. Leave a note for the team and keep the old photo file.
                $row['admin_notes'] = trim(($existing['admin_notes'] ?? '') . "\n[" . date('Y-m-d H:i') . '] Profile re-submitted via form'
                    . ($campaign ? ' (campaign: ' . $campaign['title'] . ')' : '') . '.');
                update('influencers', $row, 'id = ?', [$existing['id']]);
                $influencerId = (int) $existing['id'];
            } else {
                $row['status'] = 'new';
                $row['source'] = $campaign ? 'campaign:' . $campaign['code'] : 'direct';
                $row['created_at'] = now();
                $influencerId = insert('influencers', $row);
            }

            if ($campaign) {
                $app = one('SELECT id FROM applications WHERE influencer_id = ? AND campaign_id = ?', [$influencerId, $campaign['id']]);
                if ($app) {
                    update('applications', ['pitch' => $data['pitch'] ?: null, 'updated_at' => now()], 'id = ?', [$app['id']]);
                } else {
                    insert('applications', [
                        'influencer_id' => $influencerId,
                        'campaign_id' => $campaign['id'],
                        'status' => 'applied',
                        'pitch' => $data['pitch'] ?: null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            send_mail(setting('contact_email'), 'New creator: ' . $data['full_name'] . ($campaign ? ' (' . $campaign['title'] . ')' : ''),
                "{$data['full_name']} just " . ($existing ? 'updated their profile' : 'joined the creator network') . ".\n"
                . "Email: {$data['email']}\nFollowers: " . number_format($followers) . "\nCombined channel averages: " . number_format($impressions) . "\nFee: " . fee_range($feeMin, $feeMax, $data['currency']) . "\n"
                . ($campaign ? "Campaign: {$campaign['title']}\n" : '')
                . "\nView profile: " . abs_url('admin/influencers/' . $influencerId));

            $_SESSION['join_done'] = ['id' => $influencerId, 'name' => $data['full_name'], 'campaign' => $campaign['title'] ?? null, 'updated' => (bool) $existing];
            redirect('join/thanks');
        }
    }

    $posted = is_post();
    render('join/form', compact('campaign', 'data', 'errors', 'posted'), [
        'title' => $campaign ? 'Apply: ' . $campaign['title'] : 'Join the Creator Network',
        'description' => $campaign
            ? str_limit($campaign['description'] ?: 'Apply to this Peakilo creator campaign.', 160)
            : 'Create your free Peakilo creator profile. Add your channels, audience and rate range to get matched with paid brand campaigns.',
        'canonical' => abs_url('join'),
        'robots' => $campaign ? 'noindex, follow' : null,
        'body_class' => 'page-join',
        'scripts' => ['js/join.js', 'js/social-metrics.js'],
        'styles' => ['css/join.css'],
    ]);
}

function join_thanks()
{
    $done = $_SESSION['join_done'] ?? null;
    // The creator card is built from the profile this visitor just sent (kept in their session only).
    $creator = !empty($done['id']) ? one('SELECT * FROM influencers WHERE id = ?', [(int) $done['id']]) : null;
    render('join/thanks', ['done' => $done, 'card' => $creator ? creator_card_data($creator) : null, 'creator' => $creator], [
        'title' => 'Thank you',
        'robots' => 'noindex',
        'styles' => ['css/join.css'],
        'scripts' => ['js/creator-card.js'],
    ]);
}

/**
 * Everything the branded creator card shows. Rates are included because the
 * card is only ever shown to the creator who owns it; they choose whether the
 * downloaded image shows them.
 */
function creator_card_data(array $r)
{
    $channels = [];
    foreach (platform_options() as $p => $meta) {
        if (empty($r[$p]) || !platform_enabled($p)) continue;
        $handle = trim((string) $r[$p]);
        if (preg_match('#[/.]#', ltrim($handle, '@'))) {
            $path = trim((string) parse_url(preg_match('#^https?://#i', $handle) ? $handle : 'https://' . $handle, PHP_URL_PATH), '/');
            $parts = array_values(array_filter(explode('/', $path)));
            $handle = $parts ? (in_array($parts[0], ['in', 'add', 'channel', 'c', 'user'], true) && isset($parts[1]) ? $parts[1] : $parts[0]) : '';
        }
        $channels[] = [
            'key' => $p,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'handle' => ltrim(rawurldecode($handle), '@'),
            'followers' => (int) $r[$p . '_followers'],
            'views' => (int) ($r[$p . '_avg_views'] ?? 0),
        ];
    }
    usort($channels, function ($a, $b) { return $b['followers'] - $a['followers']; });
    $primary = $channels[0] ?? null;
    foreach ($channels as $c) {
        if ($c['key'] === $r['primary_platform']) { $primary = $c; break; }
    }
    $sym = trim(currencies()[$r['currency']] ?? '');
    $rates = [];
    foreach (rate_card($r['rate_card'] ?? '') as $k => $v) {
        $rates[] = [rate_card_options()[$k][0], money($v, $r['currency'])];
    }
    return [
        'code' => creator_code($r['id']),
        'name' => $r['display_name'] ?: $r['full_name'],
        'first' => explode(' ', trim((string) $r['full_name']))[0],
        'photo' => $r['photo'] ? upload_url($r['photo']) : '',
        'initials' => initials($r['display_name'] ?: $r['full_name']),
        'city' => (string) $r['city'],
        'country' => (string) $r['country'],
        'niches' => array_slice(csv_list($r['niches']), 0, 3),
        'languages' => array_slice(csv_list($r['languages']), 0, 3),
        'followers' => (int) $r['total_followers'],
        'views' => (int) ($r['avg_impressions'] ?? 0),
        'er' => $r['engagement_rate'] !== null && $r['engagement_rate'] !== '' ? (float) $r['engagement_rate'] : null,
        'tier' => creator_tier($r['total_followers'])['label'],
        'channels' => array_slice($channels, 0, 4),
        'handle' => $primary ? $primary['handle'] : '',
        'primaryIcon' => $primary ? $primary['icon'] : '',
        'feeFrom' => $r['fee_min'] !== null ? money($r['fee_min'], $r['currency']) : '',
        'feeTo' => $r['fee_max'] !== null && (float) $r['fee_max'] !== (float) $r['fee_min'] ? money($r['fee_max'], $r['currency']) : '',
        'feeNotes' => (string) ($r['fee_notes'] ?? ''),
        'rates' => array_slice($rates, 0, 4),
        'symbol' => $sym,
        'site' => preg_replace('#^https?://(www\.)?#', '', rtrim(abs_url(''), '/')),
    ];
}
