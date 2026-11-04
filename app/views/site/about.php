<section class="page-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>About</span></nav>
        <h1 class="h1" data-reveal>We help good creators and good brands find each other.</h1>
        <p class="lead" data-reveal style="--i:1">Peakilo is an influencer marketing agency built on two promises: brands get campaigns that move real numbers, and creators get treated like the professionals they are.</p>
    </div>
</section>

<section class="section-tight">
    <div class="container split">
        <div class="split-media" data-reveal><img src="<?= e(site_image('about')) ?>" alt="Creator portrait by the sea" loading="lazy" width="1100" height="880"></div>
        <div>
            <h2 class="h2" data-reveal>Why we started Peakilo.</h2>
            <div class="prose" style="margin-top:20px;--i:1" data-reveal>
                <p>Influencer marketing got big fast, and a lot of it got messy. Brands paid for followers who never bought anything. Creators chased late invoices and briefs that changed every week.</p>
                <p>We built Peakilo to fix the middle. We vet every creator, write briefs people can act on, agree rates up front and report honestly on what worked. The name says the goal: help every campaign and every creator reach their peak.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="h2" style="max-width:18ch;margin-bottom:48px" data-reveal>What we stand for.</h2>
        <div class="value-cards">
            <article data-reveal><i class="ph-bold ph-scales"></i><h3>Fair to creators</h3><p>We work from each creator's own rate range, keep briefs reasonable and pay on the dates we agree.</p></article>
            <article data-reveal style="--i:1"><i class="ph-bold ph-target"></i><h3>Honest with brands</h3><p>We tell you what your budget can realistically deliver, and we report the results, good or bad.</p></article>
            <article data-reveal style="--i:2"><i class="ph-bold ph-shield-check"></i><h3>Careful with data</h3><p>Creator details stay private. We only share profiles with brands for campaigns the creator applied to.</p></article>
        </div>
    </div>
</section>

<?php partial('stats_band'); ?>

<section class="section">
    <div class="container">
        <div class="cta-block" data-reveal="scale">
            <div class="cta-chevs" aria-hidden="true"><?= mark_svg('') ?><?= mark_svg('') ?></div>
            <h2>Let's build something worth sharing.</h2>
            <div>
                <p>Whether you are a brand or a creator, the first conversation is free.</p>
                <div class="ctas">
                    <a class="btn btn-light btn-lg" href="<?= e(url('contact?type=brand')) ?>">Start a campaign <i class="ph-bold ph-arrow-up-right"></i></a>
                    <a class="btn btn-on-red btn-lg" href="<?= e(url('join')) ?>">Join as a creator</a>
                </div>
            </div>
        </div>
    </div>
</section>
