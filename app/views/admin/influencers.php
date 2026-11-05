<?php
/** @var array $f @var array $rows @var array $pg @var array $campaigns @var string $view @var array $statusCounts @var array $loc */
$platforms = platform_options();
$allCount = array_sum($statusCounts);
$moreOpen = $f['niche'] || $f['platform'] || $f['min_followers'] || $f['max_fee'] !== null || $f['featured'];
$locLink = function ($set) { return url('admin/influencers' . qs(array_merge(['page' => null], $set))); };
?>
<div class="page-head">
    <div>
        <h2>Influencers</h2>
        <p><?= number_format($pg['total']) ?> profile<?= $pg['total'] == 1 ? '' : 's' ?><?= $allCount !== $pg['total'] ? ' match your filters' : ' registered' ?>.</p>
    </div>
    <div class="actions">
        <button class="btn btn-outline" type="button" data-copy="<?= e(abs_url('join')) ?>"><i class="ph ph-link"></i>Copy registration link</button>
        <a class="btn btn-dark" href="<?= e(url('admin/influencers/export' . qs(['page' => null, 'view' => null]))) ?>"><i class="ph ph-download-simple"></i>Export CSV</a>
    </div>
</div>

<div class="flex-between" style="margin-bottom:14px">
    <nav class="seg" aria-label="Filter by status">
        <a href="<?= e(url('admin/influencers' . qs(['status' => null, 'page' => null]))) ?>" class="<?= !$f['status'] ? 'is-active' : '' ?>">All <span class="n"><?= $allCount ?></span></a>
        <?php foreach (['new' => 'New', 'approved' => 'Approved', 'on_hold' => 'On hold', 'rejected' => 'Rejected'] as $s => $l): ?>
            <a href="<?= e(url('admin/influencers' . qs(['status' => $s, 'page' => null]))) ?>" class="<?= $f['status'] === $s ? 'is-active' : '' ?>"><?= $l ?> <span class="n"><?= $statusCounts[$s] ?? 0 ?></span></a>
        <?php endforeach; ?>
    </nav>
    <nav class="seg" aria-label="Layout">
        <a href="<?= e(url('admin/influencers' . qs(['view' => 'grid']))) ?>" class="<?= $view === 'grid' ? 'is-active' : '' ?>" aria-label="Card view"><i class="ph ph-squares-four"></i>Cards</a>
        <a href="<?= e(url('admin/influencers' . qs(['view' => 'table']))) ?>" class="<?= $view === 'table' ? 'is-active' : '' ?>" aria-label="Table view"><i class="ph ph-rows"></i>Table</a>
    </nav>
</div>

<section class="card">
    <form class="filters" method="get" action="<?= e(url('admin/influencers')) ?>">
        <?php if ($f['status']): ?><input type="hidden" name="status" value="<?= e($f['status']) ?>"><?php endif; ?>
        <div class="field wide"><label for="fq">Search</label><input class="input" id="fq" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Name, email, handle, city, phone"></div>
        <div class="field"><label for="fc">Campaign</label>
            <select class="select" id="fc" name="campaign"><option value="">Any campaign</option>
                <?php foreach ($campaigns as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $f['campaign'] == $c['id'] ? ' selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="field"><label for="fs">Sort by</label>
            <select class="select" id="fs" name="sort">
                <?php foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'followers' => 'Most followers', 'views' => 'Most avg. views', 'fee_low' => 'Lowest fee', 'fee_high' => 'Highest fee', 'name' => 'Name A-Z', 'updated' => 'Recently updated'] as $k => $l): ?>
                    <option value="<?= $k ?>"<?= $f['sort'] === $k ? ' selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select></div>
        <button class="btn btn-ghost" type="button" data-toggle-filters aria-expanded="<?= $moreOpen ? 'true' : 'false' ?>"><i class="ph ph-sliders-horizontal"></i>More filters</button>
        <button class="btn btn-dark" type="submit">Apply</button>
        <div class="field"><label for="fco">Country</label>
            <select class="select" id="fco" name="country" onchange="this.form.state.value='';this.form.city.value='';this.form.submit()"><option value="">All countries</option>
                <?php foreach ($loc['countries'] as $c): ?><option value="<?= e($c['k']) ?>"<?= $f['country'] === $c['k'] ? ' selected' : '' ?>><?= e($c['k']) ?> (<?= (int) $c['n'] ?>)</option><?php endforeach; ?>
                <?php if ($f['country'] !== '' && !in_array($f['country'], array_column($loc['countries'], 'k'), true)): ?><option value="<?= e($f['country']) ?>" selected><?= e($f['country']) ?> (0)</option><?php endif; ?>
            </select></div>
        <div class="field"><label for="fst">State</label>
            <select class="select" id="fst" name="state" onchange="this.form.city.value='';this.form.submit()"<?= $f['country'] === '' ? ' disabled title="Choose a country first"' : '' ?>><option value=""><?= $f['country'] === '' ? 'Choose a country first' : 'All states' ?></option>
                <?php foreach ($loc['states'] as $c): ?><option value="<?= e($c['k']) ?>"<?= $f['state'] === $c['k'] ? ' selected' : '' ?>><?= e($c['k']) ?> (<?= (int) $c['n'] ?>)</option><?php endforeach; ?>
            </select></div>
        <div class="field"><label for="fci">City</label>
            <select class="select" id="fci" name="city" onchange="this.form.submit()"<?= $f['country'] === '' ? ' disabled title="Choose a country first"' : '' ?>><option value=""><?= $f['country'] === '' ? 'Choose a country first' : 'All cities' ?></option>
                <?php foreach ($loc['cities'] as $c): ?><option value="<?= e($c['k']) ?>"<?= $f['city'] === $c['k'] ? ' selected' : '' ?>><?= e($c['k']) ?> (<?= (int) $c['n'] ?>)</option><?php endforeach; ?>
            </select></div>
        <?php if (array_filter([$f['q'], $f['campaign'], $f['niche'], $f['platform'], $f['country'], $f['state'], $f['city'], $f['min_followers'], $f['max_fee'], $f['featured']])): ?>
            <a class="btn btn-ghost" href="<?= e(url('admin/influencers' . ($f['status'] ? '?status=' . $f['status'] : ''))) ?>">Clear</a>
        <?php endif; ?>
        <div class="more-filters" data-more-filters<?= $moreOpen ? '' : ' hidden' ?>>
            <div class="field"><label for="fn">Niche</label>
                <select class="select" id="fn" name="niche"><option value="">Any niche</option>
                    <?php foreach (niche_options() as $n): ?><option<?= $f['niche'] === $n ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="fp">Platform</label>
                <select class="select" id="fp" name="platform"><option value="">Any platform</option>
                    <?php foreach ($platforms as $k => $p): ?><option value="<?= $k ?>"<?= $f['platform'] === $k ? ' selected' : '' ?>><?= e($p['label']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="fmf">Min. reach</label><input class="input" id="fmf" name="min_followers" value="<?= $f['min_followers'] ? e(short_number($f['min_followers'])) : '' ?>" placeholder="e.g. 50K"></div>
            <div class="field"><label for="fmx">Starting fee up to</label><input class="input" id="fmx" name="max_fee" value="<?= $f['max_fee'] !== null ? e($f['max_fee'] + 0) : '' ?>" inputmode="decimal" placeholder="e.g. 500"></div>
            <div class="field" style="min-width:auto;flex:0"><label>&nbsp;</label><label class="check" style="height:38px;align-items:center"><input type="checkbox" name="featured" value="1"<?= $f['featured'] === '1' ? ' checked' : '' ?>>Featured</label></div>
        </div>
    </form>

    <?php
    // Where the creators are: click a place to filter by it.
    if ($f['country'] === '') { $chips = $loc['countries']; $chipKey = 'country'; $chipTitle = 'Creators by country'; }
    elseif ($f['state'] === '' && $loc['states']) { $chips = $loc['states']; $chipKey = 'state'; $chipTitle = 'States in ' . $f['country']; }
    else { $chips = $loc['cities']; $chipKey = 'city'; $chipTitle = 'Cities in ' . ($f['state'] !== '' ? $f['state'] : $f['country']); }
    ?>
    <?php if ($chips || $f['country'] !== ''): ?>
        <div class="loc-bar">
            <span class="loc-title"><i class="ph ph-map-pin"></i><?= e($chipTitle) ?></span>
            <div class="loc-chips">
                <?php foreach (array_slice($chips, 0, 24) as $c): ?>
                    <a class="loc-chip<?= $f[$chipKey] === $c['k'] ? ' is-active' : '' ?>" href="<?= e($locLink($chipKey === 'country' ? ['country' => $c['k'], 'state' => null, 'city' => null] : ($chipKey === 'state' ? ['state' => $c['k'], 'city' => null] : ['city' => $c['k']]))) ?>"><?= e($c['k']) ?><b><?= (int) $c['n'] ?></b></a>
                <?php endforeach; ?>
                <?php if (!$chips): ?><span class="small muted">No creators with a saved <?= e($chipKey) ?> yet.</span><?php endif; ?>
                <?php if ($f['country'] !== ''): ?><a class="loc-chip loc-clear" href="<?= e($locLink(['country' => null, 'state' => null, 'city' => null])) ?>"><i class="ph ph-x"></i>All places</a><?php endif; ?>
            </div>
            <?php if ($f['country'] !== '' && $f['state'] === '' && $loc['states'] && $loc['cities']): ?>
                <div class="loc-chips loc-sub"><span class="small muted">Top cities:</span>
                    <?php foreach (array_slice($loc['cities'], 0, 12) as $c): ?><a class="loc-chip" href="<?= e($locLink(['city' => $c['k']])) ?>"><?= e($c['k']) ?><b><?= (int) $c['n'] ?></b></a><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$rows): ?>
        <div class="empty" style="border-top:1px solid var(--line)">
            <i class="ph ph-users-three"></i>
            <h3><?= $allCount ? 'No influencers match these filters' : 'No influencers registered yet' ?></h3>
            <p><?= $allCount ? 'Try removing a filter or searching for something else.' : 'Share the registration link or create a campaign link to start collecting profiles.' ?></p>
            <?php if (!$allCount): ?><a class="btn btn-primary mt-8" href="<?= e(url('admin/campaigns/new')) ?>">Create a campaign link</a><?php endif; ?>
        </div>
    <?php else: ?>
    <form method="post" action="<?= e(url('admin/influencers/bulk')) ?>" data-bulk>
        <?= csrf_field() ?>
        <div class="bulkbar">
            <label class="check" style="align-items:center"><input type="checkbox" data-check-all> <span class="count" data-selected-count>Select all</span></label>
            <select class="select input-sm" name="action" style="width:auto">
                <option value="">Bulk action</option>
                <option value="approved">Mark approved</option>
                <option value="on_hold">Mark on hold</option>
                <option value="rejected">Mark rejected</option>
                <option value="new">Mark new</option>
                <option value="feature">Feature on website</option>
                <option value="unfeature">Remove from website</option>
                <option value="delete">Delete</option>
            </select>
            <button class="btn btn-outline btn-sm" type="submit" data-bulk-apply disabled>Apply</button>
        </div>

        <?php if ($view === 'grid'): ?>
            <div class="inf-grid">
                <?php foreach ($rows as $r):
                    $plats = array_filter(array_keys($platforms), function ($p) use ($r) { return !empty($r[$p]); }); ?>
                    <article class="inf-card">
                        <label class="ic-sel" aria-label="Select <?= e($r['full_name']) ?>"><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" data-row-check></label>
                        <span class="ic-status badge b-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                        <div class="ic-photo">
                            <?php if ($r['photo']): ?><img src="<?= e(upload_url($r['photo'])) ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($r['full_name'])) ?><?php endif; ?>
                            <?php if ($r['featured']): ?><span class="ic-feat"><i class="ph-fill ph-star"></i>Featured</span><?php endif; ?>
                        </div>
                        <div class="ic-body">
                            <div class="ic-name"><a href="<?= e(url('admin/influencers/' . $r['id'])) ?>"><?= e($r['full_name']) ?></a><?php if (!empty($r['display_name'])): ?> <span class="small muted"><?= e($r['display_name']) ?></span><?php endif; ?></div>
                            <div class="ic-loc"><?= e(implode(', ', array_filter([$r['city'], $r['state'] ?? '', $r['country']]))) ?: '&nbsp;' ?></div>
                            <div class="tags"><?php foreach (array_slice(csv_list($r['niches']), 0, 3) as $n): ?><span class="tag"><?= e($n) ?></span><?php endforeach; ?></div>
                            <div class="ic-stats">
                                <div><b><?= e(short_number($r['total_followers'])) ?></b><span>followers<?= !empty($r['avg_impressions']) ? ' &middot; ' . e(short_number($r['avg_impressions'])) . ' avg. views' : '' ?></span></div>
                                <div><b><?= e(fee_range($r['fee_min'], $r['fee_max'], $r['currency'])) ?></b><span>fee range</span></div>
                            </div>
                            <div class="ic-plat"><?php foreach ($plats as $p): ?><i class="ph-fill <?= e($platforms[$p]['icon']) ?>" title="<?= e($platforms[$p]['label'] . ': ' . short_number($r[$p . '_followers'])) ?>"></i><?php endforeach; ?><span class="small muted" style="margin-left:auto"><?= e(time_ago($r['created_at'])) ?></span></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th style="width:40px"></th><th>Influencer</th><th>Channels</th><th>Reach</th><th>Niches</th><th>Fee range</th><th>Apps</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" data-row-check aria-label="Select <?= e($r['full_name']) ?>"></td>
                        <td><a class="person" href="<?= e(url('admin/influencers/' . $r['id'])) ?>"><span class="avatar"><?php if ($r['photo']): ?><img src="<?= e(upload_url($r['photo'])) ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($r['full_name'])) ?><?php endif; ?></span><span><b><?= e($r['full_name']) ?><?= $r['featured'] ? ' <i class="ph-fill ph-star" style="color:#F5A524" title="Featured"></i>' : '' ?></b><small><?= e($r['email']) ?></small></span></a></td>
                        <td class="nowrap" style="font-size:18px"><?php foreach ($platforms as $k => $p): if (!empty($r[$k])): ?><a href="<?= e(social_url($k, $r[$k])) ?>" target="_blank" rel="noopener" title="<?= e($p['label'] . ': ' . $r[$k]) ?>"><i class="ph-fill <?= e($p['icon']) ?>"></i></a> <?php endif; endforeach; ?></td>
                        <td><b><?= e(short_number($r['total_followers'])) ?></b><?php if (!empty($r['avg_impressions'])): ?><div class="small muted"><?= e(short_number($r['avg_impressions'])) ?> avg. views</div><?php endif; ?><?php if ($r['engagement_rate']): ?><div class="small muted"><?= e($r['engagement_rate'] + 0) ?>% ER</div><?php endif; ?></td>
                        <td class="small"><?= e(implode(', ', array_slice(csv_list($r['niches']), 0, 3))) ?></td>
                        <td class="nowrap"><b><?= e(fee_range($r['fee_min'], $r['fee_max'], $r['currency'])) ?></b></td>
                        <td><?= (int) $r['app_count'] ?></td>
                        <td><span class="badge b-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
                        <td class="small muted nowrap"><?= e(nice_date($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </form>
    <?php partial('admin_pager', ['pg' => $pg]); ?>
    <?php endif; ?>
</section>
