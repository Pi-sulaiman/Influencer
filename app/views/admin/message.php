<?php /** @var array $m */
$reply = 'mailto:' . rawurlencode($m['email']) . '?subject=' . rawurlencode('Re: your message to ' . setting('site_name', 'Peakilo'));
?>
<div class="page-head">
    <div class="flex">
        <a class="icon-btn" href="<?= e(url('admin/messages')) ?>" aria-label="Back to messages"><i class="ph-bold ph-arrow-left"></i></a>
        <div><h2><?= e($m['name']) ?></h2><p><?= e(ucfirst($m['inquiry_type'] ?: 'other')) ?> enquiry &middot; <?= e(nice_date($m['created_at'], 'M j, Y \a\t g:i a')) ?></p></div>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="<?= e($reply) ?>"><i class="ph ph-arrow-bend-up-left"></i>Reply by email</a>
    </div>
</div>
<div class="grid grid-side">
    <div class="grid" style="align-content:start">
        <?php if (!empty($m['goal']) || !empty($m['niches'])): ?>
        <section class="card">
            <div class="card-head"><h2>Campaign brief</h2><span class="badge b-new">Brand</span></div>
            <div class="card-body">
                <dl class="dl">
                    <dt>Goal</dt><dd><?= e($m['goal'] ?: '-') ?></dd>
                    <dt>Niches</dt><dd><div class="tags"><?php foreach (csv_list($m['niches']) as $n): ?><a class="tag" href="<?= e(url('admin/influencers?status=approved&niche=' . rawurlencode($n))) ?>" title="See approved <?= e($n) ?> influencers"><?= e($n) ?></a><?php endforeach; ?></div></dd>
                    <dt>Platforms</dt><dd><?= e($m['platforms'] ?: 'Any') ?></dd>
                    <dt>Creator size</dt><dd><?= e($m['tiers'] ?: 'Any') ?></dd>
                    <dt>Budget</dt><dd><?= e($m['budget'] ?: '-') ?></dd>
                    <dt>Launch</dt><dd><?= e($m['timeline'] ?: '-') ?></dd>
                    <dt>Target location</dt><dd><?= e($m['target_location'] ?: '-') ?></dd>
                    <dt>Website</dt><dd><?php if (!empty($m['website'])): $w = preg_match('#^https?://#', $m['website']) ? $m['website'] : 'https://' . $m['website']; ?><a href="<?= e($w) ?>" target="_blank" rel="noopener"><?= e($m['website']) ?></a><?php else: ?>-<?php endif; ?></dd>
                </dl>
                <?php if ($first = csv_list($m['niches'])[0] ?? null): ?>
                    <a class="btn btn-dark btn-sm mt-16" href="<?= e(url('admin/influencers?status=approved&niche=' . rawurlencode($first))) ?>"><i class="ph ph-users-three"></i>Find matching influencers</a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        <section class="card card-pad"><p class="msg-body"><?= e($m['message']) ?></p></section>
    </div>
    <aside class="grid">
        <section class="card card-pad">
            <dl class="dl" style="grid-template-columns:90px 1fr">
                <dt>Email</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd>
                <dt>Company</dt><dd><?= e($m['company'] ?: '-') ?></dd>
                <dt>Phone</dt><dd><?= $m['phone'] ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $m['phone'])) . '">' . e($m['phone']) . '</a>' : '-' ?></dd>
                <dt>Budget</dt><dd><?= e($m['budget'] ?: '-') ?></dd>
            </dl>
        </section>
        <form class="card card-pad" method="post" action="<?= e(url('admin/messages/' . $m['id'] . '/delete')) ?>" data-confirm="Delete this message?">
            <?= csrf_field() ?>
            <button class="btn btn-danger btn-block" type="submit"><i class="ph ph-trash"></i>Delete message</button>
        </form>
    </aside>
</div>
