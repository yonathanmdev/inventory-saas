<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Helpers\EthiopianDate;
use App\Helpers\PermissionHelper;
use App\Models\ProductModel;
use App\Models\StockLevelModel;
use App\Models\StockOutRequestModel;
use App\Models\UnitModel;
use Ramsey\Uuid\Uuid;

class StockOutRequestService
{
    public const DEFAULT_SUBJECT = 'የዕቃ ወጪ እንዲፈቀድልን ስለመጠየቅ';
    public const FORM_FOOTER = 'PLEASE MAKE SURE THAT THIS IS THE CORRECT ISSUE BEFORE USE';

    private const MAX_ROWS = 100;
    private const LOGO_ROUTE = '/businesses/%s/logo';
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const STATUSES = [
        'pending'  => 'በመጠባበቅ ላይ',
        'approved' => 'ጸድቋል',
        'issued'   => 'ወጭ ተደርጓል',
        'rejected' => 'ውድቅ ተደርጓል',
    ];

    /**
     * Keys must match StockLevelModel::CONDITIONS.
     */
    public const CONDITIONS = [
        'new'        => 'አዲስ',
        'used'       => 'ያገለገለ',
        'consumable' => 'አላቂ',
    ];

    public function __construct(
        private StockOutRequestModel $model,
        private ProductModel $productModel,
        private UnitModel $unitModel,
        private StockLevelModel $stockLevels,
        private AuditLogger $auditLogger
    ) {}

    public function letterhead(int $businessId): array
    {
        $branding = $this->model->findBranding($businessId);
        $uuid = (string) ($branding['uuid'] ?? '');
        $hasLogo = trim((string) ($branding['logo_path'] ?? '')) !== '';

        $logoUrl = ($hasLogo && Uuid::isValid($uuid))
            ? sprintf(self::LOGO_ROUTE, rawurlencode($uuid))
            : '';

        return [
            'logo_url'   => $logoUrl,
            'heading_am' => trim((string) ($_SESSION['business_name_amharic'] ?? '')),
            'heading_en' => trim((string) ($_SESSION['business_name'] ?? '')),
            'footer'     => self::FORM_FOOTER,
        ];
    }

    public function availableOptions(int $businessId): array
    {
        return $this->stockLevels->availableOptions($businessId);
    }

    public function create(array $data, int $businessId, ?int $userId): array
    {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);

        $requester = trim((string) ($_SESSION['full_name'] ?? ''));
        if ($requester === '' || $userId === null) {
            return $this->fail('የተጠቃሚው መለያ (Session ID) አልተገኘም። እባክዎ እንደገና ይግቡ።');
        }

        // create() እና update() ውስጥ
$p = $this->prepare($data, $businessId, PermissionHelper::can('stock_out.view_all'));
        if (!$p['success']) {
            return $p;
        }

        $uuid = Uuid::uuid7()->toString();

        $saved = $this->model->create(
            [
                'uuid'           => $uuid,
                'business_id'    => $businessId,
                'request_date'   => $p['date'],
                'requester_name' => $requester,
                'dept'           => $p['dept'],
                'created_by'     => $userId,
            ],
            $p['items']
        );

        // request_no ከሞዴሉ ካልተመለሰ ጊዜያዊ ማመሳከሪያ
        $ref = $saved['request_no'] ?? strtoupper(substr($uuid, -8));

        $this->auditLogger->success([
            'action'      => 'CREATE',
            'module'      => 'Stock-Out Request',
            'table_name'  => 'stock_out_requests',
            'record_id'   => $saved['id'],
            'record_uuid' => $uuid,
            'business_id' => $businessId,
            'description' => "የዕቃ መጠየቂያ ቁጥር {$ref} ተመዝግቧል",
            'new_values'  => [
                'requester_name' => $requester,
                'item_count'     => count($p['items']),
                'status'         => 'pending',
            ],
        ]);

        return [
            'success' => true,
            'message' => "ጥያቄ ቁጥር {$ref} በተሳካ ሁኔታ ተመዝግቧል። እባክዎ ለፊርማ አትሙት።",
            'uuid'    => $uuid,
        ];
    }

    /**
     * ለ create እና update የጋራ ማረጋገጫ።
     *
     * የ <option> ቁልፍ ቅርጽ፦ product_uuid:unit_uuid:model_key:item_condition
     *
     * @return array{success:bool,message?:string,date?:string,items?:array}
     */
    private function prepare(array $data, int $businessId,  bool $enforceStock): array
    {
        $text = function (array $source, string $key, int $max): string {
            $value = (string) ($source[$key] ?? '');
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            return mb_substr($value, 0, $max);
        };

        $dept = $text($data, 'dept', 100);
        if ($dept === '') {
            return $this->fail('የተጠቃሚው ክፍል ስም ያስፈልጋል።');
        }

        $ec   = EthiopianDate::parse((string) ($data['ec_date'] ?? ''));
        $date = $ec ? EthiopianDate::toGregorian(...$ec) : null;
        if ($date === null) {
            return $this->fail('ልክ ያልሆነ ቀን። እባክዎ በቀን/ወር/ዓመት ቅርጸት ያስገቡ (ለምሳሌ፦ 13/05/2018)');
        }

        $rawItems = $data['items'] ?? [];
        if (!is_array($rawItems) || count($rawItems) > self::MAX_ROWS) {
            return $this->fail('የዕቃዎቹ ዝርዝር ትክክል አይደለም።');
        }

        $lines = [];
        foreach (array_values($rawItems) as $i => $row) {
            $rowNumber = $i + 1;

            if (!is_array($row)) {
                return $this->fail("ረድፍ {$rowNumber} ትክክል አይደለም።");
            }

            $itemKey = $text($row, 'item', 200);
            $qtyRaw  = $text($row, 'qty', 10);
            $model   = $text($row, 'model', 100);
            $remark  = $text($row, 'remark', 200);

            if ($itemKey === '' && $qtyRaw === '' && $model === '' && $remark === '') {
                continue;
            }

            $parts = explode(':', $itemKey, 4);
            if (count($parts) < 4) {
                return $this->fail("ረድፍ {$rowNumber} ላይ ትክክለኛ ዕቃ ይምረጡ።");
            }

            $productUuid = trim($parts[0]);
            $unitUuid    = trim($parts[1]);
            $modelKey    = trim($parts[2]);
            $condition   = trim($parts[3]);

            if (!Uuid::isValid($productUuid) || !Uuid::isValid($unitUuid)) {
                return $this->fail("ረድፍ {$rowNumber} ላይ የተመረጠው ዕቃ ወይም መለኪያ ትክክል አይደለም።");
            }

            // ሁኔታው ከተመረጠው ዕቃ ቁልፍ ነው የሚመጣው፣ ከተጠቃሚ ግብዓት አይደለም
            if (!isset(self::CONDITIONS[$condition])) {
                return $this->fail("ረድፍ {$rowNumber} ላይ የዕቃው ሁኔታ ትክክል አይደለም።");
            }

            // የተላከው ሞዴል ከተመረጠው ዕቃ ሞዴል ጋር መመሳሰሉን ማረጋገጥ
            if (StockLevelModel::modelKey($model) !== $modelKey) {
                return $this->fail("ረድፍ {$rowNumber} ላይ ሞዴሉ ከተመረጠው ዕቃ ጋር አይመሳሰልም። እባክዎ ዕቃውን እንደገና ይምረጡ።");
            }

            if (!ctype_digit($qtyRaw) || (int) $qtyRaw < 1 || (int) $qtyRaw > 100000) {
                return $this->fail("ረድፍ {$rowNumber} ላይ የሚገባው ብዛት ከ 1 እስከ 100,000 መሆን አለበት።");
            }

            $lines[] = [
                'row'            => $rowNumber,
                'product_uuid'   => $productUuid,
                'unit_uuid'      => $unitUuid,
                'item_condition' => $condition,
                'quantity'       => (int) $qtyRaw,
                'model'          => $model,
                'remark'         => $remark,
            ];
        }

        if (!$lines) {
            return $this->fail('ቢያንስ አንድ ዕቃ መምረጥ ይኖርብዎታል።');
        }

        $productMap = $this->productModel->findIdsByUuids(
            array_values(array_unique(array_column($lines, 'product_uuid'))),
            $businessId
        );
        $unitMap = $this->unitModel->findIdsByUuids(
            array_values(array_unique(array_column($lines, 'unit_uuid'))),
            $businessId
        );

        // product + unit + model + condition ተመሳሳይ የሆኑ መስመሮችን አንድ ላይ ደምር
        $aggregated = [];
        foreach ($lines as $line) {
            if (!isset($productMap[$line['product_uuid']]) || !isset($unitMap[$line['unit_uuid']])) {
                return $this->fail("ረድፍ {$line['row']}: የተመረጠው ዕቃ በመረጃ ቋቱ ውስጥ አልተገኘም።");
            }

            $productId = (int) $productMap[$line['product_uuid']];
            $unitId    = (int) $unitMap[$line['unit_uuid']];
            $key       = $productId . ':' . $unitId . ':'
                       . StockLevelModel::normalizeModel($line['model']) . ':'
                       . $line['item_condition'];

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'product_id'     => $productId,
                    'unit_id'        => $unitId,
                    'model'          => $line['model'],
                    'item_condition' => $line['item_condition'],
                    'quantity'       => 0,
                ];
            }
            $aggregated[$key]['quantity'] += $line['quantity'];
        }

        foreach ($aggregated as $item) {
            $available = $this->stockLevels->availableFor(
                $businessId,
                (int) $item['product_id'],
                (int) $item['unit_id'],
                $item['model'],
                $item['item_condition']
            );

             // ብዛቱን የሚያይ ሰው፦ ሙሉ ፍተሻ
    if ($enforceStock && (int) $item['quantity'] > $available) {
        $label = self::CONDITIONS[$item['item_condition']];
        return $this->fail("የጠየቁት ዕቃ ብዛት ({$label}) ከቀሪው ስቶክ በላይ ነው።");
    }

    // ሌሎች፦ ዕቃው በስቶክ መኖሩን ብቻ (ብዛት አይገለጥም)
    if ($available < 1) {
        return $this->fail('የተመረጠው ዕቃ አሁን በስቶክ የለም። እባክዎ ዝርዝሩን አድሰው ይሞክሩ።');
    }
        }

        $items = [];
        foreach ($lines as $line) {
            $items[] = [
                'product_id'     => (int) $productMap[$line['product_uuid']],
                'unit_id'        => (int) $unitMap[$line['unit_uuid']],
                'quantity'       => (int) $line['quantity'],
                'model'          => $line['model'] !== '' ? $line['model'] : null,
                'item_condition' => $line['item_condition'],
                'remark'         => $line['remark'] !== '' ? $line['remark'] : null,
            ];
        }

        return [
            'success' => true,
            'date'    => $date,
            'dept'    => $dept,
            'items'   => $items,
        ];
    }

    /**
     * ማስተካከል የሚቻለው ጥያቄው pending ሲሆን፣ እና ጠያቂው ራሱ
     * ወይም stock_out.view_all ያለው ሰው ሲሆን ነው።
     */
    public function canEdit(array $request, int $userId): bool
    {
        return $request['status'] === 'pending'
            && (
                (int) $request['created_by'] === $userId
                || PermissionHelper::can('stock_out.view_all')
            );
    }

    public function update(string $uuid, array $data, int $businessId, int $userId): array
    {
        $request = $this->findByUuid($uuid, $businessId);
        if (!$request) {
            return $this->fail('ጥያቄው አልተገኘም።');
        }

        if (!$this->canEdit($request, $userId)) {
            return $this->fail('ይህን ጥያቄ ማስተካከል አይችሉም። በመጠባበቅ ላይ ያለ ጥያቄ ብቻ ይስተካከላል።');
        }

        $p = $this->prepare(
    $data,
    $businessId,
    PermissionHelper::can('stock_out.view_all')
);
        if (!$p['success']) {
            return $p;
        }

        try {
            $this->model->update(
                (int) $request['id'],
                $businessId,
                $p['dept'], // <-- Pass the department here
                $p['items']
            );
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        }

        $this->auditLogger->success([
            'action'      => 'UPDATE',
            'module'      => 'Stock-Out Request',
            'table_name'  => 'stock_out_requests',
            'record_id'   => (int) $request['id'],
            'record_uuid' => $uuid,
            'business_id' => $businessId,
            'description' => 'የዕቃ መጠየቂያ ተስተካክሏል',
            'old_values'  => [
                'item_count' => count($request['items']),
            ],
            'new_values'  => [
                'item_count' => count($p['items']),
                'edited_by'  => $userId,
            ],
        ]);

        return ['success' => true, 'message' => 'ጥያቄው በተሳካ ሁኔታ ተስተካክሏል።', 'uuid' => $uuid];
    }
public function getNotificationSummary(int $businessId): array
{
    $canViewAll = PermissionHelper::can('stock_out.view_all');

    if ($canViewAll) {
        $count = $this->model->getPendingCount($businessId);
    }

    return [
        'can_view_all' => $canViewAll,
        'count' => $count,
    ];
}
    /**
     * ለ edit ቅጽ ቀድሞ የተሞላ ዳታ (create.php ከሚጠብቀው ቅርጽ ጋር)።
     */
    public function toFormData(array $request): array
    {
        $ecDate = '';
        if (!empty($request['request_date'])) {
            $p = EthiopianDate::fromGregorian($request['request_date']);
            if (count($p) === 3) {
                $ecDate = sprintf('%02d/%02d/%d', $p[2], $p[1], $p[0]);
            }
        }

        $items = [];
        foreach ($request['items'] as $item) {
            $items[] = [
                // ከ <option value> ጋር አንድ ቅርጽ፦ product:unit:model_key:condition
                'item'   => $item['product_uuid'] . ':' . $item['unit_uuid'] . ':'
                            . StockLevelModel::modelKey((string) $item['model']) . ':'
                            . $item['item_condition'],
                'qty'    => $item['quantity'],
                'model'  => $item['model'],
                'remark' => $item['remark'],
            ];
        }

        return [
            'ec_date'        => $ecDate,
            'dept'      => $request['dept'] ?? '',
            'requester_name' => $request['requester_name'],
            'items'          => $items,
        ];
    }

    public function findByUuid(string $uuid, int $businessId): ?array
    {
        if (!Uuid::isValid($uuid)) {
            return null;
        }

        $request = $this->model->findByUuid($uuid, $businessId);
        if (!$request) {
            return null;
        }

        $request['items'] = $this->model->findItems((int) $request['id'], $businessId);

        return $request;
    }

    public function paginate(
        int $businessId,
        array $query,
        int $userId,
        bool $canViewAll
    ): array {
        $perPage = (int) ($query['per_page'] ?? 25);
        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        $status = (string) ($query['status'] ?? '');
        if (!isset(self::STATUSES[$status])) {
            $status = '';
        }

        return $this->model->paginate(
            $businessId,
            [
                'search'   => mb_substr(trim((string) ($query['q'] ?? '')), 0, 100),
                'status'   => $status,
                'page'     => (int) ($query['page'] ?? 1),
                'per_page' => $perPage,
            ],
            $canViewAll ? null : $userId   // ተራ ተጠቃሚ የራሱን ብቻ
        );
    }

    /**
     * ተጠቃሚው ይህን ጥያቄ ማየት ይችላል?
     */
    public function canView(array $request, int $userId, bool $canViewAll): bool
    {
        return $canViewAll || (int) $request['created_by'] === $userId;
    }

 public function approve(string $uuid, int $businessId, int $userId, array $approved = []): array
{
    $clean = [];
    foreach ($approved as $itemId => $qty) {
        if (!is_scalar($qty) || !ctype_digit((string) $itemId) || !ctype_digit(trim((string) $qty))) {
            return $this->fail('የሚፈቀደው ብዛት ቁጥር መሆን አለበት።');
        }
        $clean[(int) $itemId] = (int) trim((string) $qty);
    }

    return $this->transition($uuid, $businessId, 'approved', 'APPROVE',
        fn(int $id) => $this->model->approve($id, $businessId, $userId, $clean),
        'ጥያቄው ጸድቋል።'
    );
}

/**
 * ለ approve ቅጽ፦ ለእያንዳንዱ መስመር ያለው ብዛት (item id => available)
 */
public function availabilityByItem(array $items, int $businessId): array
{
    $map = [];
    foreach ($items as $item) {
        $map[(int) $item['id']] = $this->stockLevels->availableFor(
            $businessId,
            (int) $item['product_id'],
            (int) $item['unit_id'],
            (string) ($item['model'] ?? ''),
            (string) $item['item_condition']
        );
    }
    return $map;
}

    public function reject(string $uuid, int $businessId, int $userId, ?string $reason): array
    {
        return $this->transition(
            $uuid,
            $businessId,
            'rejected',
            'REJECT',
            fn(int $id) => $this->model->reject($id, $businessId, $userId, $reason),
            'ጥያቄው ውድቅ ተደርጓል።'
        );
    }
/**
     * Get request details along with available serial numbers strictly for Model 22 Issuance.
     */
public function getForIssuance(string $uuid, int $businessId): ?array
{
    if (!Uuid::isValid($uuid)) {
        return null;
    }

    // አማራጭ ፐርሚሽን ማረጋገጫ
   if (!PermissionHelper::can('stock_out.issue')) {
    throw new \DomainException('ይህን ገጽ ለመክፈት ፈቃድ (Permission) የለዎትም።');
}

    return $this->model->findForIssuance($uuid, $businessId);
}
    /**
     * Issue approved request and handle serial numbers selection.
     * $itemSerials format: [ item_id => [ 'serials' => ['SERIAL-001', 'SERIAL-002'] ] ]
     */
   public function issue(string $uuid, int $businessId, int $userId, array $itemInput = []): array
{
    if (!PermissionHelper::can('stock_out.issue')) {
        return $this->fail('ይህን እርምጃ የመፈጸም ፈቃድ የለዎትም።');
    }

    $cleanSerials = [];   // item_id => [serial_no, ...]
    $cleanBatches = [];   // item_id => [stock_in_item_id => qty]

    foreach ($itemInput as $itemId => $data) {
        if (!ctype_digit((string) $itemId) || !is_array($data)) {
            continue;
        }
        $itemId = (int) $itemId;

        if (isset($data['serials']) && is_array($data['serials'])) {
            $serials = array_values(array_filter(
                array_map(fn($s) => trim((string) $s), $data['serials']),
                fn($s) => $s !== ''
            ));
            if (count($serials) !== count(array_unique($serials))) {
                return $this->fail('ተመሳሳይ ሰሪያል ከአንድ ጊዜ በላይ ተመርጧል።');
            }
            $cleanSerials[$itemId] = $serials;
        }

        if (isset($data['batches']) && is_array($data['batches'])) {
            foreach ($data['batches'] as $stockInItemId => $qty) {
                $qty = trim((string) $qty);
                if ($qty === '' || $qty === '0') {
                    continue;
                }
                if (!ctype_digit((string) $stockInItemId) || !ctype_digit($qty)) {
                    return $this->fail('የተመረጠው ብዛት ቁጥር መሆን አለበት።');
                }
                $cleanBatches[$itemId][(int) $stockInItemId] = (int) $qty;
            }
        }
    }

    return $this->transition(
        $uuid,
        $businessId,
        'issued',
        'ISSUE',
        fn(int $id) => $this->model->issue($id, $businessId, $userId, $cleanSerials, $cleanBatches),
        'ዕቃዎቹ ከመጋዘን ውጭ ተደርገዋል (ሞዴል ፳፪ ተዘጋጅቷል)።'
    );
}

    private function transition(
        string $uuid,
        int $businessId,
        string $newStatus,
        string $action,
        callable $run,
        string $successMessage
    ): array {
        if (!Uuid::isValid($uuid)) {
            return $this->fail('ጥያቄው አልተገኘም።');
        }

        $request = $this->model->findByUuid($uuid, $businessId);
        if (!$request) {
            return $this->fail('ጥያቄው አልተገኘም።');
        }

        try {
            $run((int) $request['id']);
        } catch (InsufficientStockException $e) {
            $label = self::CONDITIONS[$e->condition] ?? $e->condition;

            return $this->fail(
                "ስቶክ አይበቃም ({$label})፦ የተጠየቀ {$e->requested}፣ ያለ {$e->available}።"
            );
        } catch (\DomainException | \InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        }

        $this->auditLogger->success([
            'action'      => $action,
            'module'      => 'Stock-Out Request',
            'table_name'  => 'stock_out_requests',
            'record_id'   => (int) $request['id'],
            'record_uuid' => $uuid,
            'business_id' => $businessId,
            'description' => "የዕቃ መጠየቂያ ሁኔታ ወደ {$newStatus} ተቀይሯል",
            'new_values'  => ['status' => $newStatus],
        ]);

        return ['success' => true, 'message' => $successMessage];
    }
    /** Issued request with its allocation lines; null if missing or not issued yet. */
public function getForModel22(string $uuid, int $businessId): ?array
{
    $request = $this->findByUuid($uuid, $businessId);
    if (!$request || $request['status'] !== 'issued') {
        return null;
    }

    $request['lines'] = $this->model->findIssuedLines((int) $request['id'], $businessId);
    return $request;
}
    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}