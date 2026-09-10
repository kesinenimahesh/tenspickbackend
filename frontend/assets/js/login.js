/* ============================================================
   TENSPICK
   LOGIN PAGE JAVASCRIPT
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  /* ========================================================
       ELEMENTS
       ======================================================== */

  const loginForm = document.getElementById("loginForm");

  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const rememberInput = document.getElementById("remember");

  const passwordToggle = document.getElementById("passwordToggle");

  const forgotPassword = document.getElementById("forgotPassword");

  const loginButton = document.getElementById("loginButton");

  const loginMessage = document.getElementById("loginMessage");

  const emailError = document.getElementById("emailError");

  const passwordError = document.getElementById("passwordError");

  /* ========================================================
       API BASE URL
       ======================================================== */

  /*
   * Backend will be connected after the login UI is completed.
   *
   * IMPORTANT:
   * Do not add /api here.
   *
   * API endpoints will later be:
   *
   * ../backend/public/api/security/csrf
   * ../backend/public/api/auth/login
   */

  const API_BASE_URL =
    window.TENSPICK_API_BASE || "../backend/public/index.php";

  /* ========================================================
       MESSAGE HELPER
       ======================================================== */

  function setMessage(element, message) {
    if (!element) {
      return;
    }

    element.textContent = message || "";
  }

  /* ========================================================
       CLEAR ERRORS
       ======================================================== */

  function clearErrors() {
    setMessage(emailError, "");
    setMessage(passwordError, "");
    setMessage(loginMessage, "");
  }

  /* ========================================================
       EMAIL VALIDATION
       ======================================================== */

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  /* ========================================================
       BUTTON LOADING
       ======================================================== */

  function setLoading(isLoading) {
    if (!loginButton) {
      return;
    }

    loginButton.disabled = isLoading;

    if (isLoading) {
      loginButton.classList.add("loading");

      loginButton.setAttribute("aria-busy", "true");
    } else {
      loginButton.classList.remove("loading");

      loginButton.removeAttribute("aria-busy");
    }
  }

  /* ========================================================
       PASSWORD SHOW / HIDE
       ======================================================== */

  if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener("click", function () {
      const passwordVisible = passwordInput.type === "text";

      if (passwordVisible) {
        passwordInput.type = "password";

        passwordToggle.textContent = "Show";

        passwordToggle.setAttribute("aria-label", "Show password");
      } else {
        passwordInput.type = "text";

        passwordToggle.textContent = "Hide";

        passwordToggle.setAttribute("aria-label", "Hide password");
      }
    });
  }

  /* ========================================================
       EMAIL INPUT
       ======================================================== */

  if (emailInput) {
    emailInput.addEventListener("input", function () {
      setMessage(emailError, "");
      setMessage(loginMessage, "");
    });
  }

  /* ========================================================
       PASSWORD INPUT
       ======================================================== */

  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      setMessage(passwordError, "");
      setMessage(loginMessage, "");
    });
  }

  /* ========================================================
       FORGOT PASSWORD
       ======================================================== */

  if (forgotPassword) {
    forgotPassword.addEventListener("click", function (event) {
      event.preventDefault();

      setMessage(loginMessage, "Password recovery will be available soon.");
    });
  }

  /* ========================================================
       VALIDATE FORM
       ======================================================== */

  function validateForm() {
    clearErrors();

    let valid = true;

    const email = emailInput ? emailInput.value.trim() : "";

    const password = passwordInput ? passwordInput.value : "";

    /* ----------------------------------------------------
           EMAIL
           ---------------------------------------------------- */

    if (!email) {
      setMessage(emailError, "Email address is required.");

      valid = false;
    } else if (!isValidEmail(email)) {
      setMessage(emailError, "Enter a valid email address.");

      valid = false;
    }

    /* ----------------------------------------------------
           PASSWORD
           ---------------------------------------------------- */

    if (!password) {
      setMessage(passwordError, "Password is required.");

      valid = false;
    } else if (password.length < 8) {
      setMessage(passwordError, "Password must contain at least 8 characters.");

      valid = false;
    }

    return {
      valid: valid,
      email: email,
      password: password,
    };
  }

  /* ========================================================
       LOGIN FORM
       ======================================================== */

  if (loginForm) {
    loginForm.addEventListener("submit", async function (event) {
      event.preventDefault();

      /* --------------------------------------------
                   Validate
                   -------------------------------------------- */

      const formData = validateForm();

      if (!formData.valid) {
        return;
      }

      /* --------------------------------------------
                   Loading
                   -------------------------------------------- */

      setLoading(true);

      try {
        /* ========================================
                       STEP 1
                       REQUEST CSRF TOKEN
                       ======================================== */

        const csrfResponse = await fetch(API_BASE_URL + "/api/security/csrf", {
          method: "GET",

          credentials: "same-origin",

          headers: {
            Accept: "application/json",
          },
        });

        if (!csrfResponse.ok) {
          throw new Error("Authentication service is not available.");
        }

        const csrfData = await csrfResponse.json();

        if (!csrfData.success || !csrfData.data || !csrfData.data.token) {
          throw new Error("Unable to initialize secure login.");
        }

        /* ========================================
                       STEP 2
                       LOGIN REQUEST
                       ======================================== */

        const loginResponse = await fetch(API_BASE_URL + "/api/auth/login", {
          method: "POST",

          credentials: "same-origin",

          headers: {
            Accept: "application/json",

            "Content-Type": "application/json",

            "X-CSRF-Token": csrfData.data.token,
          },

          body: JSON.stringify({
            email: formData.email,

            password: formData.password,

            remember: rememberInput ? rememberInput.checked : false,
          }),
        });

        /* ----------------------------------------
                       Parse response
                       ---------------------------------------- */

        const loginData = await loginResponse.json();

        /* ----------------------------------------
                       Login error
                       ---------------------------------------- */

        if (!loginResponse.ok || !loginData.success) {
          throw new Error(loginData.message || "Invalid email or password.");
        }

        /* ----------------------------------------
                       Login successful
                       ---------------------------------------- */

        window.location.href = "index.php";
      } catch (error) {
        setMessage(
          loginMessage,
          error && error.message
            ? error.message
            : "Unable to connect to the server.",
        );

        setLoading(false);
      }
    });
  }
});
