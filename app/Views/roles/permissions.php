<?php

$pageTitle = 'Permissions';

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">
            Assign Permissions
        </h1>

        <p class="page-subtitle mb-0">
            Role:
            <strong>
                <?= htmlspecialchars($role['name']) ?>
            </strong>
        </p>
    </div>

    <a
        href="/system-admin/roles"
        class="btn btn-outline-secondary"
    >
        <i class="bi bi-arrow-left me-1"></i>
        Back to Roles
    </a>

</div>


<form
    method="POST"
    action="/system-admin/roles/<?= (int) $role['id'] ?>/permissions"
>
  <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
            >
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="bi bi-shield-check me-2"></i>
                Permissions
            </h5>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <?php foreach ($permissions as $permission): ?>

                    <?php
                    $permissionId = (int) $permission['id'];

                    $checked = in_array(
                        $permissionId,
                        $assignedPermissionIds,
                        true
                    );
                    ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="form-check border rounded p-3 h-100">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="permission_ids[]"
                                value="<?= $permissionId ?>"
                                id="permission_<?= $permissionId ?>"
                                <?= $checked ? 'checked' : '' ?>
                            >

                            <label
                                class="form-check-label ms-2"
                                for="permission_<?= $permissionId ?>"
                            >

                                <strong>
                                    <?= htmlspecialchars(
                                        $permission['key_name']
                                    ) ?>
                                </strong>

                                <?php if (!empty($permission['description'])): ?>

                                    <div class="small text-muted mt-1">
                                        <?= htmlspecialchars(
                                            $permission['description']
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </label>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2">

            <a
                href="/system-admin/roles"
                class="btn btn-outline-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-check-lg me-1"></i>
                Save Permissions
            </button>

        </div>

    </div>

</form>
<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>
