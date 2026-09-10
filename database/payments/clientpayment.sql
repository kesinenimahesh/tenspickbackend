/* ============================================================
   TENSPICK CRM
   CLIENT PAYMENTS
   ============================================================ */

CREATE TABLE IF NOT EXISTS client_payments (

    /* --------------------------------------------------------
       PRIMARY IDENTIFICATION
       -------------------------------------------------------- */

    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    payment_code VARCHAR(40) NOT NULL,

    /* --------------------------------------------------------
       CLIENT / PROJECT
       -------------------------------------------------------- */

    client_id INT UNSIGNED NOT NULL,

    project_id INT UNSIGNED NOT NULL,

    /* --------------------------------------------------------
       PAYMENT DETAILS
       -------------------------------------------------------- */

    /*
    | Payment purpose is FREE TEXT.
    |
    | Examples:
    | Advance Payment
    | Website Development Payment
    | Final Payment
    | Hosting Payment
    | Design Payment
    */

    purpose VARCHAR(255) DEFAULT NULL,

    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    payment_date DATE NOT NULL,

    payment_method ENUM(
        'cash',
        'upi',
        'bank_transfer',
        'card',
        'cheque',
        'other'
    ) NOT NULL,

    transaction_id VARCHAR(150) DEFAULT NULL,

    remarks TEXT DEFAULT NULL,

    /* --------------------------------------------------------
       HISTORICAL BALANCE SNAPSHOT
       --------------------------------------------------------
       These values are stored at the time of payment.

       They MUST NOT be recalculated when future payments
       are added.
       -------------------------------------------------------- */

    project_amount_snapshot DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    paid_before DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    paid_after DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    remaining_after DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    /* --------------------------------------------------------
       AUDIT
       -------------------------------------------------------- */

    added_by INT UNSIGNED DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    /* --------------------------------------------------------
       PRIMARY KEY
       -------------------------------------------------------- */

    PRIMARY KEY (id),

    /* --------------------------------------------------------
       UNIQUE PAYMENT CODE
       -------------------------------------------------------- */

    UNIQUE KEY uq_client_payment_code (payment_code),

    /* --------------------------------------------------------
       INDEXES
       -------------------------------------------------------- */

    KEY idx_client_payments_client (client_id),

    KEY idx_client_payments_project (project_id),

    KEY idx_client_payments_date (payment_date),

    KEY idx_client_payments_method (payment_method),

    KEY idx_client_payments_added_by (added_by),

    /* --------------------------------------------------------
       CLIENT FOREIGN KEY
       -------------------------------------------------------- */

    CONSTRAINT fk_client_payments_client
        FOREIGN KEY (client_id)
        REFERENCES clients(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    /* --------------------------------------------------------
       PROJECT FOREIGN KEY
       -------------------------------------------------------- */

    CONSTRAINT fk_client_payments_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    /* --------------------------------------------------------
       STAFF FOREIGN KEY
       -------------------------------------------------------- */

    CONSTRAINT fk_client_payments_staff
        FOREIGN KEY (added_by)
        REFERENCES staff(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;