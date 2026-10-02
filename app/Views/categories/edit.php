<?php

$pageTitle = 'Edit Category';
$activeNav = 'categories';

ob_start();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="page-title">
            Edit Category
        </h1>

        <p class="page-subtitle">
            Update category information.
        </p>

    </div>


    <a
        href="/categories"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-arrow-left me-1"></i>

        Back to Categories

    </a>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-header bg-white border-bottom">

        <h5 class="mb-0">

            <i class="bi bi-pencil-square me-2"></i>

            Category Information

        </h5>

    </div>


    <div class="card-body">

        <form
            method="POST"
            action="/categories/<?= (int) $category['id'] ?>"
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


            <div class="mb-3">

                <label class="form-label">
                    Business
                </label>


                <input
                    type="text"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $category['business_name'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    readonly
                >

            </div>


            <div class="mb-3">

                <label
                    for="name"
                    class="form-label"
                >

                    Category Name

                    <span class="text-danger">*</span>

                </label>


                <input
                    type="text"
                    name="name"
                    id="name"
                    class="form-control"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $category['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <div class="mb-3">

                <label
                    for="description"
                    class="form-label"
                >
                    Description
                </label>


                <textarea
                    name="description"
                    id="description"
                    class="form-control"
                    rows="4"
                ><?= htmlspecialchars(
                    $category['description'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>


            <div class="form-check form-switch mb-4">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="is_active"
                    value="1"
                    id="is_active"
                    <?= (int) $category['is_active'] === 1
                        ? 'checked'
                        : '' ?>
                >

                <label
                    class="form-check-label"
                    for="is_active"
                >
                    Active
                </label>

            </div>


            <div class="d-flex justify-content-end gap-2">

                <a
                    href="/categories"
                    class="btn btn-light"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-check-circle me-1"></i>

                    Update Category

                </button>

            </div>

        </form>

    </div>

</div>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';