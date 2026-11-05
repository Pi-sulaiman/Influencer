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
$enabled = enabled_platforms();
$topPlatforms = array_slice($enabled, 0, 3, true);
$marquee = [
    ['ph-instagram-logo', 'Instagram'], ['ph-youtube-logo', 'YouTube'], ['ph-tiktok-logo', 'TikTok'],
    ['ph-snapchat-logo', 'Snapchat'], ['ph-facebook-logo', 'Facebook'], ['ph-x-logo', 'X'],
    ['ph-pinterest-logo', 'Pinterest'], ['ph-twitch-logo', 'Twitch'], ['ph-threads-logo', 'Threads'],
    ['ph-linkedin-logo', 'LinkedIn'], ['ph-spotify-logo', 'Podcasts'],
];
// Platforms switched off in Admin > Settings > Platforms are left out (Pinterest, Twitch and Podcasts always show).
$offLabels = array_map(function ($p) { return $p['label']; }, array_diff_key($platformMeta, $enabled));
$marquee = array_values(array_filter($marquee, function ($m) use ($offLabels) { return !in_array($m[1], $offLabels, true); }));

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
    'platforms' => array_map(function ($p) { return $p['label']; }, $enabled),
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
                        <?php foreach (['' => ['ph-globe-simple', 'Any']] + array_map(function ($p) { return [$p['icon'], $p['label']]; }, $topPlatforms) as $val => $p): ?>
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
            <div class="hero2-links" data-hero-in>
                <?php if ($creators): ?>
                    <a class="hero2-creator-link" href="<?= e(url('talent')) ?>" data-mm-browse data-base="<?= e(url('talent')) ?>"><span class="hl-ic"><i class="ph-bold ph-users-three" aria-hidden="true"></i></span><span class="hl-txt"><small>For brands</small> <b>Browse matching creators</b></span><i class="ph-bold ph-arrow-right hl-arrow" aria-hidden="true"></i></a>
                <?php endif; ?>
                <a class="hero2-creator-link" href="<?= e(url('join')) ?>"><span class="hl-ic"><i class="ph-bold ph-camera" aria-hidden="true"></i></span><span class="hl-txt"><small>Are you a creator?</small> <b>Join as a creator</b></span><i class="ph-bold ph-arrow-right hl-arrow" aria-hidden="true"></i></a>
            </div>
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
                                        <img src="<?= e($item['photo']) ?>" alt="" loading="<?= $dup ? 'lazy' : 'eager' ?>" width="240" height="300"<?= photo_fallback_attr($item) ?>>
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

<!-- ============ BRAND LOGOS (Admin > Site content > Brand logos; hidden when empty) ============ -->
<?php if ($logos):
    // Repeat the set so one half of the track is always wider than the screen.
    $set = $logos;
    while (count($set) < 10) $set = array_merge($set, $logos);
?>
<section class="logos" aria-label="<?= e(setting('logos_title', 'Brands we have worked with')) ?>">
    <p class="logos-title"><?= e(setting('logos_title', 'Brands we have worked with')) ?></p>
    <div class="logo-marquee" style="--logo-dur:<?= max(24, count($set) * 4) ?>s">
        <ul class="logo-track">
            <?php for ($half = 0; $half < 2; $half++): foreach ($set as $k => $l): $hide = $half || $k >= count($logos); ?>
                <li<?= $hide ? ' aria-hidden="true"' : '' ?>>
                    <?php if ($l['url'] && !$hide): ?><a href="<?= e($l['url']) ?>" target="_blank" rel="noopener nofollow"><?php endif; ?>
                    <img src="<?= e($l['logo']) ?>" alt="<?= $hide ? '' : e($l['name']) ?>" loading="lazy" height="40">
                    <?php if ($l['url'] && !$hide): ?></a><?php endif; ?>
                </li>
            <?php endforeach; endfor; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

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
            <div class="ah-copy">
                <p class="eyebrow" data-reveal><?= mark_svg('') ?> Brands and creators</p>
                <h2 class="h2" data-split>One agency for both sides of the post.</h2>
            </div>
            <div class="switch" role="tablist" aria-label="Choose your side" data-switch data-active="0" data-reveal style="--i:1">
                <span class="switch-thumb" aria-hidden="true"></span>
                <button type="button" role="tab" id="tab-brands" aria-controls="panel-brands" aria-selected="true"><span class="sw-ic"><i class="ph-bold ph-storefront" aria-hidden="true"></i></span><span class="sw-txt"><b>For brands</b><small>Book creators</small></span></button>
                <button type="button" role="tab" id="tab-creators" aria-controls="panel-creators" aria-selected="false" tabindex="-1"><span class="sw-ic"><i class="ph-bold ph-camera" aria-hidden="true"></i></span><span class="sw-txt"><b>For creators</b><small>Get paid campaigns</small></span></button>
            </div>
        </div>

        <?php $avatars = array_slice($creators, 0, 5); ?>
        <div class="aud-panel" id="panel-brands" role="tabpanel" aria-labelledby="tab-brands" data-reveal>
            <!-- Interactive campaign planner (example figures, no photos needed) -->
            <div class="aw aw-brand" data-planner>
                <div class="aw-head"><span class="aw-title"><i class="ph-bold ph-sparkle" aria-hidden="true"></i> Campaign planner</span><span class="aw-pill">Example</span></div>
                <div class="aw-row">
                    <span class="aw-label" id="pl-goal">Goal</span>
                    <div class="aw-seg" role="radiogroup" aria-labelledby="pl-goal" data-pl-group="goal">
                        <button type="button" role="radio" aria-checked="false" data-val="awareness">Awareness</button>
                        <button type="button" role="radio" aria-checked="true" data-val="launch">Launch</button>
                        <button type="button" role="radio" aria-checked="false" data-val="sales">Sales</button>
                    </div>
                </div>
                <div class="aw-row">
                    <span class="aw-label" id="pl-size">Creator size</span>
                    <div class="aw-seg" role="radiogroup" aria-labelledby="pl-size" data-pl-group="size">
                        <button type="button" role="radio" aria-checked="false" data-val="nano">Nano</button>
                        <button type="button" role="radio" aria-checked="true" data-val="micro">Micro</button>
                        <button type="button" role="radio" aria-checked="false" data-val="macro">Macro</button>
                    </div>
                </div>
                <div class="aw-short">
                    <div class="aw-avatars" aria-hidden="true">
                        <?php for ($k = 0; $k < 5; $k++): $av = $avatars[$k] ?? null; ?>
                            <span class="aw-av" data-av><?php if ($av): ?><img src="<?= e($av['photo']) ?>" alt="" loading="lazy" width="44" height="44"<?= photo_fallback_attr($av) ?>><?php else: ?><i class="ph-bold <?= e(array_values($catalog)[$k]['icon']) ?>"></i><?php endif; ?><em><i class="ph-bold ph-check"></i></em></span>
                        <?php endfor; ?>
                    </div>
                    <div class="aw-short-txt"><b><span data-pl="creators">10</span> creators shortlisted</b><small>Vetted for audience fit and brand safety</small></div>
                </div>
                <div class="aw-chart" aria-hidden="true">
                    <svg viewBox="0 0 300 96" preserveAspectRatio="none">
                        <defs><linearGradient id="awGrad" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#EF3B2A" stop-opacity=".32"/><stop offset="1" stop-color="#EF3B2A" stop-opacity="0"/></linearGradient></defs>
                        <path class="aw-area" data-pl-area d=""/>
                        <path class="aw-line" data-pl-line d=""/>
                        <circle class="aw-dot" data-pl-dot r="5" cx="0" cy="0"/>
                    </svg>
                    <span class="aw-peak" data-pl-peak>Peak week</span>
                </div>
                <div class="aw-kpis">
                    <div><b data-pl="reach">0</b><span>Est. reach</span></div>
                    <div><b data-pl="eng">0</b><span>Engagements</span></div>
                    <div><b data-pl="weeks">0</b><span>Weeks to launch</span></div>
                </div>
            </div>
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
            <!-- Interactive earnings guide (rough guide, not an offer) -->
            <div class="aw aw-creator" data-earn>
                <div class="aw-head"><span class="aw-title"><i class="ph-bold ph-wallet" aria-hidden="true"></i> What could you earn?</span><span class="aw-pill">Guide</span></div>
                <div class="aw-row aw-col">
                    <div class="aw-label-row"><label class="aw-label" for="earn-f">Your followers</label><output for="earn-f" data-earn-f>50K</output></div>
                    <input class="range aw-range" id="earn-f" type="range" min="0" max="100" step="1" value="52" data-earn-range>
                    <div class="range-scale"><span>1K</span><span>50K</span><span>2M</span></div>
                </div>
                <div class="aw-row aw-col">
                    <span class="aw-label" id="earn-n">Niche</span>
                    <div class="aw-chips" role="radiogroup" aria-labelledby="earn-n" data-earn-group="niche">
                        <?php foreach (['Beauty' => 1.15, 'Tech' => 1.25, 'Food' => 1, 'Travel' => 1.1, 'Finance' => 1.4, 'Gaming' => 1.05] as $nn => $mult): ?>
                            <button type="button" role="radio" aria-checked="<?= $nn === 'Beauty' ? 'true' : 'false' ?>" data-val="<?= $mult ?>"><?= e($nn) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="aw-row aw-col">
                    <span class="aw-label" id="earn-c">Content</span>
                    <div class="aw-seg" role="radiogroup" aria-labelledby="earn-c" data-earn-group="type">
                        <button type="button" role="radio" aria-checked="true" data-val="10">Reel</button>
                        <button type="button" role="radio" aria-checked="false" data-val="4">Story</button>
                        <button type="button" role="radio" aria-checked="false" data-val="22">YouTube video</button>
                    </div>
                </div>
                <div class="aw-result" aria-live="polite">
                    <span>Suggested rate per post</span>
                    <b data-earn-out>-</b>
                    <div class="aw-meter" aria-hidden="true"><span data-earn-bar></span></div>
                </div>
                <ol class="aw-steps" data-earn-steps aria-label="How you get paid">
                    <li><i class="ph-bold ph-envelope-open"></i>Brief</li>
                    <li><i class="ph-bold ph-check-circle"></i>Approved</li>
                    <li><i class="ph-bold ph-upload-simple"></i>Posted</li>
                    <li><i class="ph-bold ph-hand-coins"></i>Paid</li>
                </ol>
            </div>
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
            <?php
            // Example report data (illustrative, labelled as an example on the page).
            $reach = [0.32, 0.41, 0.55, 0.62, 0.9, 1.35, 1.92, 2.4];   // millions
            $clicks = [4, 6, 9, 12, 19, 27, 38, 48];                  // thousands
            $W = 520; $H = 170; $pl = 8; $pr = 8; $pt = 26; $pb = 26;
            $px = function ($i) use ($W, $pl, $pr, $reach) { return $pl + $i * ($W - $pl - $pr) / (count($reach) - 1); };
            $py = function ($v, $max) use ($H, $pt, $pb) { return $H - $pb - $v / $max * ($H - $pt - $pb); };
            $path = function ($arr, $max) use ($px, $py) {
                $d = '';
                foreach ($arr as $i => $v) {
                    $x = $px($i); $y = $py($v, $max);
                    if ($i === 0) { $d .= 'M' . round($x, 1) . ' ' . round($y, 1); continue; }
                    $x0 = $px($i - 1); $y0 = $py($arr[$i - 1], $max); $cx = ($x0 + $x) / 2;
                    $d .= ' C' . round($cx, 1) . ' ' . round($y0, 1) . ' ' . round($cx, 1) . ' ' . round($y, 1) . ' ' . round($x, 1) . ' ' . round($y, 1);
                }
                return $d;
            };
            $reachD = $path($reach, 2.6);
            $clicksD = $path($clicks, 52);
            $peakX = $px(7); $peakY = $py(2.4, 2.6);
            ?>
            <article class="cell cell-wide" data-spotlight data-reveal>
                <div class="rep-copy">
                    <span class="ic"><i class="ph-bold ph-chart-line-up" aria-hidden="true"></i></span>
                    <h3>Reporting that shows the peak</h3>
                    <p>Live tracking during the campaign and a clear wrap report after it, with reach, engagement and clicks side by side.</p>
                </div>
                <div class="rep" data-rep>
                    <div class="rep-top">
                        <div class="rep-kpi"><b><span data-count="2.4" data-decimals="1">0</span><em>M</em></b><span>Reach</span></div>
                        <div class="rep-kpi"><b><span data-count="6.2" data-decimals="1">0</span><em>%</em></b><span>Engagement</span></div>
                        <div class="rep-kpi"><b><span data-count="48">0</span><em>K</em></b><span>Link clicks</span></div>
                        <span class="aw-pill rep-pill">Example report</span>
                    </div>
                    <div class="rep-chart">
                        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" aria-hidden="true">
                            <defs><linearGradient id="repGrad" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#EF3B2A" stop-opacity=".3"/><stop offset="1" stop-color="#EF3B2A" stop-opacity="0"/></linearGradient></defs>
                            <?php for ($g = 0; $g < 4; $g++): $gy = $pt + $g * ($H - $pt - $pb) / 3; ?><line class="rep-grid" x1="0" x2="<?= $W ?>" y1="<?= round($gy, 1) ?>" y2="<?= round($gy, 1) ?>"/><?php endfor; ?>
                            <path class="rep-area" d="<?= $reachD ?> L<?= $W - $pr ?> <?= $H - $pb ?> L<?= $pl ?> <?= $H - $pb ?> Z"/>
                            <path class="rep-line2" d="<?= $clicksD ?>" pathLength="1"/>
                            <path class="rep-line" d="<?= $reachD ?>" pathLength="1"/>
                            <?php foreach ($reach as $i => $v): ?><circle class="rep-pt" cx="<?= round($px($i), 1) ?>" cy="<?= round($py($v, 2.6), 1) ?>" r="4" style="--i:<?= $i ?>"/><?php endforeach; ?>
                        </svg>
                        <span class="rep-peak" style="left:<?= round($peakX / $W * 100, 2) ?>%;top:<?= round($peakY / $H * 100, 2) ?>%">Peak week</span>
                        <div class="rep-hits" data-rep-hits>
                            <?php foreach ($reach as $i => $v): ?><span data-x="<?= round($px($i) / $W * 100, 2) ?>" data-y="<?= round($py($v, 2.6) / $H * 100, 2) ?>" data-label="Week <?= $i + 1 ?>" data-reach="<?= $v ?>M" data-clicks="<?= $clicks[$i] ?>K"></span><?php endforeach; ?>
                        </div>
                        <div class="rep-tip" data-rep-tip hidden></div>
                    </div>
                    <div class="rep-x" aria-hidden="true"><?php for ($i = 1; $i <= 8; $i++): ?><span>W<?= $i ?></span><?php endfor; ?></div>
                    <div class="rep-legend"><span><i class="lg-reach"></i>Reach</span><span><i class="lg-clicks"></i>Link clicks</span></div>
                </div>
            </article>
            <?php
            $mix = [];
            $shares = [46, 24, 22]; $tones = ['#EF3B2A', '#10131C', '#FF6B57'];
            foreach (array_values($topPlatforms) as $k => $p) $mix[] = [$p['label'], $shares[$k], $tones[$k], $p['icon']];
            $mix[] = ['Other', 100 - array_sum(array_slice($shares, 0, count($mix))), '#FFAE92', 'ph-dots-three-circle'];
            ?>
            <article class="cell cell-mix" data-spotlight data-reveal>
                <div class="mix-head"><h3>Platform mix</h3><span class="aw-pill">Example</span></div>
                <div class="mix-donut" data-mix>
                    <svg viewBox="0 0 42 42" aria-hidden="true">
                        <circle cx="21" cy="21" r="15.915" fill="none" class="mix-track" stroke-width="6"/>
                        <?php $off = 25; foreach ($mix as $k => $m): ?>
                            <circle cx="21" cy="21" r="15.915" fill="none" stroke="<?= $m[2] ?>" stroke-width="6" class="mix-seg" data-seg="<?= $k ?>" style="--len:<?= $m[1] ?>;--i:<?= $k ?>" stroke-dasharray="<?= $m[1] ?> <?= 100 - $m[1] ?>" stroke-dashoffset="<?= $off ?>"/>
                        <?php $off -= $m[1]; endforeach; ?>
                    </svg>
                    <div class="mix-center"><b data-mix-val>46%</b><span data-mix-name>Instagram</span></div>
                </div>
                <ul class="mix-legend">
                    <?php foreach ($mix as $k => $m): ?>
                        <li data-seg-key="<?= $k ?>" data-name="<?= e($m[0]) ?>" data-val="<?= $m[1] ?>%"><i style="background:<?= $m[2] ?>"></i><?= e($m[0]) ?><b><?= $m[1] ?>%</b></li>
                    <?php endforeach; ?>
                </ul>
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
                    <div class="hs-platforms"><?php foreach (array_slice($enabled, 0, 5) as $p): ?><i class="ph-fill <?= e($p['icon']) ?>" title="<?= e($p['label']) ?>"></i><?php endforeach; ?></div>
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
        <div class="niche-grid" data-niche-grid data-collapse>
            <?php foreach ($catalog as $slug => $n): $cnt = $counts[$n['name']]['any'] ?? 0; ?>
                <a class="niche-tile" href="<?= e(url('influencers/' . $slug)) ?>" data-spotlight>
                    <span class="nt-ic"><i class="ph-bold <?= e($n['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="nt-name"><?= e($n['name']) ?></span>
                    <span class="nt-meta"><?= $cnt ? (int) $cnt . ' vetted creator' . ($cnt > 1 ? 's' : '') : 'Influencer marketing' ?></span>
                    <i class="ph-bold ph-arrow-up-right nt-arrow" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-outline btn-lg niche-more" type="button" data-niche-more aria-expanded="false" data-more="Show all <?= count($catalog) ?> niches" data-less="Show fewer niches"><span>Show all <?= count($catalog) ?> niches</span> <i class="ph-bold ph-caret-down" aria-hidden="true"></i></button>
    </div>
</section>

<!-- ============ STATS (live or from Admin > Site content) ============ -->
<?php partial('stats_band', ['stats' => $stats]); ?>

<!-- ============ CREATOR SHOWCASE (database only) ============ -->
<?php if ($creators): ?>
<section class="section">
    <div class="container showcase-head">
        <div>
            <p class="eyebrow" data-reveal><?= mark_svg('') ?> Book creators directly</p>
            <h2 class="h2" data-split>Meet creators from our network.</h2>
        </div>
        <div class="showcase-actions">
            <a class="link-arrow" href="<?= e(url('talent')) ?>">Browse all creators <i class="ph-bold ph-arrow-right" aria-hidden="true"></i></a>
            <div class="carousel-nav">
                <button class="round-btn" type="button" data-carousel-prev aria-label="Previous creators"><i class="ph-bold ph-arrow-left"></i></button>
                <button class="round-btn" type="button" data-carousel-next aria-label="Next creators"><i class="ph-bold ph-arrow-right"></i></button>
            </div>
        </div>
    </div>
    <div class="container">
        <ol class="how-book how-book-home" data-reveal>
            <li><span><i class="ph-bold ph-heart"></i></span><div><b>Shortlist</b><small>Tap the heart on any creator</small></div></li>
            <li><span><i class="ph-bold ph-paper-plane-tilt"></i></span><div><b>Request a quote</b><small>One request for all your picks</small></div></li>
            <li><span><i class="ph-bold ph-handshake"></i></span><div><b>We confirm and launch</b><small>Quote in one business day, nothing to pay online</small></div></li>
        </ol>
    </div>
    <div class="carousel" data-carousel tabindex="0" aria-label="Featured creators">
        <div class="carousel-track">
            <?php foreach ($creators as $i => $cr): ?>
                <?php partial('talent_card', ['c' => $cr, 'extra' => 'creator-card']); ?>
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
                        <?php $first = true; foreach ($topPlatforms as $pk => $p): ?>
                            <label class="chip"><input type="radio" name="platform_est" value="<?= e($pk) ?>"<?= $first ? ' checked' : '' ?>><span><i class="ph-fill <?= e($p['icon']) ?>" aria-hidden="true"></i> <?= e($p['label']) ?></span></label>
                        <?php $first = false; endforeach; ?>
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
                <?php if ($creators): ?><a class="btn btn-on-red btn-lg" href="<?= e(url('talent')) ?>">Browse talent</a><?php endif; ?>
                <a class="btn btn-on-red btn-lg" href="<?= e(url('join')) ?>">Join as a creator</a>
            </div>
        </div>
    </div>
</section>
