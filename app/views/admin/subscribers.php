<?php /** @var array $rows @var array $pg */ ?>
<div class="page-head">
    <div><h2>Subscribers</h2><p><?= number_format($pg['total']) ?> people signed up for the newsletter.</p></div>
    <div class="actions"><a class="btn btn-dark" href="<?= e(url('admin/subscribers/export')) ?>"><i class="ph ph-download-simple"></i>Export CSV</a></div>
</div>
<section class="card">
    <?php if (!$rows): ?>
        <div class="empty"><i class="ph ph-at"></i><h3>No subscribers yet</h3><p>The newsletter form in the website footer adds people here.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Email</th><th>Source</th><th>Subscribed</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $s): ?>
                <tr>
                    <td><b><?= e($s['email']) ?></b></td>
                    <td class="small"><?= e($s['source']) ?></td>
                    <td class="small muted"><?= e(nice_date($s['created_at'], 'M j, Y g:i a')) ?></td>
                    <td><form method="post" action="<?= e(url('admin/settings')) ?>" data-confirm="Remove <?= e($s['email']) ?>?"><?= csrf_field() ?><input type="hidden" name="section" value="delete_subscriber"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit" aria-label="Remove"><i class="ph ph-trash"></i></button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php partial('admin_pager', ['pg' => $pg]); ?>
    <?php endif; ?>
</section>
