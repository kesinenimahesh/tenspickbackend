<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * DATABASE CONNECTION
 * ============================================================
 *
 * Database:
 * MySQL
 *
 * Driver:
 * PDO
 *
 * Configuration:
 * .env
 *
 * This file provides one reusable database connection
 * throughout the complete Tenspick application.
 * ============================================================
 */


/* ============================================================
   LOAD APPLICATION BOOTSTRAP
   ============================================================ */

require_once __DIR__ . '/bootstrap.php';


/* ============================================================
   DATABASE CONNECTION FUNCTION
   ============================================================ */

function db(): PDO
{
    /*
     * Keep one PDO connection for the current request.
     *
     * This prevents creating multiple database
     * connections unnecessarily.
     */

    static $pdo = null;


    /*
     * Return existing connection.
     */

    if ($pdo instanceof PDO) {

        return $pdo;
    }


    /* ========================================================
       DATABASE CONFIGURATION
       ======================================================== */

    $host =
        (string) env(
            'DB_HOST',
            '127.0.0.1'
        );


    $port =
        (string) env(
            'DB_PORT',
            '3306'
        );


    $database =
        (string) env(
            'DB_NAME',
            'tenspick_management'
        );


    $username =
        (string) env(
            'DB_USER',
            'root'
        );


    $password =
        (string) env(
            'DB_PASS',
            ''
        );


    /* ========================================================
       VALIDATE REQUIRED CONFIGURATION
       ======================================================== */

    if ($host === '') {

        throw new RuntimeException(
            'Database host is not configured.'
        );
    }


    if ($database === '') {

        throw new RuntimeException(
            'Database name is not configured.'
        );
    }


    if ($username === '') {

        throw new RuntimeException(
            'Database username is not configured.'
        );
    }


    /* ========================================================
       PDO DSN
       ======================================================== */

    $dsn =
        'mysql:' .
        'host=' . $host .
        ';port=' . $port .
        ';dbname=' . $database .
        ';charset=utf8mb4';


    /* ========================================================
       PDO OPTIONS
       ======================================================== */

    $options = [

        /*
         * Throw exceptions when database errors occur.
         */

        PDO::ATTR_ERRMODE =>
            PDO::ERRMODE_EXCEPTION,


        /*
         * Return database rows as associative arrays.
         */

        PDO::ATTR_DEFAULT_FETCH_MODE =>
            PDO::FETCH_ASSOC,


        /*
         * Use native prepared statements.
         *
         * This is important for SQL injection protection.
         */

        PDO::ATTR_EMULATE_PREPARES =>
            false,


        /*
         * Keep connection persistent only for
         * the current PHP request.
         */

        PDO::ATTR_PERSISTENT =>
            false
    ];


    /* ========================================================
       CREATE PDO CONNECTION
       ======================================================== */

    try {

        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            $options
        );

    } catch (PDOException $exception) {

        /*
         * Never expose raw database credentials,
         * DSNs or internal database errors to users.
         *
         * The original error will still be available
         * in the PHP error log through the application's
         * global error handling.
         */

        error_log(
            'Tenspick database connection failed: ' .
            $exception->getMessage()
        );


        throw new RuntimeException(
            'Unable to connect to the database.'
        );
    }


    /* ========================================================
       RETURN CONNECTION
       ======================================================== */

    return $pdo;
}