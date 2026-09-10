<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK CRM
 * PHP API ENTRY POINT
 * ============================================================
 *
 * ADMIN API
 *
 * ============================================================
 *
 * AUTH
 * ------------------------------------------------------------
 * POST /api/auth/login
 * POST /api/auth/logout
 * GET  /api/auth/me
 *
 * CLIENT PORTAL AUTH
 * ------------------------------------------------------------
 * POST /api/client-auth/login
 * POST /api/client-auth/logout
 * GET  /api/client-auth/me
 *
 * SECURITY
 * ------------------------------------------------------------
 * GET /api/security/csrf
 *
 * LEADS
 * ------------------------------------------------------------
 * GET    /api/leads
 * GET    /api/leads/{id}
 * POST   /api/leads
 * PUT    /api/leads/{id}
 * DELETE /api/leads/{id}
 *
 * POST /api/leads/{id}/convert
 *
 * CLIENTS
 * ------------------------------------------------------------
 * GET    /api/clients
 * GET    /api/clients/{id}
 * POST   /api/clients
 * PUT    /api/clients/{id}
 * DELETE /api/clients/{id}
 *
 * CLIENT PAYMENTS
 * ------------------------------------------------------------
 * GET    /api/payments
 * GET    /api/payments/{id}
 * POST   /api/payments
 * PUT    /api/payments/{id}
 * DELETE /api/payments/{id}
 *
 * GET /api/clients/{id}/payments
 * GET /api/clients/{id}/payment-projects
 * GET /api/projects/{id}/payments
 *
 * PROJECTS
 * ------------------------------------------------------------
 * GET    /api/projects
 * GET    /api/projects/{id}
 * POST   /api/projects
 * PUT    /api/projects/{id}
 * DELETE /api/projects/{id}
 *
 * PROJECT MILESTONES
 * ------------------------------------------------------------
 * GET    /api/projects/{id}/milestones
 * POST   /api/projects/{id}/milestones
 * PUT    /api/projects/{id}/milestones/{milestone_id}
 * DELETE /api/projects/{id}/milestones/{milestone_id}
 *
 * STAFF PAYMENTS
 * ------------------------------------------------------------
 * GET    /api/staff-payments
 * GET    /api/staff-payments/{id}
 * POST   /api/staff-payments
 * PUT    /api/staff-payments/{id}
 * DELETE /api/staff-payments/{id}
 *
 * GET /api/staff/{id}/payments
 * GET /api/staff/{id}/payment-summary
 *
 * STAFF TASKS
 * ------------------------------------------------------------
 * GET /api/staff/{id}/tasks
 * GET /api/staff/{id}/task-summary
 *
 * STAFF
 * ------------------------------------------------------------
 * GET    /api/staff
 * GET    /api/staff/{id}
 * POST   /api/staff
 * PUT    /api/staff/{id}
 * DELETE /api/staff/{id}
 *
 * TASKS
 * ------------------------------------------------------------
 * GET    /api/tasks
 * GET    /api/tasks/{id}
 * POST   /api/tasks
 * PUT    /api/tasks/{id}
 * DELETE /api/tasks/{id}
 *
 * TASK UPDATES / HISTORY
 * ------------------------------------------------------------
 * GET    /api/tasks/{id}/updates
 * POST   /api/tasks/{id}/updates
 * PUT    /api/tasks/{id}/updates/{update_id}
 * DELETE /api/tasks/{id}/updates/{update_id}
 *
 * ============================================================
 */


/* ============================================================
   APPLICATION BOOTSTRAP
   ============================================================ */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/activity.php';


/* ============================================================
   CONTROLLERS
   ============================================================ */

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/LeadController.php';
require_once __DIR__ . '/../controllers/ClientController.php';
require_once __DIR__ . '/../controllers/ProjectController.php';
require_once __DIR__ . '/../controllers/StaffController.php';
require_once __DIR__ . '/../controllers/TaskController.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/StaffPaymentController.php';
require_once __DIR__ . '/../controllers/ClientPortalController.php';


/* ============================================================
   API HEADERS
   ============================================================ */

$applicationUrl = rtrim(
    (string) env(
        'APP_URL',
        'http://localhost/tenspickk'
    ),
    '/'
);


/* ============================================================
   CORS
   ============================================================ */

header(
    'Access-Control-Allow-Origin: ' .
    $applicationUrl
);

header(
    'Access-Control-Allow-Credentials: true'
);

header(
    'Access-Control-Allow-Headers: ' .
    'Content-Type, X-CSRF-Token, Accept'
);

header(
    'Access-Control-Allow-Methods: ' .
    'GET, POST, PUT, DELETE, OPTIONS'
);


/* ============================================================
   OPTIONS REQUEST
   ============================================================ */

if (
    requestMethod() === 'OPTIONS'
) {
    http_response_code(204);
    exit;
}


/* ============================================================
   REQUEST URI
   ============================================================ */

$requestUri =
    isset($_SERVER['REQUEST_URI'])
        ? (string) $_SERVER['REQUEST_URI']
        : '/';

$requestPath = parse_url(
    $requestUri,
    PHP_URL_PATH
);

if (
    !is_string($requestPath) ||
    $requestPath === ''
) {
    $requestPath = '/';
}


/* ============================================================
   PATH INFO FALLBACK
   ============================================================ */

if (
    isset($_SERVER['PATH_INFO']) &&
    is_string($_SERVER['PATH_INFO']) &&
    $_SERVER['PATH_INFO'] !== ''
) {

    $pathInfo =
        (string) $_SERVER['PATH_INFO'];

    if (
        str_starts_with(
            $pathInfo,
            '/api'
        )
    ) {
        $requestPath = $pathInfo;
    }
}


/* ============================================================
   NORMALIZE API PATH
   ============================================================ */

$apiPosition = strpos(
    $requestPath,
    '/api/'
);

if (
    $apiPosition !== false
) {

    $requestPath =
        substr(
            $requestPath,
            $apiPosition
        );

} elseif (
    $requestPath === '/api'
) {

    $requestPath = '/api';
}


/* ============================================================
   NORMALIZE DUPLICATED API PREFIX
   ============================================================ */

while (
    str_starts_with(
        $requestPath,
        '/api/api/'
    )
) {

    $requestPath =
        substr(
            $requestPath,
            4
        );
}


/* ============================================================
   NORMALIZE TRAILING SLASH
   ============================================================ */

if (
    $requestPath !== '/' &&
    str_ends_with(
        $requestPath,
        '/'
    )
) {

    $requestPath =
        rtrim(
            $requestPath,
            '/'
        );
}


/* ============================================================
   REQUEST METHOD
   ============================================================ */

$method =
    requestMethod();


/* ============================================================
   DEBUG LOG
   ============================================================ */

if (
    defined('TENSPICK_DEBUG') &&
    TENSPICK_DEBUG
) {

    error_log(
        '[Tenspick API] ' .
        $method .
        ' ' .
        $requestPath
    );
}


/* ============================================================
   API ROOT
   ============================================================ */

if (
    $requestPath === '/api'
) {

    successResponse(
        'Tenspick API is running.',
        [
            'application' =>
                TENSPICK_APP_NAME,

            'environment' =>
                TENSPICK_ENV,

            'version' =>
                '1.0.0'
        ]
    );

    exit;
}


/* ============================================================
   API HEALTH
   ============================================================ */

if (
    $requestPath === '/api/health'
) {

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    successResponse(
        'Tenspick API is healthy.',
        [
            'application' =>
                TENSPICK_APP_NAME,

            'database' =>
                'configured',

            'time' =>
                date('Y-m-d H:i:s')
        ]
    );

    exit;
}


/* ============================================================
   CSRF TOKEN
   ============================================================ */

if (
    $requestPath === '/api/security/csrf'
) {

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    successResponse(
        'Security token generated.',
        [
            'token' =>
                getCsrfToken()
        ]
    );

    exit;
}


/* ============================================================
   ADMIN LOGIN
   ============================================================ */

if (
    $requestPath === '/api/auth/login'
) {

    if (
        $method !== 'POST'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    adminLogin();

    exit;
}


/* ============================================================
   ADMIN LOGOUT
   ============================================================ */

if (
    $requestPath === '/api/auth/logout'
) {

    if (
        $method !== 'POST'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    adminLogout();

    exit;
}


/* ============================================================
   CURRENT ADMIN
   ============================================================ */

if (
    $requestPath === '/api/auth/me'
) {

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    currentAdmin();

    exit;
}


/* ============================================================
   CLIENT PORTAL AUTHENTICATION
   ============================================================
 *
 * IMPORTANT:
 *
 * These routes MUST be handled BEFORE:
 *
 * /api/clients
 * /api/clients/{id}
 *
 * They are authentication endpoints and must NOT be allowed
 * to fall through to the normal Client CRUD routes.
 *
 * ============================================================
 */


/* ============================================================
   CLIENT LOGIN
   ============================================================ */

if (
    $requestPath === '/api/client-auth/login'
) {

    if (
        $method !== 'POST'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    clientLogin();

    exit;
}


/* ============================================================
   CLIENT LOGOUT
   ============================================================ */

if (
    $requestPath === '/api/client-auth/logout'
) {

    if (
        $method !== 'POST'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    clientLogout();

    exit;
}


/* ============================================================
   CURRENT CLIENT SESSION
   ============================================================ */

if (
    $requestPath === '/api/client-auth/me'
) {

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    getClientMe();

    exit;
}


/* ============================================================
   LEAD ROUTES
   ============================================================ */

$isLeadsCollection =
    $requestPath === '/api/leads';

$isLeadSingle =
    preg_match(
        '#^/api/leads/([0-9]+)$#',
        $requestPath,
        $leadMatches
    );

$isLeadConversion =
    preg_match(
        '#^/api/leads/([0-9]+)/convert$#',
        $requestPath,
        $conversionMatches
    );


/* ============================================================
   LEAD CONVERSION
   ============================================================ */

if (
    $isLeadConversion
) {

    $leadId =
        (int) $conversionMatches[1];

    if (
        $leadId <= 0
    ) {

        errorResponse(
            'Invalid lead ID.',
            null,
            400
        );

        exit;
    }

    if (
        $method !== 'POST'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    convertLeadToClient(
        $leadId
    );

    exit;
}


/* ============================================================
   LEAD ID
   ============================================================ */

$leadId = null;

if (
    $isLeadSingle
) {

    $leadId =
        (int) $leadMatches[1];
}


/* ============================================================
   LEAD COLLECTION
   ============================================================ */

if (
    $isLeadsCollection
) {

    if (
        $method === 'GET'
    ) {

        getLeads();

        exit;
    }

    if (
        $method === 'POST'
    ) {

        createLead();

        exit;
    }

    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   LEAD SINGLE
   ============================================================ */

if (
    $leadId !== null
) {

    if (
        $method === 'GET'
    ) {

        getLead(
            $leadId
        );

        exit;
    }

    if (
        $method === 'PUT'
    ) {

        updateLead(
            $leadId
        );

        exit;
    }

    if (
        $method === 'DELETE'
    ) {

        deleteLead(
            $leadId
        );

        exit;
    }

    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}

/* ============================================================
   CLIENT PORTAL — PROJECTS
   ============================================================ */

/*
 * GET /api/client-portal/projects
 */

if (
    $requestPath === '/api/client-portal/projects'
) {

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $clientPortalController =
        new ClientPortalController();


    $clientPortalController->projects();

    exit;
}


/*
 * GET /api/client-portal/projects/{id}/progress
 */

$isClientPortalProjectProgress =
    preg_match(
        '#^/api/client-portal/projects/([0-9]+)/progress$#',
        $requestPath,
        $clientPortalProgressMatches
    );


if (
    $isClientPortalProjectProgress
) {

    $projectId =
        (int) $clientPortalProgressMatches[1];


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $clientPortalController =
        new ClientPortalController();


    $clientPortalController->progress(
        $projectId
    );

    exit;
}


/*
 * GET /api/client-portal/projects/{id}/milestones
 */

$isClientPortalProjectMilestones =
    preg_match(
        '#^/api/client-portal/projects/([0-9]+)/milestones$#',
        $requestPath,
        $clientPortalMilestoneMatches
    );


if (
    $isClientPortalProjectMilestones
) {

    $projectId =
        (int) $clientPortalMilestoneMatches[1];


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $clientPortalController =
        new ClientPortalController();


    $clientPortalController->milestones(
        $projectId
    );

    exit;
}


/*
 * GET /api/client-portal/projects/{id}/payments
 */

$isClientPortalProjectPayments =
    preg_match(
        '#^/api/client-portal/projects/([0-9]+)/payments$#',
        $requestPath,
        $clientPortalProjectPaymentMatches
    );


if (
    $isClientPortalProjectPayments
) {

    $projectId =
        (int) $clientPortalProjectPaymentMatches[1];


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $clientPortalController =
        new ClientPortalController();


    $clientPortalController->projectPayments(
        $projectId
    );

    exit;
}


/*
 * GET /api/client-portal/projects/{id}
 *
 * IMPORTANT:
 * This must come AFTER the more specific:
 *
 * /progress
 * /milestones
 * /payments
 */

$isClientPortalProject =
    preg_match(
        '#^/api/client-portal/projects/([0-9]+)$#',
        $requestPath,
        $clientPortalProjectMatches
    );


if (
    $isClientPortalProject
) {

    $projectId =
        (int) $clientPortalProjectMatches[1];


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $clientPortalController =
        new ClientPortalController();


    $clientPortalController->project(
        $projectId
    );

    exit;
}


/* ============================================================
   CLIENT PORTAL — PAYMENTS
   ============================================================ */

/*
 * GET /api/client-portal/payments
 */

if (
    $requestPath === '/api/client-portal/payments'
) {

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $clientPortalController =
        new ClientPortalController();


    $clientPortalController->payments();

    exit;
}


/* ============================================================
   CLIENT PAYMENT ROUTES
   ============================================================
 *
 * MUST COME BEFORE:
 *
 * /api/clients/{id}
 *
 * ============================================================
 */


/* ============================================================
   CLIENT PAYMENT HISTORY
   ============================================================ */

$isClientPayments =
    preg_match(
        '#^/api/clients/([0-9]+)/payments$#',
        $requestPath,
        $clientPaymentMatches
    );

if (
    $isClientPayments
) {

    $clientId =
        (int) $clientPaymentMatches[1];

    if (
        $clientId <= 0
    ) {

        errorResponse(
            'Invalid client ID.',
            null,
            400
        );

        exit;
    }

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    $paymentController =
        new PaymentController();

    $paymentController->clientPayments(
        $clientId
    );

    exit;
}


/* ============================================================
   CLIENT PAYMENT PROJECTS
   ============================================================ */

$isClientPaymentProjects =
    preg_match(
        '#^/api/clients/([0-9]+)/payment-projects$#',
        $requestPath,
        $clientPaymentProjectMatches
    );

if (
    $isClientPaymentProjects
) {

    $clientId =
        (int) $clientPaymentProjectMatches[1];

    if (
        $clientId <= 0
    ) {

        errorResponse(
            'Invalid client ID.',
            null,
            400
        );

        exit;
    }

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    $paymentController =
        new PaymentController();

    $paymentController->clientProjects(
        $clientId
    );

    exit;
}


/* ============================================================
   PROJECT PAYMENT HISTORY
   ============================================================ */

$isProjectPayments =
    preg_match(
        '#^/api/projects/([0-9]+)/payments$#',
        $requestPath,
        $projectPaymentMatches
    );

if (
    $isProjectPayments
) {

    $projectId =
        (int) $projectPaymentMatches[1];

    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }

    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }

    $paymentController =
        new PaymentController();

    $paymentController->projectPayments(
        $projectId
    );

    exit;
}


/* ============================================================
   CLIENT PAYMENT COLLECTION
   ============================================================ */

if (
    $requestPath === '/api/payments'
) {

    $paymentController =
        new PaymentController();


    if (
        $method === 'GET'
    ) {

        $paymentController->index();

        exit;
    }


    if (
        $method === 'POST'
    ) {

        $paymentController->store();

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   CLIENT PAYMENT SINGLE
   ============================================================ */

$isPaymentSingle =
    preg_match(
        '#^/api/payments/([0-9]+)$#',
        $requestPath,
        $paymentMatches
    );

if (
    $isPaymentSingle
) {

    $paymentId =
        (int) $paymentMatches[1];

    if (
        $paymentId <= 0
    ) {

        errorResponse(
            'Invalid payment ID.',
            null,
            400
        );

        exit;
    }


    $paymentController =
        new PaymentController();


    if (
        $method === 'GET'
    ) {

        $paymentController->show(
            $paymentId
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        $paymentController->update(
            $paymentId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        $paymentController->destroy(
            $paymentId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   CLIENT ROUTES
   ============================================================ */

$isClientsCollection =
    $requestPath === '/api/clients';

$isClientSingle =
    preg_match(
        '#^/api/clients/([0-9]+)$#',
        $requestPath,
        $clientMatches
    );


/* ============================================================
   CLIENT COLLECTION
   ============================================================ */

if (
    $isClientsCollection
) {

    if (
        $method === 'GET'
    ) {

        getClients();

        exit;
    }


    if (
        $method === 'POST'
    ) {

        createClient();

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   CLIENT SINGLE
   ============================================================ */

if (
    $isClientSingle
) {

    $clientId =
        (int) $clientMatches[1];

    if (
        $clientId <= 0
    ) {

        errorResponse(
            'Invalid client ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getClient(
            $clientId
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        updateClient(
            $clientId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        deleteClient(
            $clientId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   PROJECT MILESTONE COLLECTION
   ============================================================ */

$isProjectMilestones =
    preg_match(
        '#^/api/projects/([0-9]+)/milestones$#',
        $requestPath,
        $projectMilestoneMatches
    );

if (
    $isProjectMilestones
) {

    $projectId =
        (int) $projectMilestoneMatches[1];

    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getProjectMilestones(
            $projectId
        );

        exit;
    }


    if (
        $method === 'POST'
    ) {

        createProjectMilestone(
            $projectId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   PROJECT MILESTONE SINGLE
   ============================================================ */

$isProjectMilestoneSingle =
    preg_match(
        '#^/api/projects/([0-9]+)/milestones/([0-9]+)$#',
        $requestPath,
        $projectMilestoneSingleMatches
    );

if (
    $isProjectMilestoneSingle
) {

    $projectId =
        (int) $projectMilestoneSingleMatches[1];

    $milestoneId =
        (int) $projectMilestoneSingleMatches[2];


    if (
        $projectId <= 0 ||
        $milestoneId <= 0
    ) {

        errorResponse(
            'Invalid project or milestone ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        updateProjectMilestone(
            $projectId,
            $milestoneId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        deleteProjectMilestone(
            $projectId,
            $milestoneId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   PROJECT COLLECTION
   ============================================================ */

if (
    $requestPath === '/api/projects'
) {

    if (
        $method === 'GET'
    ) {

        getProjects();

        exit;
    }


    if (
        $method === 'POST'
    ) {

        createProject();

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   PROJECT SINGLE
   ============================================================ */

$isProjectSingle =
    preg_match(
        '#^/api/projects/([0-9]+)$#',
        $requestPath,
        $projectMatches
    );

if (
    $isProjectSingle
) {

    $projectId =
        (int) $projectMatches[1];


    if (
        $projectId <= 0
    ) {

        errorResponse(
            'Invalid project ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getProject(
            $projectId
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        updateProject(
            $projectId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        deleteProject(
            $projectId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   STAFF PAYMENT ROUTES
   ============================================================
 *
 * IMPORTANT:
 *
 * These routes MUST come before:
 *
 * /api/staff/{id}
 *
 * The controller is instantiated ONLY when a Staff Payment
 * route actually matches.
 *
 * This prevents StaffPaymentController authentication/database
 * initialization from affecting unrelated API requests.
 *
 * ============================================================
 */


/* ============================================================
   STAFF PAYMENT HISTORY
   ============================================================ */

$isStaffPaymentHistory =
    preg_match(
        '#^/api/staff/([0-9]+)/payments$#',
        $requestPath,
        $staffPaymentHistoryMatches
    );

if (
    $isStaffPaymentHistory
) {

    $staffId =
        (int) $staffPaymentHistoryMatches[1];


    if (
        $staffId <= 0
    ) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $staffPaymentController =
        new StaffPaymentController();


    $staffPaymentController->staffPayments(
        $staffId
    );

    exit;
}


/* ============================================================
   STAFF PAYMENT SUMMARY
   ============================================================ */

$isStaffPaymentSummary =
    preg_match(
        '#^/api/staff/([0-9]+)/payment-summary$#',
        $requestPath,
        $staffPaymentSummaryMatches
    );

if (
    $isStaffPaymentSummary
) {

    $staffId =
        (int) $staffPaymentSummaryMatches[1];


    if (
        $staffId <= 0
    ) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method !== 'GET'
    ) {

        errorResponse(
            'Method not allowed.',
            null,
            405
        );

        exit;
    }


    $staffPaymentController =
        new StaffPaymentController();


    $staffPaymentController->staffPaymentSummary(
        $staffId
    );

    exit;
}


/* ============================================================
   STAFF PAYMENT COLLECTION
   ============================================================ */

if (
    $requestPath === '/api/staff-payments'
) {

    $staffPaymentController =
        new StaffPaymentController();


    if (
        $method === 'GET'
    ) {

        $staffPaymentController->index();

        exit;
    }


    if (
        $method === 'POST'
    ) {

        $staffPaymentController->store();

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   STAFF PAYMENT SINGLE
   ============================================================ */

$isStaffPaymentSingle =
    preg_match(
        '#^/api/staff-payments/([0-9]+)$#',
        $requestPath,
        $staffPaymentMatches
    );

if (
    $isStaffPaymentSingle
) {

    $staffPaymentId =
        (int) $staffPaymentMatches[1];


    if (
        $staffPaymentId <= 0
    ) {

        errorResponse(
            'Invalid staff payment ID.',
            null,
            400
        );

        exit;
    }


    $staffPaymentController =
        new StaffPaymentController();


    if (
        $method === 'GET'
    ) {

        $staffPaymentController->show(
            $staffPaymentId
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        $staffPaymentController->update(
            $staffPaymentId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        $staffPaymentController->destroy(
            $staffPaymentId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   STAFF TASK ROUTES
   ============================================================
 *
 * MUST COME BEFORE:
 *
 * /api/staff/{id}
 *
 * ============================================================
 */


/* ============================================================
   STAFF TASK LIST
   ============================================================ */

$isStaffTasks =
    preg_match(
        '#^/api/staff/([0-9]+)/tasks$#',
        $requestPath,
        $staffTaskMatches
    );

if (
    $isStaffTasks
) {

    $staffId =
        (int) $staffTaskMatches[1];


    if (
        $staffId <= 0
    ) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getStaffTasks(
            $staffId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   STAFF TASK SUMMARY
   ============================================================ */

$isStaffTaskSummary =
    preg_match(
        '#^/api/staff/([0-9]+)/task-summary$#',
        $requestPath,
        $staffTaskSummaryMatches
    );

if (
    $isStaffTaskSummary
) {

    $staffId =
        (int) $staffTaskSummaryMatches[1];


    if (
        $staffId <= 0
    ) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getStaffTaskSummary(
            $staffId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   STAFF COLLECTION
   ============================================================ */

if (
    $requestPath === '/api/staff'
) {

    if (
        $method === 'GET'
    ) {

        getStaff();

        exit;
    }


    if (
        $method === 'POST'
    ) {

        createStaff();

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   STAFF SINGLE
   ============================================================ */

$isStaffSingle =
    preg_match(
        '#^/api/staff/([0-9]+)$#',
        $requestPath,
        $staffMatches
    );

if (
    $isStaffSingle
) {

    $staffId =
        (int) $staffMatches[1];


    if (
        $staffId <= 0
    ) {

        errorResponse(
            'Invalid staff ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getStaffById(
            $staffId
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        updateStaff(
            $staffId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        deleteStaff(
            $staffId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   TASK COLLECTION
   ============================================================ */

if (
    $requestPath === '/api/tasks'
) {

    if (
        $method === 'GET'
    ) {

        getTasks();

        exit;
    }


    if (
        $method === 'POST'
    ) {

        createTask();

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   TASK UPDATES / HISTORY COLLECTION
   ============================================================ */

$isTaskUpdates =
    preg_match(
        '#^/api/tasks/([0-9]+)/updates$#',
        $requestPath,
        $taskUpdateMatches
    );

if (
    $isTaskUpdates
) {

    $taskId =
        (int) $taskUpdateMatches[1];


    if (
        $taskId <= 0
    ) {

        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getTaskUpdates(
            $taskId
        );

        exit;
    }


    if (
        $method === 'POST'
    ) {

        createTaskUpdate(
            $taskId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   TASK UPDATE / HISTORY SINGLE
   ============================================================ */

$isTaskUpdateSingle =
    preg_match(
        '#^/api/tasks/([0-9]+)/updates/([0-9]+)$#',
        $requestPath,
        $taskUpdateSingleMatches
    );

if (
    $isTaskUpdateSingle
) {

    $taskId =
        (int) $taskUpdateSingleMatches[1];

    $updateId =
        (int) $taskUpdateSingleMatches[2];


    if (
        $taskId <= 0 ||
        $updateId <= 0
    ) {

        errorResponse(
            'Invalid task or update ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        updateTaskUpdate(
            $taskId,
            $updateId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        deleteTaskUpdate(
            $taskId,
            $updateId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   TASK SINGLE
   ============================================================ */

$isTaskSingle =
    preg_match(
        '#^/api/tasks/([0-9]+)$#',
        $requestPath,
        $taskMatches
    );

if (
    $isTaskSingle
) {

    $taskId =
        (int) $taskMatches[1];


    if (
        $taskId <= 0
    ) {

        errorResponse(
            'Invalid task ID.',
            null,
            400
        );

        exit;
    }


    if (
        $method === 'GET'
    ) {

        getTask(
            $taskId
        );

        exit;
    }


    if (
        $method === 'PUT'
    ) {

        updateTask(
            $taskId
        );

        exit;
    }


    if (
        $method === 'DELETE'
    ) {

        deleteTask(
            $taskId
        );

        exit;
    }


    errorResponse(
        'Method not allowed.',
        null,
        405
    );

    exit;
}


/* ============================================================
   ROUTE NOT FOUND
   ============================================================ */

notFoundResponse(
    'API endpoint not found.'
);

exit;