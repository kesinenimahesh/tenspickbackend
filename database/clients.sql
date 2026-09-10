CREATE TABLE clients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    client_code VARCHAR(30) NOT NULL,
    source_lead_code VARCHAR(30) DEFAULT NULL,

    /* =========================================================
       BASIC INFORMATION
       ========================================================= */

    client_name VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    alternate_phone VARCHAR(20) DEFAULT NULL,

    /* =========================================================
       BUSINESS INFORMATION
       ========================================================= */

    business_type VARCHAR(100) DEFAULT NULL,
    industry VARCHAR(100) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    business_description TEXT DEFAULT NULL,

    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    pincode VARCHAR(10) DEFAULT NULL,

    /* =========================================================
       CONTACT PERSON
       ========================================================= */

    contact_person_name VARCHAR(150) DEFAULT NULL,
    contact_person_designation VARCHAR(100) DEFAULT NULL,
    contact_person_mobile VARCHAR(20) DEFAULT NULL,
    contact_person_email VARCHAR(150) DEFAULT NULL,

    /* =========================================================
       BILLING INFORMATION
       ========================================================= */

    billing_name VARCHAR(150) DEFAULT NULL,
    gst_number VARCHAR(30) DEFAULT NULL,
    pan_number VARCHAR(20) DEFAULT NULL,
    billing_address TEXT DEFAULT NULL,

    /* =========================================================
       CLIENT PORTAL
       ========================================================= */

    login_email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,

    /* =========================================================
       STATUS
       ========================================================= */

    status ENUM(
        'active',
        'inactive',
        'suspended'
    ) NOT NULL DEFAULT 'active',

    /* =========================================================
       INTERNAL
       ========================================================= */

    internal_notes TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_clients_client_code (client_code),

    UNIQUE KEY uq_clients_login_email (login_email),

    KEY idx_clients_name (client_name),
    KEY idx_clients_company (company_name),
    KEY idx_clients_mobile (mobile),
    KEY idx_clients_email (email),
    KEY idx_clients_status (status),
    KEY idx_clients_source_lead (source_lead_code)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;