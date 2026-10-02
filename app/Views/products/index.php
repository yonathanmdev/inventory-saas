<?php

use App\Helpers\PermissionHelper;

$pageTitle = 'Products';
$activeNav = 'products';

$q          = $pagination['search'];
$perPage    = $pagination['per_page'];
$page       = $pagination['page'];
$totalPages = $pagination['total_pages'];
$total      = $pagination['total'];
$status     = $pagination['status'];

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Clean URL per status
$paths = [
    'active'   => '/products',
    'inactive' => '/products/inactive',
    'all'      => '/products/all',
];

$basePath = $paths[$status] ?? '/products';

// Export is a download, so status stays a query parameter
$qs = '?' . http_build_query(array_filter([
    'q'      => $q,
    'status' => $status,
], fn($v) => $v !== ''));

$csvUrl   = '/products/export' . $qs;
$excelUrl = '/products/export/excel' . $qs;

$pageUrl = fn(int $p) => $basePath . '?' . http_build_query([
    'q'        => $q,
    'per_page' => $perPage,
    'page'     => $p,
]);

$start = max(1, $page - 2);
$end   = min($totalPages, $page + 2);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">የንብረት ዓይነቶች</h1>
        <p class="page-subtitle">በተቋሙ ውስጥ ያሉ ንብረቶችን ያስተዳድሩ</p>
    </div>

    <?php if (PermissionHelper::can('products.create')): ?>
        <a href="/products/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            የንብረት ዓይነት ይመዝግቡ
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">

    <div class="card-header bg-white py-3">
        <h5 class="mb-1">የተመዘገቡ የንብረት ዓይነቶች</h5>
        <small class="text-muted">
            <?= $total ?> product<?= $total === 1 ? '' : 's' ?>
            <?= $q !== '' ? 'matching "' . $e($q) . '"' : '' ?>
        </small>
    </div>

    <div class="card-body">
<ul class="nav nav-tabs mb-3">
    <?php foreach (['active' => 'Active', 'inactive' => 'የተሰረዙ', 'all' => 'ሁሉም'] as $key => $label): ?>

        <?php
        if ($key !== 'active' && !PermissionHelper::can('products.delete')) {
            continue;
        }
        ?>

        <li class="nav-item">
            <a class="nav-link <?= $status === $key ? 'active' : '' ?>"
               href="<?= $e($paths[$key] . '?' . http_build_query(['q' => $q, 'per_page' => $perPage])) ?>">
                <?= $label ?>
            </a>
        </li>

    <?php endforeach; ?>
</ul>
        <!-- Toolbar -->
        <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">

            <form method="get" action="/products" class="d-flex flex-wrap gap-2">
                <input
                    type="text"
                    name="q"
                    class="form-control w-auto"
                    placeholder="ፈልግ: በመለያ፣ በስም፣ ምድብ"
                    value="<?= $e($q) ?>"
                >
                <input type="hidden" name="status" value="<?= $e($status) ?>">
                <select name="per_page" class="form-select w-auto"
                        onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $n): ?>
                        <option value="<?= $n ?>" <?= $n === $perPage ? 'selected' : '' ?>>
                            <?= $n ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-outline-primary" title="Search">
                    <i class="bi bi-search"></i>
                </button>

                <?php if ($q !== ''): ?>
                    <a href="/products?per_page=<?= $perPage ?>"
                       class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <div class="dropdown">
                <button type="button" class="btn btn-outline-success dropdown-toggle"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?= $e($excelUrl) ?>">
                            <i class="bi bi-file-earmark-excel me-2"></i> Excel (.xlsx)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= $e($csvUrl) ?>">
                            <i class="bi bi-filetype-csv me-2"></i> CSV
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <?php if (empty($products)): ?>

            <div class="text-center py-5">
                <i class="bi bi-box-seam text-muted" style="font-size: 3.5rem;"></i>

                <?php if ($q !== ''): ?>
                    <h5 class="mt-3">ምንም ውጤት አልተገኘም</h5>
                    <p class="text-muted mb-0">በሌላ ቃል ለመፈለግ ይሞክሩ.</p>
                <?php else: ?>
                    <h5 class="mt-3">ምንም የተመዘገበ የንብረት ዓይነት የለም</h5>
                    <p class="text-muted mb-4">የመጀመሪያውን የንብረት ዓይነት ይመዝግቡ.</p>

                    <?php if (PermissionHelper::can('products.create')): ?>
                        <a href="/products/create" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>
                            የንብረት ዓይነት ይመዝግቡ
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table id="productsTable" class="table table-hover align-middle w-100">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ዓይነት</th>
                            <th>ምድብ</th>
                            <th>ሁኔታ</th>
                            <th>የተመዘገበበት ቀን</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <?php $isActive = (int) $product['is_active'] === 1; ?>

                        <tr>
                            <td><?= $pagination['from'] + $index ?></td>
                            <td>
                                <div class="fw-semibold"><?= $e($product['name']) ?></div>
                                <?php if (!empty($product['description'])): ?>
                                    <small class="text-muted">
                                        <?= $e(mb_strimwidth($product['description'], 0, 60, '...')) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($product['category_name'])): ?>
                                    <?= $e($product['category_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Uncategorized</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td><?= $e(date('Y-m-d', strtotime($product['created_at']))) ?></td>

                            <td class="text-end">
                                <?php if (PermissionHelper::can('products.update')): ?>
                                    <div class="btn-group btn-group-sm">
                                    <?php if ($isActive): ?>
                                        <a href="/products/<?= urlencode($product['uuid']) ?>/edit"
                                           class="btn btn-outline-secondary"
                                           title="Edit product">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <form method="POST"
                                              action="/products/<?= urlencode($product['uuid']) ?>/<?= $isActive ? 'deactivate' : 'activate' ?>"
                                              class="d-inline">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="return_to"
                                                   value="<?= $e($_SERVER['REQUEST_URI'] ?? '/products') ?>">

                                            <button type="submit"
                                                    class="btn <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                    title="<?= $isActive ? 'Deactivate product' : 'Activate product' ?>">
                                                <i class="bi <?= $isActive ? 'bi-toggle-off' : 'bi-toggle-on' ?>"></i>
                                            </button>
                                        </form>

                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">

                <small class="text-muted">
                    Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= $total ?>
                </small>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Products pagination">
                        <ul class="pagination pagination-sm mb-0">

                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $e($pageUrl(max(1, $page - 1))) ?>">«</a>
                            </li>

                            <?php if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $e($pageUrl(1)) ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $e($pageUrl($i)) ?>"><?= $i ?></a>
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

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';