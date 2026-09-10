<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * APPLICATION BOOTSTRAP
 * ============================================================
 *
 * Responsibilities:
 *
 * 1. Load .env
 * 2. Configure timezone
 * 3. Configure PHP error handling
 * 4. Configure secure sessions
 * 5. Set security headers
 *
 * This file does NOT connect to MySQL.
 * Database connection is handled separately.
 * ============================================================
 */


/* ============================================================
   LOAD ENVIRONMENT CONFIGURATION
   ============================================================ */

require_once __DIR__ . '/env.php';


/*
 * Project root:
 *
 * C:\xampp\htdocs\tenspickk
 *
 * env.php:
 *
 * C:\xampp\htdocs\tenspickk\backend\config\env.php
 *
 * dirname(__DIR__, 2):
 *
 * C:\xampp\htdocs\tenspickk
 */

$projectRoot =
    dirname(__DIR__, 2);


/*
 * .env file.
 */

$envFile =
    $projectRoot .
    DIRECTORY_SEPARATOR .
    '.env';


/*
 * Load environment variables.
 */

loadEnv($envFile);


/* ============================================================
   TIMEZONE
   ============================================================ */

date_default_timezone_set(
    'Asia/Kolkata'
);


/* ============================================================
   APPLICATION DEBUG MODE
   ============================================================ */

$appDebug =
    filter_var(
        env(
            'APP_DEBUG',
            false
        ),
        FILTER_VALIDATE_BOOLEAN
    );


/*
 * Show PHP errors only when debugging
 * is explicitly enabled.
 */

ini_set(
    'display_errors',
    $appDebug ? '1' : '0'
);

ini_set(
    'display_startup_errors',
    $appDebug ? '1' : '0'
);

ini_set(
    'log_errors',
    '1'
);


/* ============================================================
   ERROR LOG LOCATION
   ============================================================ */

$logDirectory =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    'logs';


/*
 * Create log directory if it doesn't exist.
 */

if (!is_dir($logDirectory)) {

    mkdir(
        $logDirectory,
        0755,
        true
    );
}


ini_set(
    'error_log',
    $logDirectory .
    DIRECTORY_SEPARATOR .
    'php-error.log'
);


/* ============================================================
   REMOVE PHP VERSION HEADER
   ============================================================ */

if (function_exists('header_remove')) {

    header_remove(
        'X-Powered-By'
    );
}


/* ============================================================
   SECURITY HEADERS
   ============================================================ */

/*
 * Prevent MIME type sniffing.
 */

header(
    'X-Content-Type-Options: nosniff'
);


/*
 * Prevent the application from being
 * embedded inside an iframe on another site.
 */

header(
    'X-Frame-Options: SAMEORIGIN'
);


/*
 * Control referrer information.
 */

header(
    'Referrer-Policy: strict-origin-when-cross-origin'
);


/*
 * Disable browser features that the
 * management system does not need.
 */

header(
    'Permissions-Policy: geolocation=(), microphone=(), camera=()'
);


/* ============================================================
   CONTENT SECURITY POLICY
   ============================================================ */

/*
 * Tenspick CSP
 *
 * default-src
 * ------------------------------------------------------------
 * Only allow resources from our own origin by default.
 *
 * img-src
 * ------------------------------------------------------------
 * Allow:
 * - Same-origin images
 * - Base64/data images
 *
 * style-src
 * ------------------------------------------------------------
 * Allow:
 * - Same-origin CSS
 * - Inline styles
 * - Bootstrap Icons from jsDelivr
 *
 * script-src
 * ------------------------------------------------------------
 * Allow:
 * - Same-origin JavaScript
 * - Inline JavaScript currently used by the frontend
 *
 * font-src
 * ------------------------------------------------------------
 * Allow:
 * - Same-origin fonts
 * - Data URI fonts
 * - Bootstrap Icons font files from jsDelivr
 *
 * connect-src
 * ------------------------------------------------------------
 * API/AJAX requests are restricted to our own origin.
 *
 * frame-ancestors
 * ------------------------------------------------------------
 * Prevent external websites from embedding Tenspick.
 *
 * base-uri
 * ------------------------------------------------------------
 * Prevent malicious <base> URL manipulation.
 *
 * form-action
 * ------------------------------------------------------------
 * Forms can only submit to our own origin.
 *
 *
 * Bootstrap Icons
 * ------------------------------------------------------------
 * The frontend currently uses:
 *
 * https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/
 *
 * Therefore jsDelivr must be explicitly allowed for:
 *
 * style-src
 * font-src
 */


/*
 * IMPORTANT:
 *
 * Do NOT use:
 *
 * style-src 'self' 'unsafe-inline';
 *
 * because that blocks the Bootstrap Icons CDN.
 *
 * We explicitly allow jsDelivr below.
 */

header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "img-src 'self' data:; " .
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
    "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
    "script-src 'self' 'unsafe-inline'; " .
    "font-src 'self' data: https://cdn.jsdelivr.net; " .
    "connect-src 'self'; " .
    "frame-ancestors 'self'; " .
    "base-uri 'self'; " .
    "form-action 'self';"
);


/* ============================================================
   SESSION CONFIGURATION
   ============================================================ */

/*
 * Read secure-cookie setting from .env.
 *
 * Local XAMPP:
 *
 * SESSION_SECURE=false
 *
 * Production HTTPS:
 *
 * SESSION_SECURE=true
 */

$sessionSecure =
    filter_var(
        env(
            'SESSION_SECURE',
            false
        ),
        FILTER_VALIDATE_BOOLEAN
    );


/*
 * SameSite protection.
 */

$sessionSameSite =
    (string) env(
        'SESSION_SAMESITE',
        'Lax'
    );


/*
 * Allowed SameSite values.
 */

$allowedSameSite = [
    'Lax',
    'Strict',
    'None'
];


if (
    !in_array(
        $sessionSameSite,
        $allowedSameSite,
        true
    )
) {

    $sessionSameSite =
        'Lax';
}


/*
 * Session name.
 */

$sessionName =
    (string) env(
        'SESSION_NAME',
        'tenspick_session'
    );


/*
 * Session lifetime.
 *
 * 0 means browser-session lifetime.
 */

$sessionLifetime =
    (int) env(
        'SESSION_LIFETIME',
        0
    );


/* ============================================================
   CONFIGURE SESSION COOKIE
   ============================================================ */

session_name(
    $sessionName
);


session_set_cookie_params([
    'lifetime' =>
        $sessionLifetime,

    'path' =>
        '/',

    'secure' =>
        $sessionSecure,

    'httponly' =>
        true,

    'samesite' =>
        $sessionSameSite
]);


/* ============================================================
   SESSION SECURITY OPTIONS
   ============================================================ */

/*
 * These settings MUST be configured before
 * session_start().
 */

ini_set(
    'session.use_only_cookies',
    '1'
);

ini_set(
    'session.use_trans_sid',
    '0'
);

ini_set(
    'session.use_strict_mode',
    '1'
);


/* ============================================================
   START SESSION
   ============================================================ */

if (
    session_status() !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


/* ============================================================
   APPLICATION CONSTANTS
   ============================================================ */

if (
    !defined(
        'TENSPICK_APP_NAME'
    )
) {

    define(
        'TENSPICK_APP_NAME',
        (string) env(
            'APP_NAME',
            'Tenspick'
        )
    );
}


if (
    !defined(
        'TENSPICK_ENV'
    )
) {

    define(
        'TENSPICK_ENV',
        (string) env(
            'APP_ENV',
            'local'
        )
    );
}


if (
    !defined(
        'TENSPICK_DEBUG'
    )
) {

    define(
        'TENSPICK_DEBUG',
        $appDebug
    );
}