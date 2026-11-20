<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\EthiopianDate;
use App\Helpers\FlashHelper;
use App\Services\ProductService;
use App\Services\StockInService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StockInController
{
    public function __construct(
        private StockInService $stockInService,
        private ProductService $productService,
    ) {
    }
public function index(Request $request, Response $response): Response
{
    $businessId = AuthHelper::businessId();

    if (!$businessId) {
        $response->getBody()->write('Business account is not associated with a business.');
        return $response->withStatus(403);
    }

    $query = $request->getQueryParams();

    $pagination = $this->stockInService->paginate(
        $businessId,
        (string) ($query['q'] ?? ''),
        (string) ($query['condition'] ?? ''),
        (int) ($query['page'] ?? 1),
        (int) ($query['per_page'] ?? 10)
    );

    $receipts   = $pagination['items'];
    $conditions = StockInService::CONDITIONS;
    $sources = StockInService::SOURCES;

    ob_start();
    require __DIR__ . '/../Views/stock-in/index.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}

public function show(Request $request, Response $response, array $args): Response
{
    $businessId = AuthHelper::businessId();
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid receipt.');
        return $response->withHeader('Location', BASE . '/stock-in')->withStatus(302);
    }

    $receipt = $this->stockInService->findByUuid($uuid, $businessId);

    if (!$receipt) {
        FlashHelper::error('Receipt not found.');
        return $response->withHeader('Location', BASE . '/stock-in')->withStatus(302);
    }

    $items      = $receipt['items'];
    $conditions = StockInService::CONDITIONS;
    $sources = StockInService::SOURCES;

    ob_start();
    require __DIR__ . '/../Views/stock-in/show.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}
    public function create(Request $request, Response $response): Response
    {
        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            $response->getBody()->write('Business account is not associated with a business.');
            return $response->withStatus(403);
        }

        $error = $_SESSION['error'] ?? null;
        $old   = $_SESSION['old'] ?? [];
        unset($_SESSION['error'], $_SESSION['old']);

        $products      = $this->productService->findAllByBusinessId($businessId);
        $units = $this->stockInService->unitOptions($businessId);
        $nextReceiptNo = $this->stockInService->nextReceiptNo($businessId);
        $today         = EthiopianDate::today();
        $sources = StockInService::SOURCES;

        ob_start();
        require __DIR__ . '/../Views/stock-in/create.php';
        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            FlashHelper::error('Business account is not associated with a business.');
            return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
        }

        $data   = (array) $request->getParsedBody();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        $result = $this->stockInService->create($data, $businessId, $userId);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            $_SESSION['old']   = $data;

            return $response->withHeader('Location', BASE . '/stock-in/create')->withStatus(302);
        }

        FlashHelper::success($result['message']);

        // Change this to the list or detail page once you build it
        return $response->withHeader('Location', BASE . '/stock-in/create')->withStatus(302);
    }
public function edit(Request $request, Response $response, array $args): Response
    {
        $businessId = AuthHelper::businessId();
        $uuid       = trim($args['uuid'] ?? '');

        if (!$businessId || $uuid === '') {
            FlashHelper::error('Invalid receipt.');
            return $response->withHeader('Location', BASE . '/stock-in')->withStatus(302);
        }

        // Fetch receipt with its items, serials, and unit/product UUID mappings
        $receipt = $this->stockInService->findByUuid($uuid, $businessId);

        if (!$receipt) {
            FlashHelper::error('Receipt not found.');
            return $response->withHeader('Location', BASE . '/stock-in')->withStatus(302);
        }

        $error      = $_SESSION['error'] ?? null;
        $sessionOld = $_SESSION['old'] ?? [];
        unset($_SESSION['error'], $_SESSION['old']);

        // Convert received_date (Gregorian) to Ethiopian date string for the form if ec_date is missing
        if (!empty($receipt['received_date']) && empty($receipt['ec_date'])) {
            $parts = explode('-', $receipt['received_date']);
            if (count($parts) === 3) {
                $ec = EthiopianDate::fromGregorian((int)$parts[0], (int)$parts[1], (int)$parts[2]);
                $receipt['ec_date'] = sprintf('%02d/%02d/%d', $ec[2], $ec[1], $ec[0]);
            }
        }

        // Format item prices from cents to decimal currency for the form
        if (!empty($receipt['items'])) {
            foreach ($receipt['items'] as &$item) {
                if (isset($item['unit_price'])) {
                    $item['price'] = number_format($item['unit_price'] / 100, 2, '.', '');
                }
            }
            unset($item);
        }

        // Use session old data if validation failed, otherwise use database $receipt
        $old = !empty($sessionOld) ? $sessionOld : $receipt;

        $products   = $this->productService->findAllByBusinessId($businessId);
        $units      = $this->stockInService->unitOptions($businessId);
        $conditions = StockInService::CONDITIONS;
        $sources    = StockInService::SOURCES;
        $today      = EthiopianDate::today();

        ob_start();
        require __DIR__ . '/../Views/stock-in/create.php'; // Using the shared form view
        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $businessId = AuthHelper::businessId();
        $uuid       = trim($args['uuid'] ?? '');

        if (!$businessId || $uuid === '') {
            FlashHelper::error('Invalid receipt.');
            return $response->withHeader('Location', BASE . '/stock-in')->withStatus(302);
        }

        $data   = (array) $request->getParsedBody();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        $result = $this->stockInService->update($uuid, $data, $businessId, $userId);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            $_SESSION['old']   = $data;

            return $response->withHeader('Location', BASE . '/stock-in/' . $uuid . '/edit')->withStatus(302);
        }

        FlashHelper::success($result['message']);

        return $response->withHeader('Location', BASE . '/stock-in/' . $uuid)->withStatus(302);
    }

    
}