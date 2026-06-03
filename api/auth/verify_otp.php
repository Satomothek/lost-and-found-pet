<?php
/**
 * Verify OTP API Endpoint
 * POST /api/auth/verify_otp.php
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
$otp = $input['otp'] ?? '';

if (empty($email) || empty($otp)) {
    errorResponse('Email dan OTP tidak boleh kosong', null, 400);
}

$result = verifyOTP($connection, $email, $otp);

if ($result['success']) {
    successResponse($result['message'], ['token' => $result['token']]);
} else {
    errorResponse($result['message'], null, 400);
}

closeConnection($connection);
?>
