<?php

$pageTitle = 'Register Role';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="page-title">
            Register Role
        </h1>

        <p class="page-subtitle">
            Create a role for a registered business.
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

<div class="card shadow-sm">

    <div class="card-header bg-white py-3">

        <h5 class="mb-0">
            Role Information
        </h5>

    </div>

    <div class="card-body">

        <form
            method="POST"
            action="/system-admin/roles"
        >
  <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
            >
            <div class="row g-4">

                <div class="col-md-6">

                    <label
                        for="business_id"
                        class="form-label"
                    >
                        Business
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="business_id"
                        id="business_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select business
                        </option>

                        <?php foreach ($businesses as $business): ?>

                            <option
                                value="<?= (int) $business['id'] ?>"
                            >
                                <?= htmlspecialchars(
                                    $business['name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-6">

                    <label
                        for="name"
                        class="form-label"
                    >
                        Role Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        maxlength="50"
                        placeholder="e.g. Storekeeper"
                        required
                    >

                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">

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
                    Register Role
                </button>

            </div>

        </form>

    </div>

</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>