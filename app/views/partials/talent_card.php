<?php
/** One public creator card. @var array $c (from roster_card) */
$href = url('talent/' . $c['code']);
$meta = trim(($c['niches'][0] ?? 'Creator') . ' · ' . short_number($c['reach']) . ' followers');
$slAttrs = 'data-shortlist data-code="' . e($c['code']) . '" data-name="' . e($c['name']) . '" data-photo="' . e($c['photo']) . '" data-meta="' . e($meta) . '"' . (!empty($c['demo']) ? ' data-fallback="' . e(demo_fallback_photo($c['id'])) . '"' : '');
?>
<article class="tcard<?= !empty($extra) ? ' ' . e($extra) : '' ?>" data-tcard data-code="<?= e($c['code']) ?>" data-niches="<?= e(implode('|', $c['niches'])) ?>" data-plats="<?= e(implode('|', $c['platforms'])) ?>" data-tier="<?= e($c['tier']['key']) ?>" data-reach="<?= (int) $c['reach'] ?>" data-views="<?= (int) $c['views'] ?>" data-er="<?= e((string) ($c['er'] ?? 0)) ?>" data-created="<?= e($c['created']) ?>" data-featured="<?= $c['featured'] ? 1 : 0 ?>" data-search="<?= e(mb_strtolower($c['name'] . ' ' . $c['city'] . ' ' . $c['country'] . ' ' . implode(' ', $c['niches']) . ' ' . $c['code'])) ?>"<?= isset($c['match']) && !$c['match'] ? ' hidden' : '' ?>>
    <a class="tc-media" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e($c['photo']) ?>" alt="" loading="lazy" width="400" height="460"<?= photo_fallback_attr($c) ?>>
        <span class="tc-tier"><?= e($c['tier']['label']) ?></span>
        <?php if (!empty($c['demo'])): ?><span class="tc-sample" title="Sample profile while our network grows">Sample</span><?php endif; ?>
        <?php if ($c['featured']): ?><span class="tc-feat"><i class="ph-fill ph-star"></i>Top pick</span><?php endif; ?>
    </a>
    <button type="button" class="tc-save" <?= $slAttrs ?> aria-pressed="false" aria-label="Add <?= e($c['name']) ?> to your shortlist"><i class="ph-bold ph-heart" aria-hidden="true"></i></button>
    <div class="tc-body">
        <a class="tc-name" href="<?= e($href) ?>"><?= e($c['name']) ?><?php if (empty($c['demo'])): ?> <i class="ph-fill ph-seal-check" aria-label="Vetted by Peakilo"></i><?php endif; ?></a>
        <div class="tc-loc"><?= e(implode(', ', array_slice($c['niches'], 0, 2))) ?><?= $c['city'] || $c['country'] ? ' &middot; ' . e($c['city'] ?: $c['country']) : '' ?></div>
        <div class="tc-stats">
            <div><b><?= e(short_number($c['reach'])) ?></b><span>followers</span></div>
            <div><b><?= $c['views'] ? e(short_number($c['views'])) : '-' ?></b><span>avg. views</span></div>
            <div><b><?= $c['er'] !== null ? e($c['er'] + 0) . '%' : '-' ?></b><span>engagement</span></div>
        </div>
        <div class="tc-chans">
            <?php foreach ($c['channels'] as $ch): ?>
                <span title="<?= e($ch['label']) ?>"><i class="ph-fill <?= e($ch['icon']) ?>" aria-hidden="true"></i><?= e(short_number($ch['followers'])) ?><span class="sr-only"> on <?= e($ch['label']) ?></span></span>
            <?php endforeach; ?>
        </div>
        <div class="tc-actions">
            <a class="btn btn-ghost btn-sm" href="<?= e($href) ?>">View profile</a>
            <button type="button" class="btn btn-dark btn-sm tc-add" <?= $slAttrs ?> aria-pressed="false"><i class="ph-bold ph-plus" aria-hidden="true"></i><span data-sl-label>Shortlist</span></button>
        </div>
    </div>
</article>
