<?php /** @var array $me @var array $admins @var array $fields */ ?>
<div class="page-head"><div><h2>Settings</h2><p>Site details, contact info, landing page numbers and admin accounts.</p></div></div>

<div class="grid grid-side">
    <form method="post" action="<?= e(url('admin/settings')) ?>" class="grid">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="site">
        <?php foreach ($fields as $group => $items): ?>
            <section class="card card-pad" id="<?= e(slugify($group)) ?>">
                <h2 style="font-size:16px;margin-bottom:4px"><?= e($group) ?></h2>
                <?php if ($group === 'Theme and currency'): ?><p class="small muted" style="margin-bottom:14px">Prices on the website (estimator, brief budgets, creator fees) start in your main currency. Visitors can always switch currency themselves.</p>
                <?php elseif ($group === 'SEO and analytics'): ?><p class="small muted" style="margin-bottom:14px">Paste only the ID or code, not the full script. Then submit <b><?= e(abs_url('sitemap.xml')) ?></b> in Google Search Console.</p>
                <?php else: ?><div style="height:12px"></div><?php endif; ?>
                <div class="form-grid">
                    <?php foreach ($items as $key => $def): $val = setting($key); $id = 's-' . $key; ?>
                        <div class="field<?= in_array($def[1], ['textarea', 'checkbox'], true) ? ' full' : '' ?>">
                            <?php if ($def[1] !== 'checkbox'): ?><label for="<?= e($id) ?>"><?= e($def[0]) ?></label><?php endif; ?>
                            <?php if ($def[1] === 'textarea' && $key === 'fx_rates'): ?>
                                <textarea class="textarea" id="<?= e($id) ?>" name="<?= e($key) ?>" rows="6" style="font-family:ui-monospace,monospace;font-size:13px"><?= e($val ?: default_fx_text()) ?></textarea>
                                <span class="hint">Used to convert budgets and estimates for visitors. Update them every few months.</span>
                            <?php elseif ($def[1] === 'textarea'): ?>
                                <textarea class="textarea" id="<?= e($id) ?>" name="<?= e($key) ?>" rows="3" maxlength="320"><?= e($val) ?></textarea>
                            <?php elseif ($def[1] === 'currency'): ?>
                                <select class="select" id="<?= e($id) ?>" name="<?= e($key) ?>"><?php foreach (currency_catalog() as $code => $c): ?><option value="<?= e($code) ?>"<?= $val === $code ? ' selected' : '' ?>><?= e($code . ' - ' . $c[0]) ?></option><?php endforeach; ?></select>
                            <?php elseif ($def[1] === 'theme'): ?>
                                <select class="select" id="<?= e($id) ?>" name="<?= e($key) ?>">
                                    <?php foreach (['auto' => 'Auto: follow the visitor\'s system, with a light/dark switch', 'light' => 'Light only (switch hidden)', 'dark' => 'Dark only (switch hidden)'] as $v => $l): ?><option value="<?= $v ?>"<?= ($val ?: 'auto') === $v ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                                </select>
                            <?php elseif ($def[1] === 'checkbox'): ?>
                                <label class="check"><input type="hidden" name="<?= e($key) ?>" value="0"><input type="checkbox" id="<?= e($id) ?>" name="<?= e($key) ?>" value="1"<?= $val === '1' ? ' checked' : '' ?>><?= e($def[0]) ?></label>
                            <?php else: ?>
                                <input class="input" id="<?= e($id) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>" type="<?= $def[1] === 'number' ? 'text' : e($def[1]) ?>"<?= $def[1] === 'number' ? ' inputmode="numeric"' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <div><button class="btn btn-primary btn-lg" type="submit"><i class="ph-bold ph-check"></i>Save settings</button></div>
    </form>

    <aside class="grid" style="position:sticky;top:88px">
        <form class="card card-pad" method="post" action="<?= e(url('admin/settings')) ?>" id="account">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="account"><input type="hidden" name="tab" value="account">
            <h2 style="font-size:16px;margin-bottom:16px">Your account</h2>
            <div class="field"><label for="a-name">Name</label><input class="input" id="a-name" name="name" value="<?= e($me['name']) ?>" required></div>
            <div class="field mt-16"><label for="a-email">Email</label><input class="input" id="a-email" type="email" name="email" value="<?= e($me['email']) ?>" required autocomplete="username"></div>
            <div class="field mt-16"><label for="a-new">New password <span class="muted">(leave blank to keep)</span></label><input class="input" id="a-new" type="password" name="new_password" minlength="8" autocomplete="new-password"></div>
            <div class="field mt-16"><label for="a-cur">Current password</label><input class="input" id="a-cur" type="password" name="current_password" required autocomplete="current-password"><span class="hint">Required to save any change here.</span></div>
            <button class="btn btn-dark btn-block mt-16" type="submit">Update account</button>
        </form>

        <section class="card" id="team">
            <div class="card-head"><h2>Team</h2></div>
            <div class="list">
                <?php foreach ($admins as $a): ?>
                    <div class="list-item">
                        <span class="avatar"><?= e(initials($a['name'])) ?></span>
                        <div style="min-width:0"><b><?= e($a['name']) ?><?= (int) $a['id'] === (int) $me['id'] ? ' (you)' : '' ?></b><small><?= e($a['email']) ?></small></div>
                        <?php if ((int) $a['id'] !== (int) $me['id']): ?>
                            <form class="right" method="post" action="<?= e(url('admin/settings')) ?>" data-confirm="Remove <?= e($a['name']) ?> from the admin?"><?= csrf_field() ?><input type="hidden" name="section" value="remove_admin"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="tab" value="team"><button class="btn btn-ghost btn-sm" type="submit" aria-label="Remove admin"><i class="ph ph-trash"></i></button></form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="card-body" method="post" action="<?= e(url('admin/settings')) ?>" style="border-top:1px solid var(--line)">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="add_admin"><input type="hidden" name="tab" value="team">
                <h3 style="font-size:14px;margin-bottom:12px">Add a team member</h3>
                <div class="field"><label for="n-name">Name</label><input class="input" id="n-name" name="name" required></div>
                <div class="field mt-8"><label for="n-email">Email</label><input class="input" id="n-email" type="email" name="email" required autocomplete="off"></div>
                <div class="field mt-8"><label for="n-pass">Temporary password</label><input class="input" id="n-pass" type="password" name="password" minlength="8" required autocomplete="new-password"></div>
                <button class="btn btn-outline btn-block mt-16" type="submit"><i class="ph ph-user-plus"></i>Add admin</button>
            </form>
        </section>
    </aside>
</div>
