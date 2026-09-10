<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * LEAD CONTROLLER
 * ============================================================
 *
 * ADMIN ONLY
 *
 * LEADS
 * -----
 * GET    /api/leads
 * GET    /api/leads/{id}
 * POST   /api/leads
 * PUT    /api/leads/{id}
 * DELETE /api/leads/{id}
 *
 * CONVERSION
 * ----------
 * POST   /api/leads/{id}/convert
 *
 * ============================================================
 */


/* ============================================================
   GET ALL LEADS
   ============================================================ */

function getLeads(): void
{
    requireAdminAuthentication();

    try {

        $pdo = db();

        /* ====================================================
           PAGINATION
           ==================================================== */

        $page = max(
            1,
            (int) ($_GET['page'] ?? 1)
        );

        $perPage = (int) (
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

        $search = trim(
            (string) (
                $_GET['search'] ?? ''
            )
        );

        $status = trim(
            (string) (
                $_GET['status'] ?? ''
            )
        );

        $priority = trim(
            (string) (
                $_GET['priority'] ?? ''
            )
        );


        /* ====================================================
           VALIDATE FILTERS
           ==================================================== */

        $allowedStatuses = [
            'new',
            'contacted',
            'follow_up',
            'proposal_sent',
            'negotiation',
            'won',
            'lost'
        ];

        $allowedPriorities = [
            'low',
            'medium',
            'high'
        ];

        if (
            $status !== '' &&
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $status = '';
        }

        if (
            $priority !== '' &&
            !in_array(
                $priority,
                $allowedPriorities,
                true
            )
        ) {
            $priority = '';
        }


        /* ====================================================
           WHERE
           ==================================================== */

        $where = [];

        $params = [];

        if ($search !== '') {

            $where[] = "
                (
                    name LIKE :search
                    OR company LIKE :search
                    OR phone LIKE :search
                    OR email LIKE :search
                    OR service LIKE :search
                    OR lead_code LIKE :search
                )
            ";

            $params[':search'] =
                '%' . $search . '%';
        }

        if ($status !== '') {

            $where[] =
                'status = :status';

            $params[':status'] =
                $status;
        }

        if ($priority !== '') {

            $where[] =
                'priority = :priority';

            $params[':priority'] =
                $priority;
        }

        $whereSql =
            !empty($where)
            ? 'WHERE ' . implode(
                ' AND ',
                $where
            )
            : '';


        /* ====================================================
           COUNT
           ==================================================== */

        $countSql = "
            SELECT COUNT(*)
            FROM leads
            $whereSql
        ";

        $countStatement =
            $pdo->prepare(
                $countSql
            );

        foreach (
            $params as $key => $value
        ) {

            $countStatement->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $countStatement->execute();

        $total =
            (int) $countStatement->fetchColumn();


        /* ====================================================
           PAGINATION
           ==================================================== */

        $totalPages =
            $total > 0
            ? (int) ceil(
                $total / $perPage
            )
            : 0;

        if (
            $totalPages > 0 &&
            $page > $totalPages
        ) {

            $page =
                $totalPages;
        }

        $offset =
            ($page - 1) *
            $perPage;


        /* ====================================================
           LEADS
           ==================================================== */

        $sql = "
            SELECT
                id,
                lead_code,
                name,
                company,
                phone,
                email,
                service,
                source,
                status,
                priority,
                follow_up_date,
                notes,
                created_at,
                updated_at
            FROM leads
            $whereSql
            ORDER BY id DESC
            LIMIT :limit
            OFFSET :offset
        ";

        $statement =
            $pdo->prepare(
                $sql
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

        $statistics =
            getLeadStatistics(
                $pdo
            );


        /* ====================================================
           RESPONSE
           ==================================================== */

        successResponse(
            'Leads loaded successfully.',
            [

                'items' =>
                    $items,

                'leads' =>
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
            'TENSPICK GET LEADS ERROR: ' .
            $exception->getMessage()
        );

        serverErrorResponse(
            'Unable to load leads.'
        );
    }
}


/* ============================================================
   GET SINGLE LEAD
   ============================================================ */

function getLead(
    int $id
): void {

    requireAdminAuthentication();

    if ($id <= 0) {

        errorResponse(
            'Invalid lead ID.',
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
                    lead_code,
                    name,
                    company,
                    phone,
                    email,
                    service,
                    source,
                    status,
                    priority,
                    follow_up_date,
                    notes,
                    created_at,
                    updated_at
                FROM leads
                WHERE id = :id
                LIMIT 1
                "
            );

        $statement->execute(
            [
                ':id' => $id
            ]
        );

        $lead =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$lead) {

            notFoundResponse(
                'Lead not found.'
            );
        }

        successResponse(
            'Lead loaded successfully.',
            $lead
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET LEAD ERROR: ' .
            $exception->getMessage()
        );

        serverErrorResponse(
            'Unable to load lead.'
        );
    }
}


/* ============================================================
   CREATE LEAD
   ============================================================ */

function createLead(): void
{
    requireAdminAuthentication();

    requireCsrfToken();

    $data =
        getJsonInput();

    if (empty($data)) {

        validationResponse(
            [
                'form' =>
                    'No lead data was received.'
            ],
            'Please enter lead information.'
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
                    'Lead name is required.'
            ]
        );
    }

    if (mb_strlen($name) > 150) {

        validationResponse(
            [
                'name' =>
                    'Lead name cannot exceed 150 characters.'
            ]
        );
    }


    /* ========================================================
       COMPANY
       ======================================================== */

    $company =
        trim(
            (string) (
                $data['company'] ?? ''
            )
        );

    if (mb_strlen($company) > 150) {

        validationResponse(
            [
                'company' =>
                    'Company name cannot exceed 150 characters.'
            ]
        );
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
       EMAIL
       ======================================================== */

    $email =
        trim(
            (string) (
                $data['email'] ?? ''
            )
        );

    if (
        $email !== '' &&
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


    /* ========================================================
       SERVICE
       ======================================================== */

    $service =
        trim(
            (string) (
                $data['service'] ?? ''
            )
        );

    if (mb_strlen($service) > 150) {

        validationResponse(
            [
                'service' =>
                    'Service cannot exceed 150 characters.'
            ]
        );
    }


    /* ========================================================
       SOURCE
       ======================================================== */

    $source =
        trim(
            (string) (
                $data['source'] ?? ''
            )
        );

    if (mb_strlen($source) > 100) {

        validationResponse(
            [
                'source' =>
                    'Source cannot exceed 100 characters.'
            ]
        );
    }


    /* ========================================================
       STATUS
       ======================================================== */

    $status =
        normalizeLeadStatus(
            $data['status'] ?? 'new'
        );


    /* ========================================================
       PRIORITY
       ======================================================== */

    $priority =
        normalizeLeadPriority(
            $data['priority'] ?? 'medium'
        );


    /* ========================================================
       FOLLOW UP DATE
       ======================================================== */

    $followUpDate =
        trim(
            (string) (
                $data['follow_up_date'] ?? ''
            )
        );

    if ($followUpDate === '') {

        $followUpDate = null;
    }

    if (
        $followUpDate !== null &&
        !isValidLeadDate(
            $followUpDate
        )
    ) {

        validationResponse(
            [
                'follow_up_date' =>
                    'Invalid follow-up date.'
            ]
        );
    }


    /* ========================================================
       NOTES
       ======================================================== */

    $notes =
        trim(
            (string) (
                $data['notes'] ?? ''
            )
        );


    /* ========================================================
       DATABASE
       ======================================================== */

    try {

        $pdo = db();

        $leadCode =
            generateLeadCode(
                $pdo
            );

        $statement =
            $pdo->prepare(
                "
                INSERT INTO leads
                (
                    lead_code,
                    name,
                    company,
                    phone,
                    email,
                    service,
                    source,
                    status,
                    priority,
                    follow_up_date,
                    notes
                )
                VALUES
                (
                    :lead_code,
                    :name,
                    :company,
                    :phone,
                    :email,
                    :service,
                    :source,
                    :status,
                    :priority,
                    :follow_up_date,
                    :notes
                )
                "
            );

        $statement->execute(
            [

                ':lead_code' =>
                    $leadCode,

                ':name' =>
                    $name,

                ':company' =>
                    $company !== ''
                    ? $company
                    : null,

                ':phone' =>
                    $phone !== ''
                    ? $phone
                    : null,

                ':email' =>
                    $email !== ''
                    ? $email
                    : null,

                ':service' =>
                    $service !== ''
                    ? $service
                    : null,

                ':source' =>
                    $source !== ''
                    ? $source
                    : null,

                ':status' =>
                    $status,

                ':priority' =>
                    $priority,

                ':follow_up_date' =>
                    $followUpDate,

                ':notes' =>
                    $notes !== ''
                    ? $notes
                    : null

            ]
        );

        $id =
            (int) $pdo->lastInsertId();

        if ($id <= 0) {

            throw new RuntimeException(
                'Database insert did not return a lead ID.'
            );
        }

        recordLeadActivity(
            'Created lead ' .
            $leadCode
        );

        successResponse(
            'Lead created successfully.',
            [

                'id' =>
                    $id,

                'lead_code' =>
                    $leadCode

            ],
            201
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK CREATE LEAD DATABASE ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to create lead because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK CREATE LEAD ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to create lead.',
            null,
            500
        );
    }
}


/* ============================================================
   UPDATE LEAD
   ============================================================ */

function updateLead(
    int $id
): void {

    requireAdminAuthentication();

    requireCsrfToken();

    if ($id <= 0) {

        errorResponse(
            'Invalid lead ID.',
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
                    'No lead data was received.'
            ],
            'Please enter lead information.'
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
                    'Lead name is required.'
            ]
        );
    }

    if (mb_strlen($name) > 150) {

        validationResponse(
            [
                'name' =>
                    'Lead name cannot exceed 150 characters.'
            ]
        );
    }


    /* ========================================================
       OTHER FIELDS
       ======================================================== */

    $company =
        trim(
            (string) (
                $data['company'] ?? ''
            )
        );

    $phone =
        trim(
            (string) (
                $data['phone'] ?? ''
            )
        );

    $email =
        trim(
            (string) (
                $data['email'] ?? ''
            )
        );

    if (
        $email !== '' &&
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

    $service =
        trim(
            (string) (
                $data['service'] ?? ''
            )
        );

    $source =
        trim(
            (string) (
                $data['source'] ?? ''
            )
        );

    $status =
        normalizeLeadStatus(
            $data['status'] ?? 'new'
        );

    $priority =
        normalizeLeadPriority(
            $data['priority'] ?? 'medium'
        );

    $followUpDate =
        trim(
            (string) (
                $data['follow_up_date'] ?? ''
            )
        );

    if ($followUpDate === '') {

        $followUpDate = null;
    }

    if (
        $followUpDate !== null &&
        !isValidLeadDate(
            $followUpDate
        )
    ) {

        validationResponse(
            [
                'follow_up_date' =>
                    'Invalid follow-up date.'
            ]
        );
    }

    $notes =
        trim(
            (string) (
                $data['notes'] ?? ''
            )
        );


    /* ========================================================
       DATABASE
       ======================================================== */

    try {

        $pdo = db();

        $check =
            $pdo->prepare(
                "
                SELECT
                    id,
                    lead_code
                FROM leads
                WHERE id = :id
                LIMIT 1
                "
            );

        $check->execute(
            [
                ':id' =>
                    $id
            ]
        );

        $existing =
            $check->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$existing) {

            notFoundResponse(
                'Lead not found.'
            );
        }


        /* ====================================================
           UPDATE
           ==================================================== */

        $statement =
            $pdo->prepare(
                "
                UPDATE leads
                SET
                    name = :name,
                    company = :company,
                    phone = :phone,
                    email = :email,
                    service = :service,
                    source = :source,
                    status = :status,
                    priority = :priority,
                    follow_up_date = :follow_up_date,
                    notes = :notes
                WHERE id = :id
                "
            );

        $statement->execute(
            [

                ':id' =>
                    $id,

                ':name' =>
                    $name,

                ':company' =>
                    $company !== ''
                    ? $company
                    : null,

                ':phone' =>
                    $phone !== ''
                    ? $phone
                    : null,

                ':email' =>
                    $email !== ''
                    ? $email
                    : null,

                ':service' =>
                    $service !== ''
                    ? $service
                    : null,

                ':source' =>
                    $source !== ''
                    ? $source
                    : null,

                ':status' =>
                    $status,

                ':priority' =>
                    $priority,

                ':follow_up_date' =>
                    $followUpDate,

                ':notes' =>
                    $notes !== ''
                    ? $notes
                    : null

            ]
        );


        recordLeadActivity(
            'Updated lead ' .
            (
                $existing['lead_code']
                ?? ('ID ' . $id)
            )
        );


        successResponse(
            'Lead updated successfully.',
            [
                'id' => $id
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK UPDATE LEAD DATABASE ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to update lead because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK UPDATE LEAD ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to update lead.',
            null,
            500
        );
    }
}


/* ============================================================
   DELETE LEAD
   ============================================================ */

function deleteLead(
    int $id
): void {

    requireAdminAuthentication();

    requireCsrfToken();

    if ($id <= 0) {

        errorResponse(
            'Invalid lead ID.',
            null,
            400
        );
    }

    try {

        $pdo = db();

        $check =
            $pdo->prepare(
                "
                SELECT
                    id,
                    lead_code,
                    name
                FROM leads
                WHERE id = :id
                LIMIT 1
                "
            );

        $check->execute(
            [
                ':id' =>
                    $id
            ]
        );

        $lead =
            $check->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$lead) {

            notFoundResponse(
                'Lead not found.'
            );
        }


        /* ====================================================
           DELETE
           ==================================================== */

        $statement =
            $pdo->prepare(
                "
                DELETE FROM leads
                WHERE id = :id
                "
            );

        $statement->execute(
            [
                ':id' =>
                    $id
            ]
        );

        if (
            $statement->rowCount() < 1
        ) {

            throw new RuntimeException(
                'Lead could not be deleted.'
            );
        }


        recordLeadActivity(
            'Deleted lead ' .
            (
                $lead['lead_code']
                ?? ('ID ' . $id)
            )
        );


        successResponse(
            'Lead deleted successfully.',
            [
                'id' => $id
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK DELETE LEAD DATABASE ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to delete lead because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK DELETE LEAD ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to delete lead.',
            null,
            500
        );
    }
}


/* ============================================================
   CONVERT LEAD TO CLIENT
   ============================================================ */

/**
 * POST /api/leads/{id}/convert
 *
 * Lead → Client
 *
 * The conversion:
 *
 * 1. Finds the lead
 * 2. Reads the actual clients table structure
 * 3. Maps only columns that actually exist
 * 4. Creates the client
 * 5. Optionally creates client login information
 *    when client_users exists
 * 6. Deletes the original lead
 * 7. Commits everything together
 *
 * If anything fails:
 *
 * - Client creation is rolled back
 * - Lead remains untouched
 *
 * This avoids the previous problem where conversion
 * returned 422 simply because a Lead does not contain
 * every Client field.
 */
function convertLeadToClient(
    int $id
): void {

    requireAdminAuthentication();
    requireCsrfToken();

    if ($id <= 0) {
        errorResponse(
            'Invalid lead ID.',
            null,
            400
        );
    }

    $pdo = null;

    try {

        $pdo = db();

        /* ====================================================
           GET LEAD
           ==================================================== */

        $statement = $pdo->prepare(
            "
            SELECT
                id,
                lead_code,
                name,
                company,
                phone,
                email,
                service,
                source,
                status,
                priority,
                follow_up_date,
                notes,
                created_at,
                updated_at
            FROM leads
            WHERE id = :id
            LIMIT 1
            "
        );

        $statement->execute([
            ':id' => $id
        ]);

        $lead = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$lead) {
            notFoundResponse('Lead not found.');
        }

        /* ====================================================
           NORMALIZE LEAD VALUES
           ==================================================== */

        $leadCode = trim((string) ($lead['lead_code'] ?? ''));
        $clientName = trim((string) ($lead['name'] ?? ''));
        $companyName = trim((string) ($lead['company'] ?? ''));
        $mobile = trim((string) ($lead['phone'] ?? ''));
        $email = strtolower(trim((string) ($lead['email'] ?? '')));
        $service = trim((string) ($lead['service'] ?? ''));
        $source = trim((string) ($lead['source'] ?? ''));
        $notes = trim((string) ($lead['notes'] ?? ''));

        /* ====================================================
           VALIDATION
           ==================================================== */

        $errors = [];

        if ($clientName === '') {
            $errors['client_name'] = 'Lead name is required.';
        } elseif (mb_strlen($clientName) > 150) {
            $errors['client_name'] =
                'Client name cannot exceed 150 characters.';
        }

        /*
         * clients.company_name is NOT NULL.
         * Therefore a lead without a company cannot be
         * converted until the company name is supplied.
         */
        if ($companyName === '') {
            $errors['company_name'] =
                'Company / Business Name is required for conversion.';
        } elseif (mb_strlen($companyName) > 150) {
            $errors['company_name'] =
                'Company / Business Name cannot exceed 150 characters.';
        }

        /*
         * clients.mobile is NOT NULL.
         */
        if ($mobile === '') {
            $errors['mobile'] =
                'Mobile number is required for conversion.';
        } elseif (mb_strlen($mobile) > 20) {
            $errors['mobile'] =
                'Mobile number cannot exceed 20 characters.';
        }

        /*
         * clients.email and clients.login_email are NOT NULL
         * and login_email is UNIQUE.
         */
        if ($email === '') {
            $errors['email'] =
                'Email is required for client conversion.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] =
                'Lead email is not valid.';
        } elseif (mb_strlen($email) > 150) {
            $errors['email'] =
                'Email cannot exceed 150 characters.';
        }

        if (!empty($errors)) {
            validationResponse(
                $errors,
                'This lead is missing information required for conversion.'
            );
        }

        /* ====================================================
           TRANSACTION
           ==================================================== */

        $pdo->beginTransaction();

        try {

            /* =================================================
               RE-CHECK LEAD INSIDE TRANSACTION
               ================================================= */

            $lockedLeadStatement = $pdo->prepare(
                "
                SELECT
                    id,
                    lead_code
                FROM leads
                WHERE id = :id
                LIMIT 1
                FOR UPDATE
                "
            );

            $lockedLeadStatement->execute([
                ':id' => $id
            ]);

            $lockedLead = $lockedLeadStatement->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$lockedLead) {
                throw new RuntimeException(
                    'Lead no longer exists.'
                );
            }

            if (
                $leadCode === '' &&
                isset($lockedLead['lead_code'])
            ) {
                $leadCode = trim(
                    (string) $lockedLead['lead_code']
                );
            }

            /* =================================================
               DUPLICATE EMAIL CHECK
               ================================================= */

            $duplicateStatement = $pdo->prepare(
                "
                SELECT
                    id,
                    client_code
                FROM clients
                WHERE email = :email
                   OR login_email = :login_email
                LIMIT 1
                "
            );

            $duplicateStatement->execute([
                ':email' => $email,
                ':login_email' => $email
            ]);

            $duplicateClient = $duplicateStatement->fetch(
                PDO::FETCH_ASSOC
            );

            if ($duplicateClient) {

                $duplicateCode = trim(
                    (string) (
                        $duplicateClient['client_code'] ?? ''
                    )
                );

                $message = 'A client with this email already exists.';

                if ($duplicateCode !== '') {
                    $message .=
                        ' Existing client: ' .
                        $duplicateCode . '.';
                }

                throw new LeadConversionValidationException(
                    $message,
                    [
                        'email' =>
                            'A client with this email already exists.'
                    ]
                );
            }

            /* =================================================
               GENERATE CLIENT CODE
               ================================================= */

            $clientCode = generateConvertedClientCode($pdo);

            /* =================================================
               GENERATE CLIENT PORTAL PASSWORD
               ================================================= */

            $temporaryPassword =
                generateConvertedClientPassword();

            $passwordHash = password_hash(
                $temporaryPassword,
                PASSWORD_DEFAULT
            );

            if (
                !is_string($passwordHash) ||
                $passwordHash === ''
            ) {
                throw new RuntimeException(
                    'Unable to generate the client password hash.'
                );
            }

            /* =================================================
               BUSINESS DESCRIPTION
               ================================================= */

            $businessDescriptionParts = [];

            if ($service !== '') {
                $businessDescriptionParts[] =
                    'Service Interested: ' . $service;
            }

            if ($source !== '') {
                $businessDescriptionParts[] =
                    'Lead Source: ' . $source;
            }

            if ($notes !== '') {
                $businessDescriptionParts[] =
                    'Lead Notes: ' . $notes;
            }

            $businessDescription =
                !empty($businessDescriptionParts)
                ? implode(
                    PHP_EOL . PHP_EOL,
                    $businessDescriptionParts
                )
                : null;

            /* =================================================
               INTERNAL NOTES
               ================================================= */

            $internalNotes =
                'Converted from lead ' .
                (
                    $leadCode !== ''
                    ? $leadCode
                    : 'ID ' . $id
                );

            if ($notes !== '') {
                $internalNotes .=
                    PHP_EOL .
                    PHP_EOL .
                    'Original Lead Notes:' .
                    PHP_EOL .
                    $notes;
            }

            /* =================================================
               INSERT INTO EXACT CLIENT SCHEMA
               ================================================= */

            /*
             * This INSERT intentionally matches the current
             * clients table exactly.
             *
             * Required columns:
             * - client_code
             * - client_name
             * - company_name
             * - mobile
             * - email
             * - login_email
             * - password_hash
             *
             * Optional information unavailable on the Lead
             * remains NULL.
             *
             * No client_users table is used because the current
             * clients schema stores portal credentials directly.
             */

            $insert = $pdo->prepare(
                "
                INSERT INTO clients
                (
                    client_code,
                    source_lead_code,

                    client_name,
                    company_name,
                    mobile,
                    whatsapp,
                    email,
                    alternate_phone,

                    business_type,
                    industry,
                    website,
                    business_description,

                    address,
                    city,
                    state,
                    pincode,

                    contact_person_name,
                    contact_person_designation,
                    contact_person_mobile,
                    contact_person_email,

                    billing_name,
                    gst_number,
                    pan_number,
                    billing_address,

                    login_email,
                    password_hash,

                    status,
                    internal_notes
                )
                VALUES
                (
                    :client_code,
                    :source_lead_code,

                    :client_name,
                    :company_name,
                    :mobile,
                    :whatsapp,
                    :email,
                    :alternate_phone,

                    :business_type,
                    :industry,
                    :website,
                    :business_description,

                    :address,
                    :city,
                    :state,
                    :pincode,

                    :contact_person_name,
                    :contact_person_designation,
                    :contact_person_mobile,
                    :contact_person_email,

                    :billing_name,
                    :gst_number,
                    :pan_number,
                    :billing_address,

                    :login_email,
                    :password_hash,

                    :status,
                    :internal_notes
                )
                "
            );

            $insert->execute([
                ':client_code' =>
                    $clientCode,

                ':source_lead_code' =>
                    $leadCode !== ''
                    ? $leadCode
                    : null,

                ':client_name' =>
                    $clientName,

                ':company_name' =>
                    $companyName,

                ':mobile' =>
                    $mobile,

                ':whatsapp' =>
                    $mobile,

                ':email' =>
                    $email,

                ':alternate_phone' =>
                    null,

                ':business_type' =>
                    null,

                ':industry' =>
                    null,

                ':website' =>
                    null,

                ':business_description' =>
                    $businessDescription,

                ':address' =>
                    null,

                ':city' =>
                    null,

                ':state' =>
                    null,

                ':pincode' =>
                    null,

                ':contact_person_name' =>
                    $clientName,

                ':contact_person_designation' =>
                    null,

                ':contact_person_mobile' =>
                    $mobile,

                ':contact_person_email' =>
                    $email,

                ':billing_name' =>
                    $companyName,

                ':gst_number' =>
                    null,

                ':pan_number' =>
                    null,

                ':billing_address' =>
                    null,

                ':login_email' =>
                    $email,

                ':password_hash' =>
                    $passwordHash,

                ':status' =>
                    'active',

                ':internal_notes' =>
                    $internalNotes
            ]);

            $clientId = (int) $pdo->lastInsertId();

            if ($clientId <= 0) {
                throw new RuntimeException(
                    'Client insert did not return a valid client ID.'
                );
            }

            /* =================================================
               VERIFY CLIENT
               ================================================= */

            $verify = $pdo->prepare(
                "
                SELECT
                    id,
                    client_code,
                    source_lead_code,
                    client_name,
                    company_name,
                    mobile,
                    email,
                    login_email,
                    status
                FROM clients
                WHERE id = :id
                LIMIT 1
                "
            );

            $verify->execute([
                ':id' => $clientId
            ]);

            $createdClient = $verify->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$createdClient) {
                throw new RuntimeException(
                    'Client was inserted but could not be verified.'
                );
            }

            /* =================================================
               DELETE ORIGINAL LEAD
               ================================================= */

            $delete = $pdo->prepare(
                "
                DELETE FROM leads
                WHERE id = :id
                "
            );

            $delete->execute([
                ':id' => $id
            ]);

            if ($delete->rowCount() !== 1) {
                throw new RuntimeException(
                    'Original lead could not be removed.'
                );
            }

            /* =================================================
               COMMIT
               ================================================= */

            $pdo->commit();

        } catch (LeadConversionValidationException $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            validationResponse(
                $exception->getValidationErrors(),
                $exception->getMessage()
            );

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        /* ====================================================
           ACTIVITY
           ==================================================== */

        recordLeadActivity(
            'Converted lead ' .
            (
                $leadCode !== ''
                ? $leadCode
                : 'ID ' . $id
            ) .
            ' to client ' .
            $clientCode .
            ' (ID ' .
            $clientId .
            ')'
        );

        /* ====================================================
           RESPONSE
           ==================================================== */

        successResponse(
            'Lead converted to client successfully.',
            [
                'lead_id' =>
                    $id,

                'client_id' =>
                    $clientId,

                'lead_code' =>
                    $leadCode !== ''
                    ? $leadCode
                    : null,

                'client_code' =>
                    $clientCode,

                'client_name' =>
                    $clientName,

                'company_name' =>
                    $companyName,

                'portal_account_created' =>
                    true,

                'portal_login_email' =>
                    $email,

                /*
                 * Returned only once after conversion.
                 * Only the hash is stored in the database.
                 */
                'temporary_password' =>
                    $temporaryPassword
            ],
            201
        );

    } catch (PDOException $exception) {

        if (
            $pdo !== null &&
            $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        error_log(
            'TENSPICK CONVERT LEAD DATABASE ERROR: ' .
            $exception->getMessage()
        );

        /*
         * A duplicate can also occur because of a database
         * UNIQUE constraint (race condition). Keep the API
         * response safe and useful.
         */
        if ((int) $exception->errorInfo[1] === 1062) {
            validationResponse(
                [
                    'email' =>
                        'A client with the supplied unique information already exists.'
                ],
                'The lead could not be converted because a client with the same unique information already exists.'
            );
        }

        errorResponse(
            'Unable to convert lead because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        if (
            $pdo !== null &&
            $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        error_log(
            'TENSPICK CONVERT LEAD ERROR: ' .
            $exception->getMessage()
        );

        errorResponse(
            'Unable to convert lead. Please try again.',
            null,
            500
        );
    }
}


/* ============================================================
   CONVERTED CLIENT CODE GENERATOR
   ============================================================ */

/**
 * Generates a unique client code for Lead → Client conversion:
 *
 * CL-YYYYMMDD-0001
 *
 * The generated value is checked against the UNIQUE
 * client_code index before it is returned.
 */
function generateConvertedClientCode(
    PDO $pdo
): string {

    $prefix =
        'CL-' .
        date('Ymd') .
        '-';

    $statement = $pdo->prepare(
        "
        SELECT client_code
        FROM clients
        WHERE client_code LIKE :prefix
        ORDER BY id DESC
        LIMIT 1
        "
    );

    $statement->execute([
        ':prefix' =>
            $prefix . '%'
    ]);

    $lastCode = $statement->fetchColumn();

    $number = 1;

    if (
        is_string($lastCode) &&
        preg_match(
            '/-(\d+)$/',
            $lastCode,
            $matches
        )
    ) {
        $number =
            ((int) $matches[1]) + 1;
    }

    /*
     * Normally the first candidate is available.
     *
     * The loop also protects against a previously existing
     * code with the same number.
     */
    for ($attempt = 0; $attempt < 100; $attempt++) {

        $clientCode =
            $prefix .
            str_pad(
                (string) $number,
                4,
                '0',
                STR_PAD_LEFT
            );

        $check = $pdo->prepare(
            "
            SELECT id
            FROM clients
            WHERE client_code = :client_code
            LIMIT 1
            "
        );

        $check->execute([
            ':client_code' =>
                $clientCode
        ]);

        if (!$check->fetchColumn()) {
            return $clientCode;
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


/* ============================================================
   TEMPORARY CLIENT PASSWORD
   ============================================================ */

function generateConvertedClientPassword(): string
{
    /*
     * Human-readable temporary password.
     *
     * The plaintext value is returned only once in the
     * conversion response. Only password_hash() output
     * is stored in the database.
     */
    return
        'TP-' .
        strtoupper(
            bin2hex(
                random_bytes(5)
            )
        );
}


/* ============================================================
   LEAD CONVERSION VALIDATION EXCEPTION
   ============================================================ */

final class LeadConversionValidationException extends RuntimeException
{
    private array $validationErrors;

    public function __construct(
        string $message,
        array $validationErrors = []
    ) {
        parent::__construct($message);

        $this->validationErrors =
            $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}


/* ============================================================
   LEAD STATISTICS
   ============================================================ */

function getLeadStatistics(
    PDO $pdo
): array {

    $statement =
        $pdo->query(
            "
            SELECT

                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN status = 'new'
                        THEN 1
                        ELSE 0
                    END
                ) AS new,

                SUM(
                    CASE
                        WHEN status = 'follow_up'
                        THEN 1
                        ELSE 0
                    END
                ) AS follow_up,

                SUM(
                    CASE
                        WHEN status = 'won'
                        THEN 1
                        ELSE 0
                    END
                ) AS won

            FROM leads
            "
        );


    $row =
        $statement->fetch(
            PDO::FETCH_ASSOC
        );


    return [

        'total' =>
            (int) (
                $row['total'] ?? 0
            ),

        'new' =>
            (int) (
                $row['new'] ?? 0
            ),

        'follow_up' =>
            (int) (
                $row['follow_up'] ?? 0
            ),

        'won' =>
            (int) (
                $row['won'] ?? 0
            )

    ];
}


/* ============================================================
   NORMALIZE STATUS
   ============================================================ */

function normalizeLeadStatus(
    mixed $status
): string {

    $status =
        strtolower(
            trim(
                (string) $status
            )
        );


    $allowed = [

        'new',

        'contacted',

        'follow_up',

        'proposal_sent',

        'negotiation',

        'won',

        'lost'

    ];


    if (
        !in_array(
            $status,
            $allowed,
            true
        )
    ) {

        return 'new';
    }


    return $status;
}


/* ============================================================
   NORMALIZE PRIORITY
   ============================================================ */

function normalizeLeadPriority(
    mixed $priority
): string {

    $priority =
        strtolower(
            trim(
                (string) $priority
            )
        );


    $allowed = [

        'low',

        'medium',

        'high'

    ];


    if (
        !in_array(
            $priority,
            $allowed,
            true
        )
    ) {

        return 'medium';
    }


    return $priority;
}


/* ============================================================
   VALIDATE DATE
   ============================================================ */

function isValidLeadDate(
    string $date
): bool {

    $parsed =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );


    if ($parsed === false) {

        return false;
    }


    return
        $parsed->format('Y-m-d') ===
        $date;
}


/* ============================================================
   GENERATE LEAD CODE
   ============================================================ */

function generateLeadCode(
    PDO $pdo
): string {

    $prefix =
        'LD-' .
        date('Ymd') .
        '-';


    $statement =
        $pdo->prepare(
            "
            SELECT lead_code
            FROM leads
            WHERE lead_code LIKE :prefix
            ORDER BY id DESC
            LIMIT 1
            "
        );


    $statement->execute(
        [
            ':prefix' =>
                $prefix . '%'
        ]
    );


    $last =
        $statement->fetchColumn();


    $number = 1;


    if (
        is_string($last) &&
        preg_match(
            '/-(\d+)$/',
            $last,
            $matches
        )
    ) {

        $number =
            ((int) $matches[1]) + 1;
    }


    return
        $prefix .
        str_pad(
            (string) $number,
            4,
            '0',
            STR_PAD_LEFT
        );
}


/* ============================================================
   ACTIVITY LOG
   ============================================================ */

function recordLeadActivity(
    string $description
): void {

    /*
     * Activity logging must NEVER break
     * the main Lead operation.
     */

    if (
        function_exists(
            'logActivity'
        )
    ) {

        try {

            logActivity(
                $description
            );

        } catch (Throwable $exception) {

            error_log(
                'TENSPICK LEAD ACTIVITY ERROR: ' .
                $exception->getMessage()
            );
        }
    }
}