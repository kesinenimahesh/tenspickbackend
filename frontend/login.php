<?php
declare(strict_types=1);

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Tenspick Software Company Management System"
    >

    <title>Tenspick | Login</title>

    <!-- Root theme -->
    <link rel="stylesheet" href="assets/css/root.css">

    <!-- Login page styles -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="login-page">

    <main class="login-wrapper">

        <section class="login-card">

            <!-- =====================================================
                 BRAND
            ====================================================== -->
            <div class="login-brand">

                <div class="brand-logo-wrap">

                    <img
                        src="assets/images/tenspick-logo.png"
                        alt="Tenspick"
                        class="brand-logo"
                        onerror="this.style.display='none'; document.getElementById('brandFallback').style.display='grid';"
                    >

                    <div
                        class="brand-fallback"
                        id="brandFallback"
                        aria-hidden="true"
                    >
                        T
                    </div>

                </div>

                <div class="brand-text">
                    <h1>Tenspick</h1>
                    <span>Software Company Management</span>
                </div>

            </div>


            <!-- =====================================================
                 LOGIN HEADING
            ====================================================== -->
            <div class="login-heading">

                <span class="login-eyebrow">
                    WELCOME BACK
                </span>

                <h2>
                    Sign in to your account
                </h2>

                <p>
                    Manage your leads, clients, projects
                    and team from one place.
                </p>

            </div>


            <!-- =====================================================
                 LOGIN FORM
            ====================================================== -->
            <form
                id="loginForm"
                class="login-form"
                method="POST"
                novalidate
            >

                <!-- Email -->
                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrap">

                        <span
                            class="input-icon"
                            aria-hidden="true"
                        >
                            ✉
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            autocomplete="username"
                            maxlength="190"
                            required
                        >

                    </div>

                    <small
                        class="field-error"
                        id="emailError"
                    ></small>

                </div>


                <!-- Password -->
                <div class="form-group">

                    <div class="label-row">

                        <label for="password">
                            Password
                        </label>

                        <a
                            href="#"
                            class="forgot-link"
                            id="forgotPassword"
                        >
                            Forgot password?
                        </a>

                    </div>

                    <div class="input-wrap">

                        <span
                            class="input-icon"
                            aria-hidden="true"
                        >
                            ⌑
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            maxlength="128"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                    <small
                        class="field-error"
                        id="passwordError"
                    ></small>

                </div>


                <!-- Remember -->
                <div class="login-options">

                    <label class="remember-option">

                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                        >

                        <span
                            class="custom-checkbox"
                            aria-hidden="true"
                        ></span>

                        <span>
                            Remember me
                        </span>

                    </label>

                </div>


                <!-- Server / API message -->
                <div
                    class="login-message"
                    id="loginMessage"
                    role="alert"
                    aria-live="polite"
                ></div>


                <!-- Login button -->
                <button
                    type="submit"
                    class="login-button"
                    id="loginButton"
                >

                    <span class="button-text">
                        Sign In
                    </span>

                    <span
                        class="button-loader"
                        aria-hidden="true"
                    ></span>

                </button>

            </form>


            <!-- =====================================================
                 FOOTER
            ====================================================== -->
            <div class="login-footer">

                <span>
                    © <?= date('Y') ?> Tenspick
                </span>

                <span class="footer-dot">
                    •
                </span>

                <span>
                    Secure Admin Portal
                </span>

            </div>

        </section>

    </main>


    <!-- Login JavaScript will be created next -->
    <script src="assets/js/login.js"></script>

</body>
</html>