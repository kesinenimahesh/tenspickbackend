<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/config/bootstrap.php';
require_once __DIR__ . '/../backend/core/response.php';
require_once __DIR__ . '/../backend/core/security.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$admin = authenticatedAdmin();

$currentPage = 'dashboard';

$pageTitle = 'Dashboard';

$pageSubtitle =
    "Welcome back, {$admin['name']}! Here's what's happening with your business today.";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Tenspick | Admin
    </title>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/root.css">

    <link rel="stylesheet" href="assets/css/layout.css">

    <link rel="stylesheet" href="assets/css/sidebar.css">

    <link rel="stylesheet" href="assets/css/topbar.css">

    <link rel="stylesheet" href="assets/css/dashboard.css">

    <link rel="stylesheet" href="assets/css/leads.css">

    <link rel="stylesheet" href="assets/css/clients.css">

    <link rel="stylesheet" href="assets/css/projects.css">

    <link rel="stylesheet" href="assets/css/tasks.css">

    <link rel="stylesheet" href="assets/css/staff.css">

    <link rel="stylesheet" href="assets/css/payments.css">

    <link rel="stylesheet" href="assets/css/staff-payments.css">

</head>


<body>

    <?php require __DIR__ . '/components/sidebar.php'; ?>


    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    <main class="app-main">

        <?php require __DIR__ . '/components/topbar.php'; ?>


        <div id="app-content" class="app-content">

            <div class="page-loading">

                Loading...

            </div>

        </div>

    </main>


    <script src="assets/js/router.js"></script>


    <script src="assets/js/layout.js"></script>

    <script src="assets/js/dashboard.js"></script>

    <script src="assets/js/leads.js"></script>

    <script src="assets/js/clients.js"></script>

    <script src="assets/js/projects.js"></script>

    <script src="assets/js/tasks.js"></script>

    <script src="assets/js/staff.js"></script>

    <script src="assets/js/client-payments.js"></script>

    <script src="assets/js/staff-payments.js"></script>

</body>

</html>