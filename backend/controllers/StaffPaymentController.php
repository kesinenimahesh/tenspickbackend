<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * STAFF PAYMENT CONTROLLER
 * ============================================================
 *
 * Handles:
 *
 * GET    /api/staff-payments
 * GET    /api/staff-payments/{id}
 * POST   /api/staff-payments
 * PUT    /api/staff-payments/{id}
 * DELETE /api/staff-payments/{id}
 *
 * GET    /api/staff/{id}/payments
 * GET    /api/staff/{id}/payment-summary
 *
 * ============================================================
 *
 * STAFF PAYMENT FIELDS
 * ------------------------------------------------------------
 *
 * Payment ID
 * Staff
 * Payment Type
 * Amount
 * Payment Date
 * Month / Period
 * Payment Method
 * Remarks
 * Added By
 *
 * ============================================================
 *
 * PAYMENT TYPES
 * ------------------------------------------------------------
 *
 * salary
 * advance
 * bonus
 * incentive
 * reimbursement
 * other
 *
 * ============================================================
 *
 * PAYMENT METHODS
 * ------------------------------------------------------------
 *
 * cash
 * upi
 * bank_transfer
 * card
 * cheque
 * other
 *
 * ============================================================
 *
 * BUSINESS RULES
 * ------------------------------------------------------------
 *
 * 1. New payments only for active staff.
 * 2. Amount must be greater than zero.
 * 3. Payment date is required.
 * 4. Month / Period is required.
 * 5. Payment type is required.
 * 6. Payment method is required.
 * 7. Financial fields are immutable.
 * 8. Only remarks can be edited.
 * 9. Financial payment records cannot be deleted.
 * 10. Added By comes from authenticated admin session.
 *
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/

if (!defined('STAFF_PAYMENT_TYPES')) {
    define(
        'STAFF_PAYMENT_TYPES',
        [
            'salary',
            'advance',
            'bonus',
            'incentive',
            'reimbursement',
            'other'
        ]
    );
}

if (!defined('STAFF_PAYMENT_METHODS')) {
    define(
        'STAFF_PAYMENT_METHODS',
        [
            'cash',
            'upi',
            'bank_transfer',
            'card',
            'cheque',
            'other'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| CONTROLLER
|--------------------------------------------------------------------------
*/

class StaffPaymentController
{
    private PDO $pdo;


    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        /*
         * Use existing Tenspick admin authentication.
         */
        if (function_exists('paymentRequireAdmin')) {

            paymentRequireAdmin();

        } else {

            $this->requireAdminFallback();
        }


        /*
         * Use existing Tenspick PDO connection.
         */
        if (function_exists('paymentGetPDO')) {

            $this->pdo = paymentGetPDO();

        } else {

            $this->pdo = $this->getPDOFallback();
        }


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
    | GET /api/staff-payments
    |--------------------------------------------------------------------------
    */

    public function index(): never
    {
        try {

            $search = trim(
                (string) (
                    $_GET['search'] ?? ''
                )
            );


            /*
             * Staff filter.
             */
            $staffId = null;

            if (
                isset($_GET['staff_id']) &&
                $_GET['staff_id'] !== ''
            ) {

                $staffId = $this->validatePositiveInt(
                    $_GET['staff_id'],
                    'Staff ID'
                );
            }


            /*
             * Payment type.
             */
            $paymentType = strtolower(
                trim(
                    (string) (
                        $_GET['payment_type'] ?? ''
                    )
                )
            );

            if (
                $paymentType !== '' &&
                !in_array(
                    $paymentType,
                    STAFF_PAYMENT_TYPES,
                    true
                )
            ) {

                $this->jsonError(
                    'Invalid payment type.',
                    422
                );
            }


            /*
             * Payment method.
             */
            $paymentMethod = strtolower(
                trim(
                    (string) (
                        $_GET['payment_method'] ?? ''
                    )
                )
            );

            if (
                $paymentMethod !== '' &&
                !in_array(
                    $paymentMethod,
                    STAFF_PAYMENT_METHODS,
                    true
                )
            ) {

                $this->jsonError(
                    'Invalid payment method.',
                    422
                );
            }


            /*
             * Payment period.
             */
            $paymentPeriod = trim(
                (string) (
                    $_GET['payment_period']
                    ?? $_GET['month_period']
                    ?? $_GET['period']
                    ?? ''
                )
            );


            /*
             * Date filters.
             */
            $fromDate = trim(
                (string) (
                    $_GET['from_date']
                    ?? $_GET['date_from']
                    ?? ''
                )
            );

            $toDate = trim(
                (string) (
                    $_GET['to_date']
                    ?? $_GET['date_to']
                    ?? ''
                )
            );


            if ($fromDate !== '') {

                $this->validateDate(
                    $fromDate,
                    'From date'
                );
            }

            if ($toDate !== '') {

                $this->validateDate(
                    $toDate,
                    'To date'
                );
            }

            if (
                $fromDate !== '' &&
                $toDate !== '' &&
                $fromDate > $toDate
            ) {

                $this->jsonError(
                    'From date cannot be later than to date.',
                    422
                );
            }


            /*
             * Pagination.
             */
            $page = max(
                1,
                (int) (
                    $_GET['page'] ?? 1
                )
            );

            $limit = (int) (
                $_GET['limit']
                ?? $_GET['per_page']
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
             * WHERE.
             */
            $where = [];
            $params = [];


            if ($search !== '') {

                $where[] = "
                    (
                        sp.payment_code LIKE :search
                        OR s.staff_code LIKE :search
                        OR s.name LIKE :search
                        OR s.email LIKE :search
                        OR s.phone LIKE :search
                        OR sp.remarks LIKE :search
                    )
                ";

                $params[':search'] =
                    '%' . $search . '%';
            }


            if ($staffId !== null) {

                $where[] =
                    'sp.staff_id = :staff_id';

                $params[':staff_id'] =
                    $staffId;
            }


            if ($paymentType !== '') {

                $where[] =
                    'sp.payment_type = :payment_type';

                $params[':payment_type'] =
                    $paymentType;
            }


            if ($paymentMethod !== '') {

                $where[] =
                    'sp.payment_method = :payment_method';

                $params[':payment_method'] =
                    $paymentMethod;
            }


            if ($paymentPeriod !== '') {

                $where[] =
                    'sp.payment_period LIKE :payment_period';

                $params[':payment_period'] =
                    '%' . $paymentPeriod . '%';
            }


            if ($fromDate !== '') {

                $where[] =
                    'sp.payment_date >= :from_date';

                $params[':from_date'] =
                    $fromDate;
            }


            if ($toDate !== '') {

                $where[] =
                    'sp.payment_date <= :to_date';

                $params[':to_date'] =
                    $toDate;
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
             * Total.
             */
            $countSql = "
                SELECT COUNT(*)
                FROM staff_payments sp
                INNER JOIN staff s
                    ON s.id = sp.staff_id
                {$whereSql}
            ";

            $countStmt =
                $this->pdo->prepare(
                    $countSql
                );

            foreach (
                $params
                as $key => $value
            ) {

                $countStmt->bindValue(
                    $key,
                    $value
                );
            }

            $countStmt->execute();

            $total =
                (int) $countStmt->fetchColumn();


            /*
             * Payments.
             */
            $sql = "
                SELECT

                    sp.id,
                    sp.payment_code,
                    sp.staff_id,
                    sp.payment_type,
                    sp.amount,
                    sp.payment_date,
                    sp.payment_period,
                    sp.payment_method,
                    sp.remarks,
                    sp.added_by,
                    sp.created_at,
                    sp.updated_at,

                    s.staff_code,
                    s.name AS staff_name,
                    s.email AS staff_email,
                    s.phone AS staff_phone,
                    s.department AS staff_department,
                    s.designation AS staff_designation,
                    s.status AS staff_status,
                    s.profile_image AS staff_profile_image,

                    added.name AS added_by_name,
                    added.staff_code AS added_by_code

                FROM staff_payments sp

                INNER JOIN staff s
                    ON s.id = sp.staff_id

                LEFT JOIN staff added
                    ON added.id = sp.added_by

                {$whereSql}

                ORDER BY
                    sp.payment_date DESC,
                    sp.id DESC

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


            $rows =
                $stmt->fetchAll();


            $payments = [];

            foreach (
                $rows
                as $row
            ) {

                $payments[] =
                    $this->formatPayment(
                        $row
                    );
            }


            /*
             * Filtered total amount.
             */
            $sumSql = "
                SELECT
                    COALESCE(
                        SUM(sp.amount),
                        0
                    )
                FROM staff_payments sp
                INNER JOIN staff s
                    ON s.id = sp.staff_id
                {$whereSql}
            ";

            $sumStmt =
                $this->pdo->prepare(
                    $sumSql
                );

            foreach (
                $params
                as $key => $value
            ) {

                $sumStmt->bindValue(
                    $key,
                    $value
                );
            }

            $sumStmt->execute();

            $totalAmount =
                (float) $sumStmt->fetchColumn();


            /*
             * Current month.
             */
            $monthStmt =
                $this->pdo->query(
                    "
                    SELECT
                        COALESCE(
                            SUM(amount),
                            0
                        )
                    FROM staff_payments
                    WHERE
                        YEAR(payment_date) =
                        YEAR(CURDATE())
                    AND
                        MONTH(payment_date) =
                        MONTH(CURDATE())
                    "
                );

            $thisMonth =
                (float) $monthStmt->fetchColumn();


            /*
             * Salary paid.
             */
            $salaryStmt =
                $this->pdo->query(
                    "
                    SELECT
                        COALESCE(
                            SUM(amount),
                            0
                        )
                    FROM staff_payments
                    WHERE payment_type = 'salary'
                    "
                );

            $salaryPaid =
                (float) $salaryStmt->fetchColumn();


            $totalPages =
                $total > 0
                    ? (int) ceil(
                        $total / $limit
                    )
                    : 0;


            $this->jsonResponse(
                true,
                'Staff payments fetched successfully.',
                [
                    'payments' =>
                        $payments,

                    'data' =>
                        $payments,

                    'pagination' => [
                        'page' =>
                            $page,

                        'limit' =>
                            $limit,

                        'per_page' =>
                            $limit,

                        'total' =>
                            $total,

                        'total_pages' =>
                            $totalPages
                    ],

                    'summary' => [
                        'total_payments' =>
                            $total,

                        'total_paid' =>
                            $this->formatMoney(
                                $totalAmount
                            ),

                        'this_month' =>
                            $this->formatMoney(
                                $thisMonth
                            ),

                        'salary_paid' =>
                            $this->formatMoney(
                                $salaryPaid
                            )
                    ],

                    'payment_types' =>
                        STAFF_PAYMENT_TYPES,

                    'payment_methods' =>
                        STAFF_PAYMENT_METHODS
                ]
            );

        } catch (Throwable $e) {

            $this->logError(
                'index',
                $e
            );

            $this->jsonError(
                'Unable to fetch staff payments.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/staff-payments/{id}
    |--------------------------------------------------------------------------
    */

    public function show(
        int $paymentId
    ): never
    {
        $paymentId =
            $this->validatePositiveInt(
                $paymentId,
                'Payment ID'
            );

        try {

            $payment =
                $this->findPayment(
                    $paymentId
                );

            if (!$payment) {

                $this->jsonError(
                    'Staff payment not found.',
                    404
                );
            }

            $formatted =
                $this->formatPayment(
                    $payment
                );

            $this->jsonResponse(
                true,
                'Staff payment fetched successfully.',
                [
                    'payment' =>
                        $formatted,

                    'data' =>
                        $formatted
                ]
            );

        } catch (Throwable $e) {

            $this->logError(
                'show',
                $e
            );

            $this->jsonError(
                'Unable to fetch staff payment.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | POST /api/staff-payments
    |--------------------------------------------------------------------------
    */

    public function store(): never
    {
        $request =
            $this->getRequestBody();


        /*
         * IMPORTANT:
         * CSRF is checked before any database mutation.
         */
        $this->verifyCsrf(
            $request
        );


        /*
         * Staff ID.
         */
        $staffId =
            $this->validatePositiveInt(
                $request['staff_id'] ?? null,
                'Staff ID'
            );


        /*
         * Payment type.
         */
        $paymentType =
            strtolower(
                trim(
                    (string) (
                        $request['payment_type']
                        ?? ''
                    )
                )
            );


        if (
            !in_array(
                $paymentType,
                STAFF_PAYMENT_TYPES,
                true
            )
        ) {

            $this->jsonError(
                'Invalid payment type.',
                422
            );
        }


        /*
         * Amount.
         */
        $amount =
            $this->validateAmount(
                $request['amount'] ?? null
            );


        /*
         * Payment date.
         */
        $paymentDate =
            $this->validateDate(
                $request['payment_date'] ?? null,
                'Payment date'
            );


        /*
         * Payment period.
         */
        $paymentPeriod =
            trim(
                (string) (
                    $request['payment_period']
                    ?? $request['month_period']
                    ?? $request['period']
                    ?? ''
                )
            );


        if ($paymentPeriod === '') {

            $this->jsonError(
                'Month / Period is required.',
                422
            );
        }


        if (
            mb_strlen(
                $paymentPeriod
            ) > 50
        ) {

            $this->jsonError(
                'Month / Period cannot exceed 50 characters.',
                422
            );
        }


        /*
         * Payment method.
         */
        $paymentMethod =
            strtolower(
                trim(
                    (string) (
                        $request['payment_method']
                        ?? ''
                    )
                )
            );


        if (
            !in_array(
                $paymentMethod,
                STAFF_PAYMENT_METHODS,
                true
            )
        ) {

            $this->jsonError(
                'Invalid payment method.',
                422
            );
        }


        /*
         * Remarks.
         */
        $remarks =
            trim(
                (string) (
                    $request['remarks']
                    ?? ''
                )
            );


        if (
            mb_strlen(
                $remarks
            ) > 5000
        ) {

            $this->jsonError(
                'Remarks cannot exceed 5000 characters.',
                422
            );
        }


        /*
         * Staff validation.
         */
        $staff =
            $this->findStaff(
                $staffId
            );


        if (!$staff) {

            $this->jsonError(
                'Staff member not found.',
                404
            );
        }


        /*
         * Only active staff can receive new payments.
         */
        if (
            strtolower(
                (string) (
                    $staff['status'] ?? ''
                )
            ) !== 'active'
        ) {

            $this->jsonError(
                'New payments can only be added for active staff.',
                409
            );
        }


        /*
         * Get authenticated admin.
         */
        $addedBy =
            $this->getCurrentAdminId();


        /*
         * IMPORTANT:
         *
         * staff_payments.added_by references staff.id.
         *
         * Therefore we cannot blindly insert an admin ID
         * unless that ID actually exists in the staff table.
         *
         * If the authenticated admin is not a staff record,
         * store NULL rather than causing a foreign-key 500.
         */
        $addedBy =
            $this->resolveAddedByStaffId(
                $addedBy
            );


        try {

            $this->pdo->beginTransaction();


            /*
             * Generate unique payment ID.
             */
            $paymentCode =
                $this->generatePaymentCode(
                    $paymentDate
                );


            /*
             * Insert.
             */
            $stmt =
                $this->pdo->prepare(
                    "
                    INSERT INTO staff_payments
                    (
                        payment_code,
                        staff_id,
                        payment_type,
                        amount,
                        payment_date,
                        payment_period,
                        payment_method,
                        remarks,
                        added_by
                    )
                    VALUES
                    (
                        :payment_code,
                        :staff_id,
                        :payment_type,
                        :amount,
                        :payment_date,
                        :payment_period,
                        :payment_method,
                        :remarks,
                        :added_by
                    )
                    "
                );


            $stmt->bindValue(
                ':payment_code',
                $paymentCode,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':staff_id',
                $staffId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':payment_type',
                $paymentType,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':amount',
                number_format(
                    $amount,
                    2,
                    '.',
                    ''
                ),
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':payment_date',
                $paymentDate,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':payment_period',
                $paymentPeriod,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ':payment_method',
                $paymentMethod,
                PDO::PARAM_STR
            );


            if ($remarks !== '') {

                $stmt->bindValue(
                    ':remarks',
                    $remarks,
                    PDO::PARAM_STR
                );

            } else {

                $stmt->bindValue(
                    ':remarks',
                    null,
                    PDO::PARAM_NULL
                );
            }


            if ($addedBy !== null) {

                $stmt->bindValue(
                    ':added_by',
                    $addedBy,
                    PDO::PARAM_INT
                );

            } else {

                $stmt->bindValue(
                    ':added_by',
                    null,
                    PDO::PARAM_NULL
                );
            }


            $stmt->execute();


            $paymentId =
                (int) $this->pdo->lastInsertId();


            $this->pdo->commit();


            /*
             * Fetch complete created payment.
             */
            $payment =
                $this->findPayment(
                    $paymentId
                );


            $formatted =
                $payment
                    ? $this->formatPayment(
                        $payment
                    )
                    : null;


            $this->jsonResponse(
                true,
                'Staff payment created successfully.',
                [
                    'payment_id' =>
                        $paymentId,

                    'payment_code' =>
                        $paymentCode,

                    'payment' =>
                        $formatted,

                    'data' =>
                        $formatted
                ],
                201
            );

        } catch (PDOException $e) {

            if (
                $this->pdo->inTransaction()
            ) {

                $this->pdo->rollBack();
            }


            $this->logError(
                'store PDO',
                $e
            );


            /*
             * Duplicate payment code.
             */
            if (
                (string) $e->getCode()
                === '23000'
            ) {

                $this->jsonError(
                    'Unable to create the payment because the payment ID already exists. Please try again.',
                    409
                );
            }


            $this->jsonError(
                'Unable to create staff payment.',
                500
            );

        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {

                $this->pdo->rollBack();
            }


            $this->logError(
                'store',
                $e
            );


            $this->jsonError(
                'Unable to create staff payment.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PUT /api/staff-payments/{id}
    |--------------------------------------------------------------------------
    */

    public function update(
        int $paymentId
    ): never
    {
        $paymentId =
            $this->validatePositiveInt(
                $paymentId,
                'Payment ID'
            );


        $request =
            $this->getRequestBody();


        $this->verifyCsrf(
            $request
        );


        $payment =
            $this->findPayment(
                $paymentId
            );


        if (!$payment) {

            $this->jsonError(
                'Staff payment not found.',
                404
            );
        }


        /*
         * Financial fields cannot be modified.
         */
        $immutableFields = [
            'staff_id',
            'payment_type',
            'amount',
            'payment_date',
            'payment_period',
            'month_period',
            'period',
            'payment_method',
            'payment_code',
            'added_by'
        ];


        foreach (
            $immutableFields
            as $field
        ) {

            if (
                array_key_exists(
                    $field,
                    $request
                )
            ) {

                $this->jsonError(
                    'Payment financial details cannot be changed after creation. Only remarks can be edited.',
                    409,
                    [
                        'immutable_field' =>
                            $field
                    ]
                );
            }
        }


        if (
            !array_key_exists(
                'remarks',
                $request
            )
        ) {

            $this->jsonError(
                'Only remarks can be updated for an existing payment.',
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
            mb_strlen(
                $remarks
            ) > 5000
        ) {

            $this->jsonError(
                'Remarks cannot exceed 5000 characters.',
                422
            );
        }


        try {

            $stmt =
                $this->pdo->prepare(
                    "
                    UPDATE staff_payments

                    SET
                        remarks = :remarks

                    WHERE id = :id

                    LIMIT 1
                    "
                );


            if ($remarks !== '') {

                $stmt->bindValue(
                    ':remarks',
                    $remarks,
                    PDO::PARAM_STR
                );

            } else {

                $stmt->bindValue(
                    ':remarks',
                    null,
                    PDO::PARAM_NULL
                );
            }


            $stmt->bindValue(
                ':id',
                $paymentId,
                PDO::PARAM_INT
            );


            $stmt->execute();


            $updated =
                $this->findPayment(
                    $paymentId
                );


            $formatted =
                $updated
                    ? $this->formatPayment(
                        $updated
                    )
                    : null;


            $this->jsonResponse(
                true,
                'Staff payment updated successfully.',
                [
                    'payment' =>
                        $formatted,

                    'data' =>
                        $formatted
                ]
            );

        } catch (Throwable $e) {

            $this->logError(
                'update',
                $e
            );


            $this->jsonError(
                'Unable to update staff payment.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE /api/staff-payments/{id}
    |--------------------------------------------------------------------------
    |
    | Financial records are NEVER deleted.
    |
    */

    public function destroy(
        int $paymentId
    ): never
    {
        $paymentId =
            $this->validatePositiveInt(
                $paymentId,
                'Payment ID'
            );


        $request =
            $this->getRequestBody();


        $this->verifyCsrf(
            $request
        );


        try {

            $payment =
                $this->findPayment(
                    $paymentId
                );


            if (!$payment) {

                $this->jsonError(
                    'Staff payment not found.',
                    404
                );
            }


            $this->jsonError(
                'Staff payment records cannot be deleted because they are financial records.',
                409,
                [
                    'payment_id' =>
                        $paymentId,

                    'payment_code' =>
                        $payment['payment_code']
                            ?? null
                ]
            );

        } catch (Throwable $e) {

            $this->logError(
                'destroy',
                $e
            );


            $this->jsonError(
                'Unable to process staff payment deletion request.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/{id}/payments
    |--------------------------------------------------------------------------
    */

    public function staffPayments(
        int $staffId
    ): never
    {
        $staffId =
            $this->validatePositiveInt(
                $staffId,
                'Staff ID'
            );


        try {

            $staff =
                $this->findStaff(
                    $staffId
                );


            if (!$staff) {

                $this->jsonError(
                    'Staff member not found.',
                    404
                );
            }


            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT

                        sp.id,
                        sp.payment_code,
                        sp.staff_id,
                        sp.payment_type,
                        sp.amount,
                        sp.payment_date,
                        sp.payment_period,
                        sp.payment_method,
                        sp.remarks,
                        sp.added_by,
                        sp.created_at,
                        sp.updated_at,

                        s.staff_code,
                        s.name AS staff_name,
                        s.email AS staff_email,
                        s.phone AS staff_phone,
                        s.department AS staff_department,
                        s.designation AS staff_designation,
                        s.status AS staff_status,
                        s.profile_image AS staff_profile_image,

                        added.name AS added_by_name,
                        added.staff_code AS added_by_code

                    FROM staff_payments sp

                    INNER JOIN staff s
                        ON s.id = sp.staff_id

                    LEFT JOIN staff added
                        ON added.id = sp.added_by

                    WHERE sp.staff_id = :staff_id

                    ORDER BY
                        sp.payment_date DESC,
                        sp.id DESC
                    "
                );


            $stmt->bindValue(
                ':staff_id',
                $staffId,
                PDO::PARAM_INT
            );


            $stmt->execute();


            $rows =
                $stmt->fetchAll();


            $payments = [];

            foreach (
                $rows
                as $row
            ) {

                $payments[] =
                    $this->formatPayment(
                        $row
                    );
            }


            $summary =
                $this->getStaffSummary(
                    $staffId
                );


            $this->jsonResponse(
                true,
                'Staff payment history fetched successfully.',
                [
                    'staff' =>
                        $staff,

                    'payments' =>
                        $payments,

                    'data' =>
                        $payments,

                    'summary' =>
                        $summary
                ]
            );

        } catch (Throwable $e) {

            $this->logError(
                'staffPayments',
                $e
            );


            $this->jsonError(
                'Unable to fetch staff payment history.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/{id}/payment-summary
    |--------------------------------------------------------------------------
    */

    public function staffPaymentSummary(
        int $staffId
    ): never
    {
        $staffId =
            $this->validatePositiveInt(
                $staffId,
                'Staff ID'
            );


        try {

            $staff =
                $this->findStaff(
                    $staffId
                );


            if (!$staff) {

                $this->jsonError(
                    'Staff member not found.',
                    404
                );
            }


            $summary =
                $this->getStaffSummary(
                    $staffId
                );


            $this->jsonResponse(
                true,
                'Staff payment summary fetched successfully.',
                [
                    'staff' =>
                        $staff,

                    'summary' =>
                        $summary,

                    'data' =>
                        $summary
                ]
            );

        } catch (Throwable $e) {

            $this->logError(
                'staffPaymentSummary',
                $e
            );


            $this->jsonError(
                'Unable to fetch staff payment summary.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FIND PAYMENT
    |--------------------------------------------------------------------------
    */

    private function findPayment(
        int $paymentId
    ): ?array
    {
        $stmt =
            $this->pdo->prepare(
                "
                SELECT

                    sp.id,
                    sp.payment_code,
                    sp.staff_id,
                    sp.payment_type,
                    sp.amount,
                    sp.payment_date,
                    sp.payment_period,
                    sp.payment_method,
                    sp.remarks,
                    sp.added_by,
                    sp.created_at,
                    sp.updated_at,

                    s.staff_code,
                    s.name AS staff_name,
                    s.email AS staff_email,
                    s.phone AS staff_phone,
                    s.department AS staff_department,
                    s.designation AS staff_designation,
                    s.joining_date AS staff_joining_date,
                    s.status AS staff_status,
                    s.profile_image AS staff_profile_image,

                    added.name AS added_by_name,
                    added.staff_code AS added_by_code

                FROM staff_payments sp

                INNER JOIN staff s
                    ON s.id = sp.staff_id

                LEFT JOIN staff added
                    ON added.id = sp.added_by

                WHERE sp.id = :id

                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':id',
            $paymentId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $payment =
            $stmt->fetch();


        return $payment ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | FIND STAFF
    |--------------------------------------------------------------------------
    */

    private function findStaff(
        int $staffId
    ): ?array
    {
        $stmt =
            $this->pdo->prepare(
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
                    profile_image

                FROM staff

                WHERE id = :id

                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':id',
            $staffId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $staff =
            $stmt->fetch();


        return $staff ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | STAFF SUMMARY
    |--------------------------------------------------------------------------
    */

    private function getStaffSummary(
        int $staffId
    ): array
    {
        $stmt =
            $this->pdo->prepare(
                "
                SELECT

                    COUNT(*) AS total_payments,

                    COALESCE(
                        SUM(amount),
                        0
                    ) AS total_paid,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN
                                    YEAR(payment_date)
                                    =
                                    YEAR(CURDATE())
                                AND
                                    MONTH(payment_date)
                                    =
                                    MONTH(CURDATE())
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS this_month,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_type = 'salary'
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS salary_paid,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_type = 'advance'
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS advance_paid,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_type = 'bonus'
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS bonus_paid,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_type = 'incentive'
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS incentive_paid,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_type = 'reimbursement'
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS reimbursement_paid,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN payment_type = 'other'
                                THEN amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS other_paid

                FROM staff_payments

                WHERE staff_id = :staff_id
                "
            );


        $stmt->bindValue(
            ':staff_id',
            $staffId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $row =
            $stmt->fetch()
            ?: [];


        return [

            'total_payments' =>
                (int) (
                    $row['total_payments']
                    ?? 0
                ),

            'total_paid' =>
                $this->formatMoney(
                    $row['total_paid']
                    ?? 0
                ),

            'this_month' =>
                $this->formatMoney(
                    $row['this_month']
                    ?? 0
                ),

            'salary_paid' =>
                $this->formatMoney(
                    $row['salary_paid']
                    ?? 0
                ),

            'advance_paid' =>
                $this->formatMoney(
                    $row['advance_paid']
                    ?? 0
                ),

            'bonus_paid' =>
                $this->formatMoney(
                    $row['bonus_paid']
                    ?? 0
                ),

            'incentive_paid' =>
                $this->formatMoney(
                    $row['incentive_paid']
                    ?? 0
                ),

            'reimbursement_paid' =>
                $this->formatMoney(
                    $row['reimbursement_paid']
                    ?? 0
                ),

            'other_paid' =>
                $this->formatMoney(
                    $row['other_paid']
                    ?? 0
                )
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE PAYMENT CODE
    |--------------------------------------------------------------------------
    |
    | STP-YYYYMMDD-0001
    |
    */

    private function generatePaymentCode(
        string $paymentDate
    ): string
    {
        $timestamp =
            strtotime(
                $paymentDate
            );


        if ($timestamp === false) {

            throw new RuntimeException(
                'Invalid payment date.'
            );
        }


        $datePart =
            date(
                'Ymd',
                $timestamp
            );


        $prefix =
            'STP-' .
            $datePart .
            '-';


        $stmt =
            $this->pdo->prepare(
                "
                SELECT payment_code

                FROM staff_payments

                WHERE payment_code LIKE :prefix

                ORDER BY id DESC

                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':prefix',
            $prefix . '%',
            PDO::PARAM_STR
        );


        $stmt->execute();


        $lastCode =
            $stmt->fetchColumn();


        $nextNumber = 1;


        if (
            is_string($lastCode) &&
            preg_match(
                '/^STP-' .
                preg_quote(
                    $datePart,
                    '/'
                ) .
                '-(\d+)$/',
                $lastCode,
                $matches
            )
        ) {

            $nextNumber =
                ((int) $matches[1]) + 1;
        }


        for (
            $attempt = 0;
            $attempt < 1000;
            $attempt++
        ) {

            $code =
                sprintf(
                    'STP-%s-%04d',
                    $datePart,
                    $nextNumber
                );


            $check =
                $this->pdo->prepare(
                    "
                    SELECT id

                    FROM staff_payments

                    WHERE payment_code = :code

                    LIMIT 1
                    "
                );


            $check->bindValue(
                ':code',
                $code,
                PDO::PARAM_STR
            );


            $check->execute();


            if (
                !$check->fetchColumn()
            ) {

                return $code;
            }


            $nextNumber++;
        }


        throw new RuntimeException(
            'Unable to generate a unique staff payment ID.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST BODY
    |--------------------------------------------------------------------------
    */

    private function getRequestBody(): array
    {
        $raw =
            file_get_contents(
                'php://input'
            );


        if (
            $raw === false ||
            trim($raw) === ''
        ) {

            if (!empty($_POST)) {

                return $_POST;
            }


            return [];
        }


        $contentType =
            strtolower(
                (string) (
                    $_SERVER['CONTENT_TYPE']
                    ?? ''
                )
            );


        /*
         * JSON.
         */
        if (
            str_contains(
                $contentType,
                'application/json'
            ) ||
            str_starts_with(
                ltrim($raw),
                '{'
            )
        ) {

            try {

                $data =
                    json_decode(
                        $raw,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

            } catch (JsonException $e) {

                $this->jsonError(
                    'Invalid JSON request.',
                    400
                );
            }


            if (
                !is_array($data)
            ) {

                $this->jsonError(
                    'Request body must be a JSON object.',
                    400
                );
            }


            return $data;
        }


        /*
         * URL encoded fallback.
         */
        parse_str(
            $raw,
            $data
        );


        return is_array($data)
            ? $data
            : [];
    }


    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    |
    | This implementation intentionally does not call
    | paymentVerifyCsrf().
    |
    | It reads the same CSRF session token used by the
    | Tenspick security endpoint and accepts:
    *
    * X-CSRF-TOKEN
    * X-CSRF-Token
    * X-CSRFToken
    * X-XSRF-TOKEN
    * csrf_token
    * csrfToken
    *
    */

    private function verifyCsrf(
        array $request
    ): void
    {
        if (
            session_status()
            !== PHP_SESSION_ACTIVE
        ) {

            session_start();
        }


        /*
         * Read all supported request headers.
         */
        $headerToken = null;


        $headerNames = [
            'HTTP_X_CSRF_TOKEN',
            'HTTP_X_CSRFToken',
            'HTTP_X_CSRF-TOKEN',
            'HTTP_X_XSRF_TOKEN'
        ];


        foreach (
            $headerNames
            as $headerName
        ) {

            if (
                isset(
                    $_SERVER[$headerName]
                ) &&
                is_string(
                    $_SERVER[$headerName]
                ) &&
                trim(
                    $_SERVER[$headerName]
                ) !== ''
            ) {

                $headerToken =
                    trim(
                        $_SERVER[$headerName]
                    );

                break;
            }
        }


        /*
         * PHP normally converts:
         *
         * X-CSRF-Token
         *
         * into:
         *
         * HTTP_X_CSRF_TOKEN
         *
         */
        if (
            !$headerToken &&
            function_exists(
                'getallheaders'
            )
        ) {

            $headers =
                getallheaders();


            if (
                is_array($headers)
            ) {

                foreach (
                    $headers
                    as $name => $value
                ) {

                    $normalized =
                        strtolower(
                            str_replace(
                                '_',
                                '-',
                                trim(
                                    (string) $name
                                )
                            )
                        );


                    if (
                        in_array(
                            $normalized,
                            [
                                'x-csrf-token',
                                'x-csrftoken',
                                'x-xsrf-token'
                            ],
                            true
                        )
                    ) {

                        if (
                            is_string($value) &&
                            trim($value) !== ''
                        ) {

                            $headerToken =
                                trim($value);

                            break;
                        }
                    }
                }
            }
        }


        /*
         * Request body token.
         */
        $requestToken =
            null;


        $requestTokenNames = [
            'csrf_token',
            'csrfToken',
            '_csrf'
        ];


        foreach (
            $requestTokenNames
            as $name
        ) {

            if (
                isset(
                    $request[$name]
                ) &&
                is_string(
                    $request[$name]
                ) &&
                trim(
                    $request[$name]
                ) !== ''
            ) {

                $requestToken =
                    trim(
                        $request[$name]
                    );

                break;
            }
        }


        /*
         * Prefer header token.
         */
        $token =
            $headerToken
            ?: $requestToken;


        /*
         * Read session token.
         */
        $sessionToken =
            $_SESSION['csrf_token']
            ?? $_SESSION['_csrf']
            ?? null;


        /*
         * If the application has its own global validator,
         * use it first.
         */
        if (
            function_exists(
                'verifyCsrfToken'
            )
        ) {

            if (
                !$token ||
                !verifyCsrfToken(
                    $token
                )
            ) {

                $this->jsonError(
                    'Invalid or missing CSRF token.',
                    419
                );
            }


            return;
        }


        /*
         * Standard session comparison.
         */
        if (
            !is_string($sessionToken) ||
            trim($sessionToken) === ''
        ) {

            $this->jsonError(
                'CSRF session token is unavailable.',
                419
            );
        }


        if (
            !$token ||
            !hash_equals(
                (string) $sessionToken,
                (string) $token
            )
        ) {

            $this->jsonError(
                'Invalid or missing CSRF token.',
                419
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CURRENT ADMIN ID
    |--------------------------------------------------------------------------
    */

    private function getCurrentAdminId(): ?int
    {
        if (
            function_exists(
                'paymentGetCurrentAdminId'
            )
        ) {

            $id =
                paymentGetCurrentAdminId();


            if (
                $id !== null &&
                $id > 0
            ) {

                return (int) $id;
            }
        }


        if (
            session_status()
            !== PHP_SESSION_ACTIVE
        ) {

            session_start();
        }


        $possibleIds = [

            $_SESSION['admin_id']
                ?? null,

            $_SESSION['admin']['id']
                ?? null,

            $_SESSION['admin']['staff_id']
                ?? null,

            $_SESSION['user_id']
                ?? null
        ];


        foreach (
            $possibleIds
            as $id
        ) {

            if (
                $id !== null &&
                $id !== '' &&
                filter_var(
                    $id,
                    FILTER_VALIDATE_INT
                ) !== false
            ) {

                $id =
                    (int) $id;


                if ($id > 0) {

                    return $id;
                }
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | RESOLVE added_by
    |--------------------------------------------------------------------------
    |
    | staff_payments.added_by -> staff.id
    |
    | If admin session ID does not exist in staff table,
    | return NULL instead of causing a foreign-key error.
    |
    */

    private function resolveAddedByStaffId(
        ?int $adminId
    ): ?int
    {
        if (
            $adminId === null ||
            $adminId <= 0
        ) {

            return null;
        }


        try {

            $stmt =
                $this->pdo->prepare(
                    "
                    SELECT id

                    FROM staff

                    WHERE id = :id

                    LIMIT 1
                    "
                );


            $stmt->bindValue(
                ':id',
                $adminId,
                PDO::PARAM_INT
            );


            $stmt->execute();


            $staffId =
                $stmt->fetchColumn();


            if (
                $staffId !== false &&
                $staffId !== null
            ) {

                return (int) $staffId;
            }

        } catch (Throwable $e) {

            $this->logError(
                'resolveAddedByStaffId',
                $e
            );
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE POSITIVE INTEGER
    |--------------------------------------------------------------------------
    */

    private function validatePositiveInt(
        mixed $value,
        string $field
    ): int
    {
        if (
            $value === null ||
            $value === '' ||
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {

            $this->jsonError(
                $field .
                ' must be a valid ID.',
                422
            );
        }


        $id =
            (int) $value;


        if ($id <= 0) {

            $this->jsonError(
                $field .
                ' must be greater than zero.',
                422
            );
        }


        return $id;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE AMOUNT
    |--------------------------------------------------------------------------
    */

    private function validateAmount(
        mixed $value
    ): float
    {
        if (
            $value === null ||
            $value === '' ||
            !is_numeric($value)
        ) {

            $this->jsonError(
                'Payment amount must be a valid number.',
                422
            );
        }


        $amount =
            round(
                (float) $value,
                2
            );


        if (
            !is_finite($amount)
        ) {

            $this->jsonError(
                'Payment amount is invalid.',
                422
            );
        }


        if (
            $amount <= 0
        ) {

            $this->jsonError(
                'Payment amount must be greater than zero.',
                422
            );
        }


        return $amount;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE DATE
    |--------------------------------------------------------------------------
    */

    private function validateDate(
        mixed $value,
        string $field = 'Date'
    ): string
    {
        if (
            $value === null ||
            trim(
                (string) $value
            ) === ''
        ) {

            $this->jsonError(
                $field .
                ' is required.',
                422
            );
        }


        $date =
            trim(
                (string) $value
            );


        $object =
            DateTime::createFromFormat(
                'Y-m-d',
                $date
            );


        $errors =
            DateTime::getLastErrors();


        if (
            !$object ||
            (
                is_array($errors) &&
                (
                    $errors['warning_count'] > 0 ||
                    $errors['error_count'] > 0
                )
            ) ||
            $object->format('Y-m-d') !== $date
        ) {

            $this->jsonError(
                $field .
                ' must be in YYYY-MM-DD format.',
                422
            );
        }


        return $date;
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT PAYMENT
    |--------------------------------------------------------------------------
    */

    private function formatPayment(
        array $row
    ): array
    {
        $paymentType =
            (string) (
                $row['payment_type']
                ?? ''
            );


        $paymentMethod =
            (string) (
                $row['payment_method']
                ?? ''
            );


        $amount =
            (float) (
                $row['amount']
                ?? 0
            );


        $formatted = [

            'id' =>
                (int) (
                    $row['id']
                    ?? 0
                ),

            'payment_id' =>
                $row['payment_code']
                ?? '',

            'payment_code' =>
                $row['payment_code']
                ?? '',


            /*
             * Staff.
             */
            'staff_id' =>
                (int) (
                    $row['staff_id']
                    ?? 0
                ),

            'staff_code' =>
                $row['staff_code']
                ?? '',

            'staff_name' =>
                $row['staff_name']
                ?? '',

            'staff_email' =>
                $row['staff_email']
                ?? null,

            'staff_phone' =>
                $row['staff_phone']
                ?? null,

            'staff_department' =>
                $row['staff_department']
                ?? null,

            'staff_designation' =>
                $row['staff_designation']
                ?? null,

            'staff_status' =>
                $row['staff_status']
                ?? null,

            'staff_profile_image' =>
                $row['staff_profile_image']
                ?? null,


            /*
             * Staff object.
             */
            'staff' => [

                'id' =>
                    (int) (
                        $row['staff_id']
                        ?? 0
                    ),

                'staff_code' =>
                    $row['staff_code']
                    ?? '',

                'name' =>
                    $row['staff_name']
                    ?? '',

                'email' =>
                    $row['staff_email']
                    ?? null,

                'phone' =>
                    $row['staff_phone']
                    ?? null,

                'department' =>
                    $row['staff_department']
                    ?? null,

                'designation' =>
                    $row['staff_designation']
                    ?? null,

                'joining_date' =>
                    $row['staff_joining_date']
                    ?? null,

                'status' =>
                    $row['staff_status']
                    ?? null,

                'profile_image' =>
                    $row['staff_profile_image']
                    ?? null
            ],


            /*
             * Payment.
             */
            'payment_type' =>
                $paymentType,

            'payment_type_label' =>
                $this->paymentTypeLabel(
                    $paymentType
                ),

            'amount' =>
                $this->formatMoney(
                    $amount
                ),

            'payment_date' =>
                $row['payment_date']
                ?? null,

            'payment_period' =>
                $row['payment_period']
                ?? '',

            'month_period' =>
                $row['payment_period']
                ?? '',

            'payment_method' =>
                $paymentMethod,

            'payment_method_label' =>
                $this->paymentMethodLabel(
                    $paymentMethod
                ),

            'remarks' =>
                $row['remarks']
                ?? null,


            /*
             * Added By.
             */
            'added_by' =>
                isset(
                    $row['added_by']
                ) &&
                $row['added_by'] !== null
                    ? (int) $row['added_by']
                    : null,

            'added_by_name' =>
                $row['added_by_name']
                ?? null,

            'added_by_code' =>
                $row['added_by_code']
                ?? null,


            /*
             * Dates.
             */
            'created_at' =>
                $row['created_at']
                ?? null,

            'updated_at' =>
                $row['updated_at']
                ?? null
        ];


        /*
         * Receipt data.
         */
        $formatted['receipt'] = [

            'payment_code' =>
                $row['payment_code']
                ?? '',

            'payment_date' =>
                $row['payment_date']
                ?? null,

            'amount' =>
                $this->formatMoney(
                    $amount
                ),

            'staff_name' =>
                $row['staff_name']
                ?? '',

            'staff_code' =>
                $row['staff_code']
                ?? '',

            'department' =>
                $row['staff_department']
                ?? null,

            'designation' =>
                $row['staff_designation']
                ?? null,

            'payment_type' =>
                $this->paymentTypeLabel(
                    $paymentType
                ),

            'payment_period' =>
                $row['payment_period']
                ?? '',

            'payment_method' =>
                $this->paymentMethodLabel(
                    $paymentMethod
                ),

            'remarks' =>
                $row['remarks']
                ?? null,

            'added_by_name' =>
                $row['added_by_name']
                ?? null
        ];


        return $formatted;
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT TYPE LABEL
    |--------------------------------------------------------------------------
    */

    private function paymentTypeLabel(
        string $type
    ): string
    {
        return match ($type) {

            'salary' =>
                'Salary',

            'advance' =>
                'Advance',

            'bonus' =>
                'Bonus',

            'incentive' =>
                'Incentive',

            'reimbursement' =>
                'Reimbursement',

            'other' =>
                'Other',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $type
                    )
                )
        };
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHOD LABEL
    |--------------------------------------------------------------------------
    */

    private function paymentMethodLabel(
        string $method
    ): string
    {
        return match ($method) {

            'cash' =>
                'Cash',

            'upi' =>
                'UPI',

            'bank_transfer' =>
                'Bank Transfer',

            'card' =>
                'Card',

            'cheque' =>
                'Cheque',

            'other' =>
                'Other',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $method
                    )
                )
        };
    }


    /*
    |--------------------------------------------------------------------------
    | MONEY
    |--------------------------------------------------------------------------
    */

    private function formatMoney(
        mixed $value
    ): string
    {
        if (
            $value === null ||
            $value === ''
        ) {

            $value = 0;
        }


        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PDO FALLBACK
    |--------------------------------------------------------------------------
    */

    private function getPDOFallback(): PDO
    {
        global $pdo, $db;


        if (
            isset($pdo) &&
            $pdo instanceof PDO
        ) {

            return $pdo;
        }


        if (
            isset($db) &&
            $db instanceof PDO
        ) {

            return $db;
        }


        if (
            function_exists('getPDO')
        ) {

            $connection =
                getPDO();


            if (
                $connection instanceof PDO
            ) {

                return $connection;
            }
        }


        if (
            function_exists(
                'getDatabaseConnection'
            )
        ) {

            $connection =
                getDatabaseConnection();


            if (
                $connection instanceof PDO
            ) {

                return $connection;
            }
        }


        $this->jsonError(
            'Database connection is not available.',
            500
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN AUTH FALLBACK
    |--------------------------------------------------------------------------
    */

    private function requireAdminFallback(): void
    {
        if (
            session_status()
            !== PHP_SESSION_ACTIVE
        ) {

            session_start();
        }


        if (
            empty(
                $_SESSION['admin']
            ) &&
            empty(
                $_SESSION['admin_id']
            )
        ) {

            $this->jsonError(
                'Unauthorized access.',
                401
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | JSON RESPONSE
    |--------------------------------------------------------------------------
    */

    private function jsonResponse(
        bool $success,
        string $message,
        array $data = [],
        int $statusCode = 200
    ): never
    {
        http_response_code(
            $statusCode
        );


        header(
            'Content-Type: application/json; charset=utf-8'
        );


        echo json_encode(
            [
                'success' =>
                    $success,

                'message' =>
                    $message,

                'data' =>
                    $data
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | JSON ERROR
    |--------------------------------------------------------------------------
    */

    private function jsonError(
        string $message,
        int $statusCode = 400,
        array $data = []
    ): never
    {
        $this->jsonResponse(
            false,
            $message,
            $data,
            $statusCode
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ERROR LOG
    |--------------------------------------------------------------------------
    */

    private function logError(
        string $method,
        Throwable $e
    ): void
    {
        error_log(
            'StaffPaymentController@' .
            $method .
            ': ' .
            $e->getMessage() .
            PHP_EOL .
            $e->getTraceAsString()
        );
    }
}