<?php
/**
 * Logout API Endpoint
 * GET/POST /api/logout.php
 */

header('Content-Type: application/json');
require_once '../lib/functions.php';
require_once '../lib/auth.php';

// Logout user
$result = logout();

if ($result['success']) {
    successResponse($result['message']);
} else {
    errorResponse($result['message'], null, 400);
}

?>
