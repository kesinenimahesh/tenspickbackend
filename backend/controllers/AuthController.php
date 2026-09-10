<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * AUTHENTICATION CONTROLLER
 * ============================================================
 *
 * Internal authentication:
 *
 * ADMIN ONLY
 *
 * Handles:
 *
 * - Admin login
 * - Admin logout
 *
 * Security:
 *
 * - CSRF validation
 * - Input validation
 * - Prepared statements
 * - Password verification
 * - Session regeneration
 * - Activity logging
 * - No password returned in API response
 * ============================================================
 */


/* ============================================================
   LOGIN
   ============================================================ */

function adminLogin(): void
{
    /*
     * Login changes authentication state,
     * therefore CSRF protection is required.
     */

    requireCsrfToken();


    /*
     * Read JSON request body.
     */

    $input = getJsonInput();


    /*
     * Read email.
     */

    $email = isset($input['email'])
        ? trim((string) $input['email'])
        : '';


    /*
     * Read password.
     */

    $password = isset($input['password'])
        ? (string) $input['password']
        : '';


    /*
     * Validate email.
     */

    if ($email === '') {

        validationResponse([
            'email' => 'Email address is required.'
        ]);
    }


    if (
        strlen($email) > 190 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        validationResponse([
            'email' => 'Enter a valid email address.'
        ]);
    }


    /*
     * Validate password.
     */

    if ($password === '') {

        validationResponse([
            'password' => 'Password is required.'
        ]);
    }


    if (strlen($password) < 8) {

        validationResponse([
            'password' =>
                'Password must contain at least 8 characters.'
        ]);
    }


    /*
     * Connect to database.
     */

    try {

        $pdo = db();

    } catch (Throwable $exception) {

        error_log(
            'Tenspick login database error: ' .
            $exception->getMessage()
        );

        serverErrorResponse(
            'Unable to process login at this time.'
        );
    }


    /* ========================================================
       FIND ADMIN
       ======================================================== */

    try {

        $statement = $pdo->prepare(
            "
            SELECT
                id,
                name,
                email,
                password_hash,
                status
            FROM admins
            WHERE email = :email
            LIMIT 1
            "
        );


        $statement->execute([
            ':email' => $email
        ]);


        $admin =
            $statement->fetch();

    } catch (Throwable $exception) {

        error_log(
            'Tenspick admin lookup failed: ' .
            $exception->getMessage()
        );

        serverErrorResponse(
            'Unable to process login at this time.'
        );
    }


    /* ========================================================
       INVALID CREDENTIALS
       ======================================================== */

    /*
     * Use the same response for:
     *
     * - Unknown email
     * - Wrong password
     *
     * This prevents revealing whether an email exists.
     */

    if (
        !$admin ||
        !isset($admin['password_hash']) ||
        !verifyPassword(
            $password,
            (string) $admin['password_hash']
        )
    ) {

        errorResponse(
            'Invalid email or password.',
            null,
            401
        );
    }


    /* ========================================================
       CHECK ADMIN STATUS
       ======================================================== */

    if (
        !isset($admin['status']) ||
        (int) $admin['status'] !== 1
    ) {

        errorResponse(
            'This admin account is inactive.',
            null,
            403
        );
    }


    /* ========================================================
       REHASH PASSWORD WHEN NECESSARY
       ======================================================== */

    if (
        passwordNeedsRehash(
            (string) $admin['password_hash']
        )
    ) {

        try {

            $newHash =
                hashPassword($password);


            $updatePassword =
                $pdo->prepare(
                    "
                    UPDATE admins
                    SET password_hash = :password_hash
                    WHERE id = :id
                    "
                );


            $updatePassword->execute([
                ':password_hash' => $newHash,
                ':id' => (int) $admin['id']
            ]);

        } catch (Throwable $exception) {

            /*
             * Rehash failure should not prevent a valid
             * admin from logging in.
             */

            error_log(
                'Tenspick password rehash failed: ' .
                $exception->getMessage()
            );
        }
    }


    /* ========================================================
       CREATE ADMIN SESSION
       ======================================================== */

    loginAdmin($admin);


    /* ========================================================
       UPDATE LAST LOGIN
       ======================================================== */

    try {

        $updateLogin =
            $pdo->prepare(
                "
                UPDATE admins
                SET last_login_at = NOW()
                WHERE id = :id
                "
            );


        $updateLogin->execute([
            ':id' => (int) $admin['id']
        ]);

    } catch (Throwable $exception) {

        /*
         * Login is already successful.
         * A last-login timestamp failure must not
         * break authentication.
         */

        error_log(
            'Tenspick last login update failed: ' .
            $exception->getMessage()
        );
    }


    /* ========================================================
       ACTIVITY LOG
       ======================================================== */

    logAuthenticationActivity(
        $pdo,
        'admin_login',
        (int) $admin['id'],
        'Admin logged into the Tenspick management system.'
    );


    /* ========================================================
       RESPONSE
       ======================================================== */

    successResponse(
        'Login successful.',
        [
            'admin' => [
                'id' =>
                    (int) $admin['id'],

                'name' =>
                    (string) $admin['name'],

                'email' =>
                    (string) $admin['email']
            ]
        ]
    );
}


/* ============================================================
   LOGOUT
   ============================================================ */

function adminLogout(): void
{
    /*
     * The admin must be authenticated.
     */

    $admin =
        requireAdminAuthentication();


    /*
     * Database connection.
     */

    try {

        $pdo = db();

    } catch (Throwable $exception) {

        error_log(
            'Tenspick logout database error: ' .
            $exception->getMessage()
        );

        /*
         * We can still safely destroy the session.
         */

        logoutAdmin();

        successResponse(
            'Logout successful.'
        );
    }


    /*
     * Store admin ID before destroying session.
     */

    $adminId =
        isset($admin['id'])
            ? (int) $admin['id']
            : null;


    /*
     * Record logout activity before
     * destroying the session.
     */

    logAuthenticationActivity(
        $pdo,
        'admin_logout',
        $adminId,
        'Admin logged out of the Tenspick management system.'
    );


    /*
     * Destroy admin session.
     */

    logoutAdmin();


    /*
     * Return response.
     */

    successResponse(
        'Logout successful.'
    );
}


/* ============================================================
   CURRENT ADMIN
   ============================================================ */

function currentAdmin(): void
{
    /*
     * Require authenticated admin.
     */

    $admin =
        requireAdminAuthentication();


    /*
     * Return safe session data only.
     */

    successResponse(
        'Authenticated admin.',
        [
            'admin' => [
                'id' =>
                    (int) $admin['id'],

                'name' =>
                    (string) $admin['name'],

                'email' =>
                    (string) $admin['email']
            ]
        ]
    );
}