<?php
use App\Helpers\PermissionHelper;
use App\Helpers\EthiopianDate;

$pageTitle = 'Stock In';
$activeNav = 'stock-in';

$q          = $pagination['search'];
$condition  = $pagination['condition'];
$perPage    = $pagination['per_page'];
$page       = $pagination['page'];
$totalPages = $pagination['total_pages'];
$total      = $pagination['total'];

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$pageUrl = fn(int $p) => '/stock-in?' . http_build_query(array_filter([
    'q'         => $q,
    'condition' => $condition,
    'per_page'  => $perPage,
    'page'      => $p,
], fn($v) => $v !== ''));

$badge = ['new' => 'text-bg-success', 'used' => 'text-bg-warning', 'consumable' => 'text-bg-info'];

$start = max(1, $page - 2);
$end   = min($totalPages, $page + 2);

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">ገቢ ዕቃ ደረሰኞች</h1>
        <p class="page-subtitle">የተመዘገቡ የዕቃ ወይም የንብረት ገቢ ደረሰኞች (ሞዴል ፲፱)</p>
    </div>

    <?php if (PermissionHelper::can('stock_in.create')): ?>
        <a href="<?= BASE ?>/stock-in/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>ገቢ መመዝገብ
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">

    <div class="card-header bg-white py-3">
        <h5 class="mb-1">የተመዘገቡ ገቢ ዕቃዎች</h5>
        <small class="text-muted">
            <?= $total ?> receipt<?= $total === 1 ? '' : 's' ?>
            <?= $q !== '' ? 'matching "' . $e($q) . '"' : '' ?>
        </small>
    </div>

    <div class="card-body">

        <form method="get" action="<?= BASE ?>/stock-in" class="d-flex flex-wrap gap-2 mb-3">
            <input type="text" name="q" class="form-control w-auto search-input"
                   placeholder="ፈልግ: የደረሰኝ ቁጥር፣ ከ፣ ተረካቢ" value="<?= $e($q) ?>">

            <select name="condition" class="form-select w-auto" data-auto-submit>
                <option value="">ሁሉም</option>
                <?php foreach ($conditions as $key => $label): ?>
                    <option value="<?= $e($key) ?>" <?= $key === $condition ? 'selected' : '' ?>>
                        <?= $e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="per_page" class="form-select w-auto" data-auto-submit>
                <?php foreach ([10, 25, 50, 100] as $n): ?>
                    <option value="<?= $n ?>" <?= $n === $perPage ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-outline-primary" title="Search">
                <i class="bi bi-search"></i>
            </button>

            <?php if ($q !== '' || $condition !== ''): ?>
                <a href="<?= BASE ?>/stock-in?per_page=<?= $perPage ?>" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($receipts)): ?>

            <div class="text-center py-5">
                <i class="bi bi-box-arrow-in-down text-muted empty-icon"></i>
                <h5 class="mt-3">
                    <?= ($q !== '' || $condition !== '') ? 'ምንም ውጤት አልተገኘም' : 'ምንም የተመዘገበ ደረሰኝ የለም' ?>
                </h5>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>የደረሰኝ ቁጥር</th>
                            <th>ቀን</th>
                            <th>ከ (From)</th>
                            <th>ሁኔታ</th>
                            <th class="text-end">ዕቃዎች</th>
                            <th class="text-end">ጠቅላላ ዋጋ</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($receipts as $i => $r): ?>
                        <tr>
                            <td><?= $pagination['from'] + $i ?></td>

                            <td>
                                <a href="<?= BASE ?>/stock-in/<?= $e(urlencode($r['uuid'])) ?>" class="fw-semibold text-decoration-none">
                                    <?= $e($r['receipt_no']) ?>
                                </a>
                            </td>

                            <td>
<?php 
 $gregorianDate = $r['received_date'] ?? '';
$ecDate = '';
if (!empty($gregorianDate)) {
    $ecParts = EthiopianDate::fromGregorian($gregorianDate);
    if (count($ecParts) === 3) {
        // Formats as Day/Month/Year (ቀን/ወር/ዓመት)
        $ecDate = sprintf('%02d/%02d/%d', $ecParts[2], $ecParts[1], $ecParts[0]);
    }
}
echo $e($ecDate); // <--- ቀኑ በሠንጠረዡ ውስጥ እንዲታይ እዚህ ላይ ታትሟል
?>
                          
                            </td>

                            <td class="cell-wrap"><?= $e($r['supplier_name']) ?></td>

                            <td>
                                <span class="badge <?= $badge[$r['item_condition']] ?? 'text-bg-secondary' ?>">
                                    <?= $e($conditions[$r['item_condition']] ?? $r['item_condition']) ?>
                                </span>
                            </td>

                            <td class="text-end"><?= (int) $r['item_count'] ?></td>

                            <td class="text-end text-nowrap">
                                <?= (float) $r['total_amount'] > 0
                                    ? $e(number_format((float) $r['total_amount'], 2))
                                    : '<span class="text-muted">—</span>' ?>
                            </td>

                            <td class="text-end">
                                <?php if (PermissionHelper::can('stock_in.update')): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE ?>/stock-in/<?= urlencode($r['uuid']) ?>/edit"
                                           class="btn btn-outline-secondary"
                                           title="Edit stock-in">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                <a href="<?= BASE ?>/stock-in/<?= $e(urlencode($r['uuid'])) ?>"
                                   class="btn btn-sm btn-outline-primary" title="View / print">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <small class="text-muted">
                    Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= $total ?>
                </small>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Stock-in pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $e($pageUrl(max(1, $page - 1))) ?>">«</a>
                            </li>

                            <?php if ($start > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= $e($pageUrl(1)) ?>">1</a></li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($n = $start; $n <= $end; $n++): ?>
                                <li class="page-item <?= $n === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $e($pageUrl($n)) ?>"><?= $n ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end < $totalPages): ?>
                                <?php if ($end < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $e($pageUrl($totalPages)) ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $e($pageUrl(min($totalPages, $page + 1))) ?>">»</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<script src="<?= BASE ?>/assets/js/page-actions.js" defer></script>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';