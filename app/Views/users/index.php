<?php

use App\Helpers\PermissionHelper;
use App\Helpers\AuthHelper;

$pageTitle = 'Roles & Permissions';
$activeNav = 'roles';

$isSystemAdmin = AuthHelper::isSystemAdmin();

ob_start();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="page-title">
            Roles & Permissions
        </h1>

        <p class="page-subtitle">
            Manage business roles and their permissions.
        </p>

    </div>


    <?php if (PermissionHelper::can('roles.create')): ?>

        <a
            href="/system-admin/roles/create"
            class="btn btn-primary"
        >
            <i class="bi bi-shield-plus me-1"></i>
            Register Role
        </a>

    <?php endif; ?>

</div>


<div class="card shadow-sm">

    <div class="card-header bg-white py-3">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h5 class="mb-1">
                    Registered Roles
                </h5>

                <small class="text-muted">

                    <?= count($roles) ?>

                    role<?= count($roles) === 1 ? '' : 's' ?>

                </small>

            </div>

        </div>

    </div>


    <div class="card-body">

        <?php if (empty($roles)): ?>

            <div class="text-center py-5">

                <i
                    class="bi bi-shield-lock text-muted"
                    style="font-size: 3.5rem;"
                ></i>

                <h5 class="mt-3">
                    No roles found
                </h5>

                <p class="text-muted mb-4">
                    Register your first business role to get started.
                </p>


                <?php if (PermissionHelper::can('roles.create')): ?>

                    <a
                        href="/system-admin/roles/create"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-plus-lg me-1"></i>
                        Register Role
                    </a>

                <?php endif; ?>

            </div>

        <?php else: ?>


            <div class="table-responsive">

                <table
                    id="rolesTable"
                    class="table table-hover align-middle w-100"
                >

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Role</th>

                            <?php if ($isSystemAdmin): ?>

                                <th>Business</th>

                                <th>Type</th>

                            <?php endif; ?>

                            <th class="text-end">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($roles as $role): ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                <?= htmlspecialchars(
                                    (string) $role['id'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- ROLE -->

                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $role['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            </td>


                            <!-- BUSINESS -->

                            <?php if ($isSystemAdmin): ?>

                                <td>

                                    <?php if (
                                        !empty($role['business_name'])
                                    ): ?>

                                        <span class="fw-medium">

                                            <?= htmlspecialchars(
                                                $role['business_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">

                                            System level

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <?php if (
                                        (int) $role['is_system_role'] === 1
                                    ): ?>

                                        <span class="badge text-bg-dark">

                                            <i class="bi bi-shield-lock me-1"></i>

                                            System Role

                                        </span>

                                    <?php else: ?>

                                        <span class="badge text-bg-primary">

                                            <i class="bi bi-building me-1"></i>

                                            Business Role

                                        </span>

                                    <?php endif; ?>

                                </td>

                            <?php endif; ?>


                            <!-- ACTIONS -->

                            <td class="text-end">

                                <div class="btn-group btn-group-sm">


                                    <!-- VIEW / MANAGE PERMISSIONS -->

                                    <?php if (
                                        PermissionHelper::can(
                                            'roles.permissions.view'
                                        )
                                    ): ?>

                                        <a
                                            href="/system-admin/roles/<?= urlencode(
                                                (string) $role['id']
                                            ) ?>/permissions"
                                            class="btn btn-outline-primary"
                                            title="View permissions"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>

                                    <?php endif; ?>


                                    <!-- ASSIGN / UPDATE PERMISSIONS -->

                                    <?php if (
                                        PermissionHelper::can(
                                            'roles.permissions.manage'
                                        )
                                    ): ?>

                                        <a
                                            href="/system-admin/roles/<?= urlencode(
                                                (string) $role['id']
                                            ) ?>/permissions"
                                            class="btn btn-outline-primary"
                                            title="Manage permissions"
                                        >

                                            <i class="bi bi-shield-check"></i>

                                        </a>

                                    <?php endif; ?>


                                    <!-- EDIT ROLE -->

                                    <?php if (
                                        PermissionHelper::can('roles.update')
                                        && (int) $role['is_system_role'] === 0
                                    ): ?>

                                        <a
                                            href="/system-admin/roles/<?= urlencode(
                                                (string) $role['id']
                                            ) ?>/edit"
                                            class="btn btn-outline-secondary"
                                            title="Edit role"
                                        >

                                            <i class="bi bi-pencil"></i>

                                        </a>

                                    <?php endif; ?>


                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>


<script
    nonce="<?= htmlspecialchars(
        $_SESSION['csp_nonce'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
document.addEventListener('DOMContentLoaded', function () {

    const table = document.getElementById('rolesTable');

    if (!table) {
        return;
    }

    new DataTable('#rolesTable', {

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'All']
        ],

        order: [
            [0, 'desc']
        ],

        columnDefs: [
            {
                orderable: false,
                targets: -1
            }
        ],

        layout: {

            topStart: {

                buttons: [

                    {
                        extend: 'copy',
                        text: '<i class="bi bi-copy me-1"></i> Copy'
                    },

                    {
                        extend: 'csv',
                        text: '<i class="bi bi-filetype-csv me-1"></i> CSV'
                    },

                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel'
                    },

                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer me-1"></i> Print'
                    }

                ]

            },

            topEnd: {

                search: {
                    placeholder: 'Search roles...'
                }

            },

            bottomStart: 'pageLength',

            bottomEnd: 'paging'

        },

        language: {

            emptyTable:
                'No roles found.',

            zeroRecords:
                'No matching roles found.',

            info:
                'Showing _START_ to _END_ of _TOTAL_ roles',

            infoEmpty:
                'Showing 0 roles',

            lengthMenu:
                'Show _MENU_'

        }

    });

});
</script>

