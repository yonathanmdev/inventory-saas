<?php

namespace App\Controllers;

use App\Services\UserRegistrationService;
use App\Services\BusinessRegistrationService;
use App\Services\RoleService;
use App\Helpers\AuthHelper;
use App\Helpers\FlashHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UserController
{
    public function __construct(
        private UserRegistrationService $userRegistrationService,
        private BusinessRegistrationService $businessRegistrationService,
        private RoleService $roleService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | SYSTEM ADMIN
    |--------------------------------------------------------------------------
    */

    /**
     * Display all registered users.
     */
    public function index(
        Request $request,
        Response $response
    ): Response {

        $users = $this->userRegistrationService
            ->findAllWithDetails();

        ob_start();

        require __DIR__ . '/../Views/users/index.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Display user registration form for System Admin.
     */
    public function create(
        Request $request,
        Response $response
    ): Response {

        /*
         * System Admin selects the business.
         *
         * Roles are loaded after a business is selected.
         */
        $businesses = $this->businessRegistrationService
            ->findAll();

        $roles = [];

        ob_start();

        require __DIR__ . '/../Views/users/create.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Return roles belonging to a selected business.
     *
     * GET:
     * /system-admin/users/businesses/{businessId}/roles
     */
    public function rolesByBusiness(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $businessId = (int) ($args['businessId'] ?? 0);

        if ($businessId <= 0) {

            $response->getBody()->write(
                json_encode([])
            );

            return $response
                ->withHeader(
                    'Content-Type',
                    'application/json'
                );
        }

        $roles = $this->roleService
            ->findByBusinessId($businessId);

        $response->getBody()->write(
            json_encode($roles)
        );

        return $response
            ->withHeader(
                'Content-Type',
                'application/json'
            );
    }


    /**
     * Store a new user created by System Admin.
     */
    public function store(
        Request $request,
        Response $response
    ): Response {

        $data = $request->getParsedBody();

        $result = $this->userRegistrationService
            ->register($data);

        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    '/system-admin/users/create'
                )
                ->withStatus(302);
        }

        FlashHelper::success(
            $result['message']
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/users'
            )
            ->withStatus(302);
    }


    /*
    |--------------------------------------------------------------------------
    | BUSINESS USER MANAGEMENT
    |--------------------------------------------------------------------------
    |
    | These methods use the SAME:
    |
    | - users table
    | - UserRegistrationService
    | - UserModel
    | - users/index.php
    | - users/create.php
    |
    | The difference is that the business_id comes from the
    | authenticated session.
    |--------------------------------------------------------------------------
    */


    /**
     * Display users belonging ONLY to the current business.
     *
     * GET /users
     */
    public function businessIndex(
        Request $request,
        Response $response
    ): Response {

        $businessId = AuthHelper::businessId();

        /*
         * A business user must belong to a business.
         */
        if (!$businessId) {

            $response->getBody()->write(
                'Business account is not associated with a business.'
            );

            return $response->withStatus(403);
        }

        /*
         * IMPORTANT:
         *
         * Only users belonging to the authenticated
         * user's business are retrieved.
         */
        $users = $this->userRegistrationService
            ->findAllByBusinessId($businessId);

        /*
         * Reuse the SAME users view.
         */
        ob_start();

        require __DIR__ . '/../Views/users/index.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Display user registration form for a business user.
     *
     * GET /users/create
     */
    public function businessCreate(
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

        /*
         * Business users must NOT select a business.
         *
         * The business is determined from the session.
         */
        $businesses = [];

        /*
         * Only load roles belonging to the current business.
         */
        $roles = $this->roleService
            ->findByBusinessId($businessId);

        /*
         * Reuse the SAME create view.
         */
        ob_start();

        require __DIR__ . '/../Views/users/create.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Store a new user created by a business user.
     *
     * POST /users
     */
    public function businessStore(
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

        $data = $request->getParsedBody();

        /*
         * NEVER trust business_id submitted by the browser.
         *
         * Always overwrite it with the authenticated
         * user's business ID.
         */
        $data['business_id'] = $businessId;

        /*
         * UserRegistrationService performs:
         *
         * - validation
         * - business validation
         * - role validation
         * - duplicate username validation
         * - duplicate email validation
         * - password hashing
         * - user creation
         */
        $result = $this->userRegistrationService
            ->register($data);


        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    '/users/create'
                )
                ->withStatus(302);
        }


        FlashHelper::success(
            $result['message']
        );


        return $response
            ->withHeader(
                'Location',
                '/users'
            )
            ->withStatus(302);
    }


    public function show(
    Request $request,
    Response $response,
    array $args
): Response {
    $uuid = trim($args['uuid'] ?? '');

    if ($uuid === '') {
        FlashHelper::error('Invalid user.');

        $redirectUrl = AuthHelper::isSystemAdmin()
            ? '/system-admin/users'
            : '/users';

        return $response
            ->withHeader('Location', $redirectUrl)
            ->withStatus(302);
    }

    /*
     * System Admin can view users from all businesses.
     */
    if (AuthHelper::isSystemAdmin()) {

        $user = $this->userRegistrationService
            ->findByUuidWithDetails($uuid);

    } else {

        /*
         * Business users can only view users
         * belonging to their own business.
         */
        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            $response->getBody()->write(
                'Business account is not associated with a business.'
            );

            return $response->withStatus(403);
        }

        $user = $this->userRegistrationService
            ->findByUuidWithDetailsForBusiness(
                $uuid,
                $businessId
            );
    }

    if (!$user) {
        FlashHelper::error('User not found.');

        $redirectUrl = AuthHelper::isSystemAdmin()
            ? '/system-admin/users'
            : '/users';

        return $response
            ->withHeader('Location', $redirectUrl)
            ->withStatus(302);
    }

    ob_start();

    require __DIR__ . '/../Views/users/show.php';

    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}
public function deactivate(
    Request $request,
    Response $response,
    array $args
): Response {
    $uuid = trim($args['uuid'] ?? '');

    if ($uuid === '') {
        FlashHelper::error('Invalid user.');
        return $response
            ->withHeader('Location', '/users')
            ->withStatus(302);
    }

    $businessId = AuthHelper::isSystemAdmin()
        ? null
        : AuthHelper::businessId();

    $result = $this->userRegistrationService
        ->deactivateByUuid($uuid, $businessId);

    if ($result['success']) {
        FlashHelper::success($result['message']);
    } else {
        FlashHelper::error($result['message']);
    }

    $redirectUrl = AuthHelper::isSystemAdmin()
        ? '/system-admin/users'
        : '/users';

    return $response
        ->withHeader('Location', $redirectUrl)
        ->withStatus(302);
}
public function activate(
    Request $request,
    Response $response,
    array $args
): Response {
    $uuid = trim($args['uuid'] ?? '');

    if ($uuid === '') {
        FlashHelper::error('Invalid user.');

        $redirectUrl = AuthHelper::isSystemAdmin()
            ? '/system-admin/users'
            : '/users';

        return $response
            ->withHeader('Location', $redirectUrl)
            ->withStatus(302);
    }

    $businessId = AuthHelper::isSystemAdmin()
        ? null
        : AuthHelper::businessId();

    if (!AuthHelper::isSystemAdmin() && !$businessId) {
        FlashHelper::error(
            'Business account is not associated with a business.'
        );

        return $response
            ->withHeader('Location', '/users')
            ->withStatus(302);
    }

    $result = $this->userRegistrationService->activateByUuid(
        $uuid,
        $businessId
    );

    if ($result['success']) {
        FlashHelper::success($result['message']);
    } else {
        FlashHelper::error($result['message']);
    }

    $redirectUrl = AuthHelper::isSystemAdmin()
        ? '/system-admin/users'
        : '/users';

    return $response
        ->withHeader('Location', $redirectUrl)
        ->withStatus(302);
}
}