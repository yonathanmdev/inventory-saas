<?php

$pageTitle = 'Businesses';

ob_start();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="page-title">
            Businesses
        </h1>

        <p class="page-subtitle">
            Manage businesses registered in the system.
        </p>
    </div>

    <a
        href="/system-admin/businesses/create"
        class="btn btn-primary"
    >
        <i class="bi bi-building-add me-1"></i>
        Register Business
    </a>

</div>


<div class="card shadow-sm">

    <div class="card-header bg-white py-3">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h5 class="mb-1">
                    Registered Businesses
                </h5>

                <small class="text-muted">
                    <?= count($businesses) ?>
                    business<?= count($businesses) === 1 ? '' : 'es' ?>
                </small>

            </div>

        </div>

    </div>


    <div class="card-body">

        <?php if (empty($businesses)): ?>

            <div class="text-center py-5">

                <i
                    class="bi bi-buildings text-muted"
                    style="font-size: 3.5rem;"
                ></i>

                <h5 class="mt-3">
                    No businesses found
                </h5>

                <p class="text-muted mb-4">
                    Register your first business to get started.
                </p>

                <a
                    href="/system-admin/businesses/create"
                    class="btn btn-primary"
                >
                    <i class="bi bi-plus-lg me-1"></i>
                    Register Business
                </a>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table
                    id="businessesTable"
                    class="table table-hover align-middle w-100"
                >

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Business</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Address</th>

                            <th>Status</th>

                            <th>Created</th>

                            <th class="text-end">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($businesses as $business): ?>

                        <tr>

                            <!-- ID -->

                            <td>
                                <?= htmlspecialchars(
                                    (string) $business['id']
                                ) ?>
                            </td>


                            <!-- Business -->

                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $business['name']
                                    ) ?>

                                </div>

                                <?php if (!empty($business['description'])): ?>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            mb_strimwidth(
                                                $business['description'],
                                                0,
                                                60,
                                                '...'
                                            )
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Email -->

                            <td>

                                <a
                                    href="mailto:<?= htmlspecialchars(
                                        $business['email']
                                    ) ?>"
                                    class="text-decoration-none"
                                >

                                    <?= htmlspecialchars(
                                        $business['email']
                                    ) ?>

                                </a>

                            </td>


                            <!-- Phone -->

                            <td>

                                <?php if (!empty($business['phone'])): ?>

                                    <?= htmlspecialchars(
                                        $business['phone']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Address -->

                            <td>

                                <?php if (!empty($business['address'])): ?>

                                    <?= htmlspecialchars(
                                        $business['address']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <?php if (
                                    (int) $business['is_active'] === 1
                                ): ?>

                                    <span class="badge text-bg-success">

                                        <i
                                            class="bi bi-check-circle me-1"
                                        ></i>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge text-bg-secondary">

                                        <i
                                            class="bi bi-pause-circle me-1"
                                        ></i>

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Created -->

                            <td
                                data-order="<?= htmlspecialchars(
                                    $business['created_at'] ?? ''
                                ) ?>"
                            >

                                <?php if (
                                    !empty($business['created_at'])
                                ): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $business['created_at']
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- Actions -->

                            <td class="text-end">

                                <div class="btn-group btn-group-sm">

                                    <a
                                        href="#"
                                        class="btn btn-outline-primary"
                                        title="View business"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
    href="/system-admin/businesses/<?= $business['uuid'] ?>/edit"
    class="btn btn-sm btn-outline-primary"
    title="Edit Business"
>
    <i class="bi bi-pencil"></i>
</a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/dashboard.php';

?>