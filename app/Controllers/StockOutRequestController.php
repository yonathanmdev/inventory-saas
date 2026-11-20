<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\EthiopianDate;
use App\Helpers\FlashHelper;
use App\Helpers\PermissionHelper;
use App\Services\StockOutRequestService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StockOutRequestController
{
    public function __construct(
        private StockOutRequestService $service,
    ) {
    }

    /**
     * ዝርዝር፦ ተራ ተጠቃሚ የራሱን ብቻ፣ view_all ያለው ሁሉንም ያያል።
     */
    public function index(Request $request, Response $response): Response
    {
        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            FlashHelper::error('Business account is not associated with a business.');
            return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
        }

        $result = $this->service->paginate(
            $businessId,
            $request->getQueryParams(),
            (int) ($_SESSION['user_id'] ?? 0),
            PermissionHelper::can('stock_out.view_all')
        );

        $requests   = $result['rows'];
        $pagination = $result['pagination'];
        $statuses   = StockOutRequestService::STATUSES;

        ob_start();
        require __DIR__ . '/../Views/stock-out/index.php';
        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            $response->getBody()->write(
                'Business account is not associated with a business.'
            );

            return $response->withStatus(403);
        }

        $error = $_SESSION['error'] ?? null;
        $old   = $_SESSION['old'] ?? [];

        unset($_SESSION['error'], $_SESSION['old']);

        $stockOptions = $this->service->availableOptions($businessId);
        $letterhead   = $this->service->letterhead($businessId);
        $today        = EthiopianDate::today();

        // ለአዲስ ጥያቄ ራስጌ እና ፊርማ እንዲደበቅ በነባሪነት false እናደርገዋለን
        $isApproved = false;

        ob_start();

        require __DIR__ . '/../Views/stock-out/create.php';

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

        $result = $this->service->create($data, $businessId, $userId);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            $_SESSION['old']   = $data;

            return $response->withHeader('Location', BASE . '/stock-out/requests/create')->withStatus(302);
        }

        FlashHelper::success($result['message']);

        return $response
            ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($result['uuid']))
            ->withStatus(302);
    }

   public function show(Request $request, Response $response, array $args): Response
{
    $businessId = AuthHelper::businessId();
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid request.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    $letter = $this->service->findByUuid($uuid, $businessId);

    if (!$letter) {
        FlashHelper::error('Request not found.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    // ባለቤትነት / ፈቃድ መፈተሽ
    if (!$this->service->canView(
        $letter,
        (int) ($_SESSION['user_id'] ?? 0),
        PermissionHelper::can('stock_out.view_all')
    )) {
        FlashHelper::error('ይህን ጥያቄ የማየት ፈቃድ የለዎትም።');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    $items      = $letter['items'];
    $letterhead = $this->service->letterhead($businessId);

    // ራስጌ፣ ፊርማ እና እግር የሚታዩት ሲጸድቅ ወይም ሲወጣ ብቻ
    $isApproved = in_array($letter['status'], ['approved', 'issued'], true);

    // ── አዲስ፦ ለአጽዳቂው የሚታይ የስቶክ ብዛት (ለ pending ጥያቄ ብቻ) ──
    // ብዛቱ የሚሰላው ተጠቃሚው ለማጽደቅ ፈቃድ ካለው ብቻ ነው፣ ለሌሎች አይላክም
    $availableByItem = [];
    if ($letter['status'] === 'pending' && PermissionHelper::can('stock_out.approve')) {
        $availableByItem = $this->service->availabilityByItem($items, $businessId);
    }

    // ── አዲስ፦ ለህትመት፦ 0 የተፈቀዱ መስመሮች አይታተሙም ──
    $printItems = $isApproved
        ? array_values(array_filter(
            $items,
            fn(array $i) => (int) ($i['approved_quantity'] ?? $i['quantity']) > 0
        ))
        : $items;

    ob_start();
    require __DIR__ . '/../Views/stock-out/show.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}

 public function approve(Request $request, Response $response, array $args): Response
{
    $body     = (array) $request->getParsedBody();
    $approved = is_array($body['approved'] ?? null) ? $body['approved'] : [];

    return $this->act(
        $response,
        $args,
        'stock_out.approve',
        fn(int $b, int $u, string $uuid) => $this->service->approve($uuid, $b, $u, $approved),
        '/stock-out/requests'   // redirect here when approval succeeds
    );
}

    public function reject(Request $request, Response $response, array $args): Response
    {
        $body   = (array) $request->getParsedBody();
        $reason = trim((string) ($body['reason'] ?? ''));

        return $this->act(
            $response,
            $args,
            'stock_out.approve',
            fn(int $businessId, int $userId, string $uuid) =>
                $this->service->reject($uuid, $businessId, $userId, $reason !== '' ? $reason : null)
        );
    }
/**
 * የጸደቀ ወይም የወጣ ጥያቄ ንባብ-ብቻ የህትመት ገጽ
 */
public function printView(Request $request, Response $response, array $args): Response
{
    $businessId = AuthHelper::businessId();
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid request.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    $letter = $this->service->findByUuid($uuid, $businessId);

    if (!$letter) {
        FlashHelper::error('Request not found.');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    $canViewAll = PermissionHelper::can('stock_out.view_all');

    // ባለቤት ወይም view_all ብቻ
    if (!$this->service->canView($letter, (int) ($_SESSION['user_id'] ?? 0), $canViewAll)) {
        FlashHelper::error('ይህን ጥያቄ የማየት ፈቃድ የለዎትም።');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    // ማተም የሚቻለው ሲጸድቅ ወይም ሲወጣ ብቻ
    if (!in_array($letter['status'], ['approved', 'issued'], true)) {
        FlashHelper::error('ማተም የሚቻለው የጸደቀ ጥያቄ ብቻ ነው።');
        return $response
            ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid))
            ->withStatus(302);
    }

    // 0 የተፈቀዱ መስመሮች አይታተሙም
    $printItems = array_values(array_filter(
        $letter['items'],
        fn(array $i) => (int) ($i['approved_quantity'] ?? $i['quantity']) > 0
    ));

    $letterhead    = $this->service->letterhead($businessId);
    $showCondition = $canViewAll;   // ሁኔታው የሚታተመው ብዛቱን ማየት ለሚችሉ ብቻ

    ob_start();
    require __DIR__ . '/../Views/stock-out/print.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}
 
    /**
     * ለ approve / reject / issue የጋራ ፍሰት፦
     * permission → service → flash → redirect
     */
    private function act(
    Response $response,
    array $args,
    string $permission,
    callable $run,
    ?string $successRedirect = null
): Response {
    $businessId = AuthHelper::businessId();
    $userId     = (int) ($_SESSION['user_id'] ?? 0);
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid request.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    if (!PermissionHelper::can($permission)) {
        FlashHelper::error('ይህን እርምጃ የመፈጸም ፈቃድ የለዎትም።');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    $result = $run($businessId, $userId, $uuid);

    if ($result['success']) {
        FlashHelper::success($result['message']);

        // Success: go to the given page if there is one, otherwise back to the request.
        $location = $successRedirect ?? '/stock-out/requests/' . urlencode($uuid);
    } else {
        FlashHelper::error($result['message']);

        // Failure: stay on the request page so the user sees the error in context.
        $location = '/stock-out/requests/' . urlencode($uuid);
    }

    return $response->withHeader('Location', $location)->withStatus(302);
}
    public function edit(Request $request, Response $response, array $args): Response
{
    $businessId = AuthHelper::businessId();
    $userId     = (int) ($_SESSION['user_id'] ?? 0);
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid request.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    $letter = $this->service->findByUuid($uuid, $businessId);

    if (!$letter) {
        FlashHelper::error('Request not found.');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    if (!$this->service->canEdit($letter, $userId)) {
        FlashHelper::error('ይህን ጥያቄ ማስተካከል አይችሉም።');
        return $response
            ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid))
            ->withStatus(302);
    }

    $error = $_SESSION['error'] ?? null;
    // ከስህተት በኋላ የተሞላውን ዳታ ቅድሚያ፣ አለበለዚያ ከዳታቤዝ
    $old   = $_SESSION['old'] ?? $this->service->toFormData($letter);
    unset($_SESSION['error'], $_SESSION['old']);

    $stockOptions = $this->service->availableOptions($businessId);
    $letterhead   = $this->service->letterhead($businessId);
    $today        = EthiopianDate::today();
    $isApproved   = false;

    // create.php እንደገና ጥቅም ላይ ይውላል
    $isEdit      = true;
    $formAction  = '/stock-out/requests/' . urlencode($uuid) . '/update';
    $cancelUrl   = '/stock-out/requests/' . urlencode($uuid);
    $submitLabel = 'ለውጡን አስቀምጥ';

    ob_start();
    require __DIR__ . '/../Views/stock-out/create.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}

public function update(Request $request, Response $response, array $args): Response
{
    $businessId = AuthHelper::businessId();
    $userId     = (int) ($_SESSION['user_id'] ?? 0);
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid request.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    if (!PermissionHelper::can('stock_out.update')) {
        FlashHelper::error('ይህን እርምጃ የመፈጸም ፈቃድ የለዎትም።');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    $data   = (array) $request->getParsedBody();
    $result = $this->service->update($uuid, $data, $businessId, $userId);

    if (!$result['success']) {
        $_SESSION['error'] = $result['message'];
        $_SESSION['old']   = $data + ['requester_name' => (string) ($_SESSION['full_name'] ?? '')];

        return $response
            ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid) . '/edit')
            ->withStatus(302);
    }

    FlashHelper::success($result['message']);

    return $response
        ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid))
        ->withStatus(302);
}
/**
     * Show Model 22 Issuance Form with available serial numbers (GET).
     */// Replace the two methods showIssueForm() and issue() at the end of StockOutRequestController
// with the versions below. Nothing else in the controller changes.
 
    /**
     * Show the Model 22 issue form with the available serial numbers (GET).
     */
    public function showIssueForm(Request $request, Response $response, array $args): Response
    {
        $businessId = AuthHelper::businessId();
        $uuid       = trim($args['uuid'] ?? '');
 
        if (!$businessId || $uuid === '') {
            FlashHelper::error('Invalid request.');
            return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
        }
 
        // getForIssuance() throws a DomainException when the user lacks permission.
        try {
            $stockOutRequest = $this->service->getForIssuance($uuid, $businessId);
        } catch (\DomainException $e) {
            FlashHelper::error($e->getMessage());
            return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
        }
 
        if (!$stockOutRequest) {
            FlashHelper::error('ጥያቄው አልተገኘም።');
            return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
        }
 
        if ($stockOutRequest['status'] !== 'approved') {
            FlashHelper::error('ጥያቄው ገና አልጸደቀም ወይም ቀድሞ ወጭ ተደርጓል።');
            return $response
                ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid))
                ->withStatus(302);
        }
 
        // Variables used by Views/stock-out/issue.php
        $request = $stockOutRequest;
        $items   = $stockOutRequest['items'] ?? [];
        $error   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
 
        ob_start();
        require __DIR__ . '/../Views/stock-out/issue.php';
        $html = ob_get_clean();
 
        $response->getBody()->write($html);
 
        return $response;
    }
 
    /**
     * Process the issue and save the selected serial numbers (POST).
     */
    public function issue(Request $request, Response $response, array $args): Response
    {
        $businessId = AuthHelper::businessId();
        $userId     = (int) ($_SESSION['user_id'] ?? 0);
        $uuid       = trim($args['uuid'] ?? '');
 
        if (!$businessId || $uuid === '') {
            FlashHelper::error('Invalid request.');
            return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
        }
 
        // [ item_id => [ 'item_id' => ..., 'serials' => [...] ] ]
        $body        = (array) $request->getParsedBody();
        $itemSerials = is_array($body['items'] ?? null) ? $body['items'] : [];
 
        $result = $this->service->issue($uuid, $businessId, $userId, $itemSerials);
 
        // Failure: go back to the form, which shows $_SESSION['flash_error'].
        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
 
            return $response
                ->withHeader('Location', BASE . '/stock-out/issue/' . urlencode($uuid))
                ->withStatus(302);
        }
 
        // Success: go to the request page, using the same flash helper as the other pages.
        FlashHelper::success($result['message']);
 
        return $response
            ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid))
            ->withStatus(302);
    }
public function model22(Request $request, Response $response, array $args): Response
{
    $businessId = AuthHelper::businessId();
    $uuid       = trim($args['uuid'] ?? '');

    if (!$businessId || $uuid === '') {
        FlashHelper::error('Invalid request.');
        return $response->withHeader('Location', BASE . '/dashboard')->withStatus(302);
    }

    $letter = $this->service->getForModel22($uuid, $businessId);

    if (!$letter) {
        FlashHelper::error('ሞዴል ፳፪ የሚገኘው ዕቃ ወጭ ለተደረገ ጥያቄ ብቻ ነው።');
        return $response
            ->withHeader('Location', BASE . '/stock-out/requests/' . urlencode($uuid))
            ->withStatus(302);
    }

    if (!$this->service->canView(
        $letter,
        (int) ($_SESSION['user_id'] ?? 0),
        PermissionHelper::can('stock_out.view_all')
    )) {
        FlashHelper::error('ይህን ጥያቄ የማየት ፈቃድ የለዎትም።');
        return $response->withHeader('Location', BASE . '/stock-out/requests')->withStatus(302);
    }

    $lines = $letter['lines'];

    ob_start();
    require __DIR__ . '/../Views/stock-out/model22.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);
    return $response;
}
}