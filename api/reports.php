<?php
/**
 * Pet Reports API Endpoints
 * GET, POST, PUT, DELETE /api/reports.php
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireLogin();
$currentUser = getCurrentUser();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ========== GET REPORTS ==========
if ($method === 'GET') {
    $search = sanitizeInput($_GET['search'] ?? '');
    $type = sanitizeInput($_GET['type'] ?? '');
    $page = intval($_GET['page'] ?? 1);
    
    $pagination = getPagination($page, 12);
    
    $query = "SELECT pr.*, users.name as author, users.avatar_url as authorImg,
              (SELECT COUNT(*) FROM likes WHERE report_id = pr.id) as likes,
              (SELECT COUNT(*) FROM likes WHERE report_id = pr.id AND user_id = ?) as isLiked
              FROM pet_reports pr
              JOIN users ON pr.user_id = users.id
              WHERE pr.status = 'active'";
    
    $params = [$currentUser['id']];
    
    if ($type && in_array($type, ['lost', 'found'])) {
        $query .= " AND pr.type = ?";
        $params[] = $type;
    }
    
    if ($search) {
        $query .= " AND (pr.pet_name LIKE ? OR pr.description LIKE ? OR pr.location LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $pagination['limit'];
    $params[] = $pagination['offset'];
    
    $reports = fetchAll($connection, $query, $params);
    
    if (!$reports) {
        $reports = [];
    }
    
    // Format response
    $reports = array_map(function($report) {
        return [
            'id' => $report['id'],
            'type' => $report['type'],
            'author' => $report['author'],
            'authorImg' => $report['authorImg'],
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'location' => $report['location'],
            'date' => timeAgo($report['created_at']),
            'desc' => $report['description'],
            'image' => $report['image_url'],
            'likes' => intval($report['likes']),
            'isLiked' => boolval($report['isLiked'])
        ];
    }, $reports);
    
    successResponse('Data laporan hewan berhasil diambil', ['reports' => $reports, 'page' => $pagination['page']]);
}

// ========== CREATE REPORT ==========
elseif ($method === 'POST' && $action === 'create') {
    $type = sanitizeInput($_POST['type'] ?? '');
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validate
    if (!$type || !$species || !$location || !$description) {
        errorResponse('Semua field wajib diisi', null, 400);
    }
    
    if (!in_array($type, ['lost', 'found'])) {
        errorResponse('Tipe laporan tidak valid', null, 400);
    }
    
    // Handle image upload
    $imageUrl = 'https://via.placeholder.com/600x400?text=Pet+Image';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image'], '../public/uploads/');
        if ($upload['success']) {
            $imageUrl = 'public/uploads/' . $upload['filename'];
        }
    }
    
    // Insert report
    $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, location, description, image_url)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $result = executeQuery($connection, $query, [
        $currentUser['id'],
        $type,
        $petName,
        $species,
        $location,
        $description,
        $imageUrl
    ]);
    
    if ($result['success']) {
        successResponse('Laporan berhasil dibuat', ['report_id' => $result['insert_id']]);
    } else {
        errorResponse('Gagal membuat laporan: ' . $result['error'], null, 500);
    }
}

// ========== UPDATE REPORT ==========
elseif ($method === 'PUT' && $action === 'update') {
    $reportId = intval($_POST['report_id'] ?? $_GET['id'] ?? 0);
    
    // Check ownership
    $checkQuery = "SELECT user_id FROM pet_reports WHERE id = ?";
    $report = fetchOne($connection, $checkQuery, [$reportId]);
    
    if (!$report) {
        errorResponse('Laporan tidak ditemukan', null, 404);
    }
    
    if ($report['user_id'] != $currentUser['id']) {
        errorResponse('Anda tidak memiliki akses untuk mengubah laporan ini', null, 403);
    }
    
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    $query = "UPDATE pet_reports SET pet_name = ?, species = ?, location = ?, description = ? WHERE id = ?";
    $result = executeQuery($connection, $query, [$petName, $species, $location, $description, $reportId]);
    
    if ($result['success']) {
        successResponse('Laporan berhasil diperbarui');
    } else {
        errorResponse('Gagal memperbarui laporan', null, 500);
    }
}

// ========== DELETE REPORT ==========
elseif ($method === 'DELETE' && $action === 'delete') {
    $reportId = intval($_GET['id'] ?? 0);
    
    // Check ownership
    $checkQuery = "SELECT user_id FROM pet_reports WHERE id = ?";
    $report = fetchOne($connection, $checkQuery, [$reportId]);
    
    if (!$report) {
        errorResponse('Laporan tidak ditemukan', null, 404);
    }
    
    if ($report['user_id'] != $currentUser['id']) {
        errorResponse('Anda tidak memiliki akses untuk menghapus laporan ini', null, 403);
    }
    
    $query = "UPDATE pet_reports SET status = 'resolved' WHERE id = ?";
    $result = executeQuery($connection, $query, [$reportId]);
    
    if ($result['success']) {
        successResponse('Laporan berhasil dihapus');
    } else {
        errorResponse('Gagal menghapus laporan', null, 500);
    }
}

else {
    errorResponse('Action tidak valid', null, 400);
}

closeConnection($connection);

?>
