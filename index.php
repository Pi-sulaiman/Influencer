<?php
/**
 * Peakilo front controller. Every request (except real files) lands here.
 */

// PHP built-in server (local testing): serve real static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (preg_match('#/(app|storage)/|config(\.local)?\.php$#', $file)) {
        http_response_code(403);
        exit('Forbidden');
    }
    if ($file !== __DIR__ . '/' && is_file($file) && substr($file, -4) !== '.php') {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';

$routes = [
    // Public site
    ['GET',      '/',                         'site_home'],
    ['GET',      '/about',                    'site_about'],
    ['GET',      '/services',                 'site_services'],
    ['GET',      '/brands',                   'site_brands'],
    ['GET',      '/creators',                 'site_creators'],
    ['GET|POST', '/contact',                  'site_contact'],
    ['POST',     '/subscribe',                'site_subscribe'],
    ['GET',      '/privacy',                  'site_privacy'],
    ['GET',      '/terms',                    'site_terms'],
    ['GET',      '/influencers',              'site_niches'],
    ['GET',      '/influencers/{slug}',       'site_niche'],
    ['GET',      '/talent',                   'site_talent'],
    ['GET',      '/talent/{code}',            'site_talent_profile'],

    // Blog + SEO files
    ['GET',      '/blog',                     'blog_index'],
    ['GET',      '/blog/page/{num}',          'blog_index'],
    ['GET',      '/blog/category/{slug}',     'blog_category'],
    ['GET',      '/blog/{slug}',              'blog_post'],
    ['GET',      '/sitemap.xml',              'seo_sitemap'],
    ['GET',      '/robots.txt',               'seo_robots'],
    ['GET',      '/feed.xml',                 'seo_feed'],
    ['GET',      '/llms.txt',                 'seo_llms'],
    ['GET',      '/llms-full.txt',            'seo_llms_full'],

    // Campaign links + influencer registration
    ['GET',      '/c/{code}',                 'campaign_link'],
    ['GET|POST', '/join',                     'join_form'],
    ['GET',      '/join/thanks',              'join_thanks'],

    // One-time installer
    ['GET|POST', '/install',                  'install_run'],

    // Admin
    ['GET|POST', '/admin/login',              'admin_login'],
    ['POST',     '/admin/logout',             'admin_logout'],
    ['GET',      '/admin',                    'admin_dashboard'],
    ['GET',      '/admin/influencers',        'admin_influencers'],
    ['GET',      '/admin/influencers/export', 'admin_influencers_export'],
    ['POST',     '/admin/influencers/bulk',   'admin_influencers_bulk'],
    ['GET|POST', '/admin/influencers/{id}',   'admin_influencer_view'],
    ['POST',     '/admin/influencers/{id}/delete', 'admin_influencer_delete'],
    ['GET',      '/admin/campaigns',          'admin_campaigns'],
    ['GET|POST', '/admin/campaigns/new',      'admin_campaign_form'],
    ['GET|POST', '/admin/campaigns/{id}/edit','admin_campaign_form'],
    ['GET',      '/admin/campaigns/{id}/export', 'admin_campaign_export'],
    ['POST',     '/admin/campaigns/{id}/delete', 'admin_campaign_delete'],
    ['GET',      '/admin/campaigns/{id}',     'admin_campaign_view'],
    ['POST',     '/admin/applications/{id}',  'admin_application_update'],
    ['GET',      '/admin/posts',              'admin_posts'],
    ['GET|POST', '/admin/posts/new',          'admin_post_form'],
    ['GET|POST', '/admin/posts/{id}/edit',    'admin_post_form'],
    ['POST',     '/admin/posts/{id}/delete',  'admin_post_delete'],
    ['GET|POST', '/admin/categories',         'admin_categories'],
    ['POST',     '/admin/upload',             'admin_upload'],
    ['GET',      '/admin/messages',           'admin_messages'],
    ['GET',      '/admin/messages/{id}',      'admin_message_view'],
    ['POST',     '/admin/messages/{id}/delete', 'admin_message_delete'],
    ['GET',      '/admin/subscribers',        'admin_subscribers'],
    ['GET',      '/admin/subscribers/export', 'admin_subscribers_export'],
    ['GET|POST', '/admin/settings',           'admin_settings'],
    ['GET|POST', '/admin/content',            'admin_content'],
];

dispatch($routes);
