<?php
/** @var array $inf @var array $apps @var mixed $prevId @var mixed $nextId */
$platforms = platform_options();
$wa = preg_replace('/\D/', '', (string) $inf['phone']);
?>
<div class="page-head">
    <div class="flex">
        <a class="icon-btn" href="<?= e(url('admin/influencers')) ?>" aria-label="Back to influencers"><i class="ph-bold ph-arrow-left"></i></a>
        <div><h2 style="font-size:20px">Influencer profile</h2><p>Registered <?= e(nice_date($inf['created_at'], 'M j, Y \a\t g:i a')) ?> &middot; Source: <?= e($inf['source'] ?: 'direct') ?></p></div>
    </div>
    <div class="actions">
        <?php if ($prevId): ?><a class="icon-btn" href="<?= e(url('admin/influencers/' . $prevId)) ?>" aria-label="Previous profile"><i class="ph-bold ph-caret-left"></i></a><?php endif; ?>
        <?php if ($nextId): ?><a class="icon-btn" href="<?= e(url('admin/influencers/' . $nextId)) ?>" aria-label="Next profile"><i class="ph-bold ph-caret-right"></i></a><?php endif; ?>
    </div>
</div>

<div class="grid grid-side">
    <div class="grid">
        <section class="card profile-hero">
            <div class="profile-photo">
                <?php if ($inf['photo']): ?><a href="<?= e(upload_url($inf['photo'])) ?>" target="_blank" rel="noopener" style="display:contents"><img src="<?= e(upload_url($inf['photo'])) ?>" alt="<?= e($inf['full_name']) ?>"></a><?php else: ?><?= e(initials($inf['full_name'])) ?><?php endif; ?>
            </div>
            <div>
                <div class="flex" style="flex-wrap:wrap">
                    <h2 class="profile-name"><?= e($inf['full_name']) ?></h2>
                    <span class="badge b-<?= e($inf['status']) ?>"><?= e(status_label($inf['status'])) ?></span>
                    <?php if ($inf['featured']): ?><span class="badge no-dot" style="background:#FFF4D6;color:#8A5A00"><i class="ph-fill ph-star"></i>Featured on site</span><?php endif; ?>
                </div>
                <div class="profile-meta">
                    <?php if ($inf['city'] || $inf['country']): ?><span><i class="ph ph-map-pin"></i><?= e(trim($inf['city'] . ', ' . $inf['country'], ', ')) ?></span><?php endif; ?>
                    <?php if ($inf['gender']): ?><span><i class="ph ph-user"></i><?= e($inf['gender']) ?></span><?php endif; ?>
                    <?php if ($inf['languages']): ?><span><i class="ph ph-translate"></i><?= e($inf['languages']) ?></span><?php endif; ?>
                    <?php if ($inf['primary_platform']): ?><span><i class="ph-fill <?= e($platforms[$inf['primary_platform']]['icon'] ?? 'ph-globe') ?>"></i>Main: <?= e($platforms[$inf['primary_platform']]['label'] ?? $inf['primary_platform']) ?></span><?php endif; ?>
                </div>
                <div class="profile-actions">
                    <a class="btn btn-dark btn-sm" href="mailto:<?= e($inf['email']) ?>"><i class="ph ph-envelope-simple"></i><?= e($inf['email']) ?></a>
                    <?php if ($inf['phone']): ?>
                        <a class="btn btn-outline btn-sm" href="tel:<?= e(preg_replace('/[^\d+]/', '', $inf['phone'])) ?>"><i class="ph ph-phone"></i><?= e($inf['phone']) ?></a>
                        <?php if (strlen($wa) >= 8): ?><a class="btn btn-outline btn-sm" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><i class="ph ph-whatsapp-logo"></i>WhatsApp</a><?php endif; ?>
                    <?php endif; ?>
                    <button class="btn btn-ghost btn-sm" type="button" data-copy="<?= e($inf['email']) ?>" data-copied="Email copied"><i class="ph ph-copy"></i>Copy email</button>
                </div>
                <div class="profile-stats">
                    <div><b><?= e(short_number($inf['total_followers'])) ?></b><span>Total reach</span></div>
                    <div class="fee"><b><?= e(fee_range($inf['fee_min'], $inf['fee_max'], $inf['currency'])) ?></b><span>Fee range (<?= e($inf['currency']) ?>)</span></div>
                    <div><b><?= $inf['engagement_rate'] !== null ? e($inf['engagement_rate'] + 0) . '%' : '-' ?></b><span>Engagement rate</span></div>
                    <div><b><?= count($apps) ?></b><span>Campaign applications</span></div>
                </div>
            </div>
        </section>

        <div class="grid grid-2">
            <section class="card">
                <div class="card-head"><h2>Channels</h2></div>
                <div class="card-body socials-list">
                    <?php $any = false; foreach ($platforms as $k => $p): if (empty($inf[$k])) continue; $any = true; ?>
                        <a class="social-row" href="<?= e(social_url($k, $inf[$k])) ?>" target="_blank" rel="noopener">
                            <i class="ph-fill <?= e($p['icon']) ?>"></i>
                            <div style="min-width:0"><div class="s-handle"><?= e($inf[$k]) ?></div><div class="small muted"><?= e($p['label']) ?></div></div>
                            <span class="s-count"><?= $inf[$k . '_followers'] !== null ? e(short_number($inf[$k . '_followers'])) : '-' ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($inf['website']): $any = true; ?>
                        <a class="social-row" href="<?= e(preg_match('#^https?://#', $inf['website']) ? $inf['website'] : 'https://' . $inf['website']) ?>" target="_blank" rel="noopener"><i class="ph ph-globe"></i><div style="min-width:0"><div class="s-handle"><?= e($inf['website']) ?></div><div class="small muted">Website or media kit</div></div><i class="ph ph-arrow-up-right" style="margin-left:auto;font-size:16px"></i></a>
                    <?php endif; ?>
                    <?php if (!$any): ?><p class="muted">No channels added.</p><?php endif; ?>
                </div>
            </section>
            <section class="card">
                <div class="card-head"><h2>Content and rates</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Niches</dt><dd><div class="tags"><?php foreach (csv_list($inf['niches']) as $n): ?><span class="tag"><?= e($n) ?></span><?php endforeach; ?></div></dd>
                        <dt>Content types</dt><dd><?= e($inf['content_types'] ?: '-') ?></dd>
                        <dt>Minimum fee</dt><dd><?= e(money($inf['fee_min'], $inf['currency']) ?: '-') ?></dd>
                        <dt>Maximum fee</dt><dd><?= e(money($inf['fee_max'], $inf['currency']) ?: '-') ?></dd>
                        <dt>Included</dt><dd><?= e($inf['fee_notes'] ?: '-') ?></dd>
                        <dt>Feature consent</dt><dd><?= $inf['consent_feature'] ? '<span class="badge b-approved">Allowed</span>' : '<span class="badge">Not given</span>' ?></dd>
                        <dt>Last updated</dt><dd><?= e(nice_date($inf['updated_at'], 'M j, Y g:i a')) ?></dd>
                    </dl>
                </div>
            </section>
        </div>

        <?php if ($inf['bio']): ?>
            <section class="card"><div class="card-head"><h2>Bio</h2></div><div class="card-body"><p class="msg-body"><?= e($inf['bio']) ?></p></div></section>
        <?php endif; ?>

        <section class="card">
            <div class="card-head"><h2>Campaign applications</h2></div>
            <?php if (!$apps): ?>
                <div class="empty"><i class="ph ph-megaphone"></i><p>This influencer joined directly and has not applied to a campaign yet.</p></div>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Campaign</th><th>Pitch</th><th>Applied</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($apps as $a): ?>
                        <tr>
                            <td><a class="row-link" href="<?= e(url('admin/campaigns/' . $a['campaign_id'])) ?>"><?= e($a['title']) ?></a><div class="small muted"><?= e($a['brand_name']) ?></div></td>
                            <td class="small" style="max-width:340px"><?= e(str_limit($a['pitch'] ?: '-', 160)) ?></td>
                            <td class="small muted nowrap"><?= e(nice_date($a['created_at'])) ?></td>
                            <td>
                                <form method="post" action="<?= e(url('admin/applications/' . $a['id'])) ?>" data-app-status>
                                    <?= csrf_field() ?>
                                    <select class="select app-status" name="status" aria-label="Application status">
                                        <?php foreach (['applied', 'shortlisted', 'selected', 'rejected'] as $s): ?><option value="<?= $s ?>"<?= $a['status'] === $s ? ' selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="grid" style="position:sticky;top:88px">
        <form class="card card-pad" method="post" action="<?= e(url('admin/influencers/' . $inf['id'])) ?>">
            <?= csrf_field() ?>
            <h2 style="font-size:16px;margin-bottom:16px">Review</h2>
            <div class="field">
                <label for="st">Network status</label>
                <select class="select" id="st" name="status">
                    <?php foreach (['new', 'approved', 'on_hold', 'rejected'] as $s): ?><option value="<?= $s ?>"<?= $inf['status'] === $s ? ' selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field mt-16">
                <span class="label">Internal rating</span>
                <div class="stars" role="radiogroup" aria-label="Rating">
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                        <input type="radio" id="r<?= $s ?>" name="rating" value="<?= $s ?>"<?= (int) $inf['rating'] === $s ? ' checked' : '' ?>><label for="r<?= $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>"><i class="ph-fill ph-star"></i></label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="field mt-16">
                <label class="check"><input type="checkbox" name="featured" value="1"<?= $inf['featured'] ? ' checked' : '' ?><?= $inf['consent_feature'] && $inf['photo'] ? '' : ' disabled' ?>>Feature on the website</label>
                <?php if (!$inf['consent_feature']): ?><span class="hint">The influencer did not allow featuring.</span><?php endif; ?>
            </div>
            <div class="field mt-16">
                <label for="notes">Private notes</label>
                <textarea class="textarea notes-log" id="notes" name="admin_notes" rows="6" placeholder="Only your team can see this"><?= e($inf['admin_notes']) ?></textarea>
            </div>
            <button class="btn btn-primary btn-block mt-16" type="submit">Save review</button>
        </form>
        <form class="card card-pad" method="post" action="<?= e(url('admin/influencers/' . $inf['id'] . '/delete')) ?>" data-confirm="Delete <?= e($inf['full_name']) ?> permanently? This also removes their photo and campaign applications.">
            <?= csrf_field() ?>
            <h2 style="font-size:16px">Danger zone</h2>
            <p class="small muted mt-8">Use this if the influencer asks to be removed from your network.</p>
            <button class="btn btn-danger btn-block mt-16" type="submit"><i class="ph ph-trash"></i>Delete profile</button>
        </form>
    </aside>
</div>
