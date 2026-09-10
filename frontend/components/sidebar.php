<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * ADMIN SIDEBAR COMPONENT
 * SPA VERSION
 * ============================================================
 */

$currentPage = $currentPage ?? 'dashboard';

$admin = authenticatedAdmin();

$adminName = isset($admin['name'])
    ? (string) $admin['name']
    : 'Admin';


$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);


if ($adminInitial === '') {
    $adminInitial = 'A';
}


/* ============================================================
   ACTIVE MENU
   ============================================================ */

function sidebarActive(
    string $page,
    string $currentPage
): string {

    return $page === $currentPage
        ? 'active'
        : '';

}

?>

<aside
    class="app-sidebar"
    id="appSidebar"
    aria-label="Main navigation"
>


    <!-- ========================================================
         BRAND
         ======================================================== -->

    <div class="sidebar-brand">

        <a
            href="#/dashboard"
            class="sidebar-brand-link"
            data-route="dashboard"
            aria-label="Tenspick Dashboard"
        >

            <img
                src="assets/images/tenspick-logo.png"
                alt="Tenspick Logo"
                class="sidebar-logo"
                onerror="this.style.display='none';"
            >


            <div class="sidebar-brand-content">

                <span class="sidebar-brand-name">
                    Tenspick
                </span>

                <span class="sidebar-brand-subtitle">
                    Software Company CRM
                </span>

            </div>

        </a>

    </div>


    <!-- ========================================================
         NAVIGATION
         ======================================================== -->

    <div class="sidebar-scroll">

        <nav class="sidebar-navigation">


            <!-- ==================================================
                 MAIN
                 ================================================== -->

            <div class="sidebar-group">

                <div class="sidebar-group-title">
                    Main
                </div>


                <a
                    href="#/dashboard"
                    data-route="dashboard"
                    class="sidebar-menu-item <?= sidebarActive(
                        'dashboard',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Dashboard
                    </span>

                </a>

            </div>


            <!-- ==================================================
                 BUSINESS
                 ================================================== -->

            <div class="sidebar-group">

                <div class="sidebar-group-title">
                    Business
                </div>


                <!-- Leads -->

                <a
                    href="#/leads"
                    data-route="leads"
                    class="sidebar-menu-item <?= sidebarActive(
                        'leads',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3ZM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm8 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-3.67-3.5-7-3.5ZM8 13c-2.33 0-7 1.17-7 3.5V19h6v-2.5c0-1.51.78-2.59 1.97-3.45C8.62 13.02 8.29 13 8 13Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Leads
                    </span>

                </a>


                <!-- Clients -->

                <a
                    href="#/clients"
                    data-route="clients"
                    class="sidebar-menu-item <?= sidebarActive(
                        'clients',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3ZM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm8 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-3.67-3.5-7-3.5ZM8 13c-2.33 0-7 1.17-7 3.5V19h6v-2.5c0-1.51.78-2.59 1.97-3.45C8.62 13.02 8.29 13 8 13Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Clients
                    </span>

                </a>


                <!-- Projects -->

                <a
                    href="#/projects"
                    data-route="projects"
                    class="sidebar-menu-item <?= sidebarActive(
                        'projects',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M10 4H2c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-10l-2-2Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Projects
                    </span>

                </a>


                <!-- Tasks -->

                <a
                    href="#/tasks"
                    data-route="tasks"
                    class="sidebar-menu-item <?= sidebarActive(
                        'tasks',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm-9 14-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Tasks
                    </span>

                </a>


                <!-- Staff -->

                <a
                    href="#/staff"
                    data-route="staff"
                    class="sidebar-menu-item <?= sidebarActive(
                        'staff',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3ZM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm8 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-3.67-3.5-7-3.5ZM8 13c-2.33 0-7 1.17-7 3.5V19h6v-2.5c0-1.51.78-2.59 1.97-3.45C8.62 13.02 8.29 13 8 13Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Staff
                    </span>

                </a>

            </div>


            <!-- ==================================================
                 FINANCE
                 ================================================== -->

            <div class="sidebar-group">

                <div class="sidebar-group-title">
                    Finance
                </div>


                <!-- Client Payments -->

                <a
                    href="#/client-payments"
                    data-route="client-payments"
                    class="sidebar-menu-item <?= sidebarActive(
                        'client-payments',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2Zm0 14H4v-6h16v6Zm0-10H4V6h16v2Zm-6 7h4v2h-4v-2Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Client Payments
                    </span>

                </a>


                <!-- Staff Payments -->

                <a
                    href="#/staff-payments"
                    data-route="staff-payments"
                    class="sidebar-menu-item <?= sidebarActive(
                        'staff-payments',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-6c0-1.1-.9-2-2-2ZM6 18c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1Zm12 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1Zm-1-6V9.5h2.5L21 12h-4Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Staff Payments
                    </span>

                </a>

            </div>


            <!-- ==================================================
                 COMMUNICATION
                 ================================================== -->

            <div class="sidebar-group">

                <div class="sidebar-group-title">
                    Communication
                </div>


                <!-- Chat -->

                <a
                    href="#/chat"
                    data-route="chat"
                    class="sidebar-menu-item <?= sidebarActive(
                        'chat',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1.9-2-2-2Zm0 14H5.17L4 17.17V4h16v12Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Chat
                    </span>

                </a>


                <!-- Notifications -->

                <a
                    href="#/notifications"
                    data-route="notifications"
                    class="sidebar-menu-item <?= sidebarActive(
                        'notifications',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M12 22c1.1 0 1.99-.9 1.99-2h-4C10 21.1 10.9 22 12 22Zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5S10.5 3.17 10.5 4v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Notifications
                    </span>


                    <span class="sidebar-badge">
                        5
                    </span>

                </a>

            </div>


            <!-- ==================================================
                 CONTENT
                 ================================================== -->

            <div class="sidebar-group">

                <div class="sidebar-group-title">
                    Content
                </div>


                <a
                    href="#/website-content"
                    data-route="website-content"
                    class="sidebar-menu-item <?= sidebarActive(
                        'website-content',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm6.93 6h-3.01c-.33-1.3-.85-2.48-1.55-3.5A8.03 8.03 0 0 1 18.93 8ZM12 4.04c.83 1.2 1.46 2.54 1.76 3.96h-3.52C10.54 6.58 11.17 5.24 12 4.04ZM4.26 14A8.03 8.03 0 0 1 4 12c0-.69.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26Zm.81 2h3.01c.33 1.3.85 2.48 1.55 3.5A8.03 8.03 0 0 1 5.07 16ZM8.08 8H5.07a8.03 8.03 0 0 1 4.56-3.5C8.93 5.52 8.41 6.7 8.08 8ZM12 19.96c-.83-1.2-1.46-2.54-1.76-3.96h3.52c-.3 1.42-.93 2.76-1.76 3.96ZM14.18 14H9.82c-.08-.66-.14-1.32-.14-2s.06-1.34.14-2h4.36c.08.66.14 1.32.14 2s-.06 1.34-.14 2Zm.19 5.5c.7-1.02 1.22-2.2 1.55-3.5h3.01a8.03 8.03 0 0 1-4.56 3.5ZM16.36 14c.08-.66.14-1.32.14-2s-.1 1.36-.26 2h-3.38Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Website Content
                    </span>

                </a>

            </div>


            <!-- ==================================================
                 SETTINGS
                 ================================================== -->

            <div class="sidebar-group">

                <div class="sidebar-group-title">
                    Settings
                </div>


                <!-- Settings -->

                <a
                    href="#/settings"
                    data-route="settings"
                    class="sidebar-menu-item <?= sidebarActive(
                        'settings',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.37-.31-.6-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98L14.5 2.42C14.47 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.5.42L9.12 5.07c-.61.25-1.18.59-1.69.98l-2.49-1c-.23-.08-.48 0-.6.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.08.65-.08.98s.03.66.08.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.37.31.6.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.18-.58 1.69-.98l2.49 1c.23.08.48 0 .6-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Settings
                    </span>

                </a>


                <!-- Activity Logs -->

                <a
                    href="#/activity-logs"
                    data-route="activity-logs"
                    class="sidebar-menu-item <?= sidebarActive(
                        'activity-logs',
                        $currentPage
                    ) ?>"
                >

                    <span class="sidebar-menu-icon">

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6Zm1 7V3.5L18.5 9H15ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h4v2H8V9Z"/>

                        </svg>

                    </span>


                    <span class="sidebar-menu-label">
                        Activity Logs
                    </span>

                </a>

            </div>

        </nav>

    </div>


    <!-- ========================================================
         SIDEBAR FOOTER
         ======================================================== -->

    <div class="sidebar-footer">


        <!-- Admin -->

        <div class="sidebar-admin">

            <div class="sidebar-admin-avatar">

                <?= htmlspecialchars(
                    $adminInitial,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>


            <div class="sidebar-admin-details">

                <strong>

                    <?= htmlspecialchars(
                        $adminName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>


        <!-- Logout -->

        <button
            type="button"
            class="sidebar-logout"
            id="sidebarLogout"
        >

            <span class="sidebar-logout-icon">
                ↪
            </span>

            <span>
                Logout
            </span>

        </button>

    </div>

</aside>