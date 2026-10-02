<?php

$pageTitle = 'Register Product';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">የንብረቱ ዓይነት</h1>

        <p class="page-subtitle">
            አዲስ የንብረት ዓይነት መዝግብ.
        </p>
    </div>

    <a
        href="/products"
        class="btn btn-outline-secondary"
    >
        <i class="bi bi-arrow-left"></i>
       ተመለስ
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
            action="/products"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'] ?? ''
                ) ?>"
            >

            <div class="row">

                <!-- PRODUCT NAME -->

                <div class="col-md-6 mb-3">

                    <label
                        for="name"
                        class="form-label"
                    >
                       የንብረቱ ዓይነት
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $old['name'] ?? ''
                        ) ?>"
                        required
                        maxlength="200"
                        placeholder="የንብረቱን ዓይነት ያስገቡ"
                    >

                </div>

                <!-- CATEGORY -->

                <div class="col-md-6 mb-3">

                    <label
                        for="category_id"
                        class="form-label"
                    >
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-select"
                    >

                        <option value="">
                            Select category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category['uuid'] ?>"
                                <?= (
                                    (string) (
                                        $old['category_id'] ?? ''
                                    )
                                    ===
                                    (string) $category['uuid']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars(
                                    $category['name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="col-12 mb-3">

                    <label
                        for="description"
                        class="form-label"
                    >
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="4"
                        maxlength="5000"
                        placeholder="Enter product description"
                    ><?= htmlspecialchars(
                        $old['description'] ?? ''
                    ) ?></textarea>

                </div>

            </div>


            <!-- ACTIONS -->

            <div class="d-flex justify-content-end gap-2">

                <a
                    href="/products"
                    class="btn btn-secondary"
                >
                    <i class="bi bi-x-lg me-1"></i>
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-box-seam me-1"></i>
                   መዝግብ
                </button>

            </div>

        </form>

    </div>

</div>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';
?>