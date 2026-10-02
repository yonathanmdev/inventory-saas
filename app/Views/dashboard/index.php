<?php

$pageTitle = 'Dashboard';

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="page-title">
            Welcome back,
            <?= htmlspecialchars(
                explode(
                    ' ',
                    $_SESSION['full_name'] ?? ''
                )[0]
            ) ?>
        </h1>

        <p class="page-subtitle">
            Here's what's happening with your inventory today.
        </p>

    </div>

</div>


<div class="row g-3">

    <!-- Total Products -->
    <div class="col-6 col-lg-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div class="d-flex align-items-center mb-3">

                    <div class="stat-icon bg-primary-soft">
                        <i class="bi bi-box-seam"></i>
                    </div>

                </div>

                <div class="text-muted small">
                    Total Products
                </div>

                <h2 class="mb-0">
                    <?= (int) ($totalProducts ?? 0) ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Low Stock -->
    <div class="col-6 col-lg-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div class="d-flex align-items-center mb-3">

                    <div class="stat-icon bg-danger-soft">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                </div>

                <div class="text-muted small">
                    Low Stock
                </div>

                <h2 class="mb-0">
                    <?= (int) ($lowStockCount ?? 0) ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Pending Orders -->
    <div class="col-6 col-lg-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div class="d-flex align-items-center mb-3">

                    <div class="stat-icon bg-warning-soft">
                        <i class="bi bi-receipt"></i>
                    </div>

                </div>

                <div class="text-muted small">
                    Pending Orders
                </div>

                <h2 class="mb-0">
                    <?= (int) ($pendingOrders ?? 0) ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Revenue -->
    <div class="col-6 col-lg-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div class="d-flex align-items-center mb-3">

                    <div class="stat-icon bg-success-soft">
                        <i class="bi bi-graph-up"></i>
                    </div>

                </div>

                <div class="text-muted small">
                    Revenue (30d)
                </div>

                <h2 class="mb-0">
                    <?= number_format(
                        (float) ($revenue30d ?? 0),
                        2
                    ) ?>
                </h2>

            </div>

        </div>

    </div>

</div>


<!-- Recent Activity -->

<div class="card border-0 shadow-sm mt-4">

    <div class="card-body">

        <h6 class="fw-bold mb-3">
            Recent Activity
        </h6>

        <?php if (empty($recentActivity)): ?>

            <p class="text-muted small mb-0">
                No recent activity yet.
            </p>

        <?php else: ?>

            <div class="list-group list-group-flush">

                <?php foreach ($recentActivity as $activity): ?>

                    <div class="list-group-item px-0">

                        <div class="fw-semibold">

                            <?= htmlspecialchars(
                                $activity['description'] ?? ''
                            ) ?>

                        </div>

                        <small class="text-muted">

                            <?= htmlspecialchars(
                                $activity['created_at'] ?? ''
                            ) ?>

                        </small>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';