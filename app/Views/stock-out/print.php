<?php
use App\Helpers\EthiopianDate;
use App\Services\StockOutRequestService;

/**
 * Read-only print view of an APPROVED / ISSUED stock-out request (Model 20).
 *
 * Variables supplied by StockOutRequestController::printView():
 *
 *   $letter         the request (with 'items')
 *   $printItems     approved lines only (approved quantity > 0)
 *   $letterhead     business heading / logo
 *   $showCondition  show the item condition next to the item name
 *
 * There is NO form, NO input and NO action button in this document.
 * Only the toolbar (Back / Print) is visible on screen, and it is
 * hidden when printing (.no-print).
 */

$pageTitle = 'የዕቃ መጠየቂያ ህትመት';
$activeNav = 'stock-out';

$formTitle = 'የዕቃ መጠየቂያና የወጭ ማዘዣ';
$formModel = 'ሞዴል 20';

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// The partials (header / sign / footer) are shown only for approved requests.
$isApproved = true;
$signerName = '';

$conditionLabels = StockOutRequestService::CONDITIONS;

// Gregorian → Ethiopian (ቀን/ወር/ዓመት)
$dateValue = '';
if (!empty($letter['request_date'])) {
    $p = EthiopianDate::fromGregorian($letter['request_date']);
    if (count($p) === 3) {
        $dateValue = sprintf('%02d/%02d/%d', $p[2], $p[1], $p[0]);
    }
}

$requesterName = mb_convert_case((string) $letter['requester_name'], MB_CASE_TITLE, 'UTF-8');
$unitName      = trim((string) ($letter['unit_name'] ?? ''));

$backUrl = '/stock-out/requests';

// Every 20 items are printed as one page
$pages = array_chunk($printItems, 20);
if (!$pages) {
    $pages = [[]];
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
    <a href="<?= $e($backUrl) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>ተመለስ
    </a>

    <button type="button" class="btn btn-primary btn-sm" data-print>
        <i class="bi bi-printer me-1"></i>አትም
    </button>
</div>

<div class="m20-pages">
    <?php foreach ($pages as $pageIndex => $pageItems): ?>
        <div class="m20-sheet">

            <?php require __DIR__ . '/../partials/m20-header.php'; ?>

            <div class="ec-date-wrapper">
                <span class="ec-date-input"><?= $e($dateValue) ?></span>
                <span class="lbl">ዓ/ም</span>
            </div>

            <div class="m20-instline">በአብክመ <?= $e($letterhead['heading_am'] ?? '') ?></div>

            <div class="m20-place">ባህር ዳር:</div>

            <p class="m20-body">
                ከዚህ በታች የፈረምኩት
                <strong><?= $e($requesterName) ?></strong>
                <?php if ($unitName !== ''): ?>
                    ለ <strong><?= $e($unitName) ?></strong> ክፍል
                <?php endif; ?>
                አገልግሎት የሚውሉ ከዚህ ቀጥሎ በዝርዝር የተመለከቱትን ዕቃዎች ወጭ ሆነው እንዲሰጡኝ እጠይቃለሁ።
            </p>

            <div class="m19-scroll">
                <table class="m20-table">
                    <thead>
                        <tr>
                            <th class="c-no">ተ.ቁ<small>No.</small></th>
                            <th>የዕቃው ዓይነት<small>Item</small></th>
                            <th class="c-qty">የዕቃው ብዛት<small>Quantity</small></th>
                            <th class="c-model">ሞዴል<small>Model</small></th>
                            <th class="c-rem">ማብራሪያ<small>Purpose / Remark</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pageItems as $i => $item): ?>
                            <?php
                            $qty = (int) ($item['approved_quantity'] ?? $item['quantity']);
                            $cond = (string) ($item['item_condition'] ?? '');
                            ?>
                            <tr>
                                <td class="row-no"><?= ($pageIndex * 20) + $i + 1 ?></td>
                                <td>
                                    <?= $e($item['product_name']) ?>
                                    <?php if ($showCondition && $cond !== ''): ?>
                                        <small>(<?= $e($conditionLabels[$cond] ?? $cond) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $qty ?>
                                    <small><?= $e($item['unit_name']) ?></small>
                                </td>
                                <td><?= $e($item['model'] ?? '') ?></td>
                                <td><?= $e($item['remark'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php require __DIR__ . '/../partials/m20-sign.php'; ?>
            <?php require __DIR__ . '/../partials/m20-footer.php'; ?>

        </div>
    <?php endforeach; ?>
</div>

<script src="<?= BASE ?>/assets/js/page-actions.js" defer></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';