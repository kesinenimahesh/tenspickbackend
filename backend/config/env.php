<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * ENVIRONMENT CONFIGURATION LOADER
 * ============================================================
 *
 * Loads configuration values from:
 *
 * C:\xampp\htdocs\tenspickk\.env
 *
 * This file does not connect to the database.
 * It only loads environment configuration.
 * ============================================================
 */


/* ============================================================
   LOAD ENVIRONMENT FILE
   ============================================================ */

function loadEnv(string $filePath): void
{
    /*
     * Make sure the .env file exists.
     */

    if (!is_file($filePath)) {

        throw new RuntimeException(
            '.env file is missing. Expected location: ' . $filePath
        );
    }


    /*
     * Read .env file.
     */

    $lines = file(
        $filePath,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );


    if ($lines === false) {

        throw new RuntimeException(
            'Unable to read the .env file.'
        );
    }


    /*
     * Process each line.
     */

    foreach ($lines as $line) {

        $line = trim($line);


        /*
         * Ignore empty lines.
         */

        if ($line === '') {
            continue;
        }


        /*
         * Ignore comments.
         */

        if (substr($line, 0, 1) === '#') {
            continue;
        }


        /*
         * Every environment value must contain "=".
         */

        if (strpos($line, '=') === false) {
            continue;
        }


        /*
         * Separate key and value.
         */

        list($key, $value) =
            explode('=', $line, 2);


        $key = trim($key);
        $value = trim($value);


        /*
         * Ignore invalid empty keys.
         */

        if ($key === '') {
            continue;
        }


        /*
         * Remove surrounding quotes.
         *
         * Example:
         *
         * APP_NAME="Tenspick"
         *
         * becomes:
         *
         * Tenspick
         */

        if (strlen($value) >= 2) {

            $firstCharacter =
                substr($value, 0, 1);

            $lastCharacter =
                substr($value, -1);


            if (
                (
                    $firstCharacter === '"' &&
                    $lastCharacter === '"'
                )
                ||
                (
                    $firstCharacter === "'" &&
                    $lastCharacter === "'"
                )
            ) {

                $value =
                    substr(
                        $value,
                        1,
                        -1
                    );
            }
        }


        /*
         * Store in PHP environment.
         */

        $_ENV[$key] = $value;


        /*
         * Also store in getenv().
         *
         * This makes the configuration compatible
         * with other PHP libraries if needed later.
         */

        putenv(
            $key . '=' . $value
        );
    }
}


/* ============================================================
   GET ENVIRONMENT VALUE
   ============================================================ */

function env(string $key, $default = null)
{
    /*
     * First check $_ENV.
     */

    if (isset($_ENV[$key])) {

        return $_ENV[$key];
    }


    /*
     * Then check getenv().
     */

    $value = getenv($key);


    if ($value !== false) {

        return $value;
    }


    /*
     * Return default value.
     */

    return $default;
}