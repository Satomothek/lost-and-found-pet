<?php
/**
 * User Profile API Endpoints
 * GET, POST, PUT /api/profile.php
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireLogin();
$currentUser = getCurrentUser();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ========== GET CURRENT USER PROFILE ==========
if ($method === 'GET' && !$action) {
    $userId = $currentUser['id'];
    
    $user = getUserById($connection, $userId);
    
    // Get user reports count
    $reportsQuery = "SELECT COUNT(*) as count FROM pet_reports WHERE user_id = ? AND status = 'active'";
    $reportsCount = fetchOne($connection, $reportsQuery, [$userId]);

    $lostQuery = "SELECT COUNT(*) as count FROM pet_reports WHERE user_id = ? AND status = 'active' AND type = 'lost'";
    $lostCount = fetchOne($connection, $lostQuery, [$userId]);

    $foundQuery = "SELECT COUNT(*) as count FROM pet_reports WHERE user_id = ? AND status = 'active' AND type = 'found'";
    $foundCount = fetchOne($connection, $foundQuery, [$userId]);
    
    // Get user recent reports
    $recentQuery = "SELECT id, user_id, type, pet_name, species, description, image_url, created_at
                    FROM pet_reports 
                    WHERE user_id = ? AND status = 'active'
                    ORDER BY created_at DESC 
                    LIMIT 3";
    $recent = fetchAll($connection, $recentQuery, [$userId]);
    
    if (!$recent) {
        $recent = [];
    }
    
    // Format response
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'avatar' => normalizeAssetUrl($user['avatar_url'] ?: 'https://i.pravatar.cc/150?img=68'),
        'bio' => $user['bio'] ?? '',
        'phone' => $user['phone'] ?? '',
        'joined' => formatDate($user['created_at']),
        'reports_count' => intval($reportsCount['count']),
        'lost_count' => intval($lostCount['count']),
        'found_count' => intval($foundCount['count']),
        'recent_reports' => array_map(function($report) {
            return [
                'id' => $report['id'],
                'type' => $report['type'],
                'petName' => $report['pet_name'],
                'species' => $report['species'],
                'description' => $report['description'],
                'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
                'date' => timeAgo($report['created_at'])
            ];
        }, $recent)
    ];
    
    successResponse('Profil berhasil diambil', $userData);
}

// ========== GET USER BY ID ==========
elseif ($method === 'GET' && $action === 'user') {
    $userId = intval($_GET['id'] ?? 0);
    
    if (!$userId) {
        errorResponse('User ID tidak valid', null, 400);
    }
    
    $user = getUserById($connection, $userId);
    
    if (!$user) {
        errorResponse('User tidak ditemukan', null, 404);
    }
    
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'avatar' => $user['avatar_url'],
        'bio' => $user['bio'] ?? '',
        'phone' => $user['phone'] ?? '',
        'joined' => formatDate($user['created_at'])
    ];
    
    successResponse('Data user berhasil diambil', $userData);
}

// ========== UPDATE PROFILE ==========
elseif ($method === 'POST' && $action === 'update') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    if (!$name) {
        errorResponse('Nama tidak boleh kosong', null, 400);
    }
    
    $data = [
        'name' => $name,
        'bio' => $bio,
        'phone' => $phone
    ];
    
    if (updateUserProfile($connection, $currentUser['id'], $data)) {
        // Update session
        $_SESSION['user_name'] = $name;
        
        successResponse('Profil berhasil diperbarui', [
            'name' => $name,
            'bio' => $bio,
            'phone' => $phone
        ]);
    } else {
        errorResponse('Gagal memperbarui profil', null, 500);
    }
}

// ========== UPDATE AVATAR ==========
elseif ($method === 'POST' && $action === 'avatar') {
    if (!isset($_FILES['avatar'])) {
        errorResponse('File avatar tidak ditemukan', null, 400);
    }
    
    $upload = uploadImage($_FILES['avatar'], '../public/uploads/avatars/');
    
    if ($upload['success']) {
        $avatarUrl = 'public/uploads/avatars/' . $upload['filename'];
        
        if (updateUserAvatar($connection, $currentUser['id'], $avatarUrl)) {
            successResponse('Avatar berhasil diperbarui', ['avatar_url' => normalizeAssetUrl($avatarUrl)]);
        } else {
            errorResponse('Gagal menyimpan avatar', null, 500);
        }
    } else {
        errorResponse($upload['error'], null, 400);
    }
}

// ========== GET USER REPORTS ==========
elseif ($method === 'GET' && $action === 'reports') {
    $userId = intval($_GET['id'] ?? $currentUser['id']);
    $page = intval($_GET['page'] ?? 1);
    
    $pagination = getPagination($page, 10);
    
    $query = "SELECT * FROM pet_reports 
              WHERE user_id = ? AND status = 'active'
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";
    
    $reports = fetchAll($connection, $query, [
        $userId,
        $pagination['limit'],
        $pagination['offset']
    ]);
    
    if (!$reports) {
        $reports = [];
    }
    
    $reports = array_map(function($report) {
        return [
            'id' => $report['id'],
            'type' => $report['type'],
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'location' => $report['location'],
            'description' => $report['description'],
            'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
            'date' => timeAgo($report['created_at']),
            'eventDate' => $report['event_date'] ? date('d M Y', strtotime($report['event_date'])) : null
        ];
    }, $reports);
    
    successResponse('Laporan user berhasil diambil', [
        'reports' => $reports,
        'page' => $pagination['page']
    ]);
}

// ========== GET USER BOOKMARKS ==========
elseif ($method === 'GET' && $action === 'bookmarks') {
    $query = "SELECT pr.*
              FROM pet_reports pr
              JOIN likes l ON l.report_id = pr.id
              WHERE l.user_id = ? AND pr.status = 'active'
              ORDER BY l.created_at DESC";

    $bookmarks = fetchAll($connection, $query, [$currentUser['id']]);

    if (!$bookmarks) {
        $bookmarks = [];
    }

    $bookmarks = array_map(function($report) {
        return [
            'id' => $report['id'],
            'type' => $report['type'],
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'location' => $report['location'],
            'description' => $report['description'],
            'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
            'date' => timeAgo($report['created_at']),
            'eventDate' => $report['event_date'] ? date('d M Y', strtotime($report['event_date'])) : null,
            'isLiked' => true
        ];
    }, $bookmarks);

    successResponse('Bookmarks user berhasil diambil', [
        'bookmarks' => $bookmarks
    ]);
}

else {
    errorResponse('Action tidak valid', null, 400);
}

closeConnection($connection);

?>
