<?php /** @var array $faqs */ ?>
<section class="page-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>For brands</span></nav>
        <h1 class="h1" data-reveal>Creator campaigns that earn attention.</h1>
        <p class="lead" data-reveal style="--i:1">We find creators your customers already trust, manage every detail and show you exactly what the campaign delivered.</p>
        <div class="ctas" data-reveal style="--i:2">
            <a class="btn btn-primary btn-lg" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Start a campaign <i class="ph-bold ph-arrow-up-right"></i></a>
            <a class="btn btn-outline btn-lg" href="<?= e(url('#estimate')) ?>">Estimate your reach</a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="feature-grid">
            <div class="fg" data-reveal><span class="ic"><i class="ph-bold ph-target"></i></span><div><h3>Matched on audience</h3><p>We look at who follows a creator, where they live and how they engage, then match that to your customer.</p></div></div>
            <div class="fg" data-reveal style="--i:1"><span class="ic"><i class="ph-bold ph-shield-check"></i></span><div><h3>Vetted for quality</h3><p>Every creator is checked for fake followers, content standards and brand safety before you see their profile.</p></div></div>
            <div class="fg" data-reveal><span class="ic"><i class="ph-bold ph-file-text"></i></span><div><h3>Managed end to end</h3><p>Briefs, contracts, drafts, approvals, posting schedules and payments. You approve, we handle the rest.</p></div></div>
            <div class="fg" data-reveal style="--i:1"><span class="ic"><i class="ph-bold ph-chart-line-up"></i></span><div><h3>Measured honestly</h3><p>Tracked links and codes, platform insights and a wrap report that tells you what to repeat and what to drop.</p></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split">
        <div class="split-media" data-reveal><img src="<?= e(site_image('brands')) ?>" alt="Busy city crowd, the audience a campaign needs to reach" loading="lazy" width="1100" height="880"></div>
        <div>
            <h2 class="h2" data-reveal>What you get with every campaign.</h2>
            <ul class="check-list" data-reveal>
                <li><i class="ph-fill ph-check-circle"></i>A dedicated campaign manager and one weekly update</li>
                <li><i class="ph-fill ph-check-circle"></i>A creator shortlist with audience data for each profile</li>
                <li><i class="ph-fill ph-check-circle"></i>Content approval before anything goes live</li>
                <li><i class="ph-fill ph-check-circle"></i>Usage rights handled in every creator contract</li>
                <li><i class="ph-fill ph-check-circle"></i>A final report with results and recommendations</li>
            </ul>
            <a class="btn btn-dark" style="margin-top:32px" href="<?= e(url('contact?type=brand')) ?>">Start a campaign <i class="ph-bold ph-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container faq-grid">
        <div class="faq-intro">
            <h2 class="h2" data-reveal>Brand questions.</h2>
            <p class="lead" data-reveal>Anything else? Our team replies within one business day.</p>
        </div>
        <div class="faq-list" data-faq>
            <?php foreach ($faqs as $i => $f): ?>
                <details class="faq-item" data-reveal style="--i:<?= $i ?>"<?= $i === 0 ? ' open' : '' ?>>
                    <summary><?= e($f['q']) ?> <span class="pm" aria-hidden="true"><i class="ph-bold ph-plus"></i></span></summary>
                    <div class="faq-body"><p><?= e($f['a']) ?></p></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
