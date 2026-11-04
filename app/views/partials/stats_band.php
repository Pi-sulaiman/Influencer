<?php
/** Stat band. Numbers come from Admin > Site content or live from the database; zeros are hidden. */
$stats = isset($stats) ? $stats : site_stats();
if (!$stats) {
    return;
}
?>
<section class="stats" aria-label="Peakilo in numbers">
    <div class="container stats-grid" style="--cols:<?= count($stats) ?>">
        <?php foreach ($stats as $i => $s): ?>
            <div data-reveal style="--i:<?= $i ?>">
                <span class="stat-num"><span data-count="<?= e($s['value']) ?>"<?= $s['decimals'] ? ' data-decimals="' . (int) $s['decimals'] . '"' : '' ?>>0</span><?php if ($s['suffix'] !== ''): ?><em><?= e($s['suffix']) ?></em><?php endif; ?></span>
                <span class="stat-label"><?= e($s['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
