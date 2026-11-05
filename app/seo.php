<?php
/**
 * SEO helpers: page meta, Open Graph, Twitter cards and JSON-LD schema.
 */

function seo_meta(array $meta)
{
    $siteName = setting('site_name', 'Peakilo');
    $defaults = [
        'title'       => '',
        'description' => setting('meta_description', 'Peakilo is an influencer marketing agency that matches brands with vetted creators and runs campaigns end to end, from brief to final report.'),
        'canonical'   => abs_url(ltrim(request_path(), '/')),
        'image'       => abs_url('assets/img/og-default.png'),
        'type'        => 'website',
        'robots'      => 'index, follow, max-image-preview:large',
        'schema'      => [],
        'published'   => null,
        'modified'    => null,
        'body_class'  => '',
        'prev'        => null,
        'next'        => null,
    ];
    $meta = array_merge($defaults, array_filter($meta, function ($v) { return $v !== null && $v !== ''; }));
    $meta['full_title'] = $meta['title']
        ? $meta['title'] . ' | ' . $siteName
        : $siteName . ' | ' . setting('tagline', 'Influencer Marketing Agency');
    if (!empty($meta['raw_title'])) {
        $meta['full_title'] = $meta['raw_title'];
    }
    $meta['description'] = str_limit($meta['description'], 165);
    return $meta;
}

function render_meta(array $meta)
{
    $siteName = setting('site_name', 'Peakilo');
    $out = [];
    $out[] = '<title>' . e($meta['full_title']) . '</title>';
    $out[] = '<meta name="description" content="' . e($meta['description']) . '">';
    $out[] = '<meta name="robots" content="' . e($meta['robots']) . '">';
    $out[] = '<link rel="canonical" href="' . e($meta['canonical']) . '">';
    if ($meta['prev']) $out[] = '<link rel="prev" href="' . e($meta['prev']) . '">';
    if ($meta['next']) $out[] = '<link rel="next" href="' . e($meta['next']) . '">';
    $out[] = '<meta property="og:site_name" content="' . e($siteName) . '">';
    $out[] = '<meta property="og:type" content="' . e($meta['type']) . '">';
    $out[] = '<meta property="og:title" content="' . e($meta['title'] ?: $meta['full_title']) . '">';
    $out[] = '<meta property="og:description" content="' . e($meta['description']) . '">';
    $out[] = '<meta property="og:url" content="' . e($meta['canonical']) . '">';
    $out[] = '<meta property="og:image" content="' . e($meta['image']) . '">';
    if ($meta['image'] === abs_url('assets/img/og-default.png')) {
        $out[] = '<meta property="og:image:width" content="1200">';
        $out[] = '<meta property="og:image:height" content="630">';
        $out[] = '<meta property="og:image:alt" content="' . e(setting('site_name', 'Peakilo')) . ' influencer marketing agency">';
    }
    $out[] = '<meta property="og:locale" content="en_IN">';
    if ($meta['published']) $out[] = '<meta property="article:published_time" content="' . e(date('c', strtotime($meta['published']))) . '">';
    if ($meta['modified']) $out[] = '<meta property="article:modified_time" content="' . e(date('c', strtotime($meta['modified']))) . '">';
    $out[] = '<meta name="twitter:card" content="summary_large_image">';
    $out[] = '<meta name="twitter:title" content="' . e($meta['title'] ?: $meta['full_title']) . '">';
    $out[] = '<meta name="twitter:description" content="' . e($meta['description']) . '">';
    $out[] = '<meta name="twitter:image" content="' . e($meta['image']) . '">';
    if ($v = google_verification_code()) {
        $out[] = '<meta name="google-site-verification" content="' . e($v) . '">';
    }
    $out[] = '<link rel="alternate" type="text/plain" title="Site summary for AI assistants" href="' . e(abs_url('llms.txt')) . '">';

    $graph = array_merge([schema_organization(), schema_website()], $meta['schema']);
    $out[] = '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    echo implode("\n    ", $out) . "\n";
}

function schema_organization()
{
    $same = array_values(array_filter([
        setting('instagram_url'), setting('linkedin_url'), setting('youtube_url'), setting('tiktok_url'), setting('x_url'),
    ]));
    $org = [
        '@type' => 'Organization',
        '@id'   => abs_url('#organization'),
        'name'  => setting('site_name', 'Peakilo'),
        'url'   => abs_url(''),
        'logo'  => abs_url('assets/img/icon-512.png'),
        'description' => setting('meta_description', 'Influencer marketing agency.'),
    ];
    if ($same) $org['sameAs'] = $same;
    $org['slogan'] = 'Influencer campaigns that peak.';
    $org['areaServed'] = [['@type' => 'Country', 'name' => 'India'], 'Worldwide'];
    $org['knowsAbout'] = array_merge(['Influencer marketing', 'Creator marketing', 'UGC production'], array_map(function ($n) { return $n['name'] . ' influencer marketing'; }, array_slice(array_values(niche_catalog()), 0, 8)));
    if ($email = setting('contact_email')) {
        $org['contactPoint'] = ['@type' => 'ContactPoint', 'email' => $email, 'contactType' => 'customer support'];
        if ($phone = setting('contact_phone')) $org['contactPoint']['telephone'] = $phone;
    }
    return $org;
}

function schema_website()
{
    return [
        '@type' => 'WebSite',
        '@id'   => abs_url('#website'),
        'url'   => abs_url(''),
        'name'  => setting('site_name', 'Peakilo'),
        'publisher' => ['@id' => abs_url('#organization')],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => abs_url('blog') . '?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function schema_breadcrumbs(array $items)
{
    $list = [];
    $i = 1;
    foreach ($items as $name => $path) {
        $list[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $name, 'item' => abs_url($path)];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

function schema_article(array $post)
{
    $image = $post['cover_image'] ? abs_url($post['cover_image']) : abs_url('assets/img/og-default.png');
    return [
        '@type' => 'BlogPosting',
        '@id'   => abs_url('blog/' . $post['slug']) . '#article',
        'headline' => $post['title'],
        'description' => $post['meta_description'] ?: str_limit($post['excerpt'] ?: $post['content'], 160),
        'image' => [$image],
        'datePublished' => date('c', strtotime($post['published_at'] ?: $post['created_at'])),
        'dateModified'  => date('c', strtotime($post['updated_at'] ?: $post['created_at'])),
        'author' => ['@type' => 'Person', 'name' => $post['author_name'] ?: 'Peakilo Team'],
        'publisher' => ['@id' => abs_url('#organization')],
        'mainEntityOfPage' => abs_url('blog/' . $post['slug']),
        'wordCount' => str_word_count(strip_tags($post['content'])),
        'keywords' => $post['focus_keyword'] ?: null,
        'articleSection' => $post['category_name'] ?? null,
    ];
}

function schema_faq(array $faqs)
{
    $items = [];
    foreach ($faqs as $f) {
        $items[] = ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]];
    }
    return ['@type' => 'FAQPage', 'mainEntity' => $items];
}

function schema_service($name, $description)
{
    return [
        '@type' => 'Service',
        'serviceType' => $name,
        'description' => $description,
        'provider' => ['@id' => abs_url('#organization')],
        'areaServed' => 'Worldwide',
    ];
}

/** The Search Console code, whether it was pasted as the bare code, as
    "google-site-verification=CODE" (the DNS form) or as the whole meta tag. */
function google_verification_code()
{
    $v = trim((string) setting('google_site_verification'));
    if (preg_match('/content=["\']([^"\']+)["\']/i', $v, $m)) $v = $m[1];
    $v = preg_replace('/^google-site-verification\s*[=:]\s*/i', '', $v);
    return preg_match('/^[A-Za-z0-9_\-]{10,120}$/', $v) ? $v : '';
}
