<?php

declare(strict_types=1);

/*
 * ============================================================
 * TENSPICK CRM
 * CLIENT PORTAL
 * CLIENT LOGIN PAGE
 * ============================================================
 *
 * File:
 * frontend/client/login.php
 *
 * ============================================================
 */


/*
 * ============================================================
 * NO-CACHE HEADERS
 * ============================================================
 *
 * Prevent the browser from restoring an authenticated page
 * after logout.
 */

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Cache-Control: post-check=0, pre-check=0',
    false
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


/*
 * ============================================================
 * PAGE CONFIGURATION
 * ============================================================
 */

$appName = 'TENSPICK CRM';

$pageTitle = 'Client Login';


/*
 * ============================================================
 * APPLICATION ROOT
 * ============================================================
 *
 * Automatically detects:
 *
 * /tenspickk/frontend/client/login.php
 *
 * and produces:
 *
 * /tenspickk
 *
 * This keeps the configuration compatible with both:
 *
 * http://localhost/tenspickk
 *
 * and
 *
 * http://localhost
 *
 */

$requestPath =
    str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME'] ?? ''
    );

$requestPath =
    preg_replace(
        '#/+#',
        '/',
        $requestPath
    );

$frontendMarker = '/frontend/';

$frontendPosition =
    stripos(
        $requestPath,
        $frontendMarker
    );


if (
    $frontendPosition !== false
) {

    $appRoot =
        substr(
            $requestPath,
            0,
            $frontendPosition
        );

} else {

    $appRoot = '';

}


/*
 * Normalize application root.
 */

if (
    $appRoot !== '' &&
    $appRoot !== '/'
) {

    $appRoot =
        rtrim(
            $appRoot,
            '/'
        );

} else {

    $appRoot = '';

}


/*
 * ============================================================
 * API URLS
 * ============================================================
 */

$apiBase =
    $appRoot .
    '/backend/public/index.php/api';


$clientLoginUrl =
    $apiBase .
    '/client-auth/login';


$clientLogoutUrl =
    $apiBase .
    '/client-auth/logout';


$clientMeUrl =
    $apiBase .
    '/client-auth/me';


$csrfUrl =
    $apiBase .
    '/security/csrf';


$portalUrl =
    'index.php';


$dashboardUrl =
    'index.php#dashboard';


$loginPage =
    'login.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <!-- ======================================================
         BASIC META
         ====================================================== -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <meta
        name="description"
        content="TENSPICK Client Portal Login"
    >

    <meta
        name="theme-color"
        content="#4B49AC"
    >


    <!-- ======================================================
         PAGE CACHE CONTROL
         ====================================================== -->

    <meta
        http-equiv="Cache-Control"
        content="no-cache, no-store, must-revalidate"
    >

    <meta
        http-equiv="Pragma"
        content="no-cache"
    >

    <meta
        http-equiv="Expires"
        content="0"
    >


    <!-- ======================================================
         TITLE
         ====================================================== -->

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ); ?>

        |

        <?= htmlspecialchars(
            $appName,
            ENT_QUOTES,
            'UTF-8'
        ); ?>
    </title>


    <!-- ======================================================
         BOOTSTRAP ICONS
         ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- ======================================================
         CLIENT LOGIN CSS
         ====================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/client-login.css"
    >

</head>


<body>

    <!-- ======================================================
         CLIENT LOGIN APPLICATION
         ====================================================== -->

    <div
        id="clientLoginApp"
        class="client-login-app"
    >


        <!-- ==================================================
             BRAND / INFORMATION PANEL
             ================================================== -->

        <section
            class="client-login-brand-panel"
            aria-label="TENSPICK Client Portal"
        >

            <!-- Decorative Background -->

            <div
                class="client-brand-decoration client-brand-decoration-one"
            ></div>

            <div
                class="client-brand-decoration client-brand-decoration-two"
            ></div>


            <!-- Brand Content -->

            <div class="client-brand-panel-content">


                <!-- ==========================================
                     LOGO
                     ========================================== -->

                <div class="client-brand-logo-wrapper">

                    <img
                        src="assets/images/tenspick-logo.png"
                        alt="TENSPICK"
                        class="client-brand-logo"
                    >

                </div>


                <!-- ==========================================
                     BRAND NAME
                     ========================================== -->

                <div class="client-brand-name">
                    TENSPICK
                </div>


                <div class="client-brand-label">
                    CLIENT PORTAL
                </div>


                <!-- ==========================================
                     INTRODUCTION
                     ========================================== -->

                <div class="client-brand-introduction">

                    <h1>
                        Your projects.
                        <br>
                        Your progress.
                        <br>
                        <span>
                            Your portal.
                        </span>
                    </h1>


                    <p>
                        Access your projects, monitor progress,
                        review milestones, check payment status
                        and visit your live website from one
                        secure place.
                    </p>

                </div>


                <!-- ==========================================
                     FEATURES
                     ========================================== -->

                <div class="client-brand-features">


                    <!-- Project Tracking -->

                    <div class="client-brand-feature">

                        <div class="client-feature-icon">

                            <i class="bi bi-kanban-fill"></i>

                        </div>


                        <div class="client-feature-content">

                            <strong>
                                Project Tracking
                            </strong>

                            <span>
                                View your active and completed
                                projects.
                            </span>

                        </div>

                    </div>


                    <!-- Progress -->

                    <div class="client-brand-feature">

                        <div class="client-feature-icon">

                            <i class="bi bi-bar-chart-fill"></i>

                        </div>


                        <div class="client-feature-content">

                            <strong>
                                Project Progress
                            </strong>

                            <span>
                                Track progress and project
                                milestones in real time.
                            </span>

                        </div>

                    </div>


                    <!-- Payments -->

                    <div class="client-brand-feature">

                        <div class="client-feature-icon">

                            <i class="bi bi-wallet2"></i>

                        </div>


                        <div class="client-feature-content">

                            <strong>
                                Payment Status
                            </strong>

                            <span>
                                Review payments and remaining
                                project balances.
                            </span>

                        </div>

                    </div>


                    <!-- Website -->

                    <div class="client-brand-feature">

                        <div class="client-feature-icon">

                            <i class="bi bi-globe2"></i>

                        </div>


                        <div class="client-feature-content">

                            <strong>
                                Live Website
                            </strong>

                            <span>
                                Quickly access your project's
                                live website.
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==========================================
                 BRAND FOOTER
                 ========================================== -->

            <div class="client-brand-footer">

                <span>
                    © <?= date('Y'); ?> TENSPICK
                </span>


                <span class="client-brand-footer-divider">
                    •
                </span>


                <span>
                    Secure Client Portal
                </span>

            </div>

        </section>



        <!-- ==================================================
             LOGIN PANEL
             ================================================== -->

        <section
            class="client-login-panel"
            aria-label="Client Login"
        >

            <div class="client-login-panel-inner">


                <!-- ==========================================
                     MOBILE BRAND
                     ========================================== -->

                <div class="client-mobile-brand">

                    <div class="client-mobile-logo-wrapper">

                        <img
                            src="assets/images/tenspick-logo.png"
                            alt="TENSPICK"
                            class="client-mobile-logo"
                        >

                    </div>


                    <div class="client-mobile-brand-text">

                        <strong>
                            TENSPICK
                        </strong>

                        <span>
                            Client Portal
                        </span>

                    </div>

                </div>



                <!-- ==========================================
                     LOGIN CARD
                     ========================================== -->

                <div class="client-login-card">


                    <!-- ======================================
                         LOGIN HEADER
                         ====================================== -->

                    <div class="client-login-header">

                        <div class="client-login-header-badge">

                            <i class="bi bi-person-lock"></i>

                            <span>
                                CLIENT ACCESS
                            </span>

                        </div>


                        <h2>
                            Welcome back
                        </h2>


                        <p>
                            Sign in to continue to your
                            client dashboard.
                        </p>

                    </div>



                    <!-- ======================================
                         GLOBAL MESSAGE
                         ====================================== -->

                    <div
                        id="clientLoginMessage"
                        class="client-login-message"
                        role="alert"
                        aria-live="polite"
                        hidden
                    >

                        <div class="client-login-message-icon">

                            <i
                                id="clientLoginMessageIcon"
                                class="bi bi-exclamation-circle"
                            ></i>

                        </div>


                        <div
                            id="clientLoginMessageText"
                            class="client-login-message-text"
                        ></div>


                        <button
                            type="button"
                            id="clientLoginMessageClose"
                            class="client-login-message-close"
                            aria-label="Close message"
                        >

                            <i class="bi bi-x"></i>

                        </button>

                    </div>



                    <!-- ======================================
                         LOGIN FORM
                         ====================================== -->

                    <form
                        id="clientLoginForm"
                        class="client-login-form"
                        method="post"
                        autocomplete="on"
                        novalidate
                    >


                        <!-- ==================================
                             EMAIL
                             ================================== -->

                        <div
                            class="client-form-group"
                            id="clientEmailGroup"
                        >

                            <label
                                for="clientLoginEmail"
                                class="client-form-label"
                            >

                                Login Email

                                <span>
                                    *
                                </span>

                            </label>


                            <div class="client-input-container">

                                <div class="client-input-icon">

                                    <i class="bi bi-envelope"></i>

                                </div>


                                <input
                                    type="email"
                                    id="clientLoginEmail"
                                    name="login_email"
                                    class="client-form-input"
                                    placeholder="Enter your login email"
                                    autocomplete="username"
                                    maxlength="150"
                                    inputmode="email"
                                    required
                                >

                            </div>


                            <div
                                id="clientLoginEmailError"
                                class="client-field-error"
                                hidden
                            >

                                <i class="bi bi-exclamation-circle"></i>

                                <span></span>

                            </div>

                        </div>



                        <!-- ==================================
                             PASSWORD
                             ================================== -->

                        <div
                            class="client-form-group"
                            id="clientPasswordGroup"
                        >

                            <div class="client-form-label-row">

                                <label
                                    for="clientLoginPassword"
                                    class="client-form-label"
                                >

                                    Password

                                    <span>
                                        *
                                    </span>

                                </label>

                            </div>


                            <div class="client-input-container">

                                <div class="client-input-icon">

                                    <i class="bi bi-lock"></i>

                                </div>


                                <input
                                    type="password"
                                    id="clientLoginPassword"
                                    name="password"
                                    class="client-form-input client-password-input"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    maxlength="255"
                                    required
                                >


                                <button
                                    type="button"
                                    id="clientPasswordToggle"
                                    class="client-password-toggle"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                    tabindex="0"
                                >

                                    <i
                                        id="clientPasswordToggleIcon"
                                        class="bi bi-eye"
                                    ></i>

                                </button>

                            </div>


                            <div
                                id="clientLoginPasswordError"
                                class="client-field-error"
                                hidden
                            >

                                <i class="bi bi-exclamation-circle"></i>

                                <span></span>

                            </div>

                        </div>



                        <!-- ==================================
                             LOGIN OPTIONS
                             ================================== -->

                        <div class="client-login-options">


                            <!-- Remember Me -->

                            <label
                                for="clientRememberMe"
                                class="client-remember-label"
                            >

                                <input
                                    type="checkbox"
                                    id="clientRememberMe"
                                    name="remember_me"
                                    value="1"
                                >


                                <span
                                    class="client-checkbox"
                                    aria-hidden="true"
                                ></span>


                                <span class="client-remember-text">
                                    Remember me
                                </span>

                            </label>


                            <!-- Forgot Password -->

                            <button
                                type="button"
                                id="clientForgotPassword"
                                class="client-forgot-password"
                            >

                                Forgot password?

                            </button>

                        </div>



                        <!-- ==================================
                             LOGIN BUTTON
                             ================================== -->

                        <button
                            type="submit"
                            id="clientLoginButton"
                            class="client-login-button"
                        >

                            <span
                                id="clientLoginButtonContent"
                                class="client-login-button-content"
                            >

                                <span id="clientLoginButtonText">
                                    Sign In
                                </span>


                                <i
                                    id="clientLoginButtonIcon"
                                    class="bi bi-arrow-right"
                                ></i>

                            </span>


                            <span
                                id="clientLoginButtonLoader"
                                class="client-login-button-loader"
                                hidden
                            >

                                <span></span>
                                <span></span>
                                <span></span>

                                <span>
                                    Signing in...
                                </span>

                            </span>

                        </button>


                    </form>



                    <!-- ======================================
                         SECURITY INFORMATION
                         ====================================== -->

                    <div class="client-security-information">

                        <div class="client-security-icon">

                            <i class="bi bi-shield-check"></i>

                        </div>


                        <div class="client-security-text">

                            <strong>
                                Secure Client Access
                            </strong>

                            <span>
                                Your account and project information
                                are protected.
                            </span>

                        </div>

                    </div>


                </div>



                <!-- ==========================================
                     CONTACT FOOTER
                     ========================================== -->

                <div class="client-login-support">

                    <span>
                        Need help accessing your account?
                    </span>


                    <a href="tel:8688386307">

                        <i class="bi bi-telephone"></i>

                        Contact Tenspick

                    </a>

                </div>



                <!-- ==========================================
                     COMPANY INFORMATION
                     ========================================== -->

                <div class="client-company-footer">

                    <span>
                        TENSPICK
                    </span>


                    <span>
                        Bazaar Street, Pulampeta, Tirupati
                    </span>

                </div>


            </div>

        </section>

    </div>



    <!-- ======================================================
         GLOBAL CLIENT LOGIN CONFIGURATION
         ====================================================== -->

    <script>

        window.TENSPICK_CLIENT_CONFIG = {

            /*
             * Application root.
             */

            appRoot:
                <?= json_encode(
                    $appRoot,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            /*
             * Backend API base.
             *
             * Do NOT add another /api here.
             */

            apiBase:
                <?= json_encode(
                    $apiBase,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            /*
             * Client portal.
             */

            portalUrl:
                <?= json_encode(
                    $portalUrl,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            /*
             * Default authenticated destination.
             */

            dashboardUrl:
                <?= json_encode(
                    $dashboardUrl,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            /*
             * CSRF endpoint.
             */

            csrfUrl:
                <?= json_encode(
                    $csrfUrl,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            /*
             * Client authentication endpoints.
             */

            loginUrl:
                <?= json_encode(
                    $clientLoginUrl,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            logoutUrl:
                <?= json_encode(
                    $clientLogoutUrl,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            meUrl:
                <?= json_encode(
                    $clientMeUrl,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>,


            /*
             * Login page.
             */

            loginPage:
                <?= json_encode(
                    $loginPage,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                ); ?>

        };

    </script>



    <!-- ======================================================
         CLIENT LOGIN JAVASCRIPT
         ====================================================== -->

    <script
        src="assets/js/client-login.js"
    ></script>

</body>

</html>