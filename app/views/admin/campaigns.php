<?php /** @var array $rows @var string $status */
$modes = ['form' => ['ph-note-pencil', 'Opens the form'], 'landing' => ['ph-house-line', 'Goes to landing page'], 'url' => ['ph-arrow-square-out', 'Custom redirect']];
?>
<div class="page-head">
    <div>
        <h2>Campaigns</h2>
        <p>Each campaign gets its own link. Share it and every visit and application is tracked here.</p>
    </div>
    <div class="actions"><a class="btn btn-primary" href="<?= e(url('admin/campaigns/new')) ?>"><i class="ph-bold ph-plus"></i>New campaign</a></div>
</div>

<nav class="seg" style="margin-bottom:16px" aria-label="Filter campaigns">
    <a href="<?= e(url('admin/campaigns')) ?>" class="<?= !$status ? 'is-active' : '' ?>">All</a>
    <?php foreach (['active' => 'Active', 'paused' => 'Paused', 'closed' => 'Closed'] as $s => $l): ?>
        <a href="<?= e(url('admin/campaigns?status=' . $s)) ?>" class="<?= $status === $s ? 'is-active' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!$rows): ?>
    <section class="card empty">
        <i class="ph ph-megaphone"></i>
        <h3>No campaigns here yet</h3>
        <p>Create a campaign to get a shareable link. You choose whether the link opens the registration form, the landing page or any URL you like.</p>
        <a class="btn btn-primary mt-8" href="<?= e(url('admin/campaigns/new')) ?>">Create your first campaign</a>
    </section>
<?php else: ?>
    <div class="camp-list">
        <?php foreach ($rows as $c):
            $link = abs_url('c/' . $c['code']);
            $conv = $c['visits'] > 0 ? round($c['apps'] / $c['visits'] * 100, 1) : 0; ?>
            <article class="card camp-row">
                <a class="camp-thumb" href="<?= e(url('admin/campaigns/' . $c['id'])) ?>" aria-hidden="true" tabindex="-1"><?php if ($c['cover_image']): ?><img src="<?= e(upload_url($c['cover_image'])) ?>" alt=""><?php else: ?><?= mark_svg('') ?><?php endif; ?></a>
                <div style="min-width:0">
                    <div class="flex" style="flex-wrap:wrap;gap:8px"><a class="camp-title" href="<?= e(url('admin/campaigns/' . $c['id'])) ?>"><?= e($c['title']) ?></a><span class="badge b-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></div>
                    <div class="camp-link"><i class="ph <?= e($modes[$c['mode']][0] ?? 'ph-link') ?>" title="<?= e($modes[$c['mode']][1] ?? '') ?>"></i><code><?= e($link) ?></code><button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e($link) ?>" aria-label="Copy link"><i class="ph ph-copy"></i></button></div>
                </div>
                <div class="camp-stat"><b><?= number_format((int) $c['visits']) ?></b><span>Link visits</span></div>
                <div class="camp-stat"><b><?= number_format((int) $c['apps']) ?></b><span>Applicants</span></div>
                <div class="camp-stat"><b><?= $conv ?>%</b><span>Conversion</span></div>
                <div class="camp-actions flex">
                    <a class="btn btn-outline btn-sm" href="<?= e(url('admin/campaigns/' . $c['id'] . '/edit')) ?>">Edit</a>
                    <a class="btn btn-dark btn-sm" href="<?= e(url('admin/campaigns/' . $c['id'])) ?>">Open</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
