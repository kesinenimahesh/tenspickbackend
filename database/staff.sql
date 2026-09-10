CREATE TABLE staff (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    staff_code VARCHAR(30) NOT NULL UNIQUE,

    name VARCHAR(150) NOT NULL,

    email VARCHAR(150) DEFAULT NULL UNIQUE,

    phone VARCHAR(30) DEFAULT NULL,

    department VARCHAR(100) DEFAULT NULL,

    designation VARCHAR(100) DEFAULT NULL,

    joining_date DATE DEFAULT NULL,

    salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'active',
        'inactive',
        'suspended'
    ) NOT NULL DEFAULT 'active',

    profile_image VARCHAR(255) DEFAULT NULL,

    username VARCHAR(100) DEFAULT NULL UNIQUE,

    password_hash VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_staff_status (status),
    INDEX idx_staff_department (department),
    INDEX idx_staff_designation (designation),
    INDEX idx_staff_name (name)
);