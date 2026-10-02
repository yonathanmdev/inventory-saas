<?php

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_ENV['TRUST_PROXY'] ?? '0') === '1'
        && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.gc_maxlifetime', '1800');

// __Host- prefix needs HTTPS, so only use it when HTTPS is on
session_name($isHttps ? '__Host-inv_sid' : 'inv_sid');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

$now = time();

// Idle timeout (30 min) and absolute lifetime (8 h)
$idleExpired = isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > 1800;
$absExpired  = isset($_SESSION['created_at'])    && ($now - $_SESSION['created_at'])    > 28800;

if ($idleExpired || $absExpired) {
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

$_SESSION['created_at']    ??= $now;
$_SESSION['last_activity']   = $now;

// Rotate the session id every 15 minutes
if (($now - ($_SESSION['rotated_at'] ?? 0)) > 900) {
    session_regenerate_id(true);
    $_SESSION['rotated_at'] = $now;
}

// One CSRF token per session
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));