<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * CLIENT CONTROLLER
 * ============================================================
 *
 * ADMIN CLIENT CRUD
 * -----------------
 * GET    /api/clients
 * GET    /api/clients/{id}
 * POST   /api/clients
 * PUT    /api/clients/{id}
 * DELETE /api/clients/{id}
 *
 * CLIENT PORTAL AUTHENTICATION
 * ----------------------------
 * POST   /api/client-auth/login
 * POST   /api/client-auth/logout
 * GET    /api/client-auth/me
 *
 * ============================================================
 *
 * PASSWORD SECURITY POLICY
 * ------------------------
 *
 * 1. Passwords are NEVER stored as plain text.
 * 2. Passwords are stored only as password_hash.
 * 3. password_hash() is used during client creation.
 * 4. password_verify() is used during client login.
 * 5. password_hash is NEVER returned through API responses.
 * 6. Client password UPDATE is NOT implemented.
 * 7. Client password CHANGE is NOT implemented.
 * 8. updateClient() never modifies password_hash.
 *
 * ============================================================
 *
 * CLIENT PORTAL SECURITY
 * -----------------------
 *
 * Client ID MUST come from the PHP session.
 *
 * Never trust client_id from:
 *
 * $_GET
 * $_POST
 * JSON body
 *
 * ============================================================
 */


/* ============================================================
   GET CLIENTS
   GET /api/clients
   ============================================================ */

function getClients(): void
{
    requireAdminAuthentication();

    try {

        $pdo = db();


        /* ----------------------------------------------------
           PAGINATION
        ---------------------------------------------------- */

        $page = max(
            1,
            (int) (
                $_GET['page'] ?? 1
            )
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


        /* ----------------------------------------------------
           FILTERS
        ---------------------------------------------------- */

        $search = trim(
            (string) (
                $_GET['search'] ?? ''
            )
        );


        $status = strtolower(
            trim(
                (string) (
                    $_GET['status'] ?? ''
                )
            )
        );


        $allowedStatuses = [
            'active',
            'inactive',
            'suspended'
        ];


        $conditions = [];

        $params = [];


        /* ----------------------------------------------------
           SEARCH
        ---------------------------------------------------- */

        if ($search !== '') {

            $conditions[] = "
                (
                    client_code LIKE :search
                    OR client_name LIKE :search
                    OR company_name LIKE :search
                    OR mobile LIKE :search
                    OR email LIKE :search
                    OR login_email LIKE :search
                    OR contact_person_name LIKE :search
                )
            ";


            $params[':search'] =
                '%' . $search . '%';
        }


        /* ----------------------------------------------------
           STATUS
        ---------------------------------------------------- */

        if (
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


        /* ----------------------------------------------------
           WHERE
        ---------------------------------------------------- */

        $where = '';


        if (!empty($conditions)) {

            $where =
                'WHERE ' .
                implode(
                    ' AND ',
                    $conditions
                );
        }


        /* ----------------------------------------------------
           TOTAL
        ---------------------------------------------------- */

        $countStatement = $pdo->prepare(
            "
            SELECT COUNT(*)
            FROM clients
            {$where}
            "
        );


        $countStatement->execute(
            $params
        );


        $total =
            (int) $countStatement->fetchColumn();


        /* ----------------------------------------------------
           TOTAL PAGES
        ---------------------------------------------------- */

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

            $page = $totalPages;
        }


        if ($totalPages === 0) {

            $page = 1;
        }


        $offset =
            ($page - 1) * $perPage;


        /* ----------------------------------------------------
           FETCH CLIENTS
        ---------------------------------------------------- */

        $statement = $pdo->prepare(
            "
            SELECT

                id,
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

                status,
                internal_notes,

                created_at,
                updated_at

            FROM clients

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


        $clients =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        /* ----------------------------------------------------
           STATISTICS
        ---------------------------------------------------- */

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

                FROM clients
                "
            );


        $statistics =
            $statisticsStatement->fetch(
                PDO::FETCH_ASSOC
            );


        successResponse(
            'Clients fetched successfully.',
            [

                'items' =>
                    $clients,

                'clients' =>
                    $clients,

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

                'statistics' => [

                    'total' =>
                        (int) (
                            $statistics['total'] ?? 0
                        ),

                    'active' =>
                        (int) (
                            $statistics['active'] ?? 0
                        ),

                    'inactive' =>
                        (int) (
                            $statistics['inactive'] ?? 0
                        ),

                    'suspended' =>
                        (int) (
                            $statistics['suspended'] ?? 0
                        )

                ]

            ]
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET CLIENTS ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to load clients.'
        );
    }
}


/* ============================================================
   GET SINGLE CLIENT
   GET /api/clients/{id}
   ============================================================ */

function getClient(int $clientId): void
{
    requireAdminAuthentication();


    if ($clientId <= 0) {

        errorResponse(
            'Invalid client ID.',
            null,
            400
        );
    }


    try {

        $pdo = db();


        $statement = $pdo->prepare(
            "
            SELECT

                id,
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

                status,
                internal_notes,

                created_at,
                updated_at

            FROM clients

            WHERE id = :id

            LIMIT 1
            "
        );


        $statement->execute([
            ':id' =>
                $clientId
        ]);


        $client =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$client) {

            notFoundResponse(
                'Client not found.'
            );
        }


        successResponse(
            'Client fetched successfully.',
            $client
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET CLIENT ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to load client.'
        );
    }
}


/* ============================================================
   CREATE CLIENT
   POST /api/clients
   ============================================================ */

function createClient(): void
{
    requireAdminAuthentication();

    requireCsrfToken();


    $data =
        getJsonInput();


    if (
        !is_array($data) ||
        empty($data)
    ) {

        validationResponse(
            [
                'form' =>
                    'No client data was received.'
            ],
            'Please enter client information.'
        );
    }


    /* ----------------------------------------------------
       BASIC INFORMATION
    ---------------------------------------------------- */

    $clientName = trim(
        (string) (
            $data['client_name'] ?? ''
        )
    );


    $companyName = trim(
        (string) (
            $data['company_name'] ?? ''
        )
    );


    $mobile = trim(
        (string) (
            $data['mobile'] ?? ''
        )
    );


    $whatsapp = trim(
        (string) (
            $data['whatsapp'] ?? ''
        )
    );


    $email = strtolower(
        trim(
            (string) (
                $data['email'] ?? ''
            )
        )
    );


    $alternatePhone = trim(
        (string) (
            $data['alternate_phone'] ?? ''
        )
    );


    /* ----------------------------------------------------
       BUSINESS
    ---------------------------------------------------- */

    $businessType = trim(
        (string) (
            $data['business_type'] ?? ''
        )
    );


    $industry = trim(
        (string) (
            $data['industry'] ?? ''
        )
    );


    $website = trim(
        (string) (
            $data['website'] ?? ''
        )
    );


    $businessDescription = trim(
        (string) (
            $data['business_description'] ?? ''
        )
    );


    /* ----------------------------------------------------
       ADDRESS
    ---------------------------------------------------- */

    $address = trim(
        (string) (
            $data['address'] ?? ''
        )
    );


    $city = trim(
        (string) (
            $data['city'] ?? ''
        )
    );


    $state = trim(
        (string) (
            $data['state'] ?? ''
        )
    );


    $pincode = trim(
        (string) (
            $data['pincode'] ?? ''
        )
    );


    /* ----------------------------------------------------
       CONTACT PERSON
    ---------------------------------------------------- */

    $contactPersonName = trim(
        (string) (
            $data['contact_person_name'] ?? ''
        )
    );


    $contactPersonDesignation = trim(
        (string) (
            $data['contact_person_designation'] ?? ''
        )
    );


    $contactPersonMobile = trim(
        (string) (
            $data['contact_person_mobile'] ?? ''
        )
    );


    $contactPersonEmail = strtolower(
        trim(
            (string) (
                $data['contact_person_email'] ?? ''
            )
        )
    );


    /* ----------------------------------------------------
       BILLING
    ---------------------------------------------------- */

    $billingName = trim(
        (string) (
            $data['billing_name']
            ?? $companyName
        )
    );


    $gstNumber = trim(
        (string) (
            $data['gst_number'] ?? ''
        )
    );


    $panNumber = trim(
        (string) (
            $data['pan_number'] ?? ''
        )
    );


    $billingAddress = trim(
        (string) (
            $data['billing_address'] ?? ''
        )
    );


    /* ----------------------------------------------------
       CLIENT PORTAL LOGIN
       ----------------------------------------------------
       Password is accepted ONLY during client creation.
       It is immediately converted to password_hash.
       Plain-text password is never stored.
    ---------------------------------------------------- */

    $loginEmail = strtolower(
        trim(
            (string) (
                $data['login_email']
                ?? $email
            )
        )
    );


    $password = (string) (
        $data['password'] ?? ''
    );


    $confirmPassword = (string) (
        $data['confirm_password'] ?? ''
    );


    /* ----------------------------------------------------
       STATUS
    ---------------------------------------------------- */

    $status = strtolower(
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


    /* ----------------------------------------------------
       NOTES
    ---------------------------------------------------- */

    $internalNotes = trim(
        (string) (
            $data['internal_notes'] ?? ''
        )
    );


    /* ----------------------------------------------------
       VALIDATION
    ---------------------------------------------------- */

    $errors = [];


    if ($clientName === '') {

        $errors['client_name'] =
            'Client name is required.';

    } elseif (
        mb_strlen($clientName) > 150
    ) {

        $errors['client_name'] =
            'Client name cannot exceed 150 characters.';
    }


    if ($companyName === '') {

        $errors['company_name'] =
            'Company name is required.';

    } elseif (
        mb_strlen($companyName) > 150
    ) {

        $errors['company_name'] =
            'Company name cannot exceed 150 characters.';
    }


    if ($mobile === '') {

        $errors['mobile'] =
            'Mobile number is required.';

    } elseif (
        mb_strlen($mobile) > 20
    ) {

        $errors['mobile'] =
            'Mobile number cannot exceed 20 characters.';
    }


    if (
        $email === '' ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors['email'] =
            'Please enter a valid email address.';

    } elseif (
        mb_strlen($email) > 150
    ) {

        $errors['email'] =
            'Email cannot exceed 150 characters.';
    }


    if (
        $loginEmail === '' ||
        !filter_var(
            $loginEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors['login_email'] =
            'Please enter a valid login email.';

    } elseif (
        mb_strlen($loginEmail) > 150
    ) {

        $errors['login_email'] =
            'Login email cannot exceed 150 characters.';
    }


    /*
     * Password is required ONLY when creating
     * a new client portal account.
     */

    if ($password === '') {

        $errors['password'] =
            'Portal password is required.';

    } elseif (
        strlen($password) < 8
    ) {

        $errors['password'] =
            'Portal password must contain at least 8 characters.';
    }


    if ($confirmPassword === '') {

        $errors['confirm_password'] =
            'Please confirm the password.';

    } elseif (
        $password !== $confirmPassword
    ) {

        $errors['confirm_password'] =
            'Passwords do not match.';
    }


    if ($contactPersonEmail !== '') {

        if (
            !filter_var(
                $contactPersonEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $errors['contact_person_email'] =
                'Invalid contact person email.';
        }
    }


    if (
        $whatsapp !== '' &&
        mb_strlen($whatsapp) > 20
    ) {

        $errors['whatsapp'] =
            'WhatsApp number cannot exceed 20 characters.';
    }


    if (
        $alternatePhone !== '' &&
        mb_strlen($alternatePhone) > 20
    ) {

        $errors['alternate_phone'] =
            'Alternate phone cannot exceed 20 characters.';
    }


    if (
        $businessType !== '' &&
        mb_strlen($businessType) > 100
    ) {

        $errors['business_type'] =
            'Business type cannot exceed 100 characters.';
    }


    if (
        $industry !== '' &&
        mb_strlen($industry) > 100
    ) {

        $errors['industry'] =
            'Industry cannot exceed 100 characters.';
    }


    if (
        $website !== '' &&
        mb_strlen($website) > 255
    ) {

        $errors['website'] =
            'Website cannot exceed 255 characters.';
    }


    if (
        $pincode !== '' &&
        mb_strlen($pincode) > 10
    ) {

        $errors['pincode'] =
            'Pincode cannot exceed 10 characters.';
    }


    if (
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {

        $errors['status'] =
            'Invalid client status.';
    }


    if (!empty($errors)) {

        validationResponse(
            $errors
        );
    }


    try {

        $pdo = db();


        /* ------------------------------------------------
           DUPLICATE LOGIN EMAIL
        ------------------------------------------------ */

        $check = $pdo->prepare(
            "
            SELECT id
            FROM clients
            WHERE login_email = :login_email
            LIMIT 1
            "
        );


        $check->execute([
            ':login_email' =>
                $loginEmail
        ]);


        if ($check->fetch()) {

            validationResponse([
                'login_email' =>
                    'This login email is already registered.'
            ]);
        }


        /* ------------------------------------------------
           DUPLICATE CLIENT EMAIL
        ------------------------------------------------ */

        $check = $pdo->prepare(
            "
            SELECT id
            FROM clients
            WHERE email = :email
            LIMIT 1
            "
        );


        $check->execute([
            ':email' =>
                $email
        ]);


        if ($check->fetch()) {

            validationResponse([
                'email' =>
                    'This email is already registered.'
            ]);
        }


        /* ------------------------------------------------
           CLIENT CODE
        ------------------------------------------------ */

        $clientCode =
            generateClientCode($pdo);


        /* ------------------------------------------------
           PASSWORD HASH
        ------------------------------------------------
           IMPORTANT:
           The plain password exists only in memory
           during this request.
        ------------------------------------------------ */

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        if ($passwordHash === false) {

            throw new RuntimeException(
                'Unable to secure client password.'
            );
        }


        /* ------------------------------------------------
           INSERT CLIENT
        ------------------------------------------------ */

        $statement = $pdo->prepare(
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


        $statement->execute([

            ':client_code' =>
                $clientCode,

            ':source_lead_code' =>
                null,

            ':client_name' =>
                $clientName,

            ':company_name' =>
                $companyName,

            ':mobile' =>
                $mobile,

            ':whatsapp' =>
                $whatsapp !== ''
                ? $whatsapp
                : null,

            ':email' =>
                $email,

            ':alternate_phone' =>
                $alternatePhone !== ''
                ? $alternatePhone
                : null,

            ':business_type' =>
                $businessType !== ''
                ? $businessType
                : null,

            ':industry' =>
                $industry !== ''
                ? $industry
                : null,

            ':website' =>
                $website !== ''
                ? $website
                : null,

            ':business_description' =>
                $businessDescription !== ''
                ? $businessDescription
                : null,

            ':address' =>
                $address !== ''
                ? $address
                : null,

            ':city' =>
                $city !== ''
                ? $city
                : null,

            ':state' =>
                $state !== ''
                ? $state
                : null,

            ':pincode' =>
                $pincode !== ''
                ? $pincode
                : null,

            ':contact_person_name' =>
                $contactPersonName !== ''
                ? $contactPersonName
                : null,

            ':contact_person_designation' =>
                $contactPersonDesignation !== ''
                ? $contactPersonDesignation
                : null,

            ':contact_person_mobile' =>
                $contactPersonMobile !== ''
                ? $contactPersonMobile
                : null,

            ':contact_person_email' =>
                $contactPersonEmail !== ''
                ? $contactPersonEmail
                : null,

            ':billing_name' =>
                $billingName !== ''
                ? $billingName
                : null,

            ':gst_number' =>
                $gstNumber !== ''
                ? $gstNumber
                : null,

            ':pan_number' =>
                $panNumber !== ''
                ? $panNumber
                : null,

            ':billing_address' =>
                $billingAddress !== ''
                ? $billingAddress
                : null,

            ':login_email' =>
                $loginEmail,

            ':password_hash' =>
                $passwordHash,

            ':status' =>
                $status,

            ':internal_notes' =>
                $internalNotes !== ''
                ? $internalNotes
                : null
        ]);


        $clientId =
            (int) $pdo->lastInsertId();


        if ($clientId <= 0) {

            throw new RuntimeException(
                'Database insert did not return a client ID.'
            );
        }


        recordClientActivity(
            'Created client ' .
            $clientCode
        );


        successResponse(
            'Client created successfully.',
            [

                'client_id' =>
                    $clientId,

                'client_code' =>
                    $clientCode

            ],
            201
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK CREATE CLIENT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        if (
            isset(
                $exception->errorInfo[1]
            ) &&
            (int) $exception->errorInfo[1] === 1062
        ) {

            validationResponse(
                [
                    'form' =>
                        'A client with the supplied unique information already exists.'
                ],
                'Client could not be created because unique information already exists.'
            );
        }


        serverErrorResponse(
            'Unable to create client because of a database error.'
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK CREATE CLIENT ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to create client.'
        );
    }
}


/* ============================================================
   UPDATE CLIENT
   PUT /api/clients/{id}
   ============================================================
 *
 * PASSWORD UPDATE IS INTENTIONALLY NOT IMPLEMENTED.
 *
 * This function does NOT:
 *
 * - accept password
 * - accept confirm_password
 * - generate password_hash
 * - modify password_hash
 *
 * Existing client password remains unchanged.
 *
 * ============================================================ */

function updateClient(int $clientId): void
{
    requireAdminAuthentication();

    requireCsrfToken();


    if ($clientId <= 0) {

        errorResponse(
            'Invalid client ID.',
            null,
            400
        );
    }


    $data =
        getJsonInput();


    if (
        !is_array($data) ||
        empty($data)
    ) {

        validationResponse(
            [
                'form' =>
                    'No client data was received.'
            ],
            'Please enter client information.'
        );
    }


    try {

        $pdo = db();


        /* ----------------------------------------------------
           EXISTING CLIENT
        ---------------------------------------------------- */

        $existingStatement = $pdo->prepare(
            "
            SELECT
                id,
                client_code
            FROM clients
            WHERE id = :id
            LIMIT 1
            "
        );


        $existingStatement->execute([
            ':id' =>
                $clientId
        ]);


        $existing =
            $existingStatement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$existing) {

            notFoundResponse(
                'Client not found.'
            );
        }


        /* ----------------------------------------------------
           BASIC INFORMATION
        ---------------------------------------------------- */

        $clientName = trim(
            (string) (
                $data['client_name'] ?? ''
            )
        );


        $companyName = trim(
            (string) (
                $data['company_name'] ?? ''
            )
        );


        $mobile = trim(
            (string) (
                $data['mobile'] ?? ''
            )
        );


        $whatsapp = trim(
            (string) (
                $data['whatsapp'] ?? ''
            )
        );


        $email = strtolower(
            trim(
                (string) (
                    $data['email'] ?? ''
                )
            )
        );


        $alternatePhone = trim(
            (string) (
                $data['alternate_phone'] ?? ''
            )
        );


        /* ----------------------------------------------------
           BUSINESS
        ---------------------------------------------------- */

        $businessType = trim(
            (string) (
                $data['business_type'] ?? ''
            )
        );


        $industry = trim(
            (string) (
                $data['industry'] ?? ''
            )
        );


        $website = trim(
            (string) (
                $data['website'] ?? ''
            )
        );


        $businessDescription = trim(
            (string) (
                $data['business_description'] ?? ''
            )
        );


        /* ----------------------------------------------------
           ADDRESS
        ---------------------------------------------------- */

        $address = trim(
            (string) (
                $data['address'] ?? ''
            )
        );


        $city = trim(
            (string) (
                $data['city'] ?? ''
            )
        );


        $state = trim(
            (string) (
                $data['state'] ?? ''
            )
        );


        $pincode = trim(
            (string) (
                $data['pincode'] ?? ''
            )
        );


        /* ----------------------------------------------------
           CONTACT PERSON
        ---------------------------------------------------- */

        $contactPersonName = trim(
            (string) (
                $data['contact_person_name'] ?? ''
            )
        );


        $contactPersonDesignation = trim(
            (string) (
                $data['contact_person_designation'] ?? ''
            )
        );


        $contactPersonMobile = trim(
            (string) (
                $data['contact_person_mobile'] ?? ''
            )
        );


        $contactPersonEmail = strtolower(
            trim(
                (string) (
                    $data['contact_person_email'] ?? ''
                )
            )
        );


        /* ----------------------------------------------------
           BILLING
        ---------------------------------------------------- */

        $billingName = trim(
            (string) (
                $data['billing_name']
                ?? $companyName
            )
        );


        $gstNumber = trim(
            (string) (
                $data['gst_number'] ?? ''
            )
        );


        $panNumber = trim(
            (string) (
                $data['pan_number'] ?? ''
            )
        );


        $billingAddress = trim(
            (string) (
                $data['billing_address'] ?? ''
            )
        );


        /* ----------------------------------------------------
           LOGIN EMAIL
        ---------------------------------------------------- */

        $loginEmail = strtolower(
            trim(
                (string) (
                    $data['login_email']
                    ?? $email
                )
            )
        );


        /* ----------------------------------------------------
           STATUS
        ---------------------------------------------------- */

        $status = strtolower(
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


        /* ----------------------------------------------------
           NOTES
        ---------------------------------------------------- */

        $internalNotes = trim(
            (string) (
                $data['internal_notes'] ?? ''
            )
        );


        /* ----------------------------------------------------
           VALIDATION
        ---------------------------------------------------- */

        $errors = [];


        if ($clientName === '') {

            $errors['client_name'] =
                'Client name is required.';

        } elseif (
            mb_strlen($clientName) > 150
        ) {

            $errors['client_name'] =
                'Client name cannot exceed 150 characters.';
        }


        if ($companyName === '') {

            $errors['company_name'] =
                'Company name is required.';

        } elseif (
            mb_strlen($companyName) > 150
        ) {

            $errors['company_name'] =
                'Company name cannot exceed 150 characters.';
        }


        if ($mobile === '') {

            $errors['mobile'] =
                'Mobile number is required.';

        } elseif (
            mb_strlen($mobile) > 20
        ) {

            $errors['mobile'] =
                'Mobile number cannot exceed 20 characters.';
        }


        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $errors['email'] =
                'Please enter a valid email address.';
        }


        if (
            $loginEmail === '' ||
            !filter_var(
                $loginEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $errors['login_email'] =
                'Please enter a valid login email.';
        }


        if ($contactPersonEmail !== '') {

            if (
                !filter_var(
                    $contactPersonEmail,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $errors['contact_person_email'] =
                    'Invalid contact person email.';
            }
        }


        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $errors['status'] =
                'Invalid client status.';
        }


        if (
            $whatsapp !== '' &&
            mb_strlen($whatsapp) > 20
        ) {

            $errors['whatsapp'] =
                'WhatsApp number cannot exceed 20 characters.';
        }


        if (
            $alternatePhone !== '' &&
            mb_strlen($alternatePhone) > 20
        ) {

            $errors['alternate_phone'] =
                'Alternate phone cannot exceed 20 characters.';
        }


        if (
            $website !== '' &&
            mb_strlen($website) > 255
        ) {

            $errors['website'] =
                'Website cannot exceed 255 characters.';
        }


        if (
            $pincode !== '' &&
            mb_strlen($pincode) > 10
        ) {

            $errors['pincode'] =
                'Pincode cannot exceed 10 characters.';
        }


        if (!empty($errors)) {

            validationResponse(
                $errors
            );
        }


        /* ----------------------------------------------------
           DUPLICATE LOGIN EMAIL
        ---------------------------------------------------- */

        $emailCheck = $pdo->prepare(
            "
            SELECT id
            FROM clients
            WHERE login_email = :login_email
            AND id != :client_id
            LIMIT 1
            "
        );


        $emailCheck->execute([

            ':login_email' =>
                $loginEmail,

            ':client_id' =>
                $clientId

        ]);


        if ($emailCheck->fetch()) {

            validationResponse([
                'login_email' =>
                    'This login email is already registered.'
            ]);
        }


        /* ----------------------------------------------------
           DUPLICATE CLIENT EMAIL
        ---------------------------------------------------- */

        $clientEmailCheck = $pdo->prepare(
            "
            SELECT id
            FROM clients
            WHERE email = :email
            AND id != :client_id
            LIMIT 1
            "
        );


        $clientEmailCheck->execute([

            ':email' =>
                $email,

            ':client_id' =>
                $clientId

        ]);


        if ($clientEmailCheck->fetch()) {

            validationResponse([
                'email' =>
                    'This email is already registered.'
            ]);
        }


        /* ----------------------------------------------------
           UPDATE CLIENT
        ----------------------------------------------------
         *
         * IMPORTANT:
         *
         * password_hash is intentionally absent.
         *
         * Password cannot be changed through this endpoint.
         *
         * -------------------------------------------------- */

        $statement = $pdo->prepare(
            "
            UPDATE clients
            SET

                client_name =
                    :client_name,

                company_name =
                    :company_name,

                mobile =
                    :mobile,

                whatsapp =
                    :whatsapp,

                email =
                    :email,

                alternate_phone =
                    :alternate_phone,

                business_type =
                    :business_type,

                industry =
                    :industry,

                website =
                    :website,

                business_description =
                    :business_description,

                address =
                    :address,

                city =
                    :city,

                state =
                    :state,

                pincode =
                    :pincode,

                contact_person_name =
                    :contact_person_name,

                contact_person_designation =
                    :contact_person_designation,

                contact_person_mobile =
                    :contact_person_mobile,

                contact_person_email =
                    :contact_person_email,

                billing_name =
                    :billing_name,

                gst_number =
                    :gst_number,

                pan_number =
                    :pan_number,

                billing_address =
                    :billing_address,

                login_email =
                    :login_email,

                status =
                    :status,

                internal_notes =
                    :internal_notes

            WHERE id = :client_id

            LIMIT 1
            "
        );


        $statement->execute([

            ':client_name' =>
                $clientName,

            ':company_name' =>
                $companyName,

            ':mobile' =>
                $mobile,

            ':whatsapp' =>
                $whatsapp !== ''
                ? $whatsapp
                : null,

            ':email' =>
                $email,

            ':alternate_phone' =>
                $alternatePhone !== ''
                ? $alternatePhone
                : null,

            ':business_type' =>
                $businessType !== ''
                ? $businessType
                : null,

            ':industry' =>
                $industry !== ''
                ? $industry
                : null,

            ':website' =>
                $website !== ''
                ? $website
                : null,

            ':business_description' =>
                $businessDescription !== ''
                ? $businessDescription
                : null,

            ':address' =>
                $address !== ''
                ? $address
                : null,

            ':city' =>
                $city !== ''
                ? $city
                : null,

            ':state' =>
                $state !== ''
                ? $state
                : null,

            ':pincode' =>
                $pincode !== ''
                ? $pincode
                : null,

            ':contact_person_name' =>
                $contactPersonName !== ''
                ? $contactPersonName
                : null,

            ':contact_person_designation' =>
                $contactPersonDesignation !== ''
                ? $contactPersonDesignation
                : null,

            ':contact_person_mobile' =>
                $contactPersonMobile !== ''
                ? $contactPersonMobile
                : null,

            ':contact_person_email' =>
                $contactPersonEmail !== ''
                ? $contactPersonEmail
                : null,

            ':billing_name' =>
                $billingName !== ''
                ? $billingName
                : null,

            ':gst_number' =>
                $gstNumber !== ''
                ? $gstNumber
                : null,

            ':pan_number' =>
                $panNumber !== ''
                ? $panNumber
                : null,

            ':billing_address' =>
                $billingAddress !== ''
                ? $billingAddress
                : null,

            ':login_email' =>
                $loginEmail,

            ':status' =>
                $status,

            ':internal_notes' =>
                $internalNotes !== ''
                ? $internalNotes
                : null,

            ':client_id' =>
                $clientId

        ]);


        recordClientActivity(
            'Updated client ' .
            (
                $existing['client_code']
                ?? ('ID ' . $clientId)
            )
        );


        successResponse(
            'Client updated successfully.',
            [

                'client_id' =>
                    $clientId

            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK UPDATE CLIENT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        if (
            isset(
                $exception->errorInfo[1]
            ) &&
            (int) $exception->errorInfo[1] === 1062
        ) {

            validationResponse(
                [
                    'form' =>
                        'A client with the supplied unique information already exists.'
                ],
                'Client could not be updated because unique information already exists.'
            );
        }


        serverErrorResponse(
            'Unable to update client because of a database error.'
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK UPDATE CLIENT ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to update client.'
        );
    }
}


/* ============================================================
   DELETE CLIENT
   DELETE /api/clients/{id}
   ============================================================ */

function deleteClient(int $clientId): void
{
    requireAdminAuthentication();

    requireCsrfToken();


    if ($clientId <= 0) {

        errorResponse(
            'Invalid client ID.',
            null,
            400
        );
    }


    try {

        $pdo = db();


        $statement = $pdo->prepare(
            "
            SELECT

                id,
                client_code,
                client_name

            FROM clients

            WHERE id = :id

            LIMIT 1
            "
        );


        $statement->execute([
            ':id' =>
                $clientId
        ]);


        $client =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$client) {

            notFoundResponse(
                'Client not found.'
            );
        }


        $delete = $pdo->prepare(
            "
            DELETE FROM clients
            WHERE id = :id
            LIMIT 1
            "
        );


        $delete->execute([
            ':id' =>
                $clientId
        ]);


        if (
            $delete->rowCount() !== 1
        ) {

            throw new RuntimeException(
                'Client could not be deleted.'
            );
        }


        recordClientActivity(
            'Deleted client ' .
            (
                $client['client_code']
                ?? ('ID ' . $clientId)
            )
        );


        successResponse(
            'Client deleted successfully.',
            [

                'client_id' =>
                    $clientId,

                'client_code' =>
                    $client['client_code']

            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK DELETE CLIENT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        if (
            isset(
                $exception->errorInfo[1]
            ) &&
            (int) $exception->errorInfo[1] === 1451
        ) {

            errorResponse(
                'This client cannot be deleted because related records exist.',
                null,
                409
            );
        }


        serverErrorResponse(
            'Unable to delete client because of a database error.'
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK DELETE CLIENT ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to delete client.'
        );
    }
}


/* ============================================================
   START CLIENT SESSION
   ============================================================ */

function startClientSession(): void
{
    if (
        session_status() === PHP_SESSION_NONE
    ) {

        session_start();
    }
}


/* ============================================================
   CLIENT LOGIN
   POST /api/client-auth/login
   ============================================================ */

function clientLogin(): void
{
    requireCsrfToken();


    $data =
        getJsonInput();


    if (
        !is_array($data) ||
        empty($data)
    ) {

        errorResponse(
            'Login credentials are required.',
            null,
            400
        );
    }


    /* --------------------------------------------------------
       LOGIN EMAIL
    -------------------------------------------------------- */

    $loginEmail = strtolower(
        trim(
            (string) (
                $data['login_email']
                ?? $data['email']
                ?? ''
            )
        )
    );


    /* --------------------------------------------------------
       PASSWORD
       --------------------------------------------------------
       This is the password supplied by the user.
       It is NEVER stored by this function.
    -------------------------------------------------------- */

    $password = (string) (
        $data['password'] ?? ''
    );


    if ($loginEmail === '') {

        errorResponse(
            'Login email is required.',
            null,
            422
        );
    }


    if (
        !filter_var(
            $loginEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        errorResponse(
            'Please enter a valid login email.',
            null,
            422
        );
    }


    if ($password === '') {

        errorResponse(
            'Password is required.',
            null,
            422
        );
    }


    try {

        $pdo = db();


        /* ----------------------------------------------------
           FIND CLIENT
        ---------------------------------------------------- */

        $statement = $pdo->prepare(
            "
            SELECT

                id,
                client_code,
                client_name,
                company_name,

                login_email,
                password_hash,

                status

            FROM clients

            WHERE login_email = :login_email

            LIMIT 1
            "
        );


        $statement->execute([
            ':login_email' =>
                $loginEmail
        ]);


        $client =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$client) {

            errorResponse(
                'Invalid login email or password.',
                null,
                401
            );
        }


        /* ----------------------------------------------------
           PASSWORD VERIFICATION
        ---------------------------------------------------- */

        $passwordHash = (string) (
            $client['password_hash'] ?? ''
        );


        if (
            $passwordHash === '' ||
            !password_verify(
                $password,
                $passwordHash
            )
        ) {

            errorResponse(
                'Invalid login email or password.',
                null,
                401
            );
        }


        /* ----------------------------------------------------
           ACCOUNT STATUS
        ---------------------------------------------------- */

        $status = strtolower(
            trim(
                (string) (
                    $client['status'] ?? ''
                )
            )
        );


        if ($status !== 'active') {

            if (
                $status === 'suspended'
            ) {

                errorResponse(
                    'Your client portal account is suspended. Please contact Tenspick.',
                    null,
                    403
                );
            }


            if (
                $status === 'inactive'
            ) {

                errorResponse(
                    'Your client portal account is inactive. Please contact Tenspick.',
                    null,
                    403
                );
            }


            errorResponse(
                'Your client portal account is not active.',
                null,
                403
            );
        }


        /* ----------------------------------------------------
           START SESSION
        ---------------------------------------------------- */

        startClientSession();


        /*
         * Prevent session fixation.
         */

        session_regenerate_id(true);


        /*
         * Remove any previous client authentication
         * information from the regenerated session.
         */

        clearClientSession();


        /* ----------------------------------------------------
           CREATE AUTHENTICATED CLIENT SESSION
        ---------------------------------------------------- */

        $_SESSION[
            'tenspick_client_authenticated'
        ] = true;


        $_SESSION[
            'tenspick_client_id'
        ] = (int) (
            $client['id']
        );


        $_SESSION[
            'tenspick_client_code'
        ] = (string) (
            $client['client_code'] ?? ''
        );


        $_SESSION[
            'tenspick_client_login_email'
        ] = (string) (
            $client['login_email'] ?? ''
        );


        $_SESSION[
            'tenspick_client_login_at'
        ] = date(
            'Y-m-d H:i:s'
        );


        /*
         * Ensure the session is persisted before
         * the response is returned.
         */

        session_write_close();


        /* ----------------------------------------------------
           RESPONSE
        ---------------------------------------------------- */

        successResponse(
            'Client login successful.',
            [

                'authenticated' =>
                    true,

                'client' => [

                    'id' =>
                        (int) (
                            $client['id']
                        ),

                    'client_code' =>
                        (string) (
                            $client['client_code']
                            ?? ''
                        ),

                    'client_name' =>
                        (string) (
                            $client['client_name']
                            ?? ''
                        ),

                    'company_name' =>
                        (string) (
                            $client['company_name']
                            ?? ''
                        ),

                    'login_email' =>
                        (string) (
                            $client['login_email']
                            ?? ''
                        ),

                    'status' =>
                        $status

                ]

            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK CLIENT LOGIN DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to process client login.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK CLIENT LOGIN ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to process client login.',
            null,
            500
        );
    }
}


/* ============================================================
   CLIENT LOGOUT
   POST /api/client-auth/logout
   ============================================================ */

function clientLogout(): void
{
    requireCsrfToken();


    startClientSession();


    clearClientSession();


    /*
     * Destroy the session completely.
     *
     * This ensures the old client authentication
     * cannot be reused after logout.
     */

    if (
        session_status() === PHP_SESSION_ACTIVE
    ) {

        $_SESSION = [];


        if (
            ini_get('session.use_cookies')
        ) {

            $params =
                session_get_cookie_params();


            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }


        session_destroy();
    }


    successResponse(
        'Client logged out successfully.',
        [
            'authenticated' =>
                false
        ]
    );
}


/* ============================================================
   GET CURRENT CLIENT
   GET /api/client-auth/me
   ============================================================ */

function getClientMe(): void
{
    $clientId =
        getAuthenticatedClientId();


    if ($clientId <= 0) {

        errorResponse(
            'Client authentication required.',
            null,
            401
        );
    }


    try {

        $pdo = db();


        /*
         * Client ID comes exclusively from the
         * server-side session.
         */

        $statement = $pdo->prepare(
            "
            SELECT

                id,
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

                status,

                created_at,
                updated_at

            FROM clients

            WHERE id = :client_id

            LIMIT 1
            "
        );


        $statement->execute([
            ':client_id' =>
                $clientId
        ]);


        $client =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$client) {

            clearClientSession();


            errorResponse(
                'Client account was not found.',
                null,
                401
            );
        }


        $status = strtolower(
            trim(
                (string) (
                    $client['status'] ?? ''
                )
            )
        );


        if ($status !== 'active') {

            clearClientSession();


            if (
                $status === 'suspended'
            ) {

                errorResponse(
                    'Your client portal account is suspended.',
                    null,
                    403
                );
            }


            errorResponse(
                'Your client portal account is inactive.',
                null,
                403
            );
        }


        successResponse(
            'Client session is valid.',
            [

                'authenticated' =>
                    true,

                'client' =>
                    $client

            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK CLIENT ME DATABASE ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to verify client session.'
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK CLIENT ME ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to verify client session.'
        );
    }
}


/* ============================================================
   REQUIRE CLIENT AUTHENTICATION
   ============================================================ */

/**
 * Returns authenticated client ID.
 *
 * Client portal controllers MUST use:
 *
 * $clientId = requireClientAuthentication();
 *
 * Then:
 *
 * WHERE client_id = :client_id
 *
 * Never accept client_id from frontend input.
 */
function requireClientAuthentication(): int
{
    $clientId =
        getAuthenticatedClientId();


    if ($clientId <= 0) {

        errorResponse(
            'Client authentication required.',
            null,
            401
        );
    }


    try {

        $pdo = db();


        $statement = $pdo->prepare(
            "
            SELECT
                id,
                status
            FROM clients
            WHERE id = :client_id
            LIMIT 1
            "
        );


        $statement->execute([
            ':client_id' =>
                $clientId
        ]);


        $client =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$client) {

            clearClientSession();


            errorResponse(
                'Client authentication is no longer valid.',
                null,
                401
            );
        }


        $status = strtolower(
            trim(
                (string) (
                    $client['status'] ?? ''
                )
            )
        );


        if ($status !== 'active') {

            clearClientSession();


            if (
                $status === 'suspended'
            ) {

                errorResponse(
                    'Client portal access is suspended.',
                    null,
                    403
                );
            }


            errorResponse(
                'Client portal access is not active.',
                null,
                403
            );
        }


        return $clientId;

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK REQUIRE CLIENT AUTH DATABASE ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to verify client authentication.'
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK REQUIRE CLIENT AUTH ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to verify client authentication.'
        );
    }


    return 0;
}


/* ============================================================
   GET AUTHENTICATED CLIENT ID
   ============================================================ */

function getAuthenticatedClientId(): int
{
    startClientSession();


    if (
        empty(
            $_SESSION[
                'tenspick_client_authenticated'
            ]
        )
    ) {

        return 0;
    }


    $clientId = (int) (
        $_SESSION[
            'tenspick_client_id'
        ] ?? 0
    );


    if ($clientId <= 0) {

        return 0;
    }


    return $clientId;
}


/* ============================================================
   GET AUTHENTICATED CLIENT
   ============================================================ */

function getAuthenticatedClient(): array
{
    $clientId =
        requireClientAuthentication();


    try {

        $pdo = db();


        $statement = $pdo->prepare(
            "
            SELECT

                id,
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

                status,

                created_at,
                updated_at

            FROM clients

            WHERE id = :client_id

            LIMIT 1
            "
        );


        $statement->execute([
            ':client_id' =>
                $clientId
        ]);


        $client =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$client) {

            clearClientSession();


            errorResponse(
                'Client account was not found.',
                null,
                401
            );
        }


        return $client;

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK GET AUTHENTICATED CLIENT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to load authenticated client.'
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET AUTHENTICATED CLIENT ERROR: ' .
            $exception->getMessage()
        );


        serverErrorResponse(
            'Unable to load authenticated client.'
        );
    }


    return [];
}


/* ============================================================
   CLEAR CLIENT SESSION
   ============================================================ */

function clearClientSession(): void
{
    startClientSession();


    unset(

        $_SESSION[
            'tenspick_client_authenticated'
        ],

        $_SESSION[
            'tenspick_client_id'
        ],

        $_SESSION[
            'tenspick_client_code'
        ],

        $_SESSION[
            'tenspick_client_login_email'
        ],

        $_SESSION[
            'tenspick_client_login_at'
        ]

    );
}


/* ============================================================
   GENERATE CLIENT CODE
   ============================================================ */

function generateClientCode(PDO $pdo): string
{
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


    $lastCode =
        $statement->fetchColumn();


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


    for (
        $attempt = 0;
        $attempt < 100;
        $attempt++
    ) {

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


        if (
            !$check->fetchColumn()
        ) {

            return $clientCode;
        }


        $number++;
    }


    throw new RuntimeException(
        'Unable to generate a unique client code.'
    );
}


/* ============================================================
   CLIENT ACTIVITY
   ============================================================ */

function recordClientActivity(
    string $description
): void {

    if (
        function_exists('logActivity')
    ) {

        try {

            logActivity(
                $description
            );

        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT ACTIVITY ERROR: ' .
                $exception->getMessage()
            );
        }
    }
}