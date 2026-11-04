<?php /** @var array $post @var string $html @var array $toc @var array $related @var array|null $prev @var array|null $next @var bool $isPreview */
$postUrl = abs_url('blog/' . $post['slug']);
$enc = rawurlencode($postUrl);
$encTitle = rawurlencode($post['title']);
$date = $post['published_at'] ?: $post['created_at'];
?>
<?php if ($isPreview): ?>
    <div class="ref-banner"><div class="container"><span>Preview of a <b><?= e(status_label($post['status'])) ?></b> post. Only admins can see this.</span><a href="<?= e(url('admin/posts/' . $post['id'] . '/edit')) ?>">Back to editor</a></div></div>
<?php endif; ?>

<article>
    <header class="container article-head">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span>
            <a href="<?= e(url('blog')) ?>">Blog</a>
            <?php if ($post['category_slug']): ?><span aria-hidden="true">/</span><a href="<?= e(url('blog/category/' . $post['category_slug'])) ?>"><?= e($post['category_name']) ?></a><?php endif; ?>
        </nav>
        <h1 data-reveal><?= e($post['title']) ?></h1>
        <?php if ($post['excerpt']): ?><p class="lead" data-reveal style="--i:1"><?= e($post['excerpt']) ?></p><?php endif; ?>
        <div class="article-meta" data-reveal style="--i:2">
            <span class="author"><span class="avatar"><?= e(initials($post['author_name'] ?: 'Peakilo Team')) ?></span><span><?= e($post['author_name'] ?: 'Peakilo Team') ?></span></span>
            <time datetime="<?= e(date('c', strtotime($date))) ?>"><?= e(nice_date($date, 'F j, Y')) ?></time>
            <span><?= reading_time($post['content']) ?> min read</span>
        </div>
    </header>

    <?php if ($post['cover_image']): ?>
        <div class="container"><figure class="article-cover" data-reveal="scale"><img src="<?= e(upload_url($post['cover_image'])) ?>" alt="<?= e($post['cover_alt'] ?: $post['title']) ?>" width="1600" height="800" fetchpriority="high"></figure></div>
    <?php endif; ?>

    <div class="container article-layout">
        <aside>
            <?php if (count($toc) > 1): ?>
                <nav class="toc" aria-label="On this page" data-toc>
                    <p>On this page</p>
                    <ol>
                        <?php foreach ($toc as $t): ?><li><a class="<?= e($t['level']) ?>" href="#<?= e($t['id']) ?>"><?= e($t['text']) ?></a></li><?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </aside>

        <div>
            <div class="prose"><?= $html ?></div>

            <div class="author-box">
                <span class="avatar"><?= mark_svg('') ?></span>
                <div>
                    <b><?= e($post['author_name'] ?: 'Peakilo Team') ?></b>
                    <p>The Peakilo team plans and runs influencer campaigns for brands and helps creators grow sustainable businesses.</p>
                </div>
            </div>

            <?php if ($prev || $next): ?>
                <nav class="post-nav" aria-label="More articles">
                    <?php if ($prev): ?><a href="<?= e(url('blog/' . $prev['slug'])) ?>" rel="prev"><small>Previous</small><b><?= e($prev['title']) ?></b></a><?php endif; ?>
                    <?php if ($next): ?><a class="next" href="<?= e(url('blog/' . $next['slug'])) ?>" rel="next"><small>Next</small><b><?= e($next['title']) ?></b></a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>

        <aside class="share" aria-label="Share this article">
            <p>Share</p>
            <div class="row">
                <a class="round-btn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $enc ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
                <a class="round-btn" href="https://x.com/intent/post?url=<?= $enc ?>&text=<?= $encTitle ?>" target="_blank" rel="noopener" aria-label="Share on X"><i class="ph ph-x-logo"></i></a>
                <a class="round-btn" href="https://wa.me/?text=<?= $encTitle ?>%20<?= $enc ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><i class="ph ph-whatsapp-logo"></i></a>
                <button class="round-btn" type="button" data-copy="<?= e($postUrl) ?>" aria-label="Copy link"><i class="ph ph-link"></i></button>
                <button class="round-btn" type="button" data-share aria-label="More sharing options"><i class="ph ph-share-network"></i></button>
            </div>
        </aside>
    </div>
</article>

<?php if ($related): ?>
<section class="section" style="padding-bottom:0">
    <div class="container">
        <div class="blog-head">
            <h2 class="h2">Keep reading</h2>
            <a class="link-arrow" href="<?= e(url('blog')) ?>">All articles <i class="ph-bold ph-arrow-right"></i></a>
        </div>
        <div class="posts-grid">
            <?php foreach ($related as $i => $r) partial('post_card', ['post' => $r, 'i' => $i]); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" style="padding-bottom:0">
    <div class="container">
        <div class="cta-block" data-reveal="scale">
            <div class="cta-chevs" aria-hidden="true"><?= mark_svg('') ?><?= mark_svg('') ?></div>
            <h2>Put this into practice.</h2>
            <div>
                <p>Brands can start a campaign with us. Creators can join the network for free.</p>
                <div class="ctas">
                    <a class="btn btn-light btn-lg" href="<?= e(url('contact?type=brand')) ?>">Start a campaign <i class="ph-bold ph-arrow-up-right"></i></a>
                    <a class="btn btn-on-red btn-lg" href="<?= e(url('join')) ?>">Join as a creator</a>
                </div>
            </div>
        </div>
    </div>
</section>
