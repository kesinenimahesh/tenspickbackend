CREATE TABLE projects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    project_code VARCHAR(30) NOT NULL,
    client_id INT UNSIGNED NOT NULL,

    project_name VARCHAR(200) NOT NULL,
    project_type VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,

    start_date DATE DEFAULT NULL,
    expected_completion DATE DEFAULT NULL,

    budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'planning',
        'not_started',
        'in_progress',
        'review',
        'client_review',
        'completed',
        'on_hold',
        'cancelled'
    ) NOT NULL DEFAULT 'planning',

    progress_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,

    

    live_website_link VARCHAR(500) DEFAULT NULL,

domain_purchased_email VARCHAR(255) DEFAULT NULL,

seo_added_email VARCHAR(255) DEFAULT NULL,

project_manager_id INT UNSIGNED DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_projects_project_code (project_code),

    KEY idx_projects_client_id (client_id),
    KEY idx_projects_name (project_name),
    KEY idx_projects_type (project_type),
    KEY idx_projects_status (status),
    KEY idx_projects_manager (project_manager_id),
    KEY idx_projects_start_date (start_date),
    KEY idx_projects_expected_completion (expected_completion),

    CONSTRAINT fk_projects_client
        FOREIGN KEY (client_id)
        REFERENCES clients(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;



CREATE TABLE project_milestones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    project_id INT UNSIGNED NOT NULL,

    milestone_name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,

    sequence_no INT UNSIGNED NOT NULL DEFAULT 1,

    status ENUM(
        'not_started',
        'in_progress',
        'completed',
        'on_hold'
    ) NOT NULL DEFAULT 'not_started',

    progress_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,

    start_date DATE DEFAULT NULL,
    expected_completion DATE DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_milestones_project_id (project_id),
    KEY idx_milestones_status (status),
    KEY idx_milestones_sequence (project_id, sequence_no),

    CONSTRAINT fk_project_milestones_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;