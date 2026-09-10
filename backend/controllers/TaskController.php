<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * TASK CONTROLLER
 * ============================================================
 *
 * Handles:
 * - Task listing
 * - Task details
 * - Task creation
 * - Task update
 * - Task deletion
 * - Task updates/history
 * - Mandatory completion response
 * - Staff task list
 * - Staff task summary
 *
 * Uses the existing Tenspick global-function architecture:
 * - db()
 * - successResponse()
 * - errorResponse()
 * - validationResponse()
 * - notFoundResponse()
 * - serverErrorResponse()
 * - getJsonInput()
 * - requireAdminAuthentication()
 * - requireCsrfToken()
 *
 * ============================================================
 */


/* ============================================================
   TASK CONSTANTS
   ============================================================ */

if (!defined('TENSPICK_TASK_PRIORITIES')) {
    define('TENSPICK_TASK_PRIORITIES', [
        'low',
        'medium',
        'high',
        'urgent',
    ]);
}

if (!defined('TENSPICK_TASK_STATUSES')) {
    define('TENSPICK_TASK_STATUSES', [
        'todo',
        'assigned',
        'in_progress',
        'review',
        'completed',
        'cancelled',
    ]);
}

if (!defined('TENSPICK_TASK_UPDATE_TYPES')) {
    define('TENSPICK_TASK_UPDATE_TYPES', [
        'update',
        'status_change',
        'completion',
    ]);
}


/* ============================================================
   GENERATE TASK CODE
   ============================================================ */

function generateTaskCode(PDO $pdo): string
{
    $datePrefix = 'TSK-' . date('Ymd') . '-';

    $stmt = $pdo->prepare(
        'SELECT task_code
         FROM tasks
         WHERE task_code LIKE ?
         ORDER BY id DESC
         LIMIT 1'
    );

    $stmt->execute([
        $datePrefix . '%',
    ]);

    $lastCode = $stmt->fetchColumn();

    $nextNumber = 1;

    if (is_string($lastCode) && $lastCode !== '') {
        $parts = explode('-', $lastCode);

        if (isset($parts[2]) && ctype_digit($parts[2])) {
            $nextNumber = ((int) $parts[2]) + 1;
        }
    }

    return $datePrefix . str_pad(
        (string) $nextNumber,
        4,
        '0',
        STR_PAD_LEFT
    );
}


/* ============================================================
   VALIDATE DATE
   ============================================================ */

function validateTaskDate(mixed $value): bool
{
    if ($value === null || $value === '') {
        return true;
    }

    if (!is_string($value)) {
        return false;
    }

    $date = DateTime::createFromFormat(
        'Y-m-d',
        $value
    );

    return $date !== false &&
        $date->format('Y-m-d') === $value;
}


/* ============================================================
   VALIDATE TASK DATA
   ============================================================ */

function validateTaskData(
    array $data,
    bool $isUpdate = false
): array {
    $errors = [];

    /*
    |--------------------------------------------------------------------------
    | Task title
    |--------------------------------------------------------------------------
    */

    if (
        !$isUpdate ||
        array_key_exists('task_title', $data)
    ) {
        $taskTitle = trim(
            (string) ($data['task_title'] ?? '')
        );

        if ($taskTitle === '') {
            $errors['task_title'] =
                'Task title is required.';
        } elseif (mb_strlen($taskTitle) > 200) {
            $errors['task_title'] =
                'Task title cannot exceed 200 characters.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Project
    |--------------------------------------------------------------------------
    */

    if (
        !$isUpdate ||
        array_key_exists('project_id', $data)
    ) {
        $projectId = $data['project_id'] ?? null;

        if (
            $projectId === null ||
            $projectId === '' ||
            filter_var(
                $projectId,
                FILTER_VALIDATE_INT
            ) === false ||
            (int) $projectId <= 0
        ) {
            $errors['project_id'] =
                'A valid project is required.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Assigned staff
    |--------------------------------------------------------------------------
    */

    if (
        !$isUpdate ||
        array_key_exists('assigned_staff_id', $data)
    ) {
        $staffId =
            $data['assigned_staff_id'] ?? null;

        if (
            $staffId === null ||
            $staffId === '' ||
            filter_var(
                $staffId,
                FILTER_VALIDATE_INT
            ) === false ||
            (int) $staffId <= 0
        ) {
            $errors['assigned_staff_id'] =
                'A valid staff member is required.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Priority
    |--------------------------------------------------------------------------
    */

    if (
        array_key_exists('priority', $data) &&
        !in_array(
            $data['priority'],
            TENSPICK_TASK_PRIORITIES,
            true
        )
    ) {
        $errors['priority'] =
            'Invalid task priority.';
    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    if (
        array_key_exists('status', $data) &&
        !in_array(
            $data['status'],
            TENSPICK_TASK_STATUSES,
            true
        )
    ) {
        $errors['status'] =
            'Invalid task status.';
    }


    /*
    |--------------------------------------------------------------------------
    | Start date
    |--------------------------------------------------------------------------
    */

    if (
        array_key_exists('start_date', $data) &&
        !validateTaskDate($data['start_date'])
    ) {
        $errors['start_date'] =
            'Invalid start date. Use YYYY-MM-DD.';
    }


    /*
    |--------------------------------------------------------------------------
    | Due date
    |--------------------------------------------------------------------------
    */

    if (
        array_key_exists('due_date', $data) &&
        !validateTaskDate($data['due_date'])
    ) {
        $errors['due_date'] =
            'Invalid due date. Use YYYY-MM-DD.';
    }


    /*
    |--------------------------------------------------------------------------
    | Date relationship
    |--------------------------------------------------------------------------
    */

    $startDate =
        $data['start_date'] ?? null;

    $dueDate =
        $data['due_date'] ?? null;

    if (
        $startDate !== null &&
        $startDate !== '' &&
        $dueDate !== null &&
        $dueDate !== '' &&
        validateTaskDate($startDate) &&
        validateTaskDate($dueDate) &&
        $dueDate < $startDate
    ) {
        $errors['due_date'] =
            'Due date cannot be before start date.';
    }


    /*
    |--------------------------------------------------------------------------
    | Description
    |--------------------------------------------------------------------------
    */

    if (
        array_key_exists('description', $data) &&
        $data['description'] !== null &&
        !is_string($data['description'])
    ) {
        $errors['description'] =
            'Invalid description.';
    }

    return $errors;
}


/* ============================================================
   CHECK PROJECT EXISTS
   ============================================================ */

function taskProjectExists(
    PDO $pdo,
    int $projectId
): bool {
    $stmt = $pdo->prepare(
        'SELECT id
         FROM projects
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $projectId,
    ]);

    return $stmt->fetchColumn() !== false;
}


/* ============================================================
   CHECK STAFF EXISTS
   ============================================================ */

function taskStaffExists(
    PDO $pdo,
    int $staffId
): bool {
    $stmt = $pdo->prepare(
        'SELECT id
         FROM staff
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $staffId,
    ]);

    return $stmt->fetchColumn() !== false;
}


/* ============================================================
   GET TASKS
   ============================================================ */

function getTasks(): void
{
    requireAdminAuthentication();

    try {
        $pdo = db();

        $search = trim(
            (string) ($_GET['search'] ?? '')
        );

        $projectId =
            $_GET['project_id'] ?? '';

        $staffId =
            $_GET['assigned_staff_id'] ?? '';

        $status = trim(
            (string) ($_GET['status'] ?? '')
        );

        $priority = trim(
            (string) ($_GET['priority'] ?? '')
        );

        $overdue =
            (string) ($_GET['overdue'] ?? '');

        $page = max(
            1,
            (int) ($_GET['page'] ?? 1)
        );

        $perPage =
            (int) ($_GET['per_page'] ?? 10);

        if ($perPage < 1) {
            $perPage = 10;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }


        /*
        |--------------------------------------------------------------------------
        | WHERE
        |--------------------------------------------------------------------------
        */

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(
                t.task_code LIKE ?
                OR t.task_title LIKE ?
                OR p.project_name LIKE ?
                OR s.name LIKE ?
            )';

            $searchValue =
                '%' . $search . '%';

            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
        }


        if (
            $projectId !== '' &&
            filter_var(
                $projectId,
                FILTER_VALIDATE_INT
            ) !== false &&
            (int) $projectId > 0
        ) {
            $where[] =
                't.project_id = ?';

            $params[] =
                (int) $projectId;
        }


        if (
            $staffId !== '' &&
            filter_var(
                $staffId,
                FILTER_VALIDATE_INT
            ) !== false &&
            (int) $staffId > 0
        ) {
            $where[] =
                't.assigned_staff_id = ?';

            $params[] =
                (int) $staffId;
        }


        if (
            $status !== '' &&
            in_array(
                $status,
                TENSPICK_TASK_STATUSES,
                true
            )
        ) {
            $where[] =
                't.status = ?';

            $params[] =
                $status;
        }


        if (
            $priority !== '' &&
            in_array(
                $priority,
                TENSPICK_TASK_PRIORITIES,
                true
            )
        ) {
            $where[] =
                't.priority = ?';

            $params[] =
                $priority;
        }


        if ($overdue === '1') {
            $where[] = "
                t.due_date IS NOT NULL
                AND t.due_date < CURDATE()
                AND t.status NOT IN (
                    'completed',
                    'cancelled'
                )
            ";
        }


        $whereSql = '';

        if ($where !== []) {
            $whereSql =
                'WHERE ' .
                implode(
                    ' AND ',
                    $where
                );
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $countSql = "
            SELECT COUNT(*)
            FROM tasks t

            INNER JOIN projects p
                ON p.id = t.project_id

            INNER JOIN staff s
                ON s.id = t.assigned_staff_id

            $whereSql
        ";

        $countStmt =
            $pdo->prepare($countSql);

        $countStmt->execute($params);

        $total =
            (int) $countStmt->fetchColumn();

        $totalPages = max(
            1,
            (int) ceil(
                $total / $perPage
            )
        );

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset =
            ($page - 1) * $perPage;


        /*
        |--------------------------------------------------------------------------
        | TASKS
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT
                t.id,
                t.task_code,
                t.project_id,
                t.assigned_staff_id,
                t.task_title,
                t.priority,
                t.description,
                t.start_date,
                t.due_date,
                t.status,
                t.created_at,
                t.updated_at,

                p.project_name,
                p.project_code,

                s.name AS staff_name,
                s.staff_code,

                CASE
                    WHEN
                        t.due_date IS NOT NULL
                        AND t.due_date < CURDATE()
                        AND t.status NOT IN (
                            'completed',
                            'cancelled'
                        )
                    THEN 1
                    ELSE 0
                END AS is_overdue

            FROM tasks t

            INNER JOIN projects p
                ON p.id = t.project_id

            INNER JOIN staff s
                ON s.id = t.assigned_staff_id

            $whereSql

            ORDER BY t.id DESC

            LIMIT $perPage
            OFFSET $offset
        ";

        $stmt =
            $pdo->prepare($sql);

        $stmt->execute($params);

        $items =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | GLOBAL STATISTICS
        |--------------------------------------------------------------------------
        */

        $statsStmt = $pdo->query("
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN status = 'todo'
                        THEN 1
                        ELSE 0
                    END
                ) AS todo,

                SUM(
                    CASE
                        WHEN status = 'assigned'
                        THEN 1
                        ELSE 0
                    END
                ) AS assigned,

                SUM(
                    CASE
                        WHEN status = 'in_progress'
                        THEN 1
                        ELSE 0
                    END
                ) AS in_progress,

                SUM(
                    CASE
                        WHEN status = 'review'
                        THEN 1
                        ELSE 0
                    END
                ) AS review,

                SUM(
                    CASE
                        WHEN status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed,

                SUM(
                    CASE
                        WHEN status = 'cancelled'
                        THEN 1
                        ELSE 0
                    END
                ) AS cancelled,

                SUM(
                    CASE
                        WHEN
                            due_date IS NOT NULL
                            AND due_date < CURDATE()
                            AND status NOT IN (
                                'completed',
                                'cancelled'
                            )
                        THEN 1
                        ELSE 0
                    END
                ) AS overdue

            FROM tasks
        ");

        $stats =
            $statsStmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];


        /*
        |--------------------------------------------------------------------------
        | NORMALIZE NUMBERS
        |--------------------------------------------------------------------------
        */

        $stats = [
            'total' =>
                (int) ($stats['total'] ?? 0),

            'todo' =>
                (int) ($stats['todo'] ?? 0),

            'assigned' =>
                (int) ($stats['assigned'] ?? 0),

            'in_progress' =>
                (int) ($stats['in_progress'] ?? 0),

            'review' =>
                (int) ($stats['review'] ?? 0),

            'completed' =>
                (int) ($stats['completed'] ?? 0),

            'cancelled' =>
                (int) ($stats['cancelled'] ?? 0),

            'overdue' =>
                (int) ($stats['overdue'] ?? 0),
        ];


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        |
        | Existing Tenspick response pattern:
        | successResponse(message, data, status)
        |
        */

        successResponse(
            'Tasks loaded successfully.',
            [
                'items' => $items,
                'tasks' => $items,

                'pagination' => [
                    'page' =>
                        $page,

                    'per_page' =>
                        $perPage,

                    'total' =>
                        $total,

                    'total_pages' =>
                        $totalPages,
                ],

                'stats' =>
                    $stats,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'getTasks error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to load tasks.'
        );
    }
}


/* ============================================================
   GET SINGLE TASK
   ============================================================ */

function getTask(int $id): void
{
    requireAdminAuthentication();

    if ($id <= 0) {
        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        $stmt = $pdo->prepare("
            SELECT
                t.*,

                p.project_name,
                p.project_code,

                s.name AS staff_name,
                s.staff_code,
                s.email AS staff_email,
                s.phone AS staff_phone,

                CASE
                    WHEN
                        t.due_date IS NOT NULL
                        AND t.due_date < CURDATE()
                        AND t.status NOT IN (
                            'completed',
                            'cancelled'
                        )
                    THEN 1
                    ELSE 0
                END AS is_overdue

            FROM tasks t

            INNER JOIN projects p
                ON p.id = t.project_id

            INNER JOIN staff s
                ON s.id = t.assigned_staff_id

            WHERE t.id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $id,
        ]);

        $task =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$task) {
            notFoundResponse(
                'Task not found.'
            );

            return;
        }


        $task['id'] =
            (int) $task['id'];

        $task['project_id'] =
            (int) $task['project_id'];

        $task['assigned_staff_id'] =
            (int) $task['assigned_staff_id'];

        $task['is_overdue'] =
            (int) $task['is_overdue'];


        successResponse(
            'Task loaded successfully.',
            [
                'task' => $task,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'getTask error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to load task.'
        );
    }
}


/* ============================================================
   CREATE TASK
   ============================================================ */

function createTask(): void
{
    requireAdminAuthentication();
    requireCsrfToken();

    try {
        $pdo = db();

        $data = getJsonInput();

        if (!is_array($data)) {
            validationResponse([
                'request' =>
                    'Invalid request data.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE
        |--------------------------------------------------------------------------
        */

        $errors =
            validateTaskData(
                $data,
                false
            );

        if ($errors !== []) {
            validationResponse($errors);
            return;
        }


        $projectId =
            (int) $data['project_id'];

        $staffId =
            (int) $data['assigned_staff_id'];


        /*
        |--------------------------------------------------------------------------
        | PROJECT
        |--------------------------------------------------------------------------
        */

        if (
            !taskProjectExists(
                $pdo,
                $projectId
            )
        ) {
            errorResponse(
                'Selected project does not exist.',
                null,
                422
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        */

        if (
            !taskStaffExists(
                $pdo,
                $staffId
            )
        ) {
            errorResponse(
                'Selected staff member does not exist.',
                null,
                422
            );

            return;
        }


        $taskTitle =
            trim(
                (string) $data['task_title']
            );

        $priority =
            $data['priority'] ?? 'medium';

        $status =
            $data['status'] ?? 'todo';

        $description =
            array_key_exists(
                'description',
                $data
            )
                ? trim(
                    (string) (
                        $data['description']
                    )
                )
                : null;

        $startDate =
            !empty($data['start_date'])
                ? (string) $data['start_date']
                : null;

        $dueDate =
            !empty($data['due_date'])
                ? (string) $data['due_date']
                : null;


        /*
        |--------------------------------------------------------------------------
        | TASK CODE
        |--------------------------------------------------------------------------
        */

        $taskCode =
            generateTaskCode($pdo);


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | INSERT TASK
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO tasks (
                    task_code,
                    project_id,
                    assigned_staff_id,
                    task_title,
                    priority,
                    description,
                    start_date,
                    due_date,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $taskCode,
                $projectId,
                $staffId,
                $taskTitle,
                $priority,
                $description !== ''
                    ? $description
                    : null,
                $startDate,
                $dueDate,
                $status,
            ]);

            $taskId =
                (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | INITIAL HISTORY
            |--------------------------------------------------------------------------
            */

            $historyStmt = $pdo->prepare("
                INSERT INTO task_updates (
                    task_id,
                    staff_id,
                    update_text,
                    update_type
                )
                VALUES (?, NULL, ?, 'status_change')
            ");

            $historyStmt->execute([
                $taskId,
                'Task created with status: ' .
                $status,
            ]);


            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }


        successResponse(
            'Task created successfully.',
            [
                'task_id' =>
                    $taskId,

                'task_code' =>
                    $taskCode,
            ],
            201
        );

    } catch (PDOException $e) {

        error_log(
            'createTask database error: ' .
            $e->getMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE TASK CODE
        |--------------------------------------------------------------------------
        */

        $errorCode =
            $e->errorInfo[1] ?? null;

        if ((int) $errorCode === 1062) {
            errorResponse(
                'Unable to generate a unique task code. Please try again.',
                null,
                409
            );

            return;
        }

        serverErrorResponse(
            'Unable to create task.'
        );

    } catch (Throwable $e) {

        error_log(
            'createTask error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to create task.'
        );
    }
}


/* ============================================================
   UPDATE TASK
   ============================================================ */

function updateTask(int $id): void
{
    requireAdminAuthentication();
    requireCsrfToken();

    if ($id <= 0) {
        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        $data = getJsonInput();

        if (!is_array($data)) {
            validationResponse([
                'request' =>
                    'Invalid request data.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | EXISTING TASK
        |--------------------------------------------------------------------------
        */

        $existingStmt = $pdo->prepare("
            SELECT *
            FROM tasks
            WHERE id = ?
            LIMIT 1
        ");

        $existingStmt->execute([
            $id,
        ]);

        $existing =
            $existingStmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$existing) {
            notFoundResponse(
                'Task not found.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $errors =
            validateTaskData(
                $data,
                true
            );

        if ($errors !== []) {
            validationResponse($errors);
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | RESOLVE VALUES
        |--------------------------------------------------------------------------
        */

        $taskTitle =
            array_key_exists(
                'task_title',
                $data
            )
                ? trim(
                    (string) $data['task_title']
                )
                : $existing['task_title'];

        $projectId =
            array_key_exists(
                'project_id',
                $data
            )
                ? (int) $data['project_id']
                : (int) $existing['project_id'];

        $staffId =
            array_key_exists(
                'assigned_staff_id',
                $data
            )
                ? (int) $data['assigned_staff_id']
                : (int) $existing['assigned_staff_id'];

        $priority =
            array_key_exists(
                'priority',
                $data
            )
                ? $data['priority']
                : $existing['priority'];

        $status =
            array_key_exists(
                'status',
                $data
            )
                ? $data['status']
                : $existing['status'];

        $description =
            array_key_exists(
                'description',
                $data
            )
                ? (
                    $data['description'] === null
                        ? null
                        : trim(
                            (string) $data['description']
                        )
                )
                : $existing['description'];

        $startDate =
            array_key_exists(
                'start_date',
                $data
            )
                ? (
                    !empty($data['start_date'])
                        ? (string) $data['start_date']
                        : null
                )
                : $existing['start_date'];

        $dueDate =
            array_key_exists(
                'due_date',
                $data
            )
                ? (
                    !empty($data['due_date'])
                        ? (string) $data['due_date']
                        : null
                )
                : $existing['due_date'];


        /*
        |--------------------------------------------------------------------------
        | PROJECT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            !taskProjectExists(
                $pdo,
                $projectId
            )
        ) {
            errorResponse(
                'Selected project does not exist.',
                null,
                422
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF CHECK
        |--------------------------------------------------------------------------
        */

        if (
            !taskStaffExists(
                $pdo,
                $staffId
            )
        ) {
            errorResponse(
                'Selected staff member does not exist.',
                null,
                422
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | COMPLETION
        |--------------------------------------------------------------------------
        */

        $oldStatus =
            (string) $existing['status'];

        $changingToCompleted =
            $status === 'completed' &&
            $oldStatus !== 'completed';

        $completionResponse =
            trim(
                (string) (
                    $data['completion_response']
                    ?? ''
                )
            );


        if ($changingToCompleted) {

            if ($completionResponse === '') {
                validationResponse([
                    'completion_response' =>
                        'Completion response is required when completing a task.',
                ]);

                return;
            }

            if (
                mb_strlen(
                    $completionResponse
                ) > 5000
            ) {
                validationResponse([
                    'completion_response' =>
                        'Completion response cannot exceed 5000 characters.',
                ]);

                return;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | UPDATE TASK
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE tasks
                SET
                    project_id = ?,
                    assigned_staff_id = ?,
                    task_title = ?,
                    priority = ?,
                    description = ?,
                    start_date = ?,
                    due_date = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $projectId,
                $staffId,
                $taskTitle,
                $priority,
                $description !== ''
                    ? $description
                    : null,
                $startDate,
                $dueDate,
                $status,
                $id,
            ]);


            /*
            |--------------------------------------------------------------------------
            | STATUS HISTORY
            |--------------------------------------------------------------------------
            */

            if ($oldStatus !== $status) {

                $statusStmt = $pdo->prepare("
                    INSERT INTO task_updates (
                        task_id,
                        staff_id,
                        update_text,
                        update_type
                    )
                    VALUES (?, NULL, ?, 'status_change')
                ");

                $statusStmt->execute([
                    $id,
                    'Status changed from "' .
                    $oldStatus .
                    '" to "' .
                    $status .
                    '".',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | COMPLETION HISTORY
            |--------------------------------------------------------------------------
            */

            if ($changingToCompleted) {

                $completionStmt =
                    $pdo->prepare("
                        INSERT INTO task_updates (
                            task_id,
                            staff_id,
                            update_text,
                            update_type
                        )
                        VALUES (?, ?, ?, 'completion')
                    ");

                $completionStmt->execute([
                    $id,
                    $staffId,
                    $completionResponse,
                ]);
            }


            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }


        successResponse(
            'Task updated successfully.',
            [
                'task_id' =>
                    $id,

                'completed' =>
                    $changingToCompleted,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'updateTask error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to update task.'
        );
    }
}


/* ============================================================
   DELETE TASK
   ============================================================ */

function deleteTask(int $id): void
{
    requireAdminAuthentication();
    requireCsrfToken();

    if ($id <= 0) {
        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT id
             FROM tasks
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute([
            $id,
        ]);

        if ($stmt->fetchColumn() === false) {
            notFoundResponse(
                'Task not found.'
            );

            return;
        }


        $deleteStmt = $pdo->prepare(
            'DELETE FROM tasks
             WHERE id = ?'
        );

        $deleteStmt->execute([
            $id,
        ]);


        successResponse(
            'Task deleted successfully.',
            [
                'task_id' => $id,
            ]
        );

    } catch (PDOException $e) {

        error_log(
            'deleteTask database error: ' .
            $e->getMessage()
        );

        $errorCode =
            $e->errorInfo[1] ?? null;

        if ((int) $errorCode === 1451) {
            errorResponse(
                'This task cannot be deleted because it is referenced by another record.',
                null,
                409
            );

            return;
        }

        serverErrorResponse(
            'Unable to delete task.'
        );

    } catch (Throwable $e) {

        error_log(
            'deleteTask error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to delete task.'
        );
    }
}


/* ============================================================
   GET TASK UPDATES / HISTORY
   ============================================================ */

function getTaskUpdates(int $taskId): void
{
    requireAdminAuthentication();

    if ($taskId <= 0) {
        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();


        /*
        |--------------------------------------------------------------------------
        | TASK
        |--------------------------------------------------------------------------
        */

        $taskStmt = $pdo->prepare("
            SELECT
                id,
                task_code,
                task_title,
                status
            FROM tasks
            WHERE id = ?
            LIMIT 1
        ");

        $taskStmt->execute([
            $taskId,
        ]);

        $task =
            $taskStmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$task) {
            notFoundResponse(
                'Task not found.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | HISTORY
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                tu.id,
                tu.task_id,
                tu.staff_id,
                tu.update_text,
                tu.update_type,
                tu.created_at,
                tu.updated_at,

                s.name AS staff_name,
                s.staff_code

            FROM task_updates tu

            LEFT JOIN staff s
                ON s.id = tu.staff_id

            WHERE tu.task_id = ?

            ORDER BY
                tu.created_at DESC,
                tu.id DESC
        ");

        $stmt->execute([
            $taskId,
        ]);

        $updates =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        successResponse(
            'Task history loaded successfully.',
            [
                'task' =>
                    $task,

                'items' =>
                    $updates,

                'updates' =>
                    $updates,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'getTaskUpdates error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to load task history.'
        );
    }
}


/* ============================================================
   CREATE TASK UPDATE
   ============================================================ */

function createTaskUpdate(int $taskId): void
{
    requireAdminAuthentication();
    requireCsrfToken();

    if ($taskId <= 0) {
        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        $data = getJsonInput();

        if (!is_array($data)) {
            validationResponse([
                'request' =>
                    'Invalid request data.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | TASK
        |--------------------------------------------------------------------------
        */

        $taskStmt = $pdo->prepare("
            SELECT
                id,
                status
            FROM tasks
            WHERE id = ?
            LIMIT 1
        ");

        $taskStmt->execute([
            $taskId,
        ]);

        $task =
            $taskStmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$task) {
            notFoundResponse(
                'Task not found.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE TEXT
        |--------------------------------------------------------------------------
        */

        $updateText =
            trim(
                (string) (
                    $data['update_text']
                    ?? ''
                )
            );

        if ($updateText === '') {
            validationResponse([
                'update_text' =>
                    'Update text is required.',
            ]);

            return;
        }

        if (
            mb_strlen($updateText) > 5000
        ) {
            validationResponse([
                'update_text' =>
                    'Update text cannot exceed 5000 characters.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        */

        $staffId = null;

        if (
            array_key_exists(
                'staff_id',
                $data
            ) &&
            $data['staff_id'] !== null &&
            $data['staff_id'] !== ''
        ) {

            if (
                filter_var(
                    $data['staff_id'],
                    FILTER_VALIDATE_INT
                ) === false ||
                (int) $data['staff_id'] <= 0
            ) {
                validationResponse([
                    'staff_id' =>
                        'Invalid staff member.',
                ]);

                return;
            }

            $staffId =
                (int) $data['staff_id'];

            if (
                !taskStaffExists(
                    $pdo,
                    $staffId
                )
            ) {
                validationResponse([
                    'staff_id' =>
                        'Selected staff member does not exist.',
                ]);

                return;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | NORMAL UPDATE ONLY
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO task_updates (
                task_id,
                staff_id,
                update_text,
                update_type
            )
            VALUES (?, ?, ?, 'update')
        ");

        $stmt->execute([
            $taskId,
            $staffId,
            $updateText,
        ]);

        $updateId =
            (int) $pdo->lastInsertId();


        successResponse(
            'Task update added successfully.',
            [
                'update_id' =>
                    $updateId,

                'task_id' =>
                    $taskId,
            ],
            201
        );

    } catch (Throwable $e) {

        error_log(
            'createTaskUpdate error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to add task update.'
        );
    }
}


/* ============================================================
   UPDATE NORMAL TASK UPDATE
   ============================================================ */

function updateTaskUpdate(
    int $taskId,
    int $updateId
): void {
    requireAdminAuthentication();
    requireCsrfToken();

    if (
        $taskId <= 0 ||
        $updateId <= 0
    ) {
        errorResponse(
            'Invalid task or update ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        $data = getJsonInput();

        if (!is_array($data)) {
            validationResponse([
                'request' =>
                    'Invalid request data.',
            ]);

            return;
        }


        $updateText =
            trim(
                (string) (
                    $data['update_text']
                    ?? ''
                )
            );


        if ($updateText === '') {
            validationResponse([
                'update_text' =>
                    'Update text is required.',
            ]);

            return;
        }


        if (
            mb_strlen($updateText) > 5000
        ) {
            validationResponse([
                'update_text' =>
                    'Update text cannot exceed 5000 characters.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | EXISTING UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                update_type
            FROM task_updates
            WHERE id = ?
              AND task_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $updateId,
            $taskId,
        ]);

        $existing =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$existing) {
            notFoundResponse(
                'Task update not found.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | ONLY NORMAL UPDATES ARE EDITABLE
        |--------------------------------------------------------------------------
        */

        if (
            $existing['update_type'] !== 'update'
        ) {
            errorResponse(
                'Status change and completion history cannot be edited.',
                null,
                409
            );

            return;
        }


        $updateStmt = $pdo->prepare("
            UPDATE task_updates
            SET update_text = ?
            WHERE id = ?
              AND task_id = ?
        ");

        $updateStmt->execute([
            $updateText,
            $updateId,
            $taskId,
        ]);


        successResponse(
            'Task update edited successfully.',
            [
                'update_id' =>
                    $updateId,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'updateTaskUpdate error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to edit task update.'
        );
    }
}


/* ============================================================
   DELETE NORMAL TASK UPDATE
   ============================================================ */

function deleteTaskUpdate(
    int $taskId,
    int $updateId
): void {
    requireAdminAuthentication();
    requireCsrfToken();

    if (
        $taskId <= 0 ||
        $updateId <= 0
    ) {
        errorResponse(
            'Invalid task or update ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        $stmt = $pdo->prepare("
            SELECT
                id,
                update_type
            FROM task_updates
            WHERE id = ?
              AND task_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $updateId,
            $taskId,
        ]);

        $existing =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$existing) {
            notFoundResponse(
                'Task update not found.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | PROTECT HISTORY
        |--------------------------------------------------------------------------
        */

        if (
            $existing['update_type'] !== 'update'
        ) {
            errorResponse(
                'Status change and completion history cannot be deleted.',
                null,
                409
            );

            return;
        }


        $deleteStmt = $pdo->prepare("
            DELETE FROM task_updates
            WHERE id = ?
              AND task_id = ?
        ");

        $deleteStmt->execute([
            $updateId,
            $taskId,
        ]);


        successResponse(
            'Task update deleted successfully.',
            [
                'update_id' =>
                    $updateId,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'deleteTaskUpdate error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to delete task update.'
        );
    }
}


/* ============================================================
   GET STAFF TASKS
   ============================================================ */

function getStaffTasks(int $staffId): void
{
    requireAdminAuthentication();

    if ($staffId <= 0) {
        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        if (
            !taskStaffExists(
                $pdo,
                $staffId
            )
        ) {
            notFoundResponse(
                'Staff member not found.'
            );

            return;
        }


        $stmt = $pdo->prepare("
            SELECT
                t.id,
                t.task_code,
                t.project_id,
                t.assigned_staff_id,
                t.task_title,
                t.priority,
                t.description,
                t.start_date,
                t.due_date,
                t.status,
                t.created_at,
                t.updated_at,

                p.project_name,
                p.project_code,

                CASE
                    WHEN
                        t.due_date IS NOT NULL
                        AND t.due_date < CURDATE()
                        AND t.status NOT IN (
                            'completed',
                            'cancelled'
                        )
                    THEN 1
                    ELSE 0
                END AS is_overdue

            FROM tasks t

            INNER JOIN projects p
                ON p.id = t.project_id

            WHERE t.assigned_staff_id = ?

            ORDER BY

                CASE
                    WHEN
                        t.due_date IS NOT NULL
                        AND t.due_date < CURDATE()
                        AND t.status NOT IN (
                            'completed',
                            'cancelled'
                        )
                    THEN 0
                    ELSE 1
                END,

                CASE
                    WHEN t.due_date IS NULL
                    THEN 1
                    ELSE 0
                END,

                t.due_date ASC,
                t.id DESC
        ");

        $stmt->execute([
            $staffId,
        ]);

        $tasks =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        successResponse(
            'Staff tasks loaded successfully.',
            [
                'items' =>
                    $tasks,

                'tasks' =>
                    $tasks,
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'getStaffTasks error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to load staff tasks.'
        );
    }
}


/* ============================================================
   STAFF TASK SUMMARY
   ============================================================ */

function getStaffTaskSummary(
    int $staffId
): void {
    requireAdminAuthentication();

    if ($staffId <= 0) {
        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        return;
    }

    try {
        $pdo = db();

        if (
            !taskStaffExists(
                $pdo,
                $staffId
            )
        ) {
            notFoundResponse(
                'Staff member not found.'
            );

            return;
        }


        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN status NOT IN (
                            'completed',
                            'cancelled'
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS active,

                SUM(
                    CASE
                        WHEN status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed,

                SUM(
                    CASE
                        WHEN
                            due_date IS NOT NULL
                            AND due_date < CURDATE()
                            AND status NOT IN (
                                'completed',
                                'cancelled'
                            )
                        THEN 1
                        ELSE 0
                    END
                ) AS overdue

            FROM tasks

            WHERE assigned_staff_id = ?
        ");

        $stmt->execute([
            $staffId,
        ]);

        $summary =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];


        successResponse(
            'Staff task summary loaded successfully.',
            [
                'summary' => [
                    'total' =>
                        (int) (
                            $summary['total'] ?? 0
                        ),

                    'active' =>
                        (int) (
                            $summary['active'] ?? 0
                        ),

                    'completed' =>
                        (int) (
                            $summary['completed'] ?? 0
                        ),

                    'overdue' =>
                        (int) (
                            $summary['overdue'] ?? 0
                        ),
                ],
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'getStaffTaskSummary error: ' .
            $e->getMessage()
        );

        serverErrorResponse(
            'Unable to load staff task summary.'
        );
    }
}
