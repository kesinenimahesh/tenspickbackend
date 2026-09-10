<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * PROJECT CONTROLLER
 * ============================================================
 *
 * PROJECTS
 * --------
 * GET    /api/projects
 * GET    /api/projects/{id}
 * POST   /api/projects
 * PUT    /api/projects/{id}
 * DELETE /api/projects/{id}
 *
 * PROJECT MILESTONES
 * ------------------
 * GET    /api/projects/{id}/milestones
 * POST   /api/projects/{id}/milestones
 * PUT    /api/projects/{id}/milestones/{milestone_id}
 * DELETE /api/projects/{id}/milestones/{milestone_id}
 *
 * ============================================================
 */


/* ============================================================
   PROJECT STATUS
   ============================================================ */

function tenspickProjectAllowedStatuses(): array
{
    return [
        'planning',
        'not_started',
        'in_progress',
        'review',
        'client_review',
        'completed',
        'on_hold',
        'cancelled'
    ];
}


/* ============================================================
   MILESTONE STATUS
   ============================================================ */

function tenspickProjectMilestoneAllowedStatuses(): array
{
    return [
        'not_started',
        'in_progress',
        'completed',
        'on_hold'
    ];
}


/* ============================================================
   PROJECT STATUS LABEL
   ============================================================ */

function tenspickProjectStatusLabel(
    string $status
): string {

    $labels = [

        'planning' =>
            'Planning',

        'not_started' =>
            'Not Started',

        'in_progress' =>
            'In Progress',

        'review' =>
            'Review',

        'client_review' =>
            'Client Review',

        'completed' =>
            'Completed',

        'on_hold' =>
            'On Hold',

        'cancelled' =>
            'Cancelled'
    ];

    return
        $labels[$status]
        ?? ucwords(
            str_replace(
                '_',
                ' ',
                $status
            )
        );
}


/* ============================================================
   MILESTONE STATUS LABEL
   ============================================================ */

function tenspickProjectMilestoneStatusLabel(
    string $status
): string {

    $labels = [

        'not_started' =>
            'Not Started',

        'in_progress' =>
            'In Progress',

        'completed' =>
            'Completed',

        'on_hold' =>
            'On Hold'
    ];

    return
        $labels[$status]
        ?? ucwords(
            str_replace(
                '_',
                ' ',
                $status
            )
        );
}


/* ============================================================
   NORMALIZE PROJECT STATUS
   ============================================================ */

function tenspickNormalizeProjectStatus(
    ?string $status
): string {

    $status =
        strtolower(
            trim(
                (string) $status
            )
        );

    if ($status === '') {
        return 'planning';
    }

    return $status;
}


/* ============================================================
   NORMALIZE MILESTONE STATUS
   ============================================================ */

function tenspickNormalizeProjectMilestoneStatus(
    ?string $status
): string {

    $status =
        strtolower(
            trim(
                (string) $status
            )
        );

    if ($status === '') {
        return 'not_started';
    }

    return $status;
}


/* ============================================================
   NORMALIZE PROGRESS
   ============================================================ */

function tenspickNormalizeProjectProgress(
    mixed $progress
): int {

    if (
        $progress === null ||
        $progress === ''
    ) {
        return 0;
    }

    if (!is_numeric($progress)) {
        return 0;
    }

    $progress =
        (int) $progress;

    if ($progress < 0) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    return $progress;
}


/* ============================================================
   EMAIL VALIDATION
   ============================================================ */

function tenspickValidateProjectEmail(
    string $email
): bool {

    if ($email === '') {
        return true;
    }

    if (
        mb_strlen($email) > 255
    ) {
        return false;
    }

    return
        filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) !== false;
}


/* ============================================================
   DATE VALIDATION
   ============================================================ */

function tenspickValidateProjectDate(
    string $date
): bool {

    if ($date === '') {
        return false;
    }

    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

    if ($dateObject === false) {
        return false;
    }

    return
        $dateObject->format('Y-m-d')
        === $date;
}


/* ============================================================
   GET REQUEST BODY
   ============================================================ */

function tenspickProjectRequestData(): array
{
    $raw =
        file_get_contents(
            'php://input'
        );

    if (
        $raw === false ||
        trim($raw) === ''
    ) {
        return [];
    }

    $data =
        json_decode(
            $raw,
            true
        );

    if (
        !is_array($data)
    ) {

        errorResponse(
            'Invalid JSON request body.',
            null,
            400
        );
    }

    return $data;
}


/* ============================================================
   GENERATE PROJECT CODE
   ============================================================ */

function tenspickGenerateProjectCode(
    PDO $pdo
): string {

    $prefix =
        'PRJ-' .
        date('Ymd') .
        '-';


    $statement =
        $pdo->prepare(
            "
            SELECT project_code
            FROM projects
            WHERE project_code LIKE :prefix
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
        $attempt < 1000;
        $attempt++
    ) {

        $projectCode =
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
                FROM projects
                WHERE project_code = :project_code
                LIMIT 1
                "
            );


        $check->execute([
            ':project_code' =>
                $projectCode
        ]);


        if (
            !$check->fetchColumn()
        ) {

            return $projectCode;
        }


        $number++;
    }


    throw new RuntimeException(
        'Unable to generate a unique project code.'
    );
}


/* ============================================================
   GET CLIENT
   ============================================================ */

function tenspickGetProjectClient(
    PDO $pdo,
    int $clientId
): ?array {

    $statement =
        $pdo->prepare(
            "
            SELECT
                id,
                client_code,
                client_name,
                company_name,
                mobile,
                email,
                status
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


    if (
        !$client
    ) {
        return null;
    }


    return $client;
}


/* ============================================================
   FORMAT PROJECT
   ============================================================ */

function tenspickFormatProject(
    array $project
): array {

    $project['id'] =
        (int) (
            $project['id']
            ?? 0
        );


    $project['client_id'] =
        (int) (
            $project['client_id']
            ?? 0
        );


    if (
        isset(
        $project['project_manager_id']
    ) &&
        $project['project_manager_id'] !== null &&
        $project['project_manager_id'] !== ''
    ) {

        $project['project_manager_id'] =
            (int) $project['project_manager_id'];

    } else {

        $project['project_manager_id'] =
            null;
    }


    $project['budget'] =
        isset(
        $project['budget']
    )
        ? (float) $project['budget']
        : 0.00;


    $project['progress_percentage'] =
        tenspickNormalizeProjectProgress(
            $project['progress_percentage']
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | Normalize email fields
    |--------------------------------------------------------------------------
    */

    $project['domain_purchased_email'] =
        isset(
        $project['domain_purchased_email']
    ) &&
        $project['domain_purchased_email'] !== ''
        ? (string) $project['domain_purchased_email']
        : null;


    $project['seo_added_email'] =
        isset(
        $project['seo_added_email']
    ) &&
        $project['seo_added_email'] !== ''
        ? (string) $project['seo_added_email']
        : null;


    $project['status_label'] =
        tenspickProjectStatusLabel(
            (string) (
                $project['status']
                ?? 'planning'
            )
        );


    return $project;
}


/* ============================================================
   FORMAT MILESTONE
   ============================================================ */

function tenspickFormatProjectMilestone(
    array $milestone
): array {

    $milestone['id'] =
        (int) (
            $milestone['id']
            ?? 0
        );


    $milestone['project_id'] =
        (int) (
            $milestone['project_id']
            ?? 0
        );


    $milestone['sequence_no'] =
        (int) (
            $milestone['sequence_no']
            ?? 0
        );


    $milestone['progress_percentage'] =
        tenspickNormalizeProjectProgress(
            $milestone['progress_percentage']
            ?? 0
        );


    $milestone['status_label'] =
        tenspickProjectMilestoneStatusLabel(
            (string) (
                $milestone['status']
                ?? 'not_started'
            )
        );


    return $milestone;
}


/* ============================================================
   GET ALL PROJECTS
   ============================================================ */

/**
 * GET /api/projects
 */
function getProjects(): void
{
    requireAdminAuthentication();


    try {

        $pdo =
            db();


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $page =
            max(
                1,
                (int) (
                    $_GET['page']
                    ?? 1
                )
            );


        $perPage =
            isset($_GET['per_page'])
            ? (int) $_GET['per_page']
            : (
                isset($_GET['limit'])
                ? (int) $_GET['limit']
                : 20
            );


        if ($perPage < 1) {
            $perPage = 20;
        }


        if ($perPage > 100) {
            $perPage = 100;
        }


        $offset =
            ($page - 1) *
            $perPage;


        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) (
                    $_GET['search']
                    ?? ''
                )
            );


        $status =
            trim(
                (string) (
                    $_GET['status']
                    ?? ''
                )
            );


        $clientId =
            trim(
                (string) (
                    $_GET['client_id']
                    ?? ''
                )
            );


        $projectType =
            trim(
                (string) (
                    $_GET['project_type']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | WHERE
        |--------------------------------------------------------------------------
        */

        $where = [];

        $params = [];


        if (
            $search !== ''
        ) {

            $where[] = "
                (
                    p.project_code LIKE :search
                    OR p.project_name LIKE :search
                    OR p.project_type LIKE :search
                    OR c.client_code LIKE :search
                    OR c.client_name LIKE :search
                    OR c.company_name LIKE :search
                )
            ";


            $params[':search'] =
                '%' .
                $search .
                '%';
        }


        if (
            $status !== ''
        ) {

            if (
                !in_array(
                    $status,
                    tenspickProjectAllowedStatuses(),
                    true
                )
            ) {

                errorResponse(
                    'Invalid project status.',
                    null,
                    422
                );
            }


            $where[] =
                'p.status = :status';


            $params[':status'] =
                $status;
        }


        if (
            $clientId !== ''
        ) {

            if (
                !ctype_digit(
                    $clientId
                ) ||
                (int) $clientId <= 0
            ) {

                errorResponse(
                    'Invalid client ID.',
                    null,
                    422
                );
            }


            $where[] =
                'p.client_id = :client_id';


            $params[':client_id'] =
                (int) $clientId;
        }


        if (
            $projectType !== ''
        ) {

            $where[] =
                'p.project_type = :project_type';


            $params[':project_type'] =
                $projectType;
        }


        $whereSql =
            !empty($where)
            ? 'WHERE ' .
            implode(
                ' AND ',
                $where
            )
            : '';


        /*
        |--------------------------------------------------------------------------
        | COUNT
        |--------------------------------------------------------------------------
        */

        $countStatement =
            $pdo->prepare(
                "
                SELECT COUNT(*)

                FROM projects p

                INNER JOIN clients c
                    ON c.id = p.client_id

                $whereSql
                "
            );


        foreach (
            $params as $key => $value
        ) {

            $countStatement->bindValue(
                $key,
                $value
            );
        }


        $countStatement->execute();


        $total =
            (int) $countStatement->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | PROJECTS
        |--------------------------------------------------------------------------
        */

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
                    p.updated_at,

                    c.client_code,
                    c.client_name,
                    c.company_name

                FROM projects p

                INNER JOIN clients c
                    ON c.id = p.client_id

                $whereSql

                ORDER BY p.id DESC

                LIMIT :limit
                OFFSET :offset
                "
            );


        foreach (
            $params as $key => $value
        ) {

            $statement->bindValue(
                $key,
                $value
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


        $projects =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach (
            $projects as &$project
        ) {

            $project =
                tenspickFormatProject(
                    $project
                );
        }

        unset($project);


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $totalPages =
            $total > 0
            ? (int) ceil(
                $total /
                $perPage
            )
            : 0;


        successResponse(
            'Projects fetched successfully.',
            [
                'projects' =>
                    $projects,

                'pagination' => [
                    'page' =>
                        $page,

                    'per_page' =>
                        $perPage,

                    'total' =>
                        $total,

                    'total_pages' =>
                        $totalPages
                ]
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK GET PROJECTS DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to fetch projects because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET PROJECTS ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to fetch projects.',
            null,
            500
        );
    }
}


/* ============================================================
   GET SINGLE PROJECT
   ============================================================ */

/**
 * GET /api/projects/{id}
 */
function getProject(
    int $projectId
): void {

    requireAdminAuthentication();


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


        /*
        |--------------------------------------------------------------------------
        | Project
        |--------------------------------------------------------------------------
        */

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
                    p.updated_at,

                    c.client_code,
                    c.client_name,
                    c.company_name,
                    c.mobile AS client_mobile,
                    c.email AS client_email

                FROM projects p

                INNER JOIN clients c
                    ON c.id = p.client_id

                WHERE p.id = :id

                LIMIT 1
                "
            );


        $statement->execute([
            ':id' =>
                $projectId
        ]);


        $project =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$project
        ) {

            notFoundResponse(
                'Project not found.'
            );
        }


        $project =
            tenspickFormatProject(
                $project
            );


        /*
        |--------------------------------------------------------------------------
        | Milestones
        |--------------------------------------------------------------------------
        */

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

                WHERE project_id = :project_id

                ORDER BY
                    sequence_no ASC,
                    id ASC
                "
            );


        $milestoneStatement->execute([
            ':project_id' =>
                $projectId
        ]);


        $milestones =
            $milestoneStatement->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach (
            $milestones as &$milestone
        ) {

            $milestone =
                tenspickFormatProjectMilestone(
                    $milestone
                );
        }

        unset($milestone);


        $project['milestones'] =
            $milestones;


        $project['milestone_count'] =
            count(
                $milestones
            );


        successResponse(
            'Project fetched successfully.',
            [
                'project' =>
                    $project
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK GET PROJECT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to fetch project because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET PROJECT ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to fetch project.',
            null,
            500
        );
    }
}


/* ============================================================
   CREATE PROJECT
   ============================================================ */

/**
 * POST /api/projects
 */
function createProject(): void
{
    requireAdminAuthentication();
    requireCsrfToken();


    $data =
        tenspickProjectRequestData();


    /*
    |--------------------------------------------------------------------------
    | Required fields
    |--------------------------------------------------------------------------
    */

    $clientId =
        isset($data['client_id'])
        ? (int) $data['client_id']
        : 0;


    $projectName =
        trim(
            (string) (
                $data['project_name']
                ?? ''
            )
        );


    if (
        $clientId <= 0
    ) {

        errorResponse(
            'Client is required.',
            null,
            422
        );
    }


    if (
        $projectName === ''
    ) {

        errorResponse(
            'Project name is required.',
            null,
            422
        );
    }


    if (
        mb_strlen(
            $projectName
        ) > 200
    ) {

        errorResponse(
            'Project name cannot exceed 200 characters.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Optional fields
    |--------------------------------------------------------------------------
    */

    $projectType =
        trim(
            (string) (
                $data['project_type']
                ?? ''
            )
        );


    $description =
        trim(
            (string) (
                $data['description']
                ?? ''
            )
        );


    $startDate =
        trim(
            (string) (
                $data['start_date']
                ?? ''
            )
        );


    $expectedCompletion =
        trim(
            (string) (
                $data['expected_completion']
                ?? ''
            )
        );


    $budget =
        $data['budget']
        ?? 0;


    $status =
        tenspickNormalizeProjectStatus(
            $data['status']
            ?? 'planning'
        );


    $progress =
        tenspickNormalizeProjectProgress(
            $data['progress_percentage']
            ?? 0
        );


    $liveWebsiteLink =
        trim(
            (string) (
                $data['live_website_link']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Domain Purchased Email
    |--------------------------------------------------------------------------
    */

    $domainPurchasedEmail =
        trim(
            (string) (
                $data['domain_purchased_email']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | SEO Added Email
    |--------------------------------------------------------------------------
    */

    $seoAddedEmail =
        trim(
            (string) (
                $data['seo_added_email']
                ?? ''
            )
        );


    $projectManagerId =
        isset(
        $data['project_manager_id']
    ) &&
        $data['project_manager_id'] !== '' &&
        $data['project_manager_id'] !== null
        ? (int) $data['project_manager_id']
        : null;


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $status,
            tenspickProjectAllowedStatuses(),
            true
        )
    ) {

        errorResponse(
            'Invalid project status.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Budget
    |--------------------------------------------------------------------------
    */

    if (
        !is_numeric($budget)
    ) {

        errorResponse(
            'Budget must be a valid number.',
            null,
            422
        );
    }


    $budget =
        (float) $budget;


    if (
        $budget < 0
    ) {

        errorResponse(
            'Budget cannot be negative.',
            null,
            422
        );
    }


    if (
        $budget > 9999999999.99
    ) {

        errorResponse(
            'Budget is too large.',
            null,
            422
        );
    }


    $budget =
        round(
            $budget,
            2
        );


    /*
    |--------------------------------------------------------------------------
    | Start date
    |--------------------------------------------------------------------------
    */

    if (
        $startDate !== '' &&
        !tenspickValidateProjectDate(
            $startDate
        )
    ) {

        errorResponse(
            'Invalid start date.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Expected completion
    |--------------------------------------------------------------------------
    */

    if (
        $expectedCompletion !== '' &&
        !tenspickValidateProjectDate(
            $expectedCompletion
        )
    ) {

        errorResponse(
            'Invalid expected completion date.',
            null,
            422
        );
    }


    if (
        $startDate !== '' &&
        $expectedCompletion !== '' &&
        $expectedCompletion < $startDate
    ) {

        errorResponse(
            'Expected completion cannot be before the start date.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    if (
        $liveWebsiteLink !== ''
    ) {

        if (
            !filter_var(
                $liveWebsiteLink,
                FILTER_VALIDATE_URL
            )
        ) {

            errorResponse(
                'Live website link must be a valid URL.',
                null,
                422
            );
        }


        if (
            mb_strlen(
                $liveWebsiteLink
            ) > 500
        ) {

            errorResponse(
                'Live website link cannot exceed 500 characters.',
                null,
                422
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Domain Purchased Email
    |--------------------------------------------------------------------------
    */

    if (
        $domainPurchasedEmail !== '' &&
        !tenspickValidateProjectEmail(
            $domainPurchasedEmail
        )
    ) {

        errorResponse(
            'Domain purchased email must be a valid email address.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SEO Added Email
    |--------------------------------------------------------------------------
    */

    if (
        $seoAddedEmail !== '' &&
        !tenspickValidateProjectEmail(
            $seoAddedEmail
        )
    ) {

        errorResponse(
            'SEO added email must be a valid email address.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get database
    |--------------------------------------------------------------------------
    */

    try {

        $pdo =
            db();


        /*
        |--------------------------------------------------------------------------
        | Verify client
        |--------------------------------------------------------------------------
        */

        $client =
            tenspickGetProjectClient(
                $pdo,
                $clientId
            );


        if (
            !$client
        ) {

            notFoundResponse(
                'Selected client not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Generate project code
        |--------------------------------------------------------------------------
        */

        $projectCode =
            tenspickGenerateProjectCode(
                $pdo
            );


        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */

        $statement =
            $pdo->prepare(
                "
                INSERT INTO projects (

                    project_code,
                    client_id,
                    project_name,
                    project_type,
                    description,
                    start_date,
                    expected_completion,
                    budget,
                    status,
                    progress_percentage,
                    live_website_link,
                    domain_purchased_email,
                    seo_added_email,
                    project_manager_id

                )

                VALUES (

                    :project_code,
                    :client_id,
                    :project_name,
                    :project_type,
                    :description,
                    :start_date,
                    :expected_completion,
                    :budget,
                    :status,
                    :progress_percentage,
                    :live_website_link,
                    :domain_purchased_email,
                    :seo_added_email,
                    :project_manager_id

                )
                "
            );


        $statement->execute([

            ':project_code' =>
                $projectCode,

            ':client_id' =>
                $clientId,

            ':project_name' =>
                $projectName,

            ':project_type' =>
                $projectType !== ''
                ? $projectType
                : null,

            ':description' =>
                $description !== ''
                ? $description
                : null,

            ':start_date' =>
                $startDate !== ''
                ? $startDate
                : null,

            ':expected_completion' =>
                $expectedCompletion !== ''
                ? $expectedCompletion
                : null,

            ':budget' =>
                $budget,

            ':status' =>
                $status,

            ':progress_percentage' =>
                $progress,

            ':live_website_link' =>
                $liveWebsiteLink !== ''
                ? $liveWebsiteLink
                : null,

            ':domain_purchased_email' =>
                $domainPurchasedEmail !== ''
                ? $domainPurchasedEmail
                : null,

            ':seo_added_email' =>
                $seoAddedEmail !== ''
                ? $seoAddedEmail
                : null,

            ':project_manager_id' =>
                $projectManagerId
        ]);


        $projectId =
            (int) $pdo->lastInsertId();


        if (
            $projectId <= 0
        ) {

            throw new RuntimeException(
                'Project could not be created.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */

        $pdo->commit();


        successResponse(
            'Project created successfully.',
            [
                'project_id' =>
                    $projectId,

                'project_code' =>
                    $projectCode
            ]
        );

    } catch (PDOException $exception) {

        if (
            isset($pdo) &&
            $pdo instanceof PDO &&
            $pdo->inTransaction()
        ) {

            $pdo->rollBack();
        }


        error_log(
            'TENSPICK CREATE PROJECT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        if (
            isset(
            $exception->errorInfo[1]
        ) &&
            (int) $exception->errorInfo[1] === 1062
        ) {

            errorResponse(
                'Project code already exists. Please try again.',
                null,
                409
            );
        }


        errorResponse(
            'Unable to create project because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        if (
            isset($pdo) &&
            $pdo instanceof PDO &&
            $pdo->inTransaction()
        ) {

            $pdo->rollBack();
        }


        error_log(
            'TENSPICK CREATE PROJECT ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to create project.',
            null,
            500
        );
    }
}


/* ============================================================
   UPDATE PROJECT
   ============================================================ */

/**
 * PUT /api/projects/{id}
 */
function updateProject(
    int $projectId
): void {

    requireAdminAuthentication();
    requireCsrfToken();


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );
    }


    $data =
        tenspickProjectRequestData();


    try {

        $pdo =
            db();


        /*
        |--------------------------------------------------------------------------
        | Existing project
        |--------------------------------------------------------------------------
        */

        $existingStatement =
            $pdo->prepare(
                "
                SELECT *
                FROM projects
                WHERE id = :id
                LIMIT 1
                "
            );


        $existingStatement->execute([
            ':id' =>
                $projectId
        ]);


        $existing =
            $existingStatement->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$existing
        ) {

            notFoundResponse(
                'Project not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Values
        |--------------------------------------------------------------------------
        */

        $clientId =
            array_key_exists(
                'client_id',
                $data
            )
            ? (int) $data['client_id']
            : (int) $existing['client_id'];


        $projectName =
            array_key_exists(
                'project_name',
                $data
            )
            ? trim(
                (string) $data['project_name']
            )
            : (string) $existing['project_name'];


        $projectType =
            array_key_exists(
                'project_type',
                $data
            )
            ? trim(
                (string) $data['project_type']
            )
            : (string) (
                $existing['project_type']
                ?? ''
            );


        $description =
            array_key_exists(
                'description',
                $data
            )
            ? trim(
                (string) $data['description']
            )
            : (string) (
                $existing['description']
                ?? ''
            );


        $startDate =
            array_key_exists(
                'start_date',
                $data
            )
            ? trim(
                (string) $data['start_date']
            )
            : (string) (
                $existing['start_date']
                ?? ''
            );


        $expectedCompletion =
            array_key_exists(
                'expected_completion',
                $data
            )
            ? trim(
                (string) $data['expected_completion']
            )
            : (string) (
                $existing['expected_completion']
                ?? ''
            );


        $budget =
            array_key_exists(
                'budget',
                $data
            )
            ? $data['budget']
            : $existing['budget'];


        $status =
            array_key_exists(
                'status',
                $data
            )
            ? tenspickNormalizeProjectStatus(
                $data['status']
            )
            : (string) $existing['status'];


        $progress =
            array_key_exists(
                'progress_percentage',
                $data
            )
            ? tenspickNormalizeProjectProgress(
                $data['progress_percentage']
            )
            : tenspickNormalizeProjectProgress(
                $existing['progress_percentage']
            );


        $liveWebsiteLink =
            array_key_exists(
                'live_website_link',
                $data
            )
            ? trim(
                (string) $data['live_website_link']
            )
            : (string) (
                $existing['live_website_link']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Domain Purchased Email
        |--------------------------------------------------------------------------
        */

        $domainPurchasedEmail =
            array_key_exists(
                'domain_purchased_email',
                $data
            )
            ? trim(
                (string) (
                    $data['domain_purchased_email']
                    ?? ''
                )
            )
            : (string) (
                $existing['domain_purchased_email']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | SEO Added Email
        |--------------------------------------------------------------------------
        */

        $seoAddedEmail =
            array_key_exists(
                'seo_added_email',
                $data
            )
            ? trim(
                (string) (
                    $data['seo_added_email']
                    ?? ''
                )
            )
            : (string) (
                $existing['seo_added_email']
                ?? ''
            );


        $projectManagerId =
            array_key_exists(
                'project_manager_id',
                $data
            )
            ? (
                $data['project_manager_id'] === null ||
                $data['project_manager_id'] === ''
                ? null
                : (int) $data['project_manager_id']
            )
            : (
                $existing['project_manager_id'] !== null
                ? (int) $existing['project_manager_id']
                : null
            );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (
            $clientId <= 0
        ) {

            errorResponse(
                'Client is required.',
                null,
                422
            );
        }


        if (
            $projectName === ''
        ) {

            errorResponse(
                'Project name is required.',
                null,
                422
            );
        }


        if (
            mb_strlen(
                $projectName
            ) > 200
        ) {

            errorResponse(
                'Project name cannot exceed 200 characters.',
                null,
                422
            );
        }


        if (
            !in_array(
                $status,
                tenspickProjectAllowedStatuses(),
                true
            )
        ) {

            errorResponse(
                'Invalid project status.',
                null,
                422
            );
        }


        if (
            !is_numeric($budget)
        ) {

            errorResponse(
                'Budget must be a valid number.',
                null,
                422
            );
        }


        $budget =
            round(
                (float) $budget,
                2
            );


        if (
            $budget < 0
        ) {

            errorResponse(
                'Budget cannot be negative.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        if (
            $startDate !== '' &&
            !tenspickValidateProjectDate(
                $startDate
            )
        ) {

            errorResponse(
                'Invalid start date.',
                null,
                422
            );
        }


        if (
            $expectedCompletion !== '' &&
            !tenspickValidateProjectDate(
                $expectedCompletion
            )
        ) {

            errorResponse(
                'Invalid expected completion date.',
                null,
                422
            );
        }


        if (
            $startDate !== '' &&
            $expectedCompletion !== '' &&
            $expectedCompletion < $startDate
        ) {

            errorResponse(
                'Expected completion cannot be before the start date.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Website
        |--------------------------------------------------------------------------
        */

        if (
            $liveWebsiteLink !== '' &&
            !filter_var(
                $liveWebsiteLink,
                FILTER_VALIDATE_URL
            )
        ) {

            errorResponse(
                'Live website link must be a valid URL.',
                null,
                422
            );
        }


        if (
            mb_strlen(
                $liveWebsiteLink
            ) > 500
        ) {

            errorResponse(
                'Live website link cannot exceed 500 characters.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Domain Purchased Email
        |--------------------------------------------------------------------------
        */

        if (
            $domainPurchasedEmail !== '' &&
            !tenspickValidateProjectEmail(
                $domainPurchasedEmail
            )
        ) {

            errorResponse(
                'Domain purchased email must be a valid email address.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SEO Added Email
        |--------------------------------------------------------------------------
        */

        if (
            $seoAddedEmail !== '' &&
            !tenspickValidateProjectEmail(
                $seoAddedEmail
            )
        ) {

            errorResponse(
                'SEO added email must be a valid email address.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Client
        |--------------------------------------------------------------------------
        */

        $client =
            tenspickGetProjectClient(
                $pdo,
                $clientId
            );


        if (
            !$client
        ) {

            notFoundResponse(
                'Selected client not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $statement =
            $pdo->prepare(
                "
                UPDATE projects

                SET

                    client_id =
                        :client_id,

                    project_name =
                        :project_name,

                    project_type =
                        :project_type,

                    description =
                        :description,

                    start_date =
                        :start_date,

                    expected_completion =
                        :expected_completion,

                    budget =
                        :budget,

                    status =
                        :status,

                    progress_percentage =
                        :progress_percentage,

                    live_website_link =
                        :live_website_link,

                    domain_purchased_email =
                        :domain_purchased_email,

                    seo_added_email =
                        :seo_added_email,

                    project_manager_id =
                        :project_manager_id

                WHERE id = :id

                LIMIT 1
                "
            );


        $statement->execute([

            ':client_id' =>
                $clientId,

            ':project_name' =>
                $projectName,

            ':project_type' =>
                $projectType !== ''
                ? $projectType
                : null,

            ':description' =>
                $description !== ''
                ? $description
                : null,

            ':start_date' =>
                $startDate !== ''
                ? $startDate
                : null,

            ':expected_completion' =>
                $expectedCompletion !== ''
                ? $expectedCompletion
                : null,

            ':budget' =>
                $budget,

            ':status' =>
                $status,

            ':progress_percentage' =>
                $progress,

            ':live_website_link' =>
                $liveWebsiteLink !== ''
                ? $liveWebsiteLink
                : null,

            ':domain_purchased_email' =>
                $domainPurchasedEmail !== ''
                ? $domainPurchasedEmail
                : null,

            ':seo_added_email' =>
                $seoAddedEmail !== ''
                ? $seoAddedEmail
                : null,

            ':project_manager_id' =>
                $projectManagerId,

            ':id' =>
                $projectId
        ]);


        successResponse(
            'Project updated successfully.',
            [
                'project_id' =>
                    $projectId,

                'project_code' =>
                    $existing['project_code']
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK UPDATE PROJECT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to update project because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK UPDATE PROJECT ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to update project.',
            null,
            500
        );
    }
}


/* ============================================================
   DELETE PROJECT
   ============================================================ */

/**
 * DELETE /api/projects/{id}
 */
function deleteProject(
    int $projectId
): void {

    requireAdminAuthentication();
    requireCsrfToken();


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


        /*
        |--------------------------------------------------------------------------
        | Find project
        |--------------------------------------------------------------------------
        */

        $statement =
            $pdo->prepare(
                "
                SELECT
                    id,
                    project_code,
                    project_name

                FROM projects

                WHERE id = :id

                LIMIT 1
                "
            );


        $statement->execute([
            ':id' =>
                $projectId
        ]);


        $project =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$project
        ) {

            notFoundResponse(
                'Project not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $delete =
            $pdo->prepare(
                "
                DELETE FROM projects

                WHERE id = :id

                LIMIT 1
                "
            );


        $delete->execute([
            ':id' =>
                $projectId
        ]);


        if (
            $delete->rowCount() !== 1
        ) {

            throw new RuntimeException(
                'Project could not be deleted.'
            );
        }


        successResponse(
            'Project deleted successfully.',
            [
                'project_id' =>
                    $projectId,

                'project_code' =>
                    $project['project_code']
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK DELETE PROJECT DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to delete project because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK DELETE PROJECT ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to delete project.',
            null,
            500
        );
    }
}


/* ============================================================
   GET PROJECT MILESTONES
   ============================================================ */

/**
 * GET /api/projects/{id}/milestones
 */
function getProjectMilestones(
    int $projectId
): void {

    requireAdminAuthentication();


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


        /*
        |--------------------------------------------------------------------------
        | Verify project
        |--------------------------------------------------------------------------
        */

        $projectCheck =
            $pdo->prepare(
                "
                SELECT id
                FROM projects
                WHERE id = :id
                LIMIT 1
                "
            );


        $projectCheck->execute([
            ':id' =>
                $projectId
        ]);


        if (
            !$projectCheck->fetchColumn()
        ) {

            notFoundResponse(
                'Project not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Milestones
        |--------------------------------------------------------------------------
        */

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

                WHERE project_id = :project_id

                ORDER BY
                    sequence_no ASC,
                    id ASC
                "
            );


        $statement->execute([
            ':project_id' =>
                $projectId
        ]);


        $milestones =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach (
            $milestones as &$milestone
        ) {

            $milestone =
                tenspickFormatProjectMilestone(
                    $milestone
                );
        }

        unset($milestone);


        successResponse(
            'Project milestones fetched successfully.',
            [
                'milestones' =>
                    $milestones,

                'count' =>
                    count(
                        $milestones
                    )
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK GET PROJECT MILESTONES DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to fetch project milestones because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK GET PROJECT MILESTONES ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to fetch project milestones.',
            null,
            500
        );
    }
}


/* ============================================================
   CREATE PROJECT MILESTONE
   ============================================================ */

/**
 * POST /api/projects/{id}/milestones
 */
function createProjectMilestone(
    int $projectId
): void {

    requireAdminAuthentication();
    requireCsrfToken();


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );
    }


    $data =
        tenspickProjectRequestData();


    $milestoneName =
        trim(
            (string) (
                $data['milestone_name']
                ?? ''
            )
        );


    $description =
        trim(
            (string) (
                $data['description']
                ?? ''
            )
        );


    $sequenceNo =
        isset(
        $data['sequence_no']
    )
        ? (int) $data['sequence_no']
        : 0;


    $status =
        tenspickNormalizeProjectMilestoneStatus(
            $data['status']
            ?? 'not_started'
        );


    $progress =
        tenspickNormalizeProjectProgress(
            $data['progress_percentage']
            ?? 0
        );


    $startDate =
        trim(
            (string) (
                $data['start_date']
                ?? ''
            )
        );


    $expectedCompletion =
        trim(
            (string) (
                $data['expected_completion']
                ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Required
    |--------------------------------------------------------------------------
    */

    if (
        $milestoneName === ''
    ) {

        errorResponse(
            'Milestone name is required.',
            null,
            422
        );
    }


    if (
        mb_strlen(
            $milestoneName
        ) > 200
    ) {

        errorResponse(
            'Milestone name cannot exceed 200 characters.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $status,
            tenspickProjectMilestoneAllowedStatuses(),
            true
        )
    ) {

        errorResponse(
            'Invalid milestone status.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    */

    if (
        $startDate !== '' &&
        !tenspickValidateProjectDate(
            $startDate
        )
    ) {

        errorResponse(
            'Invalid milestone start date.',
            null,
            422
        );
    }


    if (
        $expectedCompletion !== '' &&
        !tenspickValidateProjectDate(
            $expectedCompletion
        )
    ) {

        errorResponse(
            'Invalid milestone expected completion date.',
            null,
            422
        );
    }


    if (
        $startDate !== '' &&
        $expectedCompletion !== '' &&
        $expectedCompletion < $startDate
    ) {

        errorResponse(
            'Milestone expected completion cannot be before the start date.',
            null,
            422
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    try {

        $pdo =
            db();


        /*
        |--------------------------------------------------------------------------
        | Verify project
        |--------------------------------------------------------------------------
        */

        $projectCheck =
            $pdo->prepare(
                "
                SELECT id
                FROM projects
                WHERE id = :id
                LIMIT 1
                "
            );


        $projectCheck->execute([
            ':id' =>
                $projectId
        ]);


        if (
            !$projectCheck->fetchColumn()
        ) {

            notFoundResponse(
                'Project not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Auto sequence
        |--------------------------------------------------------------------------
        */

        if (
            $sequenceNo <= 0
        ) {

            $sequenceStatement =
                $pdo->prepare(
                    "
                    SELECT
                        COALESCE(
                            MAX(sequence_no),
                            0
                        ) + 1

                    FROM project_milestones

                    WHERE project_id = :project_id
                    "
                );


            $sequenceStatement->execute([
                ':project_id' =>
                    $projectId
            ]);


            $sequenceNo =
                (int) (
                    $sequenceStatement->fetchColumn()
                    ?: 1
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Completed logic
        |--------------------------------------------------------------------------
        */

        $completedAt =
            null;


        if (
            $status === 'completed'
        ) {

            $progress =
                100;


            $completedAt =
                date(
                    'Y-m-d H:i:s'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Insert milestone
        |--------------------------------------------------------------------------
        */

        $statement =
            $pdo->prepare(
                "
                INSERT INTO project_milestones (

                    project_id,
                    milestone_name,
                    description,
                    sequence_no,
                    status,
                    progress_percentage,
                    start_date,
                    expected_completion,
                    completed_at

                )

                VALUES (

                    :project_id,
                    :milestone_name,
                    :description,
                    :sequence_no,
                    :status,
                    :progress_percentage,
                    :start_date,
                    :expected_completion,
                    :completed_at

                )
                "
            );


        $statement->execute([

            ':project_id' =>
                $projectId,

            ':milestone_name' =>
                $milestoneName,

            ':description' =>
                $description !== ''
                ? $description
                : null,

            ':sequence_no' =>
                $sequenceNo,

            ':status' =>
                $status,

            ':progress_percentage' =>
                $progress,

            ':start_date' =>
                $startDate !== ''
                ? $startDate
                : null,

            ':expected_completion' =>
                $expectedCompletion !== ''
                ? $expectedCompletion
                : null,

            ':completed_at' =>
                $completedAt
        ]);


        $milestoneId =
            (int) $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | Recalculate progress
        |--------------------------------------------------------------------------
        */

        tenspickRecalculateProjectProgress(
            $pdo,
            $projectId
        );


        successResponse(
            'Milestone created successfully.',
            [
                'milestone_id' =>
                    $milestoneId,

                'project_id' =>
                    $projectId
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK CREATE PROJECT MILESTONE DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to create milestone because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK CREATE PROJECT MILESTONE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to create milestone.',
            null,
            500
        );
    }
}


/* ============================================================
   UPDATE PROJECT MILESTONE
   ============================================================ */

/**
 * PUT /api/projects/{projectId}/milestones/{milestoneId}
 */
function updateProjectMilestone(
    int $projectId,
    int $milestoneId
): void {

    requireAdminAuthentication();
    requireCsrfToken();


    if (
        $projectId <= 0 ||
        $milestoneId <= 0
    ) {

        errorResponse(
            'Invalid project or milestone ID.',
            null,
            400
        );
    }


    $data =
        tenspickProjectRequestData();


    try {

        $pdo =
            db();


        /*
        |--------------------------------------------------------------------------
        | Existing milestone
        |--------------------------------------------------------------------------
        */

        $existingStatement =
            $pdo->prepare(
                "
                SELECT *

                FROM project_milestones

                WHERE
                    id = :id
                    AND project_id = :project_id

                LIMIT 1
                "
            );


        $existingStatement->execute([

            ':id' =>
                $milestoneId,

            ':project_id' =>
                $projectId
        ]);


        $existing =
            $existingStatement->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$existing
        ) {

            notFoundResponse(
                'Milestone not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Values
        |--------------------------------------------------------------------------
        */

        $milestoneName =
            array_key_exists(
                'milestone_name',
                $data
            )
            ? trim(
                (string) $data['milestone_name']
            )
            : (string) $existing['milestone_name'];


        $description =
            array_key_exists(
                'description',
                $data
            )
            ? trim(
                (string) $data['description']
            )
            : (string) (
                $existing['description']
                ?? ''
            );


        $sequenceNo =
            array_key_exists(
                'sequence_no',
                $data
            )
            ? (int) $data['sequence_no']
            : (int) $existing['sequence_no'];


        $status =
            array_key_exists(
                'status',
                $data
            )
            ? tenspickNormalizeProjectMilestoneStatus(
                $data['status']
            )
            : (string) $existing['status'];


        $progress =
            array_key_exists(
                'progress_percentage',
                $data
            )
            ? tenspickNormalizeProjectProgress(
                $data['progress_percentage']
            )
            : tenspickNormalizeProjectProgress(
                $existing['progress_percentage']
            );


        $startDate =
            array_key_exists(
                'start_date',
                $data
            )
            ? trim(
                (string) $data['start_date']
            )
            : (string) (
                $existing['start_date']
                ?? ''
            );


        $expectedCompletion =
            array_key_exists(
                'expected_completion',
                $data
            )
            ? trim(
                (string) $data['expected_completion']
            )
            : (string) (
                $existing['expected_completion']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (
            $milestoneName === ''
        ) {

            errorResponse(
                'Milestone name is required.',
                null,
                422
            );
        }


        if (
            mb_strlen(
                $milestoneName
            ) > 200
        ) {

            errorResponse(
                'Milestone name cannot exceed 200 characters.',
                null,
                422
            );
        }


        if (
            $sequenceNo <= 0
        ) {

            errorResponse(
                'Sequence number must be greater than zero.',
                null,
                422
            );
        }


        if (
            !in_array(
                $status,
                tenspickProjectMilestoneAllowedStatuses(),
                true
            )
        ) {

            errorResponse(
                'Invalid milestone status.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        if (
            $startDate !== '' &&
            !tenspickValidateProjectDate(
                $startDate
            )
        ) {

            errorResponse(
                'Invalid milestone start date.',
                null,
                422
            );
        }


        if (
            $expectedCompletion !== '' &&
            !tenspickValidateProjectDate(
                $expectedCompletion
            )
        ) {

            errorResponse(
                'Invalid milestone expected completion date.',
                null,
                422
            );
        }


        if (
            $startDate !== '' &&
            $expectedCompletion !== '' &&
            $expectedCompletion < $startDate
        ) {

            errorResponse(
                'Milestone expected completion cannot be before the start date.',
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Completed state
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'completed'
        ) {

            $progress =
                100;


            $completedAt =
                $existing['completed_at']
                ?: date(
                    'Y-m-d H:i:s'
                );

        } else {

            $completedAt =
                null;
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $statement =
            $pdo->prepare(
                "
                UPDATE project_milestones

                SET

                    milestone_name =
                        :milestone_name,

                    description =
                        :description,

                    sequence_no =
                        :sequence_no,

                    status =
                        :status,

                    progress_percentage =
                        :progress_percentage,

                    start_date =
                        :start_date,

                    expected_completion =
                        :expected_completion,

                    completed_at =
                        :completed_at

                WHERE

                    id = :id

                    AND project_id =
                        :project_id

                LIMIT 1
                "
            );


        $statement->execute([

            ':milestone_name' =>
                $milestoneName,

            ':description' =>
                $description !== ''
                ? $description
                : null,

            ':sequence_no' =>
                $sequenceNo,

            ':status' =>
                $status,

            ':progress_percentage' =>
                $progress,

            ':start_date' =>
                $startDate !== ''
                ? $startDate
                : null,

            ':expected_completion' =>
                $expectedCompletion !== ''
                ? $expectedCompletion
                : null,

            ':completed_at' =>
                $completedAt,

            ':id' =>
                $milestoneId,

            ':project_id' =>
                $projectId
        ]);


        /*
        |--------------------------------------------------------------------------
        | Recalculate project
        |--------------------------------------------------------------------------
        */

        $newProgress =
            tenspickRecalculateProjectProgress(
                $pdo,
                $projectId
            );


        successResponse(
            'Milestone updated successfully.',
            [
                'milestone_id' =>
                    $milestoneId,

                'project_id' =>
                    $projectId,

                'project_progress' =>
                    $newProgress
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK UPDATE PROJECT MILESTONE DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to update milestone because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK UPDATE PROJECT MILESTONE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to update milestone.',
            null,
            500
        );
    }
}


/* ============================================================
   DELETE PROJECT MILESTONE
   ============================================================ */

/**
 * DELETE /api/projects/{projectId}/milestones/{milestoneId}
 */
function deleteProjectMilestone(
    int $projectId,
    int $milestoneId
): void {

    requireAdminAuthentication();
    requireCsrfToken();


    if (
        $projectId <= 0 ||
        $milestoneId <= 0
    ) {

        errorResponse(
            'Invalid project or milestone ID.',
            null,
            400
        );
    }


    try {

        $pdo =
            db();


        /*
        |--------------------------------------------------------------------------
        | Check milestone
        |--------------------------------------------------------------------------
        */

        $check =
            $pdo->prepare(
                "
                SELECT
                    id,
                    milestone_name

                FROM project_milestones

                WHERE
                    id = :id
                    AND project_id = :project_id

                LIMIT 1
                "
            );


        $check->execute([

            ':id' =>
                $milestoneId,

            ':project_id' =>
                $projectId
        ]);


        $milestone =
            $check->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$milestone
        ) {

            notFoundResponse(
                'Milestone not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $delete =
            $pdo->prepare(
                "
                DELETE FROM project_milestones

                WHERE
                    id = :id
                    AND project_id = :project_id

                LIMIT 1
                "
            );


        $delete->execute([

            ':id' =>
                $milestoneId,

            ':project_id' =>
                $projectId
        ]);


        if (
            $delete->rowCount() !== 1
        ) {

            throw new RuntimeException(
                'Milestone could not be deleted.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Recalculate project
        |--------------------------------------------------------------------------
        */

        $newProgress =
            tenspickRecalculateProjectProgress(
                $pdo,
                $projectId
            );


        successResponse(
            'Milestone deleted successfully.',
            [
                'project_id' =>
                    $projectId,

                'milestone_id' =>
                    $milestoneId,

                'project_progress' =>
                    $newProgress
            ]
        );

    } catch (PDOException $exception) {

        error_log(
            'TENSPICK DELETE PROJECT MILESTONE DATABASE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to delete milestone because of a database error.',
            null,
            500
        );

    } catch (Throwable $exception) {

        error_log(
            'TENSPICK DELETE PROJECT MILESTONE ERROR: ' .
            $exception->getMessage()
        );


        errorResponse(
            'Unable to delete milestone.',
            null,
            500
        );
    }
}


/* ============================================================
   RECALCULATE PROJECT PROGRESS
   ============================================================ */

/**
 * Overall project progress is the average of all
 * milestone progress percentages.
 *
 * No milestones = 0%.
 */
function tenspickRecalculateProjectProgress(
    PDO $pdo,
    int $projectId
): int {

    if (
        $projectId <= 0
    ) {

        return 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate average
    |--------------------------------------------------------------------------
    */

    $statement =
        $pdo->prepare(
            "
            SELECT

                COUNT(*) AS milestone_count,

                COALESCE(
                    AVG(progress_percentage),
                    0
                ) AS average_progress

            FROM project_milestones

            WHERE project_id = :project_id
            "
        );


    $statement->execute([
        ':project_id' =>
            $projectId
    ]);


    $result =
        $statement->fetch(
            PDO::FETCH_ASSOC
        );


    $milestoneCount =
        (int) (
            $result['milestone_count']
            ?? 0
        );


    if (
        $milestoneCount <= 0
    ) {

        $progress = 0;

    } else {

        $progress =
            (int) round(
                (float) (
                    $result['average_progress']
                    ?? 0
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Clamp
    |--------------------------------------------------------------------------
    */

    $progress =
        tenspickNormalizeProjectProgress(
            $progress
        );


    /*
    |--------------------------------------------------------------------------
    | Update project
    |--------------------------------------------------------------------------
    */

    $update =
        $pdo->prepare(
            "
            UPDATE projects

            SET
                progress_percentage =
                    :progress

            WHERE id = :id

            LIMIT 1
            "
        );


    $update->execute([

        ':progress' =>
            $progress,

        ':id' =>
            $projectId
    ]);


    return $progress;
}