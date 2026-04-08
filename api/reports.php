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

// GET REPORTS
if ($method === 'GET') {
    $reportId = intval($_GET['id'] ?? 0);
    
    // Jika ada ID, ambil detail laporan tunggal
    if ($reportId > 0) {
        $query = "SELECT pr.*, users.name as author, users.avatar_url as authorImg,
                  (SELECT COUNT(*) FROM likes WHERE report_id = pr.id) as likes,
                  (SELECT COUNT(*) FROM likes WHERE report_id = pr.id AND user_id = ?) as isLiked
                  FROM pet_reports pr
                  JOIN users ON pr.user_id = users.id
                  WHERE pr.id = ? AND pr.status = 'active'";
        
        $report = fetchOne($connection, $query, [$currentUser['id'], $reportId]);
        
        if (!$report) {
            errorResponse('Laporan tidak ditemukan', null, 404);
        }
        
        // Format response untuk detail
        $isEdited = !empty($report['updated_at']) && $report['updated_at'] !== $report['created_at'];
        $formattedReport = [
            'id' => $report['id'],
            'type' => $report['type'],
            'author' => $report['author'] ?: 'Anonim',
            'authorImg' => normalizeAssetUrl($report['authorImg'] ?: 'https://i.pravatar.cc/150?img=68'),
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'speciesDetail' => $report['species_detail'] ?? null,
            'location' => $report['location'],
            'description' => $report['description'],
            'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
            'eventDate' => $report['event_date'] ? date('d M Y', strtotime($report['event_date'])) : null,
            'createdAt' => timeAgo($report['created_at']),
            'updatedAt' => $isEdited ? timeAgo($report['updated_at']) : null,
            'created_at' => $report['created_at'],
            'updated_at' => $isEdited ? $report['updated_at'] : null,
            'isEdited' => $isEdited,
            'likes' => intval($report['likes']),
            'isLiked' => boolval($report['isLiked']),
            'isOwner' => $report['user_id'] == $currentUser['id']
        ];
        
        successResponse('Detail laporan berhasil diambil', $formattedReport);
    }
    
    // Jika tidak ada ID, ambil list laporan
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
        $query .= " AND (pr.pet_name LIKE ? OR pr.description LIKE ? OR pr.location LIKE ? OR pr.species LIKE ? OR pr.species_detail LIKE ? )";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $pagination['limit'];
    $params[] = $pagination['offset'];
    
    $reports = fetchAll($connection, $query, $params);
    
    if ($reports === false) {
        errorResponse('Gagal memuat laporan: ' . getLastError($connection), null, 500);
    }
    
    if (!$reports) {
        $reports = [];
    }
    
    // Format response
    $reports = array_map(function($report) {
        // Gunakan event_date jika ada, fallback ke created_at
        $dateToFormat = !empty($report['event_date']) ? $report['event_date'] : $report['created_at'];
        $dateObj = new DateTime($dateToFormat);
        $formattedDate = $dateObj->format('d/m/Y');
        
        $isEdited = !empty($report['updated_at']) && $report['updated_at'] !== $report['created_at'];
        return [
            'id' => $report['id'],
            'type' => $report['type'],
            'author' => $report['author'] ?: 'Anonim',
            'authorImg' => normalizeAssetUrl($report['authorImg'] ?: 'https://i.pravatar.cc/150?img=68'),
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'speciesDetail' => $report['species_detail'] ?? null,
            'location' => $report['location'],
            'date' => $formattedDate,
            'eventDate' => $report['event_date'] ?? null,
            'createdRelative' => timeAgo($report['created_at']),
            'updatedRelative' => $isEdited ? timeAgo($report['updated_at']) : null,
            'created_at' => $report['created_at'],
            'updated_at' => $isEdited ? $report['updated_at'] : null,
            'isEdited' => $isEdited,
            'rawDate' => $report['created_at'],
            'desc' => $report['description'],
            'description' => $report['description'],
            'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
            'likes' => intval($report['likes']),
            'isLiked' => boolval($report['isLiked']),
            'user_id' => $report['user_id']
        ];
    }, $reports);
    
    successResponse('Data laporan hewan berhasil diambil', ['reports' => $reports, 'page' => $pagination['page']]);
}

// CREATE REPORT
elseif ($method === 'POST' && $action === 'create') {
    $type = sanitizeInput($_POST['type'] ?? '');
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $speciesDetail = sanitizeInput($_POST['species_detail'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $reportDate = sanitizeInput($_POST['date'] ?? '');
    
    // Validate
    if (!$type || !$species || !$location || !$description || !$reportDate) {
        errorResponse('Semua field wajib diisi', null, 400);
    }
    
    if (!in_array($type, ['lost', 'found'])) {
        errorResponse('Tipe laporan tidak valid', null, 400);
    }

    $today = date('Y-m-d');
    $minDate = date('Y-m-d', strtotime('-7 days'));

    if ($reportDate < $minDate || $reportDate > $today) {
        errorResponse('Tanggal harus antara ' . $minDate . ' sampai ' . $today, null, 400);
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
    $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, species_detail, location, description, image_url, event_date)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = executeQuery($connection, $query, [
        $currentUser['id'],
        $type,
        $petName,
        $species,
        $speciesDetail,
        $location,
        $description,
        $imageUrl,
        $reportDate
    ]);
    
    if ($result['success']) {
        successResponse('Laporan berhasil dibuat', ['report_id' => $result['insert_id']]);
    } else {
        errorResponse('Gagal membuat laporan: ' . $result['error'], null, 500);
    }
}

// UPDATE REPORT
elseif (($method === 'PUT' || ($method === 'POST' && $action === 'update')) && $action === 'update') {
    $reportId = intval($_POST['report_id'] ?? $_GET['id'] ?? 0);
    
    // Check ownership
    $checkQuery = "SELECT user_id, image_url FROM pet_reports WHERE id = ?";
    $report = fetchOne($connection, $checkQuery, [$reportId]);
    
    if (!$report) {
        errorResponse('Laporan tidak ditemukan', null, 404);
    }
    
    if ($report['user_id'] != $currentUser['id']) {
        errorResponse('Anda tidak memiliki akses untuk mengubah laporan ini', null, 403);
    }
    
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $speciesDetail = sanitizeInput($_POST['species_detail'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $reportDate = sanitizeInput($_POST['date'] ?? '');
    
    // Handle image upload (optional for update)
    $imageUrl = $report['image_url']; // Keep existing image if no new upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image'], '../public/uploads/');
        if ($upload['success']) {
            $imageUrl = 'public/uploads/' . $upload['filename'];
            // Optionally delete old image file here if needed
        }
    }
    
    // Validate date if provided
    if ($reportDate) {
        $today = date('Y-m-d');
        $minDate = date('Y-m-d', strtotime('-7 days'));
        if ($reportDate < $minDate || $reportDate > $today) {
            errorResponse('Tanggal harus antara ' . $minDate . ' sampai ' . $today, null, 400);
        }
        $query = "UPDATE pet_reports SET pet_name = ?, species = ?, species_detail = ?, location = ?, description = ?, event_date = ?, image_url = ? WHERE id = ?";
        $result = executeQuery($connection, $query, [$petName, $species, $speciesDetail, $location, $description, $reportDate, $imageUrl, $reportId]);
    } else {
        $query = "UPDATE pet_reports SET pet_name = ?, species = ?, species_detail = ?, location = ?, description = ?, image_url = ? WHERE id = ?";
        $result = executeQuery($connection, $query, [$petName, $species, $speciesDetail, $location, $description, $imageUrl, $reportId]);
    }
    
    if ($result['success']) {
        successResponse('Laporan berhasil diperbarui');
    } else {
        errorResponse('Gagal memperbarui laporan', null, 500);
    }
}

// DELETE REPORT
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

// PATCH REPORT (Mark as Done)
elseif ($method === 'PATCH') {
    $reportId = intval($_GET['id'] ?? 0);
    
    // Check ownership
    $checkQuery = "SELECT user_id FROM pet_reports WHERE id = ?";
    $report = fetchOne($connection, $checkQuery, [$reportId]);
    
    if (!$report) {
        errorResponse('Laporan tidak ditemukan', null, 404);
    }
    
    if ($report['user_id'] != $currentUser['id']) {
        errorResponse('Anda tidak memiliki akses untuk mengubah laporan ini', null, 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $status = sanitizeInput($input['status'] ?? '');
    
    if ($status === 'completed') {
        $query = "UPDATE pet_reports SET status = 'completed' WHERE id = ?";
        $result = executeQuery($connection, $query, [$reportId]);
        
        if ($result['success']) {
            successResponse('Laporan berhasil ditandai sebagai selesai');
        } else {
            errorResponse('Gagal menandai laporan sebagai selesai', null, 500);
        }
    } else {
        errorResponse('Status tidak valid', null, 400);
    }
}

else {
    errorResponse('Action tidak valid', null, 400);
}

closeConnection($connection);

?>
