<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * STAFF CONTROLLER
 * ============================================================
 *
 * STAFF MANAGEMENT
 *
 * ENDPOINTS
 * ---------
 *
 * GET    /api/staff
 * GET    /api/staff/{id}
 * POST   /api/staff
 * PUT    /api/staff/{id}
 * DELETE /api/staff/{id}
 *
 * ============================================================
 *
 * STAFF FIELDS
 * ------------
 *
 * Staff ID
 * Name
 * Email
 * Phone
 * Department
 * Designation
 * Joining Date
 * Salary
 * Status
 * Profile
 * Login
 *
 * ============================================================
 *
 * SECURITY
 * --------
 *
 * - Admin authentication
 * - CSRF for POST / PUT / DELETE
 * - PDO prepared statements
 * - Server-side validation
 * - Password hashing
 *
 * ============================================================
 */


/* ============================================================
   GET ALL STAFF
   GET /api/staff
   ============================================================ */

function getStaff(): void
{
    requireAdminAuthentication();

    try {

        $pdo = db();


        /* ====================================================
           PAGINATION
           ==================================================== */

        $page =
            max(
                1,
                (int) (
                    $_GET['page'] ?? 1
                )
            );


        $perPage =
            (int) (
                $_GET['per_page'] ?? 20
            );


        if ($perPage < 1) {
            $perPage = 20;
        }


        if ($perPage > 100) {
            $perPage = 100;
        }


        /* ====================================================
           FILTERS
           ==================================================== */

        $search =
            trim(
                (string) (
                    $_GET['search'] ?? ''
                )
            );


        $status =
            strtolower(
                trim(
                    (string) (
                        $_GET['status'] ?? ''
                    )
                )
            );


        $department =
            trim(
                (string) (
                    $_GET['department'] ?? ''
                )
            );


        $designation =
            trim(
                (string) (
                    $_GET['designation'] ?? ''
                )
            );


        $allowedStatuses = [
            'active',
            'inactive',
            'suspended'
        ];


        $conditions = [];

        $params = [];


        /* ====================================================
           SEARCH
           ==================================================== */

        if ($search !== '') {

            $conditions[] = "
                (
                    staff_code LIKE :search
                    OR name LIKE :search
                    OR email LIKE :search
                    OR phone LIKE :search
                    OR department LIKE :search
                    OR designation LIKE :search
                )
            ";

            $params[':search'] =
                '%' .
                $search .
                '%';
        }


        /* ====================================================
           STATUS
           ==================================================== */

        if (
            $status !== '' &&
            in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $conditions[] =
                'status = :status';

            $params[':status'] =
                $status;
        }


        /* ====================================================
           DEPARTMENT
           ==================================================== */

        if ($department !== '') {

            $conditions[] =
                'department = :department';

            $params[':department'] =
                $department;
        }


        /* ====================================================
           DESIGNATION
           ==================================================== */

        if ($designation !== '') {

            $conditions[] =
                'designation = :designation';

            $params[':designation'] =
                $designation;
        }


        /* ====================================================
           WHERE
           ==================================================== */

        $where = '';

        if (!empty($conditions)) {

            $where =
                'WHERE ' .
                implode(
                    ' AND ',
                    $conditions
                );
        }


        /* ====================================================
           TOTAL
           ==================================================== */

        $countStatement =
            $pdo->prepare(
                "
                SELECT COUNT(*)
                FROM staff
                {$where}
                "
            );


        $countStatement->execute(
            $params
        );


        $total =
            (int) $countStatement
                ->fetchColumn();


        /* ====================================================
           PAGINATION
           ==================================================== */

        $totalPages =
            $total > 0
                ? (int) ceil(
                    $total /
                    $perPage
                )
                : 0;


        if (
            $totalPages > 0 &&
            $page > $totalPages
        ) {

            $page =
                $totalPages;
        }


        /*
         * Keep page 1 when there
         * are no records.
         */

        if ($totalPages === 0) {
            $page = 1;
        }


        $offset =
            ($page - 1) *
            $perPage;


        /* ====================================================
           GET STAFF
           ==================================================== */

        $statement =
            $pdo->prepare(
                "
                SELECT

                    id,
                    staff_code,

                    name,
                    email,
                    phone,

                    department,
                    designation,

                    joining_date,
                    salary,

                    status,

                    profile_image,

                    username,

                    created_at,
                    updated_at

                FROM staff

                {$where}

                ORDER BY id DESC

                LIMIT :limit
                OFFSET :offset
                "
            );


        foreach (
            $params as $key => $value
        ) {

            $statement->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );
        }


        $statement->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );


        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );


        $statement->execute();


        $items =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        /* ====================================================
           STATISTICS
           ==================================================== */

        $statisticsStatement =
            $pdo->query(
                "
                SELECT

                    COUNT(*) AS total,

                    SUM(
                        CASE
                            WHEN status = 'active'
                            THEN 1
                            ELSE 0
                        END
                    ) AS active,

                    SUM(
                        CASE
                            WHEN status = 'inactive'
                            THEN 1
                            ELSE 0
                        END
                    ) AS inactive,

                    SUM(
                        CASE
                            WHEN status = 'suspended'
                            THEN 1
                            ELSE 0
                        END
                    ) AS suspended

                FROM staff
                "
            );


        $statistics =
            $statisticsStatement
                ->fetch(
                    PDO::FETCH_ASSOC
                );


        $statistics = [

            'total' =>
                (int) (
                    $statistics['total']
                    ?? 0
                ),

            'active' =>
                (int) (
                    $statistics['active']
                    ?? 0
                ),

            'inactive' =>
                (int) (
                    $statistics['inactive']
                    ?? 0
                ),

            'suspended' =>
                (int) (
                    $statistics['suspended']
                    ?? 0
                )

        ];


        /* ====================================================
           RESPONSE
           ==================================================== */

        successResponse(
            'Staff loaded successfully.',
            [

                'items' =>
                    $items,

                'staff' =>
                    $items,

                'pagination' => [

                    'page' =>
                        $page,

                    'per_page' =>
                        $perPage,

                    'total' =>
                        $total,

                    'total_pages' =>
                        $totalPages

                ],

                'statistics' =>
                    $statistics

            ]
        );


    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET STAFF ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to load staff.'
        );
    }
}


/* ============================================================
   GET SINGLE STAFF
   GET /api/staff/{id}
   ============================================================ */

function getStaffById(
    int $id
): void {

    requireAdminAuthentication();


    if ($id <= 0) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );
    }


    try {

        $pdo = db();


        $statement =
            $pdo->prepare(
                "
                SELECT

                    id,
                    staff_code,

                    name,
                    email,
                    phone,

                    department,
                    designation,

                    joining_date,
                    salary,

                    status,

                    profile_image,

                    username,

                    created_at,
                    updated_at

                FROM staff

                WHERE id = :id

                LIMIT 1
                "
            );


        $statement->execute([
            ':id' =>
                $id
        ]);


        $staff =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$staff) {

            notFoundResponse(
                'Staff member not found.'
            );
        }


        successResponse(
            'Staff member loaded successfully.',
            $staff
        );


    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET STAFF BY ID ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to load staff member.'
        );
    }
}


/* ============================================================
   CREATE STAFF
   POST /api/staff
   ============================================================ */

function createStaff(): void
{
    requireAdminAuthentication();

    requireCsrfToken();


    $data =
        getJsonInput();


    if (empty($data)) {

        validationResponse(
            [
                'form' =>
                    'No staff data was received.'
            ],
            'Please enter staff information.'
        );
    }


    /* ========================================================
       NAME
       ======================================================== */

    $name =
        trim(
            (string) (
                $data['name'] ?? ''
            )
        );


    if ($name === '') {

        validationResponse(
            [
                'name' =>
                    'Staff name is required.'
            ]
        );
    }


    if (mb_strlen($name) > 150) {

        validationResponse(
            [
                'name' =>
                    'Staff name cannot exceed 150 characters.'
            ]
        );
    }


    /* ========================================================
       EMAIL
       ======================================================== */

    $email =
        strtolower(
            trim(
                (string) (
                    $data['email'] ?? ''
                )
            )
        );


    if ($email !== '') {

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            validationResponse(
                [
                    'email' =>
                        'Please enter a valid email address.'
                ]
            );
        }


        if (mb_strlen($email) > 150) {

            validationResponse(
                [
                    'email' =>
                        'Email cannot exceed 150 characters.'
                ]
            );
        }
    }


    /* ========================================================
       PHONE
       ======================================================== */

    $phone =
        trim(
            (string) (
                $data['phone'] ?? ''
            )
        );


    if (mb_strlen($phone) > 30) {

        validationResponse(
            [
                'phone' =>
                    'Phone number cannot exceed 30 characters.'
            ]
        );
    }


    /* ========================================================
       DEPARTMENT
       ======================================================== */

    $department =
        trim(
            (string) (
                $data['department'] ?? ''
            )
        );


    if (
        mb_strlen($department) >
        100
    ) {

        validationResponse(
            [
                'department' =>
                    'Department cannot exceed 100 characters.'
            ]
        );
    }


    /* ========================================================
       DESIGNATION
       ======================================================== */

    $designation =
        trim(
            (string) (
                $data['designation'] ?? ''
            )
        );


    if (
        mb_strlen($designation) >
        100
    ) {

        validationResponse(
            [
                'designation' =>
                    'Designation cannot exceed 100 characters.'
            ]
        );
    }


    /* ========================================================
       JOINING DATE
       ======================================================== */

    $joiningDate =
        trim(
            (string) (
                $data['joining_date'] ?? ''
            )
        );


    if ($joiningDate === '') {
        $joiningDate = null;
    }


    if ($joiningDate !== null) {

        $dateObject =
            DateTime::createFromFormat(
                'Y-m-d',
                $joiningDate
            );


        if (
            !$dateObject ||
            $dateObject->format('Y-m-d') !==
                $joiningDate
        ) {

            validationResponse(
                [
                    'joining_date' =>
                        'Please enter a valid joining date.'
                ]
            );
        }
    }


    /* ========================================================
       SALARY
       ======================================================== */

    $salary =
        $data['salary'] ?? 0;


    if (
        $salary === '' ||
        $salary === null
    ) {
        $salary = 0;
    }


    if (
        !is_numeric($salary)
    ) {

        validationResponse(
            [
                'salary' =>
                    'Salary must be a valid number.'
            ]
        );
    }


    $salary =
        (float) $salary;


    if ($salary < 0) {

        validationResponse(
            [
                'salary' =>
                    'Salary cannot be negative.'
            ]
        );
    }


    /* ========================================================
       STATUS
       ======================================================== */

    $status =
        strtolower(
            trim(
                (string) (
                    $data['status']
                    ?? 'active'
                )
            )
        );


    $allowedStatuses = [
        'active',
        'inactive',
        'suspended'
    ];


    if (
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {

        validationResponse(
            [
                'status' =>
                    'Invalid staff status.'
            ]
        );
    }


    /* ========================================================
       USERNAME
       ======================================================== */

    $username =
        trim(
            (string) (
                $data['username'] ?? ''
            )
        );


    if (
        mb_strlen($username) >
        100
    ) {

        validationResponse(
            [
                'username' =>
                    'Username cannot exceed 100 characters.'
            ]
        );
    }


    /* ========================================================
       PASSWORD
       ======================================================== */

    $password =
        (string) (
            $data['password'] ?? ''
        );


    if ($password !== '') {

        if (
            mb_strlen($password) <
            8
        ) {

            validationResponse(
                [
                    'password' =>
                        'Password must contain at least 8 characters.'
                ]
            );
        }
    }


    /* ========================================================
       DATABASE
       ======================================================== */

    try {

        $pdo = db();


        /* ====================================================
           DUPLICATE EMAIL
           ==================================================== */

        if ($email !== '') {

            $emailCheck =
                $pdo->prepare(
                    "
                    SELECT id
                    FROM staff
                    WHERE email = :email
                    LIMIT 1
                    "
                );


            $emailCheck->execute([
                ':email' =>
                    $email
            ]);


            if (
                $emailCheck->fetch()
            ) {

                validationResponse(
                    [
                        'email' =>
                            'This email is already registered.'
                    ]
                );
            }
        }


        /* ====================================================
           DUPLICATE USERNAME
           ==================================================== */

        if ($username !== '') {

            $usernameCheck =
                $pdo->prepare(
                    "
                    SELECT id
                    FROM staff
                    WHERE username = :username
                    LIMIT 1
                    "
                );


            $usernameCheck->execute([
                ':username' =>
                    $username
            ]);


            if (
                $usernameCheck->fetch()
            ) {

                validationResponse(
                    [
                        'username' =>
                            'This username is already in use.'
                    ]
                );
            }
        }


        /* ====================================================
           PASSWORD HASH
           ==================================================== */

        $passwordHash =
            null;


        if ($password !== '') {

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            if (
                $passwordHash === false
            ) {

                throw new RuntimeException(
                    'Unable to secure staff password.'
                );
            }
        }


        /* ====================================================
           STAFF CODE
           ==================================================== */

        $staffCode =
            generateStaffCode(
                $pdo
            );


        /* ====================================================
           INSERT
           ==================================================== */

        $statement =
            $pdo->prepare(
                "
                INSERT INTO staff
                (
                    staff_code,

                    name,
                    email,
                    phone,

                    department,
                    designation,

                    joining_date,
                    salary,

                    status,

                    profile_image,

                    username,
                    password_hash
                )
                VALUES
                (
                    :staff_code,

                    :name,
                    :email,
                    :phone,

                    :department,
                    :designation,

                    :joining_date,
                    :salary,

                    :status,

                    :profile_image,

                    :username,
                    :password_hash
                )
                "
            );


        $statement->execute([

            ':staff_code' =>
                $staffCode,

            ':name' =>
                $name,

            ':email' =>
                $email !== ''
                    ? $email
                    : null,

            ':phone' =>
                $phone !== ''
                    ? $phone
                    : null,

            ':department' =>
                $department !== ''
                    ? $department
                    : null,

            ':designation' =>
                $designation !== ''
                    ? $designation
                    : null,

            ':joining_date' =>
                $joiningDate,

            ':salary' =>
                $salary,

            ':status' =>
                $status,

            ':profile_image' =>
                null,

            ':username' =>
                $username !== ''
                    ? $username
                    : null,

            ':password_hash' =>
                $passwordHash

        ]);


        $staffId =
            (int) $pdo->lastInsertId();


        if ($staffId <= 0) {

            throw new RuntimeException(
                'Staff member could not be created.'
            );
        }


        /* ====================================================
           RESPONSE
           ==================================================== */

        successResponse(
            'Staff member created successfully.',
            [

                'id' =>
                    $staffId,

                'staff_id' =>
                    $staffId,

                'staff_code' =>
                    $staffCode

            ],
            201
        );


    } catch (PDOException $exception) {

        error_log(
            'TENSPICK CREATE STAFF DATABASE ERROR: ' .
            $exception->getMessage()
        );


        /*
         * MySQL duplicate key.
         */

        if (
            (int) (
                $exception->errorInfo[1]
                ?? 0
            ) === 1062
        ) {

            validationResponse(
                [
                    'staff' =>
                        'A staff member with the supplied unique information already exists.'
                ]
            );
        }


        errorResponse(
            'Unable to create staff because of a database error.',
            null,
            500
        );


    } catch (Throwable $exception) {

        error_log(
            'TENSPICK CREATE STAFF ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to create staff member.'
        );
    }
}


/* ============================================================
   UPDATE STAFF
   PUT /api/staff/{id}
   ============================================================ */

function updateStaff(
    int $id
): void {

    requireAdminAuthentication();

    requireCsrfToken();


    if ($id <= 0) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );
    }


    $data =
        getJsonInput();


    if (empty($data)) {

        validationResponse(
            [
                'form' =>
                    'No staff data was received.'
            ],
            'Please enter staff information.'
        );
    }


    try {

        $pdo = db();


        /* ====================================================
           EXISTING STAFF
           ==================================================== */

        $existingStatement =
            $pdo->prepare(
                "
                SELECT
                    id,
                    staff_code,
                    password_hash
                FROM staff
                WHERE id = :id
                LIMIT 1
                "
            );


        $existingStatement->execute([
            ':id' =>
                $id
        ]);


        $existing =
            $existingStatement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$existing) {

            notFoundResponse(
                'Staff member not found.'
            );
        }


        /* ====================================================
           NAME
           ==================================================== */

        $name =
            trim(
                (string) (
                    $data['name'] ?? ''
                )
            );


        if ($name === '') {

            validationResponse(
                [
                    'name' =>
                        'Staff name is required.'
                ]
            );
        }


        if (
            mb_strlen($name) >
            150
        ) {

            validationResponse(
                [
                    'name' =>
                        'Staff name cannot exceed 150 characters.'
                ]
            );
        }


        /* ====================================================
           EMAIL
           ==================================================== */

        $email =
            strtolower(
                trim(
                    (string) (
                        $data['email'] ?? ''
                    )
                )
            );


        if ($email !== '') {

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                validationResponse(
                    [
                        'email' =>
                            'Please enter a valid email address.'
                    ]
                );
            }


            if (
                mb_strlen($email) >
                150
            ) {

                validationResponse(
                    [
                        'email' =>
                            'Email cannot exceed 150 characters.'
                    ]
                );
            }
        }


        /* ====================================================
           PHONE
           ==================================================== */

        $phone =
            trim(
                (string) (
                    $data['phone'] ?? ''
                )
            );


        if (
            mb_strlen($phone) >
            30
        ) {

            validationResponse(
                [
                    'phone' =>
                        'Phone number cannot exceed 30 characters.'
                ]
            );
        }


        /* ====================================================
           DEPARTMENT
           ==================================================== */

        $department =
            trim(
                (string) (
                    $data['department'] ?? ''
                )
            );


        if (
            mb_strlen($department) >
            100
        ) {

            validationResponse(
                [
                    'department' =>
                        'Department cannot exceed 100 characters.'
                ]
            );
        }


        /* ====================================================
           DESIGNATION
           ==================================================== */

        $designation =
            trim(
                (string) (
                    $data['designation'] ?? ''
                )
            );


        if (
            mb_strlen($designation) >
            100
        ) {

            validationResponse(
                [
                    'designation' =>
                        'Designation cannot exceed 100 characters.'
                ]
            );
        }


        /* ====================================================
           JOINING DATE
           ==================================================== */

        $joiningDate =
            trim(
                (string) (
                    $data['joining_date'] ?? ''
                )
            );


        if ($joiningDate === '') {
            $joiningDate = null;
        }


        if ($joiningDate !== null) {

            $dateObject =
                DateTime::createFromFormat(
                    'Y-m-d',
                    $joiningDate
                );


            if (
                !$dateObject ||
                $dateObject->format('Y-m-d') !==
                    $joiningDate
            ) {

                validationResponse(
                    [
                        'joining_date' =>
                            'Please enter a valid joining date.'
                    ]
                );
            }
        }


        /* ====================================================
           SALARY
           ==================================================== */

        $salary =
            $data['salary'] ?? 0;


        if (
            $salary === '' ||
            $salary === null
        ) {
            $salary = 0;
        }


        if (
            !is_numeric($salary)
        ) {

            validationResponse(
                [
                    'salary' =>
                        'Salary must be a valid number.'
                ]
            );
        }


        $salary =
            (float) $salary;


        if ($salary < 0) {

            validationResponse(
                [
                    'salary' =>
                        'Salary cannot be negative.'
                ]
            );
        }


        /* ====================================================
           STATUS
           ==================================================== */

        $status =
            strtolower(
                trim(
                    (string) (
                        $data['status']
                        ?? 'active'
                    )
                )
            );


        $allowedStatuses = [
            'active',
            'inactive',
            'suspended'
        ];


        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            validationResponse(
                [
                    'status' =>
                        'Invalid staff status.'
                ]
            );
        }


        /* ====================================================
           USERNAME
           ==================================================== */

        $username =
            trim(
                (string) (
                    $data['username'] ?? ''
                )
            );


        if (
            mb_strlen($username) >
            100
        ) {

            validationResponse(
                [
                    'username' =>
                        'Username cannot exceed 100 characters.'
                ]
            );
        }


        /* ====================================================
           PASSWORD
           ==================================================== */

        $password =
            (string) (
                $data['password'] ?? ''
            );


        if ($password !== '') {

            if (
                mb_strlen($password) <
                8
            ) {

                validationResponse(
                    [
                        'password' =>
                            'Password must contain at least 8 characters.'
                    ]
                );
            }
        }


        /* ====================================================
           DUPLICATE EMAIL
           ==================================================== */

        if ($email !== '') {

            $emailCheck =
                $pdo->prepare(
                    "
                    SELECT id
                    FROM staff
                    WHERE email = :email
                    AND id != :id
                    LIMIT 1
                    "
                );


            $emailCheck->execute([

                ':email' =>
                    $email,

                ':id' =>
                    $id

            ]);


            if (
                $emailCheck->fetch()
            ) {

                validationResponse(
                    [
                        'email' =>
                            'This email is already registered.'
                    ]
                );
            }
        }


        /* ====================================================
           DUPLICATE USERNAME
           ==================================================== */

        if ($username !== '') {

            $usernameCheck =
                $pdo->prepare(
                    "
                    SELECT id
                    FROM staff
                    WHERE username = :username
                    AND id != :id
                    LIMIT 1
                    "
                );


            $usernameCheck->execute([

                ':username' =>
                    $username,

                ':id' =>
                    $id

            ]);


            if (
                $usernameCheck->fetch()
            ) {

                validationResponse(
                    [
                        'username' =>
                            'This username is already in use.'
                    ]
                );
            }
        }


        /* ====================================================
           UPDATE
           ==================================================== */

        $sql = "
            UPDATE staff

            SET

                name = :name,

                email = :email,

                phone = :phone,

                department = :department,

                designation = :designation,

                joining_date = :joining_date,

                salary = :salary,

                status = :status,

                username = :username
        ";


        $params = [

            ':id' =>
                $id,

            ':name' =>
                $name,

            ':email' =>
                $email !== ''
                    ? $email
                    : null,

            ':phone' =>
                $phone !== ''
                    ? $phone
                    : null,

            ':department' =>
                $department !== ''
                    ? $department
                    : null,

            ':designation' =>
                $designation !== ''
                    ? $designation
                    : null,

            ':joining_date' =>
                $joiningDate,

            ':salary' =>
                $salary,

            ':status' =>
                $status,

            ':username' =>
                $username !== ''
                    ? $username
                    : null

        ];


        /*
         * Only change password
         * when a new password
         * was supplied.
         */

        if ($password !== '') {

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            if (
                $passwordHash === false
            ) {

                throw new RuntimeException(
                    'Unable to secure staff password.'
                );
            }


            $sql .= ",
                password_hash = :password_hash
            ";


            $params[
                ':password_hash'
            ] =
                $passwordHash;
        }


        $sql .= "
            WHERE id = :id
            LIMIT 1
        ";


        $statement =
            $pdo->prepare(
                $sql
            );


        $statement->execute(
            $params
        );


        /* ====================================================
           RESPONSE
           ==================================================== */

        successResponse(
            'Staff member updated successfully.',
            [

                'id' =>
                    $id,

                'staff_code' =>
                    $existing['staff_code']

            ]
        );


    } catch (PDOException $exception) {

        error_log(
            'TENSPICK UPDATE STAFF DATABASE ERROR: ' .
            $exception->getMessage()
        );


        if (
            (int) (
                $exception->errorInfo[1]
                ?? 0
            ) === 1062
        ) {

            validationResponse(
                [
                    'staff' =>
                        'A staff member with the supplied unique information already exists.'
                ]
            );
        }


        errorResponse(
            'Unable to update staff because of a database error.',
            null,
            500
        );


    } catch (Throwable $exception) {

        error_log(
            'TENSPICK UPDATE STAFF ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to update staff member.'
        );
    }
}


/* ============================================================
   DELETE STAFF
   DELETE /api/staff/{id}
   ============================================================ */

function deleteStaff(
    int $id
): void {

    requireAdminAuthentication();

    requireCsrfToken();


    if ($id <= 0) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );
    }


    try {

        $pdo = db();


        /* ====================================================
           CHECK STAFF
           ==================================================== */

        $check =
            $pdo->prepare(
                "
                SELECT
                    id,
                    staff_code,
                    name
                FROM staff
                WHERE id = :id
                LIMIT 1
                "
            );


        $check->execute([
            ':id' =>
                $id
        ]);


        $staff =
            $check->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$staff) {

            notFoundResponse(
                'Staff member not found.'
            );
        }


        /* ====================================================
           DELETE
           ==================================================== */

        $statement =
            $pdo->prepare(
                "
                DELETE FROM staff

                WHERE id = :id

                LIMIT 1
                "
            );


        $statement->execute([
            ':id' =>
                $id
        ]);


        if (
            $statement->rowCount() !==
            1
        ) {

            throw new RuntimeException(
                'Staff member could not be deleted.'
            );
        }


        /* ====================================================
           RESPONSE
           ==================================================== */

        successResponse(
            'Staff member deleted successfully.',
            [

                'id' =>
                    $id,

                'staff_code' =>
                    $staff['staff_code']

            ]
        );


    } catch (PDOException $exception) {

        error_log(
            'TENSPICK DELETE STAFF DATABASE ERROR: ' .
            $exception->getMessage()
        );


        /*
         * This will become particularly
         * important after Tasks are added.
         *
         * If tasks have a foreign-key
         * relationship with staff,
         * deletion can be rejected here.
         */

        if (
            (int) (
                $exception->errorInfo[1]
                ?? 0
            ) === 1451
        ) {

            errorResponse(
                'This staff member cannot be deleted because related records exist.',
                null,
                409
            );
        }


        errorResponse(
            'Unable to delete staff because of a database error.',
            null,
            500
        );


    } catch (Throwable $exception) {

        error_log(
            'TENSPICK DELETE STAFF ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to delete staff member.'
        );
    }
}


/* ============================================================
   GENERATE STAFF CODE
   ============================================================ */

/**
 * Generates:
 *
 * STF-YYYYMMDD-0001
 * STF-YYYYMMDD-0002
 * STF-YYYYMMDD-0003
 *
 * The generated code is checked against
 * the UNIQUE staff_code index.
 */

function generateStaffCode(
    PDO $pdo
): string {

    $prefix =
        'STF-' .
        date('Ymd') .
        '-';


    /* ========================================================
       FIND LAST CODE
       ======================================================== */

    $statement =
        $pdo->prepare(
            "
            SELECT staff_code

            FROM staff

            WHERE staff_code LIKE :prefix

            ORDER BY id DESC

            LIMIT 1
            "
        );


    $statement->execute([
        ':prefix' =>
            $prefix . '%'
    ]);


    $lastCode =
        $statement->fetchColumn();


    $number = 1;


    /* ========================================================
       NEXT NUMBER
       ======================================================== */

    if (
        is_string($lastCode) &&
        preg_match(
            '/-(\d+)$/',
            $lastCode,
            $matches
        )
    ) {

        $number =
            ((int) $matches[1]) +
            1;
    }


    /* ========================================================
       UNIQUE CHECK
       ======================================================== */

    for (
        $attempt = 0;
        $attempt < 100;
        $attempt++
    ) {

        $staffCode =
            $prefix .
            str_pad(
                (string) $number,
                4,
                '0',
                STR_PAD_LEFT
            );


        $check =
            $pdo->prepare(
                "
                SELECT id

                FROM staff

                WHERE staff_code = :staff_code

                LIMIT 1
                "
            );


        $check->execute([
            ':staff_code' =>
                $staffCode
        ]);


        if (
            !$check->fetchColumn()
        ) {

            return $staffCode;
        }


        $number++;
    }


    /*
     * Extremely unlikely fallback.
     */

    return
        $prefix .
        strtoupper(
            bin2hex(
                random_bytes(4)
            )
        );
}