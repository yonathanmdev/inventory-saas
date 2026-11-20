<?php

namespace App\Services;

use App\Helpers\EthiopianDate;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\UnitModel;
use App\Models\StockInModel;
use PDOException;
use Ramsey\Uuid\Uuid;

class StockInService
{
    private const MAX_ROWS = 100;
    public const CONDITIONS = ['new' => 'አዲስ', 'used' => 'ያገለገሉ', 'consumable' => 'አላቂ'];
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];
    /** 1 = purchase, 2 = gift */
    public const SOURCES = [1 => 'ግዥ', 2 => 'ስጦታ'];
    private const MAX_QUANTITY = 100000;
    public function __construct(
        private StockInModel $stockInModel,
        private ProductModel $productModel,
        private UserModel $userModel,
        private UnitModel $unitModel,
        private AuditLogger $auditLogger
    ) {
    }
    public function unitOptions(int $businessId): array
{
    return $this->unitModel->findActiveOptions($businessId);
}

    public function nextReceiptNo(int $businessId): string
    {
        return (string) $this->stockInModel->nextReceiptNo($businessId);
    }
 public function create(array $data, int $businessId, int $userId): array
    {
        // trims, collapses repeated spaces (so "HP  15" and "HP 15" are the same model), cuts to length
        $text = static function (array $row, string $key, int $max): string {
            $value = $row[$key] ?? '';

            if (!is_scalar($value)) {
                return '';
            }

            return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''), 0, $max);
        };

        /* ---------- header: field names are the ones the form posts ---------- */

        $receiptNo = $text($data, 'receipt_no', 30);
        $supplier  = $text($data, 'supplier_name', 150);

        if ($receiptNo === '' || !preg_match('#^[A-Za-z0-9\-/]+$#', $receiptNo)) {
            return $this->fail('Receipt number is required (letters, digits, - and / only).');
        }
        if ($supplier === '') {
            return $this->fail('"From" (supplier) is required.');
        }

        $condition = (string) ($data['item_condition'] ?? '');
        if (!array_key_exists($condition, self::CONDITIONS)) {
            return $this->fail('Select the item condition.');
        }

        $source = (int) ($data['source'] ?? 0);
        if (!array_key_exists($source, self::SOURCES)) {
            return $this->fail('Select the source (ግዥ / ስጦታ).');
        }

        // Ethiopian text "dd/mm/yyyy" -> Gregorian date, converted on the server
        $ec           = EthiopianDate::parse((string) ($data['ec_date'] ?? ''));
        $receivedDate = $ec ? EthiopianDate::toGregorian(...$ec) : null;

        if ($receivedDate === null) {
            return $this->fail('Invalid date. Use day/month/year, for example 13/01/2019.');
        }

        if ($this->stockInModel->receiptNoExists($businessId, $receiptNo)) {
            return $this->fail('This receipt number is already used.');
        }

        /* ---------- rows ---------- */

        $rawItems = $data['items'] ?? [];

        if (!is_array($rawItems) || count($rawItems) > self::MAX_ROWS) {
            return $this->fail('Invalid item rows.');
        }

        $lines = [];

        foreach (array_values($rawItems) as $i => $row) {
            $n = $i + 1;

            if (!is_array($row)) {
                return $this->fail("Row {$n} is invalid.");
            }

            $serials = $row['serials'] ?? [];

            if (!is_array($serials) || count($serials) > self::MAX_QUANTITY) {
                return $this->fail("Row {$n}: invalid serial numbers.");
            }

            $serials = array_values(array_filter(
                array_map(
                    static fn ($s) => is_scalar($s)
                        ? mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $s) ?? ''), 0, 100)
                        : '',
                    $serials
                ),
                static fn ($s) => $s !== ''
            ));

            $productUuid = $text($row, 'product', 36);
            $unitUuid    = $text($row, 'unit', 36);
            $qtyRaw      = $text($row, 'qty', 10);
            $priceRaw    = $text($row, 'price', 20);
            $model       = $text($row, 'model', 100);       // stock_in_items.model is VARCHAR(100)
            $pageFrom    = $text($row, 'page_from', 20);
            $pageTo      = $text($row, 'page_to', 20);

            // A row only counts when it has a product, unit, quantity, price or serials.
            // Stray text in model / page boxes does not make an empty row a line.
            if ($productUuid . $unitUuid . $qtyRaw . $priceRaw === '') {
                continue;
            }

            if ($productUuid === '') return $this->fail("Row {$n}: product is required.");
            if ($unitUuid === '')    return $this->fail("Row {$n}: unit is required.");
            if ($qtyRaw === '')      return $this->fail("Row {$n}: quantity is required.");
            if ($priceRaw === '')    return $this->fail("Row {$n}: unit price is required.");

            if (!ctype_digit($qtyRaw) || (int) $qtyRaw < 1 || (int) $qtyRaw > self::MAX_QUANTITY) {
                return $this->fail("Row {$n}: quantity must be between 1 and " . self::MAX_QUANTITY . '.');
            }

            // strict parser: digits and at most 2 decimals, 8 digits before the point (no overflow)
            $unitCents = $this->toCents($priceRaw);

            if ($unitCents === null) {
                return $this->fail("Row {$n}: invalid unit price.");
            }

            if (
                $pageFrom !== '' && $pageTo !== ''
                && is_numeric($pageFrom) && is_numeric($pageTo)
                && (float) $pageFrom > (float) $pageTo
            ) {
                return $this->fail("Row {$n}: page-from cannot be greater than page-to.");
            }

            $lines[] = [
                'row'        => $n,                          // the number the user sees, even after skipped rows
                'uuid'       => $productUuid,
                'unit_uuid'  => $unitUuid,
                'qty'        => (int) $qtyRaw,
                'unit_cents' => $unitCents,
                'model'      => $model,                      // '' when there is no model, never NULL
                'serials'    => $serials,
                'page_from'  => $pageFrom !== '' ? $pageFrom : null,
                'page_to'    => $pageTo !== '' ? $pageTo : null,
            ];
        }

        if (!$lines) {
            return $this->fail('At least one item is required.');
        }

        /* ---------- resolve products and units (this business only) ---------- */

        $productMap = $this->productModel->findDetailsByUuids(
            array_values(array_unique(array_column($lines, 'uuid'))),
            $businessId
        );

        $unitMap = $this->unitModel->findIdsByUuids(
            array_values(array_unique(array_column($lines, 'unit_uuid'))),
            $businessId
        );

        $items      = [];
        $seen       = [];     // "product|serial" => row, to catch duplicates across ALL rows
        $totalCents = 0;

        foreach ($lines as $line) {
            $n = $line['row'];

            if (!isset($productMap[$line['uuid']])) {
                return $this->fail("Row {$n}: product not found or inactive.");
            }
            if (!isset($unitMap[$line['unit_uuid']])) {
                return $this->fail("Row {$n}: unit not found or inactive.");
            }

            $product      = $productMap[$line['uuid']];
            $isSerialized = (bool) $product['is_serialized'];       // the product decides, not the browser

            if ($isSerialized) {
                if (count($line['serials']) !== $line['qty']) {
                    return $this->fail("Row {$n}: this product is serialized, so exactly {$line['qty']} serial number(s) are required.");
                }

                foreach ($line['serials'] as $serial) {
                    $k = $line['uuid'] . '|' . mb_strtolower($serial);

                    if (isset($seen[$k])) {
                        return $this->fail(
                            $seen[$k] === $n
                                ? "Row {$n}: serial number {$serial} is entered twice."
                                : "Serial number {$serial} is entered in rows {$seen[$k]} and {$n}."
                        );
                    }

                    $seen[$k] = $n;
                }
            } elseif ($line['serials'] !== []) {
                return $this->fail("Row {$n}: this product is not serialized and cannot have serial numbers.");
            }

            $lineCents   = $line['qty'] * $line['unit_cents'];
            $totalCents += $lineCents;

            $items[] = [
                'product_id'    => (int) $product['id'],
                'unit_id'       => (int) $unitMap[$line['unit_uuid']],
                'quantity'      => $line['qty'],
                'unit_cents'    => $line['unit_cents'],
                'total_cents'   => $lineCents,
                'model'         => $line['model'],
                'track_serials' => $isSerialized,
                'serials'       => $line['serials'],
                'page_from'     => $line['page_from'],
                'page_to'       => $line['page_to'],
            ];
        }

        /* ---------- save: one model call = one transaction (header, lines, serials, ledger) ---------- */

        $uuid = Uuid::uuid7()->toString();

        $header = [
            'uuid'                 => $uuid,
            'business_id'          => $businessId,
            'receipt_no'           => $receiptNo,
            'expenditure_item_no'  => $this->nullable($text($data, 'expenditure_item_no', 50)),
            'incoming_register_no' => $this->nullable($text($data, 'incoming_register_no', 50)),
            'classification'       => $this->nullable($text($data, 'classification', 100)),
            'store_no'             => $this->nullable($text($data, 'store_no', 50)),
            'shelf_no'             => $this->nullable($text($data, 'shelf_no', 50)),
            'department'           => $this->nullable($text($data, 'department', 150)),
            'supplier_name'        => $supplier,
            'received_date'        => $receivedDate,
            'item_condition'       => $condition,
            'source'               => $source,
            'total_amount'         => $this->money($totalCents),
            'created_by'           => $userId,
        ];

        try {
            $receiptId = $this->stockInModel->createWithItems($header, $items);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'receipt')) {
                return $this->fail('This receipt number is already used.');
            }

            error_log('StockInService::create failed: ' . $e->getMessage());

            return $this->fail('Unable to create stock-in. Please try again.');
        } catch (\Throwable $e) {
            error_log('StockInService::create failed: ' . $e->getMessage());

            return $this->fail('Unable to create stock-in. Please try again.');
        }

        $this->auditLogger->success([
            'action'      => 'CREATE',
            'module'      => 'Stock In',
            'table_name'  => 'stock_ins',
            'record_id'   => $receiptId,
            'record_uuid' => $uuid,
            'business_id' => $businessId,
            'user_id'     => $userId,
            'description' => "Stock-in receipt {$receiptNo} registered",
            'new_values'  => [
                'receipt_no'    => $receiptNo,
                'received_date' => $receivedDate,
                'supplier_name' => $supplier,
                'source'        => $source,
                'item_count'    => count($items),
                'total_amount'  => $header['total_amount'],
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Stock-in created successfully.',
            'id'      => $receiptId,
            'uuid'    => $uuid,
        ];
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }

    private function nullable(string $v): ?string
    {
        return $v !== '' ? $v : null;
    }

    /** "1,250.5" → 125050. Blank = 0. Null if invalid. */
    private function toCents(string $value): ?int
    {
        $value = str_replace(',', '', $value);

        if ($value === '') {
            return 0;
        }
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/', $value, $m)) {
            return null;
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
public function paginate(
    int $businessId,
    string $search,
    string $condition,
    int $page,
    int $perPage
): array {
    $search    = mb_substr(trim($search), 0, 100);
    $condition = array_key_exists($condition, self::CONDITIONS) ? $condition : '';
    $perPage   = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

    $total      = $this->stockInModel->countByBusinessId($businessId, $search, $condition);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page       = min(max(1, $page), $totalPages);
    $offset     = ($page - 1) * $perPage;

    $items = $this->stockInModel->findPageByBusinessId(
        $businessId, $search, $condition, $perPage, $offset
    );

    return [
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'search'      => $search,
        'condition'   => $condition,
        'from'        => $total === 0 ? 0 : $offset + 1,
        'to'          => $offset + count($items),
    ];
}

/** Receipt with its 'items' array, or null (also when it belongs to another business). */
public function findByUuid(string $uuid, int $businessId): ?array
{
    if (!Uuid::isValid($uuid)) {
        return null;
    }

    $receipt = $this->stockInModel->findByUuid($uuid, $businessId);

    if (!$receipt) {
        return null;
    }

    $receipt['items'] = $this->stockInModel->findItems((int) $receipt['id'], $businessId);
    // Get the user who created the receipt
    $receipt['created_by_user'] = null;

    if (!empty($receipt['created_by'])) {
        $receipt['created_by_user'] = $this->userModel->findById(
            (int) $receipt['created_by'],
            $businessId
        );
    }
    return $receipt;
}

public function update(string $uuid, array $data, int $businessId, int $userId): array
    {
        // Find existing receipt first
        $existing = $this->findByUuid($uuid, $businessId);
        if (!$existing) {
            return $this->fail('Receipt not found.');
        }

        $text = static function (array $row, string $key, int $max): string {
            $value = $row[$key] ?? '';
            if (!is_scalar($value)) {
                return '';
            }
            return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''), 0, $max);
        };

        $receiptNo = $text($data, 'receipt_no', 30);
        $supplier  = $text($data, 'supplier_name', 150);

        if ($receiptNo === '' || !preg_match('#^[A-Za-z0-9\-/]+$#', $receiptNo)) {
            return $this->fail('Receipt number is required (letters, digits, - and / only).');
        }
        if ($supplier === '') {
            return $this->fail('"From" (supplier) is required.');
        }

        $condition = (string) ($data['item_condition'] ?? '');
        if (!array_key_exists($condition, self::CONDITIONS)) {
            return $this->fail('Select the item condition.');
        }

        $source = (int) ($data['source'] ?? 0);
        if (!array_key_exists($source, self::SOURCES)) {
            return $this->fail('Select the source.');
        }

        $ec = EthiopianDate::parse((string) ($data['ec_date'] ?? ''));
        $receivedDate = $ec ? EthiopianDate::toGregorian(...$ec) : null;

        if ($receivedDate === null) {
            return $this->fail('Invalid date. Use day/month/year.');
        }

        // Check receipt number uniqueness excluding current receipt
        if ($this->stockInModel->receiptNoExistsExcept($businessId, $receiptNo, (int) $existing['id'])) {
            return $this->fail('This receipt number is already used.');
        }

        $rawItems = $data['items'] ?? [];
        if (!is_array($rawItems) || count($rawItems) > self::MAX_ROWS) {
            return $this->fail('Invalid item rows.');
        }

        $lines = [];
        foreach (array_values($rawItems) as $i => $row) {
            $n = $i + 1;
            if (!is_array($row)) {
                return $this->fail("Row {$n} is invalid.");
            }

            $serials = $row['serials'] ?? [];
            if (!is_array($serials) || count($serials) > self::MAX_QUANTITY) {
                return $this->fail("Row {$n}: invalid serial numbers.");
            }

            $serials = array_values(array_filter(
                array_map(
                    static fn ($s) => is_scalar($s) ? mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $s) ?? ''), 0, 100) : '',
                    $serials
                ),
                static fn ($s) => $s !== ''
            ));

            $productUuid = $text($row, 'product', 36);
            $unitUuid    = $text($row, 'unit', 36);
            $qtyRaw      = $text($row, 'qty', 10);
            $priceRaw    = $text($row, 'price', 20);
            $model       = $text($row, 'model', 100);
            $pageFrom    = $text($row, 'page_from', 20);
            $pageTo      = $text($row, 'page_to', 20);
            $itemId      = isset($row['id']) ? (int) $row['id'] : null;

            if ($productUuid . $unitUuid . $qtyRaw . $priceRaw === '' && $serials === []) {
                continue;
            }

            if ($productUuid === '') return $this->fail("Row {$n}: product is required.");
            if ($unitUuid === '')    return $this->fail("Row {$n}: unit is required.");
            if ($qtyRaw === '')      return $this->fail("Row {$n}: quantity is required.");
            if ($priceRaw === '')    return $this->fail("Row {$n}: unit price is required.");

            if (!ctype_digit($qtyRaw) || (int) $qtyRaw < 1 || (int) $qtyRaw > self::MAX_QUANTITY) {
                return $this->fail("Row {$n}: quantity must be between 1 and " . self::MAX_QUANTITY . '.');
            }

            $unitCents = $this->toCents($priceRaw);
            if ($unitCents === null) {
                return $this->fail("Row {$n}: invalid unit price.");
            }

            $lines[] = [
                'id'         => $itemId,
                'row'        => $n,
                'uuid'       => $productUuid,
                'unit_uuid'  => $unitUuid,
                'qty'        => (int) $qtyRaw,
                'unit_cents' => $unitCents,
                'model'      => $model,
                'serials'    => $serials,
                'page_from'  => $pageFrom !== '' ? $pageFrom : null,
                'page_to'    => $pageTo !== '' ? $pageTo : null,
            ];
        }

        if (!$lines) {
            return $this->fail('At least one item is required.');
        }

        $productMap = $this->productModel->findDetailsByUuids(
            array_values(array_unique(array_column($lines, 'uuid'))),
            $businessId
        );

        $unitMap = $this->unitModel->findIdsByUuids(
            array_values(array_unique(array_column($lines, 'unit_uuid'))),
            $businessId
        );

        $items      = [];
        $seen       = [];
        $totalCents = 0;

        foreach ($lines as $line) {
            $n = $line['row'];

            if (!isset($productMap[$line['uuid']])) {
                return $this->fail("Row {$n}: product not found or inactive.");
            }
            if (!isset($unitMap[$line['unit_uuid']])) {
                return $this->fail("Row {$n}: unit not found or inactive.");
            }

            $product      = $productMap[$line['uuid']];
            $isSerialized = (bool) $product['is_serialized'];

            if ($isSerialized) {
                if (count($line['serials']) !== $line['qty']) {
                    return $this->fail("Row {$n}: this product is serialized, so exactly {$line['qty']} serial number(s) are required.");
                }

                foreach ($line['serials'] as $serial) {
                    $k = $line['uuid'] . '|' . mb_strtolower($serial);
                    if (isset($seen[$k])) {
                        return $this->fail("Row {$n}: serial number {$serial} is entered twice.");
                    }
                    $seen[$k] = $n;
                }
            } elseif ($line['serials'] !== []) {
                return $this->fail("Row {$n}: this product is not serialized and cannot have serial numbers.");
            }

            $lineCents   = $line['qty'] * $line['unit_cents'];
            $totalCents += $lineCents;

            $items[] = [
                'id'            => $line['id'],
                'product_id'    => (int) $product['id'],
                'unit'          => (int) $unitMap[$line['unit_uuid']],
                'quantity'      => $line['qty'],
                'unit_cents'    => $line['unit_cents'],
                'total_cents'   => $lineCents,
                'model'         => $line['model'],
                'track_serials' => $isSerialized,
                'serials'       => $line['serials'],
                'page_from'     => $line['page_from'],
                'page_to'       => $line['page_to'],
            ];
        }

        $header = [
            'id'                   => (int) $existing['id'],
            'uuid'                 => $uuid,
            'business_id'          => $businessId,
            'receipt_no'           => $receiptNo,
            'expenditure_item_no'  => $this->nullable($text($data, 'expenditure_item_no', 50)),
            'incoming_register_no' => $this->nullable($text($data, 'incoming_register_no', 50)),
            'classification'       => $this->nullable($text($data, 'classification', 100)),
            'store_no'             => $this->nullable($text($data, 'store_no', 50)),
            'shelf_no'             => $this->nullable($text($data, 'shelf_no', 50)),
            'department'           => $this->nullable($text($data, 'department', 150)),
            'supplier_name'        => $supplier,
            'received_date'        => $receivedDate,
            'item_condition'       => $condition,
            'source'               => $source,
            'total_amount'         => $this->money($totalCents),
        ];

        try {
            $this->stockInModel->updateWithItems($header, $items);
        } catch (\Throwable $e) {
            error_log('StockInService::update failed: ' . $e->getMessage());
            return $this->fail('Unable to update stock-in. Please try again.');
        }

        return [
            'success' => true,
            'message' => 'Stock-in updated successfully.',
            'id'      => (int) $existing['id'],
            'uuid'    => $uuid,
        ];
    }

    }