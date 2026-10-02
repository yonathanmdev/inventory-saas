<?php

$pageTitle = 'Roles & Permissions';

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

<a
    href="/system-admin/roles/create"
    class="btn btn-primary"
>
    <i class="bi bi-shield-plus me-1"></i>
    Register Role
</a>


</div>

<div class="card shadow-sm">

```
<div class="card-header bg-white py-3">

    <h5 class="mb-1">
        Registered Roles
    </h5>

    <small class="text-muted">
        <?= count($roles) ?>
        role<?= count($roles) === 1 ? '' : 's' ?>
    </small>

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

            <a
                href="/system-admin/roles/create"
                class="btn btn-primary"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Register Role
            </a>

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
                        <th>Business</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($roles as $role): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars(
                                (string) $role['id']
                            ) ?>
                        </td>

                        <td>
                            <span class="fw-semibold">
                                <?= htmlspecialchars(
                                    $role['name']
                                ) ?>
                            </span>
                        </td>

                        <td>
                            <?php if (!empty($role['business_name'])): ?>

                                <?= htmlspecialchars(
                                    $role['business_name']
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    System
                                </span>

                            <?php endif; ?>
                        </td>

                        <td>

                            <?php if (
                                (int) $role['is_system_role'] === 1
                            ): ?>

                                <span class="badge text-bg-dark">
                                    System Role
                                </span>

                            <?php else: ?>

                                <span class="badge text-bg-primary">
                                    Business Role
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <div class="btn-group btn-group-sm">

                                <!-- View / Assign Permissions -->
                                <a
                                    href="/system-admin/roles/<?= (int) $role['id'] ?>/permissions"
                                    class="btn btn-outline-primary"
                                    title="Manage permissions"
                                >
                                    <i class="bi bi-shield-check"></i>
                                </a>

                                <?php if (
                                    (int) $role['is_system_role'] === 0
                                ): ?>

                                    <!-- Edit Role -->
                                    <a
                                        href="/system-admin/roles/<?= (int) $role['id'] ?>/edit"
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
