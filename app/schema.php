<?php
/**
 * Database schema + starter content. Used once by /install.
 * The same definitions work for MySQL and SQLite.
 */

function schema_statements()
{
    $mysql = db_driver() === 'mysql';
    $id = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fk = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $longtext = $mysql ? 'MEDIUMTEXT' : 'TEXT';

    return [
        "CREATE TABLE IF NOT EXISTS admins (
            id $id,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS login_attempts (
            id $id,
            ip_hash CHAR(64) NOT NULL,
            attempted_at DATETIME NOT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS settings (
            skey VARCHAR(80) NOT NULL PRIMARY KEY,
            svalue TEXT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS campaigns (
            id $id,
            title VARCHAR(190) NOT NULL,
            code VARCHAR(40) NOT NULL UNIQUE,
            brand_name VARCHAR(190) NULL,
            description TEXT NULL,
            deliverables TEXT NULL,
            niches VARCHAR(255) NULL,
            platforms VARCHAR(255) NULL,
            budget_min DECIMAL(12,2) NULL,
            budget_max DECIMAL(12,2) NULL,
            currency VARCHAR(8) NULL,
            location VARCHAR(160) NULL,
            deadline DATE NULL,
            mode VARCHAR(20) NOT NULL DEFAULT 'form',
            redirect_url VARCHAR(500) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            cover_image VARCHAR(255) NULL,
            visits INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS campaign_visits (
            id $id,
            campaign_id $fk NOT NULL,
            visited_at DATETIME NOT NULL,
            referrer VARCHAR(255) NULL,
            ip_hash CHAR(64) NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS influencers (
            id $id,
            full_name VARCHAR(160) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(40) NULL,
            city VARCHAR(120) NULL,
            country VARCHAR(120) NULL,
            gender VARCHAR(30) NULL,
            languages VARCHAR(190) NULL,
            primary_platform VARCHAR(30) NULL,
            instagram VARCHAR(190) NULL,
            instagram_followers INT NULL,
            youtube VARCHAR(190) NULL,
            youtube_followers INT NULL,
            tiktok VARCHAR(190) NULL,
            tiktok_followers INT NULL,
            facebook VARCHAR(190) NULL,
            facebook_followers INT NULL,
            x VARCHAR(190) NULL,
            x_followers INT NULL,
            website VARCHAR(255) NULL,
            total_followers INT NOT NULL DEFAULT 0,
            engagement_rate DECIMAL(5,2) NULL,
            niches VARCHAR(255) NULL,
            content_types VARCHAR(255) NULL,
            fee_min DECIMAL(12,2) NULL,
            fee_max DECIMAL(12,2) NULL,
            currency VARCHAR(8) NULL,
            fee_notes VARCHAR(255) NULL,
            bio TEXT NULL,
            photo VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'new',
            featured INT NOT NULL DEFAULT 0,
            consent_feature INT NOT NULL DEFAULT 0,
            rating INT NULL,
            admin_notes TEXT NULL,
            source VARCHAR(80) NULL,
            ip_hash CHAR(64) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS applications (
            id $id,
            influencer_id $fk NOT NULL,
            campaign_id $fk NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'applied',
            pitch TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE (influencer_id, campaign_id)
        )$engine",

        "CREATE TABLE IF NOT EXISTS categories (
            id $id,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL UNIQUE,
            description TEXT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS posts (
            id $id,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            excerpt TEXT NULL,
            content $longtext NULL,
            cover_image VARCHAR(255) NULL,
            cover_alt VARCHAR(255) NULL,
            category_id $fk NULL,
            author_name VARCHAR(120) NULL,
            meta_title VARCHAR(255) NULL,
            meta_description VARCHAR(320) NULL,
            focus_keyword VARCHAR(120) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            featured INT NOT NULL DEFAULT 0,
            views INT NOT NULL DEFAULT 0,
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS messages (
            id $id,
            name VARCHAR(160) NOT NULL,
            email VARCHAR(190) NOT NULL,
            company VARCHAR(190) NULL,
            phone VARCHAR(40) NULL,
            inquiry_type VARCHAR(40) NULL,
            budget VARCHAR(60) NULL,
            message TEXT NOT NULL,
            is_read INT NOT NULL DEFAULT 0,
            ip_hash CHAR(64) NULL,
            created_at DATETIME NOT NULL
        )$engine",

        "CREATE TABLE IF NOT EXISTS subscribers (
            id $id,
            email VARCHAR(190) NOT NULL UNIQUE,
            source VARCHAR(60) NULL,
            created_at DATETIME NOT NULL
        )$engine",
    ];
}

function schema_indexes()
{
    return [
        'CREATE INDEX idx_inf_status ON influencers (status)',
        'CREATE INDEX idx_inf_created ON influencers (created_at)',
        'CREATE INDEX idx_app_campaign ON applications (campaign_id)',
        'CREATE INDEX idx_visits_campaign ON campaign_visits (campaign_id, visited_at)',
        'CREATE INDEX idx_posts_status ON posts (status, published_at)',
        'CREATE INDEX idx_login_ip ON login_attempts (ip_hash, attempted_at)',
    ];
}

/**
 * Versioned upgrades. Each entry runs once; the current version is stored in
 * settings.schema_version. Add new ALTER statements at the end when you
 * extend the database, and they will run automatically on the next visit.
 */
function schema_migrations()
{
    $mysql = db_driver() === 'mysql';
    $id = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $big = $mysql ? 'BIGINT' : 'INTEGER';
    return [
        6 => [
            // Brand logos for the homepage carousel (Admin > Site content > Brand logos).
            "CREATE TABLE IF NOT EXISTS brand_logos (
                id $id,
                name VARCHAR(160) NOT NULL,
                logo VARCHAR(255) NOT NULL,
                url VARCHAR(500) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_visible INT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL
            )$engine",
            // Demo mode now has 48 sample creators: add the ones an earlier version did not have.
            function () { if (demo_on() && demo_creator_count() > 0) seed_demo_creators(); },
        ],
        5 => [
            // Demo mode: sample creators and example numbers, switched off in Admin > Settings.
            'ALTER TABLE influencers ADD COLUMN is_demo INT NOT NULL DEFAULT 0',
            function () {
                if (setting('demo_mode') === '') save_setting('demo_mode', '1');
                // The example figures now live in demo mode only, so real mode starts from live counts.
                $examples = ['stat_creators' => '1200', 'stat_campaigns' => '340', 'stat_reach' => '85', 'stat_retention' => '92'];
                $allExamples = true;
                foreach ($examples as $k => $v) {
                    if (!in_array(setting($k), ['', $v], true)) $allExamples = false;
                }
                if ($allExamples) {
                    foreach (array_keys($examples) as $k) save_setting($k, '');
                    save_setting('stats_mode', 'live');
                }
                settings_all(true);
                if (demo_on()) seed_demo_creators();
            },
        ],
        4 => [
            // Richer creator profiles for agencies: public name, average views
            // per platform, monthly impressions, audience and a rate card.
            'ALTER TABLE influencers ADD COLUMN display_name VARCHAR(160) NULL',
            'ALTER TABLE influencers ADD COLUMN instagram_avg_views INT NULL',
            'ALTER TABLE influencers ADD COLUMN youtube_avg_views INT NULL',
            'ALTER TABLE influencers ADD COLUMN tiktok_avg_views INT NULL',
            'ALTER TABLE influencers ADD COLUMN facebook_avg_views INT NULL',
            'ALTER TABLE influencers ADD COLUMN x_avg_views INT NULL',
            "ALTER TABLE influencers ADD COLUMN avg_impressions $big NULL",
            "ALTER TABLE influencers ADD COLUMN monthly_impressions $big NULL",
            'ALTER TABLE influencers ADD COLUMN audience_country VARCHAR(120) NULL',
            'ALTER TABLE influencers ADD COLUMN audience_age VARCHAR(30) NULL',
            'ALTER TABLE influencers ADD COLUMN audience_gender VARCHAR(30) NULL',
            'ALTER TABLE influencers ADD COLUMN past_brands VARCHAR(500) NULL',
            'ALTER TABLE influencers ADD COLUMN rate_card TEXT NULL',
            'ALTER TABLE influencers ADD COLUMN barter_ok INT NOT NULL DEFAULT 0',
            // Creators a brand picked from the public roster (comma separated ids).
            'ALTER TABLE messages ADD COLUMN creator_ids TEXT NULL',
        ],
        3 => [
            'ALTER TABLE messages ADD COLUMN creator_ref VARCHAR(190) NULL',
            'ALTER TABLE messages ADD COLUMN target_country VARCHAR(4) NULL',
            function () {
                // Display numbers on by default while the network grows (switch to live in Admin > Site content).
                $defaults = ['stats_mode' => 'custom', 'stat_creators' => '1200', 'stat_campaigns' => '340', 'stat_reach' => '85', 'stat_retention' => '92'];
                foreach ($defaults as $k => $v) {
                    if (setting($k) === '') save_setting($k, $v);
                }
                // India first, the rest of the world auto-detected.
                if (setting('default_currency', 'USD') === 'USD') save_setting('default_currency', 'INR');
                foreach (['currency_auto' => '1', 'theme_mode' => 'auto', 'fx_rates' => default_fx_text(), 'ai_crawlers' => '1'] as $k => $v) {
                    if (setting($k) === '') save_setting($k, $v);
                }
                settings_all(true);
            },
        ],
        2 => [
            "CREATE TABLE IF NOT EXISTS testimonials (
                id $id,
                name VARCHAR(160) NOT NULL,
                role VARCHAR(190) NULL,
                quote TEXT NOT NULL,
                photo VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_visible INT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL
            )$engine",
            // Clear the old placeholder homepage numbers so live figures are used.
            "UPDATE settings SET svalue = '' WHERE (skey = 'stat_creators' AND svalue = '1200') OR (skey = 'stat_campaigns' AND svalue = '340') OR (skey = 'stat_reach' AND svalue = '85') OR (skey = 'stat_retention' AND svalue = '92')",
        ],
        1 => [
            'ALTER TABLE messages ADD COLUMN website VARCHAR(255) NULL',
            'ALTER TABLE messages ADD COLUMN goal VARCHAR(80) NULL',
            'ALTER TABLE messages ADD COLUMN niches VARCHAR(255) NULL',
            'ALTER TABLE messages ADD COLUMN platforms VARCHAR(255) NULL',
            'ALTER TABLE messages ADD COLUMN tiers VARCHAR(120) NULL',
            'ALTER TABLE messages ADD COLUMN timeline VARCHAR(60) NULL',
            'ALTER TABLE messages ADD COLUMN target_location VARCHAR(160) NULL',
        ],
    ];
}

function run_migrations()
{
    $current = (int) setting('schema_version', '0');
    $all = schema_migrations();
    $latest = max(array_keys($all));
    if ($current >= $latest) {
        return;
    }
    ksort($all);
    foreach ($all as $version => $statements) {
        if ($version <= $current) {
            continue;
        }
        foreach ($statements as $sql) {
            try {
                if (is_callable($sql)) {
                    $sql();
                } else {
                    db()->exec($sql);
                }
            } catch (Throwable $e) {
                // Column already exists: safe to ignore.
            }
        }
        save_setting('schema_version', (string) $version);
    }
    settings_all(true);
}

function install_schema()
{
    foreach (schema_statements() as $sql) {
        db()->exec($sql);
    }
    foreach (schema_indexes() as $sql) {
        try {
            db()->exec($sql);
        } catch (Throwable $e) {
            // index already exists
        }
    }
}

function default_settings()
{
    return [
        'site_name'          => 'Peakilo',
        'tagline'            => 'Influencer Marketing Agency',
        'meta_description'   => 'Peakilo is an influencer marketing agency that matches brands with vetted creators and runs campaigns end to end, from brief to final report.',
        'contact_email'      => 'hello@peakilo.com',
        'contact_phone'      => '',
        'whatsapp'           => '',
        'address'            => '',
        'instagram_url'      => 'https://instagram.com/peakilo',
        'linkedin_url'       => '',
        'youtube_url'        => '',
        'tiktok_url'         => '',
        'x_url'              => '',
        'default_currency'   => 'INR',
        'ga_id'              => '',
        'google_site_verification' => '',
        // Homepage numbers: 'custom' shows these figures, 'live' counts from the database (Admin > Site content).
        'stats_mode'         => 'live',
        'stat_creators'      => '',
        'stat_campaigns'     => '',
        'stat_reach'         => '',
        'stat_retention'     => '',
        // Demo mode shows sample creators and example numbers until you turn it off (Admin > Settings).
        'demo_mode'          => '1',
        'theme_mode'         => 'auto',
        'currency_auto'      => '1',
        'fx_rates'           => default_fx_text(),
        'ai_crawlers'        => '1',
    ];
}

function seed_content($adminName)
{
    $t = now();
    foreach (default_settings() as $k => $v) {
        if (val('SELECT COUNT(*) FROM settings WHERE skey = ?', [$k]) == 0) {
            insert('settings', ['skey' => $k, 'svalue' => $v]);
        }
    }

    $cats = [
        ['Influencer Marketing', 'influencer-marketing', 'Strategy, budgeting and measurement for brand teams running creator campaigns.'],
        ['Creator Growth', 'creator-growth', 'Practical advice for creators on rates, pitching and building a sustainable business.'],
        ['Case Studies', 'case-studies', 'How real campaigns were planned, run and measured.'],
    ];
    $catIds = [];
    foreach ($cats as $c) {
        $existing = val('SELECT id FROM categories WHERE slug = ?', [$c[1]]);
        $catIds[$c[1]] = $existing ?: insert('categories', ['name' => $c[0], 'slug' => $c[1], 'description' => $c[2]]);
    }

    foreach (seed_posts() as $i => $p) {
        if (val('SELECT COUNT(*) FROM posts WHERE slug = ?', [$p['slug']])) {
            continue;
        }
        $published = date('Y-m-d H:i:s', time() - (3 - $i) * 86400 * 6);
        insert('posts', [
            'title' => $p['title'],
            'slug' => $p['slug'],
            'excerpt' => $p['excerpt'],
            'content' => $p['content'],
            'cover_image' => $p['cover'],
            'cover_alt' => $p['cover_alt'],
            'category_id' => $catIds[$p['category']],
            'author_name' => 'Peakilo Team',
            'meta_title' => $p['meta_title'],
            'meta_description' => $p['meta_description'],
            'focus_keyword' => $p['keyword'],
            'status' => 'published',
            'featured' => $i === 0 ? 1 : 0,
            'views' => 0,
            'published_at' => $published,
            'created_at' => $published,
            'updated_at' => $published,
        ]);
    }

    if (!val('SELECT COUNT(*) FROM campaigns')) {
        insert('campaigns', [
            'title' => 'Creator Network: Open Call',
            'code' => 'open-call',
            'brand_name' => 'Peakilo',
            'description' => 'Join the Peakilo creator network. Tell us about your channels, your audience and your rates, and we will match you with paid brand campaigns that fit your content.',
            'deliverables' => "Complete your creator profile\nShare your current rate range\nAdd a clear profile photo",
            'niches' => '',
            'platforms' => 'instagram,youtube,tiktok',
            'currency' => setting('default_currency', 'USD'),
            'mode' => 'form',
            'status' => 'active',
            'visits' => 0,
            'created_at' => $t,
            'updated_at' => $t,
        ]);
    }
}

function seed_posts()
{
    return [
        [
            'title' => 'How Much Should You Charge for a Sponsored Post? A Creator Rate Guide',
            'slug' => 'how-much-to-charge-for-sponsored-posts',
            'category' => 'creator-growth',
            'keyword' => 'influencer rates',
            'meta_title' => 'Influencer Rates: How Much to Charge for Sponsored Posts',
            'meta_description' => 'A practical guide to influencer rates. Learn how to price sponsored posts, reels and videos using reach, engagement, usage rights and deliverables.',
            'excerpt' => 'Pricing is the question every creator asks first. Here is a simple, defensible way to set influencer rates that brands respect and that grow with you.',
            'cover' => 'https://picsum.photos/id/1011/1600/900',
            'cover_alt' => 'Creator paddling a canoe on a mountain lake',
            'content' => '<p>Ask ten creators how they set their <strong>influencer rates</strong> and you will hear ten different answers. Some copy a friend, some take whatever the brand offers, and some pick a number that feels right. A clear method protects you from underpricing and gives brands a reason to say yes quickly.</p>
<h2>Start with a baseline from your reach</h2>
<p>Most agencies start from a cost per thousand followers and then adjust. A common starting band for a feed post sits between $10 and $25 per 1,000 followers, with short-form video priced higher because it takes longer to make and usually performs better. Treat this as a floor to adjust, not a rule.</p>
<h2>Adjust for engagement, not just followers</h2>
<p>Brands care about attention. If your engagement rate is well above the average for your platform and niche, you can price above the baseline. If your audience is very loyal in a narrow niche such as finance or parenting, that focus is worth a premium too.</p>
<ul>
<li><strong>Above average engagement:</strong> add 20 to 50 percent.</li>
<li><strong>High-value niche:</strong> add 15 to 30 percent.</li>
<li><strong>Strong audience match for the brand:</strong> mention it and price confidently.</li>
</ul>
<h2>Price the extras separately</h2>
<p>A post is only part of the deal. Usage rights, exclusivity and whitelisting change the value of your content for the brand, so they should change the price.</p>
<ul>
<li><strong>Usage rights</strong> (the brand reuses your content in ads): add 30 to 100 percent depending on duration.</li>
<li><strong>Exclusivity</strong> (no competing brands for a period): charge per month of exclusivity.</li>
<li><strong>Rush timelines</strong>: add a fee when the turnaround is under a week.</li>
</ul>
<h2>Share a range, not a single number</h2>
<p>A range such as "$400 to $650 per reel" gives room to negotiate without you dropping your price. The low end covers a simple deliverable, and the high end covers scripting, extra revisions or usage rights. This is exactly why the Peakilo creator profile asks for a minimum and maximum fee.</p>
<h2>Review your rates every quarter</h2>
<p>As your audience grows and your portfolio improves, your rates should move too. Keep a simple log of each deal, the deliverables and the results. That log becomes your strongest negotiation tool.</p>
<blockquote>Creators who share clear rate ranges get booked faster, because brand teams can approve budgets without a long back and forth.</blockquote>
<p>Ready to get matched with paid campaigns? <a href="/join">Create your free Peakilo creator profile</a> and add your rate range in a few minutes.</p>',
        ],
        [
            'title' => 'Micro vs Macro Influencers: Which Is Right for Your Brand?',
            'slug' => 'micro-vs-macro-influencers',
            'category' => 'influencer-marketing',
            'keyword' => 'micro influencers',
            'meta_title' => 'Micro vs Macro Influencers: Which Should Your Brand Choose?',
            'meta_description' => 'Compare micro influencers and macro influencers on reach, engagement, cost and trust, and learn how to mix creator tiers for better campaign ROI.',
            'excerpt' => 'Bigger is not always better. Here is how micro and macro influencers compare on reach, cost and trust, and how to blend them in one campaign.',
            'cover' => 'https://picsum.photos/id/342/1600/900',
            'cover_alt' => 'Traveller with a backpack in a busy street market',
            'content' => '<p>Choosing between <strong>micro influencers</strong> and macro influencers is one of the first decisions in any campaign plan. Each tier does a different job, and the best results usually come from using them together.</p>
<h2>What counts as micro and macro?</h2>
<p>Definitions vary by market, but a practical split looks like this:</p>
<ul>
<li><strong>Nano:</strong> 1,000 to 10,000 followers</li>
<li><strong>Micro:</strong> 10,000 to 100,000 followers</li>
<li><strong>Mid-tier:</strong> 100,000 to 500,000 followers</li>
<li><strong>Macro:</strong> 500,000 to 1 million followers</li>
<li><strong>Mega:</strong> more than 1 million followers</li>
</ul>
<h2>Why micro influencers punch above their weight</h2>
<p>Micro creators tend to have closer relationships with their audiences. Comments turn into conversations and recommendations feel personal. For product launches that rely on trust, such as skincare, food or local services, that intimacy often drives more conversions per dollar than a single large post.</p>
<h2>When macro influencers make sense</h2>
<p>Macro and mega creators are built for awareness. If you need a large number of people to hear about a launch in a short window, a handful of large creators can deliver reach that would take dozens of micro partnerships to match. They also bring production quality and experience with brand guidelines.</p>
<h2>Blend tiers for the best of both</h2>
<p>Many strong campaigns use one or two larger creators to spark awareness, then a wave of micro creators to build trust and social proof over the following weeks. The larger posts create the moment and the micro posts keep it going.</p>
<h3>A simple budget split to start with</h3>
<ul>
<li>40 percent on one or two macro creators for launch reach</li>
<li>45 percent on 10 to 25 micro creators for trust and conversions</li>
<li>15 percent on paid amplification of the best performing posts</li>
</ul>
<h2>Measure the right thing for each tier</h2>
<p>Judge macro creators on reach and video views. Judge micro creators on engagement, clicks and sales from their unique codes or links. Mixing the two goals into one number hides what is actually working.</p>
<p>Planning a campaign and not sure about the mix? <a href="/contact">Tell us about your goals</a> and we will suggest a creator plan that fits your budget.</p>',
        ],
        [
            'title' => 'The Brand Brief Checklist: 9 Things Creators Need Before They Start',
            'slug' => 'influencer-campaign-brief-checklist',
            'category' => 'influencer-marketing',
            'keyword' => 'influencer campaign brief',
            'meta_title' => 'Influencer Campaign Brief Checklist: 9 Must-Have Items',
            'meta_description' => 'Write an influencer campaign brief creators love. A 9-point checklist covering goals, audience, key messages, deliverables, timelines, approvals and usage rights.',
            'excerpt' => 'A clear brief is the fastest way to better creator content. Use this nine point checklist before you send your next campaign to creators.',
            'cover' => 'https://picsum.photos/id/625/1600/900',
            'cover_alt' => 'Bright room with a wooden table ready for a planning session',
            'content' => '<p>Most disappointing creator content does not come from bad creators. It comes from a vague brief. A strong <strong>influencer campaign brief</strong> gives creators enough direction to stay on message and enough freedom to make content their audience actually wants to watch.</p>
<h2>1. One clear goal</h2>
<p>Pick a single primary goal: awareness, consideration or sales. It decides the format, the call to action and how you will measure success.</p>
<h2>2. Who you want to reach</h2>
<p>Describe your audience in plain language, including age range, location and what they care about. Creators know their followers and will tell you honestly if it is a fit.</p>
<h2>3. Three key messages, maximum</h2>
<p>List what the content must communicate. More than three messages turns a post into an advert, and audiences scroll past adverts.</p>
<h2>4. Deliverables, spelled out</h2>
<p>State the exact format and number of pieces, for example one 30 to 45 second reel and three stories with a link sticker.</p>
<h2>5. Do and do not list</h2>
<p>Include must-use hashtags, disclosure requirements and any claims the creator should avoid. Keep it short.</p>
<h2>6. Timeline with buffer</h2>
<p>Give dates for draft, feedback, final approval and posting. Leave at least two working days for each review round.</p>
<h2>7. Approval process</h2>
<p>Say who approves content and how many revision rounds are included. Unlimited revisions slow everyone down.</p>
<h2>8. Usage rights and exclusivity</h2>
<p>If you want to reuse the content in ads or on your website, say so up front and budget for it. Surprises here damage relationships.</p>
<h2>9. How you will measure results</h2>
<p>Share the metrics you will ask for, such as reach, views, saves, clicks or code redemptions, so creators can capture screenshots before stories expire.</p>
<blockquote>The best briefs fit on one page. If yours needs five, the campaign probably needs a clearer goal.</blockquote>
<p>Peakilo writes and manages briefs for every campaign we run. <a href="/brands">See how we work with brands</a>.</p>',
        ],
    ];
}
