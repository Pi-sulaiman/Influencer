<?php
/**
 * Niche catalogue. Powers the hero matchmaker, the niche grid on the
 * homepage and the SEO landing pages at /influencers/{slug}.
 * Edit the copy here to make each niche page your own.
 */

function niche_catalog()
{
    static $cat = null;
    if ($cat !== null) {
        return $cat;
    }
    $rows = [
        ['Fashion', 'ph-t-shirt', 'Fashion creators turn a new collection into outfits people actually want to wear. We match labels with stylists, haul creators and street style voices whose audiences shop.', ['Lookbook reels for a seasonal drop', 'Try-on hauls with tracked discount codes', 'Styling challenges across creator tiers']],
        ['Beauty', 'ph-sparkle', 'Beauty audiences trust creators who show real skin and real results. We partner skincare, makeup and haircare brands with creators who review honestly and teach well.', ['Get-ready-with-me tutorials', 'Thirty day results diaries', 'Shade range launches with diverse creators']],
        ['Lifestyle', 'ph-sun-horizon', 'Lifestyle creators fit products naturally into daily routines, which makes them ideal for broad consumer launches and brand awareness.', ['Day-in-the-life integrations', 'Home routine and productivity content', 'Seasonal gift guides']],
        ['Travel', 'ph-airplane-tilt', 'Travel creators inspire bookings long after a post goes live. We work with hotels, tourism boards and travel apps to plan trips and content that keeps selling.', ['Hosted stays and destination guides', 'Itinerary carousels and short films', 'Travel gear in real conditions']],
        ['Food', 'ph-fork-knife', 'Food content is shared, saved and cooked. We pair restaurants, FMCG and kitchen brands with recipe creators, reviewers and home cooks.', ['Recipe reels using your product', 'Restaurant launch tastings', 'Taste tests and honest reviews']],
        ['Fitness', 'ph-barbell', 'Fitness audiences follow creators for discipline and results. We match apparel, supplement and app brands with coaches and athletes people train with.', ['Workout programmes featuring your product', 'Challenge campaigns with community entries', 'Gear reviews from real training']],
        ['Health', 'ph-heartbeat', 'Health and wellness content needs credibility. We work with qualified and responsible creators so wellness brands stay trustworthy and compliant.', ['Expert explainers and myth busting', 'Wellness routine integrations', 'Mental health awareness campaigns']],
        ['Tech', 'ph-cpu', 'Tech buyers watch reviews before they buy. We connect gadget, software and telecom brands with reviewers and explainers audiences rely on.', ['Unboxings and long-form reviews', 'Feature walkthrough shorts', 'App install campaigns with tracking']],
        ['Gaming', 'ph-game-controller', 'Gaming communities are loyal and highly engaged. We run launches with streamers and creators across YouTube, Twitch and short-form video.', ['Sponsored streams and playthroughs', 'Launch day creator squads', 'Peripheral and hardware integrations']],
        ['Finance', 'ph-chart-line-up', 'Finance creators earn trust by explaining money clearly. We work with fintech, banking and investing brands on compliant, educational content.', ['Explainer videos for new products', 'App sign up campaigns', 'Budgeting and saving challenges']],
        ['Education', 'ph-graduation-cap', 'Education creators turn learning into habits. We help edtech, courses and universities reach students through teachers and study creators.', ['Study with me content', 'Course launch reviews', 'Exam season campaigns']],
        ['Parenting', 'ph-baby', 'Parents trust other parents. We match family brands with parenting creators whose audiences are planning, buying and recommending.', ['Real routines with family products', 'Honest first-time parent reviews', 'Back to school campaigns']],
        ['Comedy', 'ph-smiley', 'Comedy creators make brands memorable and shareable. We brief sketches and formats that put your product in the punchline, not in the way of it.', ['Branded sketches and skits', 'Trend and meme formats', 'Launch teasers built for shares']],
        ['Music', 'ph-music-notes', 'Music creators drive trends and sounds that spread across platforms. We plan song launches, audio brands and event campaigns with artists and dancers.', ['Sound and dance challenges', 'Behind the scenes artist content', 'Concert and festival promotion']],
        ['Art & Design', 'ph-palette', 'Artists and designers bring craft to brand stories. We pair creative tools, stationery and lifestyle brands with makers who show the process.', ['Process and time-lapse videos', 'Limited edition collaborations', 'Creative tool tutorials']],
        ['Sports', 'ph-soccer-ball', 'Sports creators bring passion and match day energy. We work with sportswear, equipment and nutrition brands on fan-first content.', ['Match day reaction content', 'Training and skills challenges', 'Athlete and fan creator meetups']],
        ['Automotive', 'ph-car', 'Car buyers research for weeks. We match automotive and accessories brands with reviewers and enthusiasts who show the details that matter.', ['Test drive reviews', 'Road trip series', 'Accessory installs and comparisons']],
        ['Home & Decor', 'ph-house-line', 'Home creators inspire makeovers people copy. We work with furniture, decor and appliance brands on room transformations and styling content.', ['Room makeover series', 'Small space styling tips', 'Appliance in real homes']],
        ['Pets', 'ph-paw-print', 'Pet content is among the most loved on social media. We match pet food, care and accessory brands with pet creators and their very photogenic friends.', ['Taste tests and product trials', 'Pet care tips from vets and owners', 'Adoption and charity campaigns']],
        ['Business', 'ph-briefcase', 'Business creators reach founders, professionals and decision makers. We run B2B and SaaS campaigns on LinkedIn, YouTube and podcasts.', ['Founder story integrations', 'Tool reviews for professionals', 'Podcast sponsorships']],
    ];
    $cat = [];
    foreach ($rows as $r) {
        $cat[slugify($r[0])] = ['name' => $r[0], 'icon' => $r[1], 'blurb' => $r[2], 'ideas' => $r[3]];
    }
    return $cat;
}

function niche_by_name($name)
{
    foreach (niche_catalog() as $slug => $n) {
        if ($n['name'] === $name) {
            return $n + ['slug' => $slug];
        }
    }
    return null;
}

/**
 * Counts approved creators per niche and per platform, for the matchmaker.
 * Only counts, never personal data, so it is safe to show publicly.
 * Returns ['Beauty' => ['any' => 12, 'instagram' => 9, ...], ...]
 */
function niche_counts()
{
    $counts = [];
    $platforms = array_keys(platform_options());
    $rows = all("SELECT niches, instagram, youtube, tiktok, facebook, x FROM influencers WHERE status = 'approved' AND is_demo = 0");
    foreach ($rows as $r) {
        foreach (csv_list($r['niches']) as $n) {
            if (!isset($counts[$n])) {
                $counts[$n] = array_fill_keys(array_merge(['any'], $platforms), 0);
            }
            $counts[$n]['any']++;
            foreach ($platforms as $p) {
                if (!empty($r[$p])) $counts[$n][$p]++;
            }
        }
    }
    return $counts;
}

/** Approved creators per platform (each creator counted once per platform). */
function platform_totals()
{
    $out = [];
    foreach (array_keys(platform_options()) as $p) {
        $out[$p] = (int) val("SELECT COUNT(*) FROM influencers WHERE status = 'approved' AND is_demo = 0 AND $p IS NOT NULL AND $p <> ''");
    }
    return $out;
}

/**
 * Creators shown publicly: approved, gave permission to be featured and
 * uploaded a photo. Featured ones come first.
 */
function public_creators($limit = 24, $niche = null)
{
    $out = [];
    foreach (roster_rows($niche ? ['niche' => $niche] : [], $limit) as $r) {
        $c = roster_card($r);
        $out[] = $c + [
            'first' => $c['name'],
            'city' => $c['city'] ?: $c['country'],
        ];
    }
    return $out;
}

/** SQL condition for creators brands may see on the website. */
function roster_where()
{
    // Sample creators only appear while demo mode is on.
    return "status = 'approved' AND consent_feature = 1 AND photo IS NOT NULL AND photo <> ''" . (demo_on() ? '' : ' AND is_demo = 0');
}

/**
 * The public name: the creator name they chose, otherwise first name and
 * last initial. Full legal names stay private.
 */
function public_name(array $r)
{
    if (!empty($r['display_name'])) {
        return $r['display_name'];
    }
    $parts = preg_split('/\s+/', trim((string) $r['full_name']));
    $first = $parts[0] ?? 'Creator';
    return count($parts) > 1 ? $first . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : $first;
}

/** Rows for the public roster. Filters: niche, platform, tier, q. */
function roster_rows(array $f = [], $limit = 300)
{
    $sql = 'SELECT * FROM influencers WHERE ' . roster_where();
    $args = [];
    if (!empty($f['niche'])) {
        $sql .= ' AND niches LIKE ?';
        $args[] = '%' . $f['niche'] . '%';
    }
    if (!empty($f['platform']) && array_key_exists($f['platform'], platform_options())) {
        $sql .= " AND {$f['platform']} IS NOT NULL AND {$f['platform']} <> ''";
    }
    $sql .= ' ORDER BY featured DESC, total_followers DESC LIMIT ' . (int) $limit;
    $rows = all($sql, $args);
    if (!empty($f['tier'])) {
        $rows = array_values(array_filter($rows, function ($r) use ($f) { return creator_tier($r['total_followers'])['key'] === $f['tier']; }));
    }
    if (!empty($f['q'])) {
        $q = mb_strtolower($f['q']);
        $rows = array_values(array_filter($rows, function ($r) use ($q) {
            return mb_strpos(mb_strtolower(public_name($r) . ' ' . $r['city'] . ' ' . $r['country'] . ' ' . $r['niches']), $q) !== false;
        }));
    }
    return $rows;
}

/**
 * Public card data for one creator. Never includes fees, contact details,
 * handles or links, so brands book through Peakilo.
 */
function roster_card(array $r)
{
    $channels = [];
    foreach (platform_options() as $p => $meta) {
        if (empty($r[$p])) continue;
        $channels[$p] = [
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'followers' => (int) $r[$p . '_followers'],
            'views' => (int) ($r[$p . '_avg_views'] ?? 0),
        ];
    }
    return [
        'id' => (int) $r['id'],
        'code' => creator_code($r['id']),
        'name' => public_name($r),
        'city' => (string) $r['city'],
        'country' => (string) $r['country'],
        'niches' => csv_list($r['niches']),
        'content_types' => csv_list($r['content_types']),
        'languages' => (string) $r['languages'],
        'photo' => upload_url($r['photo']),
        'reach' => (int) $r['total_followers'],
        'views' => (int) ($r['avg_impressions'] ?? 0),
        'monthly' => (int) ($r['monthly_impressions'] ?? 0),
        'er' => $r['engagement_rate'] !== null ? (float) $r['engagement_rate'] : null,
        'tier' => creator_tier($r['total_followers']),
        'channels' => $channels,
        'platforms' => array_keys($channels),
        'primary' => $r['primary_platform'] ?: (array_keys($channels)[0] ?? null),
        'audience' => array_values(array_filter([$r['audience_country'] ?? '', $r['audience_age'] ?? '', $r['audience_gender'] ?? ''])),
        'bio' => (string) $r['bio'],
        'past_brands' => (string) ($r['past_brands'] ?? ''),
        'barter' => !empty($r['barter_ok']),
        'featured' => !empty($r['featured']),
        'demo' => !empty($r['is_demo']),
        'created' => (string) $r['created_at'],
    ];
}

/** Public creators by code (PK1001...), in the order given. Unknown codes are dropped. */
function roster_by_codes(array $codes, $max = 20)
{
    $ids = [];
    foreach ($codes as $c) {
        $id = creator_id_from_code($c);
        if ($id > 0 && !in_array($id, $ids, true)) $ids[] = $id;
    }
    $ids = array_slice($ids, 0, $max);
    if (!$ids) {
        return [];
    }
    $rows = [];
    foreach (all('SELECT * FROM influencers WHERE ' . roster_where() . ' AND id IN (' . in_placeholders($ids) . ')', $ids) as $r) {
        $rows[(int) $r['id']] = $r;
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($rows[$id])) $out[] = roster_card($rows[$id]);
    }
    return $out;
}
