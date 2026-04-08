<?php
/**
 * Likes API Endpoints
 * POST /api/likes.php
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireLogin();
$currentUser = getCurrentUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$reportId = intval($input['report_id'] ?? 0);
$action = sanitizeInput($input['action'] ?? 'toggle');

if (!$reportId) {
    errorResponse('Report ID tidak valid', null, 400);
}

// Check if report exists
$checkQuery = "SELECT id FROM pet_reports WHERE id = ?";
$report = fetchOne($connection, $checkQuery, [$reportId]);

if (!$report) {
    errorResponse('Laporan tidak ditemukan', null, 404);
}

// Check if already liked
$checkLikeQuery = "SELECT id FROM likes WHERE user_id = ? AND report_id = ?";
$liked = fetchOne($connection, $checkLikeQuery, [$currentUser['id'], $reportId]);

if ($action === 'toggle' || $action === 'like') {
    if ($liked) {
        // Already liked, remove like
        $deleteQuery = "DELETE FROM likes WHERE user_id = ? AND report_id = ?";
        $result = executeQuery($connection, $deleteQuery, [$currentUser['id'], $reportId]);
        
        if ($result['success']) {
            successResponse('Like dihapus');
        } else {
            errorResponse('Gagal menghapus like', null, 500);
        }
    } else {
        // Not liked, add like
        $insertQuery = "INSERT INTO likes (user_id, report_id) VALUES (?, ?)";
        $result = executeQuery($connection, $insertQuery, [$currentUser['id'], $reportId]);
        
        if ($result['success']) {
            successResponse('Like ditambahkan');
        } else {
            errorResponse('Gagal menambahkan like', null, 500);
        }
    }
} else {
    errorResponse('Action tidak valid', null, 400);
}

closeConnection($connection);

?>
