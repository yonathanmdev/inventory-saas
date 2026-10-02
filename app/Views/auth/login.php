<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$oldUsername = $_SESSION['old_username'] ?? '';
unset($_SESSION['old_username']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >
<link rel="stylesheet" href="/assets/css/login.css">
    <title>Sign In | Property Management</title>
</head>

<body>

<main class="login-wrapper">

    <section class="login-card">

        <!-- Brand -->
        <aside class="brand-panel">

            <div class="brand-content">

                <div class="brand-logo" aria-hidden="true">
                    IMS
                </div>

                <h2 class="brand-title">
                   Inventory Management System
                </h2>

                <p class="brand-description">
                    Manage your inventory, products, stock and
                    business operations from one simple platform.
                </p>

                <div class="brand-features">

                    <div class="feature">
                        <span class="feature-icon">✓</span>
                        <span>Centralized inventory management</span>
                    </div>

                    <div class="feature">
                        <span class="feature-icon">✓</span>
                        <span>Real-time stock visibility</span>
                    </div>

                    <div class="feature">
                        <span class="feature-icon">✓</span>
                        <span>Secure business access</span>
                    </div>

                </div>

            </div>

            <div class="brand-footer">
                Inventory management made simple.
            </div>

        </aside>

        <!-- Login -->
        <section class="form-panel">

            <div class="form-container">

                <header class="form-header">

                    <h1>
                        Welcome back
                    </h1>

                    <p>
                        Sign in to continue to your account.
                    </p>

                </header>

                <?php if ($error): ?>

                    <div
                        class="alert"
                        role="alert"
                        aria-live="polite"
                    >
                        <span class="alert-icon">!</span>

                        <span>
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    action="/login"
                    id="loginForm"
                    novalidate
                >
<?php $csrfToken = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token'] = bin2hex(random_bytes(32))); ?>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="username"
                        >
                            Username
                        </label>

                        <div class="input-wrapper">

                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <path
                                    d="M20 21a8 8 0 0 0-16 0"
                                />
                                <circle
                                    cx="12"
                                    cy="7"
                                    r="4"
                                />
                            </svg>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                value="<?= htmlspecialchars($oldUsername, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Enter your username"
                                autocomplete="username"
                                required
                                autofocus
                            >

                        </div>

                    </div>

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="password"
                        >
                            Password
                        </label>

                        <div class="input-wrapper">

                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="11"
                                    rx="2"
                                />
                                <path
                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                />
                            </svg>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input password-input"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                Show
                            </button>

                        </div>

                    </div>

                    <div class="form-options">

                        <label class="remember">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >

                            <span>
                                Remember me
                            </span>
                        </label>

                        <a
                            href="/forgot-password"
                            class="forgot-link"
                        >
                            Forgot password?
                        </a>

                    </div>

                    <button
                        type="submit"
                        class="login-button"
                        id="loginButton"
                    >
                        <span
                            class="button-spinner"
                            aria-hidden="true"
                        ></span>

                        <span class="button-text">
                            Sign in
                        </span>
                    </button>

                </form>

               <div class="form-footer">
    © <?= date('Y') ?> Inventory Management System.
    <span class="text-secondary font-weight-normal">
        Developed by
        <a href="#" class="font-weight-bold developer-link">Warka Hub</a>
    </span>
</div>

            </div>

        </section>

    </section>

</main>

<script src="/assets/js/login.js" defer></script>
</body>
</html>