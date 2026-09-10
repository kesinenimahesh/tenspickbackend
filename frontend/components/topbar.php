<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * TOPBAR COMPONENT
 * ============================================================
 *
 * Admin-only CRM topbar.
 *
 * This component is loaded as part of the SPA layout.
 *
 * JavaScript handling:
 * - Sidebar toggle       -> #sidebarToggle
 * - Profile dropdown     -> #profileToggle
 * - Logout               -> #topbarLogout
 * - Date                 -> #topbarDate
 * - Time                 -> #topbarTime
 * - Notifications        -> #notificationBadge
 * ============================================================
 */


/* ============================================================
   ADMIN
   ============================================================ */

$admin = authenticatedAdmin();


$adminName = !empty($admin['name'])
    ? (string) $admin['name']
    : 'Admin';


$adminEmail = !empty($admin['email'])
    ? (string) $admin['email']
    : '';


/* ============================================================
   ADMIN INITIAL
   ============================================================ */

$adminNameTrimmed = trim($adminName);


$adminInitial = $adminNameTrimmed !== ''
    ? strtoupper(
        substr(
            $adminNameTrimmed,
            0,
            1
        )
    )
    : 'A';


/* ============================================================
   FRONTEND ASSET PATH
   ============================================================ */

$tenspickLogo =
    'assets/images/tenspick-logo.png';

?>

<!-- ============================================================
     TOPBAR
     ============================================================ -->

<header
    class="app-topbar"
    id="appTopbar"
>


    <!-- ========================================================
         LEFT SECTION
         ======================================================== -->

    <div class="topbar-left">


        <!-- ====================================================
             SIDEBAR TOGGLE
             ==================================================== -->

        <button
            type="button"
            class="topbar-menu-toggle"
            id="sidebarToggle"
            aria-label="Toggle sidebar"
            aria-expanded="false"
            aria-controls="appSidebar"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>


        <!-- ====================================================
             PAGE HEADING
             ==================================================== -->

        <div class="topbar-heading">

            <h1 id="topbarPageTitle">
                Dashboard
            </h1>

            <p id="topbarPageSubtitle">
                Welcome back, Admin! Here's what's happening
                with your business today.
            </p>

        </div>

    </div>


    <!-- ========================================================
         RIGHT SECTION
         ======================================================== -->

    <div class="topbar-right">


        <!-- ====================================================
             DATE & TIME
             ==================================================== -->

        <div
            class="topbar-datetime"
            id="topbarDateTime"
            aria-label="Current date and time"
        >

            <div class="topbar-datetime-icon">

                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                    focusable="false"
                >

                    <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 16H5V9h14v11ZM7 11h5v5H7v-5Z"/>

                </svg>

            </div>


            <div class="topbar-datetime-content">

                <strong id="topbarDate">
                    Sat, 29 Aug, 2026
                </strong>

                <span id="topbarTime">
                    09:43:43 pm
                </span>

            </div>

        </div>


        <!-- ====================================================
             NOTIFICATIONS
             ==================================================== -->

        <a
            href="#/notifications"
            class="topbar-notification"
            data-route="notifications"
            id="topbarNotifications"
            aria-label="Notifications"
            title="Notifications"
        >

            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
                focusable="false"
            >

                <path d="M12 22c1.1 0 1.99-.9 1.99-2h-3.98c0 1.1.89 2 1.99 2Zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5S10.5 3.17 10.5 4v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2Z"/>

            </svg>


            <span
                class="topbar-notification-count"
                id="notificationBadge"
                aria-label="5 unread notifications"
            >
                5
            </span>

        </a>


        <!-- ====================================================
             ADMIN PROFILE
             ==================================================== -->

        <div
            class="topbar-profile"
            id="topbarProfile"
        >


            <!-- =================================================
                 PROFILE BUTTON
                 ================================================= -->

            <button
                type="button"
                class="topbar-profile-button"
                id="profileToggle"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="profileDropdown"
            >


                <!-- =============================================
                     LOGO
                     ============================================= -->

                <span
                    class="topbar-profile-logo"
                    aria-hidden="true"
                >

                    <img
                        src="<?= htmlspecialchars(
                            $tenspickLogo,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt=""
                        onerror="
                            this.style.display='none';
                            this.parentElement.classList.add('fallback');
                        "
                    >

                    <span class="topbar-profile-logo-fallback">
                        T
                    </span>

                </span>


                <!-- =============================================
                     ADMIN DETAILS
                     ============================================= -->

                <span class="topbar-profile-details">

                    <strong>

                        <?= htmlspecialchars(
                            $adminName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </strong>

                    <small>
                        Administrator
                    </small>

                </span>


                <!-- =============================================
                     PROFILE ARROW
                     ============================================= -->

                <svg
                    class="topbar-profile-arrow"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                    focusable="false"
                >

                    <path d="M7 10l5 5 5-5H7Z"/>

                </svg>

            </button>


            <!-- =================================================
                 PROFILE DROPDOWN
                 ================================================= -->

            <div
                class="topbar-profile-dropdown"
                id="profileDropdown"
                hidden
            >


                <!-- =============================================
                     PROFILE USER
                     ============================================= -->

                <div class="profile-dropdown-user">


                    <!-- =========================================
                         PROFILE LOGO
                         ========================================= -->

                    <div class="profile-dropdown-logo">

                        <img
                            src="<?= htmlspecialchars(
                                $tenspickLogo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            alt=""
                            onerror="
                                this.style.display='none';
                                this.parentElement.classList.add('fallback');
                            "
                        >

                        <span>
                            <?= htmlspecialchars(
                                $adminInitial,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    </div>


                    <!-- =========================================
                         PROFILE INFORMATION
                         ========================================= -->

                    <div class="profile-dropdown-info">

                        <strong>

                            <?= htmlspecialchars(
                                $adminName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </strong>


                        <small>
                            Administrator
                        </small>


                        <?php if ($adminEmail !== ''): ?>

                            <small>

                                <?= htmlspecialchars(
                                    $adminEmail,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </small>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =================================================
                     DIVIDER
                     ================================================= -->

                <div
                    class="profile-dropdown-divider"
                    role="separator"
                ></div>


                <!-- =================================================
                     SETTINGS
                     ================================================= -->

                <a
                    href="#/settings"
                    data-route="settings"
                    class="profile-dropdown-item"
                >

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        focusable="false"
                    >

                        <path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.37-.31-.6-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98L14.5 2.42C14.47 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.5.42L9.12 5.07c-.61.25-1.18.59-1.69.98l-2.49-1c-.23-.08-.48 0-.6.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.02.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.37.31.6.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.18-.59 1.69-.98l2.49 1c.23.08.48 0 .6-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65Z"/>

                    </svg>


                    <span>
                        Settings
                    </span>

                </a>


                <!-- =================================================
                     ACTIVITY LOGS
                     ================================================= -->

                <a
                    href="#/activity-logs"
                    data-route="activity-logs"
                    class="profile-dropdown-item"
                >

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        focusable="false"
                    >

                        <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6Zm1 7V3.5L18.5 9H15ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h4v2H8V9Z"/>

                    </svg>


                    <span>
                        Activity Logs
                    </span>

                </a>


                <!-- =================================================
                     DIVIDER
                     ================================================= -->

                <div
                    class="profile-dropdown-divider"
                    role="separator"
                ></div>


                <!-- =================================================
                     LOGOUT
                     ================================================= -->

                <button
                    type="button"
                    class="profile-dropdown-item profile-dropdown-logout"
                    id="topbarLogout"
                >

                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        focusable="false"
                    >

                        <path d="M10.09 15.59 11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59ZM19 3H5c-1.1 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Z"/>

                    </svg>


                    <span>
                        Logout
                    </span>

                </button>

            </div>

        </div>

    </div>

</header>