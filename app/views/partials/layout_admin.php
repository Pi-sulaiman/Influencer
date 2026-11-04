<?php
/** @var array $meta @var string $content @var string $pageTitle */
$me = admin_user();
$counts = admin_counts();
$nav = [
    ['admin', 'ph-squares-four', 'Dashboard', null],
    ['admin/influencers', 'ph-users-three', 'Influencers', $counts['new_influencers']],
    ['admin/campaigns', 'ph-megaphone', 'Campaigns', null],
];
$content2 = [
    ['admin/posts', 'ph-article', 'Blog posts', null],
    ['admin/categories', 'ph-folders', 'Categories', null],
    ['admin/content', 'ph-images-square', 'Site content', null],
];
$inbox = [
    ['admin/messages', 'ph-envelope-simple', 'Messages', $counts['unread']],
    ['admin/subscribers', 'ph-at', 'Subscribers', null],
    ['admin/settings', 'ph-gear-six', 'Settings', null],
];
$path = request_path();
$isActive = function ($p) use ($path) {
    $p = '/' . $p;
    return $p === '/admin' ? $path === '/admin' : ($path === $p || str_starts_with($path, $p . '/'));
};
$renderLinks = function ($items) use ($isActive) {
    foreach ($items as $it) {
        echo '<a href="' . e(url($it[0])) . '"' . ($isActive($it[0]) ? ' class="is-active" aria-current="page"' : '') . '><i class="ph ' . e($it[1]) . '" aria-hidden="true"></i>' . e($it[2]);
        if ($it[3]) echo '<span class="count">' . (int) $it[3] . '</span>';
        echo '</a>';
    }
};
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($meta['full_title']) ?></title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<aside class="sidebar" id="sidebar">
    <a class="sb-logo" href="<?= e(url('admin')) ?>"><?= mark_svg('') ?><b>peakilo</b><span>Admin</span></a>
    <nav class="sb-nav" aria-label="Admin">
        <?php $renderLinks($nav); ?>
        <div class="sb-label">Content</div>
        <?php $renderLinks($content2); ?>
        <div class="sb-label">Inbox and setup</div>
        <?php $renderLinks($inbox); ?>
    </nav>
    <div class="sb-foot">
        <a href="<?= e(url('')) ?>" target="_blank" rel="noopener"><i class="ph ph-arrow-square-out"></i>View website</a>
        <form method="post" action="<?= e(url('admin/logout')) ?>"><?= csrf_field() ?><button type="submit"><i class="ph ph-sign-out"></i>Sign out</button></form>
        <div class="sb-user"><span class="avatar"><?= e(initials($me['name'])) ?></span><div><b><?= e($me['name']) ?></b><small><?= e($me['email']) ?></small></div></div>
    </div>
</aside>
<div class="scrim" data-scrim></div>

<div class="main">
    <header class="topbar">
        <button class="icon-btn sb-toggle" type="button" data-sb-toggle aria-label="Open menu" aria-controls="sidebar"><i class="ph ph-list"></i></button>
        <h1><?= e($pageTitle) ?></h1>
        <form class="top-search" action="<?= e(url('admin/influencers')) ?>" method="get" role="search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="top-q">Search influencers</label>
            <input id="top-q" type="search" name="q" placeholder="Search influencers by name, email or handle" value="<?= e(current_url_is('admin/influencers') ? query('q') : '') ?>">
        </form>
    </header>
    <div class="content">
        <?php if (demo_on() && !current_url_is('admin/settings')): ?>
            <div class="demo-strip" role="note"><i class="ph-bold ph-flask"></i><span><b>Demo mode is on.</b> The website shows sample creators and example numbers. Your real data below is not affected.</span><a href="<?= e(url('admin/settings#demo')) ?>">Turn off</a></div>
        <?php endif; ?>
        <?php foreach (flashes() as $f): ?>
            <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'error' ?>" role="status"><i class="ph-fill <?= $f['type'] === 'success' ? 'ph-check-circle' : 'ph-warning-circle' ?>"></i><span><?= e($f['message']) ?></span></div>
        <?php endforeach; ?>
        <?= $content ?>
    </div>
</div>
<div class="toast" role="status" aria-live="polite" data-toast><i class="ph-fill ph-check-circle"></i><span></span></div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= e(asset($s)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
