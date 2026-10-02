<?php

use App\Helpers\AuthHelper;
use App\Helpers\PermissionHelper;

$isSystemAdmin = AuthHelper::isSystemAdmin();

/*
|--------------------------------------------------------------------------
| SIDEBAR NAVIGATION
|--------------------------------------------------------------------------
|
| System Admin:
|     PermissionHelper::can() automatically grants access
|     because System Admin receives ['*'] permissions.
|
| Business User:
|     Access is controlled by permissions assigned to the
|     user's role.
|
| IMPORTANT:
|     Sidebar visibility is only a UI convenience.
|     Routes must ALSO be protected by PermissionMiddleware.
|
*/


/*
|--------------------------------------------------------------------------
| SYSTEM ADMIN NAVIGATION
|--------------------------------------------------------------------------
*/

if ($isSystemAdmin) {

    $sidebarSections = [

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        [
            'label' => null,

            'items' => [

                [
                    'key'        => 'dashboard',
                    'label'      => 'Dashboard',
                    'icon'       => 'speedometer2',
                    'href'       => '/system-admin',
                    'permission' => 'dashboard.view',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | BUSINESS MANAGEMENT
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'BUSINESS MANAGEMENT',

            'items' => [

                [
                    'key'        => 'businesses',
                    'label'      => 'Businesses',
                    'icon'       => 'buildings',
                    'href'       => '/system-admin/businesses',
                    'permission' => 'businesses.view',
                ],

                [
                    'key'        => 'roles',
                    'label'      => 'Roles & Permissions',
                    'icon'       => 'shield-lock',
                    'href'       => '/system-admin/roles',
                    'permission' => 'roles.view',
                ],

                [
                    'key'        => 'users',
                    'label'      => 'Users',
                    'icon'       => 'people',
                    'href'       => '/system-admin/users',
                    'permission' => 'users.view',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | INVENTORY
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'INVENTORY',

            'items' => [

                [
                    'key'        => 'categories',
                    'label'      => 'Categories',
                    'icon'       => 'tags',
                    'href'       => '/categories',
                    'permission' => 'categories.view',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | REPORTS
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'REPORTS',

            'items' => [

                [
                    'key'        => 'reports',
                    'label'      => 'Reports',
                    'icon'       => 'bar-chart',
                    'href'       => '/system-admin/reports',
                    'permission' => 'reports.view',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | SYSTEM
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'SYSTEM',

            'items' => [

                [
                    'key'        => 'settings',
                    'label'      => 'System Settings',
                    'icon'       => 'gear',
                    'href'       => '/system-admin/settings',
                    'permission' => 'settings.view',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | TRASH
        |--------------------------------------------------------------------------
        |
        | Trash is a parent menu.
        | Individual access is controlled by child permissions.
        |
        */

        [
            'label' => 'TRASH',

            'items' => [

                [
                    'key'        => 'trash',
                    'label'      => 'Trash',
                    'icon'       => 'trash3',
                    'href'       => '#',
                    'permission' => null,

                    'children' => [

                        [
                            'key'        => 'trash-businesses',
                            'label'      => 'Businesses',
                            'href'       => '/system-admin/businesses?status=inactive',
                            'permission' => 'businesses.delete',
                        ],

                        [
                            'key'        => 'trash-users',
                            'label'      => 'Users',
                            'href'       => '/system-admin/users?status=inactive',
                            'permission' => 'users.delete',
                        ],

                        [
                            'key'        => 'trash-roles',
                            'label'      => 'Roles',
                            'href'       => '/system-admin/roles?status=inactive',
                            'permission' => 'roles.delete',
                        ],

                        [
                            'key'        => 'trash-categories',
                            'label'      => 'Categories',
                            'href'       => '/categories?status=inactive',
                            'permission' => 'categories.delete',
                        ],

                    ],
                ],

            ],
        ],

    ];

    $sectionLabel = 'SYSTEM ADMINISTRATION';


/*
|--------------------------------------------------------------------------
| BUSINESS USER NAVIGATION
|--------------------------------------------------------------------------
*/

} else {

    $sidebarSections = [

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        [
            'label' => null,

            'items' => [

                [
                    'key'        => 'dashboard',
                    'label'      => 'Dashboard',
                    'icon'       => 'speedometer2',
                    'href'       => '/dashboard',
                    'permission' => 'dashboard.view',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATION
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'ADMINISTRATION',

            'items' => [

                [
                    'key'        => 'users',
                    'label'      => 'Users',
                    'icon'       => 'people',
                    'href'       => '/users',
                    'permission' => 'users.view',
                ],


            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | INVENTORY
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'INVENTORY',

            'items' => [

                [
                    'key'        => 'categories',
                    'label'      => 'Categories',
                    'icon'       => 'tags',
                    'href'       => '/categories',
                    'permission' => 'categories.view',
                ],

                [
                    'key'        => 'products',
                    'label'      => 'የንብረት ዓይነት',
                    'icon'       => 'box-seam',
                    'href'       => '/products',
                    'permission' => 'products.view',
                ],


                /*
                |--------------------------------------------------------------------------
                | FUTURE INVENTORY MODULES
                |--------------------------------------------------------------------------
                */

                [
                    'key'        => 'stock-in',
                    'label'      => 'ገቢ/ሞዴል 19',
                    'icon'       => 'box-arrow-in-down',
                    'href'       => '/stock-in',
                    'permission' => 'stock_in.view',
                ],

                [
                    'key'        => 'stock-out',
                    'label'      => 'ወጭ/ሞዴል 22',
                    'icon'       => 'box-arrow-up',
                    'href'       => '/stock-out',
                    'permission' => 'stock_out.view',
                ],

                [
                    'key'        => 'stock-history',
                    'label'      => 'Stock History',
                    'icon'       => 'clock-history',
                    'href'       => '/stock-history',
                    'permission' => 'stock_history.view',
                ],
                

            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | SALES
        |--------------------------------------------------------------------------
        |
        | Enable when Sales/Orders modules are implemented.
        |
        */

        [
            'label' => 'SALES',

            'items' => [

                /*
                [
                    'key'        => 'orders',
                    'label'      => 'Orders',
                    'icon'       => 'cart3',
                    'href'       => '/orders',
                    'permission' => 'orders.view',
                ],

                [
                    'key'        => 'sales',
                    'label'      => 'Sales',
                    'icon'       => 'cash-stack',
                    'href'       => '/sales',
                    'permission' => 'sales.view',
                ],

                [
                    'key'        => 'sales-returns',
                    'label'      => 'Sales Returns',
                    'icon'       => 'arrow-return-left',
                    'href'       => '/sales/returns',
                    'permission' => 'sales.returns.view',
                ],
                */

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | PURCHASING
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'PURCHASING',

            'items' => [

                /*
                [
                    'key'        => 'suppliers',
                    'label'      => 'Suppliers',
                    'icon'       => 'truck',
                    'href'       => '/suppliers',
                    'permission' => 'suppliers.view',
                ],

                [
                    'key'        => 'purchases',
                    'label'      => 'Purchase History',
                    'icon'       => 'receipt',
                    'href'       => '/purchases',
                    'permission' => 'purchases.view',
                ],
                */

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | REPORTS
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'REPORTS',

            'items' => [

                /*
                [
                    'key'        => 'reports',
                    'label'      => 'Reports',
                    'icon'       => 'bar-chart',
                    'href'       => '/reports',
                    'permission' => 'reports.view',
                ],
                */

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | SYSTEM
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'SYSTEM',

            'items' => [

                /*
                [
                    'key'        => 'settings',
                    'label'      => 'Business Settings',
                    'icon'       => 'gear',
                    'href'       => '/settings',
                    'permission' => 'settings.view',
                ],
                */

            ],
        ],
    

    ];

    $sectionLabel = 'BUSINESS MANAGEMENT';
}

?>

<style nonce="<?= htmlspecialchars(
    \App\Helpers\Csp::nonce(),
    ENT_QUOTES,
    'UTF-8'
) ?>">
    .offcanvas-header.d-lg-none {
       border-bottom: 1px solid rgba(255,255,255,0.08);
    }
</style>



<!-- ===================================================================== -->
<!-- SIDEBAR -->
<!-- ===================================================================== -->

<div
    class="offcanvas-lg offcanvas-start app-sidebar"
    tabindex="-1"
    id="sidebar"
>


    <!-- ================================================================= -->
    <!-- MOBILE SIDEBAR HEADER -->
    <!-- ================================================================= -->

    <div
        class="offcanvas-header d-lg-none"
    >

        <span class="brand text-white">

            <span class="brand-icon">
                IS
            </span>

            <?= $isSystemAdmin
                ? 'System Admin'
                : 'Inventory'
            ?>

        </span>


        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
            aria-label="Close navigation"
        ></button>

    </div>


    <!-- ================================================================= -->
    <!-- SIDEBAR BODY -->
    <!-- ================================================================= -->

    <div class="offcanvas-body d-flex flex-column p-0">

        <nav
            class="sidebar-nav"
            aria-label="Main navigation"
        >


            <?php foreach ($sidebarSections as $section): ?>

                <?php

                /*
                |--------------------------------------------------------------------------
                | FILTER VISIBLE ITEMS
                |--------------------------------------------------------------------------
                |
                | Parent menu items:
                |     Use their own permission.
                |
                | Parent items with children:
                |     Show the parent when at least one child is accessible.
                |
                */

                $visibleItems = [];

                foreach ($section['items'] as $item) {

                    /*
                    |--------------------------------------------------------------------------
                    | CHILDREN
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($item['children'])) {

                        $visibleChildren = [];

                        foreach ($item['children'] as $child) {

                            if (
                                !empty($child['permission'])
                                && PermissionHelper::can(
                                    $child['permission']
                                )
                            ) {
                                $visibleChildren[] = $child;
                            }

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Only show parent if it has accessible children
                        |--------------------------------------------------------------------------
                        */

                        if (!empty($visibleChildren)) {

                            $item['children'] = $visibleChildren;

                            $visibleItems[] = $item;
                        }

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Normal menu item
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty($item['permission'])
                        && PermissionHelper::can(
                            $item['permission']
                        )
                    ) {
                        $visibleItems[] = $item;
                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Do not display an empty section
                |--------------------------------------------------------------------------
                */

                if (empty($visibleItems)) {
                    continue;
                }

                ?>


                <!-- ========================================================= -->
                <!-- SECTION LABEL -->
                <!-- ========================================================= -->

                <?php if (!empty($section['label'])): ?>

                    <div class="sidebar-section-label">

                        <?= htmlspecialchars(
                            $section['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- ========================================================= -->
                <!-- SECTION ITEMS -->
                <!-- ========================================================= -->

                <?php foreach ($visibleItems as $item): ?>

                    <?php

                    $hasChildren = !empty($item['children']);

                    $isActive =
                        ($activeNav ?? '') === $item['key'];


                    /*
                    |--------------------------------------------------------------------------
                    | Check whether a child is active
                    |--------------------------------------------------------------------------
                    */

                    $hasActiveChild = false;

                    if ($hasChildren) {

                        foreach ($item['children'] as $child) {

                            if (
                                ($activeNav ?? '')
                                ===
                                ($child['key'] ?? '')
                            ) {
                                $hasActiveChild = true;
                                break;
                            }

                        }

                    }


                    $parentActive =
                        $isActive || $hasActiveChild;


                    /*
                    |--------------------------------------------------------------------------
                    | Generate unique collapse ID
                    |--------------------------------------------------------------------------
                    */

                    $collapseId =
                        'sidebar-menu-' .
                        preg_replace(
                            '/[^a-zA-Z0-9_-]/',
                            '-',
                            $item['key']
                        );

                    ?>


                    <!-- ===================================================== -->
                    <!-- NORMAL MENU ITEM -->
                    <!-- ===================================================== -->

                    <?php if (!$hasChildren): ?>

                        <a
                            class="sidebar-link <?= $isActive ? 'active' : '' ?>"
                            href="<?= htmlspecialchars(
                                $item['href'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $isActive
                                ? 'aria-current="page"'
                                : '' ?>
                        >

                            <i
                                class="bi bi-<?= htmlspecialchars(
                                    $item['icon'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                aria-hidden="true"
                            ></i>

                            <span>
                                <?= htmlspecialchars(
                                    $item['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </a>


                    <!-- ===================================================== -->
                    <!-- MENU ITEM WITH CHILDREN -->
                    <!-- ===================================================== -->

                    <?php else: ?>

                        <a
                            class="sidebar-link <?= $parentActive ? 'active' : '' ?>"
                            href="#<?= htmlspecialchars(
                                $collapseId,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-bs-toggle="collapse"
                            role="button"
                            aria-expanded="<?= $parentActive ? 'true' : 'false' ?>"
                            aria-controls="<?= htmlspecialchars(
                                $collapseId,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <i
                                class="bi bi-<?= htmlspecialchars(
                                    $item['icon'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                aria-hidden="true"
                            ></i>

                            <span>
                                <?= htmlspecialchars(
                                    $item['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <i
                                class="bi bi-chevron-down ms-auto small"
                                aria-hidden="true"
                            ></i>

                        </a>


                        <!-- ================================================= -->
                        <!-- CHILDREN -->
                        <!-- ================================================= -->

                        <div
                            class="collapse <?= $parentActive ? 'show' : '' ?>"
                            id="<?= htmlspecialchars(
                                $collapseId,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <div class="sidebar-submenu">

                                <?php foreach ($item['children'] as $child): ?>

                                    <?php

                                    $childActive =
                                        ($activeNav ?? '')
                                        ===
                                        ($child['key'] ?? '');

                                    ?>

                                    <a
                                        class="sidebar-link sidebar-submenu-link <?= $childActive ? 'active' : '' ?>"
                                        href="<?= htmlspecialchars(
                                            $child['href'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= $childActive
                                            ? 'aria-current="page"'
                                            : '' ?>
                                    >

                                        <i
                                            class="bi bi-dot"
                                            aria-hidden="true"
                                        ></i>

                                        <span>
                                            <?= htmlspecialchars(
                                                $child['label'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </a>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>


            <?php endforeach; ?>

        </nav>


        <!-- ================================================================= -->
        <!-- SIDEBAR USER INFORMATION -->
        <!-- ================================================================= -->

        <div class="sidebar-footer">

            <span class="avatar">

                <?= htmlspecialchars(
                    strtoupper(
                        mb_substr(
                            $_SESSION['full_name'] ?? 'U',
                            0,
                            1
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>


            <div>

                <div class="sidebar-footer-name">

                    <?= htmlspecialchars(
                        $_SESSION['full_name'] ?? 'User',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div class="sidebar-footer-role">

                    <?= htmlspecialchars(
                        $_SESSION['role_name'] ?? 'User',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            </div>

        </div>


    </div>

</div>