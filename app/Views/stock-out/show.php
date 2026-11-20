<?php
use App\Helpers\EthiopianDate;
use App\Helpers\PermissionHelper;
use App\Services\StockOutRequestService;

/**
 * Stock-out request: workflow page (approve / reject / issue / edit / print link).
 *
 * Variables supplied by StockOutRequestController::show():
 *
 *   $letter           the request (with 'items')
 *   $items            $letter['items']
 *   $availableByItem  item id => available quantity (only for a pending
 *                     request and a user who may approve; otherwise [])
 *
 * NOTE:
 *   $letter['dept']      = the requester's department (stock_out_requests.dept)
 *   $item['unit_name']   = the MEASUREMENT unit of an item (pcs, box ...)
 *   These are two different things.
 */

$pageTitle = 'የዕቃ መጠየቂያ ' . ($letter['request_no'] ?? '');
$activeNav = 'stock-out';

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$statuses        = StockOutRequestService::STATUSES;
$conditionLabels = StockOutRequestService::CONDITIONS;

$badge = [
    'pending'  => 'text-bg-warning',
    'approved' => 'text-bg-primary',
    'issued'   => 'text-bg-success',
    'rejected' => 'text-bg-danger',
];

$status = (string) $letter['status'];
$link   = '/stock-out/requests/' . urlencode($letter['uuid']);
$csrf   = $e($_SESSION['csrf_token'] ?? '');
$userId = (int) ($_SESSION['user_id'] ?? 0);

/* ---------- permissions ---------- */

$canViewAll = PermissionHelper::can('stock_out.view_all');
$canApprove = PermissionHelper::can('stock_out.approve');
$canIssue   = PermissionHelper::can('stock_out.issue');
$canUpdate  = PermissionHelper::can('stock_out.update');

$canPrint = in_array($status, ['approved', 'issued'], true);

$canEdit = $status === 'pending'
    && $canUpdate
    && ((int) $letter['created_by'] === $userId || $canViewAll);

// The item condition is shown only to users who can also see stock quantities.
$showCondition = $canViewAll;

// For an approver the items are listed inside the approval form,
// so the read-only table below would only repeat them.
$showItemsTable = !($status === 'pending' && $canApprove);

/* ---------- display values ---------- */

// Gregorian (date or datetime) → Ethiopian dd/mm/yyyy
$toEc = function (?string $value): string {
    if (empty($value)) {
        return '';
    }

    $parts = EthiopianDate::fromGregorian(substr($value, 0, 10));

    return count($parts) === 3
        ? sprintf('%02d/%02d/%d', $parts[2], $parts[1], $parts[0])
        : '';
};

$dateValue    = $toEc($letter['request_date'] ?? null);
$approvedDate = $toEc($letter['approved_at'] ?? null);
$issuedDate   = $toEc($letter['issued_at'] ?? null);

$requesterName = mb_convert_case((string) $letter['requester_name'], MB_CASE_TITLE, 'UTF-8');

// If the column is named differently (for example "department"), change only this line.
$deptName = trim((string) ($letter['dept'] ?? ''));

ob_start();
?>

<!-- ───────────────────────── Header and actions ───────────────────────── -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4 no-print">
    <div>
        <h1 class="page-title">
            የዕቃ መጠየቂያ
            <?php if (!empty($letter['request_no'])): ?>
                <span class="text-muted">#<?= $e($letter['request_no']) ?></span>
            <?php endif; ?>
        </h1>
        <p class="page-subtitle mb-0">
            <span class="badge <?= $badge[$status] ?? 'text-bg-secondary' ?>">
                <?= $e($statuses[$status] ?? $status) ?>
            </span>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE ?>/stock-out/requests" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>ወደ ዝርዝር
        </a>

        <?php if ($canEdit): ?>
            <a href="<?= $e($link) ?>/edit" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>አስተካክል
            </a>
        <?php endif; ?>

<?php if ($canPrint): ?>
    <a href="<?= $e($link) ?>/print" class="btn btn-outline-primary"
       target="_blank" rel="noopener">
        <i class="bi bi-printer me-1"></i>ሞዴል 20 አትም
    </a>
<?php endif; ?>
        <?php if ($status === 'pending' && $canApprove): ?>
            <button type="button" class="btn btn-outline-danger"
                    data-bs-toggle="collapse" data-bs-target="#rejectBox">
                <i class="bi bi-x-lg me-1"></i>ውድቅ አድርግ
            </button>
        <?php endif; ?>
<?php if ($status === 'approved' && $canIssue): ?>
    <a href="<?= BASE ?>/stock-out/issue/<?= $e($request['uuid']) ?>" class="btn btn-dark d-inline-flex align-items-center">
        <i class="bi bi-box-arrow-up me-1"></i>ዕቃ አውጣ (Model 22)
    </a>
<?php endif; ?>
<?php if ($status === 'issued'): ?>
    <a href="<?= $e($link) ?>/model22" class="btn btn-outline-primary" target="_blank" rel="noopener">
        <i class="bi bi-printer me-1"></i>ሞዴል ፳፪ 
    </a>
<?php endif; ?>
    </div>
</div>

<!-- ───────────────────────── Reject form ───────────────────────── -->
<?php if ($status === 'pending' && $canApprove): ?>
    <div class="collapse mb-4 no-print" id="rejectBox">
        <form method="post" action="<?= $e($link) ?>/reject" class="card card-body">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <label for="reason" class="form-label">ውድቅ የተደረገበት ምክንያት</label>
            <textarea id="reason" name="reason" class="form-control mb-2"
                      rows="2" maxlength="500" required></textarea>
            <div>
                <button type="submit" class="btn btn-danger btn-sm">ውድቅ አድርግ</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- ───────────────────────── Notices ───────────────────────── -->
<?php if ($status === 'pending'): ?>
    <div class="alert alert-warning no-print">
        <i class="bi bi-info-circle me-1"></i>
        ጥያቄው እስኪጸድቅ ድረስ ማተም አይቻልም።
    </div>
<?php endif; ?>

<?php if ($status === 'rejected'): ?>
    <div class="alert alert-danger no-print">
        <i class="bi bi-x-circle me-1"></i>
        ውድቅ ተደርጓል።
        <?php if (!empty($letter['rejection_reason'])): ?>
            ምክንያት፦ <?= $e($letter['rejection_reason']) ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ───────────────────────── Request details ───────────────────────── -->
<div class="card shadow-sm mb-4 no-print">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">የጥያቄው ዝርዝር</h5>
    </div>

    <div class="card-body">
        <div class="row g-3 <?= $showItemsTable ? 'mb-3' : '' ?>">
            <div class="col-sm-6 col-lg-3">
                <div class="text-muted small">ጠያቂ</div>
                <div class="fw-semibold"><?= $e($requesterName) ?></div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="text-muted small">ክፍል</div>
                <div class="fw-semibold"><?= $deptName !== '' ? $e($deptName) : '—' ?></div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="text-muted small">ቀን</div>
                <div class="fw-semibold"><?= $e($dateValue) ?> ዓ/ም</div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="text-muted small">ሁኔታ</div>
                <span class="badge <?= $badge[$status] ?? 'text-bg-secondary' ?>">
                    <?= $e($statuses[$status] ?? $status) ?>
                </span>
            </div>

            <?php if ($approvedDate !== ''): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted small">የጸደቀበት ቀን</div>
                    <div class="fw-semibold"><?= $e($approvedDate) ?> ዓ/ም</div>
                </div>
            <?php endif; ?>

            <?php if ($issuedDate !== ''): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted small">ዕቃ የወጣበት ቀን</div>
                    <div class="fw-semibold"><?= $e($issuedDate) ?> ዓ/ም</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($showItemsTable): ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">ተ.ቁ</th>
                            <th>የዕቃው ዓይነት</th>
                            <th>ሞዴል</th>
                            <?php if ($showCondition): ?>
                                <th>ሁኔታ</th>
                            <?php endif; ?>
                            <th class="text-end">የተጠየቀ</th>
                            <?php if ($canPrint): ?>
                                <th class="text-end">የተፈቀደ</th>
                            <?php endif; ?>
                            <th>ማብራሪያ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <?php
                            $requested = (int) $item['quantity'];
                            $approvedQ = (int) ($item['approved_quantity'] ?? $item['quantity']);
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= $e($item['product_name']) ?></td>
                                <td><?= $e($item['model'] ?? '') ?></td>
                                <?php if ($showCondition): ?>
                                    <td><?= $e($conditionLabels[$item['item_condition']] ?? '') ?></td>
                                <?php endif; ?>
                                <td class="text-end"><?= $requested ?> <?= $e($item['unit_name']) ?></td>
                                <?php if ($canPrint): ?>
                                    <td class="text-end <?= $approvedQ < $requested ? 'text-danger fw-semibold' : '' ?>">
                                        <?= $approvedQ ?> <?= $e($item['unit_name']) ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= $e($item['remark'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ───────────────────────── Approval form (pending + approver) ───────────────────────── -->
<?php if ($status === 'pending' && $canApprove): ?>
    <form method="post" action="<?= $e($link) ?>/approve" class="card card-body mb-4 no-print"
          data-confirm="ይህን ጥያቄ በዚህ ብዛት ማጽደቅ ይፈልጋሉ?">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <h6 class="mb-3">የሚፈቀደውን ብዛት ያረጋግጡ</h6>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-3">
                <thead>
                    <tr>
                        <th>ዕቃ</th>
                        <th>ሁኔታ</th>
                        <th class="text-end">የተጠየቀ</th>
                        <th class="text-end">ያለ</th>
                        <th style="width:140px">የሚፈቀድ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php
                    $avail = (int) ($availableByItem[$item['id']] ?? 0);
                    $max   = min((int) $item['quantity'], $avail);
                    ?>
                    <tr>
                        <td>
                            <?= $e($item['product_name']) ?>
                            <small class="text-muted"><?= $e($item['model'] ?? '') ?></small>
                        </td>
                        <td><?= $e($conditionLabels[$item['item_condition']] ?? '') ?></td>
                        <td class="text-end"><?= (int) $item['quantity'] ?> <?= $e($item['unit_name']) ?></td>
                        <td class="text-end <?= $avail < (int) $item['quantity'] ? 'text-danger fw-semibold' : '' ?>">
                            <?= $avail ?>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm"
                                   name="approved[<?= (int) $item['id'] ?>]"
                                   min="0" max="<?= $max ?>" step="1" value="<?= $max ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i>አጽድቅ
            </button>
        </div>
    </form>
<?php endif; ?>

<script src="<?= BASE ?>/assets/js/page-actions.js" defer></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';