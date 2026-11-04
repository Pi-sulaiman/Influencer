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
        <div class="ctas">
            <a class="btn btn-primary" href="<?= e(url('blog/category/creator-growth')) ?>">Read creator guides</a>
            <a class="btn btn-outline" href="<?= e(url('')) ?>">Back to home</a>
        </div>
    </div>
</section>
