<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * PAYMENT CONTROLLER
 * ============================================================
 *
 * Handles:
 *
 * GET    /api/payments
 * GET    /api/payments/{id}
 * POST   /api/payments
 * PUT    /api/payments/{id}
 * DELETE /api/payments/{id}
 *
 * GET    /api/clients/{id}/payments
 * GET    /api/clients/{id}/payment-projects
 * GET    /api/projects/{id}/payments
 *
 * ============================================================
 *
 * IMPORTANT FINANCIAL RULE
 * ------------------------------------------------------------
 *
 * Every payment stores a historical snapshot:
 *
 * project_amount_snapshot
 * paid_before
 * paid_after
 * remaining_after
 *
 * These values are NEVER recalculated for old payments.
 *
 * Example:
 *
 * Project = 50,000
 *
 * Payment 1 = 15,000
 * paid_before = 0
 * paid_after = 15,000
 * remaining_after = 35,000
 *
 * Payment 2 = 10,000
 * paid_before = 15,000
 * paid_after = 25,000
 * remaining_after = 25,000
 *
 * Payment 1 continues to show 35,000 forever.
 *
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| COMMON HELPERS
|--------------------------------------------------------------------------
*/

/**
 * Require administrator authentication.
 *
 * This controller assumes the existing Tenspick API bootstrap/session
 * has already started the session.
 */
if (!function_exists('paymentRequireAdmin')) {

    function paymentRequireAdmin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (
            empty($_SESSION['admin']) &&
            empty($_SESSION['admin_id'])
        ) {
            paymentJsonResponse(
                false,
                'Unauthorized access.',
                [],
                401
            );
        }
    }
}


/**
 * JSON response helper.
 */
if (!function_exists('paymentJsonResponse')) {

    function paymentJsonResponse(
        bool $success,
        string $message = '',
        array $data = [],
        int $statusCode = 200
    ): never {

        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'success' => $success,
                'message' => $message,
                'data'    => $data
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}


/**
 * Get request method.
 */
if (!function_exists('paymentRequestMethod')) {

    function paymentRequestMethod(): string
    {
        return strtoupper(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }
}


/**
 * Read JSON request body.
 */
if (!function_exists('paymentRequestBody')) {

    function paymentRequestBody(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode(
            $raw,
            true
        );

        if (!is_array($data)) {
            paymentJsonResponse(
                false,
                'Invalid JSON request.',
                [],
                400
            );
        }

        return $data;
    }
}


/**
 * Get PDO connection.
 *
 * This supports the common Tenspick DB helper patterns.
 */
if (!function_exists('paymentGetPDO')) {

    function paymentGetPDO(): PDO
    {
        /*
        |----------------------------------------------------------
        | Existing PDO function
        |----------------------------------------------------------
        */

        if (function_exists('getPDO')) {
            $pdo = getPDO();

            if ($pdo instanceof PDO) {
                return $pdo;
            }
        }

        if (function_exists('getDatabaseConnection')) {
            $pdo = getDatabaseConnection();

            if ($pdo instanceof PDO) {
                return $pdo;
            }
        }

        if (function_exists('db')) {
            $pdo = db();

            if ($pdo instanceof PDO) {
                return $pdo;
            }
        }

        /*
        |----------------------------------------------------------
        | Existing global PDO variables
        |----------------------------------------------------------
        */

        global $pdo;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        global $db;

        if ($db instanceof PDO) {
            return $db;
        }

        paymentJsonResponse(
            false,
            'Database connection is not available.',
            [],
            500
        );
    }
}


/**
 * Validate CSRF token.
 *
 * The controller supports:
 *
 * X-CSRF-TOKEN
 * X-CSRF-Token
 * csrf_token
 */
if (!function_exists('paymentVerifyCsrf')) {

    function paymentVerifyCsrf(array $request = []): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionToken =
            $_SESSION['csrf_token']
            ?? $_SESSION['_csrf']
            ?? null;

        $headerToken =
            $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;

        $requestToken =
            $request['csrf_token']
            ?? null;

        $token =
            $headerToken
            ?? $requestToken;

        /*
        |----------------------------------------------------------
        | If application already has a global CSRF validator,
        | use it.
        |----------------------------------------------------------
        */

        if (function_exists('verifyCsrfToken')) {

            if (!verifyCsrfToken($token)) {

                paymentJsonResponse(
                    false,
                    'Invalid CSRF token.',
                    [],
                    419
                );
            }

            return;
        }

        /*
        |----------------------------------------------------------
        | Fallback validation
        |----------------------------------------------------------
        */

        if (
            !$sessionToken ||
            !$token ||
            !hash_equals(
                (string) $sessionToken,
                (string) $token
            )
        ) {
            paymentJsonResponse(
                false,
                'Invalid or missing CSRF token.',
                [],
                419
            );
        }
    }
}


/**
 * Get current admin/staff ID.
 */
if (!function_exists('paymentGetCurrentAdminId')) {

    function paymentGetCurrentAdminId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $possibleIds = [
            $_SESSION['admin_id'] ?? null,
            $_SESSION['admin']['id'] ?? null,
            $_SESSION['admin']['staff_id'] ?? null,
            $_SESSION['user_id'] ?? null
        ];

        foreach ($possibleIds as $id) {

            if (
                $id !== null &&
                $id !== '' &&
                filter_var($id, FILTER_VALIDATE_INT) !== false
            ) {
                return (int) $id;
            }
        }

        return null;
    }
}


/**
 * Validate integer ID.
 */
if (!function_exists('paymentValidateId')) {

    function paymentValidateId(
        mixed $value,
        string $field
    ): int {

        if (
            $value === null ||
            $value === '' ||
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            paymentJsonResponse(
                false,
                $field . ' must be a valid ID.',
                [],
                422
            );
        }

        $id = (int) $value;

        if ($id <= 0) {
            paymentJsonResponse(
                false,
                $field . ' must be greater than zero.',
                [],
                422
            );
        }

        return $id;
    }
}


/**
 * Normalize decimal amount.
 */
if (!function_exists('paymentNormalizeAmount')) {

    function paymentNormalizeAmount(mixed $value): float
    {
        if (
            $value === null ||
            $value === '' ||
            !is_numeric($value)
        ) {
            paymentJsonResponse(
                false,
                'Payment amount must be a valid number.',
                [],
                422
            );
        }

        $amount = round(
            (float) $value,
            2
        );

        if ($amount <= 0) {
            paymentJsonResponse(
                false,
                'Payment amount must be greater than zero.',
                [],
                422
            );
        }

        return $amount;
    }
}


/**
 * Validate payment date.
 */
if (!function_exists('paymentValidateDate')) {

    function paymentValidateDate(
        mixed $value
    ): string {

        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            paymentJsonResponse(
                false,
                'Payment date is required.',
                [],
                422
            );
        }

        $date = trim(
            (string) $value
        );

        $object = DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

        if (
            !$object ||
            $object->format('Y-m-d') !== $date
        ) {
            paymentJsonResponse(
                false,
                'Payment date must be in YYYY-MM-DD format.',
                [],
                422
            );
        }

        return $date;
    }
}


/**
 * Generate unique payment code.
 *
 * Format:
 *
 * PAY-YYYYMMDD-0001
 */
if (!function_exists('paymentGenerateCode')) {

    function paymentGenerateCode(
        PDO $pdo
    ): string {

        $datePart = date('Ymd');

        $stmt = $pdo->prepare(
            "
            SELECT payment_code
            FROM client_payments
            WHERE payment_code LIKE ?
            ORDER BY id DESC
            LIMIT 1
            "
        );

        $stmt->execute(
            [
                'PAY-' . $datePart . '-%'
            ]
        );

        $lastCode = $stmt->fetchColumn();

        if (
            $lastCode &&
            preg_match(
                '/PAY-' . $datePart . '-(\d+)$/',
                (string) $lastCode,
                $matches
            )
        ) {
            $number = ((int) $matches[1]) + 1;
        } else {
            $number = 1;
        }

        return sprintf(
            'PAY-%s-%04d',
            $datePart,
            $number
        );
    }
}


/*
|--------------------------------------------------------------------------
| PAYMENT CONTROLLER
|--------------------------------------------------------------------------
*/

class PaymentController
{
    private PDO $pdo;


    /**
     * Constructor.
     */
    public function __construct()
    {
        paymentRequireAdmin();

        $this->pdo = paymentGetPDO();

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/payments
    |--------------------------------------------------------------------------
    |
    | List payments.
    |
    | Supported filters:
    |
    | search
    | client_id
    | project_id
    | payment_method
    | date_from
    | date_to
    | page
    | limit
    |
    */

    public function index(): never
    {
        try {

            $search =
                trim(
                    (string) (
                        $_GET['search']
                        ?? ''
                    )
                );

            $clientId =
                !empty($_GET['client_id'])
                    ? (int) $_GET['client_id']
                    : null;

            $projectId =
                !empty($_GET['project_id'])
                    ? (int) $_GET['project_id']
                    : null;

            $paymentMethod =
                trim(
                    (string) (
                        $_GET['payment_method']
                        ?? ''
                    )
                );

            $dateFrom =
                trim(
                    (string) (
                        $_GET['date_from']
                        ?? ''
                    )
                );

            $dateTo =
                trim(
                    (string) (
                        $_GET['date_to']
                        ?? ''
                    )
                );

            $page =
                max(
                    1,
                    (int) (
                        $_GET['page']
                        ?? 1
                    )
                );

            $limit =
                (int) (
                    $_GET['limit']
                    ?? 20
                );

            $limit = max(
                1,
                min(
                    $limit,
                    100
                )
            );

            $offset =
                ($page - 1) * $limit;


            /*
            |------------------------------------------------------
            | WHERE
            |------------------------------------------------------
            */

            $where = [];

            $params = [];


            if ($search !== '') {

                $where[] = "
                    (
                        cp.payment_code LIKE :search
                        OR c.client_name LIKE :search
                        OR c.company_name LIKE :search
                        OR p.project_name LIKE :search
                        OR p.project_code LIKE :search
                        OR cp.transaction_id LIKE :search
                        OR cp.purpose LIKE :search
                    )
                ";

                $params[':search'] =
                    '%' . $search . '%';
            }


            if ($clientId !== null && $clientId > 0) {

                $where[] =
                    'cp.client_id = :client_id';

                $params[':client_id'] =
                    $clientId;
            }


            if ($projectId !== null && $projectId > 0) {

                $where[] =
                    'cp.project_id = :project_id';

                $params[':project_id'] =
                    $projectId;
            }


            if ($paymentMethod !== '') {

                $allowedMethods = [
                    'cash',
                    'upi',
                    'bank_transfer',
                    'card',
                    'cheque',
                    'other'
                ];

                if (
                    !in_array(
                        $paymentMethod,
                        $allowedMethods,
                        true
                    )
                ) {
                    paymentJsonResponse(
                        false,
                        'Invalid payment method.',
                        [],
                        422
                    );
                }

                $where[] =
                    'cp.payment_method = :payment_method';

                $params[':payment_method'] =
                    $paymentMethod;
            }


            if ($dateFrom !== '') {

                $dateFrom =
                    paymentValidateDate(
                        $dateFrom
                    );

                $where[] =
                    'cp.payment_date >= :date_from';

                $params[':date_from'] =
                    $dateFrom;
            }


            if ($dateTo !== '') {

                $dateTo =
                    paymentValidateDate(
                        $dateTo
                    );

                $where[] =
                    'cp.payment_date <= :date_to';

                $params[':date_to'] =
                    $dateTo;
            }


            $whereSql = '';

            if (!empty($where)) {

                $whereSql =
                    'WHERE ' .
                    implode(
                        ' AND ',
                        $where
                    );
            }


            /*
            |------------------------------------------------------
            | TOTAL
            |------------------------------------------------------
            */

            $countSql = "
                SELECT COUNT(*)
                FROM client_payments cp
                INNER JOIN clients c
                    ON c.id = cp.client_id
                INNER JOIN projects p
                    ON p.id = cp.project_id
                {$whereSql}
            ";

            $countStmt =
                $this->pdo->prepare(
                    $countSql
                );

            $countStmt->execute(
                $params
            );

            $total =
                (int) $countStmt->fetchColumn();


            /*
            |------------------------------------------------------
            | PAYMENT LIST
            |------------------------------------------------------
            */

            $sql = "
                SELECT

                    cp.id,
                    cp.payment_code,

                    cp.client_id,
                    cp.project_id,

                    cp.purpose,
                    cp.amount,
                    cp.payment_date,
                    cp.payment_method,

                    cp.transaction_id,
                    cp.remarks,

                    cp.project_amount_snapshot,
                    cp.paid_before,
                    cp.paid_after,
                    cp.remaining_after,

                    cp.added_by,

                    cp.created_at,
                    cp.updated_at,

                    c.client_name,
                    c.company_name,
                    c.mobile,
                    c.email,

                    p.project_code,
                    p.project_name,
                    p.project_type,
                    p.budget AS current_project_budget,

                    s.name AS added_by_name

                FROM client_payments cp

                INNER JOIN clients c
                    ON c.id = cp.client_id

                INNER JOIN projects p
                    ON p.id = cp.project_id

                LEFT JOIN staff s
                    ON s.id = cp.added_by

                {$whereSql}

                ORDER BY
                    cp.payment_date DESC,
                    cp.id DESC

                LIMIT :limit
                OFFSET :offset
            ";

            $stmt =
                $this->pdo->prepare(
                    $sql
                );


            foreach (
                $params
                as $key => $value
            ) {
                $stmt->bindValue(
                    $key,
                    $value
                );
            }

            $stmt->bindValue(
                ':limit',
                $limit,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':offset',
                $offset,
                PDO::PARAM_INT
            );

            $stmt->execute();


            $payments =
                $stmt->fetchAll();


            /*
            |------------------------------------------------------
            | TOTAL AMOUNT FOR CURRENT FILTER
            |------------------------------------------------------
            */

            $sumSql = "
                SELECT
                    COALESCE(
                        SUM(cp.amount),
                        0
                    )
                FROM client_payments cp

                INNER JOIN clients c
                    ON c.id = cp.client_id

                INNER JOIN projects p
                    ON p.id = cp.project_id

                {$whereSql}
            ";

            $sumStmt =
                $this->pdo->prepare(
                    $sumSql
                );

            $sumStmt->execute(
                $params
            );

            $totalAmount =
                (float) $sumStmt->fetchColumn();


            $totalPages =
                $total > 0
                    ? (int) ceil(
                        $total / $limit
                    )
                    : 0;


            paymentJsonResponse(
                true,
                'Payments fetched successfully.',
                [
                    'payments' => $payments,

                    'pagination' => [
                        'page' =>
                            $page,

                        'limit' =>
                            $limit,

                        'total' =>
                            $total,

                        'total_pages' =>
                            $totalPages
                    ],

                    'summary' => [
                        'total_amount' =>
                            number_format(
                                $totalAmount,
                                2,
                                '.',
                                ''
                            )
                    ]
                ]
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@index: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to fetch payments.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/{id}
    |--------------------------------------------------------------------------
    */

    public function show(
        int $paymentId
    ): never {

        $paymentId =
            paymentValidateId(
                $paymentId,
                'Payment ID'
            );

        try {

            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        cp.id,
                        cp.payment_code,

                        cp.client_id,
                        cp.project_id,

                        cp.purpose,
                        cp.amount,
                        cp.payment_date,
                        cp.payment_method,

                        cp.transaction_id,
                        cp.remarks,

                        cp.project_amount_snapshot,
                        cp.paid_before,
                        cp.paid_after,
                        cp.remaining_after,

                        cp.added_by,

                        cp.created_at,
                        cp.updated_at,

                        c.client_name,
                        c.company_name,
                        c.mobile,
                        c.whatsapp,
                        c.email,

                        c.address,
                        c.city,
                        c.state,
                        c.pincode,

                        c.billing_name,
                        c.gst_number,
                        c.pan_number,
                        c.billing_address,

                        p.project_code,
                        p.project_name,
                        p.project_type,
                        p.description,

                        p.start_date,
                        p.expected_completion,

                        p.budget AS current_project_budget,

                        p.status AS project_status,

                        s.name AS added_by_name

                    FROM client_payments cp

                    INNER JOIN clients c
                        ON c.id = cp.client_id

                    INNER JOIN projects p
                        ON p.id = cp.project_id

                    LEFT JOIN staff s
                        ON s.id = cp.added_by

                    WHERE cp.id = ?

                    LIMIT 1
                    "
                );

            $stmt->execute(
                [
                    $paymentId
                ]
            );

            $payment =
                $stmt->fetch();


            if (!$payment) {

                paymentJsonResponse(
                    false,
                    'Payment not found.',
                    [],
                    404
                );
            }


            paymentJsonResponse(
                true,
                'Payment fetched successfully.',
                [
                    'payment' => $payment
                ]
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@show: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to fetch payment.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | POST /api/payments
    |--------------------------------------------------------------------------
    |
    | Create payment.
    |
    */

    public function store(): never
    {
        $request =
            paymentRequestBody();


        paymentVerifyCsrf(
            $request
        );


        /*
        |----------------------------------------------------------
        | INPUT
        |----------------------------------------------------------
        */

        $clientId =
            paymentValidateId(
                $request['client_id']
                    ?? null,
                'Client ID'
            );


        $projectId =
            paymentValidateId(
                $request['project_id']
                    ?? null,
                'Project ID'
            );


        $amount =
            paymentNormalizeAmount(
                $request['amount']
                    ?? null
            );


        $paymentDate =
            paymentValidateDate(
                $request['payment_date']
                    ?? null
            );


        $paymentMethod =
            trim(
                (string) (
                    $request['payment_method']
                    ?? ''
                )
            );


        $allowedMethods = [
            'cash',
            'upi',
            'bank_transfer',
            'card',
            'cheque',
            'other'
        ];


        if (
            !in_array(
                $paymentMethod,
                $allowedMethods,
                true
            )
        ) {
            paymentJsonResponse(
                false,
                'Invalid payment method.',
                [],
                422
            );
        }


        $purpose =
            trim(
                (string) (
                    $request['purpose']
                    ?? ''
                )
            );


        if (
            mb_strlen($purpose) > 255
        ) {
            paymentJsonResponse(
                false,
                'Payment purpose cannot exceed 255 characters.',
                [],
                422
            );
        }


        $transactionId =
            trim(
                (string) (
                    $request['transaction_id']
                    ?? ''
                )
            );


        if (
            mb_strlen($transactionId) > 150
        ) {
            paymentJsonResponse(
                false,
                'Transaction ID cannot exceed 150 characters.',
                [],
                422
            );
        }


        $remarks =
            trim(
                (string) (
                    $request['remarks']
                    ?? ''
                )
            );


        if (
            mb_strlen($remarks) > 5000
        ) {
            paymentJsonResponse(
                false,
                'Remarks cannot exceed 5000 characters.',
                [],
                422
            );
        }


        /*
        |----------------------------------------------------------
        | TRANSACTION
        |----------------------------------------------------------
        */

        try {

            $this->pdo->beginTransaction();


            /*
            |------------------------------------------------------
            | LOCK PROJECT
            |------------------------------------------------------
            |
            | This is critical.
            |
            | It prevents two simultaneous payment requests from
            | calculating the same paid_before amount.
            |
            */

            $projectStmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        id,
                        client_id,
                        budget,
                        status

                    FROM projects

                    WHERE id = ?

                    FOR UPDATE
                    "
                );

            $projectStmt->execute(
                [
                    $projectId
                ]
            );

            $project =
                $projectStmt->fetch();


            if (!$project) {

                $this->pdo->rollBack();

                paymentJsonResponse(
                    false,
                    'Project not found.',
                    [],
                    404
                );
            }


            /*
            |------------------------------------------------------
            | VERIFY CLIENT / PROJECT RELATIONSHIP
            |------------------------------------------------------
            */

            if (
                (int) $project['client_id']
                !== $clientId
            ) {

                $this->pdo->rollBack();

                paymentJsonResponse(
                    false,
                    'Selected project does not belong to the selected client.',
                    [],
                    422
                );
            }


            /*
            |------------------------------------------------------
            | PROJECT AMOUNT
            |------------------------------------------------------
            */

            $projectAmount =
                round(
                    (float) $project['budget'],
                    2
                );


            if ($projectAmount < 0) {

                $this->pdo->rollBack();

                paymentJsonResponse(
                    false,
                    'Project budget is invalid.',
                    [],
                    422
                );
            }


            /*
            |------------------------------------------------------
            | TOTAL PAID BEFORE THIS PAYMENT
            |------------------------------------------------------
            */

            $paidStmt =
                $this->pdo->prepare(
                    "
                    SELECT
                        COALESCE(
                            SUM(amount),
                            0
                        )
                    FROM client_payments
                    WHERE project_id = ?
                    "
                );

            $paidStmt->execute(
                [
                    $projectId
                ]
            );

            $paidBefore =
                round(
                    (float) $paidStmt->fetchColumn(),
                    2
                );


            /*
            |------------------------------------------------------
            | CHECK PROJECT BALANCE
            |------------------------------------------------------
            */

            $remainingBefore =
                round(
                    $projectAmount -
                    $paidBefore,
                    2
                );


            /*
            |------------------------------------------------------
            | PREVENT OVERPAYMENT
            |------------------------------------------------------
            */

            if (
                $amount >
                $remainingBefore
            ) {

                $this->pdo->rollBack();

                paymentJsonResponse(
                    false,
                    'Payment amount exceeds the remaining project balance.',
                    [
                        'project_amount' =>
                            number_format(
                                $projectAmount,
                                2,
                                '.',
                                ''
                            ),

                        'paid_before' =>
                            number_format(
                                $paidBefore,
                                2,
                                '.',
                                ''
                            ),

                        'remaining_before' =>
                            number_format(
                                max(
                                    0,
                                    $remainingBefore
                                ),
                                2,
                                '.',
                                ''
                            ),

                        'attempted_payment' =>
                            number_format(
                                $amount,
                                2,
                                '.',
                                ''
                            )
                    ],
                    422
                );
            }


            /*
            |------------------------------------------------------
            | CALCULATE HISTORICAL SNAPSHOT
            |------------------------------------------------------
            */

            $paidAfter =
                round(
                    $paidBefore +
                    $amount,
                    2
                );


            $remainingAfter =
                round(
                    $projectAmount -
                    $paidAfter,
                    2
                );


            /*
            |------------------------------------------------------
            | PROTECT AGAINST FLOAT ROUNDING
            |------------------------------------------------------
            */

            if (
                abs($remainingAfter) < 0.005
            ) {
                $remainingAfter = 0.00;
            }


            /*
            |------------------------------------------------------
            | PAYMENT CODE
            |------------------------------------------------------
            */

            $paymentCode =
                paymentGenerateCode(
                    $this->pdo
                );


            /*
            |------------------------------------------------------
            | CURRENT ADMIN
            |------------------------------------------------------
            */

            $addedBy =
                paymentGetCurrentAdminId();


            /*
            |------------------------------------------------------
            | INSERT PAYMENT
            |------------------------------------------------------
            */

            $insertStmt =
                $this->pdo->prepare(
                    "
                    INSERT INTO client_payments (

                        payment_code,

                        client_id,
                        project_id,

                        purpose,

                        amount,

                        payment_date,

                        payment_method,

                        transaction_id,

                        remarks,

                        project_amount_snapshot,

                        paid_before,

                        paid_after,

                        remaining_after,

                        added_by

                    ) VALUES (

                        :payment_code,

                        :client_id,
                        :project_id,

                        :purpose,

                        :amount,

                        :payment_date,

                        :payment_method,

                        :transaction_id,

                        :remarks,

                        :project_amount_snapshot,

                        :paid_before,

                        :paid_after,

                        :remaining_after,

                        :added_by

                    )
                    "
                );


            $insertStmt->bindValue(
                ':payment_code',
                $paymentCode
            );

            $insertStmt->bindValue(
                ':client_id',
                $clientId,
                PDO::PARAM_INT
            );

            $insertStmt->bindValue(
                ':project_id',
                $projectId,
                PDO::PARAM_INT
            );

            $insertStmt->bindValue(
                ':purpose',
                $purpose !== ''
                    ? $purpose
                    : null,
                $purpose !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $insertStmt->bindValue(
                ':amount',
                number_format(
                    $amount,
                    2,
                    '.',
                    ''
                )
            );

            $insertStmt->bindValue(
                ':payment_date',
                $paymentDate
            );

            $insertStmt->bindValue(
                ':payment_method',
                $paymentMethod
            );

            $insertStmt->bindValue(
                ':transaction_id',
                $transactionId !== ''
                    ? $transactionId
                    : null,
                $transactionId !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $insertStmt->bindValue(
                ':remarks',
                $remarks !== ''
                    ? $remarks
                    : null,
                $remarks !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $insertStmt->bindValue(
                ':project_amount_snapshot',
                number_format(
                    $projectAmount,
                    2,
                    '.',
                    ''
                )
            );

            $insertStmt->bindValue(
                ':paid_before',
                number_format(
                    $paidBefore,
                    2,
                    '.',
                    ''
                )
            );

            $insertStmt->bindValue(
                ':paid_after',
                number_format(
                    $paidAfter,
                    2,
                    '.',
                    ''
                )
            );

            $insertStmt->bindValue(
                ':remaining_after',
                number_format(
                    $remainingAfter,
                    2,
                    '.',
                    ''
                )
            );


            if ($addedBy !== null) {

                $insertStmt->bindValue(
                    ':added_by',
                    $addedBy,
                    PDO::PARAM_INT
                );

            } else {

                $insertStmt->bindValue(
                    ':added_by',
                    null,
                    PDO::PARAM_NULL
                );
            }


            $insertStmt->execute();


            $paymentId =
                (int) $this->pdo->lastInsertId();


            /*
            |------------------------------------------------------
            | COMMIT
            |------------------------------------------------------
            */

            $this->pdo->commit();


            /*
            |------------------------------------------------------
            | RETURN CREATED PAYMENT
            |------------------------------------------------------
            */

            paymentJsonResponse(
                true,
                'Payment created successfully.',
                [
                    'payment_id' =>
                        $paymentId,

                    'payment_code' =>
                        $paymentCode,

                    'project_amount' =>
                        number_format(
                            $projectAmount,
                            2,
                            '.',
                            ''
                        ),

                    'paid_before' =>
                        number_format(
                            $paidBefore,
                            2,
                            '.',
                            ''
                        ),

                    'payment_amount' =>
                        number_format(
                            $amount,
                            2,
                            '.',
                            ''
                        ),

                    'paid_after' =>
                        number_format(
                            $paidAfter,
                            2,
                            '.',
                            ''
                        ),

                    'remaining_after' =>
                        number_format(
                            $remainingAfter,
                            2,
                            '.',
                            ''
                        )
                ],
                201
            );

        } catch (
            Throwable $e
        ) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            error_log(
                'PaymentController@store: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to create payment.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PUT /api/payments/{id}
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Financial fields are immutable.
    |
    | Cannot change:
    |
    | client_id
    | project_id
    | amount
    | payment_date
    | payment_method
    | project_amount_snapshot
    | paid_before
    | paid_after
    | remaining_after
    |
    | Can change:
    |
    | purpose
    | transaction_id
    | remarks
    |
    */

    public function update(
        int $paymentId
    ): never {

        $paymentId =
            paymentValidateId(
                $paymentId,
                'Payment ID'
            );


        $request =
            paymentRequestBody();


        paymentVerifyCsrf(
            $request
        );


        /*
        |----------------------------------------------------------
        | Allowed editable fields
        |----------------------------------------------------------
        */

        $purposeProvided =
            array_key_exists(
                'purpose',
                $request
            );

        $transactionProvided =
            array_key_exists(
                'transaction_id',
                $request
            );

        $remarksProvided =
            array_key_exists(
                'remarks',
                $request
            );


        if (
            !$purposeProvided &&
            !$transactionProvided &&
            !$remarksProvided
        ) {

            paymentJsonResponse(
                false,
                'No editable payment fields were provided.',
                [],
                422
            );
        }


        /*
        |----------------------------------------------------------
        | Validate purpose
        |----------------------------------------------------------
        */

        $purpose = null;

        if ($purposeProvided) {

            $purpose =
                trim(
                    (string) $request['purpose']
                );

            if (
                mb_strlen($purpose) > 255
            ) {
                paymentJsonResponse(
                    false,
                    'Payment purpose cannot exceed 255 characters.',
                    [],
                    422
                );
            }
        }


        /*
        |----------------------------------------------------------
        | Validate transaction ID
        |----------------------------------------------------------
        */

        $transactionId = null;

        if ($transactionProvided) {

            $transactionId =
                trim(
                    (string) $request['transaction_id']
                );

            if (
                mb_strlen($transactionId) > 150
            ) {
                paymentJsonResponse(
                    false,
                    'Transaction ID cannot exceed 150 characters.',
                    [],
                    422
                );
            }
        }


        /*
        |----------------------------------------------------------
        | Validate remarks
        |----------------------------------------------------------
        */

        $remarks = null;

        if ($remarksProvided) {

            $remarks =
                trim(
                    (string) $request['remarks']
                );

            if (
                mb_strlen($remarks) > 5000
            ) {
                paymentJsonResponse(
                    false,
                    'Remarks cannot exceed 5000 characters.',
                    [],
                    422
                );
            }
        }


        try {

            /*
            |------------------------------------------------------
            | Check payment exists
            |------------------------------------------------------
            */

            $checkStmt =
                $this->pdo->prepare(
                    "
                    SELECT id
                    FROM client_payments
                    WHERE id = ?
                    LIMIT 1
                    "
                );

            $checkStmt->execute(
                [
                    $paymentId
                ]
            );


            if (!$checkStmt->fetchColumn()) {

                paymentJsonResponse(
                    false,
                    'Payment not found.',
                    [],
                    404
                );
            }


            /*
            |------------------------------------------------------
            | Build UPDATE dynamically
            |------------------------------------------------------
            */

            $sets = [];

            $params = [
                ':id' => $paymentId
            ];


            if ($purposeProvided) {

                $sets[] =
                    'purpose = :purpose';

                $params[':purpose'] =
                    $purpose !== ''
                        ? $purpose
                        : null;
            }


            if ($transactionProvided) {

                $sets[] =
                    'transaction_id = :transaction_id';

                $params[':transaction_id'] =
                    $transactionId !== ''
                        ? $transactionId
                        : null;
            }


            if ($remarksProvided) {

                $sets[] =
                    'remarks = :remarks';

                $params[':remarks'] =
                    $remarks !== ''
                        ? $remarks
                        : null;
            }


            $sql = "
                UPDATE client_payments
                SET
                    " .
                    implode(
                        ', ',
                        $sets
                    ) .
                "
                WHERE id = :id
            ";


            $stmt =
                $this->pdo->prepare(
                    $sql
                );

            $stmt->execute(
                $params
            );


            paymentJsonResponse(
                true,
                'Payment updated successfully.',
                [
                    'payment_id' =>
                        $paymentId
                ]
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@update: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to update payment.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE /api/payments/{id}
    |--------------------------------------------------------------------------
    |
    | Financial payment records are not deleted.
    |
    | This protects:
    |
    | receipts
    | payment history
    | balance snapshots
    | accounting history
    |
    */

    public function destroy(
        int $paymentId
    ): never {

        $paymentId =
            paymentValidateId(
                $paymentId,
                'Payment ID'
            );


        $request =
            paymentRequestBody();


        paymentVerifyCsrf(
            $request
        );


        try {

            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT
                        id,
                        payment_code,
                        amount
                    FROM client_payments
                    WHERE id = ?
                    LIMIT 1
                    "
                );

            $stmt->execute(
                [
                    $paymentId
                ]
            );


            $payment =
                $stmt->fetch();


            if (!$payment) {

                paymentJsonResponse(
                    false,
                    'Payment not found.',
                    [],
                    404
                );
            }


            /*
            |------------------------------------------------------
            | DO NOT DELETE FINANCIAL RECORD
            |------------------------------------------------------
            */

            paymentJsonResponse(
                false,
                'Payment records cannot be deleted because they are financial records.',
                [
                    'payment_id' =>
                        $paymentId,

                    'payment_code' =>
                        $payment['payment_code']
                ],
                409
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@destroy: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to process payment deletion request.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/clients/{id}/payments
    |--------------------------------------------------------------------------
    |
    | Complete payment history for one client.
    |
    */

    public function clientPayments(
        int $clientId
    ): never {

        $clientId =
            paymentValidateId(
                $clientId,
                'Client ID'
            );


        try {

            /*
            |------------------------------------------------------
            | Client
            |------------------------------------------------------
            */

            $clientStmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        id,
                        client_code,
                        client_name,
                        company_name,
                        mobile,
                        whatsapp,
                        email,

                        address,
                        city,
                        state,
                        pincode,

                        billing_name,
                        gst_number,
                        pan_number,
                        billing_address

                    FROM clients

                    WHERE id = ?

                    LIMIT 1
                    "
                );

            $clientStmt->execute(
                [
                    $clientId
                ]
            );

            $client =
                $clientStmt->fetch();


            if (!$client) {

                paymentJsonResponse(
                    false,
                    'Client not found.',
                    [],
                    404
                );
            }


            /*
            |------------------------------------------------------
            | Payments
            |------------------------------------------------------
            */

            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        cp.id,
                        cp.payment_code,

                        cp.client_id,
                        cp.project_id,

                        cp.purpose,
                        cp.amount,

                        cp.payment_date,
                        cp.payment_method,

                        cp.transaction_id,
                        cp.remarks,

                        cp.project_amount_snapshot,

                        cp.paid_before,
                        cp.paid_after,
                        cp.remaining_after,

                        cp.added_by,

                        cp.created_at,
                        cp.updated_at,

                        p.project_code,
                        p.project_name,
                        p.project_type,

                        s.name AS added_by_name

                    FROM client_payments cp

                    INNER JOIN projects p
                        ON p.id = cp.project_id

                    LEFT JOIN staff s
                        ON s.id = cp.added_by

                    WHERE cp.client_id = ?

                    ORDER BY
                        cp.payment_date ASC,
                        cp.id ASC
                    "
                );

            $stmt->execute(
                [
                    $clientId
                ]
            );


            $payments =
                $stmt->fetchAll();


            /*
            |------------------------------------------------------
            | TOTAL PAID
            |------------------------------------------------------
            */

            $totalPaid = 0.00;

            foreach (
                $payments
                as $payment
            ) {
                $totalPaid +=
                    (float) $payment['amount'];
            }


            $totalPaid =
                round(
                    $totalPaid,
                    2
                );


            paymentJsonResponse(
                true,
                'Client payment history fetched successfully.',
                [
                    'client' =>
                        $client,

                    'payments' =>
                        $payments,

                    'summary' => [
                        'payment_count' =>
                            count($payments),

                        'total_paid' =>
                            number_format(
                                $totalPaid,
                                2,
                                '.',
                                ''
                            )
                    ]
                ]
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@clientPayments: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to fetch client payment history.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/projects/{id}/payments
    |--------------------------------------------------------------------------
    |
    | Complete payment history for one project.
    |
    */

    public function projectPayments(
        int $projectId
    ): never {

        $projectId =
            paymentValidateId(
                $projectId,
                'Project ID'
            );


        try {

            /*
            |------------------------------------------------------
            | Project
            |------------------------------------------------------
            */

            $projectStmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        p.id,
                        p.project_code,
                        p.client_id,

                        p.project_name,
                        p.project_type,

                        p.description,

                        p.start_date,
                        p.expected_completion,

                        p.budget,

                        p.status,
                        p.progress_percentage,

                        p.live_website_link,

                        c.client_code,
                        c.client_name,
                        c.company_name,
                        c.mobile,
                        c.whatsapp,
                        c.email

                    FROM projects p

                    INNER JOIN clients c
                        ON c.id = p.client_id

                    WHERE p.id = ?

                    LIMIT 1
                    "
                );

            $projectStmt->execute(
                [
                    $projectId
                ]
            );

            $project =
                $projectStmt->fetch();


            if (!$project) {

                paymentJsonResponse(
                    false,
                    'Project not found.',
                    [],
                    404
                );
            }


            /*
            |------------------------------------------------------
            | Payments
            |------------------------------------------------------
            */

            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        cp.id,
                        cp.payment_code,

                        cp.client_id,
                        cp.project_id,

                        cp.purpose,
                        cp.amount,

                        cp.payment_date,
                        cp.payment_method,

                        cp.transaction_id,
                        cp.remarks,

                        cp.project_amount_snapshot,

                        cp.paid_before,
                        cp.paid_after,
                        cp.remaining_after,

                        cp.added_by,

                        cp.created_at,
                        cp.updated_at,

                        s.name AS added_by_name

                    FROM client_payments cp

                    LEFT JOIN staff s
                        ON s.id = cp.added_by

                    WHERE cp.project_id = ?

                    ORDER BY
                        cp.payment_date ASC,
                        cp.id ASC
                    "
                );

            $stmt->execute(
                [
                    $projectId
                ]
            );


            $payments =
                $stmt->fetchAll();


            /*
            |------------------------------------------------------
            | Current summary
            |------------------------------------------------------
            */

            $totalPaid = 0.00;

            foreach (
                $payments
                as $payment
            ) {
                $totalPaid +=
                    (float) $payment['amount'];
            }


            $totalPaid =
                round(
                    $totalPaid,
                    2
                );


            $projectAmount =
                round(
                    (float) $project['budget'],
                    2
                );


            $currentRemaining =
                round(
                    $projectAmount -
                    $totalPaid,
                    2
                );


            paymentJsonResponse(
                true,
                'Project payment history fetched successfully.',
                [
                    'project' =>
                        $project,

                    'payments' =>
                        $payments,

                    'summary' => [

                        'project_amount' =>
                            number_format(
                                $projectAmount,
                                2,
                                '.',
                                ''
                            ),

                        'total_paid' =>
                            number_format(
                                $totalPaid,
                                2,
                                '.',
                                ''
                            ),

                        'remaining_amount' =>
                            number_format(
                                max(
                                    0,
                                    $currentRemaining
                                ),
                                2,
                                '.',
                                ''
                            ),

                        'payment_count' =>
                            count($payments)
                    ]
                ]
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@projectPayments: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to fetch project payment history.',
                [],
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/clients/{id}/payment-projects
    |--------------------------------------------------------------------------
    |
    | Projects belonging to a client.
    |
    | Used by the payment form after selecting a client.
    |
    */

    public function clientProjects(
        int $clientId
    ): never {

        $clientId =
            paymentValidateId(
                $clientId,
                'Client ID'
            );


        try {

            /*
            |------------------------------------------------------
            | Verify client
            |------------------------------------------------------
            */

            $clientStmt =
                $this->pdo->prepare(
                    "
                    SELECT id
                    FROM clients
                    WHERE id = ?
                    LIMIT 1
                    "
                );

            $clientStmt->execute(
                [
                    $clientId
                ]
            );


            if (!$clientStmt->fetchColumn()) {

                paymentJsonResponse(
                    false,
                    'Client not found.',
                    [],
                    404
                );
            }


            /*
            |------------------------------------------------------
            | Projects
            |------------------------------------------------------
            */

            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        p.id,
                        p.project_code,
                        p.project_name,
                        p.project_type,

                        p.budget,

                        p.status,
                        p.progress_percentage,

                        p.start_date,
                        p.expected_completion,

                        COALESCE(
                            SUM(cp.amount),
                            0
                        ) AS total_paid,

                        (
                            p.budget -
                            COALESCE(
                                SUM(cp.amount),
                                0
                            )
                        ) AS remaining_amount

                    FROM projects p

                    LEFT JOIN client_payments cp
                        ON cp.project_id = p.id

                    WHERE p.client_id = ?

                    GROUP BY

                        p.id,
                        p.project_code,
                        p.project_name,
                        p.project_type,
                        p.budget,
                        p.status,
                        p.progress_percentage,
                        p.start_date,
                        p.expected_completion

                    ORDER BY
                        p.created_at DESC
                    "
                );

            $stmt->execute(
                [
                    $clientId
                ]
            );


            $projects =
                $stmt->fetchAll();


            /*
            |------------------------------------------------------
            | Normalize financial values
            |------------------------------------------------------
            */

            foreach (
                $projects
                as &$project
            ) {

                $budget =
                    (float) $project['budget'];

                $paid =
                    (float) $project['total_paid'];

                $remaining =
                    max(
                        0,
                        $budget - $paid
                    );

                $project['budget'] =
                    number_format(
                        $budget,
                        2,
                        '.',
                        ''
                    );

                $project['total_paid'] =
                    number_format(
                        $paid,
                        2,
                        '.',
                        ''
                    );

                $project['remaining_amount'] =
                    number_format(
                        $remaining,
                        2,
                        '.',
                        ''
                    );
            }

            unset($project);


            paymentJsonResponse(
                true,
                'Client projects fetched successfully.',
                [
                    'projects' =>
                        $projects
                ]
            );

        } catch (
            Throwable $e
        ) {

            error_log(
                'PaymentController@clientProjects: ' .
                $e->getMessage()
            );

            paymentJsonResponse(
                false,
                'Unable to fetch client projects.',
                [],
                500
            );
        }
    }
}