<?php

use App\Helpers\AuthHelper;
use App\Helpers\PermissionHelper;

$pageTitle = 'Categories';
$activeNav = 'categories';

$isSystemAdmin =
    AuthHelper::isSystemAdmin();

ob_start();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="page-title">
            Categories
        </h1>

        <p class="page-subtitle">
            Manage product categories for your inventory.
        </p>

    </div>


    <?php if (
        PermissionHelper::can('categories.create')
    ): ?>

        <a
            href="/categories/create"
            class="btn btn-primary"
        >

            <i class="bi bi-plus-circle me-1"></i>

            Register Category

        </a>

    <?php endif; ?>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table
                id="categoriesTable"
                class="table table-hover align-middle"
            >

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Category</th>

                        <?php if ($isSystemAdmin): ?>

                            <th>Business</th>

                        <?php endif; ?>

                        <th>Description</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach (
                        $categories as $index => $category
                    ): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>


                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $category['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            </td>


                            <?php if ($isSystemAdmin): ?>

                                <td>

                                    <?= htmlspecialchars(
                                        $category['business_name'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>

                            <?php endif; ?>


                            <td>

                                <span class="text-muted">

                                    <?= htmlspecialchars(
                                        $category['description'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if (
                                    (int) $category['is_active'] === 1
                                ): ?>

                                    <span class="badge bg-success-subtle text-success">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary-subtle text-secondary">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $category['created_at'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <?php if (
                                    PermissionHelper::can(
                                        'categories.update'
                                    )
                                ): ?>

                                    <a
                                        href="/categories/<?= (int) $category['id'] ?>/edit"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<script
    nonce="<?= htmlspecialchars(
        $_SESSION['csp_nonce'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const table =
            document.getElementById(
                'categoriesTable'
            );


        if (!table) {
            return;
        }


        const isSystemAdmin =
            <?= $isSystemAdmin
                ? 'true'
                : 'false' ?>;


        const actionsColumnIndex =
            isSystemAdmin ? 6 : 5;


        new DataTable(
            '#categoriesTable',
            {

                pageLength: 10,

                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],

                order: [[0, 'desc']],

                columnDefs: [
                    {
                        orderable: false,
                        targets: actionsColumnIndex
                    }
                ],

                layout: {

                    topStart: {

                        buttons: [

                            {
                                extend: 'copy',
                                text:
                                    '<i class="bi bi-copy me-1"></i> Copy'
                            },

                            {
                                extend: 'csv',
                                text:
                                    '<i class="bi bi-filetype-csv me-1"></i> CSV'
                            },

                            {
                                extend: 'excel',
                                text:
                                    '<i class="bi bi-file-earmark-excel me-1"></i> Excel'
                            },

                            {
                                extend: 'print',
                                text:
                                    '<i class="bi bi-printer me-1"></i> Print'
                            }

                        ]

                    },

                    topEnd: {

                        search: {
                            placeholder:
                                'Search categories...'
                        }

                    },

                    bottomStart: 'pageLength',

                    bottomEnd: 'paging'

                },

                language: {

                    emptyTable:
                        'No categories found.',

                    zeroRecords:
                        'No matching categories found.',

                    info:
                        'Showing _START_ to _END_ of _TOTAL_ categories',

                    infoEmpty:
                        'Showing 0 categories',

                    lengthMenu:
                        'Show _MENU_'

                }

            }
        );

    }
);

</script>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';