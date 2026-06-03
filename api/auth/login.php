<?php
/**
 * Login API Endpoint
 * POST /api/auth/login.php
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
$password = $input['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    errorResponse('Email dan password tidak boleh kosong', null, 400);
}

// Attempt login
$result = login($connection, $email, $password);

if ($result['success']) {
    successResponse($result['message'], $result['user']);
} else {
    errorResponse($result['message'], null, 401);
}

closeConnection($connection);

?>
