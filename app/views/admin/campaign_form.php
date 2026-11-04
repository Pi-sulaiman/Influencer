<?php /** @var array|null $c @var array $data @var array $errors */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<span class="error">' . e($errors[$k]) . '</span>' : ''; };
$has = function ($k) use ($errors) { return isset($errors[$k]) ? ' has-error' : ''; };
$niches = csv_list($data['niches']);
$plats = csv_list(str_replace(',', ', ', (string) $data['platforms']));
?>
<form method="post" enctype="multipart/form-data" action="<?= e(url($c ? 'admin/campaigns/' . $c['id'] . '/edit' : 'admin/campaigns/new')) ?>" data-campaign-form>
    <?= csrf_field() ?>
    <div class="page-head">
        <div class="flex">
            <a class="icon-btn" href="<?= e(url($c ? 'admin/campaigns/' . $c['id'] : 'admin/campaigns')) ?>" aria-label="Back"><i class="ph-bold ph-arrow-left"></i></a>
            <div><h2><?= $c ? 'Edit campaign' : 'New campaign' ?></h2><p>Influencers see the title, brief and budget on the campaign form.</p></div>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit"><i class="ph-bold ph-check"></i><?= $c ? 'Save changes' : 'Create campaign' ?></button></div>
    </div>

    <?php if ($errors): ?><div class="alert alert-error"><i class="ph-fill ph-warning-circle"></i>Please fix the highlighted fields.</div><?php endif; ?>

    <div class="grid grid-side">
        <div class="grid">
            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:16px">Campaign details</h2>
                <div class="form-grid">
                    <div class="field full<?= $has('title') ?>">
                        <label for="title">Campaign name</label>
                        <input class="input" id="title" name="title" value="<?= e($data['title']) ?>" placeholder="e.g. Summer skincare launch" required data-code-source>
                        <?= $err('title') ?>
                    </div>
                    <div class="field">
                        <label for="brand">Brand name</label>
                        <input class="input" id="brand" name="brand_name" value="<?= e($data['brand_name']) ?>" placeholder="Shown to influencers">
                    </div>
                    <div class="field">
                        <label for="loc">Location <span class="muted">(optional)</span></label>
                        <input class="input" id="loc" name="location" value="<?= e($data['location']) ?>" placeholder="e.g. India, remote, Dubai only">
                    </div>
                    <div class="field full">
                        <label for="desc">Brief for influencers</label>
                        <textarea class="textarea" id="desc" name="description" rows="5" placeholder="What the campaign is about, who it is for and what makes a great fit"><?= e($data['description']) ?></textarea>
                    </div>
                    <div class="field full">
                        <label for="deliv">Deliverables <span class="muted">(one per line)</span></label>
                        <textarea class="textarea" id="deliv" name="deliverables" rows="4" placeholder="1 Instagram reel (30-45s)&#10;3 stories with link sticker&#10;Usage rights for 30 days"><?= e($data['deliverables']) ?></textarea>
                    </div>
                </div>
            </section>

            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:6px">What should the link do?</h2>
                <p class="small muted" style="margin-bottom:16px">Visits and applications are tracked for all three options.</p>
                <div class="mode-cards">
                    <label class="mode-card"><input type="radio" name="mode" value="form"<?= $data['mode'] === 'form' ? ' checked' : '' ?>><span><i class="ph-bold ph-note-pencil"></i><b>Open the form</b><small>Influencers land straight on the registration form with this campaign's brief.</small></span></label>
                    <label class="mode-card"><input type="radio" name="mode" value="landing"<?= $data['mode'] === 'landing' ? ' checked' : '' ?>><span><i class="ph-bold ph-house-line"></i><b>Landing page first</b><small>They see the Peakilo homepage with an "Apply now" banner for this campaign.</small></span></label>
                    <label class="mode-card"><input type="radio" name="mode" value="url"<?= $data['mode'] === 'url' ? ' checked' : '' ?>><span><i class="ph-bold ph-arrow-square-out"></i><b>Custom redirect</b><small>Send them anywhere, like a brand page, a WhatsApp group or a Google Form.</small></span></label>
                </div>
                <div class="field mt-16<?= $has('redirect_url') ?>" data-redirect-field<?= $data['mode'] === 'url' ? '' : ' hidden' ?>>
                    <label for="redir">Redirect URL</label>
                    <input class="input" id="redir" name="redirect_url" value="<?= e($data['redirect_url']) ?>" placeholder="https://">
                    <?= $err('redirect_url') ?>
                </div>
            </section>

            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:16px">Who you are looking for</h2>
                <div class="field">
                    <span class="label">Platforms</span>
                    <div class="chips">
                        <?php foreach (platform_options() as $k => $p): ?>
                            <label class="chip"><input type="checkbox" name="platforms[]" value="<?= $k ?>"<?= in_array($k, $plats, true) ? ' checked' : '' ?>><span><i class="ph-fill <?= e($p['icon']) ?>"></i><?= e($p['label']) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <span class="hint">Selected platforms are switched on by default in the form.</span>
                </div>
                <div class="field mt-16">
                    <span class="label">Niches</span>
                    <div class="chips">
                        <?php foreach (niche_options() as $n): ?>
                            <label class="chip"><input type="checkbox" name="niches[]" value="<?= e($n) ?>"<?= in_array($n, $niches, true) ? ' checked' : '' ?>><span><?= e($n) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-grid mt-16">
                    <div class="field">
                        <label for="bmin">Budget per influencer, from</label>
                        <input class="input" id="bmin" name="budget_min" value="<?= e($data['budget_min'] !== null && $data['budget_min'] !== '' ? $data['budget_min'] + 0 : '') ?>" inputmode="decimal" placeholder="Optional">
                    </div>
                    <div class="field<?= $has('budget_max') ?>">
                        <label for="bmax">Budget per influencer, up to</label>
                        <input class="input" id="bmax" name="budget_max" value="<?= e($data['budget_max'] !== null && $data['budget_max'] !== '' ? $data['budget_max'] + 0 : '') ?>" inputmode="decimal" placeholder="Optional">
                        <?= $err('budget_max') ?>
                    </div>
                    <div class="field">
                        <label for="cur">Currency</label>
                        <select class="select" id="cur" name="currency"><?php foreach (currencies() as $code => $s): ?><option<?= $data['currency'] === $code ? ' selected' : '' ?>><?= e($code) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="field">
                        <label for="dl">Apply by <span class="muted">(optional)</span></label>
                        <input class="input" id="dl" type="date" name="deadline" value="<?= e($data['deadline']) ?>">
                    </div>
                </div>
            </section>
        </div>

        <aside class="grid" style="position:sticky;top:88px">
            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:16px">Publish</h2>
                <div class="field">
                    <label for="status">Status</label>
                    <select class="select" id="status" name="status">
                        <option value="active"<?= $data['status'] === 'active' ? ' selected' : '' ?>>Active: accepting applications</option>
                        <option value="paused"<?= $data['status'] === 'paused' ? ' selected' : '' ?>>Paused: link shows a paused page</option>
                        <option value="closed"<?= $data['status'] === 'closed' ? ' selected' : '' ?>>Closed: no new applications</option>
                    </select>
                </div>
                <div class="field mt-16<?= $has('code') ?>">
                    <label for="code">Link</label>
                    <div class="link-box" style="padding-left:12px"><span class="small muted nowrap"><?= e(preg_replace('#^https?://#', '', abs_url('c/'))) ?></span><input id="code" name="code" value="<?= e($data['code']) ?>" placeholder="auto" data-code-input<?= $c ? '' : ' data-auto="1"' ?> pattern="[a-z0-9-]{2,40}"></div>
                    <span class="hint">Short and readable, like <b>summer-glow</b>. Changing it breaks links you already shared.</span>
                    <?= $err('code') ?>
                </div>
                <button class="btn btn-primary btn-block mt-24" type="submit"><?= $c ? 'Save changes' : 'Create campaign' ?></button>
            </section>
            <section class="card card-pad">
                <h2 style="font-size:16px;margin-bottom:12px">Cover image <span class="muted small">(optional)</span></h2>
                <label class="cover-drop" data-cover-drop>
                    <?php if (!empty($data['cover_image'])): ?><img src="<?= e(upload_url($data['cover_image'])) ?>" alt=""><?php endif; ?>
                    <span><i class="ph ph-image"></i>Upload a wide image<br><small>Shown at the top of the campaign form</small></span>
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-cover-input>
                </label>
                <?= $err('cover') ?>
                <?php if (!empty($data['cover_image'])): ?><label class="check mt-8"><input type="checkbox" name="remove_cover" value="1">Remove current image</label><?php endif; ?>
            </section>
            <?php if ($c): ?>
            <section class="card card-pad">
                <button class="btn btn-danger btn-block" type="submit" formaction="<?= e(url('admin/campaigns/' . $c['id'] . '/delete')) ?>" formnovalidate data-confirm="Delete this campaign? Influencer profiles stay, but applications and visit stats for this campaign are removed."><i class="ph ph-trash"></i>Delete campaign</button>
            </section>
            <?php endif; ?>
        </aside>
    </div>
</form>
