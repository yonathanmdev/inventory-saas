<style nonce="<?= htmlspecialchars(
    \App\Helpers\Csp::nonce(),
    ENT_QUOTES,
    'UTF-8'
) ?>">
    .app-breadcrumb .breadcrumb-separator {
        font-size: 10px;
    }
</style>
<?php if (!empty($breadcrumbs)): ?>
<nav class="app-breadcrumb d-flex align-items-center gap-2">
    <?php $last = array_key_last($breadcrumbs); ?>
    <?php foreach ($breadcrumbs as $key => $crumb): ?>
        <?php if ($key > 0): ?><i class="bi bi-chevron-right text-muted"></i><?php endif; ?>
        <?php if ($key === $last): ?>
            <span class="current"><?= htmlspecialchars($crumb['label']) ?></span>
        <?php else: ?>
            <a href="<?= htmlspecialchars($crumb['href']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php endif; ?>