<?php
/** @var array $c (roster_card) @var array $similar */
$meta = trim(($c['niches'][0] ?? 'Creator') . ' · ' . short_number($c['reach']) . ' followers');
$slAttrs = 'data-shortlist data-code="' . e($c['code']) . '" data-name="' . e($c['name']) . '" data-photo="' . e($c['photo']) . '" data-meta="' . e($meta) . '"';
$quote = url('contact?type=brand&creators=' . rawurlencode($c['code']));
?>
<section class="tp">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span><a href="<?= e(url('talent')) ?>">Find talent</a><span aria-hidden="true">/</span><span><?= e($c['name']) ?></span></nav>

        <div class="tp-grid">
            <div class="tp-media" data-reveal="scale">
                <img src="<?= e($c['photo']) ?>" alt="<?= e($c['name']) ?>, <?= e($c['niches'][0] ?? 'content') ?> creator" width="600" height="720">
                <span class="tc-tier"><?= e($c['tier']['label']) ?> &middot; <?= e($c['tier']['range']) ?></span>
                <button type="button" class="tc-save" <?= $slAttrs ?> aria-pressed="false" aria-label="Add <?= e($c['name']) ?> to your shortlist"><i class="ph-bold ph-heart" aria-hidden="true"></i></button>
            </div>

            <div class="tp-main">
                <p class="eyebrow"><?= mark_svg('') ?> <?= e($c['code']) ?> &middot; Vetted by Peakilo</p>
                <h1 class="h1 tp-name"><?= e($c['name']) ?> <i class="ph-fill ph-seal-check" aria-hidden="true"></i></h1>
                <p class="tp-loc">
                    <?php if ($c['city'] || $c['country']): ?><span><i class="ph-bold ph-map-pin"></i><?= e(trim($c['city'] . ', ' . $c['country'], ', ')) ?></span><?php endif; ?>
                    <?php if ($c['languages']): ?><span><i class="ph-bold ph-translate"></i><?= e($c['languages']) ?></span><?php endif; ?>
                    <?php if ($c['barter']): ?><span><i class="ph-bold ph-gift"></i>Open to product collaborations</span><?php endif; ?>
                </p>
                <div class="tp-tags"><?php foreach ($c['niches'] as $n): $nb = niche_by_name($n); ?><a class="tag" href="<?= e(url('talent?niche=' . rawurlencode($n))) ?>"><?php if ($nb): ?><i class="ph-bold <?= e($nb['icon']) ?>"></i><?php endif; ?><?= e($n) ?></a><?php endforeach; ?></div>

                <div class="tp-stats">
                    <div><b><?= e(short_number($c['reach'])) ?></b><span>Total followers</span></div>
                    <div><b><?= $c['views'] ? e(short_number($c['views'])) : '-' ?></b><span>Avg. impressions per post</span></div>
                    <div><b><?= $c['er'] !== null ? e($c['er'] + 0) . '%' : '-' ?></b><span>Engagement rate</span></div>
                    <div><b><?= $c['monthly'] ? e(short_number($c['monthly'])) : '-' ?></b><span>Impressions, last 30 days</span></div>
                </div>

                <section class="tp-block">
                    <h2>Channels</h2>
                    <div class="tp-chans">
                        <?php $maxF = max(1, max(array_column($c['channels'], 'followers') ?: [1])); foreach ($c['channels'] as $key => $ch): ?>
                            <div class="tp-chan">
                                <span class="ch-ic"><i class="ph-fill <?= e($ch['icon']) ?>" aria-hidden="true"></i></span>
                                <div class="tpc-main">
                                    <div class="tpc-top"><b><?= e($ch['label']) ?><?= $c['primary'] === $key ? ' <small>Main</small>' : '' ?></b><span><b><?= e(short_number($ch['followers'])) ?></b> <?= $key === 'youtube' ? 'subscribers' : 'followers' ?><?= $ch['views'] ? ' &middot; <b>' . e(short_number($ch['views'])) . '</b> avg. views' : '' ?></span></div>
                                    <span class="tpc-bar" aria-hidden="true"><span style="--w:<?= round(100 * $ch['followers'] / $maxF) ?>%"></span></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ($c['audience'] || $c['content_types']): ?>
                <section class="tp-block tp-two">
                    <?php if ($c['audience']): ?>
                        <div><h2>Audience</h2><ul class="tp-list"><?php foreach ($c['audience'] as $a): ?><li><i class="ph-fill ph-check-circle"></i><?= e($a) ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>
                    <?php if ($c['content_types']): ?>
                        <div><h2>Content formats</h2><div class="tp-tags"><?php foreach ($c['content_types'] as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div></div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($c['bio']): ?>
                    <section class="tp-block"><h2>About</h2><p class="tp-bio"><?= nl2br(e($c['bio'])) ?></p></section>
                <?php endif; ?>
                <?php if ($c['past_brands']): ?>
                    <section class="tp-block"><h2>Brands worked with</h2><p class="tp-bio"><?= e($c['past_brands']) ?></p></section>
                <?php endif; ?>
            </div>

            <aside class="tp-book">
                <div class="tp-book-card">
                    <p class="jp-label"><span class="live-dot" aria-hidden="true"></span>Available to book</p>
                    <h2>Work with <?= e($c['name']) ?></h2>
                    <p class="tp-price"><b>Pricing on request</b><span>Depends on format, usage rights and dates.</span></p>
                    <ul class="tp-promise">
                        <li><i class="ph-bold ph-clock"></i>Availability and a quote within one business day</li>
                        <li><i class="ph-bold ph-credit-card"></i>Nothing to pay online. We agree terms with you directly</li>
                        <li><i class="ph-bold ph-handshake"></i>One contract and one contact, managed by Peakilo</li>
                    </ul>
                    <a class="btn btn-primary btn-lg btn-block" href="<?= e($quote) ?>" data-magnetic>Request a quote <i class="ph-bold ph-arrow-up-right"></i></a>
                    <button type="button" class="btn btn-outline btn-lg btn-block tc-add" <?= $slAttrs ?> aria-pressed="false" style="margin-top:10px"><i class="ph-bold ph-plus" aria-hidden="true"></i><span data-sl-label>Add to shortlist</span></button>
                    <p class="field-help" style="margin-top:12px;text-align:center">Shortlist several creators and request one quote for all of them.</p>
                </div>
            </aside>
        </div>

        <?php if ($similar): ?>
            <div class="tp-similar">
                <div class="niches-head"><h2 class="h2">Similar creators</h2><a class="link-arrow" href="<?= e(url('talent?niche=' . rawurlencode($c['niches'][0] ?? ''))) ?>">See all <?= e($c['niches'][0] ?? '') ?> creators <i class="ph-bold ph-arrow-right"></i></a></div>
                <div class="talent-grid"><?php foreach ($similar as $s) partial('talent_card', ['c' => $s]); ?></div>
            </div>
        <?php endif; ?>
    </div>
</section>
