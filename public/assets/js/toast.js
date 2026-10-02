document.addEventListener('DOMContentLoaded', function () {

    const toastElement = document.getElementById('appToast');

    if (!toastElement) {
        return;
    }

    const toast = new bootstrap.Toast(toastElement.querySelector('.toast'), {
        autohide: true,
        delay: 4000
    });

    toast.show();

});