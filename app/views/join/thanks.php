<?php
/** @var array|null $done @var array|null $card @var array|null $creator */
$first = $done ? explode(' ', trim($done['name']))[0] : '';
$phone = $creator['phone'] ?? '';
// Show the number they gave us, with the middle hidden: +91 ••••• 43210
$digits = preg_replace('/\D/', '', $phone);
$maskedPhone = $digits !== '' ? (strpos(trim($phone), '+') === 0 ? '+' : '') . (strlen($digits) > 10 ? substr($digits, 0, strlen($digits) - 10) . ' ' : '') . '••••• ' . substr($digits, -5) : '';

$waLink = setting('broadcast_whatsapp');
$igUrl = setting('instagram_url');
$igHandle = '';
if ($igUrl) {
    $parts = array_values(array_filter(explode('/', (string) parse_url($igUrl, PHP_URL_PATH))));
    $igHandle = $parts[0] ?? '';
}
$extra = array_filter([
    ['url' => setting('broadcast_instagram'), 'icon' => 'ph-instagram-logo', 'label' => 'Instagram broadcast channel'],
    ['url' => setting('broadcast_telegram'), 'icon' => 'ph-telegram-logo', 'label' => 'Telegram channel'],
], function ($c) { return $c['url'] !== ''; });

$tasks = 1 + ($card ? 1 : 0) + ($waLink ? 1 : 0) + ($igUrl ? 1 : 0);
$doneCount = 1;
?>
<section class="container join-done" data-setup data-key="<?= e($card['code'] ?? 'guest') ?>">
    <header class="jd-head">
        <div class="thanks-mark" aria-hidden="true"><?= mark_svg('') ?><i class="ph-bold ph-check"></i></div>
        <p class="eyebrow"><?= $done && $done['campaign'] ? 'Application received' : 'Profile received' ?></p>
        <h1 class="h2"><?= $first ? 'You are in, ' . e($first) . '.' : 'Thanks, you are in.' ?></h1>
        <p class="lead">
            <?php if ($done && $done['campaign']): ?>
                Your application for <b><?= e($done['campaign']) ?></b> is with our team.
            <?php elseif ($done && $done['updated']): ?>
                Your creator profile is updated.
            <?php else: ?>
                Your creator profile is with our team.
            <?php endif; ?>
            We will message you on WhatsApp<?= $maskedPhone ? ' at <b class="nowrap">' . e($maskedPhone) . '</b>' : '' ?> when a brand campaign fits you.
        </p>
    </header>

    <div class="jd-grid<?= $card ? '' : ' no-card' ?>">
        <?php if ($card): ?>
            <section class="jd-card" data-cc aria-labelledby="cc-title">
                <script type="application/json" data-cc-data><?= json_encode($card, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
                <div class="cc-top">
                    <h2 id="cc-title">Your creator card</h2>
                    <span class="cc-code"><?= e($card['code']) ?></span>
                </div>
                <div class="cc-stage" data-cc-stage>
                    <canvas data-cc-canvas width="1080" height="1350" role="img" aria-label="Peakilo creator card for <?= e($card['name']) ?>"></canvas>
                    <span class="cc-loading" data-cc-loading><span class="spinner" aria-hidden="true"></span> Building your card</span>
                </div>
                <div class="cc-controls">
                    <div class="cc-row">
                        <span class="cc-label" id="cc-style">Style</span>
                        <div class="cc-swatches" role="radiogroup" aria-labelledby="cc-style" data-cc-group="theme">
                            <button type="button" role="radio" aria-checked="true" data-val="red" class="sw-red"><span class="sr-only">Peakilo red</span></button>
                            <button type="button" role="radio" aria-checked="false" data-val="ink" class="sw-ink"><span class="sr-only">Night</span></button>
                            <button type="button" role="radio" aria-checked="false" data-val="cream" class="sw-cream"><span class="sr-only">Cream</span></button>
                        </div>
                    </div>
                    <div class="cc-row">
                        <span class="cc-label" id="cc-size">Size</span>
                        <div class="cc-seg" role="radiogroup" aria-labelledby="cc-size" data-cc-group="format">
                            <button type="button" role="radio" aria-checked="true" data-val="post"><i class="ph-bold ph-square" aria-hidden="true"></i>Post</button>
                            <button type="button" role="radio" aria-checked="false" data-val="story"><i class="ph-bold ph-device-mobile" aria-hidden="true"></i>Story</button>
                        </div>
                    </div>
                    <?php if ($card['feeFrom']): ?>
                        <label class="cc-toggle">
                            <input type="checkbox" data-cc-rates>
                            <span class="tr-switch" aria-hidden="true"></span>
                            <span>Show my rates <small>Turn on for a rate card you can send to brands</small></span>
                        </label>
                    <?php endif; ?>
                    <div class="cc-actions">
                        <button class="btn btn-primary btn-lg" type="button" data-cc-download><i class="ph-bold ph-download-simple" aria-hidden="true"></i>Download card</button>
                        <button class="btn btn-outline btn-lg" type="button" data-cc-share hidden><i class="ph-bold ph-share-network" aria-hidden="true"></i>Share</button>
                    </div>
                    <p class="cc-tip"><i class="ph-bold ph-sparkle" aria-hidden="true"></i>Post it on your story<?= $igHandle ? ' and tag <b>@' . e($igHandle) . '</b>' : '' ?>. Brands love creators who are easy to book.</p>
                </div>
            </section>
        <?php endif; ?>

        <section class="jd-setup" aria-labelledby="setup-title">
            <div class="setup-head">
                <div class="setup-ring" style="--p:<?= round($doneCount / $tasks, 3) ?>" data-setup-ring aria-hidden="true">
                    <svg viewBox="0 0 44 44"><circle cx="22" cy="22" r="19" class="sr-track"/><circle cx="22" cy="22" r="19" class="sr-fill" pathLength="1"/></svg>
                    <b data-setup-count><?= $doneCount ?>/<?= $tasks ?></b>
                </div>
                <div>
                    <h2 id="setup-title">Finish setting up</h2>
                    <p data-setup-sub>A couple of taps so you never miss a paid campaign.</p>
                </div>
            </div>
            <ol class="setup-list" data-setup-total="<?= $tasks ?>">
                <li class="is-done">
                    <span class="sl-check" aria-hidden="true"><i class="ph-bold ph-check"></i></span>
                    <div class="sl-txt"><b>Profile sent</b><small>We review new profiles within a few working days.</small></div>
                </li>
                <?php if ($card): ?>
                    <li data-task="card">
                        <span class="sl-check" aria-hidden="true"><i class="ph-bold ph-check"></i></span>
                        <div class="sl-txt"><b>Save your creator card</b><small>Share it on your story or keep it for brand pitches.</small></div>
                        <button class="btn btn-sm btn-outline" type="button" data-cc-download>Save</button>
                    </li>
                <?php endif; ?>
                <?php if ($waLink): ?>
                    <li data-task="wa" class="is-key">
                        <span class="sl-check" aria-hidden="true"><i class="ph-bold ph-check"></i></span>
                        <div class="sl-txt">
                            <span class="sl-tag">Most important</span>
                            <b>Turn on campaign alerts</b>
                            <small>Join the Peakilo creator community on WhatsApp. New paid campaigns are posted there first, so members get the first chance to apply.</small>
                            <a class="btn btn-wa btn-lg" href="<?= e($waLink) ?>" target="_blank" rel="noopener" data-task-link><i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i>Join on WhatsApp</a>
                        </div>
                    </li>
                <?php endif; ?>
                <?php if ($igUrl): ?>
                    <li data-task="ig">
                        <span class="sl-check" aria-hidden="true"><i class="ph-bold ph-check"></i></span>
                        <div class="sl-txt">
                            <b>Follow <?= $igHandle ? '@' . e($igHandle) : 'Peakilo' ?> on Instagram</b>
                            <small>Open calls, creator features and tips from campaigns we run.</small>
                            <a class="btn btn-ig" href="<?= e($igUrl) ?>" target="_blank" rel="noopener" data-task-link><i class="ph-fill ph-instagram-logo" aria-hidden="true"></i>Follow on Instagram</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ol>
            <p class="setup-done" data-setup-done hidden><i class="ph-fill ph-confetti" aria-hidden="true"></i><span><b>All set.</b> Keep an eye on WhatsApp for your first brief.</span></p>
            <?php if ($extra): ?>
                <div class="setup-extra">
                    <span>Also on</span>
                    <?php foreach ($extra as $c): ?><a href="<?= e($c['url']) ?>" target="_blank" rel="noopener"><i class="ph-fill <?= e($c['icon']) ?>" aria-hidden="true"></i><?= e($c['label']) ?></a><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="jd-next">
        <h2 class="h3">What happens next</h2>
        <ol class="next-steps">
            <li><b>Review</b><span>Our team checks your channels and audience, usually within a few working days.</span></li>
            <li><b>Match</b><span>When a brand campaign fits, we send you the brief, deliverables and budget on WhatsApp.</span></li>
            <li><b>Create and get paid</b><span>You say yes, make the content and get paid on the date we agreed.</span></li>
        </ol>
        <div class="ctas">
            <a class="btn btn-outline" href="<?= e(url('blog/category/creator-growth')) ?>">Read creator guides</a>
            <a class="btn btn-ghost" href="<?= e(url('')) ?>">Back to home</a>
        </div>
    </div>
</section>
