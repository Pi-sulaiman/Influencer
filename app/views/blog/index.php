<?php /** @var array $posts @var array $categories @var array|null $category @var string $search @var array $pg @var string $basePath */ ?>
<section class="page-hero" style="padding-bottom:32px">
    <?= mark_svg('page-hero-chev') ?>
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('')) ?>">Home</a><span aria-hidden="true">/</span>
            <?php if ($category): ?><a href="<?= e(url('blog')) ?>">Blog</a><span aria-hidden="true">/</span><span><?= e($category['name']) ?></span>
            <?php else: ?><span>Blog</span><?php endif; ?>
        </nav>
        <h1 class="h1"><?= $category ? e($category['name']) : 'Ideas for brands and creators.' ?></h1>
        <p class="lead"><?= $category ? e($category['description']) : 'Practical guides on influencer marketing, creator rates, briefs and campaign results.' ?></p>
    </div>
</section>

<section class="section-tight" style="padding-top:0">
    <div class="container">
        <div class="blog-tools">
            <nav class="cat-pills" aria-label="Categories">
                <a href="<?= e(url('blog')) ?>" class="<?= !$category ? 'is-active' : '' ?>">All</a>
                <?php foreach ($categories as $c): if (!$c['post_count']) continue; ?>
                    <a href="<?= e(url('blog/category/' . $c['slug'])) ?>" class="<?= $category && $category['id'] == $c['id'] ? 'is-active' : '' ?>"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="search-box" action="<?= e(url($basePath)) ?>" method="get" role="search">
                <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                <label class="sr-only" for="blog-q">Search articles</label>
                <input class="input" id="blog-q" type="search" name="q" value="<?= e($search) ?>" placeholder="Search articles">
            </form>
        </div>

        <?php if ($search !== ''): ?>
            <p class="muted" style="margin-bottom:24px"><?= (int) $pg['total'] ?> result<?= $pg['total'] == 1 ? '' : 's' ?> for "<?= e($search) ?>". <a class="accent" href="<?= e(url($basePath)) ?>">Clear search</a></p>
        <?php endif; ?>

        <?php if (!$posts): ?>
            <div class="empty-state">
                <i class="ph-bold ph-article"></i>
                <h2 class="h3">No articles yet</h2>
                <p class="muted"><?= $search !== '' ? 'Try a different search term.' : 'New articles are on the way. Check back soon.' ?></p>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts as $i => $p):
                    $variant = ($i === 0 && $pg['page'] === 1 && $search === '' && count($posts) > 2) ? 'is-featured' : '';
                    partial('post_card', ['post' => $p, 'variant' => $variant, 'i' => $i % 3]);
                endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($pg['pages'] > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($n = 1; $n <= $pg['pages']; $n++):
                    $href = $category ? url($basePath) . ($n > 1 ? '?page=' . $n : '') : url($n > 1 ? 'blog/page/' . $n : 'blog');
                    if ($search !== '') $href .= (str_contains($href, '?') ? '&' : '?') . 'q=' . rawurlencode($search); ?>
                    <?php if ($n === $pg['page']): ?><span class="is-current" aria-current="page"><?= $n ?></span>
                    <?php else: ?><a href="<?= e($href) ?>"><?= $n ?></a><?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
