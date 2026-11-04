<?php /** @var array $rows */ ?>
<div class="page-head"><div><h2>Categories</h2><p>Each category gets its own SEO friendly page at /blog/category/name.</p></div></div>
<div class="grid grid-side">
    <section class="card">
        <?php if (!$rows): ?>
            <div class="empty"><i class="ph ph-folders"></i><h3>No categories yet</h3><p>Add your first category on the right.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><b><?= e($r['name']) ?></b><div class="small muted"><?= e(str_limit($r['description'], 80)) ?></div></td>
                        <td class="small"><a href="<?= e(url('blog/category/' . $r['slug'])) ?>" target="_blank">/blog/category/<?= e($r['slug']) ?></a></td>
                        <td><?= (int) $r['post_count'] ?></td>
                        <td class="nowrap">
                            <button class="btn btn-outline btn-sm" type="button" data-edit-cat='<?= e(json_encode(['id' => $r['id'], 'name' => $r['name'], 'slug' => $r['slug'], 'description' => $r['description']])) ?>'>Edit</button>
                            <form method="post" action="<?= e(url('admin/categories')) ?>" style="display:inline" data-confirm="Delete the category <?= e($r['name']) ?>? Its posts will become uncategorised.">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-ghost btn-sm" type="submit" aria-label="Delete"><i class="ph ph-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
    <form class="card card-pad" method="post" action="<?= e(url('admin/categories')) ?>" data-cat-form>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="" data-cat-id>
        <h2 style="font-size:16px;margin-bottom:16px" data-cat-heading>Add a category</h2>
        <div class="field"><label for="cname">Name</label><input class="input" id="cname" name="name" required></div>
        <div class="field mt-16"><label for="cslug">Slug <span class="muted">(optional)</span></label><input class="input" id="cslug" name="slug" placeholder="auto"></div>
        <div class="field mt-16"><label for="cdesc">Description</label><textarea class="textarea" id="cdesc" name="description" rows="3" placeholder="Shown on the category page and used as its meta description"></textarea></div>
        <button class="btn btn-primary btn-block mt-16" type="submit" data-cat-submit>Add category</button>
        <button class="btn btn-ghost btn-block mt-8" type="button" data-cat-reset hidden>Cancel editing</button>
    </form>
</div>
