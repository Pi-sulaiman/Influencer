<?php /** @var array $catalog @var array $counts */ ?>
<section class="page-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>Niches</span></nav>
        <h1 class="h1" data-split>Influencer marketing for every niche.</h1>
        <p class="lead">Pick your industry to see how we run campaigns there, which formats work and the vetted creators ready to match.</p>
    </div>
</section>
<section class="section-tight" style="padding-top:0">
    <div class="container">
        <div class="niche-grid">
            <?php foreach ($catalog as $slug => $n): $cnt = $counts[$n['name']]['any'] ?? 0; ?>
                <a class="niche-tile" href="<?= e(url('influencers/' . $slug)) ?>" data-spotlight>
                    <span class="nt-ic"><i class="ph-bold <?= e($n['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="nt-name"><?= e($n['name']) ?> influencers</span>
                    <span class="nt-meta"><?= $cnt ? (int) $cnt . ' vetted creator' . ($cnt > 1 ? 's' : '') : 'Influencer marketing' ?></span>
                    <i class="ph-bold ph-arrow-up-right nt-arrow" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="cta-block">
            <div class="cta-chevs" aria-hidden="true"><?= mark_svg('') ?><?= mark_svg('') ?></div>
            <h2 data-split>Not sure which niche fits?</h2>
            <div>
                <p>Tell us about your product and customer. We will suggest the creator mix that reaches them.</p>
                <div class="ctas"><a class="btn btn-light btn-lg" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Start a campaign <i class="ph-bold ph-arrow-up-right"></i></a></div>
            </div>
        </div>
    </div>
</section>
