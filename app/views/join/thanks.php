<?php /** @var array|null $done */
$first = $done ? explode(' ', trim($done['name']))[0] : '';
?>
<section class="container error-page join-thanks">
    <div>
        <div class="thanks-mark" aria-hidden="true"><?= mark_svg('') ?><i class="ph-bold ph-check"></i></div>
        <h1><?= $first ? 'You are in, ' . e($first) . '.' : 'Thanks, you are in.' ?></h1>
        <p>
            <?php if ($done && $done['campaign']): ?>
                Your application for <b><?= e($done['campaign']) ?></b> has been received. We will email you if you are shortlisted.
            <?php elseif ($done && $done['updated']): ?>
                Your creator profile has been updated. We will be in touch when a campaign fits.
            <?php else: ?>
                Your creator profile has been received. Our team reviews new profiles within a few working days and will reach out when a campaign fits.
            <?php endif; ?>
        </p>
        <?php
        // Broadcast channels from Admin > Settings > Creator community.
        $channels = array_filter([
            ['url' => setting('broadcast_whatsapp'), 'icon' => 'ph-whatsapp-logo', 'label' => 'Join our WhatsApp channel', 'cls' => 'bc-wa'],
            ['url' => setting('broadcast_instagram'), 'icon' => 'ph-instagram-logo', 'label' => 'Join our Instagram channel', 'cls' => 'bc-ig'],
            ['url' => setting('broadcast_telegram'), 'icon' => 'ph-telegram-logo', 'label' => 'Join our Telegram channel', 'cls' => 'bc-tg'],
        ], function ($c) { return $c['url'] !== ''; });
        ?>
        <?php if ($channels): ?>
            <div class="broadcast-card">
                <span class="bc-step">One last step</span>
                <h2>Get new campaigns first.</h2>
                <p>Join the Peakilo creators channel. We post open campaigns, deadlines and tips there before anywhere else.</p>
                <div class="bc-btns">
                    <?php foreach ($channels as $c): ?>
                        <a class="btn btn-lg <?= e($c['cls']) ?>" href="<?= e($c['url']) ?>" target="_blank" rel="noopener"><i class="ph-fill <?= e($c['icon']) ?>" aria-hidden="true"></i><?= e($c['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <ol class="next-steps" style="text-align:left;max-width:440px;margin:28px auto 0">
            <li><b>Review</b><span>Our team checks your channels and audience, usually within a few working days.</span></li>
            <li><b>Match</b><span>When a brand campaign fits, we send you the brief, deliverables and budget.</span></li>
            <li><b>Create and get paid</b><span>You say yes, make the content and get paid on the date we agreed.</span></li>
        </ol>
        <div class="ctas">
            <a class="btn btn-primary" href="<?= e(url('blog/category/creator-growth')) ?>">Read creator guides</a>
            <a class="btn btn-outline" href="<?= e(url('')) ?>">Back to home</a>
        </div>
    </div>
</section>
