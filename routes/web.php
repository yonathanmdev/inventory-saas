<?php

use App\Controllers\AuthController;
use App\Controllers\BusinessController;
use App\Controllers\DashboardController;
use App\Controllers\RoleController;
use App\Controllers\UserController;
use App\Controllers\CategoryController;
use App\Controllers\ProductController;

use App\Helpers\AuthHelper;

use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\SystemAdminMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Services\AuditLogger;

return function ($app) {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Home
    |--------------------------------------------------------------------------
    */

    $app->get(
        '/',
        function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ) {

            $target = AuthHelper::check()
                ? (
                    AuthHelper::isSystemAdmin()
                        ? '/system-admin'
                        : '/dashboard'
                )
                : '/login';

            return $response
                ->withHeader('Location', $target)
                ->withStatus(302);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    $app->get(
        '/login',
        [AuthController::class, 'showLogin']
    );


    $app->post(
        '/login',
        [AuthController::class, 'login']
    );


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    $app->post(
        '/logout',
        [AuthController::class, 'logout']
    );


    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED BUSINESS ROUTES
    |--------------------------------------------------------------------------
    |
    | These routes are available to normal authenticated business users.
    |
    | IMPORTANT:
    | PermissionMiddleware controls WHAT the user can do.
    | Models/controllers must still enforce business_id isolation.
    |
    |--------------------------------------------------------------------------
    */


    $app->group('', function ($group) {


        /*
        |--------------------------------------------------------------------------
        | Business Dashboard
        |--------------------------------------------------------------------------
        */

        $group->get(
            '/dashboard',
            [DashboardController::class, 'index']
        )->add(
            new PermissionMiddleware('dashboard.view')
        );


        /*
        |--------------------------------------------------------------------------
        | Business Users
        |--------------------------------------------------------------------------
        |
        | Same users table and same UserController as System Admin.
        | The controller/model must scope these operations to the
        | authenticated user's business_id.
        |
        |--------------------------------------------------------------------------
        */

$group->get(
    '/businesses/{uuid}/logo',
    [BusinessController::class, 'logo']
)->add(
    new PermissionMiddleware('businesses.update')
);
        /*
         * User list
         */
        $group->get(
            '/users',
            [UserController::class, 'businessIndex']
        )->add(
            new PermissionMiddleware('users.view')
        );


        /*
         * Register user
         */
        $group->get(
            '/users/create',
            [UserController::class, 'businessCreate']
        )->add(
            new PermissionMiddleware('users.create')
        );


        /*
         * Store user
         */
        $group->post(
            '/users',
            [UserController::class, 'businessStore']
        )->add(
            new PermissionMiddleware('users.create')
        );
        $group->get(
    '/users/{uuid}',
    [UserController::class, 'show']
)->add(
    new PermissionMiddleware('users.view')
);
$group->post('/users/{uuid}/deactivate', [UserController::class, 'deactivate'])
    ->add(new PermissionMiddleware('users.deactivate'));

$group->post('/users/{uuid}/activate', [UserController::class, 'activate'])
    ->add(new PermissionMiddleware('users.deactivate'));
 /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Shared by System Admin and Business Users.
    |
    | System Admin:
    |     Can access categories across businesses.
    |
    | Business User:
    |     Can access only their own business categories.
    |
    |--------------------------------------------------------------------------
    */

    $group->get(
        '/categories',
        [CategoryController::class, 'index']
    )->add(
        new PermissionMiddleware('categories.view')
    );


    $group->get(
        '/categories/create',
        [CategoryController::class, 'create']
    )->add(
        new PermissionMiddleware('categories.create')
    );


    $group->post(
        '/categories',
        [CategoryController::class, 'store']
    )->add(
        new PermissionMiddleware('categories.create')
    );


    $group->get(
        '/categories/{id}/edit',
        [CategoryController::class, 'edit']
    )->add(
        new PermissionMiddleware('categories.update')
    );


    $group->post(
        '/categories/{id}',
        [CategoryController::class, 'update']
    )->add(
        new PermissionMiddleware('categories.update')
    );

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        |
        | These routes should later be connected to ProductController.
        | Every operation must also be scoped by business_id.
        |
        |--------------------------------------------------------------------------
        */
/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
*/

$group->get(
    '/products',
    [ProductController::class, 'index']
)->add(
    new PermissionMiddleware('products.view')
);


$group->get(
    '/products/create',
    [ProductController::class, 'create']
)->add(
    new PermissionMiddleware('products.create')
);


$group->post(
    '/products',
    [ProductController::class, 'store']
)->add(
    new PermissionMiddleware('products.create')
);


$group->get(
    '/products/{uuid}/edit',
    [ProductController::class, 'edit']
)->add(
    new PermissionMiddleware('products.update')
);


$group->post(
    '/products/{uuid}',
    [ProductController::class, 'update']
)->add(
    new PermissionMiddleware('products.update')
);


$group->get('/products/export/excel', [ProductController::class, 'exportExcel'])->add(
    new PermissionMiddleware('products.export')
);
$group->get('/products/export', [ProductController::class, 'export'])->add(
    new PermissionMiddleware('products.export')
);
$group->get('/products/{status:inactive|all}', [ProductController::class, 'index'])->add(
    new PermissionMiddleware('products.view')
);
$group->post(
    '/products/{uuid}/deactivate',
    [ProductController::class, 'deactivate']
)->add(
    new PermissionMiddleware('products.update')
);


$group->post(
    '/products/{uuid}/activate',
    [ProductController::class, 'activate']
)->add(
    new PermissionMiddleware('products.update')
);

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        /*
        | Example:
        |
        | $group->get(
        |     '/orders',
        |     [OrderController::class, 'index']
        | )->add(
        |     new PermissionMiddleware('orders.view')
        | );
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        */

        /*
        | Example:
        |
        | $group->get(
        |     '/suppliers',
        |     [SupplierController::class, 'index']
        | )->add(
        |     new PermissionMiddleware('suppliers.view')
        | );
        */


        /*
        |--------------------------------------------------------------------------
        | Business Roles
        |--------------------------------------------------------------------------
        |
        | These will use the SAME RoleController and RoleService.
        | When implemented, they must only access roles belonging to
        | AuthHelper::businessId().
        |
        |--------------------------------------------------------------------------
        */

        /*
        | Example:
        |
        | $group->get(
        |     '/roles',
        |     [RoleController::class, 'businessIndex']
        | )->add(
        |     new PermissionMiddleware('roles.view')
        | );
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Business Reports
        |--------------------------------------------------------------------------
        */

        /*
        | Add report routes here when ReportController is implemented.
        */


        /*
        |--------------------------------------------------------------------------
        | Business Settings
        |--------------------------------------------------------------------------
        */

        /*
        | Add settings routes here when SettingsController is implemented.
        */


    })->add(
        AuthMiddleware::class
    );


    /*
    |--------------------------------------------------------------------------
    | SYSTEM ADMINISTRATOR ROUTES
    |--------------------------------------------------------------------------
    |
    | Everything under /system-admin is restricted to System Admin.
    |
    | SystemAdminMiddleware checks is_system_admin.
    |
    |--------------------------------------------------------------------------
    */


    $app->group('/system-admin', function ($group) {


        /*
        |--------------------------------------------------------------------------
        | System Admin Dashboard
        |--------------------------------------------------------------------------
        */

        $group->get(
            '',
            [DashboardController::class, 'systemAdmin']
        )->add(
            new PermissionMiddleware('dashboard.view')
        );


        /*
        |--------------------------------------------------------------------------
        | Business Management
        |--------------------------------------------------------------------------
        */


        /*
         * Business list
         */
        $group->get(
            '/businesses',
            [BusinessController::class, 'index']
        )->add(
            new PermissionMiddleware('businesses.view')
        );


        /*
         * Business registration form
         */
        $group->get(
            '/businesses/create',
            [BusinessController::class, 'create']
        )->add(
            new PermissionMiddleware('businesses.create')
        );


        /*
         * Create business
         */
        $group->post(
            '/businesses',
            [BusinessController::class, 'store']
        )->add(
            new PermissionMiddleware('businesses.create')
        );
        
        $group->get(
    '/businesses/{id}/edit',
    [BusinessController::class, 'edit']
    )->add(
    new PermissionMiddleware('businesses.update')
    );
    $group->post(
    '/businesses/{id}',
    [BusinessController::class, 'update']
    )->add(
    new PermissionMiddleware('businesses.update')
    );

        /*
        |--------------------------------------------------------------------------
        | System Admin User Management
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | These use the SAME users table as business users.
        |
        | System Admin sees users across all businesses.
        |
        |--------------------------------------------------------------------------
        */


        /*
         * All users
         */
        $group->get(
            '/users',
            [UserController::class, 'index']
        )->add(
            new PermissionMiddleware('users.view')
        );


        /*
         * Register business user
         */
        $group->get(
            '/users/create',
            [UserController::class, 'create']
        )->add(
            new PermissionMiddleware('users.create')
        );

$group->get(
    '/users/{uuid}',
    [UserController::class, 'show']
)->add(
    new PermissionMiddleware('users.view')
);
$group->post('/users/{uuid}/deactivate', [UserController::class, 'deactivate'])
    ->add(new PermissionMiddleware('users.deactivate'));

$group->post('/users/{uuid}/activate', [UserController::class, 'activate'])
    ->add(new PermissionMiddleware('users.deactivate'));
        /*
         * Load roles belonging to selected business.
         *
         * Example:
         *
         * /system-admin/users/businesses/1/roles
         */
        $group->get(
            '/users/businesses/{businessId}/roles',
            [UserController::class, 'rolesByBusiness']
        )->add(
            new PermissionMiddleware('users.create')
        );


        /*
         * Store user
         */
        $group->post(
            '/users',
            [UserController::class, 'store']
        )->add(
            new PermissionMiddleware('users.create')
        );


        /*
        |--------------------------------------------------------------------------
        | System Admin Role Management
        |--------------------------------------------------------------------------
        */


        /*
         * Roles list
         */
        $group->get(
            '/roles',
            [RoleController::class, 'index']
        )->add(
            new PermissionMiddleware('roles.view')
        );


        /*
         * Create role
         */
        $group->get(
            '/roles/create',
            [RoleController::class, 'create']
        )->add(
            new PermissionMiddleware('roles.create')
        );


        /*
         * Store role
         */
        $group->post(
            '/roles',
            [RoleController::class, 'store']
        )->add(
            new PermissionMiddleware('roles.create')
        );


        /*
        |--------------------------------------------------------------------------
        | Role Permissions
        |--------------------------------------------------------------------------
        */


        /*
         * Display permissions for a role
         */
        $group->get(
            '/roles/{id}/permissions',
            [RoleController::class, 'permissions']
        )->add(
            new PermissionMiddleware('roles.manage')
        );


        /*
         * Save permissions for a role
         */
        $group->post(
            '/roles/{id}/permissions',
            [RoleController::class, 'updatePermissions']
        )->add(
            new PermissionMiddleware('roles.manage')
        );


        /*
        |--------------------------------------------------------------------------
        | System Admin Reports
        |--------------------------------------------------------------------------
        */

        /*
        | Add report routes here when ReportController is implemented.
        */


        /*
        |--------------------------------------------------------------------------
        | System Settings
        |--------------------------------------------------------------------------
        */

        /*
        | Add system settings routes here when SettingsController is implemented.
        */

    })->add(
        SystemAdminMiddleware::class
    );

};