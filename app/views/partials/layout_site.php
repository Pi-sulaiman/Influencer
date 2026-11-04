<?php
/** @var array $meta  @var string $content */
$siteName = setting('site_name', 'Peakilo');
$ref = $_COOKIE['pk_ref'] ?? '';
$refCampaign = null;
if ($ref && !current_url_is('join')) {
    $refCampaign = one("SELECT title, code FROM campaigns WHERE code = ? AND status = 'active'", [$ref]);
}
?><!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php $themeMode = in_array(setting('theme_mode', 'auto'), ['light', 'dark'], true) ? setting('theme_mode') : 'auto'; ?>
    <script>
        (function () {
            var d = document.documentElement, mode = <?= json_encode($themeMode) ?>;
            d.className = d.className.replace('no-js', 'js');
            if (mode !== 'auto') { d.setAttribute('data-theme', mode); return; }
            try {
                var t = localStorage.getItem('pk-theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                d.setAttribute('data-theme', t);
            } catch (e) { d.setAttribute('data-theme', 'light'); }
        })();
        window.PK_GEO = <?= json_encode(geo_js_config(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    </script>
    <?php render_meta($meta); ?>
    <meta name="theme-color" content="#FAF6F2" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0D1018" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="48x48">
    <link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e(url('site.webmanifest')) ?>">
    <link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> Blog" href="<?= e(abs_url('feed.xml')) ?>">
    <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
    <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&f[]=satoshi@401&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/motion.css')) ?>">
    <?php if (!empty($meta['styles'])) foreach ((array) $meta['styles'] as $st): ?>
    <link rel="stylesheet" href="<?= e(asset($st)) ?>">
    <?php endforeach; ?>
    <?php if ($ga = setting('ga_id')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
    <?php endif; ?>
</head>
<body class="<?= e($meta['body_class']) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="progress-bar" aria-hidden="true"></div>
<div id="top-sentinel" aria-hidden="true" style="position:absolute;top:0;left:0;height:40px;width:1px"></div>

<?php if ($refCampaign): ?>
<div class="ref-banner">
    <div class="container">
        <span>You are viewing Peakilo from the <b><?= e($refCampaign['title']) ?></b> campaign.</span>
        <a href="<?= e(url('join?c=' . rawurlencode($refCampaign['code']))) ?>">Apply now</a>
    </div>
</div>
<?php endif; ?>

<header class="nav" data-nav>
    <div class="container nav-inner">
        <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?> home">
            <?= mark_svg() ?><span class="logo-word">peakilo</span>
        </a>
        <nav class="nav-links" aria-label="Main">
            <?php foreach (nav_items() as $path => $label): ?>
                <a href="<?= e(url($path)) ?>"<?= current_url_is($path) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="nav-actions">
            <?php if ($themeMode === 'auto'): ?>
            <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Switch light or dark theme">
                <i class="ph ph-moon" aria-hidden="true"></i><i class="ph ph-sun" aria-hidden="true"></i>
            </button>
            <?php endif; ?>
            <a class="btn btn-ghost nav-join" href="<?= e(url('join')) ?>">Join as a creator</a>
            <a class="btn btn-primary nav-cta" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Start a campaign</a>
            <button class="icon-btn nav-burger" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu" data-burger>
                <span><span></span><span></span><span></span></span>
            </button>
        </div>
    </div>
</header>
<div class="mobile-menu" id="mobile-menu" data-mobile-menu aria-hidden="true">
    <?php foreach (nav_items() + ['contact' => 'Contact'] as $path => $label): ?>
        <a class="mm-link" href="<?= e(url($path)) ?>"><?= e($label) ?> <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
    <?php endforeach; ?>
    <div class="mm-ctas">
        <a class="btn btn-primary btn-lg" href="<?= e(url('contact?type=brand')) ?>">Start a campaign</a>
        <a class="btn btn-outline btn-lg" href="<?= e(url('join')) ?>">Join as a creator</a>
    </div>
</div>

<main id="main">
<?php foreach (flashes() as $f): ?>
    <div class="container" style="margin-top:16px"><div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'error' ?>" role="status"><i class="ph-fill <?= $f['type'] === 'success' ? 'ph-check-circle' : 'ph-warning-circle' ?>"></i><span><?= e($f['message']) ?></span></div></div>
<?php endforeach; ?>
<?= $content ?>
</main>

<?php partial('footer'); ?>

<div class="sl-tray" data-sl-tray data-base="<?= e(url('')) ?>" hidden>
    <div class="sl-panel" data-sl-panel id="sl-panel" hidden>
        <div class="sl-panel-head"><b>Your shortlist</b><button type="button" class="link-btn" data-sl-clearall>Clear all</button></div>
        <ul data-sl-items></ul>
        <p class="sl-note"><i class="ph-bold ph-shield-check"></i>Saved in this browser. Send it as one quote request, with nothing to pay online.</p>
    </div>
    <button type="button" class="sl-open" data-sl-toggle aria-expanded="false" aria-controls="sl-panel">
        <span class="sl-faces" data-sl-faces aria-hidden="true"></span>
        <span class="sl-text"><b data-sl-count>0 creators</b><small>in your shortlist</small></span>
        <i class="ph-bold ph-caret-up sl-caret" aria-hidden="true"></i>
    </button>
    <a class="btn btn-primary btn-sm sl-request" href="<?= e(url('contact?type=brand')) ?>" data-sl-request>Request quote <i class="ph-bold ph-arrow-up-right"></i></a>
</div>
<div class="toast" role="status" aria-live="polite" data-toast><i class="ph-fill ph-check-circle"></i><span></span></div>
<div class="grain" aria-hidden="true"></div>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/lenis@1.3.11/dist/lenis.min.js" defer></script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<script src="<?= e(asset('js/motion.js')) ?>" defer></script>
<script src="<?= e(asset('js/shortlist.js')) ?>" defer></script>
<?php if (!empty($meta['scripts'])) foreach ((array) $meta['scripts'] as $s): ?>
<script src="<?= e(asset($s)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
