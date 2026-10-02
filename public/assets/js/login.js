document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');

    if (toggle && passwordInput) {
        toggle.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggle.textContent = isPassword ? 'Hide' : 'Show';
            toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }

    if (loginForm && loginButton) {
        loginForm.addEventListener('submit', function () {
            loginButton.classList.add('loading');
            loginButton.disabled = true;
        });
    }
});