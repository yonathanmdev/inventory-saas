<?php
use App\Helpers\EthiopianDate;

$pageTitle = 'ዕቃ ማውጣት (ሞዴል ፳፪)';
$activeNav = 'stock-out';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$request = is_array($request ?? null) ? $request : [];
$items   = is_array($items ?? null) ? $items : [];

$toEc = function (?string $v): string {
    if (empty($v)) return '';
    $p = EthiopianDate::fromGregorian(substr($v, 0, 10));
    return count($p) === 3 ? sprintf('%02d/%02d/%d', $p[2], $p[1], $p[0]) : '';
};

ob_start();
?>

<div class="mb-4">
    <h1 class="page-title">ዕቃ ማውጣት</h1>
    <p class="page-subtitle mb-0">
        ጥያቄ #<?= $e($request['request_no'] ?? $request['id'] ?? '') ?>
        · <?= $e($request['requester_name'] ?? '') ?>
        · <?= $e($request['dept'] ?? '') ?>
    </p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $e($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE ?>/stock-out/issue/<?= $e($request['uuid'] ?? '') ?>" id="issueForm" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">

<?php foreach ($items as $item):
    $itemId           = (int) $item['id'];
    $qty              = (int) ($item['approved_quantity'] ?? $item['quantity'] ?? 0);
    if ($qty <= 0) continue;                       // approved as 0: nothing to issue
    $availableSerials = $item['available_serials'] ?? [];
    $batches          = $item['batches'] ?? [];
    $isSerialized     = (int) ($item['is_serialized'] ?? 0) === 1;
?>
    <div class="card shadow-sm mb-3" data-item-card
         data-qty="<?= $qty ?>" data-serialized="<?= $isSerialized ? '1' : '0' ?>">

        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2">
            <div>
                <strong><?= $e($item['product_name'] ?? '') ?></strong>
                <?php if (!empty($item['model'])): ?>
                    <span class="text-muted">· <?= $e($item['model']) ?></span>
                <?php endif; ?>
            </div>
            <div class="small">
                የተፈቀደ <strong><?= $qty ?> <?= $e($item['unit_name'] ?? '') ?></strong>
                · ተመርጧል <strong><span class="js-count">0</span> / <?= $qty ?></strong>
                · <strong><span class="js-total">0.00</span> ብር</strong>
            </div>
        </div>

        <div class="card-body">
            <input type="hidden" name="items[<?= $itemId ?>][item_id]" value="<?= $itemId ?>">

            <?php if ($isSerialized): ?>

                <?php if (count($availableSerials) < $qty): ?>
                    <div class="alert alert-danger py-2">
                        ሰሪያል በስቶክ አይበቃም (ያለ <?= count($availableSerials) ?>፣ የሚያስፈልግ <?= $qty ?>)
                    </div>
                <?php endif; ?>

                <div class="row g-2">
                <?php for ($s = 0; $s < $qty; $s++): ?>
                    <div class="col-md-6">
                        <select name="items[<?= $itemId ?>][serials][]"
                                class="form-select form-select-sm serial-select" required>
                            <option value="">— ሰሪያል #<?= $s + 1 ?> ምረጥ —</option>
                            <?php foreach ($availableSerials as $ser): ?>
                                <option value="<?= $e($ser['serial_no']) ?>"
                                        data-price="<?= $e($ser['unit_price']) ?>">
                                    <?= $e($ser['serial_no']) ?>
                                    — <?= number_format((float) $ser['unit_price'], 2) ?> ብር
                                    (<?= $e($ser['receipt_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endfor; ?>
                </div>

            <?php elseif ($batches): ?>

                <div class="small text-muted mb-2">
                    ከየትኛው የግዥ ደረሰኝ ይውጣ? ጠቅላላው <?= $qty ?> መሆን አለበት።
                    አንድ ደረሰኝ ብቻውን ካሟላ ሌሎቹ ይዘጋሉ።
                </div>

                <?php foreach ($batches as $b): ?>
                    <div class="batch-row d-flex align-items-center gap-2 border rounded p-2 mb-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                ደረሰኝ <?= $e($b['receipt_no']) ?> · <?= $e($toEc($b['received_date'])) ?> ዓ/ም
                            </div>
                            <div class="small text-muted">
                                ዋጋ <strong><?= number_format((float) $b['unit_price'], 2) ?> ብር</strong>
                                · ቀሪ <?= (int) $b['remaining'] ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary batch-fill">ሁሉንም</button>
                        <input type="number" min="0" step="1"
                               max="<?= (int) $b['remaining'] ?>"
                               class="form-control form-control-sm batch-qty" style="width:90px"
                               data-remaining="<?= (int) $b['remaining'] ?>"
                               data-price="<?= $e($b['unit_price']) ?>"
                               name="items[<?= $itemId ?>][batches][<?= (int) $b['stock_in_item_id'] ?>]">
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="alert alert-danger mb-0">በግዥ መዝገብ ውስጥ ቀሪ ስቶክ የለም</div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="<?= BASE ?>/stock-out/requests/<?= $e($request['uuid'] ?? '') ?>" class="btn btn-outline-secondary">ሰርዝ</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> ዕቃዎቹን ውጭ አድርግ
        </button>
    </div>
</form>

<script src="<?= BASE ?>/assets/js/issue-prices.js" defer></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';