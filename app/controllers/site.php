<?php
/**
 * Public marketing pages.
 */

function home_faqs()
{
    return [
        ['q' => 'What does an influencer marketing agency actually do?', 'a' => 'We plan the campaign, find and vet creators who fit your audience, negotiate rates, manage briefs and approvals, make sure content goes live on time and report on the results. You get one point of contact instead of dozens of creator conversations.'],
        ['q' => 'How much budget do I need to start?', 'a' => 'Campaigns with micro creators can start from a few thousand dollars. We size the plan around your goal and tell you up front what reach and content you can expect for the budget.'],
        ['q' => 'Which platforms do you run campaigns on?', 'a' => 'Instagram, YouTube, TikTok, Facebook, X, Snapchat and Pinterest, plus podcasts and UGC that you can run as ads on any channel.'],
        ['q' => 'How do I book a creator, and how does payment work?', 'a' => 'Shortlist creators on our Find talent page, or send us a brief and we shortlist for you. We confirm availability and send a quote within one business day. Nothing is paid online: once you approve the plan, we agree payment terms with you directly and handle every creator payment.'],
        ['q' => 'How do you choose creators?', 'a' => 'Every creator in our network is reviewed for audience quality, engagement, content standards and brand safety. For each campaign we shortlist creators whose audience matches your target customer and share profiles before anyone is booked.'],
        ['q' => 'I am a creator. Does it cost anything to join?', 'a' => 'No. Joining the Peakilo creator network is free. Create your profile with your channels and rate range and we will reach out when a paid campaign fits your content.'],
        ['q' => 'How do you measure results?', 'a' => 'We agree on the metrics before launch, such as reach, views, engagement, clicks or sales from tracked links and codes. You get a clear report at the end of every campaign with what worked and what we would change.'],
    ];
}

function site_home()
{
    // Everything on the landing page comes from the database: real blog posts,
    // real approved creators (only those who allowed featuring) and real counts.
    $posts = all("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 'published' AND p.published_at <= ? ORDER BY p.featured DESC, p.published_at DESC LIMIT 3", [now()]);
    $creators = public_creators(24);
    // In "custom numbers" mode the display totals are shown and per-niche counts are hidden.
    $liveCounts = stats_mode() === 'live';
    $counts = $liveCounts ? niche_counts() : [];
    $approved = display_creator_total();
    $reach = (int) val("SELECT SUM(total_followers) FROM influencers WHERE status = 'approved'");

    $faqs = home_faqs();
    render('site/home', [
        'posts' => $posts,
        'creators' => $creators,
        'counts' => $counts,
        'approved' => $approved,
        'liveCounts' => $liveCounts,
        'networkReach' => $reach,
        'faqs' => $faqs,
        'testimonials' => site_testimonials(), // managed in Admin > Site content; hidden when empty
        'stats' => site_stats(),
    ], [
        'raw_title' => 'Peakilo | Influencer Marketing Agency for Brands and Creators',
        'description' => setting('meta_description'),
        'canonical' => abs_url(''),
        'schema' => [schema_faq($faqs), schema_service('Influencer marketing', 'End to end influencer marketing campaigns: strategy, creator sourcing, content production, paid amplification and reporting.')],
        'body_class' => 'page-home',
        'scripts' => ['js/home.js'],
    ]);
}

function site_about()
{
    render('site/about', [], [
        'title' => 'About Peakilo',
        'description' => 'Peakilo is an influencer marketing agency built on fair creator partnerships and measurable brand results. Meet the team and the way we work.',
        'schema' => [schema_breadcrumbs(['Home' => '', 'About' => 'about'])],
    ]);
}

function site_services()
{
    render('site/services', [], [
        'title' => 'Influencer Marketing Services',
        'description' => 'Influencer campaigns, creator sourcing, UGC production, talent management, paid amplification and reporting. See every Peakilo service.',
        'schema' => [
            schema_breadcrumbs(['Home' => '', 'Services' => 'services']),
            schema_service('Influencer campaign management', 'Strategy, creator sourcing, briefing, approvals and launch for influencer campaigns.'),
            schema_service('UGC production', 'Creator-made video and photo content for ads, product pages and social channels.'),
        ],
    ]);
}

function site_brands()
{
    render('site/brands', ['faqs' => array_slice(home_faqs(), 0, 4)], [
        'title' => 'Influencer Marketing for Brands',
        'description' => 'Launch creator campaigns that reach the right audience. Peakilo plans, sources, manages and reports on influencer campaigns for brands.',
        'schema' => [schema_breadcrumbs(['Home' => '', 'For brands' => 'brands'])],
    ]);
}

function site_creators()
{
    render('site/creators', [], [
        'title' => 'Join the Peakilo Creator Network',
        'description' => 'Get matched with paid brand campaigns that fit your content. Free to join, clear briefs, fair rates and on-time payments.',
        'schema' => [schema_breadcrumbs(['Home' => '', 'For creators' => 'creators'])],
    ]);
}

function site_privacy()
{
    render('site/legal', ['doc' => 'privacy'], [
        'title' => 'Privacy Policy',
        'description' => 'How Peakilo collects, uses and protects information from brands, creators and website visitors.',
    ]);
}

function site_terms()
{
    render('site/legal', ['doc' => 'terms'], [
        'title' => 'Terms of Use',
        'description' => 'The terms that apply when you use the Peakilo website and creator network.',
    ]);
}

/** Options for the brand brief builder. */
function brief_goals()
{
    return [
        'awareness' => ['ph-megaphone', 'Brand awareness', 'Get seen by the right people'],
        'launch'    => ['ph-rocket-launch', 'Product launch', 'Make a new product the talk of the feed'],
        'sales'     => ['ph-shopping-bag', 'Sales and sign ups', 'Drive clicks, codes and conversions'],
        'ugc'       => ['ph-video-camera', 'UGC for ads', 'Creator videos for your paid ads'],
        'app'       => ['ph-device-mobile', 'App installs', 'Grow downloads with tracked links'],
        'visits'    => ['ph-storefront', 'Store or event visits', 'Bring people through the door'],
    ];
}

function brief_tiers()
{
    return ['Nano (1-10K)', 'Micro (10-100K)', 'Mid-tier (100-500K)', 'Macro (500K-1M)', 'Celebrity (1M+)'];
}

function brief_budgets()
{
    return ['Under $5K', '$5K - $15K', '$15K - $50K', '$50K - $150K', '$150K+', 'Not sure yet'];
}

function brief_timelines()
{
    return ['As soon as possible', 'In 2 to 4 weeks', 'In 1 to 3 months', 'Just exploring'];
}

function site_contact()
{
    $errors = [];
    $type = in_array(query('type'), ['brand', 'creator', 'other'], true) ? query('type') : 'brand';
    $data = [
        'name' => '', 'email' => '', 'company' => '', 'phone' => '', 'website' => '',
        'inquiry_type' => $type, 'budget' => '', 'message' => '', 'goal' => '',
        'niches' => [], 'platforms' => [], 'tiers' => [], 'timeline' => '', 'target_location' => '',
    ];
    // Pre-fill from the homepage matchmaker and niche pages.
    if (($n = query('niche')) && in_array($n, niche_options(), true)) $data['niches'] = [$n];
    if (($p = query('platform')) && array_key_exists($p, platform_options())) $data['platforms'] = [$p];

    // Creators a brand shortlisted on /talent ("Request a quote").
    $picked = roster_by_codes(is_post() ? input_array('creators') : csv_list(query('creators')));
    if ($picked && !is_post() && !$data['niches']) {
        $data['niches'] = picked_niches($picked);
    }

    if (is_post()) {
        foreach (['name', 'email', 'company', 'phone', 'website', 'inquiry_type', 'budget', 'message', 'goal', 'timeline', 'target_location'] as $k) {
            $data[$k] = mb_substr((string) input($k), 0, $k === 'message' ? 5000 : 190);
        }
        $data['email'] = strtolower($data['email']);
        $data['niches'] = array_slice(array_values(array_intersect(input_array('niches'), niche_options())), 0, 6);
        $data['platforms'] = array_values(array_intersect(input_array('platforms'), array_keys(platform_options())));
        $data['tiers'] = array_values(array_intersect(input_array('tiers'), brief_tiers()));
        if (!in_array($data['inquiry_type'], ['brand', 'creator', 'other'], true)) $data['inquiry_type'] = 'other';
        if (!array_key_exists($data['goal'], brief_goals())) $data['goal'] = '';
        if (!in_array($data['budget'], brief_budgets(), true)) $data['budget'] = '';
        if (!in_array($data['timeline'], brief_timelines(), true)) $data['timeline'] = '';

        if (input('company_site') !== '') {
            // Honeypot filled by a bot: quietly accept and drop.
            redirect('contact?sent=1');
        }
        $isBrand = $data['inquiry_type'] === 'brand';
        if ($data['name'] === '') $errors['name'] = 'Please tell us your name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email so we can reply.';
        if ($isBrand) {
            if ($data['goal'] === '') $errors['goal'] = 'Choose the main goal for this campaign.';
            if (!$data['niches'] && !$picked) $errors['niches'] = 'Pick at least one niche.';
            if (!$data['niches'] && $picked) $data['niches'] = picked_niches($picked);
        } elseif (mb_strlen($data['message']) < 10) {
            $errors['message'] = 'Add a few details so we can help (10 characters minimum).';
        }
        if ($data['website'] !== '' && !preg_match('#^(https?://)?[^\s/$.?\#].[^\s]*$#i', $data['website'])) $errors['website'] = 'Enter a valid website address.';

        $recent = (int) val('SELECT COUNT(*) FROM messages WHERE ip_hash = ? AND created_at > ?', [ip_hash(), date('Y-m-d H:i:s', time() - 3600)]);
        if ($recent >= 5) $errors['form'] = 'You have sent several messages already. We will be in touch soon.';

        if (!$errors) {
            $goals = brief_goals();
            $row = [
                'name' => $data['name'], 'email' => $data['email'], 'company' => $data['company'] ?: null,
                'phone' => $data['phone'] ?: null, 'website' => $data['website'] ?: null,
                'inquiry_type' => $data['inquiry_type'], 'budget' => $data['budget'] ?: null,
                'message' => $data['message'] !== '' ? $data['message'] : '(No extra details. See brief.)',
                'goal' => $isBrand && $data['goal'] ? $goals[$data['goal']][1] : null,
                'niches' => $data['niches'] ? implode(', ', $data['niches']) : null,
                'platforms' => $data['platforms'] ? implode(', ', array_map(function ($p) { return platform_options()[$p]['label']; }, $data['platforms'])) : null,
                'tiers' => $data['tiers'] ? implode(', ', $data['tiers']) : null,
                'timeline' => $data['timeline'] ?: null,
                'target_location' => $data['target_location'] ?: null,
                'creator_ids' => $isBrand && $picked ? implode(',', array_column($picked, 'id')) : null,
                'creator_ref' => $isBrand && $picked ? mb_substr(implode(', ', array_column($picked, 'code')), 0, 190) : null,
                'is_read' => 0, 'ip_hash' => ip_hash(), 'created_at' => now(),
            ];
            $id = insert('messages', $row);
            $brief = $isBrand ? "Goal: {$row['goal']}\nNiches: {$row['niches']}\nPlatforms: {$row['platforms']}\nCreator tiers: {$row['tiers']}\nBudget: {$row['budget']}\nTimeline: {$row['timeline']}\nTarget location: {$row['target_location']}\nWebsite: {$row['website']}\n\n" : '';
            if ($isBrand && $picked) {
                $brief .= 'Creators requested (' . count($picked) . "):\n";
                foreach ($picked as $c) {
                    $brief .= '- ' . $c['code'] . ' ' . $c['name'] . ', ' . short_number($c['reach']) . ' followers, ' . implode('/', $c['niches']) . "\n";
                }
                $brief .= "\n";
            }
            send_mail(setting('contact_email'), ($isBrand ? 'New campaign brief' : 'New ' . $data['inquiry_type'] . ' enquiry') . ' from ' . $data['name'] . ($data['company'] ? ' (' . $data['company'] . ')' : ''),
                "Name: {$data['name']}\nEmail: {$data['email']}\nCompany: {$data['company']}\nPhone: {$data['phone']}\n\n{$brief}{$row['message']}\n\nView in admin: " . abs_url('admin/messages/' . $id));
            redirect('contact?sent=1' . ($isBrand ? '&brief=1' : '') . ($isBrand && $picked ? '&picked=' . count($picked) : ''));
        }
    }

    // Anonymous niche/platform pairs of approved creators, for the live match counter.
    $pool = [];
    $liveCounts = stats_mode() === 'live';
    foreach (all("SELECT niches, instagram, youtube, tiktok, facebook, x FROM influencers WHERE status = 'approved'") as $r) {
        $pl = [];
        foreach (array_keys(platform_options()) as $p) if (!empty($r[$p])) $pl[] = $p;
        $pool[] = [csv_list($r['niches']), $pl];
    }

    render('site/contact', ['errors' => $errors, 'data' => $data, 'sent' => query('sent') === '1', 'pool' => $pool, 'liveCounts' => $liveCounts, 'picked' => $picked], [
        'title' => 'Start a Campaign or Contact Peakilo',
        'description' => 'Build your influencer campaign brief in two minutes. Pick your goal, niches, platforms and budget, and the Peakilo team will reply within one business day.',
        'schema' => [schema_breadcrumbs(['Home' => '', 'Contact' => 'contact'])],
        'robots' => query('sent') || $picked ? 'noindex' : null,
        'body_class' => 'page-contact',
        'scripts' => ['js/brief.js'],
    ]);
}

function site_subscribe()
{
    $ajax = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    $email = strtolower(input('email'));
    $ok = false;
    $msg = '';
    if (input('website') !== '') {
        $ok = true;
        $msg = 'Thanks, you are on the list.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
    } else {
        if (!val('SELECT COUNT(*) FROM subscribers WHERE email = ?', [$email])) {
            insert('subscribers', ['email' => $email, 'source' => 'footer', 'created_at' => now()]);
        }
        $ok = true;
        $msg = 'Thanks, you are on the list.';
    }
    if ($ajax) {
        json_out(['ok' => $ok, 'message' => $msg], $ok ? 200 : 422);
    }
    flash($ok ? 'success' : 'error', $msg);
    back('');
}

/** Most common niches across the creators a brand picked (max 6). */
function picked_niches(array $picked)
{
    $count = [];
    foreach ($picked as $c) {
        foreach ($c['niches'] as $n) {
            if (in_array($n, niche_options(), true)) $count[$n] = ($count[$n] ?? 0) + 1;
        }
    }
    arsort($count);
    return array_slice(array_keys($count), 0, 6);
}

/* ---------------- Talent roster (brands browse and shortlist) ---------------- */

function talent_sorts()
{
    return ['featured' => 'Recommended', 'reach' => 'Most followers', 'views' => 'Most avg. views', 'er' => 'Highest engagement', 'newest' => 'Newest'];
}

function site_talent()
{
    $tiers = ['nano' => 'Nano', 'micro' => 'Micro', 'mid' => 'Mid-tier', 'macro' => 'Macro', 'mega' => 'Mega'];
    $f = [
        'q' => mb_substr(query('q'), 0, 60),
        'niche' => in_array(query('niche'), niche_options(), true) ? query('niche') : '',
        'platform' => array_key_exists(query('platform'), platform_options()) ? query('platform') : '',
        'tier' => array_key_exists(query('tier'), $tiers) ? query('tier') : '',
        'sort' => array_key_exists(query('sort'), talent_sorts()) ? query('sort') : 'featured',
    ];
    $total = (int) val('SELECT COUNT(*) FROM influencers WHERE ' . roster_where());
    // Every card is rendered; ones outside the filters are hidden, so the
    // page can re-filter instantly in the browser without a reload.
    $matching = array_flip(array_map(function ($r) { return (int) $r['id']; }, roster_rows($f)));
    $cards = array_map(function ($r) use ($matching) { return roster_card($r) + ['match' => isset($matching[(int) $r['id']])]; }, roster_rows([]));
    $sortKey = ['reach' => 'reach', 'views' => 'views', 'er' => 'er'][$f['sort']] ?? null;
    if ($sortKey) {
        usort($cards, function ($a, $b) use ($sortKey) { return ($b[$sortKey] ?? 0) <=> ($a[$sortKey] ?? 0); });
    } elseif ($f['sort'] === 'newest') {
        usort($cards, function ($a, $b) { return strcmp($b['created'], $a['created']); });
    }
    render('site/talent', compact('f', 'cards', 'total', 'tiers'), [
        'title' => 'Find Influencers to Book',
        'description' => 'Browse vetted creators by niche, platform and audience size. Shortlist the ones you like and Peakilo sends availability and a quote within one business day.',
        'canonical' => abs_url('talent'),
        'robots' => array_filter([$f['q'], $f['niche'], $f['platform'], $f['tier']]) ? 'noindex, follow' : null,
        'schema' => [schema_breadcrumbs(['Home' => '', 'Find talent' => 'talent'])],
        'scripts' => ['js/talent.js'],
    ]);
}

function site_talent_profile($params)
{
    $id = creator_id_from_code($params['code']);
    $row = $id ? one('SELECT * FROM influencers WHERE id = ? AND ' . roster_where(), [$id]) : null;
    if (!$row) {
        not_found();
    }
    $c = roster_card($row);
    if ($params['code'] !== $c['code']) {
        redirect('talent/' . $c['code'], 301);
    }
    $similar = [];
    if ($c['niches']) {
        foreach (roster_rows(['niche' => $c['niches'][0]], 8) as $r) {
            if ((int) $r['id'] !== $c['id']) $similar[] = roster_card($r);
        }
    }
    render('site/talent_profile', ['c' => $c, 'similar' => array_slice($similar, 0, 4)], [
        'title' => $c['name'] . ', ' . ($c['niches'][0] ?? 'Content') . ' creator',
        'description' => $c['name'] . ' is a ' . strtolower($c['tier']['label']) . ' ' . strtolower(implode(' and ', array_slice($c['niches'], 0, 2))) . ' creator'
            . ($c['city'] ? ' from ' . $c['city'] : '') . ' with ' . short_number($c['reach']) . ' followers. Book through Peakilo.',
        'canonical' => abs_url('talent/' . $c['code']),
        'image' => preg_match('#^https?://#', $c['photo']) ? $c['photo'] : site_origin() . $c['photo'],
        'schema' => [schema_breadcrumbs(['Home' => '', 'Find talent' => 'talent', $c['name'] => 'talent/' . $c['code']])],
        'scripts' => ['js/talent.js'],
    ]);
}

/* ---------------- Niche landing pages (SEO) ---------------- */

function site_niches()
{
    $counts = stats_mode() === 'live' ? niche_counts() : [];
    render('site/niches', ['catalog' => niche_catalog(), 'counts' => $counts], [
        'title' => 'Influencer Marketing by Niche',
        'description' => 'Find vetted influencers in 20 niches, from beauty and fashion to tech, finance and gaming. Peakilo matches your brand with creators your customers follow.',
        'schema' => [schema_breadcrumbs(['Home' => '', 'Niches' => 'influencers'])],
    ]);
}

function niche_faqs($name)
{
    $l = strtolower($name);
    return [
        ['q' => "How do you find the right $l influencers for my brand?", 'a' => "We shortlist $l creators from our vetted network based on audience location, age, engagement and content quality, then share profiles with you before anyone is booked."],
        ['q' => "How much does a $l influencer campaign cost?", 'a' => "It depends on creator size, platform, deliverables and usage rights. Micro $l creators are the most affordable way to start, and we give you a clear quote after a short brief."],
        ['q' => "Which platforms work best for $l campaigns?", 'a' => "Most $l campaigns perform well on Instagram Reels, TikTok and YouTube Shorts, with long-form YouTube for reviews and tutorials. We recommend a mix based on your goal."],
    ];
}

function site_niche($params)
{
    $catalog = niche_catalog();
    $slug = $params['slug'];
    if (!isset($catalog[$slug])) {
        not_found();
    }
    $niche = $catalog[$slug] + ['slug' => $slug];
    $counts = stats_mode() === 'live' ? niche_counts() : [];
    $c = $counts[$niche['name']] ?? ['any' => 0];
    $creators = public_creators(12, $niche['name']);
    $faqs = niche_faqs($niche['name']);
    $related = [];
    foreach ($catalog as $s => $n) {
        if ($s !== $slug) $related[$s] = $n;
    }
    $related = array_slice($related, (crc32($slug) % 14), 5, true) ?: array_slice($related, 0, 5, true);
    $posts = all("SELECT p.*, c.name AS category_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 'published' AND p.published_at <= ? AND (p.title LIKE ? OR p.content LIKE ?) ORDER BY p.published_at DESC LIMIT 3", [now(), '%' . $niche['name'] . '%', '%' . $niche['name'] . '%']);

    render('site/niche', compact('niche', 'c', 'creators', 'faqs', 'related', 'posts'), [
        'title' => $niche['name'] . ' Influencer Marketing Agency',
        'description' => 'Work with vetted ' . strtolower($niche['name']) . ' influencers. ' . str_limit($niche['blurb'], 110),
        'schema' => [
            schema_breadcrumbs(['Home' => '', 'Niches' => 'influencers', $niche['name'] => 'influencers/' . $slug]),
            schema_service($niche['name'] . ' influencer marketing', $niche['blurb']),
            schema_faq($faqs),
        ],
    ]);
}
