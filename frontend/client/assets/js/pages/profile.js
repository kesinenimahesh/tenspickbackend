"use strict";

/**
 * ============================================================
 * TENSPICK CRM
 * CLIENT PORTAL
 * PROFILE MODULE
 * ============================================================
 *
 * FILE:
 * assets/js/pages/profile.js
 *
 * ============================================================
 *
 * PURPOSE
 * ============================================================
 *
 * Read-only authenticated client profile.
 *
 * ============================================================
 *
 * BACKEND
 * ============================================================
 *
 * GET /api/client-auth/me
 *
 * ============================================================
 *
 * SECURITY
 * ============================================================
 *
 * - No client_id is sent from frontend.
 * - Backend identifies client from PHP session.
 * - No profile editing from this page.
 * - No fake profile data.
 *
 * ============================================================
 *
 * ROUTER CONTRACT
 * ============================================================
 *
 * init(container, params)
 * destroy()
 * refresh()
 *
 * ============================================================
 */

(function (window, document) {

    "use strict";


    /* ========================================================
     * API
     * ======================================================== */

    const API =
        window.TenspickClientAPI;


    const AUTH =
        window.TenspickClientAuth;


    /* ========================================================
     * STATE
     * ======================================================== */

    const state = {

        initialized: false,

        destroyed: false,

        loading: false,

        client: null,

        container: null,

        requestId: 0

    };


    /* ========================================================
     * HELPERS
     * ======================================================== */

    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return "";
        }


        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    function displayValue(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return "—";
        }


        const text =
            String(value).trim();


        if (!text) {
            return "—";
        }


        return escapeHtml(text);

    }


    /* ========================================================
     * DATE
     * ======================================================== */

    function formatDate(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return "—";
        }


        const raw =
            String(value).trim();


        if (!raw) {
            return "—";
        }


        /*
         * MySQL DATE
         *
         * YYYY-MM-DD
         */
        const dateOnly =
            raw.match(
                /^(\d{4})-(\d{2})-(\d{2})$/
            );


        if (dateOnly) {

            const year =
                Number(dateOnly[1]);

            const month =
                Number(dateOnly[2]);

            const day =
                Number(dateOnly[3]);


            const date =
                new Date(
                    year,
                    month - 1,
                    day
                );


            if (
                !Number.isNaN(
                    date.getTime()
                )
            ) {

                return date.toLocaleDateString(
                    "en-IN",
                    {
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    }
                );

            }

        }


        /*
         * MySQL DATETIME
         *
         * YYYY-MM-DD HH:MM:SS
         */
        const dateTime =
            raw.match(
                /^(\d{4})-(\d{2})-(\d{2})[\sT]/
            );


        if (dateTime) {

            const year =
                Number(dateTime[1]);

            const month =
                Number(dateTime[2]);

            const day =
                Number(dateTime[3]);


            const date =
                new Date(
                    year,
                    month - 1,
                    day
                );


            if (
                !Number.isNaN(
                    date.getTime()
                )
            ) {

                return date.toLocaleDateString(
                    "en-IN",
                    {
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    }
                );

            }

        }


        /*
         * Generic fallback.
         */
        const date =
            new Date(raw);


        if (
            !Number.isNaN(
                date.getTime()
            )
        ) {

            return date.toLocaleDateString(
                "en-IN",
                {
                    day: "2-digit",
                    month: "short",
                    year: "numeric"
                }
            );

        }


        return escapeHtml(raw);

    }


    /* ========================================================
     * INITIALS
     * ======================================================== */

    function getInitials(name) {

        const text =
            String(
                name || ""
            ).trim();


        if (!text) {
            return "C";
        }


        const parts =
            text
                .split(/\s+/)
                .filter(Boolean);


        if (
            parts.length === 1
        ) {

            return parts[0]
                .substring(0, 2)
                .toUpperCase();

        }


        return (
            parts[0].charAt(0) +
            parts[1].charAt(0)
        ).toUpperCase();

    }


    /* ========================================================
     * CLIENT NAME
     * ======================================================== */

    function getClientName(client) {

        return (
            client?.client_name ||
            client?.company_name ||
            client?.name ||
            "Client"
        );

    }


    /* ========================================================
     * API RESPONSE EXTRACTION
     * ======================================================== */

    function extractClient(
        apiResponse
    ) {

        if (!apiResponse) {
            return null;
        }


        /*
         * Current TenspickClientAPI wrapper:
         *
         * {
         *     ok: true,
         *     status: 200,
         *     data: {
         *         success: true,
         *         message: "...",
         *         data: {
         *             client: {...}
         *         }
         *     }
         * }
         */


        const backend =
            apiResponse.data &&
            typeof apiResponse.data === "object"
                ? apiResponse.data
                : apiResponse;


        /*
         * Normal response.
         */
        if (
            backend.data &&
            typeof backend.data === "object"
        ) {

            if (
                backend.data.client &&
                typeof backend.data.client === "object"
            ) {

                return backend.data.client;

            }


            /*
             * Some auth responses may return the client
             * object directly inside data.
             */
            if (
                !Array.isArray(
                    backend.data
                )
            ) {

                const possibleClient =
                    backend.data;


                if (
                    possibleClient.id ||
                    possibleClient.client_id ||
                    possibleClient.client_code ||
                    possibleClient.client_name
                ) {

                    return possibleClient;

                }

            }

        }


        /*
         * Alternative backend structure.
         */
        if (
            backend.client &&
            typeof backend.client === "object"
        ) {

            return backend.client;

        }


        /*
         * Direct object fallback.
         */
        if (
            backend.id ||
            backend.client_id ||
            backend.client_code ||
            backend.client_name
        ) {

            return backend;

        }


        return null;

    }


    /* ========================================================
     * FIELD
     * ======================================================== */

    function detailField(
        icon,
        label,
        fieldValue
    ) {

        return `

            <div
                class="client-profile-field"
            >

                <div
                    class="
                        client-profile-field-icon
                    "
                >

                    <i
                        class="bi ${icon}"
                        aria-hidden="true"
                    ></i>

                </div>


                <div
                    class="
                        client-profile-field-content
                    "
                >

                    <span>
                        ${escapeHtml(label)}
                    </span>


                    <strong>
                        ${displayValue(fieldValue)}
                    </strong>

                </div>

            </div>

        `;

    }


    /* ========================================================
     * SECTION
     * ======================================================== */

    function section(
        icon,
        title,
        content
    ) {

        return `

            <section
                class="
                    client-profile-section
                "
            >

                <div
                    class="
                        client-profile-section-header
                    "
                >

                    <div
                        class="
                            client-profile-section-title
                        "
                    >

                        <div
                            class="
                                client-profile-section-icon
                            "
                        >

                            <i
                                class="bi ${icon}"
                                aria-hidden="true"
                            ></i>

                        </div>


                        <div>

                            <h3>
                                ${escapeHtml(title)}
                            </h3>

                        </div>

                    </div>

                </div>


                <div
                    class="
                        client-profile-section-body
                    "
                >

                    ${content}

                </div>

            </section>

        `;

    }


    /* ========================================================
     * PAGE SHELL
     * ======================================================== */

    function renderShell(
        container
    ) {

        container.innerHTML = `

            <section
                id="clientProfilePage"
                class="
                    client-profile-page
                "
            >

                <!-- PAGE HEADER -->

                <div
                    class="
                        client-profile-heading
                    "
                >

                    <div>

                        <div
                            class="
                                client-page-eyebrow
                            "
                        >
                            ACCOUNT
                        </div>


                        <h2>
                            My Profile
                        </h2>


                        <p>
                            View your account and business information.
                        </p>

                    </div>


                    <div
                        class="
                            client-profile-heading-icon
                        "
                    >

                        <i
                            class="
                                bi
                                bi-person-vcard
                            "
                            aria-hidden="true"
                        ></i>

                    </div>

                </div>


                <!-- LOADING -->

                <div
                    id="clientProfileLoading"
                    class="
                        client-profile-loading
                    "
                >

                    <div
                        class="
                            client-profile-spinner
                        "
                    ></div>


                    <p>
                        Loading profile...
                    </p>

                </div>


                <!-- ERROR -->

                <div
                    id="clientProfileError"
                    class="
                        client-profile-error
                    "
                    hidden
                >

                    <div
                        class="
                            client-profile-error-icon
                        "
                    >

                        <i
                            class="
                                bi
                                bi-exclamation-circle
                            "
                            aria-hidden="true"
                        ></i>

                    </div>


                    <div>

                        <h3>
                            Unable to load profile
                        </h3>


                        <p
                            id="
                                clientProfileErrorMessage
                            "
                        >
                            Please try again.
                        </p>


                        <button
                            type="button"
                            id="clientProfileRetry"
                            class="
                                client-profile-retry
                            "
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-clockwise
                                "
                                aria-hidden="true"
                            ></i>


                            Retry

                        </button>

                    </div>

                </div>


                <!-- CONTENT -->

                <div
                    id="clientProfileContent"
                    hidden
                ></div>

            </section>

        `;


        const retry =
            container.querySelector(
                "#clientProfileRetry"
            );


        if (retry) {

            retry.addEventListener(
                "click",
                function () {

                    loadProfile();

                }
            );

        }

    }


    /* ========================================================
     * PROFILE HERO
     * ======================================================== */

    function renderProfileHeader(
        client
    ) {

        const name =
            getClientName(
                client
            );


        const company =
            client?.company_name ||
            "";


        const initials =
            getInitials(
                name
            );


        const rawStatus =
            String(
                client?.status ||
                "active"
            )
                .trim()
                .toLowerCase();


        let statusLabel =
            "Active";


        if (
            rawStatus &&
            rawStatus !== "active"
        ) {

            statusLabel =
                rawStatus
                    .charAt(0)
                    .toUpperCase() +
                rawStatus.slice(1);

        }


        const safeStatus =
            rawStatus.replace(
                /[^a-z0-9_-]/g,
                ""
            ) ||
            "active";


        return `

            <section
                class="
                    client-profile-hero
                "
            >

                <div
                    class="
                        client-profile-avatar
                    "
                >

                    <span>
                        ${escapeHtml(
                            initials
                        )}
                    </span>

                </div>


                <div
                    class="
                        client-profile-identity
                    "
                >

                    <h3>
                        ${escapeHtml(
                            name
                        )}
                    </h3>


                    ${
                        company &&
                        company !== name
                            ? `

                                <p>
                                    ${escapeHtml(
                                        company
                                    )}
                                </p>

                              `
                            : ""
                    }


                    <div
                        class="
                            client-profile-meta
                        "
                    >

                        ${
                            client?.client_code
                                ? `

                                    <span>

                                        <i
                                            class="
                                                bi
                                                bi-hash
                                            "
                                            aria-hidden="true"
                                        ></i>

                                        ${escapeHtml(
                                            client.client_code
                                        )}

                                    </span>

                                  `
                                : ""
                        }


                        <span
                            class="
                                client-profile-status
                                client-profile-status-${safeStatus}
                            "
                        >

                            <span
                                class="
                                    client-profile-status-dot
                                "
                            ></span>


                            ${escapeHtml(
                                statusLabel
                            )}

                        </span>

                    </div>

                </div>


                <div
                    class="
                        client-profile-account-badge
                    "
                >

                    <i
                        class="
                            bi
                            bi-shield-check
                        "
                        aria-hidden="true"
                    ></i>


                    <div>

                        <strong>
                            Client Account
                        </strong>


                        <span>
                            Portal Access
                        </span>

                    </div>

                </div>

            </section>

        `;

    }


    /* ========================================================
     * CONTACT INFORMATION
     * ======================================================== */

    function renderContact(
        client
    ) {

        return section(
            "bi-person-lines-fill",
            "Contact Information",

            `

                <div
                    class="
                        client-profile-grid
                    "
                >

                    ${detailField(
                        "bi-person",
                        "Client Name",
                        client?.client_name
                    )}


                    ${detailField(
                        "bi-building",
                        "Company Name",
                        client?.company_name
                    )}


                    ${detailField(
                        "bi-phone",
                        "Mobile",
                        client?.mobile
                    )}


                    ${detailField(
                        "bi-whatsapp",
                        "WhatsApp",
                        client?.whatsapp
                    )}


                    ${detailField(
                        "bi-envelope",
                        "Email",
                        client?.email
                    )}


                    ${detailField(
                        "bi-telephone",
                        "Alternate Phone",
                        client?.alternate_phone
                    )}

                </div>

            `
        );

    }


    /* ========================================================
     * BUSINESS INFORMATION
     * ======================================================== */

    function renderBusiness(
        client
    ) {

        return section(
            "bi-briefcase",
            "Business Information",

            `

                <div
                    class="
                        client-profile-grid
                    "
                >

                    ${detailField(
                        "bi-diagram-3",
                        "Business Type",
                        client?.business_type
                    )}


                    ${detailField(
                        "bi-tags",
                        "Industry",
                        client?.industry
                    )}


                    ${detailField(
                        "bi-globe2",
                        "Website",
                        client?.website
                    )}


                    ${detailField(
                        "bi-card-text",
                        "Client Code",
                        client?.client_code
                    )}

                </div>


                ${
                    client?.business_description
                        ? `

                            <div
                                class="
                                    client-profile-description
                                "
                            >

                                <span>
                                    Business Description
                                </span>


                                <p>
                                    ${escapeHtml(
                                        client.business_description
                                    )}
                                </p>

                            </div>

                          `
                        : ""
                }

            `
        );

    }


    /* ========================================================
     * ADDRESS
     * ======================================================== */

    function renderAddress(
        client
    ) {

        return section(
            "bi-geo-alt",
            "Address Information",

            `

                <div
                    class="
                        client-profile-grid
                    "
                >

                    ${detailField(
                        "bi-pin-map",
                        "Address",
                        client?.address
                    )}


                    ${detailField(
                        "bi-buildings",
                        "City",
                        client?.city
                    )}


                    ${detailField(
                        "bi-map",
                        "State",
                        client?.state
                    )}


                    ${detailField(
                        "bi-mailbox",
                        "Pincode",
                        client?.pincode
                    )}

                </div>

            `
        );

    }


    /* ========================================================
     * CONTACT PERSON
     * ======================================================== */

    function renderContactPerson(
        client
    ) {

        return section(
            "bi-person-badge",
            "Contact Person",

            `

                <div
                    class="
                        client-profile-grid
                    "
                >

                    ${detailField(
                        "bi-person",
                        "Name",
                        client?.contact_person_name
                    )}


                    ${detailField(
                        "bi-briefcase",
                        "Designation",
                        client?.contact_person_designation
                    )}


                    ${detailField(
                        "bi-phone",
                        "Mobile",
                        client?.contact_person_mobile
                    )}


                    ${detailField(
                        "bi-envelope",
                        "Email",
                        client?.contact_person_email
                    )}

                </div>

            `
        );

    }


    /* ========================================================
     * BILLING
     * ======================================================== */

    function renderBilling(
        client
    ) {

        return section(
            "bi-receipt",
            "Billing Information",

            `

                <div
                    class="
                        client-profile-grid
                    "
                >

                    ${detailField(
                        "bi-person-vcard",
                        "Billing Name",
                        client?.billing_name
                    )}


                    ${detailField(
                        "bi-file-earmark-text",
                        "GST Number",
                        client?.gst_number
                    )}


                    ${detailField(
                        "bi-credit-card-2-front",
                        "PAN Number",
                        client?.pan_number
                    )}


                    ${detailField(
                        "bi-geo",
                        "Billing Address",
                        client?.billing_address
                    )}

                </div>

            `
        );

    }


    /* ========================================================
     * ACCOUNT INFORMATION
     * ======================================================== */

    function renderAccount(
        client
    ) {

        /*
         * Login email may be returned as either:
         *
         * login_email
         * email
         */
        const loginEmail =
            client?.login_email ||
            client?.email;


        return section(
            "bi-shield-lock",
            "Account Information",

            `

                <div
                    class="
                        client-profile-grid
                    "
                >

                    ${detailField(
                        "bi-envelope-at",
                        "Login Email",
                        loginEmail
                    )}


                    ${detailField(
                        "bi-person-vcard",
                        "Client ID",
                        client?.id
                    )}


                    ${detailField(
                        "bi-calendar-plus",
                        "Account Created",
                        formatDate(
                            client?.created_at
                        )
                    )}


                    ${detailField(
                        "bi-calendar-check",
                        "Last Updated",
                        formatDate(
                            client?.updated_at
                        )
                    )}

                </div>


                <div
                    class="
                        client-profile-security-note
                    "
                >

                    <div
                        class="
                            client-profile-security-icon
                        "
                    >

                        <i
                            class="
                                bi
                                bi-lock
                            "
                            aria-hidden="true"
                        ></i>

                    </div>


                    <div>

                        <strong>
                            Account Security
                        </strong>


                        <p>
                            Your profile is displayed from
                            your authenticated client account.
                        </p>

                    </div>

                </div>

            `
        );

    }


    /* ========================================================
     * RENDER CONTENT
     * ======================================================== */

    function renderContent(
        client
    ) {

        const content =
            document.getElementById(
                "clientProfileContent"
            );


        if (!content) {
            return;
        }


        content.innerHTML = `

            ${renderProfileHeader(client)}

            <div
                class="
                    client-profile-sections
                "
            >

                ${renderContact(client)}

                ${renderBusiness(client)}

                ${renderAddress(client)}

                ${renderContactPerson(client)}

                ${renderBilling(client)}

                ${renderAccount(client)}

            </div>

        `;


        content.hidden =
            false;

    }


    /* ========================================================
     * LOADING STATE
     * ======================================================== */

    function setLoading(
        isLoading
    ) {

        const loading =
            document.getElementById(
                "clientProfileLoading"
            );


        const content =
            document.getElementById(
                "clientProfileContent"
            );


        const error =
            document.getElementById(
                "clientProfileError"
            );


        if (loading) {

            loading.hidden =
                !isLoading;

        }


        if (isLoading) {

            if (content) {
                content.hidden =
                    true;
            }


            if (error) {
                error.hidden =
                    true;
            }

        }

    }


    /* ========================================================
     * ERROR
     * ======================================================== */

    function showError(
        message
    ) {

        const loading =
            document.getElementById(
                "clientProfileLoading"
            );


        const content =
            document.getElementById(
                "clientProfileContent"
            );


        const error =
            document.getElementById(
                "clientProfileError"
            );


        const errorMessage =
            document.getElementById(
                "clientProfileErrorMessage"
            );


        if (loading) {
            loading.hidden =
                true;
        }


        if (content) {
            content.hidden =
                true;
        }


        if (errorMessage) {

            errorMessage.textContent =
                message ||
                "Unable to load profile.";

        }


        if (error) {

            error.hidden =
                false;

        }

    }


    /* ========================================================
     * LOAD PROFILE
     * ======================================================== */

    async function loadProfile() {

        if (
            state.destroyed
        ) {

            return false;

        }


        if (
            !API ||
            typeof API.get !== "function"
        ) {

            showError(
                "Client API service is not available."
            );


            return false;

        }


        const requestId =
            ++state.requestId;


        state.loading =
            true;


        setLoading(
            true
        );


        try {

            /*
             * =================================================
             * IMPORTANT
             * =================================================
             *
             * No client_id.
             *
             * The backend gets the authenticated client
             * from the PHP session.
             */
            const response =
                await API.get(
                    "/client-auth/me"
                );


            if (
                state.destroyed ||
                requestId !==
                state.requestId
            ) {

                return false;

            }


            const backend =
                response?.data;


            /*
             * API wrapper normally gives:
             *
             * response.data.success
             */
            if (
                backend &&
                backend.success === false
            ) {

                throw new Error(
                    backend.message ||
                    "Unable to load profile."
                );

            }


            const client =
                extractClient(
                    response
                );


            if (!client) {

                throw new Error(
                    "Client profile information was not returned."
                );

            }


            state.client =
                client;


            if (
                state.destroyed ||
                requestId !==
                state.requestId
            ) {

                return false;

            }


            renderContent(
                client
            );


            clearError();


            setLoading(
                false
            );


            console.log(
                "[Tenspick Client Profile] " +
                "Profile loaded successfully."
            );


            return true;


        } catch (error) {

            if (
                error?.name ===
                "AbortError"
            ) {

                return false;

            }


            if (
                state.destroyed ||
                requestId !==
                state.requestId
            ) {

                return false;

            }


            console.error(
                "[Tenspick Client Profile] " +
                "Profile loading failed:",
                error
            );


            showError(
                error?.message ||
                "Unable to load profile."
            );


            return false;


        } finally {

            if (
                requestId ===
                state.requestId
            ) {

                state.loading =
                    false;

            }

        }

    }


    /* ========================================================
     * CLEAR ERROR
     * ======================================================== */

    function clearError() {

        const error =
            document.getElementById(
                "clientProfileError"
            );


        if (error) {

            error.hidden =
                true;

        }

    }


    /* ========================================================
     * INIT
     * ======================================================== */

    async function init(
        container,
        params
    ) {

        if (!container) {

            return false;

        }


        state.initialized =
            true;

        state.destroyed =
            false;

        state.container =
            container;

        state.client =
            null;

        state.requestId =
            0;


        renderShell(
            container
        );


        /*
         * The Client Router already performs authentication.
         *
         * We use the authenticated client endpoint to obtain
         * the complete profile.
         */

        const success =
            await loadProfile();


        return success;

    }


    /* ========================================================
     * DESTROY
     * ======================================================== */

    function destroy() {

        state.destroyed =
            true;


        state.requestId++;


        state.initialized =
            false;


        state.loading =
            false;


        state.client =
            null;


        state.container =
            null;

    }


    /* ========================================================
     * REFRESH
     * ======================================================== */

    async function refresh() {

        if (
            !state.initialized ||
            state.destroyed ||
            !state.container
        ) {

            return false;

        }


        state.destroyed =
            false;


        return await loadProfile();

    }


    /* ========================================================
     * PUBLIC MODULE
     * ======================================================== */

    window.TenspickClientProfile = {

        init,

        destroy,

        refresh,

        getClient: function () {

            return state.client;

        }

    };


})(window, document);