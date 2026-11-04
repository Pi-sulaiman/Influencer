<?php
/** @var array $post @var string $variant */
$variant = $variant ?? '';
$i = $i ?? 0;
$href = url('blog/' . $post['slug']);
?>
<article class="post-card <?= e($variant) ?>" data-reveal style="--i:<?= (int) $i ?>">
    <div class="pc-media">
        <?php if ($post['cover_image']): ?>
            <img src="<?= e(upload_url($post['cover_image'])) ?>" alt="<?= e($post['cover_alt'] ?: $post['title']) ?>" loading="lazy" width="800" height="450">
        <?php endif; ?>
    </div>
    <div class="pc-body">
        <div class="pc-meta">
            <?php if (!empty($post['category_name'])): ?><span class="cat"><?= e($post['category_name']) ?></span><?php endif; ?>
            <time datetime="<?= e(date('c', strtotime($post['published_at'] ?: $post['created_at']))) ?>"><?= e(nice_date($post['published_at'] ?: $post['created_at'])) ?></time>
            <span><?= reading_time($post['content']) ?> min read</span>
        </div>
        <h3><a href="<?= e($href) ?>"><?= e($post['title']) ?></a></h3>
        <?php if ($variant !== 'is-row'): ?><p><?= e(str_limit($post['excerpt'] ?: $post['content'], 150)) ?></p><?php endif; ?>
    </div>
</article>
