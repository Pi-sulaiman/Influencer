<?php /** @var array $rows @var array $pg @var string $type */ ?>
<div class="page-head"><div><h2>Messages</h2><p>Enquiries sent through the contact form.</p></div></div>
<nav class="seg" style="margin-bottom:14px" aria-label="Filter messages">
    <a href="<?= e(url('admin/messages')) ?>" class="<?= !$type ? 'is-active' : '' ?>">All</a>
    <a href="<?= e(url('admin/messages?type=brand')) ?>" class="<?= $type === 'brand' ? 'is-active' : '' ?>">Brands</a>
    <a href="<?= e(url('admin/messages?type=creator')) ?>" class="<?= $type === 'creator' ? 'is-active' : '' ?>">Creators</a>
    <a href="<?= e(url('admin/messages?type=other')) ?>" class="<?= $type === 'other' ? 'is-active' : '' ?>">Other</a>
</nav>
<section class="card">
    <?php if (!$rows): ?>
        <div class="empty"><i class="ph ph-envelope-simple-open"></i><h3>Inbox is empty</h3><p>When someone uses the contact form, their message lands here and in your email.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>From</th><th>Type</th><th>Budget</th><th>Message</th><th>Received</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $m): ?>
                <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
                    <td><a class="person" href="<?= e(url('admin/messages/' . $m['id'])) ?>"><span class="avatar" style="background:<?= $m['inquiry_type'] === 'brand' ? '#10131C' : '#EF3B2A' ?>"><?= e(initials($m['name'])) ?></span><span><b><?= e($m['name']) ?></b><small><?= e($m['company'] ?: $m['email']) ?></small></span></a></td>
                    <td><span class="badge no-dot"><?= e(ucfirst($m['inquiry_type'] ?: 'other')) ?></span><?php if (!empty($m['creator_ids'])): $nc = count(array_filter(explode(',', $m['creator_ids']))); ?><div style="margin-top:4px"><span class="badge b-new"><i class="ph-fill ph-heart"></i>Quote &middot; <?= $nc ?> creator<?= $nc > 1 ? 's' : '' ?></span></div><?php endif; ?></td>
                    <td class="small nowrap"><?= e($m['budget'] ?: '-') ?></td>
                    <td class="small" style="max-width:420px"><a href="<?= e(url('admin/messages/' . $m['id'])) ?>"><?php if (!empty($m['goal'])): ?><b><?= e($m['goal']) ?></b><?= !empty($m['niches']) ? ' &middot; ' . e($m['niches']) : '' ?><br><?php endif; ?><?= e(str_limit($m['message'], 110)) ?></a></td>
                    <td class="small muted nowrap"><?= e(time_ago($m['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php partial('admin_pager', ['pg' => $pg]); ?>
    <?php endif; ?>
</section>
