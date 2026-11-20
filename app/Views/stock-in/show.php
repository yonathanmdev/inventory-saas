<?php

use App\Helpers\EthiopianDate;
use App\Helpers\PermissionHelper;

$pageTitle = 'Stock In';
$activeNav = 'stock-in';

$series = '';   // keep the same as in create.php

$e     = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
// Prices are stored in cents in database, divide by 100 for proper currency display
$money = fn($v) => ((float) $v) > 0 ? number_format(((float) $v) / 100, 2) : '';

$gregorianDate = $receipt['received_date'] ?? '';
$ecDate = '';
if (!empty($gregorianDate)) {
    $ecParts = EthiopianDate::fromGregorian($gregorianDate);
    if (count($ecParts) === 3) {
        // Formats as Day/Month/Year (ቀን/ወር/ዓመት)
        $ecDate = sprintf('%02d/%02d/%d', $ecParts[2], $ecParts[1], $ecParts[0]);
    }
}
$condLabel  = $conditions[$receipt['item_condition']] ?? $receipt['item_condition'];
$created_by_user = $receipt['created_by_user'] ?? null;
$blankRows  = max(0, 1 - count($items));      // pad to look like the paper form
$grand      = (float) $receipt['total_amount'];

ob_start();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
    <div>
        <h1 class="page-title">ደረሰኝ ቁጥር <?= $e($receipt['receipt_no']) ?></h1>
        <p class="page-subtitle">Registered <?= $e($receipt['created_at']) ?></p>
    </div>

    <div class="d-flex gap-2">
        <a href="<?= BASE ?>/stock-in" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> ወደ ዝርዝር
        </a>
        
        <?php if (PermissionHelper::can('stock_in.update')): ?>
            <a href="<?= BASE ?>/stock-in/<?= $e($receipt['uuid']) ?>/edit" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> አስተካክል
            </a>
        <?php endif; ?>

        <?php if (PermissionHelper::can('stock_in.create')): ?>
            <a href="<?= BASE ?>/stock-in/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>ገቢ መመዝገብ
            </a>
        <?php endif; ?>

        <button type="button" class="btn btn-outline-primary" data-print>
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<div class="model19">

    <div class="m19-top">
        <div class="m19-left">
             <span class="m19-val inline"><?= $e($condLabel) ?></span>
            <div class="m19-model">ሞዴል ፲፱ <small>Model 19</small></div>

            <div class="m19-org">
                የአማራ ብሔራዊ ክልላዊ መንግሥት ገንዘብ ቢሮ
                <span>Amhara National Regional State Finance Bureau</span>
            </div>

            <div class="m19-line">
                <span class="lbl">ክፍል <small>Department</small></span>
                <span class="m19-val"><?= $e($receipt['department'] ?? '') ?></span>
            </div>
        </div>

        <div class="m19-right">
            <div class="m19-serial">
                <span>ሴሪ Serial <?= $e($series) ?> No.</span>
                <span class="m19-receipt-view"><?= $e($receipt['receipt_no']) ?></span>
            </div>

            <div class="m19-line">
                <span class="lbl">(1) በዕቃ ገቢና ወጪ መዝገብ ተራ ቁጥር <small>Item No. in Expenditure Registry</small></span>
                <span class="m19-val"><?= $e($receipt['expenditure_item_no'] ?? '') ?></span>
            </div>
            <div class="m19-line">
                <span class="lbl">(2) በዕቃ ገቢ መዝገብ የገባበት ቁጥር <small>No. of entry in the register of incoming goods</small></span>
                <span class="m19-val"><?= $e($receipt['incoming_register_no'] ?? '') ?></span>
            </div>
            <div class="m19-line">
                <span class="lbl">(3) የዕቃ ምደባ <small>Classification of Stock</small></span>
                <span class="m19-val"><?= $e($receipt['classification'] ?? '') ?></span>
            </div>
            <div class="m19-line">
                <span class="lbl">(4) የመጋዘን ቁጥር <small>Store's No.</small></span>
                <span class="m19-val"><?= $e($receipt['store_no'] ?? '') ?></span>
            </div>
            <div class="m19-line">
                <span class="lbl">(5) የመደርደሪያ ቁጥር <small>Shelf No.</small></span>
                <span class="m19-val"><?= $e($receipt['shelf_no'] ?? '') ?></span>
            </div>
        </div>
    </div>

    <div class="m19-title">
        የዕቃ ወይም የንብረት ገቢ ደረሰኝ
        <small>Receipt for Articles or Property Received</small>
    </div>
    <div class="m19-line m19-date">
        <span class="lbl">ቀን <small>Date</small></span>
        <span class="m19-val"><?= $e($ecDate) ?> ዓ/ም</span>

        <span class="lbl m19-from">ከ <small>From</small></span>
        <span class="m19-val"><?= $e($receipt['supplier_name']) ?></span>
    </div>

    <div class="m19-scroll">
        <table class="m19-table ro">
            <thead>
                <tr>
                    <th rowspan="2" class="col-no">ተ.ቁ<small>Serial No.</small></th>
                    <th rowspan="2" class="col-desc">የዕቃ ወይም የንብረት ዓይነት በዝርዝር<small>Detailed Description of Articles or Property</small></th>
                    <th rowspan="2" class="col-sm">ሞዴል<small>Model</small></th>
                    <th rowspan="2" class="col-sm">ሴሪ<small>Serial</small></th>
                    <th colspan="2">ገጽ ቁጥር<small>Page No.</small></th>
                    <th rowspan="2" class="col-qty">ብዛት<small>Quantity</small></th>
                    <th rowspan="2" class="col-money">የአንዱ ዋጋ<small>Unit Price (ብር)</small></th>
                    <th rowspan="2" class="col-money">ጠቅላላ ዋጋ<small>Total Price (ብር)</small></th>
                </tr>
                <tr>
                    <th class="col-xs">ከ<small>From</small></th>
                    <th class="col-xs">እስከ<small>To</small></th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($items as $i => $it): ?>
                <tr>
                    <td class="ctr"><?= $i + 1 ?></td>
                    <td><?= $e($it['product_name']) ?></td>
                    <td><?= $e($it['model'] ?? '') ?></td>
                    <td><?= $e(!empty($it['serials']) ? implode(', ', $it['serials']) : '') ?></td>
                    <td><?= $e($it['page_from'] ?? '') ?></td>
                    <td><?= $e($it['page_to'] ?? '') ?></td>
                    <td class="ctr"><?= (int) $it['quantity'] ?> <?= $e($it['unit_name']) ?></td>
                    <td class="num"><?= $e($money($it['unit_price'])) ?></td>
                    <td class="num"><?= $e($money($it['total_price'])) ?></td>
                </tr>
            <?php endforeach; ?>

            <?php for ($b = 0; $b < $blankRows; $b++): ?>
                <tr>
                    <td class="ctr"><?= count($items) + $b + 1 ?></td>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            <?php endfor; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="8" class="text-end">ድምር <small class="d-inline">Total</small></td>
                    <td class="num"><?= $grand > 0 ? $e(number_format($grand, 2)) : '' ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="m19-sign">
        <div class="m19-line">
            <span class="lbl">ተረካቢው <small>Receiver (Recipient)</small></span>
            <span class="m19-val"><?= $e($created_by_user['full_name'] ?? '') ?></span>
        </div>
    </div>

</div>

<script src="<?= BASE ?>/assets/js/page-actions.js" defer></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>