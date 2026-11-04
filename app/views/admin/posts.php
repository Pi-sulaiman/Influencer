<?php /** @var array $rows @var array $pg @var string $status @var string $search */ ?>
<div class="page-head">
    <div><h2>Blog posts</h2><p>Every post gets a clean URL, meta tags, schema markup and a spot in the sitemap automatically.</p></div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= e(url('sitemap.xml')) ?>" target="_blank"><i class="ph ph-tree-structure"></i>Sitemap</a>
        <a class="btn btn-primary" href="<?= e(url('admin/posts/new')) ?>"><i class="ph-bold ph-plus"></i>New post</a>
    </div>
</div>
<div class="flex-between" style="margin-bottom:14px">
    <nav class="seg" aria-label="Filter posts">
        <a href="<?= e(url('admin/posts')) ?>" class="<?= !$status ? 'is-active' : '' ?>">All</a>
        <a href="<?= e(url('admin/posts?status=published')) ?>" class="<?= $status === 'published' ? 'is-active' : '' ?>">Published</a>
        <a href="<?= e(url('admin/posts?status=draft')) ?>" class="<?= $status === 'draft' ? 'is-active' : '' ?>">Drafts</a>
    </nav>
    <form method="get" action="<?= e(url('admin/posts')) ?>" class="flex">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <input class="input input-sm" type="search" name="q" value="<?= e($search) ?>" placeholder="Search titles" aria-label="Search posts" style="width:220px">
    </form>
</div>
<section class="card">
    <?php if (!$rows): ?>
        <div class="empty"><i class="ph ph-article"></i><h3>No posts found</h3><p>Write helpful articles your customers search for. Each one is a new way for Google to find you.</p><a class="btn btn-primary mt-8" href="<?= e(url('admin/posts/new')) ?>">Write a post</a></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Title</th><th>Category</th><th>SEO</th><th>Views</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p):
                $seo = 0;
                if ($p['meta_description']) $seo++;
                if ($p['focus_keyword']) $seo++;
                if ($p['cover_image']) $seo++; ?>
                <tr>
                    <td><div class="person" style="min-width:260px">
                        <span class="avatar" style="border-radius:10px;width:54px;height:40px;background:var(--surface-3)"><?php if ($p['cover_image']): ?><img src="<?= e(upload_url($p['cover_image'])) ?>" alt="" loading="lazy"><?php endif; ?></span>
                        <span><a class="row-link" href="<?= e(url('admin/posts/' . $p['id'] . '/edit')) ?>"><?= e($p['title']) ?></a><?= $p['featured'] ? ' <i class="ph-fill ph-star" style="color:#F5A524" title="Featured"></i>' : '' ?><small style="display:block">/blog/<?= e($p['slug']) ?></small></span>
                    </div></td>
                    <td class="small"><?= e($p['category_name'] ?: '-') ?></td>
                    <td><span class="badge no-dot <?= $seo === 3 ? 'b-approved' : ($seo === 2 ? 'b-on_hold' : 'b-rejected') ?>" title="Meta description, focus keyword, cover image"><?= $seo ?>/3</span></td>
                    <td><?= number_format((int) $p['views']) ?></td>
                    <td><span class="badge b-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
                    <td class="small muted nowrap"><?= e(nice_date($p['published_at'] ?: $p['updated_at'])) ?></td>
                    <td class="nowrap"><a class="btn btn-ghost btn-sm" href="<?= e(url('blog/' . $p['slug'])) ?>" target="_blank" aria-label="View post"><i class="ph ph-eye"></i></a><a class="btn btn-outline btn-sm" href="<?= e(url('admin/posts/' . $p['id'] . '/edit')) ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php partial('admin_pager', ['pg' => $pg]); ?>
    <?php endif; ?>
</section>
