<?php
use App\Helpers\EthiopianDate;

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$deptAm = trim((string) ($_SESSION['business_name_amharic'] ?? ''));
$deptEn = trim((string) ($_SESSION['business_name'] ?? ''));

$toEc = function (?string $v): string {
    if (empty($v)) return '';
    $p = EthiopianDate::fromGregorian(substr($v, 0, 10));
    return count($p) === 3 ? sprintf('%02d/%02d/%d', $p[2], $p[1], $p[0]) : '';
};

// "100.10" -> [100, '10', 10010]; whole cents avoid float errors when adding
$split = function ($price): array {
    $c = (int) round(((float) $price) * 100);
    return [intdiv($c, 100), sprintf('%02d', $c % 100), $c];
};

$lines      = is_array($lines ?? null) ? $lines : [];
$grandCents = 0;
?>
<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ሞዴል ፳፪ <?= $e($letter['request_no'] ?? '') ?></title>

    <!-- Copy the Bootstrap and Bootstrap Icons <link> tags from layouts/dashboard.php here -->
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/model22.css">
</head>
<body>

<div class="m22-toolbar no-print">
    <a href="<?= BASE ?>/stock-out/requests/<?= $e($letter['uuid']) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>ወደ ጥያቄው
    </a>
    <button type="button" class="btn btn-primary" data-print>
        <i class="bi bi-printer me-1"></i> አትም
    </button>
</div>

<div class="model22-container">

<div class="m22-head">

    <div class="m22-head-left">
        <div class="m22-model">ሞዴል ፳፪ <small>Model 22</small></div>
    </div>

    <div class="m22-head-center">
        <?php if (!empty($letterhead['logo_url'])): ?>
            <img src="<?= $e($letterhead['logo_url']) ?>" alt="" class="m22-logo">
        <?php endif; ?>
<!-- Regional state: static, the same for every business -->
<div class="m22-region-am">የአማራ ብሔራዊ ክልላዊ መንግሥት</div>
<div class="m22-region-en">Amhara National Regional State</div>

        <?php if ($deptAm !== ''): ?>
            <div class="m22-heading-am"><?= $e($deptAm) ?></div>
        <?php endif; ?>
        <?php if ($deptEn !== ''): ?>
            <div class="m22-heading-en"><?= $e($deptEn) ?></div>
        <?php endif; ?>

        <div class="m22-dept">
            <div class="m22-dept-value"><?= $e($letter['dept'] ?? '') ?></div>
            <div class="m22-dept-label">Department</div>
        </div>
    </div>

    <div class="m22-head-right">
        <span class="m22-no-label">№</span>
        <span class="m22-no-value"><?= $e($orderNo) ?></span>
    </div>

</div>

    <div class="m22-title">
        <h2>የዕቃ ወይም የንብረት መውጫ ደረሰኝ</h2>
        <small>Receipt for Articles or Property</small>
    </div>

    <div class="m22-meta">
        <div class="m22-line">
            <div>እኔ <span class="field-line"><?= $e($letter['requester_name'] ?? '') ?></span></div>
            <div>ቀን: <span class="field-line"><?= $e($toEc($letter['issued_at'] ?? null)) ?> ዓ/ም</span></div>
        </div>
        <div class="m22-note">
            እቃዎችን ተቀብሎ መመርመር የሚከተሉትን እቃዎች አገልግሎት ላይ እንዲውሉ በዝርዝር ተመልክቼ በውጤቱ ትክክለኛ መሆኑን በፊርማዬ አረጋግጣለሁ።
        </div>
    </div>

    <div class="m22-scroll">
        <table class="m22-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-no">ተ.ቁ<small>No.</small></th>
                    <th rowspan="2" class="col-desc">የዕቃው ወይም የንብረቱ ዓይነትና ዝርዝር<small>Detailed Description</small></th>
                    <th rowspan="2" class="col-sm">ሞዴል<small>Model</small></th>
                    <th rowspan="2" class="col-serial">ሴሪያል ቁጥር<small>Serial No.</small></th>
                    <th colspan="2">የገጽ ቁጥር<small>Page No.</small></th>
                    <th rowspan="2" class="col-qty">ብዛት<small>Quantity</small></th>
                    <th colspan="2">የአንድ ዋጋ<small>Unit Price</small></th>
                    <th colspan="2">የጠቅላላ ዋጋ<small>Total Price</small></th>
                    <th rowspan="2" class="col-remark">ምርመራ<small>Remarks</small></th>
                </tr>
                <tr>
                    <th class="col-xs">ከ</th><th class="col-xs">እስከ</th>
                    <th class="col-xs">ብር</th><th class="col-xs">ሰ.</th>
                    <th class="col-xs">ብር</th><th class="col-xs">ሰ.</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($lines as $n => $l):
                $qty = (int) $l['quantity'];
                [$ub, $uc, $unitCents] = $split($l['unit_price']);
                $lineCents   = $unitCents * $qty;
                $grandCents += $lineCents;
            ?>
                <tr>
                    <td><?= $n + 1 ?></td>
                    <td class="cell-left">
                        <strong><?= $e($l['product_name']) ?></strong>
                        <div class="cell-sub">ደረሰኝ <?= $e($l['receipt_no']) ?></div>
                    </td>
                    <td><?= $e($l['model'] !== '' ? $l['model'] : '-') ?></td>
                    <td class="cell-left cell-sub"><?= $l['serials'] ? $e(implode(', ', $l['serials'])) : '-' ?></td>
                    <td>-</td>
                    <td>-</td>
                    <td><strong><?= $qty ?> <?= $e($l['unit_name']) ?></strong></td>
                    <td class="cell-right"><?= number_format($ub) ?></td>
                    <td><?= $uc ?></td>
                    <td class="cell-right"><?= number_format(intdiv($lineCents, 100)) ?></td>
                    <td><?= sprintf('%02d', $lineCents % 100) ?></td>
                    <td><?= $e($l['remark'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="9" class="cell-right"><strong>ድምር / Total</strong></td>
                    <td class="cell-right"><strong><?= number_format(intdiv($grandCents, 100)) ?></strong></td>
                    <td><strong><?= sprintf('%02d', $grandCents % 100) ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php
$storekeeperName = mb_convert_case(trim((string) ($_SESSION['full_name'] ?? '')), MB_CASE_TITLE, 'UTF-8');
$recipientName   = mb_convert_case(trim((string) ($letter['requester_name'] ?? '')), MB_CASE_TITLE, 'UTF-8');
?>

<div class="m22-signatures">
    <div class="m22-sig-party">
        <span class="m22-sig-label">የግምጃ ቤቱ ፊርማ <small>Storekeeper's Signature</small></span>
        <span class="m22-sig-name"><?= $e($storekeeperName) ?></span>
        <span class="m22-sig-line"></span>
    </div>

    <div class="m22-sig-party">
        <span class="m22-sig-label">የተቀባይ ፊርማ <small>Recipient's Signature</small></span>
        <span class="m22-sig-name"><?= $e($recipientName) ?></span>
        <span class="m22-sig-line"></span>
    </div>
</div>

    <div class="m22-footer-note">
        <strong>ማስታወሻ፦</strong> ይህ ፎርም በ3 ቅጂ ሆኖ ይዘጋጃል፦ 1ኛው ለተቀባይ ይሰጣል፣ 2ኛው ለንብረት አስተዳደር ሰራተኛ ይሰጣል፣ 3ኛው በመጋዘኑ ይቀመጣል።
    </div>
</div>

<script src="<?= BASE ?>/assets/js/print-button.js" defer></script>
</body>
</html>