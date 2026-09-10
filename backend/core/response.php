<?php

declare(strict_types=1);

/**
 * ============================================================
 * TENSPICK
 * API RESPONSE HANDLER
 * ============================================================
 *
 * This file provides a common JSON response format
 * for every PHP API in the Tenspick system.
 *
 * Standard response:
 *
 * {
 *     "success": true,
 *     "message": "...",
 *     "data": {}
 * }
 *
 * Every future module will use the same response structure:
 *
 * Leads
 * Clients
 * Projects
 * Tasks
 * Staff
 * Payments
 * Chat
 * Website Content
 * Notifications
 * etc.
 * ============================================================
 */


/* ============================================================
   JSON RESPONSE
   ============================================================ */

function jsonResponse(
    bool $success,
    string $message = '',
    $data = null,
    int $statusCode = 200
): void {

    /*
     * Set HTTP status code.
     */

    http_response_code(
        $statusCode
    );


    /*
     * Tell the browser that this is JSON.
     */

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    /*
     * Prevent browser caching of API responses.
     *
     * This is particularly useful for:
     *
     * - Login
     * - User data
     * - Client data
     * - Payments
     * - Chat
     */

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    header(
        'Pragma: no-cache'
    );


    /*
     * Build standard response.
     */

    $response = [
        'success' => $success,

        'message' => $message,

        'data' => $data
    ];


    /*
     * Convert PHP array to JSON.
     */

    $json = json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    /*
     * JSON encoding should normally never fail,
     * but handle the situation safely.
     */

    if ($json === false) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Unable to generate API response.',
            'data' => null
        ]);

        exit;
    }


    /*
     * Send response.
     */

    echo $json;

    exit;
}


/* ============================================================
   SUCCESS RESPONSE
   ============================================================ */

function successResponse(
    string $message = 'Request completed successfully.',
    $data = null,
    int $statusCode = 200
): void {

    jsonResponse(
        true,
        $message,
        $data,
        $statusCode
    );
}


/* ============================================================
   ERROR RESPONSE
   ============================================================ */

function errorResponse(
    string $message = 'Something went wrong.',
    $data = null,
    int $statusCode = 400
): void {

    jsonResponse(
        false,
        $message,
        $data,
        $statusCode
    );
}


/* ============================================================
   VALIDATION ERROR
   ============================================================ */

function validationResponse(
    array $errors,
    string $message = 'Please correct the highlighted fields.'
): void {

    jsonResponse(
        false,
        $message,
        [
            'errors' => $errors
        ],
        422
    );
}


/* ============================================================
   UNAUTHORIZED RESPONSE
   ============================================================ */

function unauthorizedResponse(
    string $message = 'Authentication required.'
): void {

    jsonResponse(
        false,
        $message,
        null,
        401
    );
}


/* ============================================================
   FORBIDDEN RESPONSE
   ============================================================ */

function forbiddenResponse(
    string $message = 'You do not have permission to perform this action.'
): void {

    jsonResponse(
        false,
        $message,
        null,
        403
    );
}


/* ============================================================
   NOT FOUND RESPONSE
   ============================================================ */

function notFoundResponse(
    string $message = 'The requested resource was not found.'
): void {

    jsonResponse(
        false,
        $message,
        null,
        404
    );
}


/* ============================================================
   SERVER ERROR RESPONSE
   ============================================================ */

function serverErrorResponse(
    string $message = 'An unexpected server error occurred.'
): void {

    jsonResponse(
        false,
        $message,
        null,
        500
    );
}