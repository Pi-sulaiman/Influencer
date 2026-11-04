<?php
/** @var array $c @var array $apps @var array $counts @var int $totalApps @var string $status @var array $visitSeries @var array $appSeries */
$link = abs_url('c/' . $c['code']);
$conv = $c['visits'] > 0 ? round($totalApps / $c['visits'] * 100, 1) : 0;
$platforms = platform_options();
$chart = ['labels' => array_map(function ($d) { return date('M j', strtotime($d)); }, array_keys($visitSeries)), 'series' => [
    ['name' => 'Applications', 'data' => array_values($appSeries)],
    ['name' => 'Link visits', 'data' => array_values($visitSeries), 'style' => 'dashed'],
]];
$modeText = ['form' => 'Opens the registration form', 'landing' => 'Opens the landing page with an apply banner', 'url' => 'Redirects to ' . $c['redirect_url']];
$shareText = rawurlencode(($c['brand_name'] ? $c['brand_name'] . ': ' : '') . $c['title'] . ' - apply here: ' . $link);
?>
<div class="page-head">
    <div class="flex">
        <a class="icon-btn" href="<?= e(url('admin/campaigns')) ?>" aria-label="Back to campaigns"><i class="ph-bold ph-arrow-left"></i></a>
        <div>
            <div class="flex" style="gap:8px"><h2><?= e($c['title']) ?></h2><span class="badge b-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></div>
            <p><?= e($c['brand_name'] ?: 'No brand set') ?> &middot; Created <?= e(nice_date($c['created_at'])) ?><?= $c['deadline'] ? ' &middot; Apply by ' . e(nice_date($c['deadline'])) : '' ?></p>
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= e(url('admin/campaigns/' . $c['id'] . '/export')) ?>"><i class="ph ph-download-simple"></i>Export applicants</a>
        <a class="btn btn-dark" href="<?= e(url('admin/campaigns/' . $c['id'] . '/edit')) ?>"><i class="ph ph-pencil-simple"></i>Edit</a>
    </div>
</div>

<section class="card card-pad" style="margin-bottom:20px">
    <div class="flex-between">
        <div class="grow" style="min-width:260px">
            <span class="label small muted" style="font-weight:700;text-transform:uppercase;letter-spacing:.08em">Shareable link</span>
            <div class="link-box mt-8"><input value="<?= e($link) ?>" readonly aria-label="Campaign link" onclick="this.select()"><button class="btn btn-primary btn-sm" type="button" data-copy="<?= e($link) ?>"><i class="ph ph-copy"></i>Copy</button></div>
            <p class="small muted mt-8"><i class="ph ph-info"></i> <?= e($modeText[$c['mode']] ?? '') ?></p>
        </div>
        <div class="flex">
            <a class="btn btn-outline" href="https://wa.me/?text=<?= $shareText ?>" target="_blank" rel="noopener"><i class="ph ph-whatsapp-logo"></i>WhatsApp</a>
            <a class="btn btn-outline" href="mailto:?subject=<?= rawurlencode('Campaign: ' . $c['title']) ?>&body=<?= $shareText ?>"><i class="ph ph-envelope-simple"></i>Email</a>
            <a class="btn btn-outline" href="<?= e($link) ?>" target="_blank" rel="noopener"><i class="ph ph-arrow-square-out"></i>Open</a>
        </div>
    </div>
</section>

<div class="kpis">
    <div class="card kpi is-red"><?= mark_svg('chev') ?><div class="k-top">Link visits <span class="k-ic"><i class="ph-bold ph-cursor-click"></i></span></div><div class="k-num"><?= number_format((int) $c['visits']) ?></div><div class="k-sub">Unique sessions</div></div>
    <div class="card kpi"><div class="k-top">Applicants <span class="k-ic"><i class="ph-bold ph-users-three"></i></span></div><div class="k-num"><?= number_format($totalApps) ?></div><div class="k-sub"><?= $conv ?>% of visitors applied</div></div>
    <div class="card kpi"><div class="k-top">Shortlisted <span class="k-ic"><i class="ph-bold ph-list-checks"></i></span></div><div class="k-num"><?= number_format($counts['shortlisted']) ?></div><div class="k-sub"><?= number_format($counts['selected']) ?> selected</div></div>
    <div class="card kpi"><div class="k-top">Budget per creator <span class="k-ic"><i class="ph-bold ph-wallet"></i></span></div><div class="k-num" style="font-size:24px"><?= e(($c['budget_min'] || $c['budget_max']) ? fee_range($c['budget_min'], $c['budget_max'], $c['currency']) : 'Not set') ?></div><div class="k-sub"><?= e(implode(', ', array_map(function ($p) use ($platforms) { return $platforms[$p]['label'] ?? $p; }, csv_list(str_replace(',', ', ', (string) $c['platforms']))))) ?: 'Any platform' ?></div></div>
</div>

<section class="card" style="margin-bottom:20px">
    <div class="card-head"><h2>Last 14 days</h2><div class="chart-legend"><span><i style="background:#EF3B2A"></i>Applications</span><span><i style="background:#10131C"></i>Link visits</span></div></div>
    <div class="card-body"><div class="chart" style="height:200px" data-chart='<?= e(json_encode($chart)) ?>'></div></div>
</section>

<section class="card">
    <div class="card-head"><h2>Applicants</h2></div>
    <div class="card-body">
        <nav class="pipeline" aria-label="Filter applicants">
            <?php foreach (['applied' => 'Applied', 'shortlisted' => 'Shortlisted', 'selected' => 'Selected', 'rejected' => 'Rejected'] as $s => $l): ?>
                <a href="<?= e(url('admin/campaigns/' . $c['id'] . ($status === $s ? '' : '?status=' . $s))) ?>" class="<?= $status === $s ? 'is-active' : '' ?>"><b><?= $counts[$s] ?></b><span><?= $l ?></span></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php if (!$apps): ?>
        <div class="empty">
            <i class="ph ph-user-plus"></i>
            <h3><?= $status ? 'Nobody in this stage yet' : 'No applications yet' ?></h3>
            <p><?= $status ? 'Move applicants here using the status menu.' : 'Share the campaign link above. Applications appear here as soon as influencers submit the form.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Influencer</th><th>Channels</th><th>Reach</th><th>Fee range</th><th>Pitch</th><th>Applied</th><th>Stage</th></tr></thead>
            <tbody>
            <?php foreach ($apps as $a): ?>
                <tr>
                    <td><a class="person" href="<?= e(url('admin/influencers/' . $a['id'])) ?>"><span class="avatar"><?php if ($a['photo']): ?><img src="<?= e(upload_url($a['photo'])) ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($a['full_name'])) ?><?php endif; ?></span><span><b><?= e($a['full_name']) ?></b><small><?= e(trim($a['city'] . ', ' . $a['country'], ', ')) ?></small></span></a></td>
                    <td class="nowrap" style="font-size:18px"><?php foreach ($platforms as $k => $p): if (!empty($a[$k])): ?><a href="<?= e(social_url($k, $a[$k])) ?>" target="_blank" rel="noopener" title="<?= e($p['label'] . ': ' . short_number($a[$k . '_followers'])) ?>"><i class="ph-fill <?= e($p['icon']) ?>"></i></a> <?php endif; endforeach; ?></td>
                    <td><b><?= e(short_number($a['total_followers'])) ?></b></td>
                    <td class="nowrap"><?= e(fee_range($a['fee_min'], $a['fee_max'], $a['currency'])) ?></td>
                    <td class="small" style="max-width:280px"><?= e(str_limit($a['pitch'] ?: '-', 110)) ?></td>
                    <td class="small muted nowrap"><?= e(time_ago($a['applied_at'])) ?></td>
                    <td>
                        <form method="post" action="<?= e(url('admin/applications/' . $a['app_id'])) ?>" data-app-status>
                            <?= csrf_field() ?>
                            <select class="select app-status" name="status" aria-label="Stage for <?= e($a['full_name']) ?>">
                                <?php foreach (['applied', 'shortlisted', 'selected', 'rejected'] as $s): ?><option value="<?= $s ?>"<?= $a['app_status'] === $s ? ' selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
