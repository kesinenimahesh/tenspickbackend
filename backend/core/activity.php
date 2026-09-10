<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * ACTIVITY LOG CORE
 * ============================================================
 *
 * This file provides the common activity logging function
 * for the complete Tenspick system.
 *
 * Examples:
 *
 * - Admin login
 * - Admin logout
 * - Lead created
 * - Lead updated
 * - Lead deleted
 * - Client created
 * - Project updated
 * - Staff assigned to task
 * - Payment added
 * - Client chat activity
 *
 * Database table:
 *
 * activity_logs
 *
 * Database schema will be created later.
 * ============================================================
 */


/* ============================================================
   RECORD ACTIVITY
   ============================================================ */

function recordActivity(
    PDO $pdo,
    string $action,
    string $module,
    ?int $userId = null,
    ?string $description = null,
    ?int $recordId = null
): bool {

    /*
     * Validate action.
     */

    $action = trim($action);

    if ($action === '') {
        return false;
    }


    /*
     * Validate module.
     */

    $module = trim($module);

    if ($module === '') {
        return false;
    }


    /*
     * Get IP address.
     */

    $ipAddress =
        clientIp();


    /*
     * Get browser/user-agent.
     */

    $userAgent =
        clientUserAgent();


    /*
     * Limit values before database insertion.
     *
     * The database will also enforce its own limits.
     */

    $action =
        substr($action, 0, 100);

    $module =
        substr($module, 0, 100);

    $description =
        $description !== null
            ? substr(trim($description), 0, 1000)
            : null;

    $ipAddress =
        substr($ipAddress, 0, 45);

    $userAgent =
        substr($userAgent, 0, 500);


    /* ========================================================
       INSERT ACTIVITY
       ======================================================== */

    try {

        $statement = $pdo->prepare(
            "
            INSERT INTO activity_logs
            (
                user_id,
                action,
                module,
                record_id,
                description,
                ip_address,
                user_agent,
                created_at
            )
            VALUES
            (
                :user_id,
                :action,
                :module,
                :record_id,
                :description,
                :ip_address,
                :user_agent,
                NOW()
            )
            "
        );


        $statement->bindValue(
            ':user_id',
            $userId,
            $userId === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_INT
        );


        $statement->bindValue(
            ':action',
            $action,
            PDO::PARAM_STR
        );


        $statement->bindValue(
            ':module',
            $module,
            PDO::PARAM_STR
        );


        $statement->bindValue(
            ':record_id',
            $recordId,
            $recordId === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_INT
        );


        $statement->bindValue(
            ':description',
            $description,
            $description === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_STR
        );


        $statement->bindValue(
            ':ip_address',
            $ipAddress,
            PDO::PARAM_STR
        );


        $statement->bindValue(
            ':user_agent',
            $userAgent,
            PDO::PARAM_STR
        );


        return $statement->execute();

    } catch (Throwable $exception) {

        /*
         * Activity logging must never expose database errors
         * to the user or break the main operation.
         */

        error_log(
            'Tenspick activity log failed: ' .
            $exception->getMessage()
        );

        return false;
    }
}


/* ============================================================
   LOG AUTHENTICATION ACTIVITY
   ============================================================ */

function logAuthenticationActivity(
    PDO $pdo,
    string $action,
    ?int $userId = null,
    ?string $description = null
): bool {

    return recordActivity(
        $pdo,
        $action,
        'authentication',
        $userId,
        $description
    );
}


/* ============================================================
   LOG MODULE ACTIVITY
   ============================================================ */

function logModuleActivity(
    PDO $pdo,
    string $module,
    string $action,
    ?int $recordId = null,
    ?int $userId = null,
    ?string $description = null
): bool {

    /*
     * If no user ID is explicitly provided,
     * use the currently authenticated user.
     */

    if ($userId === null) {

        $userId =
            authenticatedUserId();
    }


    return recordActivity(
        $pdo,
        $action,
        $module,
        $userId,
        $description,
        $recordId
    );
}