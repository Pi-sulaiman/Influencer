<?php
/**
 * Admin panel: dashboard, influencers, campaigns, blog, messages, settings.
 */

function admin_render($view, array $data = [], $title = 'Admin')
{
    require_admin();
    $data['pageTitle'] = $title;
    render('admin/' . $view, $data, ['title' => $title . ' | Admin', 'raw_title' => $title . ' | Peakilo Admin', 'robots' => 'noindex, nofollow'], 'admin');
}

function admin_counts()
{
    static $c = null;
    if ($c === null) {
        $c = [
            'new_influencers' => (int) val("SELECT COUNT(*) FROM influencers WHERE status = 'new'"),
            'unread' => (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
        ];
    }
    return $c;
}

/* ---------------- Auth ---------------- */

function admin_login()
{
    if (admin_user()) {
        redirect('admin');
    }
    $error = '';
    $email = '';
    if (is_post()) {
        $email = input('email');
        $result = attempt_login($email, (string) ($_POST['password'] ?? ''));
        if ($result === true) {
            $next = query('next');
            redirect($next && str_starts_with($next, '/admin') ? ltrim($next, '/') : 'admin');
        }
        $error = $result;
    }
    header('X-Robots-Tag: noindex, nofollow');
    render('admin/login', compact('error', 'email'), ['title' => 'Sign in', 'robots' => 'noindex'], null);
}

function admin_logout()
{
    logout();
    flash('success', 'You have been signed out.');
    redirect('admin/login');
}

/* ---------------- Dashboard ---------------- */

function series_by_day($rows, $col, $days)
{
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $out[date('Y-m-d', strtotime("-$i days"))] = 0;
    }
    foreach ($rows as $r) {
        $d = substr($r[$col], 0, 10);
        if (isset($out[$d])) $out[$d]++;
    }
    return $out;
}

function admin_dashboard()
{
    require_admin();
    $d7 = date('Y-m-d H:i:s', strtotime('-7 days'));
    $d14 = date('Y-m-d H:i:s', strtotime('-14 days'));
    $d30 = date('Y-m-d 00:00:00', strtotime('-29 days'));

    $stats = [
        'total' => (int) val('SELECT COUNT(*) FROM influencers'),
        'new7' => (int) val('SELECT COUNT(*) FROM influencers WHERE created_at >= ?', [$d7]),
        'prev7' => (int) val('SELECT COUNT(*) FROM influencers WHERE created_at >= ? AND created_at < ?', [$d14, $d7]),
        'pending' => (int) val("SELECT COUNT(*) FROM influencers WHERE status = 'new'"),
        'approved' => (int) val("SELECT COUNT(*) FROM influencers WHERE status = 'approved'"),
        'active_campaigns' => (int) val("SELECT COUNT(*) FROM campaigns WHERE status = 'active'"),
        'visits30' => (int) val('SELECT COUNT(*) FROM campaign_visits WHERE visited_at >= ?', [$d30]),
        'apps30' => (int) val('SELECT COUNT(*) FROM applications WHERE created_at >= ?', [$d30]),
        'reach' => (int) val('SELECT SUM(total_followers) FROM influencers'),
        'unread' => (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
        'posts' => (int) val("SELECT COUNT(*) FROM posts WHERE status = 'published'"),
        'subscribers' => (int) val('SELECT COUNT(*) FROM subscribers'),
    ];

    $regSeries = series_by_day(all('SELECT created_at FROM influencers WHERE created_at >= ?', [$d30]), 'created_at', 30);
    $visitSeries = series_by_day(all('SELECT visited_at FROM campaign_visits WHERE visited_at >= ?', [$d30]), 'visited_at', 30);

    $niches = [];
    foreach (all('SELECT niches FROM influencers ORDER BY id DESC LIMIT 5000') as $r) {
        foreach (csv_list($r['niches']) as $n) {
            $niches[$n] = ($niches[$n] ?? 0) + 1;
        }
    }
    arsort($niches);
    $niches = array_slice($niches, 0, 6, true);

    $platforms = [];
    foreach (all('SELECT primary_platform, COUNT(*) AS n FROM influencers GROUP BY primary_platform') as $r) {
        $platforms[$r['primary_platform'] ?: 'other'] = (int) $r['n'];
    }
    arsort($platforms);

    $currency = setting('default_currency', 'USD');
    $fees = one('SELECT AVG(fee_min) AS amin, AVG(fee_max) AS amax, COUNT(*) AS n FROM influencers WHERE currency = ? AND fee_min IS NOT NULL', [$currency]);

    $recent = all('SELECT id, full_name, photo, city, country, niches, total_followers, fee_min, fee_max, currency, status, created_at FROM influencers ORDER BY created_at DESC LIMIT 6');
    $campaigns = all("SELECT c.*, (SELECT COUNT(*) FROM applications a WHERE a.campaign_id = c.id) AS apps FROM campaigns c WHERE c.status = 'active' ORDER BY c.created_at DESC LIMIT 5");
    $messages = all('SELECT id, name, inquiry_type, message, is_read, created_at FROM messages ORDER BY created_at DESC LIMIT 4');

    admin_render('dashboard', compact('stats', 'regSeries', 'visitSeries', 'niches', 'platforms', 'fees', 'currency', 'recent', 'campaigns', 'messages'), 'Dashboard');
}

/* ---------------- Influencers ---------------- */

function influencer_filters()
{
    $f = [
        'q' => mb_substr(query('q'), 0, 80),
        'status' => query('status'),
        'campaign' => (int) query('campaign'),
        'niche' => query('niche'),
        'platform' => query('platform'),
        'country' => mb_substr(query('country'), 0, 80),
        'min_followers' => parse_count(query('min_followers')),
        'max_fee' => to_money_or_null(query('max_fee')),
        'featured' => query('featured'),
        'sort' => query('sort', 'newest'),
    ];
    $where = ['1 = 1'];
    $args = [];
    if ($f['q'] !== '') {
        $like = '%' . $f['q'] . '%';
        $where[] = '(i.full_name LIKE ? OR i.display_name LIKE ? OR i.email LIKE ? OR i.instagram LIKE ? OR i.youtube LIKE ? OR i.tiktok LIKE ? OR i.city LIKE ? OR i.phone LIKE ?)';
        array_push($args, $like, $like, $like, $like, $like, $like, $like, $like);
    }
    if (in_array($f['status'], ['new', 'approved', 'on_hold', 'rejected'], true)) {
        $where[] = 'i.status = ?';
        $args[] = $f['status'];
    }
    if ($f['campaign']) {
        $where[] = 'EXISTS (SELECT 1 FROM applications a WHERE a.influencer_id = i.id AND a.campaign_id = ?)';
        $args[] = $f['campaign'];
    }
    if ($f['niche'] !== '' && in_array($f['niche'], niche_options(), true)) {
        $where[] = 'i.niches LIKE ?';
        $args[] = '%' . $f['niche'] . '%';
    }
    if (array_key_exists($f['platform'], platform_options())) {
        $where[] = "(i.{$f['platform']} IS NOT NULL AND i.{$f['platform']} <> '')";
    }
    if ($f['country'] !== '') {
        $where[] = 'i.country LIKE ?';
        $args[] = '%' . $f['country'] . '%';
    }
    if ($f['min_followers']) {
        $where[] = 'i.total_followers >= ?';
        $args[] = $f['min_followers'];
    }
    if ($f['max_fee'] !== null) {
        $where[] = 'i.fee_min <= ?';
        $args[] = $f['max_fee'];
    }
    if ($f['featured'] === '1') {
        $where[] = 'i.featured = 1';
    }
    $sorts = [
        'newest' => 'i.created_at DESC',
        'oldest' => 'i.created_at ASC',
        'followers' => 'i.total_followers DESC',
        'views' => 'CASE WHEN i.avg_impressions IS NULL THEN 1 ELSE 0 END, i.avg_impressions DESC',
        'fee_low' => 'CASE WHEN i.fee_min IS NULL THEN 1 ELSE 0 END, i.fee_min ASC',
        'fee_high' => 'CASE WHEN i.fee_max IS NULL THEN 1 ELSE 0 END, i.fee_max DESC',
        'name' => 'i.full_name ASC',
        'updated' => 'i.updated_at DESC',
    ];
    $order = $sorts[$f['sort']] ?? $sorts['newest'];
    return [$f, ' WHERE ' . implode(' AND ', $where), $args, $order];
}

function admin_influencers()
{
    require_admin();
    list($f, $where, $args, $order) = influencer_filters();
    $view = query('view') === 'table' ? 'table' : (query('view') === 'grid' ? 'grid' : ($_COOKIE['pk_admin_view'] ?? 'grid'));
    if (query('view')) {
        setcookie('pk_admin_view', $view, time() + 86400 * 365, base_path() . '/admin');
    }
    $total = (int) val('SELECT COUNT(*) FROM influencers i' . $where, $args);
    $pg = paginate($total, 24, query('page', 1));
    $rows = all('SELECT i.*, (SELECT COUNT(*) FROM applications a WHERE a.influencer_id = i.id) AS app_count FROM influencers i' . $where . ' ORDER BY ' . $order . ' LIMIT ' . (int) $pg['per'] . ' OFFSET ' . (int) $pg['offset'], $args);
    $campaigns = all('SELECT id, title FROM campaigns ORDER BY created_at DESC');
    $statusCounts = [];
    foreach (all('SELECT status, COUNT(*) AS n FROM influencers GROUP BY status') as $r) {
        $statusCounts[$r['status']] = (int) $r['n'];
    }
    admin_render('influencers', compact('f', 'rows', 'pg', 'campaigns', 'view', 'statusCounts'), 'Influencers');
}

function admin_influencers_bulk()
{
    require_admin();
    $ids = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));
    $action = input('action');
    if (!$ids) {
        flash('error', 'Select at least one influencer first.');
        back('admin/influencers');
    }
    $in = in_placeholders($ids);
    $n = count($ids);
    switch ($action) {
        case 'approved':
        case 'on_hold':
        case 'rejected':
        case 'new':
            q("UPDATE influencers SET status = ?, updated_at = ? WHERE id IN ($in)", array_merge([$action, now()], $ids));
            flash('success', "$n influencer" . ($n > 1 ? 's' : '') . ' marked ' . strtolower(status_label($action)) . '.');
            break;
        case 'feature':
            q("UPDATE influencers SET featured = 1 WHERE consent_feature = 1 AND id IN ($in)", $ids);
            flash('success', 'Featured the selected influencers who allowed it.');
            break;
        case 'unfeature':
            q("UPDATE influencers SET featured = 0 WHERE id IN ($in)", $ids);
            flash('success', 'Removed from the website.');
            break;
        case 'delete':
            foreach (all("SELECT photo FROM influencers WHERE id IN ($in)", $ids) as $r) {
                delete_upload($r['photo']);
            }
            q("DELETE FROM applications WHERE influencer_id IN ($in)", $ids);
            q("DELETE FROM influencers WHERE id IN ($in)", $ids);
            flash('success', "$n influencer" . ($n > 1 ? 's' : '') . ' deleted.');
            break;
        default:
            flash('error', 'Choose an action.');
    }
    back('admin/influencers');
}

function csv_download($filename, array $header, array $rows)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel friendly UTF-8
    fputcsv($out, $header);
    foreach ($rows as $r) {
        // Neutralise spreadsheet formula injection.
        $r = array_map(function ($v) {
            $v = (string) $v;
            return ($v !== '' && strpos('=+-@', $v[0]) !== false) ? "'" . $v : $v;
        }, $r);
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

function influencer_csv_row(array $i)
{
    $rates = rate_card($i['rate_card'] ?? '');
    $row = [$i['id'], creator_code($i['id']), $i['full_name'], $i['display_name'] ?? '', $i['email'], $i['phone'], $i['city'], $i['country'], $i['gender'], $i['languages'], $i['primary_platform']];
    foreach (array_keys(platform_options()) as $p) {
        array_push($row, $i[$p], $i[$p . '_followers'], $i[$p . '_avg_views'] ?? '');
    }
    array_push($row, $i['total_followers'], $i['avg_impressions'] ?? '', $i['monthly_impressions'] ?? '', $i['engagement_rate'],
        $i['audience_country'] ?? '', $i['audience_age'] ?? '', $i['audience_gender'] ?? '', $i['niches'], $i['content_types'], $i['past_brands'] ?? '',
        $i['currency'], $i['fee_min'], $i['fee_max']);
    foreach (array_keys(rate_card_options()) as $k) {
        $row[] = $rates[$k] ?? '';
    }
    array_push($row, !empty($i['barter_ok']) ? 'Yes' : 'No', $i['fee_notes'], $i['website'], status_label($i['status']), $i['rating'],
        $i['photo'] ? abs_url($i['photo']) : '', $i['created_at']);
    return $row;
}

function influencer_csv_header()
{
    $h = ['ID', 'Creator code', 'Full name', 'Creator name', 'Email', 'Phone', 'City', 'Country', 'Gender', 'Languages', 'Main platform'];
    foreach (platform_options() as $p) {
        array_push($h, $p['label'], $p['label'] . ' followers', $p['label'] . ' avg. views');
    }
    array_push($h, 'Total followers', 'Avg. impressions per post (all channels)', 'Impressions last 30 days', 'Engagement %',
        'Audience country', 'Audience age', 'Audience gender', 'Niches', 'Content types', 'Past brands', 'Currency', 'Fee min', 'Fee max');
    foreach (rate_card_options() as $r) {
        $h[] = 'Rate: ' . $r[0];
    }
    array_push($h, 'Product-only OK', 'Fee notes', 'Website', 'Status', 'Rating', 'Photo URL', 'Registered');
    return $h;
}

function admin_influencers_export()
{
    require_admin();
    list($f, $where, $args, $order) = influencer_filters();
    $rows = array_map('influencer_csv_row', all('SELECT i.* FROM influencers i' . $where . ' ORDER BY ' . $order, $args));
    csv_download('peakilo-influencers-' . date('Y-m-d') . '.csv', influencer_csv_header(), $rows);
}

function admin_influencer_view($params)
{
    require_admin();
    $id = (int) $params['id'];
    $inf = one('SELECT * FROM influencers WHERE id = ?', [$id]);
    if (!$inf) {
        not_found();
    }
    if (is_post()) {
        $status = input('status');
        $data = [
            'status' => in_array($status, ['new', 'approved', 'on_hold', 'rejected'], true) ? $status : $inf['status'],
            'rating' => ($r = (int) input('rating')) >= 1 && $r <= 5 ? $r : null,
            'admin_notes' => mb_substr((string) input('admin_notes'), 0, 5000),
            'featured' => input('featured') && $inf['consent_feature'] ? 1 : 0,
            'updated_at' => now(),
        ];
        update('influencers', $data, 'id = ?', [$id]);
        flash('success', 'Profile updated.');
        redirect('admin/influencers/' . $id);
    }
    $apps = all('SELECT a.*, c.title, c.code, c.brand_name, c.status AS campaign_status FROM applications a JOIN campaigns c ON c.id = a.campaign_id WHERE a.influencer_id = ? ORDER BY a.created_at DESC', [$id]);
    $prevId = val('SELECT id FROM influencers WHERE id < ? ORDER BY id DESC LIMIT 1', [$id]);
    $nextId = val('SELECT id FROM influencers WHERE id > ? ORDER BY id ASC LIMIT 1', [$id]);
    admin_render('influencer', compact('inf', 'apps', 'prevId', 'nextId'), $inf['full_name']);
}

function admin_influencer_delete($params)
{
    require_admin();
    $inf = one('SELECT * FROM influencers WHERE id = ?', [(int) $params['id']]);
    if ($inf) {
        delete_upload($inf['photo']);
        q('DELETE FROM applications WHERE influencer_id = ?', [$inf['id']]);
        q('DELETE FROM influencers WHERE id = ?', [$inf['id']]);
        flash('success', $inf['full_name'] . ' was deleted.');
    }
    redirect('admin/influencers');
}

/* ---------------- Campaigns ---------------- */

function admin_campaigns()
{
    require_admin();
    $status = query('status');
    $where = in_array($status, ['active', 'paused', 'closed'], true) ? ' WHERE c.status = ?' : '';
    $args = $where ? [$status] : [];
    $rows = all("SELECT c.*,
        (SELECT COUNT(*) FROM applications a WHERE a.campaign_id = c.id) AS apps,
        (SELECT COUNT(*) FROM applications a WHERE a.campaign_id = c.id AND a.status IN ('shortlisted','selected')) AS shortlisted
        FROM campaigns c$where ORDER BY CASE c.status WHEN 'active' THEN 0 WHEN 'paused' THEN 1 ELSE 2 END, c.created_at DESC", $args);
    admin_render('campaigns', compact('rows', 'status'), 'Campaigns');
}

function unique_code($base, $ignoreId = 0)
{
    $code = slugify($base);
    $code = substr($code, 0, 36) ?: 'campaign';
    $try = $code;
    $i = 2;
    while (val('SELECT COUNT(*) FROM campaigns WHERE code = ? AND id <> ?', [$try, (int) $ignoreId])) {
        $try = $code . '-' . $i++;
    }
    return $try;
}

function admin_campaign_form($params)
{
    require_admin();
    $id = (int) ($params['id'] ?? 0);
    $c = $id ? one('SELECT * FROM campaigns WHERE id = ?', [$id]) : null;
    if ($id && !$c) {
        not_found();
    }
    $errors = [];
    $data = $c ?: [
        'title' => '', 'code' => '', 'brand_name' => '', 'description' => '', 'deliverables' => '', 'niches' => '',
        'platforms' => 'instagram', 'budget_min' => '', 'budget_max' => '', 'currency' => setting('default_currency', 'USD'),
        'location' => '', 'deadline' => '', 'mode' => 'form', 'redirect_url' => '', 'status' => 'active', 'cover_image' => '',
    ];

    if (is_post()) {
        $data['title'] = mb_substr(input('title'), 0, 190);
        $data['brand_name'] = mb_substr(input('brand_name'), 0, 190);
        $data['description'] = mb_substr((string) input('description'), 0, 5000);
        $data['deliverables'] = mb_substr((string) input('deliverables'), 0, 3000);
        $data['niches'] = implode(', ', array_intersect(input_array('niches'), niche_options()));
        $data['platforms'] = implode(',', array_intersect(input_array('platforms'), array_keys(platform_options())));
        $data['budget_min'] = to_money_or_null(input('budget_min'));
        $data['budget_max'] = to_money_or_null(input('budget_max'));
        $data['currency'] = array_key_exists(input('currency'), currencies()) ? input('currency') : 'USD';
        $data['location'] = mb_substr(input('location'), 0, 160);
        $data['deadline'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', input('deadline')) ? input('deadline') : null;
        $data['mode'] = in_array(input('mode'), ['form', 'landing', 'url'], true) ? input('mode') : 'form';
        $data['redirect_url'] = mb_substr(input('redirect_url'), 0, 500);
        $data['status'] = in_array(input('status'), ['active', 'paused', 'closed'], true) ? input('status') : 'active';
        $codeIn = input('code');

        if ($data['title'] === '') $errors['title'] = 'Give the campaign a name.';
        if ($data['mode'] === 'url' && !preg_match('#^(https?://|/)#i', $data['redirect_url'])) $errors['redirect_url'] = 'Enter a full URL starting with https:// or a path starting with /.';
        if ($data['budget_min'] !== null && $data['budget_max'] !== null && $data['budget_max'] < $data['budget_min']) $errors['budget_max'] = 'Maximum should be higher than minimum.';
        if ($codeIn !== '' && !preg_match('/^[a-z0-9-]{2,40}$/', $codeIn)) $errors['code'] = 'Use 2-40 lowercase letters, numbers or dashes.';
        if ($codeIn !== '' && !isset($errors['code']) && val('SELECT COUNT(*) FROM campaigns WHERE code = ? AND id <> ?', [$codeIn, $id])) $errors['code'] = 'That link is already used by another campaign.';

        if (!$errors && !empty($_FILES['cover']['name'])) {
            try {
                $new = save_image_upload($_FILES['cover'], 'campaigns', 1600);
                if (!empty($data['cover_image'])) delete_upload($data['cover_image']);
                $data['cover_image'] = $new;
            } catch (RuntimeException $e) {
                $errors['cover'] = $e->getMessage();
            }
        }
        if (input('remove_cover') && !empty($data['cover_image'])) {
            delete_upload($data['cover_image']);
            $data['cover_image'] = null;
        }

        if (!$errors) {
            $data['code'] = $codeIn !== '' ? $codeIn : ($c['code'] ?? unique_code($data['title']));
            $row = array_intersect_key($data, array_flip(['title', 'code', 'brand_name', 'description', 'deliverables', 'niches', 'platforms', 'budget_min', 'budget_max', 'currency', 'location', 'deadline', 'mode', 'redirect_url', 'status', 'cover_image']));
            $row['updated_at'] = now();
            if ($c) {
                update('campaigns', $row, 'id = ?', [$id]);
                flash('success', 'Campaign saved.');
            } else {
                $row['created_at'] = now();
                $row['visits'] = 0;
                $id = insert('campaigns', $row);
                flash('success', 'Campaign created. Share the link below to start collecting applications.');
            }
            redirect('admin/campaigns/' . $id);
        }
    }
    admin_render('campaign_form', ['c' => $c, 'data' => $data, 'errors' => $errors], $c ? 'Edit campaign' : 'New campaign');
}

function admin_campaign_view($params)
{
    require_admin();
    $c = one('SELECT * FROM campaigns WHERE id = ?', [(int) $params['id']]);
    if (!$c) {
        not_found();
    }
    $status = query('status');
    $where = 'a.campaign_id = ?';
    $args = [$c['id']];
    if (in_array($status, ['applied', 'shortlisted', 'selected', 'rejected'], true)) {
        $where .= ' AND a.status = ?';
        $args[] = $status;
    }
    $apps = all("SELECT a.id AS app_id, a.status AS app_status, a.pitch, a.created_at AS applied_at, i.* FROM applications a JOIN influencers i ON i.id = a.influencer_id WHERE $where ORDER BY a.created_at DESC", $args);
    $counts = ['applied' => 0, 'shortlisted' => 0, 'selected' => 0, 'rejected' => 0];
    foreach (all('SELECT status, COUNT(*) AS n FROM applications WHERE campaign_id = ? GROUP BY status', [$c['id']]) as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    $totalApps = array_sum($counts);
    $visitSeries = series_by_day(all('SELECT visited_at FROM campaign_visits WHERE campaign_id = ? AND visited_at >= ?', [$c['id'], date('Y-m-d 00:00:00', strtotime('-13 days'))]), 'visited_at', 14);
    $appSeries = series_by_day(all('SELECT created_at FROM applications WHERE campaign_id = ? AND created_at >= ?', [$c['id'], date('Y-m-d 00:00:00', strtotime('-13 days'))]), 'created_at', 14);
    admin_render('campaign_view', compact('c', 'apps', 'counts', 'totalApps', 'status', 'visitSeries', 'appSeries'), $c['title']);
}

function admin_campaign_export($params)
{
    require_admin();
    $c = one('SELECT * FROM campaigns WHERE id = ?', [(int) $params['id']]);
    if (!$c) {
        not_found();
    }
    $rows = [];
    foreach (all('SELECT a.status AS app_status, a.pitch, a.created_at AS applied_at, i.* FROM applications a JOIN influencers i ON i.id = a.influencer_id WHERE a.campaign_id = ? ORDER BY a.created_at DESC', [$c['id']]) as $r) {
        $rows[] = array_merge([status_label($r['app_status']), $r['applied_at'], $r['pitch']], influencer_csv_row($r));
    }
    csv_download('campaign-' . $c['code'] . '-' . date('Y-m-d') . '.csv', array_merge(['Application status', 'Applied at', 'Pitch'], influencer_csv_header()), $rows);
}

function admin_campaign_delete($params)
{
    require_admin();
    $c = one('SELECT * FROM campaigns WHERE id = ?', [(int) $params['id']]);
    if ($c) {
        delete_upload($c['cover_image']);
        q('DELETE FROM applications WHERE campaign_id = ?', [$c['id']]);
        q('DELETE FROM campaign_visits WHERE campaign_id = ?', [$c['id']]);
        q('DELETE FROM campaigns WHERE id = ?', [$c['id']]);
        flash('success', 'Campaign deleted. Influencer profiles were kept.');
    }
    redirect('admin/campaigns');
}

function admin_application_update($params)
{
    require_admin();
    $app = one('SELECT * FROM applications WHERE id = ?', [(int) $params['id']]);
    $status = input('status');
    if ($app && in_array($status, ['applied', 'shortlisted', 'selected', 'rejected'], true)) {
        update('applications', ['status' => $status, 'updated_at' => now()], 'id = ?', [$app['id']]);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_out(['ok' => true, 'status' => $status, 'label' => status_label($status)]);
        }
        flash('success', 'Application marked ' . strtolower(status_label($status)) . '.');
    }
    back('admin/campaigns');
}

/* ---------------- Blog ---------------- */

function admin_posts()
{
    require_admin();
    $status = query('status');
    $search = mb_substr(query('q'), 0, 80);
    $where = ['1 = 1'];
    $args = [];
    if (in_array($status, ['draft', 'published'], true)) {
        $where[] = 'p.status = ?';
        $args[] = $status;
    }
    if ($search !== '') {
        $where[] = 'p.title LIKE ?';
        $args[] = '%' . $search . '%';
    }
    $total = (int) val('SELECT COUNT(*) FROM posts p WHERE ' . implode(' AND ', $where), $args);
    $pg = paginate($total, 20, query('page', 1));
    $rows = all('SELECT p.id, p.title, p.slug, p.status, p.views, p.featured, p.published_at, p.updated_at, p.cover_image, p.meta_description, p.focus_keyword, c.name AS category_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.updated_at DESC LIMIT ' . (int) $pg['per'] . ' OFFSET ' . (int) $pg['offset'], $args);
    admin_render('posts', compact('rows', 'pg', 'status', 'search'), 'Blog posts');
}

function unique_slug($base, $ignoreId = 0)
{
    $slug = slugify($base);
    $try = $slug;
    $i = 2;
    while (val('SELECT COUNT(*) FROM posts WHERE slug = ? AND id <> ?', [$try, (int) $ignoreId])) {
        $try = $slug . '-' . $i++;
    }
    return $try;
}

function admin_post_form($params)
{
    require_admin();
    $id = (int) ($params['id'] ?? 0);
    $post = $id ? one('SELECT * FROM posts WHERE id = ?', [$id]) : null;
    if ($id && !$post) {
        not_found();
    }
    $errors = [];
    $data = $post ?: [
        'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'cover_image' => '', 'cover_alt' => '', 'category_id' => '',
        'author_name' => admin_user()['name'], 'meta_title' => '', 'meta_description' => '', 'focus_keyword' => '',
        'status' => 'draft', 'featured' => 0, 'published_at' => '',
    ];

    if (is_post()) {
        $data['title'] = mb_substr(input('title'), 0, 255);
        $data['slug'] = slugify(input('slug') ?: $data['title']);
        $data['excerpt'] = mb_substr((string) input('excerpt'), 0, 600);
        $data['content'] = sanitize_html((string) ($_POST['content'] ?? ''));
        $data['cover_alt'] = mb_substr(input('cover_alt'), 0, 255);
        $data['category_id'] = (int) input('category_id') ?: null;
        $data['author_name'] = mb_substr(input('author_name'), 0, 120);
        $data['meta_title'] = mb_substr(input('meta_title'), 0, 255);
        $data['meta_description'] = mb_substr(input('meta_description'), 0, 320);
        $data['focus_keyword'] = mb_substr(input('focus_keyword'), 0, 120);
        $data['status'] = input('status') === 'published' ? 'published' : 'draft';
        $data['featured'] = input('featured') ? 1 : 0;
        $pub = input('published_at');
        $data['published_at'] = $pub ? date('Y-m-d H:i:s', strtotime($pub)) : ($data['status'] === 'published' ? ($post['published_at'] ?? now()) : null);
        if ($data['status'] === 'published' && !$data['published_at']) $data['published_at'] = now();

        if ($data['title'] === '') $errors['title'] = 'Add a title.';
        if (trim(strip_tags($data['content'])) === '' && $data['status'] === 'published') $errors['content'] = 'A published post needs some content.';
        if (val('SELECT COUNT(*) FROM posts WHERE slug = ? AND id <> ?', [$data['slug'], $id])) {
            $data['slug'] = unique_slug($data['slug'], $id);
        }

        if (!$errors && !empty($_FILES['cover']['name'])) {
            try {
                $new = save_image_upload($_FILES['cover'], 'blog', 1800);
                if (!empty($data['cover_image'])) delete_upload($data['cover_image']);
                $data['cover_image'] = $new;
            } catch (RuntimeException $e) {
                $errors['cover'] = $e->getMessage();
            }
        }
        if (input('remove_cover')) {
            delete_upload($data['cover_image']);
            $data['cover_image'] = null;
        }

        if (!$errors) {
            $row = array_intersect_key($data, array_flip(['title', 'slug', 'excerpt', 'content', 'cover_image', 'cover_alt', 'category_id', 'author_name', 'meta_title', 'meta_description', 'focus_keyword', 'status', 'featured', 'published_at']));
            $row['updated_at'] = now();
            if ($row['featured']) {
                q('UPDATE posts SET featured = 0 WHERE id <> ?', [$id]);
            }
            if ($post) {
                update('posts', $row, 'id = ?', [$id]);
            } else {
                $row['created_at'] = now();
                $row['views'] = 0;
                $id = insert('posts', $row);
            }
            flash('success', $data['status'] === 'published' ? 'Post published.' : 'Draft saved.');
            redirect('admin/posts/' . $id . '/edit');
        }
    }
    $categories = all('SELECT id, name FROM categories ORDER BY name');
    $extraScripts = ['js/editor.js'];
    admin_render('post_form', compact('post', 'data', 'errors', 'categories', 'extraScripts'), $post ? 'Edit post' : 'New post');
}

function admin_post_delete($params)
{
    require_admin();
    $p = one('SELECT * FROM posts WHERE id = ?', [(int) $params['id']]);
    if ($p) {
        delete_upload($p['cover_image']);
        q('DELETE FROM posts WHERE id = ?', [$p['id']]);
        flash('success', 'Post deleted.');
    }
    redirect('admin/posts');
}

function admin_categories()
{
    require_admin();
    if (is_post()) {
        $action = input('action');
        if ($action === 'delete') {
            $cid = (int) input('id');
            q('UPDATE posts SET category_id = NULL WHERE category_id = ?', [$cid]);
            q('DELETE FROM categories WHERE id = ?', [$cid]);
            flash('success', 'Category deleted. Its posts are now uncategorised.');
        } else {
            $name = mb_substr(input('name'), 0, 120);
            $slug = slugify(input('slug') ?: $name);
            $desc = mb_substr((string) input('description'), 0, 500);
            $cid = (int) input('id');
            if ($name === '') {
                flash('error', 'Category name is required.');
            } elseif (val('SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?', [$slug, $cid])) {
                flash('error', 'Another category already uses that slug.');
            } elseif ($cid) {
                update('categories', ['name' => $name, 'slug' => $slug, 'description' => $desc], 'id = ?', [$cid]);
                flash('success', 'Category updated.');
            } else {
                insert('categories', ['name' => $name, 'slug' => $slug, 'description' => $desc]);
                flash('success', 'Category added.');
            }
        }
        redirect('admin/categories');
    }
    $rows = all('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id) AS post_count FROM categories c ORDER BY c.name');
    admin_render('categories', compact('rows'), 'Categories');
}

function admin_upload()
{
    require_admin();
    if (empty($_FILES['file'])) {
        json_out(['ok' => false, 'error' => 'No file received.'], 422);
    }
    try {
        $path = save_image_upload($_FILES['file'], 'blog', 1800);
        json_out(['ok' => true, 'url' => url($path)]);
    } catch (RuntimeException $e) {
        json_out(['ok' => false, 'error' => $e->getMessage()], 422);
    }
}

/* ---------------- Messages + subscribers ---------------- */

function admin_messages()
{
    require_admin();
    $type = query('type');
    $where = in_array($type, ['brand', 'creator', 'other'], true) ? ' WHERE inquiry_type = ?' : '';
    $args = $where ? [$type] : [];
    $total = (int) val('SELECT COUNT(*) FROM messages' . $where, $args);
    $pg = paginate($total, 25, query('page', 1));
    $rows = all('SELECT * FROM messages' . $where . ' ORDER BY created_at DESC LIMIT ' . (int) $pg['per'] . ' OFFSET ' . (int) $pg['offset'], $args);
    admin_render('messages', compact('rows', 'pg', 'type'), 'Messages');
}

function admin_message_view($params)
{
    require_admin();
    $m = one('SELECT * FROM messages WHERE id = ?', [(int) $params['id']]);
    if (!$m) {
        not_found();
    }
    if (!$m['is_read']) {
        update('messages', ['is_read' => 1], 'id = ?', [$m['id']]);
    }
    // Creators the brand shortlisted on /talent, with their private fees so
    // the team can build a quote. Totals are grouped per currency.
    $requested = [];
    $costs = [];
    $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($m['creator_ids'] ?? '')))));
    if ($ids) {
        $rows = [];
        foreach (all('SELECT * FROM influencers WHERE id IN (' . in_placeholders($ids) . ')', $ids) as $r) {
            $rows[(int) $r['id']] = $r;
        }
        foreach ($ids as $id) {
            if (!isset($rows[$id])) continue;
            $r = $rows[$id];
            $requested[] = $r;
            $cur = $r['currency'] ?: setting('default_currency', 'USD');
            $costs[$cur] = $costs[$cur] ?? ['min' => 0, 'max' => 0, 'n' => 0];
            $costs[$cur]['min'] += (float) ($r['fee_min'] ?? $r['fee_max'] ?? 0);
            $costs[$cur]['max'] += (float) ($r['fee_max'] ?? $r['fee_min'] ?? 0);
            $costs[$cur]['n']++;
        }
    }
    admin_render('message', compact('m', 'requested', 'costs'), 'Message from ' . $m['name']);
}

function admin_message_delete($params)
{
    require_admin();
    q('DELETE FROM messages WHERE id = ?', [(int) $params['id']]);
    flash('success', 'Message deleted.');
    redirect('admin/messages');
}

function admin_subscribers()
{
    require_admin();
    $total = (int) val('SELECT COUNT(*) FROM subscribers');
    $pg = paginate($total, 50, query('page', 1));
    $rows = all('SELECT * FROM subscribers ORDER BY created_at DESC LIMIT ' . (int) $pg['per'] . ' OFFSET ' . (int) $pg['offset']);
    admin_render('subscribers', compact('rows', 'pg'), 'Subscribers');
}

function admin_subscribers_export()
{
    require_admin();
    $rows = array_map(function ($r) { return [$r['email'], $r['source'], $r['created_at']]; }, all('SELECT * FROM subscribers ORDER BY created_at DESC'));
    csv_download('peakilo-subscribers-' . date('Y-m-d') . '.csv', ['Email', 'Source', 'Subscribed'], $rows);
}

/* ---------------- Settings ---------------- */

function settings_fields()
{
    return [
        'General' => [
            'site_name' => ['Site name', 'text'],
            'tagline' => ['Tagline (used in the homepage title)', 'text'],
            'meta_description' => ['Default meta description', 'textarea'],
        ],
        'Theme and currency' => [
            'theme_mode' => ['Website theme', 'theme'],
            'default_currency' => ['Main currency', 'currency'],
            'currency_auto' => ['Show visitors abroad prices in their own currency', 'checkbox'],
            'fx_rates' => ['Exchange rates (units per 1 USD, one per line, e.g. INR=84)', 'textarea'],
        ],
        'Contact details' => [
            'contact_email' => ['Contact email (also receives notifications)', 'email'],
            'contact_phone' => ['Phone', 'text'],
            'whatsapp' => ['WhatsApp number', 'text'],
            'address' => ['Address', 'text'],
        ],
        'Social links' => [
            'instagram_url' => ['Instagram URL', 'url'],
            'linkedin_url' => ['LinkedIn URL', 'url'],
            'youtube_url' => ['YouTube URL', 'url'],
            'tiktok_url' => ['TikTok URL', 'url'],
            'x_url' => ['X URL', 'url'],
        ],
        'SEO and analytics' => [
            'ga_id' => ['Google Analytics 4 measurement ID (G-XXXXXXX)', 'text'],
            'google_site_verification' => ['Google Search Console verification code', 'text'],
        ],
    ];
}

function admin_settings()
{
    require_admin();
    $me = admin_user();
    if (is_post()) {
        $section = input('section');
        if ($section === 'site') {
            foreach (settings_fields() as $group) {
                foreach ($group as $key => $def) {
                    $v = mb_substr((string) input($key), 0, 1000);
                    if ($def[1] === 'number') $v = (string) (int) preg_replace('/\D/', '', $v);
                    if ($def[1] === 'currency' && !array_key_exists($v, currencies())) $v = 'INR';
                    if ($def[1] === 'theme' && !in_array($v, ['auto', 'light', 'dark'], true)) $v = 'auto';
                    if ($def[1] === 'checkbox') $v = $v ? '1' : '0';
                    if ($key === 'fx_rates') $v = mb_substr((string) ($_POST['fx_rates'] ?? ''), 0, 4000);
                    save_setting($key, $v);
                }
            }
            flash('success', 'Settings saved.');
        } elseif ($section === 'account') {
            $full = one('SELECT * FROM admins WHERE id = ?', [$me['id']]);
            $name = mb_substr(input('name'), 0, 120);
            $email = strtolower(input('email'));
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            if (!password_verify($current, $full['password_hash'])) {
                flash('error', 'Your current password is not correct.');
            } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Enter a name and a valid email.');
            } elseif (val('SELECT COUNT(*) FROM admins WHERE email = ? AND id <> ?', [$email, $me['id']])) {
                flash('error', 'Another admin already uses that email.');
            } elseif ($new !== '' && strlen($new) < 8) {
                flash('error', 'New password must be at least 8 characters.');
            } else {
                $row = ['name' => $name, 'email' => $email];
                if ($new !== '') $row['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                update('admins', $row, 'id = ?', [$me['id']]);
                flash('success', 'Account updated.');
            }
        } elseif ($section === 'add_admin') {
            $name = mb_substr(input('name'), 0, 120);
            $email = strtolower(input('email'));
            $pass = (string) ($_POST['password'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
                flash('error', 'Enter a name, a valid email and a password of at least 8 characters.');
            } elseif (val('SELECT COUNT(*) FROM admins WHERE email = ?', [$email])) {
                flash('error', 'An admin with that email already exists.');
            } else {
                insert('admins', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'created_at' => now()]);
                flash('success', $name . ' can now sign in to the admin.');
            }
        } elseif ($section === 'remove_admin') {
            $aid = (int) input('id');
            if ($aid === (int) $me['id']) {
                flash('error', 'You cannot remove your own account.');
            } else {
                q('DELETE FROM admins WHERE id = ?', [$aid]);
                flash('success', 'Admin removed.');
            }
        } elseif ($section === 'delete_subscriber') {
            q('DELETE FROM subscribers WHERE id = ?', [(int) input('id')]);
            flash('success', 'Subscriber removed.');
            redirect('admin/subscribers');
        }
        redirect('admin/settings' . (input('tab') ? '#' . preg_replace('/[^a-z-]/', '', input('tab')) : ''));
    }
    settings_all(true);
    $admins = all('SELECT id, name, email, last_login_at, created_at FROM admins ORDER BY created_at');
    admin_render('settings', ['me' => $me, 'admins' => $admins, 'fields' => settings_fields()], 'Settings');
}

/* ---------------- Site content: numbers, testimonials, page photos ---------------- */

function admin_content()
{
    require_admin();
    if (is_post()) {
        $action = input('action');

        if ($action === 'stats') {
            foreach (['stat_creators', 'stat_campaigns', 'stat_reach', 'stat_retention'] as $k) {
                $v = trim((string) input($k));
                $v = $v === '' ? '' : (string) round((float) preg_replace('/[^\d.]/', '', $v), 1);
                if ($k === 'stat_retention' && $v !== '' && (float) $v > 100) $v = '100';
                save_setting($k, $v);
            }
            save_setting('stats_mode', input('stats_mode') === 'live' ? 'live' : 'custom');
            flash('success', 'Homepage numbers saved.');
            redirect('admin/content#numbers');
        }

        if ($action === 'testimonial') {
            $tid = (int) input('id');
            $existing = $tid ? one('SELECT * FROM testimonials WHERE id = ?', [$tid]) : null;
            $row = [
                'name' => mb_substr(input('name'), 0, 160),
                'role' => mb_substr(input('role'), 0, 190) ?: null,
                'quote' => mb_substr((string) input('quote'), 0, 400),
                'sort_order' => (int) input('sort_order'),
                'is_visible' => input('is_visible') ? 1 : 0,
            ];
            if ($row['name'] === '' || mb_strlen($row['quote']) < 10) {
                flash('error', 'Add a name and a quote of at least 10 characters.');
                redirect('admin/content#testimonials');
            }
            if (!empty($_FILES['photo']['name'])) {
                try {
                    $row['photo'] = save_image_upload($_FILES['photo'], 'testimonials', 300);
                    if ($existing && $existing['photo']) delete_upload($existing['photo']);
                } catch (RuntimeException $e) {
                    flash('error', $e->getMessage());
                    redirect('admin/content#testimonials');
                }
            }
            if (input('remove_photo') && $existing && $existing['photo']) {
                delete_upload($existing['photo']);
                $row['photo'] = null;
            }
            if ($existing) {
                update('testimonials', $row, 'id = ?', [$tid]);
                flash('success', 'Testimonial updated.');
            } else {
                $row['created_at'] = now();
                insert('testimonials', $row);
                flash('success', 'Testimonial added. It shows on the homepage while it is marked visible.');
            }
            redirect('admin/content#testimonials');
        }

        if ($action === 'delete_testimonial') {
            $t = one('SELECT * FROM testimonials WHERE id = ?', [(int) input('id')]);
            if ($t) {
                delete_upload($t['photo']);
                q('DELETE FROM testimonials WHERE id = ?', [$t['id']]);
                flash('success', 'Testimonial deleted.');
            }
            redirect('admin/content#testimonials');
        }

        if ($action === 'image' || $action === 'reset_image') {
            $slot = input('slot');
            $slots = site_image_slots();
            if (!isset($slots[$slot])) {
                flash('error', 'Unknown photo slot.');
                redirect('admin/content#photos');
            }
            $old = setting('img_' . $slot);
            if ($action === 'reset_image') {
                delete_upload($old);
                save_setting('img_' . $slot, '');
                flash('success', 'Photo reset to the default.');
            } else {
                try {
                    $path = save_image_upload($_FILES['image'] ?? [], 'site', 1800);
                    delete_upload($old);
                    save_setting('img_' . $slot, $path);
                    flash('success', $slots[$slot][0] . ' photo updated.');
                } catch (RuntimeException $e) {
                    flash('error', $e->getMessage());
                }
            }
            redirect('admin/content#photos');
        }
        redirect('admin/content');
    }

    settings_all(true);
    $testimonials = all('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC');
    $editing = query('edit') ? one('SELECT * FROM testimonials WHERE id = ?', [(int) query('edit')]) : null;
    admin_render('content', [
        'live' => live_stats(),
        'testimonials' => $testimonials,
        'editing' => $editing,
        'slots' => site_image_slots(),
    ], 'Site content');
}
