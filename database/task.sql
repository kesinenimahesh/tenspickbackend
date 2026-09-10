CREATE TABLE tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    task_code VARCHAR(30) NOT NULL UNIQUE,

    project_id INT UNSIGNED NOT NULL,
    assigned_staff_id INT UNSIGNED NOT NULL,

    task_title VARCHAR(200) NOT NULL,

    priority ENUM(
        'low',
        'medium',
        'high',
        'urgent'
    ) NOT NULL DEFAULT 'medium',

    description TEXT DEFAULT NULL,

    start_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,

    status ENUM(
        'todo',
        'assigned',
        'in_progress',
        'review',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'todo',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tasks_project (project_id),
    INDEX idx_tasks_staff (assigned_staff_id),
    INDEX idx_tasks_status (status),
    INDEX idx_tasks_priority (priority),
    INDEX idx_tasks_due_date (due_date),
    INDEX idx_tasks_start_date (start_date),
    INDEX idx_tasks_title (task_title),

    CONSTRAINT fk_tasks_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_tasks_staff
        FOREIGN KEY (assigned_staff_id)
        REFERENCES staff(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);



CREATE TABLE task_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    task_id INT UNSIGNED NOT NULL,

    staff_id INT UNSIGNED DEFAULT NULL,

    update_text TEXT NOT NULL,

    update_type ENUM(
        'update',
        'status_change',
        'completion'
    ) NOT NULL DEFAULT 'update',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_task_updates_task (task_id),
    INDEX idx_task_updates_staff (staff_id),
    INDEX idx_task_updates_created (created_at),

    CONSTRAINT fk_task_updates_task
        FOREIGN KEY (task_id)
        REFERENCES tasks(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_task_updates_staff
        FOREIGN KEY (staff_id)
        REFERENCES staff(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);