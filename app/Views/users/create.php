<?php

use App\Helpers\AuthHelper;

$pageTitle = 'Register User';
$activeNav = 'users';

$isSystemAdmin = AuthHelper::isSystemAdmin();

$formAction = $isSystemAdmin
    ? '/system-admin/users'
    : '/users';

$backUrl = $isSystemAdmin
    ? '/system-admin/users'
    : '/users';

ob_start();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">
            Register User
        </h1>

        <p class="page-subtitle">
            Create a user and assign them to a business role.
        </p>
    </div>

    <a
        href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
        class="btn btn-outline-secondary"
    >
        <i class="bi bi-arrow-left"></i>
        Back
    </a>

</div>


<?php $flash = \App\Helpers\FlashHelper::get(); ?>

<?php if ($flash): ?>

    <?php
        $alertClass = match ($flash['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            'info'    => 'alert-info',
            default   => 'alert-secondary',
        };
    ?>

    <div
        class="alert <?= $alertClass ?> alert-dismissible fade show"
        role="alert"
    >

        <?= htmlspecialchars(
            $flash['message'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>

    </div>

<?php endif; ?>


<div class="card">

    <div class="card-header">

        <div class="d-flex align-items-center">

            <div class="me-3">

                <span
                    class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary"
                    style="width: 42px; height: 42px;"
                >
                    <i class="bi bi-person-plus"></i>
                </span>

            </div>

            <div>

                <h5 class="mb-1">
                    User Information
                </h5>

                <small class="text-muted">

                    <?php if ($isSystemAdmin): ?>

                        Assign the user to a business and role.

                    <?php else: ?>

                        Create a user for your business and assign a role.

                    <?php endif; ?>

                </small>

            </div>

        </div>

    </div>


    <div class="card-body">

        <form
            method="POST"
            action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
            id="userRegistrationForm"
            novalidate
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <!-- =========================
                 BUSINESS & ROLE
            ========================== -->

            <div class="mb-4">

                <h6 class="text-uppercase text-muted fw-semibold mb-3">
                    Organization Access
                </h6>


                <div class="row">

                    <?php if ($isSystemAdmin): ?>

                        <!-- BUSINESS -->

                        <div class="col-md-6 mb-3">

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

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    — Select a business —
                                </option>

                                <?php foreach ($businesses as $business): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            (string) $business['id'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $business['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div class="form-text">
                                Select the business this user will belong to.
                            </div>

                        </div>


                        <!-- ROLE -->

                        <div class="col-md-6 mb-3">

                            <label
                                for="role_id"
                                class="form-label"
                            >
                                Role
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="role_id"
                                id="role_id"
                                class="form-select"
                                required
                                disabled
                            >

                                <option value="">
                                    — Select a business first —
                                </option>

                            </select>

                            <div
                                id="roleHelp"
                                class="form-text"
                            >
                                Roles available for the selected business
                                will appear here.
                            </div>

                        </div>

                    <?php else: ?>

                        <!-- BUSINESS USER -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Business
                            </label>

                            <div class="form-control bg-light">
                                Your current business
                            </div>

                            <div class="form-text">
                                The new user will automatically belong to
                                your business.
                            </div>

                        </div>


                        <!-- ROLE -->

                        <div class="col-md-6 mb-3">

                            <label
                                for="role_id"
                                class="form-label"
                            >
                                Role
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="role_id"
                                id="role_id"
                                class="form-select"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    — Select a role —
                                </option>

                                <?php foreach ($roles as $role): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            (string) $role['id'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $role['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div class="form-text">
                                Select the role that should be assigned
                                to this user.
                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <hr class="my-4">


            <!-- =========================
                 PERSONAL INFORMATION
            ========================== -->

            <div class="mb-4">

                <h6 class="text-uppercase text-muted fw-semibold mb-3">
                    User Information
                </h6>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="full_name"
                            class="form-label"
                        >
                            Full Name
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            id="full_name"
                            class="form-control"
                            required
                            maxlength="150"
                            autocomplete="name"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="email"
                            class="form-label"
                        >
                            Email
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            required
                            maxlength="150"
                            autocomplete="email"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="username"
                            class="form-label"
                        >
                            Username
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            required
                            maxlength="150"
                            autocomplete="username"
                        >

                        <div class="form-text">
                            The username must be unique.
                        </div>

                    </div>

                </div>

            </div>


            <hr class="my-4">


            <!-- =========================
                 PASSWORD
            ========================== -->

            <div class="mb-4">

                <h6 class="text-uppercase text-muted fw-semibold mb-3">
                    Login Credentials
                </h6>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="password"
                            class="form-label"
                        >
                            Password
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >

                        <div class="form-text">
                            Minimum 8 characters.
                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="password_confirm"
                            class="form-label"
                        >
                            Confirm Password
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="password"
                            name="password_confirm"
                            id="password_confirm"
                            class="form-control"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >

                    </div>

                </div>

            </div>


            <!-- =========================
                 INFORMATION NOTICE
            ========================== -->

            <div class="alert alert-info d-flex align-items-start">

                <i class="bi bi-info-circle me-2 mt-1"></i>

                <div>

                    <strong>Access control</strong>

                    <div class="small mt-1">

                        This user's access will be determined by the
                        permissions assigned to the selected role.

                    </div>

                </div>

            </div>


            <!-- =========================
                 ACTIONS
            ========================== -->

            <div class="d-flex justify-content-end gap-2">

                <a
                    href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="registerUserButton"
                >

                    <i class="bi bi-person-plus me-1"></i>

                    Register User

                </button>

            </div>

        </form>

    </div>

</div>


<?php if ($isSystemAdmin): ?>

<script
    nonce="<?= htmlspecialchars(
        $_SESSION['csp_nonce'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
document.addEventListener('DOMContentLoaded', function () {

    const businessSelect =
        document.getElementById('business_id');

    const roleSelect =
        document.getElementById('role_id');

    const roleHelp =
        document.getElementById('roleHelp');


    if (!businessSelect || !roleSelect) {
        return;
    }


    /*
     * Load roles whenever the selected
     * business changes.
     */
    businessSelect.addEventListener('change', async function () {

        const businessId = this.value;

        roleSelect.innerHTML = '';

        roleSelect.disabled = true;


        const loadingOption =
            document.createElement('option');

        loadingOption.value = '';

        loadingOption.textContent =
            'Loading roles...';

        roleSelect.appendChild(loadingOption);


        if (!businessId) {

            roleSelect.innerHTML =
                '<option value="">— Select a business first —</option>';

            roleHelp.textContent =
                'Select a business to load its roles.';

            return;
        }


        try {

            const response = await fetch(
                '/system-admin/users/businesses/'
                + encodeURIComponent(businessId)
                + '/roles',
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );


            if (!response.ok) {
                throw new Error(
                    'Unable to load roles.'
                );
            }


            const roles = await response.json();

            roleSelect.innerHTML = '';


            if (!Array.isArray(roles) || roles.length === 0) {

                const option =
                    document.createElement('option');

                option.value = '';

                option.textContent =
                    '— No roles available —';

                roleSelect.appendChild(option);

                roleSelect.disabled = true;

                roleHelp.textContent =
                    'This business does not have any roles yet.';

                return;
            }


            const defaultOption =
                document.createElement('option');

            defaultOption.value = '';

            defaultOption.textContent =
                '— Select a role —';

            defaultOption.disabled = true;

            defaultOption.selected = true;

            roleSelect.appendChild(defaultOption);


            roles.forEach(function (role) {

                const option =
                    document.createElement('option');

                option.value = role.id;

                option.textContent = role.name;

                roleSelect.appendChild(option);

            });


            roleSelect.disabled = false;

            roleHelp.textContent =
                'Select the role that should be assigned to this user.';


        } catch (error) {

            roleSelect.innerHTML = '';

            const option =
                document.createElement('option');

            option.value = '';

            option.textContent =
                '— Unable to load roles —';

            roleSelect.appendChild(option);

            roleSelect.disabled = true;

            roleHelp.textContent =
                'There was a problem loading the roles. Please try again.';

            console.error(error);
        }

    });

});
</script>

<?php endif; ?>


<script
    nonce="<?= htmlspecialchars(
        $_SESSION['csp_nonce'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
document.addEventListener('DOMContentLoaded', function () {

    const form =
        document.getElementById('userRegistrationForm');

    const registerButton =
        document.getElementById('registerUserButton');


    if (!form || !registerButton) {
        return;
    }


    /*
     * Prevent accidental double submission.
     */
    form.addEventListener('submit', function (event) {

        if (!form.checkValidity()) {
            return;
        }

        registerButton.disabled = true;

        registerButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1" '
            + 'role="status" aria-hidden="true"></span>'
            + ' Registering...';

    });

});
</script>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>