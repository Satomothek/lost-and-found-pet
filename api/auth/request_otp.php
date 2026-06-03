<?php
/**
 * Request OTP API Endpoint
 * POST /api/auth/request_otp.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../lib/functions.php';
require_once '../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = $input['email'] ?? '';

if (empty($email)) {
    errorResponse('Email tidak boleh kosong', null, 400);
}

$result = requestOTP($connection, $email);

if ($result['success']) {
    successResponse($result['message']);
} else {
    errorResponse($result['message'], null, 400);
}

closeConnection($connection);
?>
