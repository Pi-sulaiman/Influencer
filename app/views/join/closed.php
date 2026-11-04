<?php /** @var array $campaign */ ?>
<section class="container error-page">
    <div>
        <div class="code" aria-hidden="true" style="font-size:clamp(4rem,3rem + 6vw,7rem)"><?= mark_svg('') ?></div>
        <h1><?= e($campaign['title']) ?> is <?= $campaign['status'] === 'paused' ? 'paused' : 'closed' ?>.</h1>
        <p>This campaign is not accepting new applications right now. You can still join the Peakilo creator network and we will match you with the next one that fits.</p>
        <div class="ctas">
            <a class="btn btn-primary" href="<?= e(url('join')) ?>">Join as a creator</a>
            <a class="btn btn-outline" href="<?= e(url('')) ?>">Back to home</a>
        </div>
    </div>
</section>
