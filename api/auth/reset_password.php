<?php
/**
 * Reset Password API Endpoint
 * POST /api/auth/reset_password.php
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

$token = $input['token'] ?? '';
$password = $input['password'] ?? '';
$passwordConfirm = $input['password_confirm'] ?? '';

// Validate input
if (empty($token) || empty($password) || empty($passwordConfirm)) {
    errorResponse('Semua field harus diisi', null, 400);
}

// Process reset password request
$result = resetPassword($connection, $token, $password, $passwordConfirm);

if ($result['success']) {
    successResponse($result['message']);
} else {
    errorResponse($result['message'], null, 400);
}

closeConnection($connection);

?>
