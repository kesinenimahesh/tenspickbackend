<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tenspick Password Hash Generator
|--------------------------------------------------------------------------
*/

$password = 'client@123';

$hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($hash === false) {
    exit("Failed to generate password hash.\n");
}

echo "Password:\n";
echo $password . "\n\n";

echo "Password Hash:\n";
echo $hash . "\n";