<?php
/** @var array $niche @var array $c @var array $creators @var array $faqs @var array $related @var array $posts */
$name = $niche['name'];
$lower = strtolower($name);
$platformMeta = platform_options();
$ideaIcons = ['ph-lightbulb', 'ph-film-slate', 'ph-trophy'];
$briefUrl = url('contact?type=brand&niche=' . rawurlencode($name));
?>
<section class="page-hero niche-hero">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><a href="<?= e(url('influencers')) ?>">Niches</a><span aria-hidden="true">/</span><span><?= e($name) ?></span></nav>
        <span class="nh-icon" aria-hidden="true"><i class="ph-bold <?= e($niche['icon']) ?>"></i></span>
        <h1 class="h1" data-split><?= e($name) ?> influencer marketing that performs.</h1>
        <p class="lead"><?= e($niche['blurb']) ?></p>
        <div class="niche-stats">
            <span><i class="ph-bold ph-seal-check"></i><?= $c['any'] ? (int) $c['any'] . ' vetted ' . e($lower) . ' creator' . ($c['any'] > 1 ? 's' : '') . ' in our network' : 'We source ' . e($lower) . ' creators for every brief' ?></span>
            <?php foreach (array_slice(array_keys(enabled_platforms()), 0, 3) as $p): if (!empty($c[$p])): ?>
                <span><i class="ph-fill <?= e($platformMeta[$p]['icon']) ?>"></i><?= (int) $c[$p] ?> on <?= e($platformMeta[$p]['label']) ?></span>
            <?php endif; endforeach; ?>
        </div>
        <div class="ctas">
            <a class="btn btn-primary btn-lg" href="<?= e($briefUrl) ?>" data-magnetic>Find <?= e($lower) ?> creators <i class="ph-bold ph-arrow-up-right"></i></a>
            <a class="btn btn-outline btn-lg" href="<?= e(url('join')) ?>">Join as a creator</a>
        </div>
    </div>
</section>

<?php if ($creators): ?>
<section class="section-tight">
    <div class="container showcase-head">
        <h2 class="h2" data-split><?= e($name) ?> creators from our network</h2>
        <div class="carousel-nav">
            <button class="round-btn" type="button" data-carousel-prev aria-label="Previous creators"><i class="ph-bold ph-arrow-left"></i></button>
            <button class="round-btn" type="button" data-carousel-next aria-label="Next creators"><i class="ph-bold ph-arrow-right"></i></button>
        </div>
    </div>
    <div class="carousel" data-carousel tabindex="0" aria-label="<?= e($name) ?> creators">
        <div class="carousel-track">
            <?php foreach ($creators as $i => $cr): ?>
                <article class="creator-card" data-reveal style="--i:<?= min($i, 4) ?>">
                    <div class="cc-media"><img src="<?= e($cr['photo']) ?>" alt="<?= e($cr['name']) ?>, <?= e($lower) ?> creator" loading="lazy" draggable="false" width="400" height="440"></div>
                    <div class="cc-body">
                        <div class="cc-name"><?= e($cr['name']) ?> <i class="ph-fill ph-seal-check" aria-label="Vetted by Peakilo"></i></div>
                        <div class="cc-meta"><?= e(implode(', ', array_slice($cr['niches'], 0, 2))) ?><?= $cr['city'] ? ' &middot; ' . e($cr['city']) : '' ?></div>
                        <div class="cc-stats">
                            <div><b><?= e(short_number($cr['reach'])) ?></b> <span class="muted">reach</span></div>
                            <div class="cc-platforms"><?php foreach ($cr['platforms'] as $p): ?><i class="ph-fill <?= e($platformMeta[$p]['icon']) ?>" title="<?= e($platformMeta[$p]['label']) ?>"></i><?php endforeach; ?></div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <h2 class="h2" style="max-width:20ch;margin-bottom:40px" data-split>Campaign ideas for <?= e($lower) ?> brands.</h2>
        <div class="ideas-grid">
            <?php foreach ($niche['ideas'] as $i => $idea): ?>
                <article class="idea"><i class="ph-bold <?= e($ideaIcons[$i % 3]) ?>" aria-hidden="true"></i><h3><?= e($idea) ?></h3></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container split">
        <div>
            <h2 class="h2" data-split>How we run <?= e($lower) ?> campaigns.</h2>
            <ul class="check-list">
                <li><i class="ph-fill ph-check-circle"></i>A one page brief built around your goal and your <?= e($lower) ?> audience</li>
                <li><i class="ph-fill ph-check-circle"></i>A shortlist of vetted <?= e($lower) ?> creators with audience data for each profile</li>
                <li><i class="ph-fill ph-check-circle"></i>Drafts, approvals, posting and tracked links handled for you</li>
                <li><i class="ph-fill ph-check-circle"></i>A wrap report with reach, engagement, clicks and sales</li>
            </ul>
            <a class="btn btn-dark" style="margin-top:28px" href="<?= e($briefUrl) ?>">Start a campaign <i class="ph-bold ph-arrow-right"></i></a>
        </div>
        <div class="faq-list" data-faq>
            <?php foreach ($faqs as $i => $f): ?>
                <details class="faq-item"<?= $i === 0 ? ' open' : '' ?>>
                    <summary><?= e($f['q']) ?> <span class="pm" aria-hidden="true"><i class="ph-bold ph-plus"></i></span></summary>
                    <div class="faq-body"><p><?= e($f['a']) ?></p></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($posts): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="blog-head"><h2 class="h2">Related reading</h2><a class="link-arrow" href="<?= e(url('blog')) ?>">All articles <i class="ph-bold ph-arrow-right"></i></a></div>
        <div class="posts-grid"><?php foreach ($posts as $i => $p) partial('post_card', ['post' => $p, 'i' => $i]); ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="niches-head"><h2 class="h2">Explore other niches</h2><a class="link-arrow" href="<?= e(url('influencers')) ?>">All niches <i class="ph-bold ph-arrow-right"></i></a></div>
        <div class="niche-grid">
            <?php foreach ($related as $s => $n): ?>
                <a class="niche-tile" href="<?= e(url('influencers/' . $s)) ?>" data-spotlight>
                    <span class="nt-ic"><i class="ph-bold <?= e($n['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="nt-name"><?= e($n['name']) ?></span>
                    <span class="nt-meta">Influencer marketing</span>
                    <i class="ph-bold ph-arrow-up-right nt-arrow" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
