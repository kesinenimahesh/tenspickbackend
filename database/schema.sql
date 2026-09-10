/* ============================================================
   TENSPICK
   SOFTWARE COMPANY MANAGEMENT SYSTEM
   DATABASE FOUNDATION
   ============================================================

   Database:
   tenspick_management

   Authentication:
   Admin only

   Client portal authentication will be added with
   the Client module later.

   ============================================================ */


/* ============================================================
   DATABASE
   ============================================================ */

CREATE DATABASE IF NOT EXISTS tenspick_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;


USE tenspick_management;


/* ============================================================
   ADMINS
   ============================================================ */

CREATE TABLE IF NOT EXISTS admins (

    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(190) NOT NULL,

    password_hash VARCHAR(255) NOT NULL,

    status TINYINT(1) NOT NULL DEFAULT 1,

    last_login_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_admin_email (email),

    KEY idx_admin_status (status)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   ACTIVITY LOGS
   ============================================================ */

CREATE TABLE IF NOT EXISTS activity_logs (

    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    admin_id INT UNSIGNED NULL,

    action VARCHAR(100) NOT NULL,

    module VARCHAR(100) NOT NULL,

    record_id INT UNSIGNED NULL,

    description VARCHAR(1000) NULL,

    ip_address VARCHAR(45) NULL,

    user_agent VARCHAR(500) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_activity_admin (admin_id),

    KEY idx_activity_module (module),

    KEY idx_activity_record (record_id),

    KEY idx_activity_created (created_at),

    CONSTRAINT fk_activity_admin
        FOREIGN KEY (admin_id)
        REFERENCES admins(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins
(
    name,
    email,
    password_hash,
    status
)
VALUES
(
    'Tenspick Admin',
    'admin@tenspick.org',
    '$2y$10$WRWAJ0AYesQS1PfWSVvcj.WD8IyrDPORhhiSyyL4tjieu.VpmiFw6',
    1
);