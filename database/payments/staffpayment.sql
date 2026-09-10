CREATE TABLE IF NOT EXISTS staff_payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    payment_code VARCHAR(40) NOT NULL,

    staff_id INT UNSIGNED NOT NULL,

    payment_type ENUM(
        'salary',
        'advance',
        'bonus',
        'incentive',
        'reimbursement',
        'other'
    ) NOT NULL DEFAULT 'salary',

    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    payment_date DATE NOT NULL,

    payment_period VARCHAR(50) NOT NULL,

    payment_method ENUM(
        'cash',
        'upi',
        'bank_transfer',
        'card',
        'cheque',
        'other'
    ) NOT NULL DEFAULT 'bank_transfer',

    remarks TEXT DEFAULT NULL,

    added_by INT UNSIGNED DEFAULT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_staff_payment_code (payment_code),

    KEY idx_staff_payments_staff (staff_id),
    KEY idx_staff_payments_type (payment_type),
    KEY idx_staff_payments_date (payment_date),
    KEY idx_staff_payments_period (payment_period),
    KEY idx_staff_payments_method (payment_method),
    KEY idx_staff_payments_added_by (added_by),

    CONSTRAINT fk_staff_payments_staff
        FOREIGN KEY (staff_id)
        REFERENCES staff(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_staff_payments_added_by
        FOREIGN KEY (added_by)
        REFERENCES staff(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;