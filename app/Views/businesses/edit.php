<?php

$pageTitle = 'Edit Business';

ob_start();

/*
 * Expected variables from controller:
 *
 * $business = [
 *     'id'          => ...,
 *     'name'        => ...,
 *     'email'       => ...,
 *     'phone'       => ...,
 *     'address'     => ...,
 *     'description' => ...,
 * ];
 */

$flash = \App\Helpers\FlashHelper::get();

?>


<div class="d-flex justify-content-between align-items-center mb-4">

<div>
    <h1 class="page-title">Edit Business</h1>

    <p class="page-subtitle">
        Update the business information.
    </p>
</div>

<a
    href="/system-admin/businesses"
    class="btn btn-outline-secondary"
>
    <i class="bi bi-arrow-left"></i>
    Back to Businesses
</a>

</div>

<?php if ($flash): ?>

```
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
```

<?php endif; ?>

<div class="card">

```
<div class="card-header">
    <h5 class="mb-0">
        <i class="bi bi-building"></i>
        Business Information
    </h5>
</div>

<div class="card-body">

    <form
        method="POST"
        action="/system-admin/businesses/<?= $business['uuid'] ?>"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"
        >


        <div class="row">

            <!-- Business Name -->
            <div class="col-md-6 mb-3">

                <label class="form-label">
                    Business Name
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    required
                    maxlength="150"
                    value="<?= htmlspecialchars($business['name'] ?? '') ?>"
                >

            </div>


            <!-- Email -->
            <div class="col-md-6 mb-3">

                <label class="form-label">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    maxlength="150"
                    value="<?= htmlspecialchars($business['email'] ?? '') ?>"
                >

            </div>


            <!-- Phone -->
            <div class="col-md-6 mb-3">

                <label class="form-label">
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    class="form-control"
                    maxlength="50"
                    value="<?= htmlspecialchars($business['phone'] ?? '') ?>"
                >

            </div>


            <!-- Address -->
            <div class="col-md-6 mb-3">

                <label class="form-label">
                    Address
                </label>

                <input
                    type="text"
                    name="address"
                    class="form-control"
                    maxlength="255"
                    value="<?= htmlspecialchars($business['address'] ?? '') ?>"
                >

            </div>
<div class="col-6 mb-3">

    <label class="form-label">
        Business Logo
    </label>

    <?php if (!empty($business['logo_path'])): ?>

        <div class="mb-3">

            <div class="small text-muted mb-2">
                Current logo
            </div>

         <img
    src="/businesses/<?= htmlspecialchars($business['uuid'], ENT_QUOTES, 'UTF-8') ?>/logo"
    alt="Business logo"
    class="business-logo-preview"
>

        </div>

    <?php endif; ?>

    <input
        type="file"
        name="logo"
        class="form-control"
        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
    >

    <div class="form-text">
        Upload a new logo only if you want to replace the current one.
        Maximum size: 5 MB.
    </div>

</div>

            <!-- Description -->
            <div class="col-12 mb-3">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                    maxlength="1000"
                ><?= htmlspecialchars($business['description'] ?? '') ?></textarea>

            </div>

        </div>


        <div class="d-flex justify-content-end gap-2">

            <a
                href="/system-admin/businesses"
                class="btn btn-secondary"
            >
                <i class="bi bi-x-circle"></i>
                Cancel
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-check-circle"></i>
                Update Business
            </button>

        </div>

    </form>

</div>
```

</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>
