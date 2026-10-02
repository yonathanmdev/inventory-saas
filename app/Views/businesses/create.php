<?php

$pageTitle = 'Register Business';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">Register Business</h1>

        <p class="page-subtitle">
            Create a new business in the system.
        </p>
    </div>

    <a
        href="/system-admin/businesses"
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

    <div class="alert <?= $alertClass ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>

<?php endif; ?>


<div class="card">

    <div class="card-body">

        <form
            method="POST"
            action="/system-admin/businesses"
             enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
            >

            <div class="row">
 <div class="col-md-6 mb-3">

                    <label class="form-label">
                        የተቋሙ ስም
                    </label>

                    <input
                        type="text"
                        name="nameAmharic"
                        class="form-control"
                        required
                        maxlength="150"
                    >

                </div>
                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Business Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required
                        maxlength="150"
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        maxlength="150"
                    >

                </div>


                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        maxlength="50"
                    >

                </div>

<div class="col-6 mb-3">

    <label class="form-label">
        Address
    </label>

    <input
        type="text"
        name="address"
        class="form-control"
        maxlength="255"
    >

</div>

<div class="col-6 mb-3">

    <label class="form-label">
        Business Logo
    </label>

    <input
        type="file"
        name="logo"
        class="form-control"
        accept="image/jpeg,image/png,image/webp"
    >

    <div class="form-text">
        JPG, PNG or WEBP. Maximum size: 5 MB.
    </div>

</div>


                <div class="col-12 mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="4"
                    ></textarea>

                </div>

            </div>


            <div class="d-flex justify-content-end gap-2">

                <a
                    href="/system-admin/businesses"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-building-add"></i>
                    Register Business
                </button>

            </div>

        </form>

    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>