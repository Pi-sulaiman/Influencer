<section class="page-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>For creators</span></nav>
        <h1 class="h1" data-reveal>Get paid for content you are proud of.</h1>
        <p class="lead" data-reveal style="--i:1">Join the Peakilo creator network for free. Share your channels and rate range once, and we will match you with brand campaigns that fit.</p>
        <div class="ctas" data-reveal style="--i:2">
            <a class="btn btn-primary btn-lg" href="<?= e(url('join')) ?>" data-magnetic>Join as a creator <i class="ph-bold ph-arrow-up-right"></i></a>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="value-cards">
            <article data-reveal><i class="ph-bold ph-user-circle-plus"></i><h3>1. Create your profile</h3><p>Add your photo, channels, audience size, niches and your minimum and maximum fee. It takes about five minutes.</p></article>
            <article data-reveal style="--i:1"><i class="ph-bold ph-envelope-open"></i><h3>2. Get matched</h3><p>When a brand campaign fits your audience, we send you the brief, the deliverables and the budget before you commit.</p></article>
            <article data-reveal style="--i:2"><i class="ph-bold ph-hand-coins"></i><h3>3. Create and get paid</h3><p>Make the content in your own voice, get it approved, post it and get paid on the agreed date.</p></article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split reverse">
        <div class="split-media" data-reveal><img src="<?= e(site_image('creators')) ?>" alt="Creator outdoors in a winter jacket" loading="lazy" width="1100" height="880"></div>
        <div>
            <h2 class="h2" data-reveal>Why creators work with us.</h2>
            <ul class="check-list" data-reveal>
                <li><i class="ph-fill ph-check-circle"></i>No joining fees and no exclusivity to join the network</li>
                <li><i class="ph-fill ph-check-circle"></i>You see the full brief and budget before saying yes</li>
                <li><i class="ph-fill ph-check-circle"></i>Reasonable revision limits written into every deal</li>
                <li><i class="ph-fill ph-check-circle"></i>Payment dates agreed up front and kept</li>
                <li><i class="ph-fill ph-check-circle"></i>Your details are only shared for campaigns you apply to</li>
            </ul>
            <a class="btn btn-dark" style="margin-top:32px" href="<?= e(url('join')) ?>">Join as a creator <i class="ph-bold ph-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="cta-block" data-reveal="scale">
            <div class="cta-chevs" aria-hidden="true"><?= mark_svg('') ?><?= mark_svg('') ?></div>
            <h2>Your next brand deal starts here.</h2>
            <div>
                <p>It is free, it takes five minutes and you stay in control of your rates.</p>
                <div class="ctas">
                    <a class="btn btn-light btn-lg" href="<?= e(url('join')) ?>" data-magnetic>Join as a creator <i class="ph-bold ph-arrow-up-right"></i></a>
                    <a class="btn btn-on-red btn-lg" href="<?= e(url('blog/category/creator-growth')) ?>">Read creator guides</a>
                </div>
            </div>
        </div>
    </div>
</section>
