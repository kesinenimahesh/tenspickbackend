/* ============================================================
   TENSPICK CRM
   DASHBOARD JS
   ============================================================ */

(function (window, document) {

    "use strict";


    const Dashboard = {

        initialized: false,

        quickButton: null,

        quickPanel: null,


        /* ====================================================
           INITIALIZE
           ==================================================== */

        init: function () {

            /*
             * Dashboard pages are loaded through the SPA router.
             *
             * Therefore this function can safely be called
             * every time the Dashboard page is loaded.
             */

            this.quickButton =
                document.getElementById(
                    "dashboardQuickAction"
                );


            this.quickPanel =
                document.getElementById(
                    "dashboardQuickPanel"
                );


            this.bindQuickAction();


            this.bindActionButtons();


            this.initialized = true;

        },


        /* ====================================================
           QUICK ACTION
           ==================================================== */

        bindQuickAction: function () {

            if (
                !this.quickButton ||
                !this.quickPanel
            ) {

                return;

            }


            /*
             * Prevent duplicate listeners if the dashboard
             * initialization is accidentally called again.
             */

            if (
                this.quickButton.dataset
                    .dashboardBound === "true"
            ) {

                return;

            }


            this.quickButton.dataset
                .dashboardBound = "true";


            this.quickButton.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();


                    const panel =
                        document.getElementById(
                            "dashboardQuickPanel"
                        );


                    if (!panel) {

                        return;

                    }


                    const isHidden =
                        panel.hidden;


                    panel.hidden =
                        !isHidden;


                    const button =
                        document.getElementById(
                            "dashboardQuickAction"
                        );


                    if (button) {

                        button.setAttribute(
                            "aria-expanded",
                            String(isHidden)
                        );

                    }

                }
            );

        },


        /* ====================================================
           TABLE ACTION BUTTONS
           ==================================================== */

        bindActionButtons: function () {

            const buttons =
                document.querySelectorAll(
                    ".dashboard-more-btn"
                );


            buttons.forEach(
                function (button) {

                    if (
                        button.dataset
                            .dashboardActionBound
                            === "true"
                    ) {

                        return;

                    }


                    button.dataset
                        .dashboardActionBound =
                        "true";


                    button.addEventListener(
                        "click",
                        function (event) {

                            event.preventDefault();

                            event.stopPropagation();


                            /*
                             * These buttons are intentionally
                             * static for now.
                             *
                             * Later they will open the relevant
                             * View/Edit/Delete actions.
                             */

                            console.log(
                                "Dashboard action selected."
                            );

                        }
                    );

                }
            );

        }

    };


    /* ========================================================
       SPA PAGE INITIALIZATION
       ======================================================== */

    function initializeDashboard() {

        /*
         * Dashboard elements only exist after the router
         * inserts dashboard.php into #app-content.
         */

        if (
            !document.querySelector(
                ".dashboard-page"
            )
        ) {

            return;

        }


        Dashboard.init();

    }


    /* ========================================================
       INITIAL LOAD
       ======================================================== */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initializeDashboard,
            {
                once: true
            }
        );

    } else {

        initializeDashboard();

    }


    /* ========================================================
       SPA PAGE LOADED EVENT
       ======================================================== */

    document.addEventListener(
        "tenspick:page-loaded",
        function (event) {

            if (
                event.detail &&
                event.detail.route &&
                event.detail.route !== "dashboard"
            ) {

                return;

            }


            initializeDashboard();

        }
    );


    /* ========================================================
       PUBLIC OBJECT
       ======================================================== */

    window.TenspickDashboard =
        Dashboard;


})(window, document);