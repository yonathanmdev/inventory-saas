<?php

$pageTitle = 'System Admin Dashboard';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">
            System Admin Dashboard
        </h1>

        <p class="page-subtitle">
            Manage the entire Inventory Management System.
        </p>
    </div>

</div>

<div class="row">

    <div class="col-md-4 mb-4">

        <div class="card">

            <div class="card-body">

                <h6 class="text-muted">
                    Total Businesses
                </h6>

                <h2>
                    <?= (int) $businessCount ?>
                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-4">

        <div class="card">

            <div class="card-body">

                <h6 class="text-muted">
                    Active Businesses
                </h6>

                <h2>
                    <?= (int) $activeBusinessCount ?>
                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-4">

        <div class="card">

            <div class="card-body">

                <h6 class="text-muted">
                    Total Users
                </h6>

                <h2>
                    <?= (int) $userCount ?>
                </h2>

            </div>

        </div>

    </div>

</div>

<?php

$content = ob_get_clean();
require __DIR__ . '/../layouts/dashboard.php';