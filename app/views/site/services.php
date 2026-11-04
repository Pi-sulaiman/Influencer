<?php
$services = [
    ['ph-megaphone', 'Influencer campaigns', 'Full campaign management from first brief to final report.', ['Campaign strategy and KPIs', 'Creator sourcing and vetting', 'Briefs, contracts and approvals', 'Launch, tracking and wrap report']],
    ['ph-users-three', 'Creator sourcing', 'Need the right people for a launch? We build the shortlist for you.', ['Audience and niche matching', 'Engagement and fake follower checks', 'Brand safety review', 'Rate negotiation']],
    ['ph-video-camera', 'UGC production', 'Creator-made video and photos for ads, product pages and social.', ['Hooks and scripts that convert', 'Batches of ad-ready variations', 'Full usage rights options', 'Fast turnaround']],
    ['ph-star', 'Talent management', 'Long-term representation for creators ready to grow into a business.', ['Brand deal pipeline', 'Media kit and rate card', 'Contract and invoice handling', 'Growth strategy']],
    ['ph-lightning', 'Paid amplification', 'Turn your best creator posts into ads that keep performing.', ['Whitelisting and Spark Ads', 'Audience targeting', 'Creative testing', 'Budget optimisation']],
    ['ph-chart-bar', 'Analytics and reporting', 'Clear numbers and clear next steps after every campaign.', ['Live campaign dashboard', 'Reach, engagement and sales', 'Creator level breakdowns', 'Recommendations for next time']],
];
?>
<section class="page-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>Services</span></nav>
        <h1 class="h1" data-reveal>Influencer marketing services, done properly.</h1>
        <p class="lead" data-reveal style="--i:1">Pick a single service or hand us the whole campaign. Either way you get one team accountable for the result.</p>
        <div class="ctas" data-reveal style="--i:2">
            <a class="btn btn-primary btn-lg" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Start a campaign <i class="ph-bold ph-arrow-up-right"></i></a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container service-rows">
        <?php foreach ($services as $i => $s): ?>
            <article class="service-row" data-reveal style="--i:<?= $i % 3 ?>">
                <span class="ic"><i class="ph-bold <?= e($s[0]) ?>"></i></span>
                <div>
                    <h2 class="h3"><?= e($s[1]) ?></h2>
                    <p><?= e($s[2]) ?></p>
                </div>
                <ul>
                    <?php foreach ($s[3] as $item): ?><li><i class="ph-bold ph-check"></i><?= e($item) ?></li><?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="container split reverse">
        <div class="split-media" data-reveal><img src="<?= e(site_image('services')) ?>" alt="Creator holding a camera, ready to shoot" loading="lazy" width="1100" height="880"></div>
        <div>
            <h2 class="h2" data-reveal>Not sure which service you need?</h2>
            <p class="lead" style="margin-top:18px" data-reveal>Most brands start with one campaign and add UGC and paid amplification once they see which creators perform. Tell us your goal and budget and we will recommend a starting point.</p>
            <ul class="check-list" data-reveal>
                <li><i class="ph-fill ph-check-circle"></i>A free 30 minute strategy call</li>
                <li><i class="ph-fill ph-check-circle"></i>A sample creator shortlist for your niche</li>
                <li><i class="ph-fill ph-check-circle"></i>A clear quote with no hidden fees</li>
            </ul>
            <a class="btn btn-dark" style="margin-top:32px" href="<?= e(url('#estimate')) ?>">Estimate your reach <i class="ph-bold ph-arrow-right"></i></a>
        </div>
    </div>
</section>
