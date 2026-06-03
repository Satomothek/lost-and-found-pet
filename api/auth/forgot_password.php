<?php
/**
 * Forgot Password API Endpoint
 * POST /api/auth/forgot_password.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../lib/functions.php';
require_once '../../lib/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', null, 405);
}

// Get JSON or form data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$email = $input['email'] ?? '';

// Validate input
if (empty($email)) {
    errorResponse('Email tidak boleh kosong', null, 400);
}

// Process forgot password request
$result = forgotPassword($connection, $email);

if ($result['success']) {
    successResponse($result['message']);
} else {
    errorResponse($result['message'], null, 400);
}

closeConnection($connection);

?>
