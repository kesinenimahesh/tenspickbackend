/* ============================================================
   TENSPICK CRM
   LEAD API ROUTES
   ============================================================ */


/*
 * ------------------------------------------------------------
 * GET ALL LEADS
 * ------------------------------------------------------------
 *
 * GET /api/leads
 *
 * Optional:
 *
 * ?search=mahesh
 * ?status=new
 * ?priority=high
 * ?page=1
 * ?per_page=20
 *
 */

if (
    $method === 'GET' &&
    $path === '/api/leads'
) {

    requireAdminAuthentication();

    LeadController::index();

}


/*
 * ------------------------------------------------------------
 * GET SINGLE LEAD
 * ------------------------------------------------------------
 *
 * GET /api/leads/{id}
 *
 */

if (
    $method === 'GET' &&
    preg_match(
        '#^/api/leads/([0-9]+)$#',
        $path,
        $matches
    )
) {

    requireAdminAuthentication();

    LeadController::show(
        (int) $matches[1]
    );

}


/*
 * ------------------------------------------------------------
 * CREATE LEAD
 * ------------------------------------------------------------
 *
 * POST /api/leads
 *
 */

if (
    $method === 'POST' &&
    $path === '/api/leads'
) {

    requireAdminAuthentication();

    requireCsrfToken();

    LeadController::store();

}


/*
 * ------------------------------------------------------------
 * UPDATE LEAD
 * ------------------------------------------------------------
 *
 * PUT /api/leads/{id}
 *
 */

if (
    $method === 'PUT' &&
    preg_match(
        '#^/api/leads/([0-9]+)$#',
        $path,
        $matches
    )
) {

    requireAdminAuthentication();

    requireCsrfToken();

    LeadController::update(
        (int) $matches[1]
    );

}


/*
 * ------------------------------------------------------------
 * DELETE LEAD
 * ------------------------------------------------------------
 *
 * DELETE /api/leads/{id}
 *
 */

if (
    $method === 'DELETE' &&
    preg_match(
        '#^/api/leads/([0-9]+)$#',
        $path,
        $matches
    )
) {

    requireAdminAuthentication();

    requireCsrfToken();

    LeadController::destroy(
        (int) $matches[1]
    );

}