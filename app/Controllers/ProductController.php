<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FlashHelper;
use App\Helpers\PermissionHelper;
use App\Services\ProductService;
use App\Services\CategoryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
    public function __construct(
        private ProductService $productService,
        private CategoryService $categoryService,
    ) {
    }


  public function index(
    Request $request,
    Response $response,
    array $args = []
): Response {

    $businessId = AuthHelper::businessId();

    if (!$businessId) {
        $response->getBody()->write(
            'Business account is not associated with a business.'
        );
        return $response->withStatus(403);
    }

    $query  = $request->getQueryParams();
    $status = (string) ($args['status'] ?? 'active');

    // Inactive / all lists require the delete (trash) permission
    if ($status !== 'active' && !PermissionHelper::can('products.delete')) {
        return $response
            ->withHeader('Location', '/products')
            ->withStatus(302);
    }

    $pagination = $this->productService->paginate(
        $businessId,
        (string) ($query['q'] ?? ''),
        (int) ($query['page'] ?? 1),
        (int) ($query['per_page'] ?? 10),
        $status
    );

    $products   = $pagination['items'];
    $categories = $this->categoryService->findAll($businessId);

    ob_start();
    require __DIR__ . '/../Views/products/index.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}
/**
 * Export products (current search) as CSV
 */
public function export(Request $request, Response $response): Response
{
    $businessId = AuthHelper::businessId();

    if (!$businessId) {
        return $response->withStatus(403);
    }

    $search = (string) ($request->getQueryParams()['q'] ?? '');
    $rows   = $this->productService->findAllForExport($businessId, $search);

    $stream = fopen('php://temp', 'r+');

    // UTF-8 BOM so Excel shows Amharic and other Unicode correctly
    fwrite($stream, "\xEF\xBB\xBF");

    fputcsv(
        $stream,
        ['ID', 'Name', 'Category', 'Description', 'Status', 'Created', 'Updated'],
        ',', '"', ''
    );

    foreach ($rows as $row) {
        fputcsv($stream, [
            $row['id'],
            $this->csvSafe($row['name']),
            $this->csvSafe($row['category_name'] ?? ''),
            $this->csvSafe($row['description'] ?? ''),
            (int) $row['is_active'] === 1 ? 'Active' : 'Inactive',
            $row['created_at'],
            $row['updated_at'],
        ], ',', '"', '');
    }

    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);

    $response->getBody()->write($csv);

    return $response
        ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->withHeader(
            'Content-Disposition',
            'attachment; filename="products-' . date('Y-m-d') . '.csv"'
        );
}
public function exportExcel(Request $request, Response $response): Response
{
    $businessId = AuthHelper::businessId();

    if (!$businessId) {
        return $response->withStatus(403);
    }

    $search  = (string) ($request->getQueryParams()['q'] ?? '');
    $content = $this->productService->toExcel($businessId, $search);

    $response->getBody()->write($content);

    return $response
        ->withHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        )
        ->withHeader(
            'Content-Disposition',
            'attachment; filename="products-' . date('Y-m-d') . '.xlsx"'
        )
        ->withHeader('Content-Length', (string) strlen($content));
}
/**
 * Prevent CSV/formula injection in Excel.
 */
private function csvSafe(?string $value): string
{
    $value = (string) $value;

    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }

    return $value;
}

    /**
     * Show create form
     */
    public function create(
        Request $request,
        Response $response
    ): Response {

        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            $response->getBody()->write(
                'Business account is not associated with a business.'
            );

            return $response->withStatus(403);
        }


        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);


        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);

        $categories = $this->categoryService
            ->findAll($businessId);

        ob_start();

        require __DIR__ . '/../Views/products/create.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Store product
     */
    public function store(
        Request $request,
        Response $response
    ): Response {

        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            FlashHelper::error(
                'Business account is not associated with a business.'
            );

            return $response
                ->withHeader('Location', '/products')
                ->withStatus(302);
        }


        $data = (array) $request->getParsedBody();


        $result = $this->productService->create(
            $data,
            $businessId
        );


        if (!$result['success']) {

            $_SESSION['error'] = $result['message'];
            $_SESSION['old'] = $data;

            return $response
                ->withHeader('Location', '/products/create')
                ->withStatus(302);
        }


        FlashHelper::success(
            $result['message']
        );


        return $response
            ->withHeader('Location', '/products')
            ->withStatus(302);
    }


    /**
     * Edit form
     */
    public function edit(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $businessId = AuthHelper::businessId();

        $uuid = trim($args['uuid'] ?? '');

        if (!$businessId || $uuid === '') {
            FlashHelper::error('Invalid product.');

            return $response
                ->withHeader('Location', '/products')
                ->withStatus(302);
        }


        $product = $this->productService->findByUuid(
            $uuid,
            $businessId
        );


        if (!$product) {
            FlashHelper::error('Product not found.');

            return $response
                ->withHeader('Location', '/products')
                ->withStatus(302);
        }


        $categories = $this->categoryService
            ->findAll($businessId);


        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);


        ob_start();

        require __DIR__ . '/../Views/products/edit.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Update
     */
    public function update(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $businessId = AuthHelper::businessId();

        $uuid = trim($args['uuid'] ?? '');

        if (!$businessId || $uuid === '') {

            FlashHelper::error('Invalid product.');

            return $response
                ->withHeader('Location', '/products')
                ->withStatus(302);
        }


        $data = (array) $request->getParsedBody();


        $result = $this->productService->update(
            $uuid,
            $data,
            $businessId
        );


        if (!$result['success']) {

            $_SESSION['error'] = $result['message'];

            return $response
                ->withHeader(
                    'Location',
                    '/products/' . urlencode($uuid) . '/edit'
                )
                ->withStatus(302);
        }


        FlashHelper::success(
            $result['message']
        );


        return $response
            ->withHeader('Location', '/products')
            ->withStatus(302);
    }


    /**
     * Deactivate
     */
    public function deactivate(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $businessId = AuthHelper::businessId();
        $uuid = trim($args['uuid'] ?? '');

        if (!$businessId || $uuid === '') {

            FlashHelper::error('Invalid product.');

            return $response
                ->withHeader('Location', '/products')
                ->withStatus(302);
        }


        $result = $this->productService->deactivate(
            $uuid,
            $businessId
        );


        if ($result['success']) {
            FlashHelper::success($result['message']);
        } else {
            FlashHelper::error($result['message']);
        }


        return $response
            ->withHeader('Location', '/products')
            ->withStatus(302);
    }


    /**
     * Activate
     */
    public function activate(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $businessId = AuthHelper::businessId();
        $uuid = trim($args['uuid'] ?? '');

        if (!$businessId || $uuid === '') {

            FlashHelper::error('Invalid product.');

            return $response
                ->withHeader('Location', '/products')
                ->withStatus(302);
        }


        $result = $this->productService->activate(
            $uuid,
            $businessId
        );


        if ($result['success']) {
            FlashHelper::success($result['message']);
        } else {
            FlashHelper::error($result['message']);
        }


        return $response
            ->withHeader('Location', '/products')
            ->withStatus(302);
    }
}