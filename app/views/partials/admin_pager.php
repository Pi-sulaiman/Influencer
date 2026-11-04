<?php /** @var array $pg */
if ($pg['pages'] <= 1) return;
$path = request_path();
$window = 2;
?>
<nav class="pager" aria-label="Pagination">
    <?php if ($pg['page'] > 1): ?><a href="<?= e(url(ltrim($path, '/') . qs(['page' => $pg['page'] - 1]))) ?>" aria-label="Previous page"><i class="ph-bold ph-caret-left"></i></a><?php endif; ?>
    <?php for ($n = 1; $n <= $pg['pages']; $n++):
        if ($n !== 1 && $n !== $pg['pages'] && abs($n - $pg['page']) > $window) {
            if ($n === 2 || $n === $pg['pages'] - 1) echo '<span>&hellip;</span>';
            continue;
        } ?>
        <?php if ($n === $pg['page']): ?><span class="is-current" aria-current="page"><?= $n ?></span>
        <?php else: ?><a href="<?= e(url(ltrim($path, '/') . qs(['page' => $n]))) ?>"><?= $n ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($pg['page'] < $pg['pages']): ?><a href="<?= e(url(ltrim($path, '/') . qs(['page' => $pg['page'] + 1]))) ?>" aria-label="Next page"><i class="ph-bold ph-caret-right"></i></a><?php endif; ?>
</nav>
