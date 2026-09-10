<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * INITIAL ADMIN CREATOR
 * ============================================================
 *
 * Run this file ONCE from the command line:
 *
 * php database/seed_admin.php
 *
 * It creates the first admin account.
 *
 * IMPORTANT:
 * This file is for initial/local setup.
 * After creating the admin, remove this file or keep it
 * outside the public web directory.
 * ============================================================
 */


/* ============================================================
   LOAD CONFIGURATION
   ============================================================ */

require_once __DIR__ . '/../backend/config/bootstrap.php';

require_once __DIR__ . '/../backend/config/database.php';

require_once __DIR__ . '/../backend/core/security.php';


/* ============================================================
   ADMIN DETAILS
   ============================================================ */

$name = 'Tenspick Admin';

$email = 'admin@tenspick.local';

$password = 'ChangeMe@123';


/* ============================================================
   VALIDATE ADMIN DETAILS
   ============================================================ */

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {
    exit(
        "ERROR: Invalid admin email.\n"
    );
}


if (strlen($password) < 8) {
    exit(
        "ERROR: Admin password must contain at least 8 characters.\n"
    );
}


/* ============================================================
   DATABASE
   ============================================================ */

try {

    $pdo = db();

} catch (Throwable $exception) {

    exit(
        "ERROR: Unable to connect to MySQL.\n" .
        $exception->getMessage() .
        "\n"
    );
}


/* ============================================================
   CHECK EXISTING ADMIN
   ============================================================ */

try {

    $check = $pdo->prepare(
        "
        SELECT
            id,
            email
        FROM admins
        WHERE email = :email
        LIMIT 1
        "
    );


    $check->execute([
        ':email' => $email
    ]);


    $existingAdmin =
        $check->fetch();

} catch (Throwable $exception) {

    exit(
        "ERROR: Unable to check admin account.\n"
    );
}


/* ============================================================
   ADMIN ALREADY EXISTS
   ============================================================ */

if ($existingAdmin) {

    echo "\n";
    echo "============================================\n";
    echo "TENSPICK ADMIN\n";
    echo "============================================\n";
    echo "Admin account already exists.\n";
    echo "Email: " . $email . "\n";
    echo "============================================\n";
    echo "\n";

    exit(0);
}


/* ============================================================
   HASH PASSWORD
   ============================================================ */

$passwordHash =
    hashPassword($password);


/* ============================================================
   CREATE ADMIN
   ============================================================ */

try {

    $statement = $pdo->prepare(
        "
        INSERT INTO admins
        (
            name,
            email,
            password_hash,
            status,
            created_at,
            updated_at
        )
        VALUES
        (
            :name,
            :email,
            :password_hash,
            1,
            NOW(),
            NOW()
        )
        "
    );


    $statement->execute([
        ':name' =>
            $name,

        ':email' =>
            $email,

        ':password_hash' =>
            $passwordHash
    ]);

} catch (Throwable $exception) {

    exit(
        "ERROR: Unable to create admin account.\n"
    );
}


/* ============================================================
   SUCCESS
   ============================================================ */

echo "\n";

echo "============================================\n";
echo " TENSPICK ADMIN CREATED SUCCESSFULLY\n";
echo "============================================\n";

echo "Name:     " . $name . "\n";
echo "Email:    " . $email . "\n";
echo "Password: " . $password . "\n";

echo "============================================\n";

echo "\n";

echo "IMPORTANT:\n";
echo "Change this password after your first login.\n";
echo "Remove or protect database/seed_admin.php after setup.\n";

echo "\n";