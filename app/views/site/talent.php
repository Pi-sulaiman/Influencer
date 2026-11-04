<?php
/** @var array $f @var array $cards @var int $total @var array $tiers */
$platforms = platform_options();
$filtered = array_filter([$f['q'], $f['niche'], $f['platform'], $f['tier']]);
$shown = count(array_filter($cards, function ($c) { return $c['match']; }));
?>
<section class="page-hero talent-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><span>Find talent</span></nav>
        <h1 class="h1" data-split>Find creators your customers already follow.</h1>
        <p class="lead" data-reveal style="--i:1">Browse vetted creators, shortlist the ones you like and ask for a quote. We confirm availability and pricing within one business day. Nothing to pay online.</p>
        <ol class="how-book" data-reveal style="--i:2">
            <li><span><i class="ph-bold ph-heart"></i></span><div><b>Shortlist</b><small>Tap the heart on any creator</small></div></li>
            <li><span><i class="ph-bold ph-paper-plane-tilt"></i></span><div><b>Request a quote</b><small>Tell us your goal and dates</small></div></li>
            <li><span><i class="ph-bold ph-handshake"></i></span><div><b>We confirm and launch</b><small>One contract, managed by us</small></div></li>
        </ol>
    </div>
</section>

<section class="section-tight talent-wrap" style="padding-top:8px">
    <div class="container">
        <?php if (!$total): ?>
            <div class="network-open talent-soon" data-reveal="scale">
                <div>
                    <h2 class="h2">Our roster is being hand-picked.</h2>
                    <p class="lead">We vet every creator before brands see them. Send a short brief and we will shortlist creators for you within one business day.</p>
                </div>
                <div class="ctas" style="display:flex;gap:12px;flex-wrap:wrap">
                    <a class="btn btn-primary btn-lg" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Send a brief <i class="ph-bold ph-arrow-up-right"></i></a>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('join')) ?>">I am a creator</a>
                </div>
            </div>
        <?php else: ?>
            <form class="talent-filters" method="get" action="<?= e(url('talent')) ?>" data-talent-filters role="search" aria-label="Filter creators">
                <div class="tf-search">
                    <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                    <label class="sr-only" for="tf-q">Search creators</label>
                    <input id="tf-q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search by name, city or niche" autocomplete="off" data-tf="q">
                </div>
                <div class="tf-field">
                    <label for="tf-niche">Niche</label>
                    <select class="select" id="tf-niche" name="niche" data-tf="niche">
                        <option value="">All niches</option>
                        <?php foreach (niche_options() as $n): ?><option<?= $f['niche'] === $n ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="tf-field">
                    <label for="tf-tier">Size</label>
                    <select class="select" id="tf-tier" name="tier" data-tf="tier">
                        <option value="">Any size</option>
                        <?php foreach ($tiers as $k => $l): ?><option value="<?= e($k) ?>"<?= $f['tier'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="tf-field">
                    <label for="tf-sort">Sort</label>
                    <select class="select" id="tf-sort" name="sort" data-tf="sort">
                        <?php foreach (talent_sorts() as $k => $l): ?><option value="<?= e($k) ?>"<?= $f['sort'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="tf-plats" role="radiogroup" aria-label="Platform">
                    <?php foreach (['' => ['ph-globe-simple', 'All']] + array_map(function ($p) { return [$p['icon'], $p['label']]; }, $platforms) as $k => $p): ?>
                        <label class="tf-plat"><input type="radio" name="platform" value="<?= e($k) ?>"<?= $f['platform'] === $k ? ' checked' : '' ?> data-tf="platform"><span><i class="ph-fill <?= e($p[0]) ?>" aria-hidden="true"></i><?= e($p[1]) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-dark tf-apply" type="submit">Apply filters</button>
            </form>

            <div class="talent-bar">
                <p aria-live="polite"><b data-talent-count><?= $shown ?></b> <span data-talent-noun><?= $shown === 1 ? 'creator' : 'creators' ?></span><?= $filtered ? ' match your filters' : ' ready to book' ?></p>
                <a class="link-btn" href="<?= e(url('talent')) ?>" data-talent-reset<?= $filtered ? '' : ' hidden' ?>>Clear filters</a>
            </div>

            <div class="talent-grid" data-talent-grid<?= $shown ? '' : ' hidden' ?>>
                <?php foreach ($cards as $c) partial('talent_card', ['c' => $c]); ?>
            </div>

            <div class="talent-empty" data-talent-empty<?= $shown ? ' hidden' : '' ?>>
                <i class="ph-bold ph-binoculars" aria-hidden="true"></i>
                <h3>No creators match those filters yet.</h3>
                <p>Our network grows every week. Tell us what you need and we will scout creators for you.</p>
                <div class="ctas"><a class="btn btn-primary" href="<?= e(url('contact?type=brand')) ?>">Send a brief</a><a class="btn btn-ghost" href="<?= e(url('talent')) ?>" data-talent-reset-btn>Clear filters</a></div>
            </div>

            <aside class="talent-cta" data-reveal>
                <div>
                    <h2 class="h3">Need a bigger or more specific list?</h2>
                    <p>We work with more creators than we show here. Share your brief and we will hand-pick a shortlist with audience data for each profile.</p>
                </div>
                <a class="btn btn-light btn-lg" href="<?= e(url('contact?type=brand')) ?>">Send a brief <i class="ph-bold ph-arrow-up-right"></i></a>
            </aside>
        <?php endif; ?>
    </div>
</section>
