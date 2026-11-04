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
    return ['full_name', 'email', 'phone', 'city', 'country', 'gender', 'languages', 'primary_platform',
        'instagram', 'instagram_followers', 'youtube', 'youtube_followers', 'tiktok', 'tiktok_followers',
        'facebook', 'facebook_followers', 'x', 'x_followers', 'website', 'engagement_rate',
        'fee_min', 'fee_max', 'currency', 'fee_notes', 'bio', 'pitch'];
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

    if (is_post()) {
        foreach (join_fields() as $f) {
            $data[$f] = mb_substr((string) input($f), 0, in_array($f, ['bio', 'pitch'], true) ? 1500 : 190);
        }
        $data['email'] = strtolower($data['email']);
        $data['niches'] = array_slice(array_intersect(input_array('niches'), niche_options()), 0, 5);
        $data['content_types'] = array_intersect(input_array('content_types'), content_type_options());
        $data['consent_terms'] = input('consent_terms') ? '1' : '';
        $data['consent_feature'] = input('consent_feature') ? '1' : '';

        // Honeypot: bots fill hidden fields. Pretend success.
        if (input('company_site') !== '') {
            redirect('join/thanks');
        }

        if (mb_strlen($data['full_name']) < 2) $errors['full_name'] = 'Please enter your full name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if ($data['phone'] !== '' && !preg_match('/^[0-9+()\-\s.]{6,25}$/', $data['phone'])) $errors['phone'] = 'Enter a valid phone number.';
        if ($data['country'] === '') $errors['country'] = 'Tell us which country you are based in.';

        $hasChannel = false;
        foreach (array_keys(platform_options()) as $p) {
            if ($data[$p] !== '') $hasChannel = true;
        }
        if (!$hasChannel) $errors['channels'] = 'Add at least one social channel.';
        if (!$data['niches']) $errors['niches'] = 'Pick at least one niche.';

        $feeMin = to_money_or_null($data['fee_min']);
        $feeMax = to_money_or_null($data['fee_max']);
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

        if (!$errors) {
            $followers = 0;
            $row = [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'city' => $data['city'] ?: null,
                'country' => $data['country'],
                'gender' => $data['gender'] ?: null,
                'languages' => $data['languages'] ?: null,
                'primary_platform' => array_key_exists($data['primary_platform'], platform_options()) ? $data['primary_platform'] : null,
                'website' => $data['website'] ?: null,
                'engagement_rate' => $data['engagement_rate'] !== '' ? min(100, (float) str_replace(',', '.', preg_replace('/[^\d.,]/', '', $data['engagement_rate']))) : null,
                'niches' => implode(', ', $data['niches']),
                'content_types' => implode(', ', $data['content_types']) ?: null,
                'fee_min' => $feeMin,
                'fee_max' => $feeMax,
                'currency' => $data['currency'],
                'fee_notes' => $data['fee_notes'] ?: null,
                'bio' => $data['bio'] ?: null,
                'consent_feature' => $data['consent_feature'] ? 1 : 0,
                'ip_hash' => ip_hash(),
                'updated_at' => now(),
            ];
            foreach (array_keys(platform_options()) as $p) {
                $row[$p] = $data[$p] ?: null;
                $count = $data[$p] !== '' ? parse_count($data[$p . '_followers']) : null;
                $row[$p . '_followers'] = $count;
                $followers += (int) $count;
            }
            $row['total_followers'] = $followers;
            if (!$row['primary_platform']) {
                foreach (array_keys(platform_options()) as $p) {
                    if ($row[$p]) { $row['primary_platform'] = $p; break; }
                }
            }
            if ($photo) {
                $row['photo'] = $photo;
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
                . "Email: {$data['email']}\nFollowers: " . number_format($followers) . "\nFee: " . fee_range($feeMin, $feeMax, $data['currency']) . "\n"
                . ($campaign ? "Campaign: {$campaign['title']}\n" : '')
                . "\nView profile: " . abs_url('admin/influencers/' . $influencerId));

            $_SESSION['join_done'] = ['name' => $data['full_name'], 'campaign' => $campaign['title'] ?? null, 'updated' => (bool) $existing];
            redirect('join/thanks');
        }
    }

    render('join/form', compact('campaign', 'data', 'errors'), [
        'title' => $campaign ? 'Apply: ' . $campaign['title'] : 'Join the Creator Network',
        'description' => $campaign
            ? str_limit($campaign['description'] ?: 'Apply to this Peakilo creator campaign.', 160)
            : 'Create your free Peakilo creator profile. Add your channels, audience and rate range to get matched with paid brand campaigns.',
        'canonical' => abs_url('join'),
        'robots' => $campaign ? 'noindex, follow' : null,
        'body_class' => 'page-join',
        'scripts' => ['js/join.js'],
        'styles' => ['css/join.css'],
    ]);
}

function join_thanks()
{
    $done = $_SESSION['join_done'] ?? null;
    render('join/thanks', ['done' => $done], ['title' => 'Thank you', 'robots' => 'noindex', 'styles' => ['css/join.css']]);
}
