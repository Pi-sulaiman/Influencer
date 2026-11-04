<?php /** @var array $live @var array $logos @var array $testimonials @var array|null $editing @var array $slots */
$t = $editing ?: ['id' => '', 'name' => '', 'role' => '', 'quote' => '', 'photo' => '', 'sort_order' => count($testimonials), 'is_visible' => 1];
$liveReach = $live['reach'] >= 1000000 ? round($live['reach'] / 1000000, 1) . 'M' : short_number($live['reach']);
?>
<div class="page-head">
    <div><h2>Site content</h2><p>Real numbers, real testimonials and your own photos for the public website.</p></div>
    <div class="actions"><a class="btn btn-outline" href="<?= e(url('')) ?>" target="_blank"><i class="ph ph-arrow-square-out"></i>View homepage</a></div>
</div>

<div class="grid grid-side">
    <div class="grid">
        <!-- Homepage numbers -->
        <form class="card" method="post" action="<?= e(url('admin/content')) ?>" id="numbers">
            <?= csrf_field() ?><input type="hidden" name="action" value="stats">
            <div class="card-head"><h2>Homepage numbers</h2><span class="small muted">Shown on the homepage and About page</span></div>
            <div class="card-body">
                <?php if (demo_on()): ?><div class="alert alert-info"><i class="ph-fill ph-flask"></i><span>Demo mode is on, so the website shows example numbers. These settings apply once you turn demo mode off in <a href="<?= e(url('admin/settings#demo')) ?>">Settings</a>.</span></div><?php endif; ?>
                <div class="mode-cards" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-bottom:18px">
                    <label class="mode-card"><input type="radio" name="stats_mode" value="custom"<?= setting('stats_mode', 'live') === 'custom' ? ' checked' : '' ?>><span><i class="ph-bold ph-pencil-simple-line"></i><b>Custom numbers</b><small>Shows the figures you type below, with a "+" (for example 1,200+). Per-niche counts are hidden. Good while the network is growing.</small></span></label>
                    <label class="mode-card"><input type="radio" name="stats_mode" value="live"<?= setting('stats_mode', 'live') !== 'custom' ? ' checked' : '' ?>><span><i class="ph-bold ph-database"></i><b>Live from database</b><small>Counts approved creators (<?= (int) $live['creators'] ?> now), closed campaigns (<?= (int) $live['campaigns'] ?>) and combined followers (<?= e($liveReach) ?>). Niche counts show everywhere.</small></span></label>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="st-c">Vetted creators</label>
                        <input class="input" id="st-c" name="stat_creators" value="<?= e(setting('stat_creators')) ?>" inputmode="numeric" placeholder="e.g. 1200">
                    </div>
                    <div class="field">
                        <label for="st-k">Campaigns delivered</label>
                        <input class="input" id="st-k" name="stat_campaigns" value="<?= e(setting('stat_campaigns')) ?>" inputmode="numeric" placeholder="e.g. 340">
                    </div>
                    <div class="field">
                        <label for="st-r">People reached, in millions</label>
                        <input class="input" id="st-r" name="stat_reach" value="<?= e(setting('stat_reach')) ?>" inputmode="decimal" placeholder="e.g. 85">
                    </div>
                    <div class="field">
                        <label for="st-t">Repeat brand rate (%)</label>
                        <input class="input" id="st-t" name="stat_retention" value="<?= e(setting('stat_retention')) ?>" inputmode="numeric" placeholder="e.g. 92">
                    </div>
                </div>
                <p class="small muted mt-16">Custom numbers appear publicly exactly as typed. Leave a box empty to hide that number.</p>
                <button class="btn btn-primary mt-16" type="submit"><i class="ph-bold ph-check"></i>Save numbers</button>
            </div>
        </form>

        <!-- Testimonials -->
        <section class="card" id="testimonials">
            <div class="card-head"><h2>Testimonials</h2><span class="small muted"><?= count($testimonials) ?> saved</span></div>
            <?php if (!$testimonials): ?>
                <div class="empty">
                    <i class="ph ph-quotes"></i>
                    <h3>No testimonials yet</h3>
                    <p>The testimonial section stays hidden on the homepage until you add at least one real quote from a brand or creator you worked with.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Person</th><th>Quote</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($testimonials as $row): ?>
                        <tr>
                            <td><div class="person"><span class="avatar"><?php if ($row['photo']): ?><img src="<?= e(upload_url($row['photo'])) ?>" alt=""><?php else: ?><?= e(initials($row['name'])) ?><?php endif; ?></span><span><b><?= e($row['name']) ?></b><small><?= e($row['role']) ?></small></span></div></td>
                            <td class="small" style="max-width:360px"><?= e(str_limit($row['quote'], 120)) ?></td>
                            <td><?= (int) $row['sort_order'] ?></td>
                            <td><span class="badge <?= $row['is_visible'] ? 'b-published' : 'b-draft' ?>"><?= $row['is_visible'] ? 'Visible' : 'Hidden' ?></span></td>
                            <td class="nowrap">
                                <a class="btn btn-outline btn-sm" href="<?= e(url('admin/content?edit=' . $row['id'] . '#testimonial-form')) ?>">Edit</a>
                                <form method="post" action="<?= e(url('admin/content')) ?>" style="display:inline" data-confirm="Delete this testimonial from <?= e($row['name']) ?>?">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_testimonial"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" type="submit" aria-label="Delete"><i class="ph ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>

        <!-- Brand logos -->
        <section class="card" id="logos">
            <div class="card-head"><h2>Brand logos</h2><span class="small muted"><?= count($logos) ?> saved &middot; homepage carousel</span></div>
            <div class="card-body">
                <p class="small muted" style="margin-bottom:14px">Logos scroll in an endless grayscale strip under the homepage hero and turn colour on hover. The strip stays hidden until you add one. Only add brands you have really run campaigns with: a "worked with" claim for any other brand is false advertising and can break trademark rules.</p>
                <form class="logo-title-form" method="post" action="<?= e(url('admin/content')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="action" value="logos_title">
                    <div class="field" style="flex:1"><label for="lt">Strip title</label><input class="input" id="lt" name="logos_title" value="<?= e(setting('logos_title', 'Brands we have worked with')) ?>" maxlength="120"></div>
                    <button class="btn btn-outline" type="submit">Save title</button>
                </form>
                <?php if ($logos): ?>
                    <div class="logo-grid">
                        <?php foreach ($logos as $l): ?>
                            <div class="logo-item<?= $l['is_visible'] ? '' : ' is-hidden' ?>">
                                <div class="li-img"><img src="<?= e(upload_url($l['logo'])) ?>" alt="<?= e($l['name']) ?>" loading="lazy"></div>
                                <div class="li-meta"><b><?= e($l['name']) ?></b><small>Order <?= (int) $l['sort_order'] ?><?= $l['is_visible'] ? '' : ' &middot; hidden' ?></small></div>
                                <div class="li-actions">
                                    <form method="post" action="<?= e(url('admin/content')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="logo_toggle"><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= $l['is_visible'] ? 'Hide' : 'Show' ?></button></form>
                                    <form method="post" action="<?= e(url('admin/content')) ?>" data-confirm="Remove the <?= e($l['name']) ?> logo?"><?= csrf_field() ?><input type="hidden" name="action" value="logo_delete"><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit" aria-label="Delete"><i class="ph ph-trash"></i></button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form class="logo-add" method="post" action="<?= e(url('admin/content')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?><input type="hidden" name="action" value="logo">
                    <h3>Add a brand</h3>
                    <div class="form-grid">
                        <div class="field"><label for="lg-name">Brand name</label><input class="input" id="lg-name" name="name" required maxlength="160" placeholder="e.g. Brand you ran a campaign for"></div>
                        <div class="field"><label for="lg-file">Logo file</label><input class="input" id="lg-file" type="file" name="logo" accept=".svg,image/svg+xml,image/png,image/webp,image/jpeg" required><span class="hint">SVG works best (cleaned automatically). PNG or WEBP with a transparent background also works.</span></div>
                        <div class="field"><label for="lg-url">Link <span class="muted">(optional)</span></label><input class="input" id="lg-url" name="url" placeholder="https://"></div>
                        <div class="field"><label for="lg-order">Order</label><input class="input" id="lg-order" name="sort_order" type="number" value="<?= count($logos) ?>"></div>
                    </div>
                    <button class="btn btn-primary mt-16" type="submit"><i class="ph ph-plus"></i>Add logo</button>
                </form>
            </div>
        </section>

        <!-- Page photos -->
        <section class="card" id="photos">
            <div class="card-head"><h2>Page photos</h2><span class="small muted">JPG, PNG or WEBP up to <?= (int) cfg('upload_max_mb', 5) ?> MB</span></div>
            <div class="card-body">
                <p class="small muted" style="margin-bottom:16px">These start as stock placeholder photos. Upload your own team, office or campaign photos to replace them.</p>
                <div class="photo-slots">
                    <?php foreach ($slots as $key => $s): $custom = site_image_is_custom($key); ?>
                        <div class="photo-slot">
                            <div class="ps-img"><img src="<?= e(site_image($key)) ?>" alt="" loading="lazy"><span class="badge <?= $custom ? 'b-published' : 'b-on_hold' ?> ps-badge"><?= $custom ? 'Your photo' : 'Placeholder' ?></span></div>
                            <div class="ps-body">
                                <b><?= e($s[0]) ?></b>
                                <small class="muted"><?= e($s[2]) ?></small>
                                <form method="post" action="<?= e(url('admin/content')) ?>" enctype="multipart/form-data" class="ps-form">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="image"><input type="hidden" name="slot" value="<?= e($key) ?>">
                                    <label class="btn btn-outline btn-sm"><i class="ph ph-upload-simple"></i>Upload<input type="file" name="image" accept="image/jpeg,image/png,image/webp" hidden onchange="this.form.submit()"></label>
                                </form>
                                <?php if ($custom): ?>
                                    <form method="post" action="<?= e(url('admin/content')) ?>" data-confirm="Go back to the placeholder photo?">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="reset_image"><input type="hidden" name="slot" value="<?= e($key) ?>">
                                        <button class="btn btn-ghost btn-sm" type="submit">Reset</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <aside class="grid" style="position:sticky;top:88px">
        <form class="card card-pad" method="post" action="<?= e(url('admin/content')) ?>" enctype="multipart/form-data" id="testimonial-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="testimonial"><input type="hidden" name="id" value="<?= e($t['id']) ?>">
            <h2 style="font-size:16px;margin-bottom:4px"><?= $editing ? 'Edit testimonial' : 'Add a testimonial' ?></h2>
            <p class="small muted" style="margin-bottom:14px">Only use real quotes, with the person's permission.</p>
            <div class="field"><label for="t-name">Name</label><input class="input" id="t-name" name="name" value="<?= e($t['name']) ?>" required></div>
            <div class="field mt-16"><label for="t-role">Role and company</label><input class="input" id="t-role" name="role" value="<?= e($t['role']) ?>" placeholder="Head of Marketing, Brand name"></div>
            <div class="field mt-16"><label for="t-quote">Quote</label><textarea class="textarea" id="t-quote" name="quote" rows="4" maxlength="400" required placeholder="Keep it to two or three sentences"><?= e($t['quote']) ?></textarea></div>
            <div class="field mt-16">
                <label for="t-photo">Photo <span class="muted">(optional)</span></label>
                <?php if ($t['photo']): ?><div class="flex" style="margin-bottom:6px"><span class="avatar"><img src="<?= e(upload_url($t['photo'])) ?>" alt=""></span><label class="check"><input type="checkbox" name="remove_photo" value="1">Remove photo</label></div><?php endif; ?>
                <input class="input" id="t-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" style="padding-top:8px">
            </div>
            <div class="form-grid mt-16">
                <div class="field"><label for="t-order">Order</label><input class="input" id="t-order" type="number" name="sort_order" value="<?= (int) $t['sort_order'] ?>"></div>
                <div class="field"><label>&nbsp;</label><label class="check" style="height:42px;align-items:center"><input type="checkbox" name="is_visible" value="1"<?= $t['is_visible'] ? ' checked' : '' ?>>Show on site</label></div>
            </div>
            <button class="btn btn-primary btn-block mt-16" type="submit"><?= $editing ? 'Save testimonial' : 'Add testimonial' ?></button>
            <?php if ($editing): ?><a class="btn btn-ghost btn-block mt-8" href="<?= e(url('admin/content#testimonials')) ?>">Cancel</a><?php endif; ?>
        </form>
    </aside>
</div>
