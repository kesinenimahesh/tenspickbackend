<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * ADMIN SECURITY CORE
 * ============================================================
 *
 * Internal authentication:
 *
 * ADMIN ONLY
 *
 * There are no:
 *
 * - Roles
 * - Role IDs
 * - Permission tables
 * - Staff login roles
 *
 * Client authentication will be handled separately
 * when the Client Portal module is developed.
 * ============================================================
 */


/* ============================================================
   CSRF TOKEN
   ============================================================ */

/**
 * Return the current CSRF token.
 *
 * A new cryptographically secure token is generated
 * when one does not already exist.
 */
function getCsrfToken(): string
{
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        strlen($_SESSION['csrf_token']) !== 64
    ) {
        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


/* ============================================================
   CSRF VALIDATION
   ============================================================ */

/**
 * Validate the CSRF token sent by the frontend.
 */
function validateCsrfToken(?string $token): bool
{
    if (
        $token === null ||
        $token === ''
    ) {
        return false;
    }


    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        return false;
    }


    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


/* ============================================================
   REQUIRE CSRF
   ============================================================ */

/**
 * Require a valid CSRF token for state-changing requests.
 */
function requireCsrfToken(): void
{
    $enabled = filter_var(
        env('CSRF_ENABLED', true),
        FILTER_VALIDATE_BOOLEAN
    );


    /*
     * CSRF protection can be disabled only explicitly
     * through the environment configuration.
     */

    if (!$enabled) {
        return;
    }


    /*
     * Read token from HTTP header.
     */

    $token = null;


    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {

        $token =
            trim(
                (string) $_SERVER['HTTP_X_CSRF_TOKEN']
            );
    }


    /*
     * Validate.
     */

    if (!validateCsrfToken($token)) {

        errorResponse(
            'Invalid or expired security token.',
            null,
            419
        );
    }
}


/* ============================================================
   ADMIN AUTHENTICATION CHECK
   ============================================================ */

/**
 * Determine whether an admin is logged in.
 */
function isAdminAuthenticated(): bool
{
    return isset($_SESSION['admin'])
        && is_array($_SESSION['admin'])
        && isset($_SESSION['admin']['id'])
        && (int) $_SESSION['admin']['id'] > 0;
}


/* ============================================================
   GET AUTHENTICATED ADMIN
   ============================================================ */

/**
 * Return the currently authenticated admin.
 */
function authenticatedAdmin(): ?array
{
    if (!isAdminAuthenticated()) {
        return null;
    }

    return $_SESSION['admin'];
}


/* ============================================================
   REQUIRE ADMIN AUTHENTICATION
   ============================================================ */

/**
 * Stop the request when an admin is not authenticated.
 */
function requireAdminAuthentication(): array
{
    if (!isAdminAuthenticated()) {

        unauthorizedResponse(
            'Admin authentication is required.'
        );
    }


    return $_SESSION['admin'];
}


/* ============================================================
   ADMIN ID
   ============================================================ */

/**
 * Return the authenticated admin ID.
 */
function authenticatedAdminId(): ?int
{
    if (!isAdminAuthenticated()) {
        return null;
    }


    return isset($_SESSION['admin']['id'])
        ? (int) $_SESSION['admin']['id']
        : null;
}


/* ============================================================
   ADMIN LOGIN
   ============================================================ */

/**
 * Create the authenticated admin session.
 *
 * Only safe admin information is stored.
 *
 * Passwords and password hashes are NEVER stored
 * inside the session.
 */
function loginAdmin(array $admin): void
{
    /*
     * Regenerate session ID after authentication.
     *
     * Protects against session fixation.
     */

    session_regenerate_id(true);


    /*
     * Store only required admin information.
     */

    $_SESSION['admin'] = [

        'id' =>
            isset($admin['id'])
            ? (int) $admin['id']
            : 0,

        'name' =>
            isset($admin['name'])
            ? (string) $admin['name']
            : '',

        'email' =>
            isset($admin['email'])
            ? (string) $admin['email']
            : ''
    ];


    /*
     * Generate a fresh CSRF token after login.
     */

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));


    /*
     * Record authentication time.
     */

    $_SESSION['admin_authenticated_at'] =
        time();
}


/* ============================================================
   ADMIN LOGOUT
   ============================================================ */

/**
 * Completely destroy the admin session.
 */
function logoutAdmin(): void
{
    /*
     * Remove all session values.
     */

    $_SESSION = [];


    /*
     * Remove session cookie.
     */

    if (
        ini_get('session.use_cookies')
    ) {

        $params =
            session_get_cookie_params();


        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            '',
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }


    /*
     * Destroy server-side session.
     */

    session_destroy();
}


/* ============================================================
   PASSWORD HASH
   ============================================================ */

/**
 * Create a secure password hash.
 */
function hashPassword(string $password): string
{
    return password_hash(
        $password,
        PASSWORD_DEFAULT
    );
}


/* ============================================================
   PASSWORD VERIFY
   ============================================================ */

/**
 * Verify a password against its stored hash.
 */
function verifyPassword(
    string $password,
    string $passwordHash
): bool {

    return password_verify(
        $password,
        $passwordHash
    );
}


/* ============================================================
   PASSWORD REHASH
   ============================================================ */

/**
 * Determine whether the password hash should be upgraded.
 */
function passwordNeedsRehash(
    string $passwordHash
): bool {

    return password_needs_rehash(
        $passwordHash,
        PASSWORD_DEFAULT
    );
}


/* ============================================================
   CLIENT IP
   ============================================================ */

/**
 * Return the request IP address.
 *
 * We intentionally use REMOTE_ADDR directly instead of
 * blindly trusting forwarded headers.
 */
function clientIp(): string
{
    return isset($_SERVER['REMOTE_ADDR'])
        ? (string) $_SERVER['REMOTE_ADDR']
        : '0.0.0.0';
}


/* ============================================================
   USER AGENT
   ============================================================ */

/**
 * Return a limited user-agent string for activity logging.
 */
function clientUserAgent(): string
{
    $userAgent =
        isset($_SERVER['HTTP_USER_AGENT'])
        ? (string) $_SERVER['HTTP_USER_AGENT']
        : '';


    return substr(
        $userAgent,
        0,
        500
    );
}


/* ============================================================
   REQUEST METHOD
   ============================================================ */

/**
 * Return the current HTTP request method.
 */
function requestMethod(): string
{
    return strtoupper(
        isset($_SERVER['REQUEST_METHOD'])
        ? (string) $_SERVER['REQUEST_METHOD']
        : 'GET'
    );
}


/* ============================================================
   JSON REQUEST BODY
   ============================================================ */

/**
 * Read and decode a JSON request body.
 *
 * APIs will use this instead of directly trusting
 * raw POST variables.
 */
function getJsonInput(): array
{
    $rawBody =
        file_get_contents('php://input');


    if ($rawBody === false) {
        return [];
    }


    $rawBody =
        trim($rawBody);


    if ($rawBody === '') {
        return [];
    }


    $data =
        json_decode(
            $rawBody,
            true
        );


    if (
        !is_array($data)
    ) {

        errorResponse(
            'Invalid JSON request.',
            null,
            400
        );
    }


    return $data;
}