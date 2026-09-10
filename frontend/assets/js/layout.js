/* ============================================================
   TENSPICK CRM
   ADMIN LAYOUT.JS
   ============================================================ */

(function () {

    "use strict";


    /* ========================================================
       CONFIG
       ======================================================== */

    const MOBILE_BREAKPOINT = 900;


    /* ========================================================
       SIDEBAR HELPERS
       ======================================================== */

    function getSidebar() {

        return document.getElementById(
            "appSidebar"
        );

    }


    function getToggle() {

        return document.getElementById(
            "sidebarToggle"
        );

    }


    function getOverlay() {

        return document.getElementById(
            "sidebarOverlay"
        );

    }


    /* ========================================================
       OPEN SIDEBAR
       ======================================================== */

    function openSidebar() {

        const sidebar = getSidebar();
        const toggle = getToggle();
        const overlay = getOverlay();


        if (!sidebar) {
            return;
        }


        sidebar.classList.add(
            "open"
        );


        if (overlay) {

            overlay.classList.add(
                "active"
            );

        }


        if (toggle) {

            toggle.setAttribute(
                "aria-expanded",
                "true"
            );

        }


        /*
         * Prevent background scrolling
         * only on mobile/tablet.
         */

        if (
            window.innerWidth <=
            MOBILE_BREAKPOINT
        ) {

            document.body.style.overflow =
                "hidden";

        }

    }


    /* ========================================================
       CLOSE SIDEBAR
       ======================================================== */

    function closeSidebar() {

        const sidebar = getSidebar();
        const toggle = getToggle();
        const overlay = getOverlay();


        if (sidebar) {

            sidebar.classList.remove(
                "open"
            );

        }


        if (overlay) {

            overlay.classList.remove(
                "active"
            );

        }


        if (toggle) {

            toggle.setAttribute(
                "aria-expanded",
                "false"
            );

        }


        document.body.style.overflow =
            "";

    }


    /* ========================================================
       TOGGLE SIDEBAR
       ======================================================== */

    function toggleSidebar() {

        const sidebar = getSidebar();


        if (!sidebar) {

            console.warn(
                "Tenspick CRM: #appSidebar not found."
            );

            return;

        }


        if (
            sidebar.classList.contains(
                "open"
            )
        ) {

            closeSidebar();

        } else {

            openSidebar();

        }

    }


    /* ========================================================
       SIDEBAR EVENTS
       ========================================================
       
       Event delegation is used because the topbar/sidebar
       can be rendered dynamically by the PHP SPA.
       ======================================================== */

    function initializeSidebarEvents() {

        if (
            document.body.dataset
                .tenspickSidebarEvents ===
            "true"
        ) {

            return;

        }


        document.body.dataset
            .tenspickSidebarEvents =
            "true";


        document.addEventListener(
            "click",
            function (event) {


                /* ============================================
                   TOGGLE BUTTON
                   ============================================ */

                const toggle =
                    event.target.closest(
                        "#sidebarToggle"
                    );


                if (toggle) {

                    event.preventDefault();

                    event.stopPropagation();

                    toggleSidebar();

                    return;

                }


                /* ============================================
                   OVERLAY
                   ============================================ */

                const overlay =
                    event.target.closest(
                        "#sidebarOverlay"
                    );


                if (overlay) {

                    event.preventDefault();

                    closeSidebar();

                    return;

                }


                /* ============================================
                   MOBILE SIDEBAR LINKS
                   ============================================ */

                const sidebar =
                    getSidebar();


                if (
                    sidebar &&
                    window.innerWidth <=
                    MOBILE_BREAKPOINT
                ) {

                    const link =
                        event.target.closest(
                            "a"
                        );


                    if (
                        link &&
                        sidebar.contains(
                            link
                        )
                    ) {

                        closeSidebar();

                    }

                }

            }
        );

    }


    /* ========================================================
       PROFILE DROPDOWN
       ======================================================== */

    function openProfile() {

        const profile =
            document.getElementById(
                "topbarProfile"
            );

        const toggle =
            document.getElementById(
                "profileToggle"
            );

        const dropdown =
            document.getElementById(
                "profileDropdown"
            );


        if (
            !profile ||
            !toggle ||
            !dropdown
        ) {

            return;

        }


        profile.classList.add(
            "is-open"
        );


        dropdown.hidden = false;


        toggle.setAttribute(
            "aria-expanded",
            "true"
        );

    }


    function closeProfile() {

        const profile =
            document.getElementById(
                "topbarProfile"
            );

        const toggle =
            document.getElementById(
                "profileToggle"
            );

        const dropdown =
            document.getElementById(
                "profileDropdown"
            );


        if (
            !profile ||
            !toggle ||
            !dropdown
        ) {

            return;

        }


        profile.classList.remove(
            "is-open"
        );


        dropdown.hidden = true;


        toggle.setAttribute(
            "aria-expanded",
            "false"
        );

    }


    function toggleProfile() {

        const profile =
            document.getElementById(
                "topbarProfile"
            );


        if (!profile) {

            return;

        }


        if (
            profile.classList.contains(
                "is-open"
            )
        ) {

            closeProfile();

        } else {

            openProfile();

        }

    }


    /* ========================================================
       PROFILE EVENTS
       ======================================================== */

    function initializeProfileEvents() {

        if (
            document.body.dataset
                .tenspickProfileEvents ===
            "true"
        ) {

            return;

        }


        document.body.dataset
            .tenspickProfileEvents =
            "true";


        document.addEventListener(
            "click",
            function (event) {

                const profileToggle =
                    event.target.closest(
                        "#profileToggle"
                    );


                if (profileToggle) {

                    event.preventDefault();

                    event.stopPropagation();

                    toggleProfile();

                    return;

                }


                const profile =
                    document.getElementById(
                        "topbarProfile"
                    );


                if (
                    profile &&
                    profile.classList.contains(
                        "is-open"
                    ) &&
                    !profile.contains(
                        event.target
                    )
                ) {

                    closeProfile();

                }

            }
        );

    }


    /* ========================================================
       CLOCK
       ======================================================== */

    function updateClock() {

        const dateElement =
            document.getElementById(
                "topbarDate"
            );

        const timeElement =
            document.getElementById(
                "topbarTime"
            );


        const now = new Date();


        /* ----------------------------------------------------
           DATE
           ---------------------------------------------------- */

        if (dateElement) {

            dateElement.textContent =
                new Intl.DateTimeFormat(
                    "en-IN",
                    {
                        weekday: "short",
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    }
                ).format(now);

        }


        /* ----------------------------------------------------
           TIME
           ---------------------------------------------------- */

        if (timeElement) {

            timeElement.textContent =
                new Intl.DateTimeFormat(
                    "en-IN",
                    {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                        hour12: true
                    }
                ).format(now);

        }

    }


    function initializeClock() {

        updateClock();


        /*
         * Only one clock timer.
         */

        if (
            window.TenspickClockTimer
        ) {

            clearInterval(
                window.TenspickClockTimer
            );

        }


        window.TenspickClockTimer =
            setInterval(
                updateClock,
                1000
            );

    }


    /* ========================================================
       LOGOUT
       ======================================================== */

    async function logout() {

        const confirmed =
            window.confirm(
                "Are you sure you want to logout?"
            );


        if (!confirmed) {

            return;

        }


        try {

            /* ================================================
               GET CSRF TOKEN
               ================================================ */

            const csrfResponse =
                await fetch(
                    "../backend/public/index.php/api/security/csrf",
                    {
                        method: "GET",

                        credentials:
                            "same-origin",

                        headers: {
                            "Accept":
                                "application/json"
                        },

                        cache: "no-store"
                    }
                );


            if (
                !csrfResponse.ok
            ) {

                throw new Error(
                    "Unable to initialize logout."
                );

            }


            const csrfData =
                await csrfResponse.json();


            if (
                !csrfData.success ||
                !csrfData.data ||
                !csrfData.data.token
            ) {

                throw new Error(
                    "Unable to initialize logout."
                );

            }


            /* ================================================
               LOGOUT REQUEST
               ================================================ */

            const response =
                await fetch(
                    "../backend/public/index.php/api/auth/logout",
                    {
                        method: "POST",

                        credentials:
                            "same-origin",

                        headers: {

                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json",

                            "X-CSRF-Token":
                                csrfData.data.token

                        },

                        body:
                            JSON.stringify({})

                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "Logout failed."
                );

            }


            window.location.href =
                "login.php";


        } catch (error) {

            console.error(
                "Tenspick logout error:",
                error
            );


            window.alert(
                error.message ||
                "Unable to logout."
            );

        }

    }


    /* ========================================================
       LOGOUT EVENTS
       ======================================================== */

    function initializeLogout() {

        if (
            document.body.dataset
                .tenspickLogoutEvents ===
            "true"
        ) {

            return;

        }


        document.body.dataset
            .tenspickLogoutEvents =
            "true";


        /*
         * Event delegation allows both:
         *
         * #sidebarLogout
         * #topbarLogout
         *
         * to work even when dynamically rendered.
         */

        document.addEventListener(
            "click",
            function (event) {

                const logoutButton =
                    event.target.closest(
                        "#sidebarLogout, #topbarLogout"
                    );


                if (!logoutButton) {

                    return;

                }


                event.preventDefault();


                logout();

            }
        );

    }


    /* ========================================================
       ESC KEY
       ======================================================== */

    function initializeKeyboard() {

        if (
            document.body.dataset
                .tenspickKeyboardEvents ===
            "true"
        ) {

            return;

        }


        document.body.dataset
            .tenspickKeyboardEvents =
            "true";


        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key !==
                    "Escape"
                ) {

                    return;

                }


                closeSidebar();

                closeProfile();

            }
        );

    }


    /* ========================================================
       RESIZE
       ======================================================== */

    function initializeResize() {

        if (
            document.body.dataset
                .tenspickResizeEvents ===
            "true"
        ) {

            return;

        }


        document.body.dataset
            .tenspickResizeEvents =
            "true";


        window.addEventListener(
            "resize",
            function () {

                /*
                 * When returning to desktop,
                 * remove mobile state.
                 */

                if (
                    window.innerWidth >
                    MOBILE_BREAKPOINT
                ) {

                    closeSidebar();

                }

            }
        );

    }


    /* ========================================================
       SPA PAGE LOADED
       ======================================================== */

    function initializeSPAEvents() {

        if (
            document.body.dataset
                .tenspickSPAEvents ===
            "true"
        ) {

            return;

        }


        document.body.dataset
            .tenspickSPAEvents =
            "true";


        document.addEventListener(
            "tenspick:page-loaded",
            function () {

                /*
                 * Close mobile sidebar
                 * after changing page.
                 */

                if (
                    window.innerWidth <=
                    MOBILE_BREAKPOINT
                ) {

                    closeSidebar();

                }


                closeProfile();


                /*
                 * Update clock in case
                 * topbar was recreated.
                 */

                updateClock();

            }
        );

    }


    /* ========================================================
       INITIALIZE
       ======================================================== */

    function initialize() {

        initializeSidebarEvents();

        initializeProfileEvents();

        initializeClock();

        initializeLogout();

        initializeKeyboard();

        initializeResize();

        initializeSPAEvents();

    }


    /* ========================================================
       START
       ======================================================== */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize,
            {
                once: true
            }
        );

    } else {

        initialize();

    }


    /* ========================================================
       PUBLIC API
       ======================================================== */

    window.TenspickLayout = {

        openSidebar:
            openSidebar,

        closeSidebar:
            closeSidebar,

        toggleSidebar:
            toggleSidebar,

        openProfile:
            openProfile,

        closeProfile:
            closeProfile,

        toggleProfile:
            toggleProfile,

        updateClock:
            updateClock

    };


})();