<?php
use App\Helpers\EthiopianDate;

// Determine if it's edit mode based on whether receipt uuid exists
$isEdit = !empty($receipt['uuid']);
$pageTitle = $isEdit ? 'ገቢ ዕቃ ማስተካከያ' : 'ገቢ ዕቃ ደረሰኝ';
$activeNav = 'stock-in';

$deptAm = trim((string) ($_SESSION['business_name_amharic'] ?? ''));
$deptEn = trim((string) ($_SESSION['business_name'] ?? ''));
$conditions = ['new' => 'አዲስ', 'used' => 'ያገለገሉ', 'consumable' => 'አላቂ'];

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Combine old session data or database receipt data safely
$old = is_array($old ?? null) ? $old : ($receipt ?? []);
$val = fn(string $key, string $default = '') => $e($old[$key] ?? $default);

// Date handling for create vs edit
if ($isEdit && !empty($old['ec_date'])) {
    $dateValue = $old['ec_date'];
} else {
    [$todayY, $todayM, $todayD] = $today ?? [2018, 1, 1];
    $dateValue = $old['ec_date'] ?? sprintf('%02d/%02d/%d', $todayD, $todayM, $todayY);
}

$rows = is_array($old['items'] ?? null) ? array_values($old['items']) : [];
$rowCount = max(1, count($rows));
$selSource = (int) ($old['source'] ?? 0);
$selCondition = (string) ($old['item_condition'] ?? '');

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'ገቢ ዕቃ ማስተካከያ' : 'ገቢ ዕቃ ደረሰኝ' ?></h1>
        <p class="page-subtitle"><?= $isEdit ? 'የተመዘገበውን የዕቃ ወይም የንብረት ገቢ ደረሰኝ ማስተካከል' : 'የዕቃ ወይም የንብረት ገቢ ደረሰኝ (ሞዴል ፲፱)' ?></p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger no-print">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $e($error) ?>
    </div>
<?php endif; ?>

<!-- Form Action and Method dynamically switch for Create or Edit -->
<form method="POST" action="<?= $isEdit ? '/stock-in/' . $e($old['uuid']) : '/stock-in' ?>" id="stockInForm" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <div class="model19">

        <!-- Header -->
        <div class="m19-top">
            <div class="m19-left">
                <div class="m19-model">ሞዴል ፲፱ <small>Model 19</small></div>

                <div class="m19-org">
                    የአማራ ብሔራዊ ክልላዊ መንግሥት ገንዘብ ቢሮ
                    <span>Amhara National Regional State Finance Bureau</span>
                </div>

                <div class="m19-line">
                    <span class="lbl">ክፍል <small>Department</small></span>
                    <span class="m19-dept">
                        <?php if ($deptAm !== ''): ?>
                            <span class="m19-dept-am"><?= $e($deptAm) ?></span>
                        <?php endif; ?>
                        <?php if ($deptEn !== '' && $deptEn !== $deptAm): ?>
                            <span class="m19-dept-en"><?= $e($deptEn) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="m19-right">
                <div class="m19-serial">
                    <span>ሴሪ Serial <?= $e($series ?? '') ?> No.</span>
                    <input type="text" name="receipt_no" class="m19-receipt" maxlength="30" required
                           value="<?= $val('receipt_no') ?>">
                </div>

                <label class="m19-line">
                    <span class="lbl">(1) በዕቃ ገቢና ወጪ መዝገብ ተራ ቁጥር <small>Item No. in Expenditure Registry</small></span>
                    <input type="text" name="expenditure_item_no" maxlength="50" value="<?= $val('expenditure_item_no') ?>">
                </label>
                <label class="m19-line">
                    <span class="lbl">(2) በዕቃ ገቢ መዝገብ የገባበት ቁጥር <small>No. of entry in the register of incoming goods</small></span>
                    <input type="text" name="incoming_register_no" maxlength="50" value="<?= $val('incoming_register_no') ?>">
                </label>
                <label class="m19-line">
                    <span class="lbl">(3) የዕቃ ምደባ <small>Classification of Stock</small></span>
                    <input type="text" name="classification" maxlength="100" value="<?= $val('classification') ?>">
                </label>
                <label class="m19-line">
                    <span class="lbl">(4) የመጋዘን ቁጥር <small>Store's No.</small></span>
                    <input type="text" name="store_no" maxlength="50" value="<?= $val('store_no') ?>">
                </label>
                <label class="m19-line">
                    <span class="lbl">(5) የመደርደሪያ ቁጥር <small>Shelf No.</small></span>
                    <input type="text" name="shelf_no" maxlength="50" value="<?= $val('shelf_no') ?>">
                </label>
            </div>
        </div>

        <!-- Title -->
        <div class="m19-title">
            የዕቃ ወይም የንብረት ገቢ ደረሰኝ
            <small>Receipt for Articles or Property Received</small>
        </div>

        <div class="m19-line m19-date">
            <span class="lbl">ቀን <small>Date</small></span>
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

            <select name="source" id="source" required aria-label="Source">
                <option value="" disabled <?= $selSource === 0 ? 'selected' : '' ?>>— ምንጭ —</option>
                <?php foreach ($sources as $key => $label): ?>
                    <option value="<?= (int) $key ?>" <?= (int) $key === $selSource ? 'selected' : '' ?>>
                        <?= $e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <span class="lbl m19-from">ከ <small>From</small></span>
            <input type="text" name="supplier_name" maxlength="150" required value="<?= $val('supplier_name') ?>">

            <select name="item_condition" id="item_condition" required aria-label="Item condition">
                <option value="" disabled <?= empty($selCondition) ? 'selected' : '' ?>>— የዕቃው ሁኔታ —</option>
                <?php foreach ($conditions as $key => $label): ?>
                    <option value="<?= $e($key) ?>" <?= (string) $key === (string) $selCondition ? 'selected' : '' ?>>
                        <?= $e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-md-none text-muted small mb-1 no-print">← swipe the table sideways →</div>

        <div class="m19-scroll">
            <table class="m19-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-no">ተ.ቁ<small>Serial No.</small></th>
                        <th rowspan="2" class="col-desc">የዕቃ ወይም የንብረት ዓይነት በዝርዝር<small>Detailed Description of Articles or Property</small></th>
                        <th rowspan="2" class="col-sm">ሞዴል<small>Model</small></th>
                        <th rowspan="2" class="col-qty">ብዛት<small>Quantity</small></th>
                        <th rowspan="2" class="col-sm">ሴሪ<small>Serial</small></th>
                        <th colspan="2">ገጽ ቁጥር<small>Page No.</small></th>
                        <th rowspan="2" class="col-sm">መለኪያ<small>Unit</small></th>
                        <th rowspan="2" class="col-money">የአንዱ ዋጋ<small>Unit Price (ብር)</small></th>
                        <th rowspan="2" class="col-money">ጠቅላላ ዋጋ<small>Total Price (ብር)</small></th>
                        <th rowspan="2" class="col-act no-print"></th>
                    </tr>
                    <tr>
                        <th class="col-xs">ከ<small>From</small></th>
                        <th class="col-xs">እስከ<small>To</small></th>
                    </tr>
                </thead>

                <tbody id="itemsBody">
                <?php for ($i = 0; $i < $rowCount; $i++): ?>
                    <?php 
                        $row = is_array($rows[$i] ?? null) ? $rows[$i] : []; 
                        // Keep support for both create input post structure and database retrieval keys
                        $productUuid = $row['product'] ?? $row['uuid'] ?? '';
                        $unitUuid = $row['unit'] ?? $row['unit_uuid'] ?? '';
                        $priceVal = $row['price'] ?? (isset($row['unit_price']) ? number_format($row['unit_price'] / 100, 2, '.', '') : '');
                    ?>
                    <tr>
                        <td class="row-no"><?= $i + 1 ?></td>

                        <td>
                            <?php if (!empty($row['id'])): ?>
                                <input type="hidden" name="items[<?= $i ?>][id]" value="<?= (int) $row['id'] ?>">
                            <?php endif; ?>

                            <select name="items[<?= $i ?>][product]" class="product-select" required>
                                <option value="">— ምረጥ —</option>
                                <?php foreach ($products as $p): ?>
                                    <option
                                        value="<?= $e($p['uuid']) ?>"
                                        data-is-serialized="<?= (int) ($p['is_serialized'] ?? 0) ?>"
                                        <?= ($productUuid === $p['uuid']) ? 'selected' : '' ?>
                                    >
                                        <?= $e($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="sel-text"></span>
                        </td>
                        <td><textarea name="items[<?= $i ?>][model]" rows="1" maxlength="100"><?= $e($row['model'] ?? '') ?></textarea></td>
                        <td><input type="number" name="items[<?= $i ?>][qty]" class="qty" min="1" max="100000" step="1"
                                   value="<?= $e($row['qty'] ?? $row['quantity'] ?? '') ?>" required></td>
    
                        <td class="serial-cell">
                            <div class="serial-required no-print mb-1 d-none">
                                <span class="badge text-bg-warning">ሴሪ ያስፈልጋል</span>
                                <span class="d-block text-muted small">Serial numbers required</span>
                            </div>
                            <div class="serial-fields">
                                <?php
                                $serials = is_array($row['serials'] ?? null) ? array_values($row['serials']) : [];
                                foreach ($serials as $serialIndex => $serial):
                                ?>
                                    <input
                                        type="text"
                                        name="items[<?= $i ?>][serials][]"
                                        class="form-control form-control-sm serial-input mb-1"
                                        maxlength="150"
                                        value="<?= $e($serial) ?>"
                                        placeholder="Serial <?= $serialIndex + 1 ?>"
                                    >
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <td><textarea name="items[<?= $i ?>][page_from]" rows="1" maxlength="20"><?= $e($row['page_from'] ?? '') ?></textarea></td>
                        <td><textarea name="items[<?= $i ?>][page_to]" rows="1" maxlength="20"><?= $e($row['page_to'] ?? '') ?></textarea></td>
                        <td>
                            <select name="items[<?= $i ?>][unit]" aria-label="Unit">
                                <option value="">መለኪያ ይምረጡ</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $e($u['uuid']) ?>"
                                        <?= ($unitUuid === $u['uuid']) ? 'selected' : '' ?>>
                                        <?= $e($u['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="sel-text"></span>
                        </td>
                        <td><input type="text" name="items[<?= $i ?>][price]" class="price" inputmode="decimal"
                                   maxlength="12" value="<?= $e($priceVal) ?>" required></td>

                        <td class="line-total"></td>

                        <td class="no-print">
                            <button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove row">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                <?php endfor; ?>
                </tbody>

                <template id="stockInRowTemplate">
                    <tr>
                        <td class="row-no"></td>
                        <td>
                            <select name="items[__INDEX__][product]" class="product-select" required>
                                <option value="">— ምረጥ —</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $e($p['uuid']) ?>" data-is-serialized="<?= (int) ($p['is_serialized'] ?? 0) ?>">
                                        <?= $e($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="sel-text"></span>
                        </td>
                        <td><textarea name="items[__INDEX__][model]" rows="1" maxlength="100"></textarea></td>
                        <td><input type="number" name="items[__INDEX__][qty]" class="qty" min="1" max="100000" step="1" required></td>
                        <td class="serial-cell">
                            <div class="serial-required no-print mb-1 d-none">
                                <span class="badge text-bg-warning">ሴሪ ያስፈልጋል</span>
                                <span class="d-block text-muted small">Serial numbers required</span>
                            </div>
                            <div class="serial-fields"></div>
                        </td>
                        <td><textarea name="items[__INDEX__][page_from]" rows="1" maxlength="20"></textarea></td>
                        <td><textarea name="items[__INDEX__][page_to]" rows="1" maxlength="20"></textarea></td>
                        <td>
                            <select name="items[__INDEX__][unit]" aria-label="Unit">
                                <option value="">መለኪያ ይምረጡ</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $e($u['uuid']) ?>"><?= $e($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="sel-text"></span>
                        </td>
                        <td><input type="text" name="items[__INDEX__][price]" class="price" inputmode="decimal" maxlength="12" required></td>
                        <td class="line-total"></td>
                        <td class="no-print">
                            <button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove row">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                </template>

                <tfoot>
                    <tr>
                        <td colspan="9" class="text-end">ድምር <small class="d-inline">Total</small></td>
                        <td id="grandTotal" class="text-end"></td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-2 no-print">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="addRow">
                <i class="bi bi-plus-lg me-1"></i> Add row
            </button>
        </div>

    </div>

    <div class="d-flex justify-content-end gap-2 mt-3 no-print">
        <a href="<?= BASE ?>/dashboard" class="btn btn-outline-secondary">Cancel</a>
        <button type="button" class="btn btn-outline-primary" data-print>
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'አስተካክል' : 'መዝግብ' ?>
        </button>
    </div>
</form>

<script src="<?= BASE ?>/assets/js/stock-in.js" defer></script>
<script src="<?= BASE ?>/assets/js/calendar-converter.js" defer></script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>