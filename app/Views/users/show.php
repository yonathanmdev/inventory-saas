<?php

use App\Helpers\AuthHelper;
use App\Helpers\PermissionHelper;

$pageTitle = 'User Details';
$activeNav = 'users';

$isSystemAdmin = AuthHelper::isSystemAdmin();

$listUrl = $isSystemAdmin
    ? '/system-admin/users'
    : '/users';

$editUrl = $isSystemAdmin
    ? '/system-admin/users/' . (int) $user['id'] . '/edit'
    : '/users/' . (int) $user['id'] . '/edit';

ob_start();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">
            User Details
        </h1>

        <p class="page-subtitle">
            View account and profile information.
        </p>
    </div>

    <div class="d-flex gap-2">

        <a
            href="<?= htmlspecialchars(
                $listUrl,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Back to Users
        </a>

        <?php if (PermissionHelper::can('users.update')): ?>

            <a
                href="<?= htmlspecialchars(
                    $editUrl,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="btn btn-primary"
            >
                <i class="bi bi-pencil me-1"></i>
                Edit User
            </a>

        <?php endif; ?>

    </div>

</div>


<div class="row g-4">

    <!-- Profile -->

    <div class="col-lg-4">

        <div class="card shadow-sm h-100">

            <div class="card-body text-center py-4">

                <div
                    class="rounded-circle bg-primary-subtle text-primary
                           d-inline-flex align-items-center
                           justify-content-center mb-3"
                    style="width: 90px; height: 90px; font-size: 2rem;"
                >
                    <?= htmlspecialchars(
                        strtoupper(
                            mb_substr(
                                $user['full_name'] ?? 'U',
                                0,
                                1
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <h4 class="mb-1">
                    <?= htmlspecialchars(
                        $user['full_name'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h4>

                <p class="text-muted mb-3">
                    @<?= htmlspecialchars(
                        $user['username'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>


                <?php if (!empty($user['is_system_admin'])): ?>

                    <span class="badge text-bg-primary">
                        <i class="bi bi-shield-check me-1"></i>
                        System Administrator
                    </span>

                <?php elseif (!empty($user['role_name'])): ?>

                    <span class="badge text-bg-light border">
                        <i class="bi bi-shield me-1"></i>
                        <?= htmlspecialchars(
                            $user['role_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                <?php endif; ?>


                <hr class="my-4">


                <?php if ((int) $user['is_active'] === 1): ?>

                    <span class="badge text-bg-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Active
                    </span>

                <?php else: ?>

                    <span class="badge text-bg-secondary">
                        <i class="bi bi-pause-circle me-1"></i>
                        Inactive
                    </span>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- Account information -->

    <div class="col-lg-8">

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-white py-3">

                <h5 class="mb-0">
                    <i class="bi bi-person me-2"></i>
                    Account Information
                </h5>

            </div>


            <div class="card-body">

                <div class="row g-4">

                    <div class="col-md-6">

                        <small class="text-muted d-block mb-1">
                            Full Name
                        </small>

                        <div class="fw-semibold">
                            <?= htmlspecialchars(
                                $user['full_name'] ?? '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                    </div>


                    <div class="col-md-6">

                        <small class="text-muted d-block mb-1">
                            Username
                        </small>

                        <div class="fw-semibold">
                            <?= htmlspecialchars(
                                $user['username'] ?? '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                    </div>


                    <div class="col-md-6">

                        <small class="text-muted d-block mb-1">
                            Email
                        </small>

                        <?php if (!empty($user['email'])): ?>

                            <a
                                href="mailto:<?= htmlspecialchars(
                                    $user['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="text-decoration-none"
                            >
                                <?= htmlspecialchars(
                                    $user['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </a>

                        <?php else: ?>

                            <span class="text-muted">—</span>

                        <?php endif; ?>

                    </div>


                    <div class="col-md-6">

                        <small class="text-muted d-block mb-1">
                            Role
                        </small>

                        <div>
                            <?php if (!empty($user['role_name'])): ?>

                                <span class="badge text-bg-light border">
                                    <i class="bi bi-shield me-1"></i>

                                    <?= htmlspecialchars(
                                        $user['role_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            <?php else: ?>

                                <span class="text-muted">—</span>

                            <?php endif; ?>
                        </div>

                    </div>


                    <?php if ($isSystemAdmin): ?>

                        <div class="col-md-6">

                            <small class="text-muted d-block mb-1">
                                Business
                            </small>

                            <?php if (!empty($user['business_name'])): ?>

                                <div class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $user['business_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            <?php else: ?>

                                <span class="badge text-bg-primary">
                                    System Level
                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>


                    <div class="col-md-6">

                        <small class="text-muted d-block mb-1">
                            Account Status
                        </small>

                        <?php if ((int) $user['is_active'] === 1): ?>

                            <span class="badge text-bg-success">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="badge text-bg-secondary">
                                Inactive
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- Account dates -->

        <div class="card shadow-sm">

            <div class="card-header bg-white py-3">

                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Account Activity
                </h5>

            </div>


            <div class="card-body">

                <div class="row g-4">

                    <div class="col-md-4">

                        <small class="text-muted d-block mb-1">
                            Created
                        </small>

                        <div class="fw-semibold">

                            <?php if (!empty($user['created_at'])): ?>

                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y H:i',
                                        strtotime($user['created_at'])
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <small class="text-muted d-block mb-1">
                            Last Login
                        </small>

                        <div class="fw-semibold">

                            <?php if (!empty($user['last_login_at'])): ?>

                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y H:i',
                                        strtotime($user['last_login_at'])
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Never
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <small class="text-muted d-block mb-1">
                            User ID
                        </small>

                        <div class="fw-semibold">
                            #<?= $user['uuid'] ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>