<?php
/**
 * Blog + SEO files (sitemap.xml, robots.txt, feed.xml).
 */

function blog_base_select()
{
    return "SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id = p.category_id";
}

function blog_index($params = [], $category = null)
{
    $page = max(1, (int) ($params['num'] ?? query('page', 1)));
    $search = mb_substr(query('q'), 0, 80);
    $per = 9;

    $where = ["p.status = 'published'", 'p.published_at <= ?'];
    $args = [now()];
    if ($category) {
        $where[] = 'p.category_id = ?';
        $args[] = $category['id'];
    }
    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
        $like = '%' . $search . '%';
        array_push($args, $like, $like, $like);
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $total = (int) val('SELECT COUNT(*) FROM posts p' . $whereSql, $args);
    $pg = paginate($total, $per, $page);
    if ($page > 1 && $page > $pg['pages']) {
        not_found();
    }
    $posts = all(blog_base_select() . $whereSql . ' ORDER BY p.featured DESC, p.published_at DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $pg['offset'], $args);

    $categories = all("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id AND p.status = 'published') AS post_count FROM categories c ORDER BY c.name");

    $basePath = $category ? 'blog/category/' . $category['slug'] : 'blog';
    $pageUrl = function ($n) use ($basePath, $category) {
        if ($category) return abs_url($basePath) . ($n > 1 ? '?page=' . $n : '');
        return abs_url($n > 1 ? 'blog/page/' . $n : 'blog');
    };

    $title = $category ? $category['name'] : 'Blog';
    $desc = $category
        ? ($category['description'] ?: 'Articles about ' . $category['name'] . ' from the Peakilo team.')
        : 'Guides on influencer marketing, creator rates, campaign briefs and growth from the Peakilo team.';

    $crumbs = ['Home' => '', 'Blog' => 'blog'];
    if ($category) $crumbs[$category['name']] = $basePath;

    render('blog/index', compact('posts', 'categories', 'category', 'search', 'pg', 'basePath'), [
        'title' => $title . ($pg['page'] > 1 ? ' - Page ' . $pg['page'] : ''),
        'description' => $desc,
        'canonical' => $pageUrl($pg['page']),
        'prev' => $pg['page'] > 1 ? $pageUrl($pg['page'] - 1) : null,
        'next' => $pg['page'] < $pg['pages'] ? $pageUrl($pg['page'] + 1) : null,
        'robots' => $search !== '' ? 'noindex, follow' : null,
        'schema' => [schema_breadcrumbs($crumbs), [
            '@type' => 'Blog',
            'name' => setting('site_name', 'Peakilo') . ' Blog',
            'url' => abs_url('blog'),
            'publisher' => ['@id' => abs_url('#organization')],
        ]],
    ]);
}

function blog_category($params)
{
    $cat = one('SELECT * FROM categories WHERE slug = ?', [$params['slug']]);
    if (!$cat) {
        not_found();
    }
    $params['num'] = query('page', 1);
    blog_index($params, $cat);
}

function blog_post($params)
{
    $post = one(blog_base_select() . " WHERE p.slug = ?", [$params['slug']]);
    $isPreview = $post && $post['status'] !== 'published' && admin_user();
    if (!$post || ($post['status'] !== 'published' && !$isPreview) || (!$isPreview && strtotime($post['published_at']) > time())) {
        not_found();
    }

    if (!$isPreview) {
        $key = 'viewed_' . $post['id'];
        if (empty($_SESSION[$key])) {
            q('UPDATE posts SET views = views + 1 WHERE id = ?', [$post['id']]);
            $_SESSION[$key] = 1;
        }
    }

    list($html, $toc) = build_toc($post['content']);

    $related = all(blog_base_select() . " WHERE p.status = 'published' AND p.id <> ? AND p.published_at <= ? ORDER BY CASE WHEN p.category_id = ? THEN 0 ELSE 1 END, p.published_at DESC LIMIT 3", [$post['id'], now(), (int) $post['category_id']]);
    $prev = one("SELECT title, slug FROM posts WHERE status = 'published' AND published_at < ? ORDER BY published_at DESC LIMIT 1", [$post['published_at']]);
    $next = one("SELECT title, slug FROM posts WHERE status = 'published' AND published_at > ? AND published_at <= ? ORDER BY published_at ASC LIMIT 1", [$post['published_at'], now()]);

    $crumbs = ['Home' => '', 'Blog' => 'blog'];
    if ($post['category_slug']) $crumbs[$post['category_name']] = 'blog/category/' . $post['category_slug'];
    $crumbs[$post['title']] = 'blog/' . $post['slug'];

    render('blog/post', compact('post', 'html', 'toc', 'related', 'prev', 'next', 'isPreview'), [
        'title' => $post['meta_title'] ?: $post['title'],
        'description' => $post['meta_description'] ?: ($post['excerpt'] ?: str_limit($post['content'], 160)),
        'canonical' => abs_url('blog/' . $post['slug']),
        'image' => $post['cover_image'] ? (preg_match('#^https?://#', $post['cover_image']) ? $post['cover_image'] : abs_url($post['cover_image'])) : null,
        'type' => 'article',
        'published' => $post['published_at'],
        'modified' => $post['updated_at'],
        'robots' => $isPreview ? 'noindex, nofollow' : null,
        'schema' => [schema_article($post), schema_breadcrumbs($crumbs)],
    ]);
}

function seo_sitemap()
{
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [];
    $static = ['' => '1.0', 'services' => '0.9', 'brands' => '0.9', 'creators' => '0.9', 'join' => '0.8', 'about' => '0.7', 'contact' => '0.7', 'blog' => '0.8', 'privacy' => '0.3', 'terms' => '0.3'];
    $latest = val("SELECT MAX(updated_at) FROM posts WHERE status = 'published'");
    foreach ($static as $path => $prio) {
        $urls[] = ['loc' => abs_url($path), 'lastmod' => $path === 'blog' && $latest ? $latest : null, 'priority' => $prio];
    }
    $urls[] = ['loc' => abs_url('influencers'), 'lastmod' => null, 'priority' => '0.8'];
    $urls[] = ['loc' => abs_url('talent'), 'lastmod' => null, 'priority' => '0.8'];
    foreach (all('SELECT id, updated_at FROM influencers WHERE ' . roster_where()) as $r) {
        $urls[] = ['loc' => abs_url('talent/' . creator_code($r['id'])), 'lastmod' => $r['updated_at'], 'priority' => '0.5'];
    }
    foreach (array_keys(niche_catalog()) as $slug) {
        $urls[] = ['loc' => abs_url('influencers/' . $slug), 'lastmod' => null, 'priority' => '0.7'];
    }
    foreach (all("SELECT c.slug, MAX(p.updated_at) AS lastmod FROM categories c JOIN posts p ON p.category_id = c.id AND p.status = 'published' GROUP BY c.slug") as $c) {
        $urls[] = ['loc' => abs_url('blog/category/' . $c['slug']), 'lastmod' => $c['lastmod'], 'priority' => '0.6'];
    }
    foreach (all("SELECT slug, updated_at, cover_image, title FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC", [now()]) as $p) {
        $urls[] = ['loc' => abs_url('blog/' . $p['slug']), 'lastmod' => $p['updated_at'], 'priority' => '0.7', 'image' => $p['cover_image'], 'title' => $p['title']];
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    foreach ($urls as $u) {
        echo "  <url>\n    <loc>" . e($u['loc']) . "</loc>\n";
        if (!empty($u['lastmod'])) echo '    <lastmod>' . date('Y-m-d', strtotime($u['lastmod'])) . "</lastmod>\n";
        echo '    <priority>' . $u['priority'] . "</priority>\n";
        if (!empty($u['image'])) {
            $img = preg_match('#^https?://#', $u['image']) ? $u['image'] : abs_url($u['image']);
            echo "    <image:image><image:loc>" . e($img) . "</image:loc></image:image>\n";
        }
        echo "  </url>\n";
    }
    echo '</urlset>';
}

function seo_robots()
{
    header('Content-Type: text/plain; charset=utf-8');
    $private = ['/admin', '/install', '/c/', '/join/thanks', '/*?q=', '/*?ref='];
    echo "User-agent: *\n";
    echo "Allow: /\n";
    foreach ($private as $p) echo "Disallow: $p\n";
    echo "\n";
    // AI search and assistant crawlers (Admin > Settings > SEO and analytics).
    $bots = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot', 'CCBot', 'meta-externalagent'];
    $allowAi = setting('ai_crawlers', '1') === '1';
    foreach ($bots as $b) {
        echo "User-agent: $b\n";
        if ($allowAi) {
            echo "Allow: /\n";
            foreach ($private as $p) echo "Disallow: $p\n";
        } else {
            echo "Disallow: /\n";
        }
        echo "\n";
    }
    echo 'Sitemap: ' . abs_url('sitemap.xml') . "\n";
    echo '# Summary for AI assistants: ' . abs_url('llms.txt') . "\n";
}

function seo_feed()
{
    header('Content-Type: application/rss+xml; charset=utf-8');
    $posts = all(blog_base_select() . " WHERE p.status = 'published' AND p.published_at <= ? ORDER BY p.published_at DESC LIMIT 20", [now()]);
    $site = setting('site_name', 'Peakilo');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n<channel>\n";
    echo '  <title>' . e($site) . " Blog</title>\n";
    echo '  <link>' . e(abs_url('blog')) . "</link>\n";
    echo '  <description>' . e(setting('meta_description')) . "</description>\n";
    echo "  <language>en</language>\n";
    echo '  <atom:link href="' . e(abs_url('feed.xml')) . '" rel="self" type="application/rss+xml"/>' . "\n";
    foreach ($posts as $p) {
        echo "  <item>\n";
        echo '    <title>' . e($p['title']) . "</title>\n";
        echo '    <link>' . e(abs_url('blog/' . $p['slug'])) . "</link>\n";
        echo '    <guid isPermaLink="true">' . e(abs_url('blog/' . $p['slug'])) . "</guid>\n";
        echo '    <pubDate>' . date(DATE_RSS, strtotime($p['published_at'])) . "</pubDate>\n";
        if ($p['category_name']) echo '    <category>' . e($p['category_name']) . "</category>\n";
        echo '    <description>' . e($p['excerpt'] ?: str_limit($p['content'], 200)) . "</description>\n";
        echo '    <content:encoded><![CDATA[' . str_replace(']]>', ']]&gt;', $p['content']) . "]]></content:encoded>\n";
        echo "  </item>\n";
    }
    echo "</channel>\n</rss>";
}

/* ---------------- llms.txt: a plain summary of the site for AI assistants ---------------- */

function llms_clean($text)
{
    $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function llms_body($full)
{
    $site = setting('site_name', 'Peakilo');
    $out = [];
    $out[] = '# ' . $site;
    $out[] = '';
    $out[] = '> ' . llms_clean(setting('meta_description', 'Peakilo is an influencer marketing agency that matches brands with vetted creators and runs campaigns end to end, from brief to final report.'));
    $out[] = '';
    $out[] = $site . ' is an influencer marketing agency based in India that works with brands worldwide. It plans campaigns, shortlists vetted creators, manages briefs, approvals and creator payments, and reports on results.';
    $out[] = '';
    $out[] = '- Platforms: ' . platform_list_text() . ', plus UGC (creator-made content for ads).';
    $out[] = '- Brands: send a brief at ' . abs_url('contact') . ' or shortlist creators at ' . abs_url('talent') . '. The team replies within one business day.';
    $out[] = '- Creators: joining is free at ' . abs_url('join') . '. Creators set their own rates and never pay anything.';
    $out[] = '- Payments: nothing is paid online. Peakilo agrees payment terms with the brand and pays creators on the agreed date.';
    $out[] = '- Main currency: ' . setting('default_currency', 'INR') . '. Visitors abroad see prices in their own currency.';
    if ($email = setting('contact_email')) $out[] = '- Contact: ' . $email;
    $out[] = '';

    $out[] = '## Main pages';
    $out[] = '';
    $pages = [
        '' => ['Home', 'Overview, niche matchmaker, services, process and FAQ.'],
        'services' => ['Services', 'Influencer campaigns, creator sourcing, UGC production, paid amplification and reporting.'],
        'brands' => ['For brands', 'How Peakilo runs campaigns for brands, from brief to final report.'],
        'creators' => ['For creators', 'How creators get paid brand campaigns through Peakilo.'],
        'talent' => ['Find talent', 'Browse vetted creators by niche, platform and size, and request a quote.'],
        'influencers' => ['Influencer niches', 'Every niche Peakilo works in, with creator counts.'],
        'join' => ['Join as a creator', 'Free creator sign-up: channels, audience and rates in three steps.'],
        'contact' => ['Start a campaign', 'Brief builder for brands: goal, niches, platforms and budget.'],
        'about' => ['About', 'Who runs Peakilo and how the agency works.'],
        'blog' => ['Blog', 'Guides on influencer marketing for brands and creator growth.'],
    ];
    foreach ($pages as $path => $p) {
        $out[] = '- [' . $p[0] . '](' . abs_url($path) . '): ' . $p[1];
    }
    $out[] = '';

    $out[] = '## Influencer niches';
    $out[] = '';
    foreach (niche_catalog() as $slug => $n) {
        $out[] = '- [' . $n['name'] . ' influencer marketing](' . abs_url('influencers/' . $slug) . '): ' . llms_clean($n['blurb']);
    }
    $out[] = '';

    $posts = all("SELECT title, slug, excerpt, content, meta_description, published_at FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC" . ($full ? '' : ' LIMIT 30'), [now()]);
    if ($posts) {
        $out[] = '## Blog';
        $out[] = '';
        foreach ($posts as $p) {
            $out[] = '- [' . llms_clean($p['title']) . '](' . abs_url('blog/' . $p['slug']) . '): ' . str_limit(llms_clean($p['meta_description'] ?: $p['excerpt'] ?: $p['content']), 200);
        }
        $out[] = '';
    }

    $out[] = '## Frequently asked questions';
    $out[] = '';
    foreach (home_faqs() as $f) {
        $out[] = '### ' . llms_clean($f['q']);
        $out[] = llms_clean($f['a']);
        $out[] = '';
    }

    if ($full && $posts) {
        $out[] = '## Blog articles (full text)';
        $out[] = '';
        foreach ($posts as $p) {
            $out[] = '### ' . llms_clean($p['title']);
            $out[] = 'URL: ' . abs_url('blog/' . $p['slug']) . ' | Published: ' . date('Y-m-d', strtotime($p['published_at']));
            $out[] = '';
            $html = preg_replace('#</(p|h[1-6]|li|blockquote)>#i', "\n\n", (string) $p['content']);
            $html = preg_replace('#<li[^>]*>#i', '- ', $html);
            $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace("/[ \t]+/", ' ', $text);
            $out[] = trim(preg_replace("/\n\s*\n+/", "\n\n", $text));
            $out[] = '';
        }
    }

    $out[] = '## Optional';
    $out[] = '';
    if (!$full) $out[] = '- [Full text for AI assistants](' . abs_url('llms-full.txt') . '): every blog article in plain text.';
    $out[] = '- [Sitemap](' . abs_url('sitemap.xml') . ')';
    $out[] = '- [Blog RSS feed](' . abs_url('feed.xml') . ')';
    $out[] = '- [Privacy policy](' . abs_url('privacy') . ')';
    $out[] = '- [Terms](' . abs_url('terms') . ')';
    return implode("\n", $out) . "\n";
}

function seo_llms()
{
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    echo llms_body(false);
}

function seo_llms_full()
{
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    echo llms_body(true);
}
