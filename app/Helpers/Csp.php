<?php

namespace App\Helpers;

class Csp
{
    /**
     * Generate and return the CSP nonce for the current request.
     *
     * The same nonce is reused during the request so that
     * views can use it on their inline <script> tags.
     */
    public static function nonce(): string
    {
        if (!isset($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = base64_encode(
                random_bytes(32)
            );
        }

        return $_SESSION['csp_nonce'];
    }
}
