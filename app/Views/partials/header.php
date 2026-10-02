<header class="app-header d-flex align-items-center justify-content-between">
<style nonce="<?= htmlspecialchars(
    \App\Helpers\Csp::nonce(),
    ENT_QUOTES,
    'UTF-8'
) ?>">
    .dropdown-menu.dropdown-menu-end.shadow-sm {
        min-width: 280px;
    }

    .account-btn.dropdown-toggle {
        list-style: none;
    }
</style>

    <div class="d-flex align-items-center">
        <button class="sidebar-toggle-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
            <i class="bi bi-list"></i>
        </button>
        <a href="/dashboard" class="brand">
            <span class="brand-icon">IMS</span>
            <span class="d-none d-sm-inline">Inventory Management System</span>
        </a>
    </div>

    <div class="d-flex align-items-center gap-2">

        <span class="business-pill d-none d-md-inline">
            <?= htmlspecialchars($_SESSION['business_name'] ?? '') ?>
        </span>

        <div class="dropdown">
            <button class="header-icon-btn" data-bs-toggle="dropdown">
                <i class="bi bi-bell"></i>
                <?php if (!empty($lowStockCount)): ?>
                    <span class="header-badge"><?= (int) $lowStockCount ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <?php if (!empty($lowStockCount)): ?>
                    <li>
                        <a class="dropdown-item" href="/products?filter=low_stock">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            <?= (int) $lowStockCount ?> product(s) low on stock
                        </a>
                    </li>
                <?php else: ?>
                    <li><span class="dropdown-item-text text-muted small">You're all caught up</span></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="dropdown">
            <button class="account-btn dropdown-toggle" data-bs-toggle="dropdown">
                <span class="avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></span>
                <span class="d-none d-md-flex flex-column align-items-start">
                    <span class="account-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Account') ?></span>
                    <span class="account-role"><?= htmlspecialchars($_SESSION['role_name'] ?? '') ?></span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="/settings"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
    <form method="POST" action="/logout" class="d-inline w-100">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <button type="submit" class="dropdown-item text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>
            Logout
        </button>
    </form>
</li>
            </ul>
        </div>
    </div>
</header>