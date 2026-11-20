<?php
use App\Helpers\PermissionHelper;
use App\Helpers\EthiopianDate;
$conditionLabels = \App\Services\StockOutRequestService::CONDITIONS;

$canViewAll    = PermissionHelper::can('stock_out.view_all');
$showAvailable = $canViewAll;   // "ያለ" ብዛት
$showCondition = $canViewAll;   // የዕቃ ሁኔታ ስም
// (1) ከላይ፣ ከ $pageTitle በፊት
$isEdit      = $isEdit      ?? false;
$formAction  = $formAction  ?? '/stock-out/requests';
$cancelUrl   = $cancelUrl   ?? '/dashboard';
$submitLabel = $submitLabel ?? 'ጥያቄውን መዝግብ';
$pageTitle = 'የዕቃ መጠየቂያና የወጭ ማዘዣ';
$activeNav = 'stock-out';

$formTitle = 'የዕቃ መጠየቂያና የወጭ ማዘዣ';
$formModel = 'ሞዴል 20';

$e   = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$old = is_array($old ?? null) ? $old : [];
$val = fn(string $key, string $default = '') => $e($old[$key] ?? $default);

[$todayY, $todayM, $todayD] = $today ?? [2019, 1, 1];
$dateValue = $old['ec_date'] ?? sprintf('%02d/%02d/%d', $todayD, $todayM, $todayY);

$rows     = is_array($old['items'] ?? null) ? array_values($old['items']) : [];
$rowCount = max(1, count($rows));

// ከ Controller የሚመጣው $isApproved ካለ እንጠቀማለን፣ ካለፈ ግን በነባሪነት false ይሆናል
$isApproved = $isApproved ?? false;

ob_start();
?>

<div class="mb-4">
<h1 class="page-title"><?= $isEdit ? 'የዕቃ መጠየቂያ ማስተካከያ' : 'የዕቃ መጠየቂያና የወጭ ማዘዣ' ?></h1>
    <p class="page-subtitle">ቅጹን ይሙሉ፣ ያስቀምጡ፣ ከዚያ አትመው ያስፈርሙ።</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $e($error) ?>
    </div>
<?php endif; ?>
<form method="POST" action="<?= $e($formAction) ?>" id="requestForm" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">

    <div class="m20-pages">
        <div class="m20-sheet">

            <!-- ራስጌ የሚታየው ጥያቄው ሲጸድቅ ወይም ለማተም ሲዘጋጅ ($isApproved = true ሲሆን) ብቻ ነው -->
            <?php if ($isApproved): ?>
                <?php require __DIR__ . '/../partials/m20-header.php'; ?>
            <?php endif; ?>
            
            <div class="ec-date-wrapper">
                <input
                    type="text"
                    name="ec_date"
                    class="ethiopian-date ec-date-input"
                    required
                    value="<?= $e($dateValue) ?>"
                    placeholder="ቀን/ወር/ዓመት"
                    inputmode="none"
                    autocomplete="off"
                    pattern="\d{1,2}/\d{1,2}/\d{4}"
                >
                <span class="lbl">ዓ/ም</span>
            </div>

            <div class="m20-instline">በአብክመ <?= $e($letterhead['heading_am'] ?? '') ?></div>

            <div class="m20-place">
                <input type="text" name="place" class="m20-in m20-in-place" maxlength="100"
                       placeholder="ባ/ዳር" value="<?= $val('address', 'ባህር ዳር') ?>" readonly>:
            </div>

            <p class="m20-body">
                ከዚህ በታች የፈረምኩት
               <input 
                    type="text" 
                    name="requester_name" 
                    class="m20-in m20-in-name" 
                    maxlength="150" 
                    required
                    value="<?= $e(mb_convert_case($old['requester_name'] ?? ($_SESSION['full_name'] ?? ''), MB_CASE_TITLE, 'UTF-8')) ?>"                    readonly
                >
                ለ
                <input type="text" name="dept" class="m20-in m20-in-unit" maxlength="150" required
                       value="<?= $val('dept') ?>" placeholder="የተጠቃሚው ክፍል ስም">
                ክፍል አገልግሎት የሚውሉ ከዚህ ቀጥሎ በዝርዝር የተመለከቱትን ዕቃዎች ወጭ ሆነው እንዲሰጡኝ እጠይቃለሁ።
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
                            <th class="c-act no-print"></th>
                        </tr>
                    </thead>

                        <tbody data-items-body data-show-available="<?= $showAvailable ? '1' : '0' ?>">
                        <?php for ($i = 0; $i < $rowCount; $i++): ?>
                            <?php $row = is_array($rows[$i] ?? null) ? $rows[$i] : []; ?>
                           <tr>
                                <td class="row-no"><?= $i + 1 ?></td>
                                <td>
                                    <div class="screen-only">
                                        <select name="items[<?= $i ?>][item]" class="item-select">
    <option value="">— ምረጥ —</option>
    <?php foreach ($stockOptions as $o): ?>
        <?php
        $condition = (string) $o['item_condition'];
        $key = $o['product_uuid'] . ':' . $o['unit_uuid'] . ':' . $o['model_key'] . ':' . $condition;
        ?>
        <option
            value="<?= $e($key) ?>"
            data-product="<?= $e($o['product_uuid']) ?>"
            data-unit-uuid="<?= $e($o['unit_uuid']) ?>"
            data-unit="<?= $e($o['unit_name']) ?>"
            data-model="<?= $e($o['model']) ?>"
            data-model-key="<?= $e($o['model_key']) ?>"
            <?php if ($showCondition): ?>data-condition="<?= $e($condition) ?>"<?php endif; ?>
            <?php if ($showAvailable): ?>data-available="<?= (int) $o['available'] ?>"<?php endif; ?>
            <?= (($row['item'] ?? '') === $key) ? 'selected' : '' ?>
        >
            <?= $e($o['product_name']) ?>
            — <?= $e($o['model'] ?: 'ሞዴል የለውም') ?>
            <?php if ($showCondition): ?>
                — <?= $e($conditionLabels[$condition] ?? $condition) ?>
            <?php endif; ?>
            — በ<?= $e($o['unit_name']) ?>
            <?php if ($showAvailable): ?>
                (ያለ፦ <?= (int) $o['available'] ?>)
            <?php endif; ?>
        </option>
    <?php endforeach; ?>
</select>
                                    </div>
                                    <span class="print-only item-print-label"></span>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        name="items[<?= $i ?>][qty]"
                                        class="qty"
                                        min="1"
                                        max="100000"
                                        step="1"
                                        value="<?= $e($row['qty'] ?? '') ?>"
                                    >
                                    <small class="unit-hint no-print"></small>
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="items[<?= $i ?>][model]"
                                        class="model-input"
                                        maxlength="100"
                                        value="<?= $e($row['model'] ?? '') ?>"
                                    readonly>
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="items[<?= $i ?>][remark]"
                                        maxlength="200"
                                        value="<?= $e($row['remark'] ?? '') ?>"
                                    >
                                </td>
                                <td class="no-print">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link text-danger remove-row"
                                        title="ረድፉን ባዶ አድርግ"
                                    >
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-2 mb-3 no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-add-row>
                    <i class="bi bi-plus-lg me-1"></i> ረድፍ ጨምር
                </button>
                <small class="d-inline text-muted ms-2">
                    እያንዳንዱ 20 ዕቃዎች እንደ አንድ ገጽ ይታተማሉ።
                </small>
            </div>
   
            <!-- ፊርማ እና እግር ማስታወሻ የሚታዩት ሲጸድቅ ብቻ ነው -->
            <?php if ($isApproved): ?>
                <?php $signerName = ''; require __DIR__ . '/../partials/m20-sign.php'; ?>
                <?php require __DIR__ . '/../partials/m20-footer.php'; ?>
            <?php endif; ?>

        </div>
    </div>

  <!-- አዝራሩ ሁልጊዜ ከስር እንዲጣበቅ (Sticky/Fixed) ማድረግ ከፈለጉ -->
    <div class="d-flex justify-content-end gap-2 mt-3 p-3 bg-white border-top shadow-sm sticky-bottom">
       <a href="<?= $e($cancelUrl) ?>" class="btn btn-outline-secondary">ሰርዝ</a>
<button type="submit" class="btn btn-primary">
    <i class="bi bi-check-lg me-1"></i> <?= $e($submitLabel) ?>
</button>
    </div>
</form>

<script src="<?= BASE ?>/assets/js/letter-items.js" defer></script>
<script src="<?= BASE ?>/assets/js/calendar-converter.js" defer></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';