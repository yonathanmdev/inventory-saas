<?php

use App\Helpers\PermissionHelper;
use App\Helpers\AuthHelper;

$pageTitle = 'Users';
$activeNav = 'users';

$isSystemAdmin = AuthHelper::isSystemAdmin();
$basePath = $isSystemAdmin ? '/system-admin/users' : '/users';
$users = $users ?? [];

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle">Manage user accounts and their status.</p>
    </div>

    <?php if (PermissionHelper::can('users.create')): ?>
        <a href="<?= $basePath ?>/create" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Register User
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-1">Registered Users</h5>
        <small class="text-muted">
            <?= count($users) ?> user<?= count($users) === 1 ? '' : 's' ?>
        </small>
    </div>

    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="mt-3">No users found</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <?php if ($isSystemAdmin): ?>
                                <th>Business</th>
                            <?php endif; ?>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="fw-semibold">
                                <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>

                            <?php if ($isSystemAdmin): ?>
                                <td>
                                    <?php if (!empty($user['business_name'])): ?>
                                        <?= htmlspecialchars($user['business_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="text-muted">System level</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <td>
                                <?php if ((int) $user['is_system_admin'] === 1): ?>
                                    <span class="badge text-bg-dark">System Admin</span>
                                <?php elseif (!empty($user['role_name'])): ?>
                                    <?= htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= !empty($user['last_login_at'])
                                    ? htmlspecialchars($user['last_login_at'], ENT_QUOTES, 'UTF-8')
                                    : '<span class="text-muted">Never</span>' ?>
                            </td>

                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= $basePath ?>/<?= urlencode($user['uuid']) ?>"
                                       class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <!-- activate/deactivate: see note below -->
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