<?php
/** @var array $inf @var array $apps @var mixed $prevId @var mixed $nextId */
$platforms = platform_options();
$socialMetrics = json_decode($inf['social_metrics'] ?? '{}', true) ?: [];
$wa = preg_replace('/\D/', '', (string) $inf['phone']);
$rates = rate_card($inf['rate_card'] ?? '');
$tier = creator_tier($inf['total_followers']);
?>
<div class="page-head">
    <div class="flex">
        <a class="icon-btn" href="<?= e(url('admin/influencers')) ?>" aria-label="Back to influencers"><i class="ph-bold ph-arrow-left"></i></a>
        <div><h2 style="font-size:20px">Influencer profile <span class="badge no-dot" title="Code brands see on the website"><?= e(creator_code($inf['id'])) ?></span></h2><p>Registered <?= e(nice_date($inf['created_at'], 'M j, Y \a\t g:i a')) ?> &middot; Source: <?= e($inf['source'] ?: 'direct') ?></p></div>
    </div>
    <div class="actions">
        <?php if ($prevId): ?><a class="icon-btn" href="<?= e(url('admin/influencers/' . $prevId)) ?>" aria-label="Previous profile"><i class="ph-bold ph-caret-left"></i></a><?php endif; ?>
        <?php if ($nextId): ?><a class="icon-btn" href="<?= e(url('admin/influencers/' . $nextId)) ?>" aria-label="Next profile"><i class="ph-bold ph-caret-right"></i></a><?php endif; ?>
    </div>
</div>

<div class="grid grid-side">
    <div class="grid">
        <section class="card profile-hero">
            <div>
                <div class="profile-photo">
                    <?php if ($inf['photo']): ?><a href="<?= e(upload_url($inf['photo'])) ?>" target="_blank" rel="noopener" style="display:contents"><img src="<?= e(upload_url($inf['photo'])) ?>" alt="<?= e($inf['full_name']) ?>"<?= !empty($inf['is_demo']) ? ' onerror="this.onerror=null;this.src=\'' . e(demo_fallback_photo($inf['id'])) . '\'"' : '' ?>></a><?php else: ?><?= e(initials($inf['full_name'])) ?><?php endif; ?>
                </div>
                <form class="photo-change" method="post" action="<?= e(url('admin/influencers/' . $inf['id'])) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?><input type="hidden" name="action" value="photo">
                    <label class="btn btn-ghost btn-sm"><i class="ph ph-camera"></i>Change photo<input class="sr-only" type="file" name="photo" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()"></label>
                </form>
                <?php if (!empty($inf['is_demo'])): ?><p class="small muted" style="max-width:220px;margin-top:6px"><span class="badge b-on_hold">Sample (demo)</span> Shown on the website only while demo mode is on.</p><?php endif; ?>
            </div>
            <div>
                <div class="flex" style="flex-wrap:wrap">
                    <h2 class="profile-name"><?= e($inf['full_name']) ?><?php if (!empty($inf['display_name'])): ?> <span class="muted" style="font-weight:600;font-size:.7em">"<?= e($inf['display_name']) ?>"</span><?php endif; ?></h2>
                    <span class="badge no-dot"><?= e($tier['label']) ?> &middot; <?= e($tier['range']) ?></span>
                    <span class="badge b-<?= e($inf['status']) ?>"><?= e(status_label($inf['status'])) ?></span>
                    <?php if ($inf['featured']): ?><span class="badge no-dot" style="background:#FFF4D6;color:#8A5A00"><i class="ph-fill ph-star"></i>Featured on site</span><?php endif; ?>
                </div>
                <div class="profile-meta">
                    <?php if ($inf['city'] || $inf['country']): ?><span><i class="ph ph-map-pin"></i><?= e(implode(', ', array_filter([$inf['city'], $inf['state'] ?? '', $inf['country']]))) ?></span><?php endif; ?>
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
                    <div><b><?= e(short_number($inf['total_followers'])) ?></b><span>Total followers</span></div>
                    <div><b><?= !empty($inf['avg_impressions']) ? e(short_number($inf['avg_impressions'])) : '-' ?></b><span>Combined channel averages</span></div>
                    <div><b><?= !empty($inf['monthly_impressions']) ? e(short_number($inf['monthly_impressions'])) : '-' ?></b><span>Impressions, last 30 days</span></div>
                    <div><b><?= $inf['engagement_rate'] !== null ? e($inf['engagement_rate'] + 0) . '%' : '-' ?></b><span>Engagement rate</span></div>
                    <div class="fee"><b><?= e(fee_range($inf['fee_min'], $inf['fee_max'], $inf['currency'])) ?></b><span>Fee range (<?= e($inf['currency']) ?>)</span></div>
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
                            <div style="min-width:0"><div class="s-handle"><?= e($inf[$k]) ?></div><div class="small muted"><?= e($p['label']) ?><?= !empty($inf[$k . '_avg_views']) ? ' &middot; ' . e(short_number($inf[$k . '_avg_views'])) . ' avg. views' : '' ?></div></div>
                            <span class="s-count"><?= $inf[$k . '_followers'] !== null ? e(short_number($inf[$k . '_followers'])) : '-' ?></span>
                        </a>
                        <?php $metric = $socialMetrics[$k] ?? null; if ($metric): ?>
                            <div class="small muted">
                                Followers: <?= ($metric['followers_source'] ?? '') === 'fetched' ? 'automatically fetched' : 'self-reported / unavailable' ?>.
                                Average views: <?= ($metric['views_source'] ?? '') === 'fetched' ? 'automatically fetched' : 'self-reported / unavailable' ?>.
                                <?php if (!empty($metric['lookup'])): $lookup = $metric['lookup']; ?>
                                    <?= e($lookup['source'] ?? '') ?> · <?= (int) ($lookup['sample_size'] ?? 0) ?> videos with view counts.
                                    Checked <?= e($lookup['fetched_at'] ?? '') ?>.
                                    <?= e($lookup['sample_description'] ?? '') ?>
                                    <?php if (isset($lookup['average_likes'])): ?>
                                        Avg. likes: <?= e(number_format($lookup['average_likes'])) ?> (<?= (int) ($lookup['likes_sample_size'] ?? 0) ?> Reels).
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array($k, ['instagram', 'youtube'], true)):
                            $multiplier = social_impression_multiplier($k);
                            $projection = social_admin_projection($inf[$k . '_avg_views'] ?? null, $inf[$k . '_followers'] ?? null, $multiplier); ?>
                            <div class="small" style="margin:8px 0 16px">
                                <b>Estimated impressions per video (planning): <?= $projection['estimated_impressions'] !== null ? number_format($projection['estimated_impressions']) : '—' ?></b><br>
                                Views ÷ followers: <?= $projection['views_followers_percent'] !== null ? number_format($projection['views_followers_percent'], 2) . '%' : '—' ?>.<br>
                                <span class="muted">Estimate = average views × <?= e($multiplier) ?>; percentage = average views ÷ followers × 100. Assumption-based proxy, not measured impressions or unique reach. Missing inputs stay unavailable; zero followers have no percentage.</span>
                            </div>
                        <?php endif; ?>
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
                        <?php foreach (rate_card_options() as $rk => $ro): if (isset($rates[$rk])): ?>
                            <dt><?= e($ro[0]) ?></dt><dd><?= e(money($rates[$rk], $inf['currency'])) ?></dd>
                        <?php endif; endforeach; ?>
                        <dt>Included</dt><dd><?= e($inf['fee_notes'] ?: '-') ?></dd>
                        <dt>Product-only</dt><dd><?= !empty($inf['barter_ok']) ? '<span class="badge b-approved">Open to it</span>' : 'No' ?></dd>
                        <dt>Feature consent</dt><dd><?= $inf['consent_feature'] ? '<span class="badge b-approved">Allowed</span>' : '<span class="badge">Not given</span>' ?></dd>
                        <dt>Last updated</dt><dd><?= e(nice_date($inf['updated_at'], 'M j, Y g:i a')) ?></dd>
                    </dl>
                </div>
            </section>
        </div>

        <section class="card">
            <div class="card-head"><h2>Audience</h2></div>
            <div class="card-body">
                <dl class="dl">
                    <dt>Top country</dt><dd><?= e($inf['audience_country'] ?? '' ?: '-') ?></dd>
                    <dt>Main age group</dt><dd><?= e($inf['audience_age'] ?? '' ?: '-') ?></dd>
                    <dt>Gender split</dt><dd><?= e($inf['audience_gender'] ?? '' ?: '-') ?></dd>
                    <dt>Past brands</dt><dd><?= e($inf['past_brands'] ?? '' ?: '-') ?></dd>
                </dl>
            </div>
        </section>

        <?php if (!empty($inf['rate_card_image']) || !empty($inf['insights_image'])): ?>
            <section class="card">
                <div class="card-head"><h2>Uploads</h2><span class="small muted">Click to open full size</span></div>
                <div class="card-body upload-thumbs">
                    <?php foreach (['insights_image' => 'Insights screenshot', 'rate_card_image' => 'Rate card'] as $k => $label): if (!empty($inf[$k])): ?>
                        <a href="<?= e(upload_url($inf[$k])) ?>" target="_blank" rel="noopener"><img src="<?= e(upload_url($inf[$k])) ?>" alt="<?= e($label) ?>" loading="lazy"><span><?= e($label) ?></span></a>
                    <?php endif; endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

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
