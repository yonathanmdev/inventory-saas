<?php
use App\Helpers\PermissionHelper;
use App\Helpers\EthiopianDate;

$pageTitle = 'Stock Out';
$activeNav = 'stock-out';

$q          = $pagination['search'];
$status     = $pagination['status'];
$perPage    = $pagination['per_page'];
$page       = $pagination['page'];
$totalPages = $pagination['total_pages'];
$total      = $pagination['total'];

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$canViewAll = PermissionHelper::can('stock_out.view_all');
$canApprove = PermissionHelper::can('stock_out.approve');
$canIssue   = PermissionHelper::can('stock_out.issue');
$canUpdate  = PermissionHelper::can('stock_out.update');          // ← አዲስ
$userId     = (int) ($_SESSION['user_id'] ?? 0);                  // ← አዲስ
$pageUrl = fn(int $p) => '/stock-out/requests?' . http_build_query(array_filter([
    'q'        => $q,
    'status'   => $status,
    'per_page' => $perPage,
    'page'     => $p,
], fn($v) => $v !== ''));

$badge = [
    'pending'  => 'text-bg-warning',
    'approved' => 'text-bg-primary',
    'issued'   => 'text-bg-success',
    'rejected' => 'text-bg-danger',
];

$start = max(1, $page - 2);
$end   = min($totalPages, $page + 2);

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">የዕቃ መጠየቂያ ጥያቄዎች</h1>
        <p class="page-subtitle">
            <?= $canViewAll ? 'የሁሉም ተጠቃሚዎች ጥያቄዎች (ሞዴል ፳)' : 'የእርስዎ ጥያቄዎች (ሞዴል ፳)' ?>
        </p>
    </div>

    <?php if (PermissionHelper::can('stock_out.create')): ?>
        <a href="<?= BASE ?>/stock-out/requests/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>አዲስ ጥያቄ
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">

    <div class="card-header bg-white py-3">
        <h5 class="mb-1">የተመዘገቡ ጥያቄዎች</h5>
        <small class="text-muted">
            <?= $total ?> request<?= $total === 1 ? '' : 's' ?>
            <?= $q !== '' ? 'matching "' . $e($q) . '"' : '' ?>
        </small>
    </div>

    <div class="card-body">

        <form method="get" action="<?= BASE ?>/stock-out/requests" class="d-flex flex-wrap gap-2 mb-3">
            <input type="text" name="q" class="form-control w-auto search-input"
                   placeholder="<?= $canViewAll ? 'ፈልግ: የጥያቄ ቁጥር፣ ጠያቂ' : 'ፈልግ: የጥያቄ ቁጥር' ?>"
                   value="<?= $e($q) ?>">

            <select name="status" class="form-select w-auto" data-auto-submit>
                <option value="">ሁሉም</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $e($key) ?>" <?= $key === $status ? 'selected' : '' ?>>
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

            <?php if ($q !== '' || $status !== ''): ?>
                <a href="<?= BASE ?>/stock-out/requests?per_page=<?= $perPage ?>" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($requests)): ?>

            <div class="text-center py-5">
                <i class="bi bi-box-arrow-up text-muted empty-icon"></i>
                <h5 class="mt-3">
                    <?= ($q !== '' || $status !== '') ? 'ምንም ውጤት አልተገኘም' : 'ምንም የተመዘገበ ጥያቄ የለም' ?>
                </h5>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ቀን</th>
                            <?php if ($canViewAll): ?>
                                <th>ጠያቂ</th>
                            <?php endif; ?>
                            <th>ክፍል</th>
                            <th>ሁኔታ</th>
                            <th class="text-end">ዕቃዎች</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $i => $r): ?>
                        <?php $link = '/stock-out/requests/' . urlencode($r['uuid']); ?>
                        <tr>
                            <td><?= $pagination['from'] + $i ?></td>

                            <td>
                                <?php
                                $ecDate = '';
                                if (!empty($r['request_date'])) {
                                    $ecParts = EthiopianDate::fromGregorian($r['request_date']);
                                    if (count($ecParts) === 3) {
                                        $ecDate = sprintf('%02d/%02d/%d', $ecParts[2], $ecParts[1], $ecParts[0]);
                                    }
                                }
                                echo $e($ecDate);
                                ?>
                            </td>

                            <?php if ($canViewAll): ?>
                                <td class="cell-wrap"><?= $e($r['requester_name']) ?></td>
                            <?php endif; ?>

                            <td class="cell-wrap"><?= $e($r['dept'] ?? '') ?></td>

                            <td>
                                <span class="badge <?= $badge[$r['status']] ?? 'text-bg-secondary' ?>">
                                    <?= $e($statuses[$r['status']] ?? $r['status']) ?>
                                </span>
                            </td>

                            <td class="text-end"><?= (int) $r['item_count'] ?></td>

                            <td class="text-end text-nowrap">
                              <div class="btn-group btn-group-sm">

    <a href="<?= $e($link) ?>" class="btn btn-outline-primary" title="እይ">
        <i class="bi bi-eye"></i>
    </a>

<?php $link = '/stock-out/requests/' . urlencode($r['uuid']); ?>

<?php if ($r['status'] === 'approved'): ?>
    <a href="<?= $e($link) ?>/print" class="btn btn-outline-dark"
       title="ሞዴል 20 አትም" target="_blank" rel="noopener">
        <i class="bi bi-printer"></i>
    </a>
<?php elseif ($r['status'] === 'issued'): ?>
    <a href="<?= $e($link) ?>/model22" class="btn btn-outline-dark"
       title="ሞዴል ፳፪ አትም" target="_blank" rel="noopener">
        <i class="bi bi-printer"></i>
    </a>
<?php endif; ?>
    <?php if ($r['status'] === 'pending'
              && $canUpdate
              && ((int) $r['created_by'] === $userId || $canViewAll)): ?>
        <a href="<?= $e($link) ?>/edit" class="btn btn-outline-secondary" title="አስተካክል">
            <i class="bi bi-pencil"></i>
        </a>
    <?php endif; ?>

    <?php if ($r['status'] === 'pending' && $canApprove): ?>
        <a href="<?= $e($link) ?>" class="btn btn-outline-success" title="ገምግም እና አጽድቅ">
            <i class="bi bi-clipboard-check"></i>
        </a>
    <?php endif; ?>

   <?php if ($r['status'] === 'approved' && $canIssue): ?>
    <a href="<?= BASE ?>/stock-out/issue/<?= $e($r['uuid']) ?>" 
       class="btn btn-outline-dark btn-sm" 
       title="ዕቃ አውጣ (Model 22)">
        <i class="bi bi-box-arrow-up"></i>
    </a>
<?php endif; ?>

</div>
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
                    <nav aria-label="Stock-out pagination">
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