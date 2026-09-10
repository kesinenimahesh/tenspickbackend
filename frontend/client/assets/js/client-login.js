"use strict";

/**
 * ============================================================
 * TENSPICK CRM
 * CLIENT PORTAL
 * CLIENT LOGIN
 * ============================================================
 *
 * File:
 * frontend/client/assets/js/client-login.js
 *
 * ============================================================
 *
 * AUTHENTICATION FLOW
 * ------------------------------------------------------------
 *
 * NORMAL LOGIN:
 *
 * login.php
 *     ↓
 * GET /api/client-auth/me
 *     ↓
 * if authenticated → dashboard
 * if not authenticated → stay on login
 *
 * LOGIN:
 *
 * POST /api/client-auth/login
 *     ↓
 * GET /api/client-auth/me
 *     ↓
 * dashboard
 *
 * LOGOUT:
 *
 * dashboard
 *     ↓
 * POST /api/client-auth/logout
 *     ↓
 * client session cleared
 *     ↓
 * login.php?logged_out=1
 *     ↓
 * DO NOT auto-login
 *
 * ============================================================
 *
 * SECURITY
 * ------------------------------------------------------------
 * - Backend PHP session is the authentication authority.
 * - Frontend NEVER sends client_id.
 * - Frontend NEVER stores password.
 * - Frontend NEVER stores password_hash.
 * - Remember Me stores email only.
 * - No password update/change functionality.
 * - No automatic login after logout.
 * ============================================================
 */

(function (window, document) {

    "use strict";


    /* ========================================================
       CONFIGURATION
       ======================================================== */

    const DEBUG = true;

    const REMEMBER_EMAIL_KEY =
        "tenspick_client_login_email";

    const LOGOUT_FLAG =
        "tenspick_client_logged_out";


    /* ========================================================
       APPLICATION ROOT
       ======================================================== */

    function detectAppRoot() {

        const pathname =
            window.location.pathname || "";


        const normalizedPath =
            pathname.replace(
                /\/+/g,
                "/"
            );


        /*
         * Normal:
         *
         * /tenspickk/frontend/client/login.php
         *
         * Result:
         *
         * /tenspickk
         */

        const frontendMarker =
            "/frontend/";


        const frontendIndex =
            normalizedPath
                .toLowerCase()
                .indexOf(
                    frontendMarker
                );


        if (
            frontendIndex !== -1
        ) {

            return normalizedPath.substring(
                0,
                frontendIndex
            );

        }


        /*
         * Backend fallback.
         */

        const backendMarker =
            "/backend/";


        const backendIndex =
            normalizedPath
                .toLowerCase()
                .indexOf(
                    backendMarker
                );


        if (
            backendIndex !== -1
        ) {

            return normalizedPath.substring(
                0,
                backendIndex
            );

        }


        /*
         * Detect from loaded script.
         */

        const scripts =
            document.getElementsByTagName(
                "script"
            );


        for (
            let i = 0;
            i < scripts.length;
            i++
        ) {

            const src =
                scripts[i].src || "";


            if (!src) {

                continue;

            }


            const scriptPath =
                src.split("?")[0];


            const index =
                scriptPath
                    .toLowerCase()
                    .indexOf(
                        frontendMarker
                    );


            if (
                index !== -1
            ) {

                return scriptPath.substring(
                    0,
                    index
                );

            }

        }


        /*
         * Application is running from web root.
         */

        return "";

    }


    const APP_ROOT =
        detectAppRoot();


    /* ========================================================
       API BASE
       ======================================================== */

    const API_BASE =
        window.location.origin +
        APP_ROOT +
        "/backend/public/index.php/api";


    /* ========================================================
       ENDPOINTS
       ======================================================== */

    const ENDPOINTS = {

        login:
            API_BASE +
            "/client-auth/login",

        logout:
            API_BASE +
            "/client-auth/logout",

        me:
            API_BASE +
            "/client-auth/me",

        csrf:
            API_BASE +
            "/security/csrf"

    };


    /* ========================================================
       DEBUG
       ======================================================== */

    function log() {

        if (!DEBUG) {

            return;

        }


        console.log(
            "[Tenspick Client Login]",
            ...arguments
        );

    }


    function warn() {

        if (!DEBUG) {

            return;

        }


        console.warn(
            "[Tenspick Client Login]",
            ...arguments
        );

    }


    function logError() {

        console.error(
            "[Tenspick Client Login]",
            ...arguments
        );

    }


    /* ========================================================
       STARTUP DEBUG
       ======================================================== */

    log(
        "=================================================="
    );

    log(
        "CLIENT LOGIN INITIALIZED"
    );

    log(
        "Current URL:",
        window.location.href
    );

    log(
        "Application Root:",
        APP_ROOT
    );

    log(
        "API Base:",
        API_BASE
    );

    log(
        "Login Endpoint:",
        ENDPOINTS.login
    );

    log(
        "Logout Endpoint:",
        ENDPOINTS.logout
    );

    log(
        "ME Endpoint:",
        ENDPOINTS.me
    );

    log(
        "CSRF Endpoint:",
        ENDPOINTS.csrf
    );

    log(
        "=================================================="
    );


    /* ========================================================
       STATE
       ======================================================== */

    const state = {

        initialized:
            false,

        csrfToken:
            null,

        csrfPromise:
            null,

        submitting:
            false,

        sessionChecking:
            false,

        destroyed:
            false

    };


    /* ========================================================
       DOM REFERENCES
       ======================================================== */

    let form = null;

    let emailInput = null;

    let passwordInput = null;

    let rememberInput = null;

    let loginButton = null;

    let loginButtonContent = null;

    let loginButtonLoader = null;

    let passwordToggle = null;

    let passwordToggleIcon = null;

    let messageBox = null;

    let messageIcon = null;

    let messageText = null;

    let messageClose = null;

    let emailGroup = null;

    let passwordGroup = null;

    let emailError = null;

    let passwordError = null;

    let forgotPasswordButton = null;


    /* ========================================================
       CACHE DOM
       ======================================================== */

    function cacheElements() {

        form =
            document.getElementById(
                "clientLoginForm"
            );

        emailInput =
            document.getElementById(
                "clientLoginEmail"
            );

        passwordInput =
            document.getElementById(
                "clientLoginPassword"
            );

        rememberInput =
            document.getElementById(
                "clientRememberMe"
            );

        loginButton =
            document.getElementById(
                "clientLoginButton"
            );

        loginButtonContent =
            document.getElementById(
                "clientLoginButtonContent"
            );

        loginButtonLoader =
            document.getElementById(
                "clientLoginButtonLoader"
            );

        passwordToggle =
            document.getElementById(
                "clientPasswordToggle"
            );

        passwordToggleIcon =
            document.getElementById(
                "clientPasswordToggleIcon"
            );

        messageBox =
            document.getElementById(
                "clientLoginMessage"
            );

        messageIcon =
            document.getElementById(
                "clientLoginMessageIcon"
            );

        messageText =
            document.getElementById(
                "clientLoginMessageText"
            );

        messageClose =
            document.getElementById(
                "clientLoginMessageClose"
            );

        emailGroup =
            document.getElementById(
                "clientEmailGroup"
            );

        passwordGroup =
            document.getElementById(
                "clientPasswordGroup"
            );

        emailError =
            document.getElementById(
                "clientLoginEmailError"
            );

        passwordError =
            document.getElementById(
                "clientLoginPasswordError"
            );

        forgotPasswordButton =
            document.getElementById(
                "clientForgotPassword"
            );

    }


    /* ========================================================
       EVENTS
       ======================================================== */

    function onFormSubmit(event) {

        handleSubmit(event);

    }


    function onPasswordToggle() {

        togglePassword();

    }


    function onEmailInput() {

        clearFieldError(
            "email"
        );

        hideMessage();

    }


    function onPasswordInput() {

        clearFieldError(
            "password"
        );

        hideMessage();

    }


    function onMessageClose() {

        hideMessage();

    }


    function onForgotPassword() {

        handleForgotPassword();

    }


    /* ========================================================
       BIND EVENTS
       ======================================================== */

    function bindEvents() {

        if (form) {

            form.addEventListener(
                "submit",
                onFormSubmit
            );

        }


        if (passwordToggle) {

            passwordToggle.addEventListener(
                "click",
                onPasswordToggle
            );

        }


        if (emailInput) {

            emailInput.addEventListener(
                "input",
                onEmailInput
            );

        }


        if (passwordInput) {

            passwordInput.addEventListener(
                "input",
                onPasswordInput
            );

        }


        if (messageClose) {

            messageClose.addEventListener(
                "click",
                onMessageClose
            );

        }


        if (forgotPasswordButton) {

            forgotPasswordButton.addEventListener(
                "click",
                onForgotPassword
            );

        }

    }


    /* ========================================================
       UNBIND EVENTS
       ======================================================== */

    function unbindEvents() {

        if (form) {

            form.removeEventListener(
                "submit",
                onFormSubmit
            );

        }


        if (passwordToggle) {

            passwordToggle.removeEventListener(
                "click",
                onPasswordToggle
            );

        }


        if (emailInput) {

            emailInput.removeEventListener(
                "input",
                onEmailInput
            );

        }


        if (passwordInput) {

            passwordInput.removeEventListener(
                "input",
                onPasswordInput
            );

        }


        if (messageClose) {

            messageClose.removeEventListener(
                "click",
                onMessageClose
            );

        }


        if (forgotPasswordButton) {

            forgotPasswordButton.removeEventListener(
                "click",
                onForgotPassword
            );

        }

    }


    /* ========================================================
       INIT
       ======================================================== */

    async function init() {

        if (
            state.initialized
        ) {

            return;

        }


        state.initialized =
            true;

        state.destroyed =
            false;


        cacheElements();


        if (!form) {

            warn(
                "Client login form not found."
            );

            return;

        }


        bindEvents();


        restoreRememberedEmail();


        /*
         * IMPORTANT:
         *
         * If the user just logged out, NEVER perform the
         * normal authenticated-session redirect.
         */

        const logoutRedirect =
            isLogoutRedirect();


        if (
            logoutRedirect
        ) {

            log(
                "Post-logout login page detected."
            );


            /*
             * Clear the marker immediately.
             */

            clearLogoutFlag();


            /*
             * Do NOT call logout again.
             *
             * client-auth.js already called the backend logout
             * endpoint before redirecting here.
             */


            /*
             * Clear any browser-side client state.
             */

            clearClientStorage();


            /*
             * Make sure the login page is visible.
             */

            hideGlobalLoading();


            showMessage(
                "You have been logged out successfully.",
                "success"
            );

        } else {

            /*
             * Normal login-page visit.
             */

            const authenticated =
                await checkExistingSession();


            if (
                state.destroyed
            ) {

                return;

            }


            if (
                authenticated
            ) {

                return;

            }

        }


        /*
         * Load CSRF token.
         */

        try {

            await loadCsrfToken();

        } catch (error) {

            warn(
                "Initial CSRF loading failed:",
                error
            );

        }

    }


    /* ========================================================
       DESTROY
       ======================================================== */

    function destroy() {

        if (
            !state.initialized
        ) {

            return;

        }


        unbindEvents();


        state.destroyed =
            true;

        state.initialized =
            false;

        state.submitting =
            false;

        state.sessionChecking =
            false;

        state.csrfPromise =
            null;

        state.csrfToken =
            null;

    }


    /* ========================================================
       LOGOUT REDIRECT DETECTION
       ======================================================== */

    function isLogoutRedirect() {

        /*
         * Primary:
         *
         * login.php?logged_out=1
         */

        try {

            const params =
                new URLSearchParams(
                    window.location.search
                );


            if (
                params.get(
                    "logged_out"
                ) === "1"
            ) {

                return true;

            }

        } catch (error) {

            warn(
                "Unable to read logout URL parameter:",
                error
            );

        }


        /*
         * Secondary:
         *
         * sessionStorage marker.
         */

        try {

            if (
                sessionStorage.getItem(
                    LOGOUT_FLAG
                ) === "1"
            ) {

                return true;

            }

        } catch (error) {

            /*
             * Ignore storage errors.
             */

        }


        return false;

    }


    /* ========================================================
       CLEAR LOGOUT FLAG
       ======================================================== */

    function clearLogoutFlag() {

        try {

            sessionStorage.removeItem(
                LOGOUT_FLAG
            );

        } catch (error) {

            /*
             * Ignore storage errors.
             */

        }


        /*
         * Remove logged_out=1 from URL.
         */

        try {

            const url =
                new URL(
                    window.location.href
                );


            if (
                url.searchParams.has(
                    "logged_out"
                )
            ) {

                url.searchParams.delete(
                    "logged_out"
                );


                window.history.replaceState(
                    {},
                    document.title,
                    url.pathname +
                    (
                        url.search
                            ? url.search
                            : ""
                    ) +
                    (
                        url.hash
                            ? url.hash
                            : ""
                    )
                );

            }

        } catch (error) {

            warn(
                "Unable to clean logout URL:",
                error
            );

        }

    }


    /* ========================================================
       CLEAR CLIENT STORAGE
       ======================================================== */

    function clearClientStorage() {

        /*
         * Only remove temporary client-auth storage.
         *
         * IMPORTANT:
         *
         * Do NOT remove remembered email because Remember Me
         * is supposed to preserve the email.
         *
         * Do NOT store/remove passwords because passwords are
         * never stored by this application.
         */

        try {

            sessionStorage.removeItem(
                "tenspick_client"
            );

        } catch (error) {

            /*
             * Ignore storage errors.
             */

        }

    }


    /* ========================================================
       CHECK EXISTING SESSION
       ======================================================== */

    async function checkExistingSession() {

        if (
            state.sessionChecking
        ) {

            return false;

        }


        state.sessionChecking =
            true;


        log(
            "Checking existing client session..."
        );


        try {

            const response =
                await fetch(
                    ENDPOINTS.me,
                    {
                        method:
                            "GET",

                        credentials:
                            "include",

                        headers:
                            {
                                Accept:
                                    "application/json",

                                "X-Requested-With":
                                    "XMLHttpRequest"
                            },

                        cache:
                            "no-store"
                    }
                );


            log(
                "ME response status:",
                response.status
            );


            const result =
                await parseJsonResponse(
                    response
                );


            log(
                "ME response data:",
                result
            );


            /*
             * No authenticated session.
             */

            if (
                response.status === 401 ||
                response.status === 403
            ) {

                return false;

            }


            /*
             * Endpoint missing.
             */

            if (
                response.status === 404
            ) {

                warn(
                    "Client authentication endpoint returned 404:",
                    ENDPOINTS.me
                );

                return false;

            }


            /*
             * Valid client session.
             */

            if (
                response.ok &&
                isSuccessResponse(
                    result
                )
            ) {

                log(
                    "Existing client session found."
                );


                redirectToDashboard();


                return true;

            }


            return false;

        } catch (error) {

            /*
             * A network error must NOT automatically redirect.
             */

            logError(
                "Existing session check failed:",
                error
            );


            return false;

        } finally {

            state.sessionChecking =
                false;

        }

    }


    /* ========================================================
       LOAD CSRF
       ======================================================== */

    async function loadCsrfToken() {

        if (
            state.csrfToken
        ) {

            return state.csrfToken;

        }


        if (
            state.csrfPromise
        ) {

            return state.csrfPromise;

        }


        state.csrfPromise =
            fetch(
                ENDPOINTS.csrf,
                {
                    method:
                        "GET",

                    credentials:
                        "include",

                    headers:
                        {
                            Accept:
                                "application/json",

                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                    cache:
                        "no-store"
                }
            )
                .then(
                    async function (
                        response
                    ) {

                        const result =
                            await parseJsonResponse(
                                response
                            );


                        log(
                            "CSRF response:",
                            response.status,
                            result
                        );


                        if (
                            !response.ok
                        ) {

                            throw new Error(
                                getResponseMessage(
                                    result,
                                    "Unable to obtain security token."
                                )
                            );

                        }


                        const token =
                            extractCsrfToken(
                                result
                            );


                        if (!token) {

                            throw new Error(
                                "Security token was not returned by the server."
                            );

                        }


                        state.csrfToken =
                            token;


                        return token;

                    }
                )
                .catch(
                    function (error) {

                        state.csrfToken =
                            null;


                        throw error;

                    }
                )
                .finally(
                    function () {

                        state.csrfPromise =
                            null;

                    }
                );


        return state.csrfPromise;

    }


    /* ========================================================
       SUBMIT LOGIN
       ======================================================== */

    async function handleSubmit(event) {

        event.preventDefault();


        if (
            state.submitting
        ) {

            return;

        }


        hideMessage();

        clearAllFieldErrors();


        if (
            !validateForm()
        ) {

            focusFirstInvalidField();

            return;

        }


        state.submitting =
            true;


        setLoading(
            true
        );


        try {

            /*
             * Ensure CSRF exists.
             */

            if (
                !state.csrfToken
            ) {

                await loadCsrfToken();

            }


            /*
             * IMPORTANT:
             *
             * Password is used only for this request.
             *
             * It is NEVER stored in localStorage,
             * sessionStorage, cookies, or any custom state.
             */

            const payload = {

                login_email:
                    emailInput.value.trim(),

                password:
                    passwordInput.value,

                remember_me:
                    rememberInput &&
                    rememberInput.checked
                        ? 1
                        : 0

            };


            log(
                "Submitting client login..."
            );


            let loginResponse =
                await sendLoginRequest(
                    payload
                );


            /*
             * CSRF retry.
             */

            if (
                isCsrfFailure(
                    loginResponse.status,
                    loginResponse.result
                )
            ) {

                log(
                    "CSRF rejected. Refreshing token..."
                );


                state.csrfToken =
                    null;


                await loadCsrfToken();


                loginResponse =
                    await sendLoginRequest(
                        payload
                    );

            }


            /*
             * Login rejected.
             */

            if (
                !loginResponse.ok
            ) {

                handleLoginFailure(
                    loginResponse.status,
                    loginResponse.result
                );


                return;

            }


            /*
             * HTTP success but application failure.
             */

            if (
                !isSuccessResponse(
                    loginResponse.result
                )
            ) {

                showMessage(
                    getResponseMessage(
                        loginResponse.result,
                        "Unable to sign in. Please check your login details."
                    ),
                    "error"
                );


                return;

            }


            log(
                "Client login accepted."
            );


            /*
             * Remember ONLY email.
             */

            saveRememberedEmail();


            /*
             * Clear password from browser field after
             * successful authentication.
             */

            if (
                passwordInput
            ) {

                passwordInput.value =
                    "";

            }


            showMessage(
                "Login successful. Verifying your session...",
                "success"
            );


            /*
             * Verify actual PHP session before redirect.
             */

            const authenticated =
                await verifyAuthenticatedClient();


            if (
                !authenticated
            ) {

                showMessage(
                    "Login was accepted, but your client session could not be verified. Please try again.",
                    "error"
                );


                return;

            }


            showMessage(
                "Login successful. Redirecting...",
                "success"
            );


            await wait(
                250
            );


            if (
                !state.destroyed
            ) {

                redirectToDashboard();

            }

        } catch (error) {

            logError(
                "Client login error:",
                error
            );


            showMessage(
                error &&
                error.message
                    ? error.message
                    : "Unable to connect to the server. Please try again.",
                "error"
            );

        } finally {

            state.submitting =
                false;


            setLoading(
                false
            );

        }

    }


    /* ========================================================
       SEND LOGIN REQUEST
       ======================================================== */

    async function sendLoginRequest(
        payload
    ) {

        let response;


        try {

            response =
                await fetch(
                    ENDPOINTS.login,
                    {
                        method:
                            "POST",

                        credentials:
                            "include",

                        headers:
                            {
                                Accept:
                                    "application/json",

                                "Content-Type":
                                    "application/json",

                                "X-CSRF-Token":
                                    state.csrfToken || "",

                                "X-Requested-With":
                                    "XMLHttpRequest"
                            },

                        body:
                            JSON.stringify(
                                payload
                            ),

                        cache:
                            "no-store"
                    }
                );

        } catch (error) {

            throw new Error(
                "Unable to connect to the server. Please check your connection and try again."
            );

        }


        const result =
            await parseJsonResponse(
                response
            );


        return {

            response:
                response,

            status:
                response.status,

            ok:
                response.ok,

            result:
                result

        };

    }


    /* ========================================================
       VERIFY SESSION AFTER LOGIN
       ======================================================== */

    async function verifyAuthenticatedClient() {

        log(
            "Verifying client session after login..."
        );


        try {

            await wait(
                100
            );


            const response =
                await fetch(
                    ENDPOINTS.me,
                    {
                        method:
                            "GET",

                        credentials:
                            "include",

                        headers:
                            {
                                Accept:
                                    "application/json",

                                "X-Requested-With":
                                    "XMLHttpRequest"
                            },

                        cache:
                            "no-store"
                    }
                );


            const result =
                await parseJsonResponse(
                    response
                );


            log(
                "Session verification:",
                response.status,
                result
            );


            if (
                response.ok &&
                isSuccessResponse(
                    result
                )
            ) {

                return true;

            }


            return false;

        } catch (error) {

            logError(
                "Session verification failed:",
                error
            );


            return false;

        }

    }


    /* ========================================================
       LOGIN FAILURE
       ======================================================== */

    function handleLoginFailure(
        status,
        result
    ) {

        let message =
            getResponseMessage(
                result,
                "Unable to sign in. Please try again."
            );


        if (
            status === 401
        ) {

            message =
                "Invalid login email or password.";


            setFieldError(
                "password",
                "Please check your login details."
            );

        } else if (
            status === 403
        ) {

            if (
                !message ||
                message === "Forbidden."
            ) {

                message =
                    "Your client account is not active. Please contact Tenspick.";

            }

        } else if (
            status === 404
        ) {

            message =
                "Client login service was not found. Please contact the administrator.";

        } else if (
            status >= 500
        ) {

            message =
                "The server is temporarily unavailable. Please try again later.";

        }


        showMessage(
            message,
            "error"
        );

    }


    /* ========================================================
       CSRF FAILURE
       ======================================================== */

    function isCsrfFailure(
        status,
        result
    ) {

        if (
            status === 419
        ) {

            return true;

        }


        if (
            status !== 403
        ) {

            return false;

        }


        const message =
            getResponseMessage(
                result,
                ""
            ).toLowerCase();


        return (
            message.includes(
                "csrf"
            ) ||

            message.includes(
                "security token"
            ) ||

            message.includes(
                "security verification"
            )
        );

    }


    /* ========================================================
       FORM VALIDATION
       ======================================================== */

    function validateForm() {

        let valid =
            true;


        if (
            !validateEmail()
        ) {

            valid =
                false;

        }


        if (
            !validatePassword()
        ) {

            valid =
                false;

        }


        return valid;

    }


    /* ========================================================
       EMAIL VALIDATION
       ======================================================== */

    function validateEmail() {

        if (!emailInput) {

            return false;

        }


        const email =
            emailInput.value.trim();


        if (!email) {

            setFieldError(
                "email",
                "Please enter your login email."
            );


            return false;

        }


        if (
            email.length > 150
        ) {

            setFieldError(
                "email",
                "Login email is too long."
            );


            return false;

        }


        const pattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


        if (
            !pattern.test(
                email
            )
        ) {

            setFieldError(
                "email",
                "Please enter a valid email address."
            );


            return false;

        }


        return true;

    }


    /* ========================================================
       PASSWORD VALIDATION
       ======================================================== */

    function validatePassword() {

        if (!passwordInput) {

            return false;

        }


        const password =
            passwordInput.value;


        if (!password) {

            setFieldError(
                "password",
                "Please enter your password."
            );


            return false;

        }


        if (
            password.length > 255
        ) {

            setFieldError(
                "password",
                "Password is too long."
            );


            return false;

        }


        return true;

    }


    /* ========================================================
       FIELD ERROR
       ======================================================== */

    function setFieldError(
        field,
        message
    ) {

        let group =
            null;

        let errorElement =
            null;


        if (
            field === "email"
        ) {

            group =
                emailGroup;

            errorElement =
                emailError;

        }


        if (
            field === "password"
        ) {

            group =
                passwordGroup;

            errorElement =
                passwordError;

        }


        if (
            !group ||
            !errorElement
        ) {

            return;

        }


        const text =
            errorElement.querySelector(
                "span"
            );


        if (text) {

            text.textContent =
                message;

        }


        errorElement.hidden =
            false;


        group.classList.add(
            "has-error"
        );

    }


    /* ========================================================
       CLEAR FIELD ERROR
       ======================================================== */

    function clearFieldError(
        field
    ) {

        let group =
            null;

        let errorElement =
            null;


        if (
            field === "email"
        ) {

            group =
                emailGroup;

            errorElement =
                emailError;

        }


        if (
            field === "password"
        ) {

            group =
                passwordGroup;

            errorElement =
                passwordError;

        }


        if (
            !group ||
            !errorElement
        ) {

            return;

        }


        errorElement.hidden =
            true;


        group.classList.remove(
            "has-error"
        );

    }


    /* ========================================================
       CLEAR ALL ERRORS
       ======================================================== */

    function clearAllFieldErrors() {

        clearFieldError(
            "email"
        );

        clearFieldError(
            "password"
        );

    }


    /* ========================================================
       FOCUS INVALID
       ======================================================== */

    function focusFirstInvalidField() {

        if (
            emailGroup &&
            emailGroup.classList.contains(
                "has-error"
            )
        ) {

            if (emailInput) {

                emailInput.focus();

            }


            return;

        }


        if (
            passwordGroup &&
            passwordGroup.classList.contains(
                "has-error"
            )
        ) {

            if (passwordInput) {

                passwordInput.focus();

            }

        }

    }


    /* ========================================================
       PASSWORD TOGGLE
       ======================================================== */

    function togglePassword() {

        if (!passwordInput) {

            return;

        }


        const visible =
            passwordInput.type === "text";


        passwordInput.type =
            visible
                ? "password"
                : "text";


        if (
            passwordToggleIcon
        ) {

            passwordToggleIcon.className =
                visible
                    ? "bi bi-eye"
                    : "bi bi-eye-slash";

        }


        if (
            passwordToggle
        ) {

            passwordToggle.setAttribute(
                "aria-label",
                visible
                    ? "Show password"
                    : "Hide password"
            );


            passwordToggle.setAttribute(
                "aria-pressed",
                visible
                    ? "false"
                    : "true"
            );

        }

    }


    /* ========================================================
       BUTTON LOADING
       ======================================================== */

    function setLoading(
        loading
    ) {

        if (!loginButton) {

            return;

        }


        loginButton.disabled =
            loading;


        loginButton.setAttribute(
            "aria-busy",
            loading
                ? "true"
                : "false"
        );


        if (
            loginButtonContent
        ) {

            loginButtonContent.hidden =
                loading;

        }


        if (
            loginButtonLoader
        ) {

            loginButtonLoader.hidden =
                !loading;

        }

    }


    /* ========================================================
       SHOW MESSAGE
       ======================================================== */

    function showMessage(
        message,
        type
    ) {

        if (
            !messageBox ||
            !messageText
        ) {

            return;

        }


        messageText.textContent =
            message || "";


        messageBox.classList.remove(
            "is-success"
        );


        if (
            type === "success"
        ) {

            messageBox.classList.add(
                "is-success"
            );


            if (
                messageIcon
            ) {

                messageIcon.className =
                    "bi bi-check-circle";

            }

        } else {

            if (
                messageIcon
            ) {

                messageIcon.className =
                    "bi bi-exclamation-circle";

            }

        }


        messageBox.hidden =
            false;

    }


    /* ========================================================
       HIDE MESSAGE
       ======================================================== */

    function hideMessage() {

        if (!messageBox) {

            return;

        }


        messageBox.hidden =
            true;


        messageBox.classList.remove(
            "is-success"
        );


        if (
            messageText
        ) {

            messageText.textContent =
                "";

        }

    }


    /* ========================================================
       FORGOT PASSWORD
       ======================================================== */

    function handleForgotPassword() {

        /*
         * Password reset/change functionality is intentionally
         * NOT implemented.
         */

        showMessage(
            "Please contact Tenspick support to reset your client portal password.",
            "error"
        );

    }


    /* ========================================================
       REMEMBER EMAIL
       ======================================================== */

    function restoreRememberedEmail() {

        if (
            !emailInput ||
            !rememberInput
        ) {

            return;

        }


        try {

            const email =
                localStorage.getItem(
                    REMEMBER_EMAIL_KEY
                );


            if (email) {

                emailInput.value =
                    email;

                rememberInput.checked =
                    true;

            }

        } catch (error) {

            /*
             * Ignore storage errors.
             */

        }

    }


    function saveRememberedEmail() {

        if (
            !emailInput ||
            !rememberInput
        ) {

            return;

        }


        const email =
            emailInput.value.trim();


        try {

            if (
                rememberInput.checked &&
                email
            ) {

                /*
                 * ONLY EMAIL IS STORED.
                 */

                localStorage.setItem(
                    REMEMBER_EMAIL_KEY,
                    email
                );

            } else {

                localStorage.removeItem(
                    REMEMBER_EMAIL_KEY
                );

            }

        } catch (error) {

            /*
             * Ignore storage errors.
             */

        }

    }


    /* ========================================================
       DASHBOARD URL
       ======================================================== */

    function redirectToDashboard() {

        /*
         * login.php and index.php are in the same directory.
         */

        const portalUrl =
            "index.php#dashboard";


        log(
            "Redirecting to client dashboard:",
            portalUrl
        );


        window.location.replace(
            portalUrl
        );

    }


    /* ========================================================
       JSON RESPONSE
       ======================================================== */

    async function parseJsonResponse(
        response
    ) {

        const contentType =
            response.headers.get(
                "content-type"
            ) || "";


        /*
         * Normal JSON response.
         */

        if (
            contentType
                .toLowerCase()
                .includes(
                    "application/json"
                )
        ) {

            try {

                return await response.json();

            } catch (error) {

                return {

                    success:
                        false,

                    message:
                        "Invalid JSON response from server."

                };

            }

        }


        /*
         * Fallback for PHP responses without a JSON
         * content type.
         */

        let text =
            "";


        try {

            text =
                await response.text();

        } catch (error) {

            text =
                "";

        }


        if (text) {

            try {

                return JSON.parse(
                    text
                );

            } catch (error) {

                /*
                 * Not JSON.
                 */

            }

        }


        return {

            success:
                false,

            message:
                text
                    ? text.substring(
                        0,
                        500
                    )
                    : "Server returned an invalid response."

        };

    }


    /* ========================================================
       SUCCESS RESPONSE
       ======================================================== */

    function isSuccessResponse(
        result
    ) {

        if (
            !result ||
            typeof result !== "object"
        ) {

            return false;

        }


        return (
            result.success === true ||
            result.status === "success"
        );

    }


    /* ========================================================
       RESPONSE MESSAGE
       ======================================================== */

    function getResponseMessage(
        result,
        fallback
    ) {

        if (
            result &&
            typeof result.message === "string" &&
            result.message.trim()
        ) {

            return result.message.trim();

        }


        return fallback;

    }


    /* ========================================================
       CSRF TOKEN
       ======================================================== */

    function extractCsrfToken(
        result
    ) {

        if (
            !result ||
            typeof result !== "object"
        ) {

            return "";

        }


        /*
         * Direct token.
         */

        if (
            typeof result.token === "string" &&
            result.token.trim()
        ) {

            return result.token.trim();

        }


        /*
         * csrf_token.
         */

        if (
            typeof result.csrf_token === "string" &&
            result.csrf_token.trim()
        ) {

            return result.csrf_token.trim();

        }


        /*
         * Nested data.token.
         */

        if (
            result.data &&
            typeof result.data === "object"
        ) {

            if (
                typeof result.data.token === "string" &&
                result.data.token.trim()
            ) {

                return result.data.token.trim();

            }


            if (
                typeof result.data.csrf_token === "string" &&
                result.data.csrf_token.trim()
            ) {

                return result.data.csrf_token.trim();

            }


            if (
                typeof result.data.csrfToken === "string" &&
                result.data.csrfToken.trim()
            ) {

                return result.data.csrfToken.trim();

            }

        }


        return "";

    }


    /* ========================================================
       WAIT
       ======================================================== */

    function wait(
        milliseconds
    ) {

        return new Promise(
            function (resolve) {

                window.setTimeout(
                    resolve,
                    milliseconds
                );

            }
        );

    }


    /* ========================================================
       PUBLIC API
       ======================================================== */

    window.TenspickClientLogin = {

        init:
            init,

        destroy:
            destroy,

        loadCsrfToken:
            loadCsrfToken,

        checkExistingSession:
            checkExistingSession,

        verifyAuthenticatedClient:
            verifyAuthenticatedClient,

        getApiBase:
            function () {

                return API_BASE;

            },

        getAppRoot:
            function () {

                return APP_ROOT;

            },

        getEndpoints:
            function () {

                return {

                    login:
                        ENDPOINTS.login,

                    logout:
                        ENDPOINTS.logout,

                    me:
                        ENDPOINTS.me,

                    csrf:
                        ENDPOINTS.csrf

                };

            }

    };


    /*
     * Backward compatibility.
     */

    window.ClientLogin =
        window.TenspickClientLogin;


    /* ========================================================
       DOM READY
       ======================================================== */

    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            init,
            {
                once:
                    true
            }
        );

    } else {

        init();

    }


})(window, document);