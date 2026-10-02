<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Helpers\AuthHelper;
use Ramsey\Uuid\Uuid;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductService
{
        private const PER_PAGE_OPTIONS = [10, 25, 50, 100];
    private const STATUSES         = ['active', 'inactive', 'all'];

    public function __construct(
        private ProductModel $productModel,
        private AuditLogger $auditLogger,
        private CategoryModel $categoryModel
    ) {
    }

private function resolveCategoryId(mixed $raw, int $businessId): int|false|null
{
    $uuid = trim((string) $raw);

    if ($uuid === '') {
        return null;                       // no category chosen
    }

    $category = $this->categoryModel
        ->findByUuidAndBusinessId($uuid, $businessId);

    return $category ? (int) $category['id'] : false;   // false = invalid
}
    /**
     * Get all products for current business
     */
    public function findAllByBusinessId(int $businessId): array
    {
        return $this->productModel
            ->findAllByBusinessId($businessId);
    }
public function paginate(
    int $businessId,
    string $search,
    int $page,
    int $perPage,
    string $status = 'active'
): array {

    $status  = in_array($status, self::STATUSES, true) ? $status : 'active';
    $search  = mb_substr(trim($search), 0, 100);
    $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

    $total      = $this->productModel->countByBusinessId($businessId, $search, $status);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page       = min(max(1, $page), $totalPages);   // clamp out-of-range pages
    $offset     = ($page - 1) * $perPage;

    $items = $this->productModel->findPageByBusinessId(
        $businessId,
        $search,
        $perPage,
        $offset,
        $status
    );

    return [
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'search'      => $search,
        'status'      => $status,
        'from'        => $total === 0 ? 0 : $offset + 1,
        'to'          => $offset + count($items),
    ];
}
public function findAllForExport(int $businessId, string $search = '', string $status = 'active'): array
{
    $status = in_array($status, self::STATUSES, true) ? $status : 'active';

    return $this->productModel->findAllForExport(
        $businessId,
        mb_substr(trim($search), 0, 100),
        $status
    );
}

    /**
     * Find product by UUID
     */
    public function findByUuid(
        string $uuid,
        int $businessId
    ): ?array {
        return $this->productModel
            ->findByUuid($uuid, $businessId);
    }
 /**
     * Build the Excel file and return its binary content.
     */
    public function toExcel(int $businessId, string $search = ''): string
    {
        $rows = $this->productModel->findAllForExport($businessId, $search);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        $headers = ['ID', 'Name', 'Category', 'Description', 'Status', 'Created', 'Updated'];

        foreach ($headers as $i => $title) {
            $sheet->setCellValueExplicit([$i + 1, 1], $title, DataType::TYPE_STRING);
        }

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6EFD'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $r = 2;

        foreach ($rows as $row) {
            $sheet->setCellValueExplicit("A{$r}", (int) $row['id'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$r}", (string) $row['name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$r}", (string) ($row['category_name'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$r}", (string) ($row['description'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(
                "E{$r}",
                (int) $row['is_active'] === 1 ? 'Active' : 'Inactive',
                DataType::TYPE_STRING
            );
            $sheet->setCellValueExplicit("F{$r}", (string) $row['created_at'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$r}", (string) $row['updated_at'], DataType::TYPE_STRING);
            $r++;
        }

        $sheet->freezePane('A2');

        if ($r > 2) {
            $sheet->setAutoFilter('A1:G' . ($r - 1));
        }

        foreach (['A', 'B', 'C', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getColumnDimension('D')->setWidth(50);
        $sheet->getStyle('D2:D' . max(2, $r - 1))
            ->getAlignment()->setWrapText(true);

        $tmp = tempnam(sys_get_temp_dir(), 'products_');

        try {
            (new Xlsx($spreadsheet))->save($tmp);
            return (string) file_get_contents($tmp);
        } finally {
            @unlink($tmp);
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Create product
     */
    public function create(
        array $data,
        int $businessId
    ): array {

        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Product name is required.'
            ];
        }


        /*
         * Category tenant validation
         */

$categoryId = $this->resolveCategoryId($data['category_id'] ?? '', $businessId);

if ($categoryId === false) {
    return ['success' => false, 'message' => 'Invalid category selected.'];
}


        /*
         * UUID
         */

        $uuid = Uuid::uuid7()->toString();


        /*
         * Create
         */

        $productId = $this->productModel->create([
            'uuid'        => $uuid,
            'business_id' => $businessId,
            'category_id' => $categoryId,
            'name'        => $name,
            'description' => $description !== ''
                ? $description
                : null,
        ]);


        /*
         * Audit
         */

        $this->auditLogger->success([
            'action'      => 'CREATE',
            'module'      => 'Products',
            'table_name'  => 'products',
            'record_id'   => $productId,
            'record_uuid' => $uuid,
            'business_id' => $businessId,

            'description' =>
                'Product created successfully',

            'new_values' => [
                'uuid'        => $uuid,
                'business_id' => $businessId,
                'category_id' => $categoryId,
                'sku'         => $sku,
                'name'        => $name,
                'description' => $description,
                'is_active'   => 1
            ]
        ]);


        return [
            'success'  => true,
            'message'  => 'Product created successfully.',
            'uuid'     => $uuid,
            'product_id' => $productId
        ];
    }


    /**
     * Update product
     */
    public function update(
        string $uuid,
        array $data,
        int $businessId
    ): array {

        $product = $this->productModel->findByUuid(
            $uuid,
            $businessId
        );

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }


        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Product name is required.'
            ];
        }



        /*
         * Category validation
         */
$categoryId = $this->resolveCategoryId($data['category_id'] ?? '', $businessId);

if ($categoryId === false) {
    return ['success' => false, 'message' => 'Invalid category selected.'];
}


        $updated = $this->productModel->update(
            $uuid,
            $businessId,
            [
                'category_id' => $categoryId,
                'sku'         => $sku,
                'name'        => $name,
                'description' => $description !== ''
                    ? $description
                    : null
            ]
        );


        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Unable to update product.'
            ];
        }


        $this->auditLogger->success([
            'action'      => 'UPDATE',
            'module'      => 'Products',
            'table_name'  => 'products',
            'record_id'   => (int) $product['id'],
            'record_uuid' => $uuid,
            'business_id' => $businessId,

            'description' =>
                'Product updated successfully',

            'old_values' => [
                'category_id' => $product['category_id'],
                'sku'         => $product['sku'],
                'name'        => $product['name'],
                'description' => $product['description']
            ],

            'new_values' => [
                'category_id' => $categoryId,
                'sku'         => $sku,
                'name'        => $name,
                'description' => $description
            ]
        ]);


        return [
            'success' => true,
            'message' => 'Product updated successfully.'
        ];
    }


    /**
     * Deactivate
     */
    public function deactivate(
        string $uuid,
        int $businessId
    ): array {

        $product = $this->productModel->findByUuid(
            $uuid,
            $businessId
        );

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }

        if ((int) $product['is_active'] !== 1) {
            return [
                'success' => false,
                'message' => 'Product is already inactive.'
            ];
        }

        $this->productModel->deactivate(
            $uuid,
            $businessId
        );


        $this->auditLogger->success([
            'action'      => 'DEACTIVATE',
            'module'      => 'Products',
            'table_name'  => 'products',
            'record_id'   => (int) $product['id'],
            'record_uuid' => $uuid,
            'business_id' => $businessId,

            'description' =>
                'Product deactivated',

            'old_values' => [
                'is_active' => 1
            ],

            'new_values' => [
                'is_active' => 0
            ]
        ]);


        return [
            'success' => true,
            'message' => 'Product deactivated successfully.'
        ];
    }


    /**
     * Activate
     */
    public function activate(
        string $uuid,
        int $businessId
    ): array {

        $product = $this->productModel->findByUuid(
            $uuid,
            $businessId
        );

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }

        if ((int) $product['is_active'] === 1) {
            return [
                'success' => false,
                'message' => 'Product is already active.'
            ];
        }

        $this->productModel->activate(
            $uuid,
            $businessId
        );


        $this->auditLogger->success([
            'role_name_at_time' => $_SESSION['role_name'] ?? null,
            'action'      => 'ACTIVATE',
            'module'      => 'Products',
            'table_name'  => 'products',
            'record_id'   => (int) $product['id'],
            'record_uuid' => $uuid,
            'business_id' => $businessId,

            'description' =>
                'Product activated',

            'old_values' => [
                'is_active' => 0
            ],

            'new_values' => [
                'is_active' => 1
            ]
        ]);


        return [
            'success' => true,
            'message' => 'Product activated successfully.'
        ];
    }
}