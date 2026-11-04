<?php
/** @var array $stats @var array $regSeries @var array $visitSeries @var array $niches @var array $platforms @var array|null $fees @var string $currency @var array $recent @var array $campaigns @var array $messages */
$delta = $stats['prev7'] > 0 ? round(($stats['new7'] - $stats['prev7']) / $stats['prev7'] * 100) : ($stats['new7'] > 0 ? 100 : 0);
$conv = $stats['visits30'] > 0 ? round($stats['apps30'] / $stats['visits30'] * 100, 1) : 0;
$labels = array_map(function ($d) { return date('M j', strtotime($d)); }, array_keys($regSeries));
$chart = ['labels' => $labels, 'series' => [
    ['name' => 'New influencers', 'data' => array_values($regSeries)],
    ['name' => 'Campaign link visits', 'data' => array_values($visitSeries), 'style' => 'dashed'],
]];
$platformMeta = platform_options();
$donutColors = ['#EF3B2A', '#10131C', '#FF6B57', '#FFAE92', '#646877', '#D9D0C7'];
$totalP = max(1, array_sum($platforms));
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>
<div class="page-head">
    <div>
        <h2><?= e($greet) ?>, <?= e(explode(' ', admin_user()['name'])[0]) ?>.</h2>
        <p><?= $stats['pending'] ? 'You have ' . $stats['pending'] . ' new influencer profile' . ($stats['pending'] > 1 ? 's' : '') . ' waiting for review.' : 'All influencer profiles are reviewed. Nice work.' ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= e(url('admin/posts/new')) ?>"><i class="ph ph-pencil-simple"></i>Write a post</a>
        <a class="btn btn-primary" href="<?= e(url('admin/campaigns/new')) ?>"><i class="ph-bold ph-plus"></i>New campaign</a>
    </div>
</div>

<div class="kpis">
    <a class="card kpi is-red" href="<?= e(url('admin/influencers')) ?>">
        <?= mark_svg('chev') ?>
        <div class="k-top">Registered influencers <span class="k-ic"><i class="ph-bold ph-users-three"></i></span></div>
        <div class="k-num"><?= number_format($stats['total']) ?></div>
        <div class="k-sub"><span class="<?= $delta >= 0 ? 'delta-up' : 'delta-down' ?>"><?= $delta >= 0 ? '+' : '' ?><?= $delta ?>%</span> <?= $stats['new7'] ?> new this week</div>
    </a>
    <a class="card kpi" href="<?= e(url('admin/influencers?status=new')) ?>">
        <div class="k-top">Waiting for review <span class="k-ic"><i class="ph-bold ph-hourglass-medium"></i></span></div>
        <div class="k-num"><?= number_format($stats['pending']) ?></div>
        <div class="k-sub"><?= number_format($stats['approved']) ?> approved in total</div>
    </a>
    <a class="card kpi" href="<?= e(url('admin/campaigns')) ?>">
        <div class="k-top">Active campaigns <span class="k-ic"><i class="ph-bold ph-megaphone"></i></span></div>
        <div class="k-num"><?= number_format($stats['active_campaigns']) ?></div>
        <div class="k-sub"><?= number_format($stats['visits30']) ?> link visits, <?= $conv ?>% applied (30 days)</div>
    </a>
    <div class="card kpi">
        <div class="k-top">Network reach <span class="k-ic"><i class="ph-bold ph-broadcast"></i></span></div>
        <div class="k-num"><?= e(short_number($stats['reach'])) ?></div>
        <div class="k-sub"><?php if ($fees && $fees['n']): ?>Avg fee <?= e(fee_range(round($fees['amin']), round($fees['amax']), $currency)) ?><?php else: ?>Total followers across profiles<?php endif; ?></div>
    </div>
</div>

<div class="grid grid-main">
    <section class="card">
        <div class="card-head">
            <h2>Last 30 days</h2>
            <div class="chart-legend"><span><i style="background:#EF3B2A"></i>New influencers</span><span><i style="background:#10131C"></i>Link visits</span></div>
        </div>
        <div class="card-body"><div class="chart" data-chart='<?= e(json_encode($chart)) ?>'></div></div>
    </section>

    <section class="card">
        <div class="card-head"><h2>Main platform</h2><a class="more" href="<?= e(url('admin/influencers')) ?>">Browse <i class="ph-bold ph-arrow-right"></i></a></div>
        <div class="card-body">
            <?php if (!$platforms): ?>
                <div class="empty"><i class="ph ph-chart-pie-slice"></i><p>Platform split appears once influencers register.</p></div>
            <?php else: ?>
            <div class="donut-wrap">
                <svg class="donut" viewBox="0 0 42 42" role="img" aria-label="Main platform split">
                    <circle cx="21" cy="21" r="15.915" fill="none" stroke="#EFE8E1" stroke-width="6"/>
                    <?php $offset = 25; $i = 0; foreach ($platforms as $p => $n): $pct = $n / $totalP * 100; ?>
                        <circle cx="21" cy="21" r="15.915" fill="none" stroke="<?= $donutColors[$i % 6] ?>" stroke-width="6" stroke-dasharray="<?= round($pct, 2) ?> <?= round(100 - $pct, 2) ?>" stroke-dashoffset="<?= round($offset, 2) ?>"><title><?= e(($platformMeta[$p]['label'] ?? ucfirst($p)) . ': ' . $n) ?></title></circle>
                    <?php $offset -= $pct; $i++; endforeach; ?>
                    <text x="21" y="22.5" text-anchor="middle" font-size="7" font-weight="900" fill="#10131C" font-family="Satoshi, sans-serif"><?= e(short_number($totalP)) ?></text>
                </svg>
                <div class="donut-legend">
                    <?php $i = 0; foreach ($platforms as $p => $n): ?>
                        <div><i class="sw" style="background:<?= $donutColors[$i++ % 6] ?>"></i><?= e($platformMeta[$p]['label'] ?? ucfirst($p)) ?><b><?= round($n / $totalP * 100) ?>%</b></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <h3 class="small muted mt-24" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">Top niches</h3>
            <?php if (!$niches): ?><p class="muted small">No niche data yet.</p><?php else: $maxN = max($niches); ?>
                <div class="hbars">
                    <?php foreach ($niches as $name => $n): ?>
                        <a class="hbar" href="<?= e(url('admin/influencers?niche=' . rawurlencode($name))) ?>">
                            <div class="hb-top"><?= e($name) ?><span><?= $n ?></span></div>
                            <div class="hb-track"><div class="hb-fill" style="width:<?= round($n / $maxN * 100) ?>%"></div></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid grid-main mt-24">
    <section class="card">
        <div class="card-head"><h2>Newest influencers</h2><a class="more" href="<?= e(url('admin/influencers')) ?>">View all <i class="ph-bold ph-arrow-right"></i></a></div>
        <?php if (!$recent): ?>
            <div class="empty">
                <i class="ph ph-user-plus"></i>
                <h3>No influencers yet</h3>
                <p>Share your registration link to start collecting creator profiles.</p>
                <button class="btn btn-dark mt-8" type="button" data-copy="<?= e(abs_url('join')) ?>"><i class="ph ph-link"></i>Copy registration link</button>
            </div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Influencer</th><th>Niche</th><th>Reach</th><th>Fee range</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><a class="person" href="<?= e(url('admin/influencers/' . $r['id'])) ?>"><span class="avatar"><?php if ($r['photo']): ?><img src="<?= e(upload_url($r['photo'])) ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($r['full_name'])) ?><?php endif; ?></span><span><b><?= e($r['full_name']) ?></b><small><?= e(trim($r['city'] . ', ' . $r['country'], ', ')) ?> &middot; <?= e(time_ago($r['created_at'])) ?></small></span></a></td>
                        <td class="small"><?= e(implode(', ', array_slice(csv_list($r['niches']), 0, 2))) ?></td>
                        <td><b><?= e(short_number($r['total_followers'])) ?></b></td>
                        <td class="nowrap"><?= e(fee_range($r['fee_min'], $r['fee_max'], $r['currency'])) ?></td>
                        <td><span class="badge b-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>

    <div class="grid" style="align-content:start">
        <section class="card">
            <div class="card-head"><h2>Active campaigns</h2><a class="more" href="<?= e(url('admin/campaigns')) ?>">All <i class="ph-bold ph-arrow-right"></i></a></div>
            <?php if (!$campaigns): ?>
                <div class="empty"><i class="ph ph-megaphone"></i><p>No active campaigns.</p><a class="btn btn-dark btn-sm" href="<?= e(url('admin/campaigns/new')) ?>">Create one</a></div>
            <?php else: ?>
                <div class="list">
                    <?php foreach ($campaigns as $c): $cv = $c['visits'] > 0 ? round($c['apps'] / $c['visits'] * 100) : 0; ?>
                        <a class="list-item" href="<?= e(url('admin/campaigns/' . $c['id'])) ?>">
                            <div><b><?= e($c['title']) ?></b><small><?= (int) $c['visits'] ?> visits &middot; <?= $cv ?>% applied</small></div>
                            <div class="right"><b><?= (int) $c['apps'] ?></b><small>applicants</small></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="card">
            <div class="card-head"><h2>Latest messages</h2><a class="more" href="<?= e(url('admin/messages')) ?>">Inbox <i class="ph-bold ph-arrow-right"></i></a></div>
            <?php if (!$messages): ?>
                <div class="empty"><i class="ph ph-envelope-simple-open"></i><p>Contact form messages will show up here.</p></div>
            <?php else: ?>
                <div class="list">
                    <?php foreach ($messages as $m): ?>
                        <a class="list-item" href="<?= e(url('admin/messages/' . $m['id'])) ?>">
                            <span class="avatar" style="background:<?= $m['inquiry_type'] === 'brand' ? '#10131C' : '#EF3B2A' ?>"><?= e(initials($m['name'])) ?></span>
                            <div style="min-width:0"><b<?= $m['is_read'] ? '' : ' style="color:#B8281A"' ?>><?= e($m['name']) ?></b><small><?= e(str_limit($m['message'], 60)) ?></small></div>
                            <div class="right"><small><?= e(time_ago($m['created_at'])) ?></small></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="grid grid-3 mt-24">
    <div class="card card-pad flex"><span class="k-ic" style="width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:var(--surface-2);font-size:22px"><i class="ph-bold ph-link"></i></span><div class="grow"><b>Registration link</b><div class="small muted"><?= e(abs_url('join')) ?></div></div><button class="btn btn-outline btn-sm" type="button" data-copy="<?= e(abs_url('join')) ?>">Copy</button></div>
    <a class="card card-pad flex" href="<?= e(url('admin/posts')) ?>"><span class="k-ic" style="width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:var(--surface-2);font-size:22px"><i class="ph-bold ph-article"></i></span><div class="grow"><b><?= $stats['posts'] ?> published posts</b><div class="small muted">Keep the blog fresh for SEO</div></div><i class="ph-bold ph-arrow-right"></i></a>
    <a class="card card-pad flex" href="<?= e(url('admin/subscribers')) ?>"><span class="k-ic" style="width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:var(--surface-2);font-size:22px"><i class="ph-bold ph-at"></i></span><div class="grow"><b><?= $stats['subscribers'] ?> subscribers</b><div class="small muted">Newsletter sign ups</div></div><i class="ph-bold ph-arrow-right"></i></a>
</div>
