<?php /** @var int $code @var string $title @var string $message */ ?>
<section class="container error-page">
    <div>
        <div class="code" aria-hidden="true"><?php if ($code == 404): ?>4<?= mark_svg('') ?>4<?php else: ?><?= (int) $code ?><?php endif; ?></div>
        <h1><?= e($title) ?></h1>
        <p><?= e($message) ?></p>
        <div class="ctas">
            <a class="btn btn-primary" href="<?= e(url('')) ?>">Go to the homepage</a>
            <a class="btn btn-outline" href="<?= e(url('blog')) ?>">Read the blog</a>
        </div>
    </div>
</section>
