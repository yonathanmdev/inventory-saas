document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-submit]').forEach((el) => {
        el.addEventListener('change', () => el.form.submit());
    });

    document.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-print]')) window.print();
});
});