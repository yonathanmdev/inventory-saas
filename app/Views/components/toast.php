<?php

use App\Helpers\FlashHelper;

$flash = FlashHelper::get();

if ($flash):
?>

<div
    id="appToast"
    class="toast-container position-fixed top-0 end-0 p-3"
    style="z-index: 9999;"
>
    <div
        class="toast align-items-center border-0 shadow"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >

        <div class="d-flex">

            <div class="toast-body">

                <?php if ($flash['type'] === 'success'): ?>

                    <i class="bi bi-check-circle-fill text-success me-2"></i>

                <?php elseif ($flash['type'] === 'error'): ?>

                    <i class="bi bi-x-circle-fill text-danger me-2"></i>

                <?php elseif ($flash['type'] === 'warning'): ?>

                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>

                <?php else: ?>

                    <i class="bi bi-info-circle-fill text-info me-2"></i>

                <?php endif; ?>

                <?= htmlspecialchars($flash['message']) ?>

            </div>

            <button
                type="button"
                class="btn-close me-2 m-auto"
                data-bs-dismiss="toast"
                aria-label="Close"
            ></button>

        </div>

    </div>
</div>

<?php endif; ?>