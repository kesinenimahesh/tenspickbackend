<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * CLIENT PORTAL CONTROLLER
 * ============================================================
 *
 * File:
 * backend/controllers/ClientPortalController.php
 *
 * ============================================================
 *
 * PURPOSE
 * ------------------------------------------------------------
 * Read-only API layer for the authenticated client portal.
 *
 * ============================================================
 *
 * AUTHENTICATION
 * ------------------------------------------------------------
 *
 * Client authentication is handled through:
 *
 *     requireClientAuthentication()
 *
 * The authenticated client ID ALWAYS comes from the
 * server-side PHP session.
 *
 * NEVER accept client_id from:
 *
 * - $_GET
 * - $_POST
 * - JSON body
 * - frontend JavaScript
 *
 * ============================================================
 *
 * CLIENT PROJECTS
 * ------------------------------------------------------------
 *
 * GET /api/client-portal/projects
 *
 * GET /api/client-portal/projects/{id}
 *
 * GET /api/client-portal/projects/{id}/progress
 *
 * GET /api/client-portal/projects/{id}/milestones
 *
 * ============================================================
 *
 * CLIENT PAYMENTS
 * ------------------------------------------------------------
 *
 * GET /api/client-portal/payments
 *
 * GET /api/client-portal/projects/{id}/payments
 *
 * ============================================================
 *
 * SECURITY
 * ------------------------------------------------------------
 *
 * Every project-specific request verifies:
 *
 * project.id
 * AND
 * project.client_id = authenticated client
 *
 * ============================================================
 *
 * READ ONLY
 * ------------------------------------------------------------
 *
 * Clients cannot:
 *
 * - create projects
 * - update projects
 * - delete projects
 * - create milestones
 * - update milestones
 * - delete milestones
 * - create payments
 * - update payments
 * - delete payments
 *
 * ============================================================
 */


/* ============================================================
   CLIENT PORTAL CONTROLLER
   ============================================================ */

class ClientPortalController
{

    /* ========================================================
       GET CLIENT PROJECTS
       GET /api/client-portal/projects
       ======================================================== */

    public function projects(): never
    {
        $clientId =
            requireClientAuthentication();


        try {

            $pdo =
                db();


            $statement =
                $pdo->prepare(
                    "
                    SELECT

                        p.id,

                        p.project_code,
                        p.project_name,
                        p.project_type,

                        p.description,

                        p.start_date,
                        p.expected_completion,

                        p.budget,

                        p.status,
                        p.progress_percentage,

                        p.live_website_link,

                        p.domain_purchased_email,
                        p.seo_added_email,

                        p.project_manager_id,

                        p.created_at,
                        p.updated_at

                    FROM projects p

                    WHERE
                        p.client_id = :projects_client_id

                    ORDER BY
                        p.created_at DESC,
                        p.id DESC
                    "
                );


            $statement->execute(
                [
                    ':projects_client_id' =>
                        $clientId
                ]
            );


            $projects =
                $statement->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach (
                $projects as &$project
            ) {

                $project =
                    $this->normalizeProject(
                        $project
                    );
            }

            unset($project);


            successResponse(
                'Client projects fetched successfully.',
                [
                    'projects' =>
                        $projects,

                    'count' =>
                        count($projects)
                ]
            );


        } catch (PDOException $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROJECTS DATABASE ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch your projects because of a database error.',
                null,
                500
            );


        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROJECTS ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch your projects.',
                null,
                500
            );
        }
    }


    /* ========================================================
       GET SINGLE CLIENT PROJECT
       GET /api/client-portal/projects/{id}
       ======================================================== */

    public function project(
        int $projectId
    ): never {

        $clientId =
            requireClientAuthentication();


        if (
            $projectId <= 0
        ) {

            errorResponse(
                'Invalid project ID.',
                null,
                400
            );
        }


        try {

            $pdo =
                db();


            /* ------------------------------------------------
               PROJECT OWNERSHIP
               ------------------------------------------------ */

            $statement =
                $pdo->prepare(
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

                        p.domain_purchased_email,
                        p.seo_added_email,

                        p.project_manager_id,

                        p.created_at,
                        p.updated_at

                    FROM projects p

                    WHERE
                        p.id = :project_id

                        AND p.client_id = :project_client_id

                    LIMIT 1
                    "
                );


            $statement->execute(
                [
                    ':project_id' =>
                        $projectId,

                    ':project_client_id' =>
                        $clientId
                ]
            );


            $project =
                $statement->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$project) {

                errorResponse(
                    'Project not found.',
                    null,
                    404
                );
            }


            $project =
                $this->normalizeProject(
                    $project
                );


            /* ------------------------------------------------
               MILESTONES
               ------------------------------------------------ */

            $milestoneStatement =
                $pdo->prepare(
                    "
                    SELECT

                        id,
                        project_id,

                        milestone_name,
                        description,

                        sequence_no,

                        status,
                        progress_percentage,

                        start_date,
                        expected_completion,

                        completed_at,

                        created_at,
                        updated_at

                    FROM project_milestones

                    WHERE
                        project_id = :detail_project_id

                    ORDER BY
                        sequence_no ASC,
                        id ASC
                    "
                );


            $milestoneStatement->execute(
                [
                    ':detail_project_id' =>
                        $projectId
                ]
            );


            $milestones =
                $milestoneStatement->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach (
                $milestones as &$milestone
            ) {

                $milestone =
                    $this->formatMilestone(
                        $milestone
                    );
            }

            unset($milestone);


            $project['milestones'] =
                $milestones;


            $project['milestone_count'] =
                count($milestones);


            /* ------------------------------------------------
               PAYMENT SUMMARY
               ------------------------------------------------ */

            $paymentSummary =
                $this->getProjectPaymentSummary(
                    $pdo,
                    $projectId,
                    $clientId
                );


            $project['payment_summary'] =
                $paymentSummary;


            successResponse(
                'Client project fetched successfully.',
                [
                    'project' =>
                        $project
                ]
            );


        } catch (PDOException $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROJECT DATABASE ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch the project because of a database error.',
                null,
                500
            );


        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROJECT ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch the project.',
                null,
                500
            );
        }
    }


    /* ========================================================
       GET PROJECT PROGRESS
       GET /api/client-portal/projects/{id}/progress
       ======================================================== */

    public function progress(
        int $projectId
    ): never {

        $clientId =
            requireClientAuthentication();


        if (
            $projectId <= 0
        ) {

            errorResponse(
                'Invalid project ID.',
                null,
                400
            );
        }


        try {

            $pdo =
                db();


            /* ------------------------------------------------
               VERIFY PROJECT OWNERSHIP
               ------------------------------------------------ */

            $projectStatement =
                $pdo->prepare(
                    "
                    SELECT

                        id,

                        project_code,
                        project_name,

                        status,
                        progress_percentage,

                        start_date,
                        expected_completion

                    FROM projects

                    WHERE
                        id = :progress_project_id

                        AND client_id = :progress_client_id

                    LIMIT 1
                    "
                );


            $projectStatement->execute(
                [
                    ':progress_project_id' =>
                        $projectId,

                    ':progress_client_id' =>
                        $clientId
                ]
            );


            $project =
                $projectStatement->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$project) {

                errorResponse(
                    'Project not found.',
                    null,
                    404
                );
            }


            /* ------------------------------------------------
               MILESTONES
               ------------------------------------------------ */

            $milestoneStatement =
                $pdo->prepare(
                    "
                    SELECT

                        id,
                        project_id,

                        milestone_name,
                        description,

                        sequence_no,

                        status,
                        progress_percentage,

                        start_date,
                        expected_completion,

                        completed_at,

                        created_at,
                        updated_at

                    FROM project_milestones

                    WHERE
                        project_id = :progress_milestone_project_id

                    ORDER BY
                        sequence_no ASC,
                        id ASC
                    "
                );


            $milestoneStatement->execute(
                [
                    ':progress_milestone_project_id' =>
                        $projectId
                ]
            );


            $milestones =
                $milestoneStatement->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach (
                $milestones as &$milestone
            ) {

                $milestone =
                    $this->formatMilestone(
                        $milestone
                    );
            }

            unset($milestone);


            /* ------------------------------------------------
               MILESTONE PROGRESS
               ------------------------------------------------ */

            $milestoneProgress =
                0.00;


            if (
                count($milestones) > 0
            ) {

                $totalProgress =
                    0.00;


                foreach (
                    $milestones as $milestone
                ) {

                    $totalProgress +=
                        $this->normalizeProgress(
                            $milestone[
                                'progress_percentage'
                            ] ?? 0
                        );
                }


                $milestoneProgress =
                    round(
                        $totalProgress /
                        count($milestones),
                        2
                    );
            }


            /* ------------------------------------------------
               STORED PROJECT PROGRESS
               ------------------------------------------------ */

            $storedProgress =
                $this->normalizeProgress(
                    $project[
                        'progress_percentage'
                    ] ?? 0
                );


            /* ------------------------------------------------
               FINAL PROJECT PROGRESS
               ------------------------------------------------ */

            $progress =
                count($milestones) > 0
                    ? $milestoneProgress
                    : $storedProgress;


            successResponse(
                'Project progress fetched successfully.',
                [
                    'project' => [

                        'id' =>
                            (int) $project['id'],

                        'project_code' =>
                            $project['project_code'],

                        'project_name' =>
                            $project['project_name'],

                        'status' =>
                            $project['status'],

                        'progress_percentage' =>
                            $progress,

                        'start_date' =>
                            $project['start_date'],

                        'expected_completion' =>
                            $project['expected_completion']
                    ],

                    'progress' =>
                        $progress,

                    'milestone_progress' =>
                        $milestoneProgress,

                    'stored_project_progress' =>
                        $storedProgress,

                    'milestone_count' =>
                        count($milestones),

                    'milestones' =>
                        $milestones
                ]
            );


        } catch (PDOException $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROGRESS DATABASE ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch project progress because of a database error.',
                null,
                500
            );


        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROGRESS ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch project progress.',
                null,
                500
            );
        }
    }


    /* ========================================================
       GET PROJECT MILESTONES
       GET /api/client-portal/projects/{id}/milestones
       ======================================================== */

    public function milestones(
        int $projectId
    ): never {

        $clientId =
            requireClientAuthentication();


        if (
            $projectId <= 0
        ) {

            errorResponse(
                'Invalid project ID.',
                null,
                400
            );
        }


        try {

            $pdo =
                db();


            /* ------------------------------------------------
               VERIFY PROJECT OWNERSHIP
               ------------------------------------------------ */

            $projectStatement =
                $pdo->prepare(
                    "
                    SELECT

                        id,
                        project_code,
                        project_name

                    FROM projects

                    WHERE
                        id = :milestone_project_id

                        AND client_id = :milestone_client_id

                    LIMIT 1
                    "
                );


            $projectStatement->execute(
                [
                    ':milestone_project_id' =>
                        $projectId,

                    ':milestone_client_id' =>
                        $clientId
                ]
            );


            $project =
                $projectStatement->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$project) {

                errorResponse(
                    'Project not found.',
                    null,
                    404
                );
            }


            /* ------------------------------------------------
               MILESTONES
               ------------------------------------------------ */

            $statement =
                $pdo->prepare(
                    "
                    SELECT

                        id,
                        project_id,

                        milestone_name,
                        description,

                        sequence_no,

                        status,
                        progress_percentage,

                        start_date,
                        expected_completion,

                        completed_at,

                        created_at,
                        updated_at

                    FROM project_milestones

                    WHERE
                        project_id = :milestones_project_id

                    ORDER BY
                        sequence_no ASC,
                        id ASC
                    "
                );


            $statement->execute(
                [
                    ':milestones_project_id' =>
                        $projectId
                ]
            );


            $milestones =
                $statement->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach (
                $milestones as &$milestone
            ) {

                $milestone =
                    $this->formatMilestone(
                        $milestone
                    );
            }

            unset($milestone);


            successResponse(
                'Project milestones fetched successfully.',
                [
                    'project' => [

                        'id' =>
                            (int) $project['id'],

                        'project_code' =>
                            $project['project_code'],

                        'project_name' =>
                            $project['project_name']
                    ],

                    'milestones' =>
                        $milestones,

                    'count' =>
                        count($milestones)
                ]
            );


        } catch (PDOException $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL MILESTONES DATABASE ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch milestones because of a database error.',
                null,
                500
            );


        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL MILESTONES ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch project milestones.',
                null,
                500
            );
        }
    }


    /* ========================================================
       GET CLIENT PAYMENTS
       GET /api/client-portal/payments
       ======================================================== */

    public function payments(): never
    {
        $clientId =
            requireClientAuthentication();


        try {

            $pdo =
                db();


            /* ------------------------------------------------
               PAYMENT HISTORY
               ------------------------------------------------
             *
             * IMPORTANT:
             *
             * Two different parameter names are used:
             *
             * :payments_client_id
             * :payments_project_client_id
             *
             * This prevents PDO HY093 errors when native
             * prepared statements are enabled.
             */

            $statement =
                $pdo->prepare(
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

                        cp.created_at,
                        cp.updated_at,

                        p.project_code,
                        p.project_name,
                        p.project_type,

                        p.budget AS current_project_budget

                    FROM client_payments cp

                    INNER JOIN projects p
                        ON p.id = cp.project_id

                    WHERE

                        cp.client_id =
                            :payments_client_id

                        AND

                        p.client_id =
                            :payments_project_client_id

                    ORDER BY

                        cp.payment_date DESC,
                        cp.id DESC
                    "
                );


            $statement->execute(
                [
                    ':payments_client_id' =>
                        $clientId,

                    ':payments_project_client_id' =>
                        $clientId
                ]
            );


            $payments =
                $statement->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /* ------------------------------------------------
               NORMALIZE PAYMENTS
               ------------------------------------------------ */

            $totalPaid =
                0.00;


            foreach (
                $payments as &$payment
            ) {

                $payment =
                    $this->normalizePayment(
                        $payment
                    );


                $totalPaid +=
                    (float) $payment['amount'];
            }

            unset($payment);


            $totalPaid =
                round(
                    $totalPaid,
                    2
                );


            /* ------------------------------------------------
               FINANCIAL SUMMARY
               ------------------------------------------------ */

            $summary =
                $this->getClientFinancialSummary(
                    $pdo,
                    $clientId
                );


            /*
             * Payment count and actual payment total
             * are added without replacing the project
             * financial summary.
             */

            $summary['payment_count'] =
                count($payments);


            $summary['total_paid_from_history'] =
                number_format(
                    $totalPaid,
                    2,
                    '.',
                    ''
                );


            /* ------------------------------------------------
               RESPONSE
               ------------------------------------------------ */

            successResponse(
                'Client payment history fetched successfully.',
                [
                    'payments' =>
                        $payments,

                    'summary' =>
                        $summary,

                    'count' =>
                        count($payments)
                ]
            );


        } catch (PDOException $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PAYMENTS DATABASE ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch your payments because of a database error.',
                null,
                500
            );


        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PAYMENTS ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch your payments.',
                null,
                500
            );
        }
    }


    /* ========================================================
       GET PROJECT PAYMENTS
       GET /api/client-portal/projects/{id}/payments
       ======================================================== */

    public function projectPayments(
        int $projectId
    ): never {

        $clientId =
            requireClientAuthentication();


        if (
            $projectId <= 0
        ) {

            errorResponse(
                'Invalid project ID.',
                null,
                400
            );
        }


        try {

            $pdo =
                db();


            /* ------------------------------------------------
               VERIFY PROJECT OWNERSHIP
               ------------------------------------------------ */

            $projectStatement =
                $pdo->prepare(
                    "
                    SELECT

                        id,
                        project_code,
                        project_name,
                        project_type,

                        description,

                        budget,

                        status,
                        progress_percentage,

                        start_date,
                        expected_completion,

                        live_website_link

                    FROM projects

                    WHERE

                        id =
                            :project_payment_project_id

                        AND

                        client_id =
                            :project_payment_client_id

                    LIMIT 1
                    "
                );


            $projectStatement->execute(
                [
                    ':project_payment_project_id' =>
                        $projectId,

                    ':project_payment_client_id' =>
                        $clientId
                ]
            );


            $project =
                $projectStatement->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$project) {

                errorResponse(
                    'Project not found.',
                    null,
                    404
                );
            }


            /* ------------------------------------------------
               PROJECT PAYMENTS
               ------------------------------------------------ */

            $statement =
                $pdo->prepare(
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

                        cp.created_at,
                        cp.updated_at

                    FROM client_payments cp

                    WHERE

                        cp.project_id =
                            :project_payments_project_id

                        AND

                        cp.client_id =
                            :project_payments_client_id

                    ORDER BY

                        cp.payment_date ASC,
                        cp.id ASC
                    "
                );


            $statement->execute(
                [
                    ':project_payments_project_id' =>
                        $projectId,

                    ':project_payments_client_id' =>
                        $clientId
                ]
            );


            $payments =
                $statement->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /* ------------------------------------------------
               NORMALIZE PAYMENTS
               ------------------------------------------------ */

            $totalPaid =
                0.00;


            foreach (
                $payments as &$payment
            ) {

                $payment =
                    $this->normalizePayment(
                        $payment
                    );


                $totalPaid +=
                    (float) $payment['amount'];
            }

            unset($payment);


            $totalPaid =
                round(
                    $totalPaid,
                    2
                );


            /* ------------------------------------------------
               PROJECT AMOUNT
               ------------------------------------------------ */

            $projectAmount =
                round(
                    (float) (
                        $project['budget']
                        ?? 0
                    ),
                    2
                );


            /* ------------------------------------------------
               BALANCE
               ------------------------------------------------ */

            $remaining =
                max(
                    0,
                    round(
                        $projectAmount -
                        $totalPaid,
                        2
                    )
                );


            /* ------------------------------------------------
               NORMALIZE PROJECT
               ------------------------------------------------ */

            $project =
                $this->normalizeProject(
                    $project
                );


            /* ------------------------------------------------
               RESPONSE
               ------------------------------------------------ */

            successResponse(
                'Project payment history fetched successfully.',
                [
                    'project' => [

                        'id' =>
                            (int) $project['id'],

                        'project_code' =>
                            $project['project_code'],

                        'project_name' =>
                            $project['project_name'],

                        'project_type' =>
                            $project['project_type'],

                        'budget' =>
                            $project['budget'],

                        'status' =>
                            $project['status'],

                        'start_date' =>
                            $project['start_date'],

                        'expected_completion' =>
                            $project['expected_completion']
                    ],

                    'payments' =>
                        $payments,

                    'summary' => [

                        'payment_count' =>
                            count($payments),

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
                                $remaining,
                                2,
                                '.',
                                ''
                            )
                    ],

                    'count' =>
                        count($payments)
                ]
            );


        } catch (PDOException $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROJECT PAYMENTS DATABASE ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch project payments because of a database error.',
                null,
                500
            );


        } catch (Throwable $exception) {

            error_log(
                'TENSPICK CLIENT PORTAL PROJECT PAYMENTS ERROR: ' .
                $exception->getMessage()
            );


            errorResponse(
                'Unable to fetch project payments.',
                null,
                500
            );
        }
    }


    /* ========================================================
       PRIVATE — CLIENT FINANCIAL SUMMARY
       ======================================================== */

    private function getClientFinancialSummary(
        PDO $pdo,
        int $clientId
    ): array {

        /*
         * IMPORTANT:
         *
         * Payment totals are grouped by project first.
         *
         * This prevents a project budget from being multiplied
         * when a project has multiple payment records.
         */

        $statement =
            $pdo->prepare(
                "
                SELECT

                    COUNT(p.id)
                        AS project_count,

                    COALESCE(
                        SUM(p.budget),
                        0
                    )
                    AS total_project_amount,

                    COALESCE(
                        SUM(
                            COALESCE(
                                payment_totals.total_paid,
                                0
                            )
                        ),
                        0
                    )
                    AS total_paid

                FROM projects p

                LEFT JOIN (

                    SELECT

                        project_id,

                        SUM(amount)
                            AS total_paid

                    FROM client_payments

                    WHERE
                        client_id =
                            :financial_payment_client_id

                    GROUP BY
                        project_id

                ) payment_totals

                    ON payment_totals.project_id =
                        p.id

                WHERE
                    p.client_id =
                        :financial_project_client_id
                "
            );


        $statement->execute(
            [
                ':financial_payment_client_id' =>
                    $clientId,

                ':financial_project_client_id' =>
                    $clientId
            ]
        );


        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$row) {

            return [

                'project_count' =>
                    0,

                'total_project_amount' =>
                    '0.00',

                'total_paid' =>
                    '0.00',

                'remaining_amount' =>
                    '0.00'
            ];
        }


        $projectCount =
            (int) (
                $row['project_count']
                ?? 0
            );


        $totalProjectAmount =
            round(
                (float) (
                    $row['total_project_amount']
                    ?? 0
                ),
                2
            );


        $totalPaid =
            round(
                (float) (
                    $row['total_paid']
                    ?? 0
                ),
                2
            );


        $remaining =
            max(
                0,
                round(
                    $totalProjectAmount -
                    $totalPaid,
                    2
                )
            );


        return [

            'project_count' =>
                $projectCount,

            'total_project_amount' =>
                number_format(
                    $totalProjectAmount,
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
                    $remaining,
                    2,
                    '.',
                    ''
                )
        ];
    }


    /* ========================================================
       PRIVATE — PROJECT PAYMENT SUMMARY
       ======================================================== */

    private function getProjectPaymentSummary(
        PDO $pdo,
        int $projectId,
        int $clientId
    ): array {

        $statement =
            $pdo->prepare(
                "
                SELECT

                    p.budget,

                    COALESCE(
                        payment_totals.total_paid,
                        0
                    ) AS total_paid

                FROM projects p

                LEFT JOIN (

                    SELECT

                        project_id,

                        SUM(amount)
                            AS total_paid

                    FROM client_payments

                    WHERE

                        project_id =
                            :summary_project_id

                        AND

                        client_id =
                            :summary_client_id

                    GROUP BY
                        project_id

                ) payment_totals

                    ON payment_totals.project_id =
                        p.id

                WHERE

                    p.id =
                        :summary_lookup_project_id

                    AND

                    p.client_id =
                        :summary_lookup_client_id

                LIMIT 1
                "
            );


        $statement->execute(
            [
                ':summary_project_id' =>
                    $projectId,

                ':summary_client_id' =>
                    $clientId,

                ':summary_lookup_project_id' =>
                    $projectId,

                ':summary_lookup_client_id' =>
                    $clientId
            ]
        );


        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$row) {

            return [

                'project_amount' =>
                    '0.00',

                'total_paid' =>
                    '0.00',

                'remaining_amount' =>
                    '0.00'
            ];
        }


        $projectAmount =
            round(
                (float) (
                    $row['budget']
                    ?? 0
                ),
                2
            );


        $totalPaid =
            round(
                (float) (
                    $row['total_paid']
                    ?? 0
                ),
                2
            );


        $remaining =
            max(
                0,
                round(
                    $projectAmount -
                    $totalPaid,
                    2
                )
            );


        return [

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
                    $remaining,
                    2,
                    '.',
                    ''
                )
        ];
    }


    /* ========================================================
       PRIVATE — NORMALIZE PROJECT
       ======================================================== */

    private function normalizeProject(
        array $project
    ): array {

        if (
            array_key_exists(
                'id',
                $project
            )
        ) {

            $project['id'] =
                (int) (
                    $project['id']
                    ?? 0
                );
        }


        if (
            array_key_exists(
                'client_id',
                $project
            )
        ) {

            $project['client_id'] =
                (int) (
                    $project['client_id']
                    ?? 0
                );
        }


        if (
            array_key_exists(
                'project_manager_id',
                $project
            )
        ) {

            $project['project_manager_id'] =
                $project['project_manager_id'] !== null
                    ? (int) $project['project_manager_id']
                    : null;
        }


        if (
            array_key_exists(
                'budget',
                $project
            )
        ) {

            $project['budget'] =
                number_format(
                    (float) (
                        $project['budget']
                        ?? 0
                    ),
                    2,
                    '.',
                    ''
                );
        }


        if (
            array_key_exists(
                'progress_percentage',
                $project
            )
        ) {

            $project['progress_percentage'] =
                $this->normalizeProgress(
                    $project[
                        'progress_percentage'
                    ] ?? 0
                );
        }


        return $project;
    }


    /* ========================================================
       PRIVATE — NORMALIZE PAYMENT
       ======================================================== */

    private function normalizePayment(
        array $payment
    ): array {

        if (
            array_key_exists(
                'id',
                $payment
            )
        ) {

            $payment['id'] =
                (int) (
                    $payment['id']
                    ?? 0
                );
        }


        if (
            array_key_exists(
                'client_id',
                $payment
            )
        ) {

            $payment['client_id'] =
                (int) (
                    $payment['client_id']
                    ?? 0
                );
        }


        if (
            array_key_exists(
                'project_id',
                $payment
            )
        ) {

            $payment['project_id'] =
                (int) (
                    $payment['project_id']
                    ?? 0
                );
        }


        $moneyFields = [

            'amount',

            'project_amount_snapshot',

            'paid_before',

            'paid_after',

            'remaining_after'
        ];


        foreach (
            $moneyFields as $field
        ) {

            if (
                array_key_exists(
                    $field,
                    $payment
                )
            ) {

                $payment[$field] =
                    number_format(
                        (float) (
                            $payment[$field]
                            ?? 0
                        ),
                        2,
                        '.',
                        ''
                    );
            }
        }


        return $payment;
    }


    /* ========================================================
       PRIVATE — FORMAT MILESTONE
       ======================================================== */

    private function formatMilestone(
        array $milestone
    ): array {

        $milestone['id'] =
            (int) (
                $milestone['id']
                ?? 0
            );


        if (
            array_key_exists(
                'project_id',
                $milestone
            )
        ) {

            $milestone['project_id'] =
                (int) (
                    $milestone['project_id']
                    ?? 0
                );
        }


        if (
            array_key_exists(
                'sequence_no',
                $milestone
            )
        ) {

            $milestone['sequence_no'] =
                (int) (
                    $milestone['sequence_no']
                    ?? 0
                );
        }


        $milestone[
            'progress_percentage'
        ] =
            $this->normalizeProgress(
                $milestone[
                    'progress_percentage'
                ] ?? 0
            );


        return $milestone;
    }


    /* ========================================================
       PRIVATE — NORMALIZE PROGRESS
       ======================================================== */

    private function normalizeProgress(
        mixed $value
    ): float {

        $progress =
            (float) $value;


        if (
            !is_finite(
                $progress
            )
        ) {

            $progress =
                0.0;
        }


        return round(
            min(
                100,
                max(
                    0,
                    $progress
                )
            ),
            2
        );
    }

}