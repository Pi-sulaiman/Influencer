<?php
/**
 * @var array $posts        Latest published blog posts (database)
 * @var array $creators     Approved creators who allowed featuring (database)
 * @var array $counts       Approved creator counts per niche and platform (database)
 * @var int   $approved     Approved creators in the network (database)
 * @var int   $networkReach Total followers of approved creators (database)
 * @var array $stats        Homepage numbers: manual overrides or live database figures
 * @var array $testimonials Real testimonials from Admin > Site content (section hidden when empty)
 * @var array $faqs
 */
$catalog = niche_catalog();
$platformMeta = platform_options();
$marquee = [
    ['ph-instagram-logo', 'Instagram'], ['ph-youtube-logo', 'YouTube'], ['ph-tiktok-logo', 'TikTok'],
    ['ph-snapchat-logo', 'Snapchat'], ['ph-facebook-logo', 'Facebook'], ['ph-x-logo', 'X'],
    ['ph-pinterest-logo', 'Pinterest'], ['ph-twitch-logo', 'Twitch'], ['ph-threads-logo', 'Threads'],
    ['ph-linkedin-logo', 'LinkedIn'], ['ph-spotify-logo', 'Podcasts'],
];

/* ---- Hero wall: real creators first, niche tiles fill the gaps ---- */
$tileTones = ['t-red', 't-ink', 't-peach', 't-surface', 't-blush'];
$tiles = [];
$ti = 0;
foreach ($catalog as $slug => $n) {
    $tiles[] = ['type' => 'niche', 'name' => $n['name'], 'icon' => $n['icon'], 'slug' => $slug, 'count' => $counts[$n['name']]['any'] ?? 0, 'tone' => $tileTones[$ti++ % count($tileTones)]];
}
$total = max(18, count($creators));
$wall = [];
$nc = count($creators);
$nextAt = $nc ? 0 : PHP_INT_MAX;
$c = 0; $t = 0;
for ($i = 0; $i < $total; $i++) {
    if ($c < $nc && $i >= $nextAt) {
        $wall[] = ['type' => 'creator'] + $creators[$c++];
        $nextAt = (int) round($c * $total / $nc);
    } else {
        $wall[] = $tiles[$t++ % count($tiles)];
    }
}
$cols = [[], [], []];
foreach ($wall as $i => $item) {
    $cols[$i % 3][] = $item;
}

$matchData = [
    'approved' => $approved,
    'mode' => $liveCounts ? 'live' : 'custom',
    'counts' => $counts ?: new stdClass(),
    'platformTotals' => $liveCounts ? platform_totals() : new stdClass(),
    'platforms' => array_map(function ($p) { return $p['label']; }, $platformMeta),
];
?>

<!-- ============ HERO ============ -->
<section class="hero2" data-hero>
    <div class="hero2-bg" aria-hidden="true">
        <svg class="hero2-chev" viewBox="-4 -4 108 98" data-hero-chev><path d="M0 0H28L62 40L52 52H62L72 40L38 0H66L100 40L78 66H40L24 90H0L34 40Z"/></svg>
        <span class="hero2-glow g1"></span><span class="hero2-glow g2"></span>
    </div>

    <div class="container hero2-grid">
        <div class="hero2-copy" data-hero-copy>
            <p class="hero2-kicker" data-hero-in><?= mark_svg('') ?> Influencer marketing agency</p>
            <h1 class="hero2-title" data-hero-title>
                <span class="line">Influencer campaigns</span>
                <span class="line">that <span class="rotator" data-rotator="peak,convert,scale,sell"><span>peak</span></span>.</span>
            </h1>
            <p class="hero2-sub" data-hero-in>Tell us your niche. We match you with vetted creators and run the campaign from brief to final report.</p>

            <form class="mm" action="<?= e(url('contact')) ?>" method="get" data-matchmaker data-hero-in>
                <input type="hidden" name="type" value="brand">
                <script type="application/json" data-mm-data><?= json_encode($matchData, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
                <div class="mm-sentence">
                    <span class="mm-word">I need</span>
                    <div class="mm-select" data-mm-select>
                        <label class="sr-only" for="mm-niche">Niche</label>
                        <select class="mm-native" id="mm-niche" name="niche" data-mm-native>
                            <option value="">any niche</option>
                            <?php foreach ($catalog as $n): ?><option value="<?= e($n['name']) ?>"><?= e($n['name']) ?></option><?php endforeach; ?>
                        </select>
                        <button class="mm-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="mm-pop" data-mm-trigger hidden>
                            <i class="ph-bold ph-squares-four" data-mm-icon aria-hidden="true"></i><span data-mm-label>any niche</span><i class="ph-bold ph-caret-down mm-caret" aria-hidden="true"></i>
                        </button>
                        <div class="mm-pop" id="mm-pop" role="listbox" aria-label="Choose a niche" tabindex="-1" data-mm-pop data-lenis-prevent hidden>
                            <button type="button" role="option" class="mm-opt" data-value="" data-icon="ph-squares-four" aria-selected="true"><i class="ph-bold ph-squares-four"></i><span>Any niche</span></button>
                            <?php foreach ($catalog as $n): $cnt = $counts[$n['name']]['any'] ?? 0; ?>
                                <button type="button" role="option" class="mm-opt" data-value="<?= e($n['name']) ?>" data-icon="<?= e($n['icon']) ?>" aria-selected="false"><i class="ph-bold <?= e($n['icon']) ?>"></i><span><?= e($n['name']) ?></span><?php if ($cnt): ?><small><?= (int) $cnt ?></small><?php endif; ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <span class="mm-word">creators on</span>
                    <div class="mm-plats" role="radiogroup" aria-label="Platform">
                        <?php foreach (['' => ['ph-globe-simple', 'Any'], 'instagram' => ['ph-instagram-logo', 'Instagram'], 'youtube' => ['ph-youtube-logo', 'YouTube'], 'tiktok' => ['ph-tiktok-logo', 'TikTok']] as $val => $p): ?>
                            <label class="mm-plat" title="<?= e($p[1]) ?>"><input type="radio" name="platform" value="<?= e($val) ?>"<?= $val === '' ? ' checked' : '' ?>><span><i class="ph-fill <?= e($p[0]) ?>" aria-hidden="true"></i><em><?= e($p[1]) ?></em></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mm-foot">
                    <p class="mm-count" aria-live="polite" data-mm-count>
                        <?php if ($approved): ?><b><?= number_format($approved) ?><?= $liveCounts ? '' : '+' ?></b> vetted creators ready to match<?php else: ?>Pick a niche and we will build your shortlist<?php endif; ?>
                    </p>
                    <button class="btn btn-primary btn-lg" type="submit" data-magnetic>Get matched <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></button>
                </div>
            </form>
            <a class="hero2-creator-link" href="<?= e(url('join')) ?>" data-hero-in>Are you a creator? <b>Join as a creator</b> <i class="ph-bold ph-arrow-right" aria-hidden="true"></i></a>
        </div>

        <div class="wall" aria-hidden="true" data-wall>
            <div class="wall-tilt" data-wall-tilt>
                <?php foreach ($cols as $ci => $col): ?>
                    <div class="wall-col" data-wall-col data-dir="<?= $ci === 1 ? 'down' : 'up' ?>" style="--dur:<?= [46, 58, 40][$ci] ?>s">
                        <div class="wall-strip" data-wall-strip>
                            <?php for ($dup = 0; $dup < 2; $dup++): foreach ($col as $item): ?>
                                <?php if ($item['type'] === 'creator'):
                                    $pIcon = $platformMeta[$item['primary']]['icon'] ?? 'ph-globe'; ?>
                                    <div class="wcard wcard-creator" data-niches="<?= e(implode('|', $item['niches'])) ?>" data-plats="<?= e(implode('|', $item['platforms'])) ?>">
                                        <img src="<?= e($item['photo']) ?>" alt="" loading="<?= $dup ? 'lazy' : 'eager' ?>" width="240" height="300">
                                        <span class="wc-reach"><i class="ph-fill <?= e($pIcon) ?>"></i><?= e(short_number($item['reach'])) ?></span>
                                        <span class="wc-info"><b><?= e($item['first']) ?></b><small><?= e($item['niches'][0] ?? 'Creator') ?><?= $item['city'] ? ' &middot; ' . e($item['city']) : '' ?></small></span>
                                    </div>
                                <?php else: ?>
                                    <div class="wcard wcard-niche <?= e($item['tone']) ?>" data-niches="<?= e($item['name']) ?>" data-plats="*">
                                        <i class="ph-bold <?= e($item['icon']) ?>"></i>
                                        <b><?= e($item['name']) ?></b>
                                        <small><?= $item['count'] ? (int) $item['count'] . ' creator' . ($item['count'] > 1 ? 's' : '') : 'creators' ?></small>
                                        <?= mark_svg('wc-chev') ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ PLATFORMS ============ -->
<section class="platforms" aria-label="Platforms we run campaigns on">
    <p class="platforms-title">Campaigns across every platform your audience uses</p>
    <div class="marquee" data-marquee>
        <div class="marquee-track" data-marquee-track>
            <?php for ($loop = 0; $loop < 2; $loop++): foreach ($marquee as $p): ?>
                <span<?= $loop ? ' aria-hidden="true"' : '' ?>><i class="ph-fill <?= e($p[0]) ?>" aria-hidden="true"></i><?= e($p[1]) ?></span>
            <?php endforeach; endfor; ?>
        </div>
    </div>
</section>

<!-- ============ AUDIENCE SWITCH ============ -->
<section class="section" id="how">
    <div class="container">
        <div class="audience-head">
            <h2 class="h2" data-split>One agency for both sides of the post.</h2>
            <div class="switch" role="tablist" aria-label="Choose your side" data-switch data-active="0" data-reveal style="--i:1">
                <span class="switch-thumb" aria-hidden="true"></span>
                <button type="button" role="tab" id="tab-brands" aria-controls="panel-brands" aria-selected="true"><i class="ph-bold ph-storefront" aria-hidden="true"></i> For brands</button>
                <button type="button" role="tab" id="tab-creators" aria-controls="panel-creators" aria-selected="false" tabindex="-1"><i class="ph-bold ph-camera" aria-hidden="true"></i> For creators</button>
            </div>
        </div>

        <div class="aud-panel" id="panel-brands" role="tabpanel" aria-labelledby="tab-brands" data-reveal>
            <figure class="aud-media" data-clip>
                <img src="<?= e(site_image('home_brands')) ?>" alt="Brand lead in a suit holding a coffee" loading="lazy" width="1000" height="1080" data-parallax-img>
                <figcaption class="badge-float"><i class="ph-fill ph-seal-check" aria-hidden="true"></i> Every creator is vetted before you see them</figcaption>
            </figure>
            <div class="aud-body">
                <h3 class="h3">Launch with creators your customers already trust.</h3>
                <ul class="aud-list">
                    <li><span class="ic"><i class="ph-bold ph-target" aria-hidden="true"></i></span><div><h4>Audience-first matching</h4><p>We shortlist creators by who follows them, not just how many.</p></div></li>
                    <li><span class="ic"><i class="ph-bold ph-handshake" aria-hidden="true"></i></span><div><h4>One contract, one contact</h4><p>We handle negotiation, briefs, approvals and payments for every creator.</p></div></li>
                    <li><span class="ic"><i class="ph-bold ph-chart-line-up" aria-hidden="true"></i></span><div><h4>Reports you can act on</h4><p>Reach, engagement, clicks and sales, with clear next steps.</p></div></li>
                </ul>
                <a class="btn btn-primary" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Start a campaign <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="aud-panel" id="panel-creators" role="tabpanel" aria-labelledby="tab-creators" hidden>
            <figure class="aud-media">
                <img src="<?= e(site_image('home_creators')) ?>" alt="Creator in sunglasses posing for a photo" loading="lazy" width="1000" height="1080">
                <figcaption class="badge-float"><i class="ph-fill ph-currency-circle-dollar" aria-hidden="true"></i> Free to join. You set your own rate range.</figcaption>
            </figure>
            <div class="aud-body">
                <h3 class="h3">Paid campaigns that fit your content.</h3>
                <ul class="aud-list">
                    <li><span class="ic"><i class="ph-bold ph-sparkle" aria-hidden="true"></i></span><div><h4>Briefs that respect your style</h4><p>Clear goals and creative freedom, so the content still sounds like you.</p></div></li>
                    <li><span class="ic"><i class="ph-bold ph-wallet" aria-hidden="true"></i></span><div><h4>Fair rates, paid on time</h4><p>We work from your rate range and pay on the dates we agree.</p></div></li>
                    <li><span class="ic"><i class="ph-bold ph-rocket-launch" aria-hidden="true"></i></span><div><h4>Grow with every campaign</h4><p>Results you can add to your media kit, plus repeat brand partners.</p></div></li>
                </ul>
                <a class="btn btn-primary" href="<?= e(url('join')) ?>" data-magnetic>Join as a creator <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- ============ SERVICES BENTO ============ -->
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="services-head">
            <p class="eyebrow" data-reveal><?= mark_svg('') ?> What we do</p>
            <h2 class="h2" data-split>Everything a creator campaign needs, under one roof.</h2>
        </div>
        <div class="bento" data-bento>
            <a class="cell cell-a" href="<?= e(url('services')) ?>" data-spotlight data-tilt>
                <img src="<?= e(site_image('home_campaigns')) ?>" alt="" loading="lazy" width="1200" height="1100">
                <span class="ic"><i class="ph-bold ph-megaphone" aria-hidden="true"></i></span>
                <h3>Influencer campaigns, end to end</h3>
                <p>Strategy, creator sourcing, briefs, approvals and launch, managed by one team that answers to your goal.</p>
            </a>
            <article class="cell" data-spotlight data-tilt>
                <span class="ic"><i class="ph-bold ph-users-three" aria-hidden="true"></i></span>
                <h3>Creator sourcing</h3>
                <p>Vetted creators matched on audience, niche and brand safety.</p>
            </article>
            <article class="cell cell-red" data-spotlight data-tilt>
                <?= mark_svg('cell-chev') ?>
                <span class="ic"><i class="ph-bold ph-video-camera" aria-hidden="true"></i></span>
                <h3>UGC production</h3>
                <p>Creator-made video and photos for ads and product pages.</p>
            </article>
            <article class="cell cell-photo" data-spotlight data-tilt>
                <img src="<?= e(site_image('home_talent')) ?>" alt="" loading="lazy" width="700" height="600">
                <span class="ic"><i class="ph-bold ph-star" aria-hidden="true"></i></span>
                <h3>Talent management</h3>
                <p>Long-term representation for creators ready to grow.</p>
            </article>
            <article class="cell" data-spotlight data-tilt>
                <span class="ic"><i class="ph-bold ph-lightning" aria-hidden="true"></i></span>
                <h3>Paid amplification</h3>
                <p>Whitelisted creator ads that scale your best performing posts.</p>
            </article>
            <article class="cell cell-wide" data-spotlight data-reveal>
                <div>
                    <span class="ic"><i class="ph-bold ph-chart-bar" aria-hidden="true"></i></span>
                    <h3>Reporting that shows the peak</h3>
                    <p>Live tracking during the campaign and a clear wrap report after it, with reach, engagement, clicks and sales side by side.</p>
                </div>
                <div class="bars" aria-hidden="true">
                    <?php foreach ([28, 36, 32, 48, 44, 60, 56, 72, 68, 92] as $i => $h): ?>
                        <span style="--h:<?= $h ?>%;--i:<?= $i ?>"<?= $i === 9 ? ' data-label="Peak week"' : '' ?>></span>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ============ PROCESS (pinned horizontal scroll on desktop) ============ -->
<section class="hs" data-hscroll aria-labelledby="hs-title">
    <div class="hs-pin">
        <div class="container hs-head">
            <h2 class="h2" id="hs-title" data-split>From brief to peak in five moves.</h2>
            <div class="hs-meta">
                <span class="hs-progress" aria-hidden="true"><span data-hs-bar></span></span>
                <a class="btn btn-dark" href="<?= e(url('brands')) ?>">See how we work <i class="ph-bold ph-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="hs-viewport" data-hs-viewport>
            <ol class="hs-track" data-hs-track>
                <li class="hs-card hs-surface">
                    <span class="hs-ic"><i class="ph-bold ph-clipboard-text"></i></span>
                    <h3>Brief</h3>
                    <p>We start with your goal, audience and budget, then turn it into a one page brief creators can act on.</p>
                    <ul class="hs-checks"><li><i class="ph-fill ph-check-circle"></i>Campaign goal and KPIs</li><li><i class="ph-fill ph-check-circle"></i>Target audience and niches</li><li><i class="ph-fill ph-check-circle"></i>Budget, timing and usage rights</li></ul>
                </li>
                <li class="hs-card hs-ink">
                    <span class="hs-ic"><i class="ph-bold ph-magnifying-glass"></i></span>
                    <h3>Match</h3>
                    <p>We shortlist creators from our network by audience fit, engagement and content quality. You approve every name.</p>
                    <div class="hs-chips"><?php foreach (array_slice(array_values($catalog), 0, 8) as $n): ?><span><i class="ph-bold <?= e($n['icon']) ?>"></i><?= e($n['name']) ?></span><?php endforeach; ?></div>
                </li>
                <li class="hs-card hs-red">
                    <?= mark_svg('hs-chev') ?>
                    <span class="hs-ic"><i class="ph-bold ph-pencil-simple-line"></i></span>
                    <h3>Create</h3>
                    <p>Creators make content in their own voice. We manage drafts, feedback and approvals against your brief.</p>
                    <div class="hs-tags"><span>Scripts</span><span>Drafts</span><span>Approvals</span></div>
                </li>
                <li class="hs-card hs-peach">
                    <span class="hs-ic"><i class="ph-bold ph-rocket-launch"></i></span>
                    <h3>Launch</h3>
                    <p>Content goes live on schedule with tracked links and codes. The best posts can be boosted as ads.</p>
                    <div class="hs-platforms"><i class="ph-fill ph-instagram-logo"></i><i class="ph-fill ph-youtube-logo"></i><i class="ph-fill ph-tiktok-logo"></i><i class="ph-fill ph-snapchat-logo"></i><i class="ph-fill ph-x-logo"></i></div>
                </li>
                <li class="hs-card hs-surface">
                    <span class="hs-ic"><i class="ph-bold ph-chart-line-up"></i></span>
                    <h3>Report</h3>
                    <p>You get a wrap report with results, learnings and a recommendation for the next campaign.</p>
                    <div class="bars hs-bars" aria-hidden="true" data-reveal="fade"><?php foreach ([30, 42, 38, 56, 50, 70, 64, 90] as $i => $h): ?><span style="--h:<?= $h ?>%;--i:<?= $i ?>"></span><?php endforeach; ?></div>
                </li>
            </ol>
        </div>
    </div>
</section>

<!-- ============ NICHES ============ -->
<section class="section" id="niches">
    <div class="container">
        <div class="niches-head">
            <h2 class="h2" data-split>Creators for every niche your customers follow.</h2>
            <a class="link-arrow" href="<?= e(url('influencers')) ?>">Explore all niches <i class="ph-bold ph-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="niche-grid" data-niche-grid>
            <?php foreach ($catalog as $slug => $n): $cnt = $counts[$n['name']]['any'] ?? 0; ?>
                <a class="niche-tile" href="<?= e(url('influencers/' . $slug)) ?>" data-spotlight>
                    <span class="nt-ic"><i class="ph-bold <?= e($n['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="nt-name"><?= e($n['name']) ?></span>
                    <span class="nt-meta"><?= $cnt ? (int) $cnt . ' vetted creator' . ($cnt > 1 ? 's' : '') : 'Influencer marketing' ?></span>
                    <i class="ph-bold ph-arrow-up-right nt-arrow" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ STATS (live or from Admin > Site content) ============ -->
<?php partial('stats_band', ['stats' => $stats]); ?>

<!-- ============ CREATOR SHOWCASE (database only) ============ -->
<?php if ($creators): ?>
<section class="section">
    <div class="container showcase-head">
        <h2 class="h2" data-split>Meet creators from our network.</h2>
        <div class="carousel-nav">
            <button class="round-btn" type="button" data-carousel-prev aria-label="Previous creators"><i class="ph-bold ph-arrow-left"></i></button>
            <button class="round-btn" type="button" data-carousel-next aria-label="Next creators"><i class="ph-bold ph-arrow-right"></i></button>
        </div>
    </div>
    <div class="carousel" data-carousel tabindex="0" aria-label="Featured creators">
        <div class="carousel-track">
            <?php foreach ($creators as $i => $cr): ?>
                <article class="creator-card" data-reveal style="--i:<?= min($i, 4) ?>">
                    <div class="cc-media"><img src="<?= e($cr['photo']) ?>" alt="<?= e($cr['name']) ?>, <?= e($cr['niches'][0] ?? 'content') ?> creator" loading="lazy" draggable="false" width="400" height="440"></div>
                    <div class="cc-body">
                        <div class="cc-name"><?= e($cr['name']) ?> <i class="ph-fill ph-seal-check" aria-label="Vetted by Peakilo"></i></div>
                        <div class="cc-meta"><?= e(implode(', ', array_slice($cr['niches'], 0, 2))) ?><?= $cr['city'] ? ' &middot; ' . e($cr['city']) : '' ?></div>
                        <div class="cc-stats">
                            <div><b><?= e(short_number($cr['reach'])) ?></b> <span class="muted">reach</span></div>
                            <div class="cc-platforms" aria-label="Platforms"><?php foreach ($cr['platforms'] as $p): ?><i class="ph-fill <?= e($platformMeta[$p]['icon']) ?>" title="<?= e($platformMeta[$p]['label']) ?>"></i><?php endforeach; ?></div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <a class="creator-card cc-join" href="<?= e(url('join')) ?>">
                <?= mark_svg('') ?>
                <b>Your profile could be next.</b>
                <span>Join as a creator <i class="ph-bold ph-arrow-right"></i></span>
            </a>
        </div>
    </div>
</section>
<?php else: ?>
<section class="section">
    <div class="container">
        <div class="network-open" data-reveal="scale">
            <div>
                <h2 class="h2">Our creator network is open.</h2>
                <p class="lead">Approved creators who choose to be featured appear here. Join now and be one of the first faces brands see.</p>
            </div>
            <a class="btn btn-primary btn-lg" href="<?= e(url('join')) ?>" data-magnetic>Join as a creator <i class="ph-bold ph-arrow-up-right"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ ESTIMATOR ============ -->
<section class="section" id="estimate" style="padding-top:0">
    <div class="container">
        <div class="estimator-wrap" data-estimator>
            <div class="est-controls">
                <p class="eyebrow"><?= mark_svg('') ?> Campaign estimator</p>
                <h2 class="h2">See what your budget can reach.</h2>
                <p class="lead">Move the slider and pick a creator tier to get a quick, honest estimate.</p>

                <div class="est-group">
                    <div class="field-label"><label for="est-budget">Budget</label><output for="est-budget" data-est-budget-out>$10,000</output></div>
                    <input class="range" id="est-budget" type="range" min="1000" max="100000" step="500" value="10000" data-est-budget>
                    <div class="range-scale"><span data-usd="1000">$1K</span><span data-usd="50000">$50K</span><span data-usd="100000">$100K</span></div>
                </div>

                <fieldset class="est-group">
                    <legend>Creator tier</legend>
                    <div class="chips">
                        <label class="chip"><input type="radio" name="tier" value="nano"><span>Nano <small>1-10K</small></span></label>
                        <label class="chip"><input type="radio" name="tier" value="micro" checked><span>Micro <small>10-100K</small></span></label>
                        <label class="chip"><input type="radio" name="tier" value="mid"><span>Mid-tier <small>100-500K</small></span></label>
                        <label class="chip"><input type="radio" name="tier" value="macro"><span>Macro <small>500K+</small></span></label>
                    </div>
                </fieldset>

                <fieldset class="est-group">
                    <legend>Main platform</legend>
                    <div class="chips">
                        <label class="chip"><input type="radio" name="platform_est" value="instagram" checked><span><i class="ph-fill ph-instagram-logo" aria-hidden="true"></i> Instagram</span></label>
                        <label class="chip"><input type="radio" name="platform_est" value="tiktok"><span><i class="ph-fill ph-tiktok-logo" aria-hidden="true"></i> TikTok</span></label>
                        <label class="chip"><input type="radio" name="platform_est" value="youtube"><span><i class="ph-fill ph-youtube-logo" aria-hidden="true"></i> YouTube</span></label>
                    </div>
                </fieldset>
            </div>

            <div class="est-results" aria-live="polite">
                <?= mark_svg('chev-bg') ?>
                <span class="est-kicker">Estimated reach</span>
                <div class="est-big"><span data-est="reach">0</span><small>people</small></div>
                <div class="est-rows">
                    <div><b data-est="creators">0</b><span>creators</span></div>
                    <div><b data-est="engagements">0</b><span>engagements</span></div>
                    <div><b data-est="cpm">$0</b><span>cost per 1K reach</span></div>
                </div>
                <p class="fine">Based on typical market rates for each tier. Your real plan depends on niche, content format and usage rights.</p>
                <a class="btn btn-light" href="<?= e(url('contact?type=brand')) ?>" data-est-cta>Start a campaign <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<?php if ($testimonials): ?>
<section class="section" style="padding-top:0">
    <div class="container quotes-grid" data-quotes>
        <div class="quote-stage">
            <?php foreach ($testimonials as $i => $tq): ?>
                <figure class="quote<?= $i === 0 ? ' is-active' : '' ?>" data-quote aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                    <blockquote><?= e($tq['quote']) ?></blockquote>
                    <figcaption>
                        <?php if ($tq['img']): ?><img src="<?= e($tq['img']) ?>" alt="" loading="lazy" width="52" height="52"><?php else: ?><span class="q-ini" aria-hidden="true"><?= e(initials($tq['name'])) ?></span><?php endif; ?>
                        <div><b><?= e($tq['name']) ?></b><span><?= e($tq['role']) ?></span></div>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
        <div class="quote-side">
            <?= mark_svg('big-chev') ?>
            <div class="quote-progress" data-quote-dots>
                <?php foreach ($testimonials as $i => $tq): ?>
                    <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="Show quote <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-nav">
                <button class="round-btn" type="button" data-quote-prev aria-label="Previous quote"><i class="ph-bold ph-arrow-left"></i></button>
                <button class="round-btn" type="button" data-quote-next aria-label="Next quote"><i class="ph-bold ph-arrow-right"></i></button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ BLOG PREVIEW (database) ============ -->
<?php if ($posts): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="blog-head">
            <h2 class="h2" data-split>Latest from the blog</h2>
            <a class="link-arrow" href="<?= e(url('blog')) ?>">All articles <i class="ph-bold ph-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="blog-preview">
            <?php $first = array_shift($posts); partial('post_card', ['post' => $first, 'variant' => 'is-large']); ?>
            <?php if ($posts): ?>
            <div class="side">
                <?php foreach ($posts as $i => $p) partial('post_card', ['post' => $p, 'variant' => 'is-row', 'i' => $i + 1]); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ FAQ ============ -->
<section class="section" style="padding-top:0">
    <div class="container faq-grid">
        <div class="faq-intro">
            <h2 class="h2" data-split>Questions, answered.</h2>
            <p class="lead" data-reveal style="--i:1">Still unsure about something? Send us a note and a real person will reply within one business day.</p>
            <a class="btn btn-outline" href="<?= e(url('contact?type=other')) ?>" data-reveal style="--i:2">Ask a question</a>
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

<!-- ============ CTA ============ -->
<section class="container" aria-label="Get started">
    <div class="cta-block" data-cta-scrub>
        <div class="cta-chevs" aria-hidden="true"><?= mark_svg('') ?><?= mark_svg('') ?></div>
        <h2 data-split>Ready to reach your peak?</h2>
        <div>
            <p>Tell us about your brand or your channel. We reply within one business day.</p>
            <div class="ctas">
                <a class="btn btn-light btn-lg" href="<?= e(url('contact?type=brand')) ?>" data-magnetic>Start a campaign <i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i></a>
                <a class="btn btn-on-red btn-lg" href="<?= e(url('join')) ?>">Join as a creator</a>
            </div>
        </div>
    </div>
</section>
