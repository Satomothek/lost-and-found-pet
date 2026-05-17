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

/**
 * validatePetImage — Kirim gambar ke Google Gemini Vision API dan cek apakah foto hewan.
 *
 * @param  string $filePath  Path absolut/relatif ke file gambar yang sudah terupload.
 * @return array  ['is_pet' => bool, 'message' => string, 'detail' => string]
 */
function validatePetImage(string $filePath): array {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return ['is_pet' => true, 'message' => 'OK', 'detail' => 'File tidak dapat dibaca'];
    }

    $imageData = base64_encode(file_get_contents($filePath));
    $mimeType  = mime_content_type($filePath);
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if (!in_array($mimeType, $allowedMime)) {
        return ['is_pet' => false,
                'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.',
                'detail'  => "MIME: $mimeType"];
    }

    // Ambil Gemini API key
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY
            : (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ''));

    error_log('[validatePetImage] Gemini API key: ' . (!empty($apiKey) ? 'YA (panjang=' . strlen($apiKey) . ')' : 'TIDAK'));

    if (!$apiKey) {
        error_log('[validatePetImage] GEMINI_API_KEY tidak ditemukan, validasi dilewati.');
        return ['is_pet' => true, 'message' => 'OK', 'detail' => 'API key tidak dikonfigurasi'];
    }

    $prompt = 'Analyze this image and determine if it contains an animal or pet. '
            . 'Respond ONLY with a valid JSON object, no explanation, no markdown. '
            . 'If it contains an animal: {"is_pet":true,"animal_type":"cat","reason":"Clear photo of a cat"} '
            . 'If it does NOT contain an animal: {"is_pet":false,"animal_type":null,"reason":"Brief reason"}';

    $payload = json_encode([
        'contents' => [[
            'parts' => [
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]],
                ['text' => $prompt]
            ]
        ]],
        'generationConfig' => [
            'temperature'     => 0,
            'maxOutputTokens' => 256,
        ]
    ]);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    error_log("[validatePetImage] Gemini HTTP: $httpCode | cURL: " . ($curlErr ?: 'none'));

    if ($curlErr || $httpCode !== 200) {
        error_log("[validatePetImage] Gagal: " . substr($response, 0, 300));
        return ['is_pet' => true, 'message' => 'OK', 'detail' => "HTTP $httpCode"];
    }

    // Parse respons Gemini
    $body = json_decode($response, true);
    $text = trim($body['candidates'][0]['content']['parts'][0]['text'] ?? '');

    // Bersihkan markdown fence kalau ada
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/i', '', $text);
    $text = trim($text);

    error_log("[validatePetImage] Gemini raw text: $text");

    $result = json_decode($text, true);

    if (!is_array($result) || !isset($result['is_pet'])) {
        error_log("[validatePetImage] Parse error: $text");
        return ['is_pet' => true, 'message' => 'OK', 'detail' => 'Parse error'];
    }

    if ($result['is_pet']) {
        return [
            'is_pet'  => true,
            'message' => 'OK',
            'detail'  => $result['animal_type'] ?? 'hewan terdeteksi',
        ];
    }

    $reason  = $result['reason'] ?? 'Gambar tidak menampilkan hewan';
    $message = "Foto yang diunggah bukan foto hewan. " . $reason . ". Mohon unggah foto hewan peliharaan yang jelas.";

    return [
        'is_pet'  => false,
        'message' => $message,
        'detail'  => $reason,
    ];
}

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
        $conditionText = $report['status'] === 'resolved' ? 'SELESAI' : ($report['type'] === 'found' ? 'AMAN' : 'DALAM PENCARIAN');
        $conditionState = $report['status'] === 'resolved' ? 'resolved' : ($report['type'] === 'found' ? 'safe' : 'searching');

        $formattedReport = [
            'id' => $report['id'],
            'type' => $report['type'],
            'status' => $report['status'],
            'condition' => $conditionText,
            'condition_state' => $conditionState,
            'author' => $report['author'] ?: 'Anonim',
            'authorImg' => normalizeAssetUrl($report['authorImg'] ?: 'https://i.pravatar.cc/150?img=68'),
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'speciesDetail' => $report['species_detail'] ?? null,
            'location' => $report['location'],
            'location_description' => $report['location_description'] ?? null,
            'description' => $report['description'],
            'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
            'latitude' => $report['latitude'] !== null ? floatval($report['latitude']) : null,
            'longitude' => $report['longitude'] !== null ? floatval($report['longitude']) : null,
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
        $conditionText = $report['status'] === 'resolved' ? 'SELESAI' : ($report['type'] === 'found' ? 'AMAN' : 'DALAM PENCARIAN');
        $conditionState = $report['status'] === 'resolved' ? 'resolved' : ($report['type'] === 'found' ? 'safe' : 'searching');

        return [
            'id' => $report['id'],
            'type' => $report['type'],
            'status' => $report['status'],
            'condition' => $conditionText,
            'condition_state' => $conditionState,
            'author' => $report['author'] ?: 'Anonim',
            'authorImg' => normalizeAssetUrl($report['authorImg'] ?: 'https://i.pravatar.cc/150?img=68'),
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'speciesDetail' => $report['species_detail'] ?? null,
            'location' => $report['location'],
            'location_description' => $report['location_description'] ?? null,
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
            'latitude' => $report['latitude'] !== null ? floatval($report['latitude']) : null,
            'longitude' => $report['longitude'] !== null ? floatval($report['longitude']) : null,
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
    $locationDescription = sanitizeInput($_POST['location_description'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $reportDate = sanitizeInput($_POST['date'] ?? '');
    $latitude = sanitizeInput($_POST['latitude'] ?? '');
    $longitude = sanitizeInput($_POST['longitude'] ?? '');
    
    if ($latitude !== '' && !is_numeric($latitude)) {
        errorResponse('Koordinat latitude tidak valid', null, 400);
    }
    if ($longitude !== '' && !is_numeric($longitude)) {
        errorResponse('Koordinat longitude tidak valid', null, 400);
    }
    
    // Validate
    if (!$type || !$species || !$location || !$locationDescription || !$description || !$reportDate) {
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
    
    // Handle image upload + validasi foto hewan via Claude Vision
    $imageUrl = 'https://via.placeholder.com/600x400?text=Pet+Image';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image'], '../public/uploads/');
        if ($upload['success']) {
            $uploadedPath = '../public/uploads/' . $upload['filename'];

            // ── Validasi foto hewan ──────────────────────────────────────────
            $validationResult = validatePetImage($uploadedPath);
            if (!$validationResult['is_pet']) {
                if (file_exists($uploadedPath)) unlink($uploadedPath);
                errorResponse($validationResult['message'], null, 422);
            }
            // ────────────────────────────────────────────────────────────────

            $imageUrl = 'public/uploads/' . $upload['filename'];
        }
    }
    
    // Insert report
    $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, species_detail, location, location_description, description, image_url, latitude, longitude, event_date)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = executeQuery($connection, $query, [
        $currentUser['id'],
        $type,
        $petName,
        $species,
        $speciesDetail,
        $location,
        $locationDescription,
        $description,
        $imageUrl,
        $latitude !== '' ? floatval($latitude) : null,
        $longitude !== '' ? floatval($longitude) : null,
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
    $latitude = sanitizeInput($_POST['latitude'] ?? '');
    $longitude = sanitizeInput($_POST['longitude'] ?? '');
    
    if ($latitude !== '' && !is_numeric($latitude)) {
        errorResponse('Koordinat latitude tidak valid', null, 400);
    }
    if ($longitude !== '' && !is_numeric($longitude)) {
        errorResponse('Koordinat longitude tidak valid', null, 400);
    }
    
    // Handle image upload (optional for update) + validasi foto hewan
    $imageUrl = $report['image_url']; // Keep existing image if no new upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image'], '../public/uploads/');
        if ($upload['success']) {
            $uploadedPath = '../public/uploads/' . $upload['filename'];

            // ── Validasi foto hewan ──────────────────────────────────────────
            $validationResult = validatePetImage($uploadedPath);
            if (!$validationResult['is_pet']) {
                if (file_exists($uploadedPath)) unlink($uploadedPath);
                errorResponse($validationResult['message'], null, 422);
            }
            // ────────────────────────────────────────────────────────────────

            $imageUrl = 'public/uploads/' . $upload['filename'];
        }
    }
    
    // Validate date if provided
    $locationDescription = sanitizeInput($_POST['location_description'] ?? '');
    if ($reportDate) {
        $today = date('Y-m-d');
        $minDate = date('Y-m-d', strtotime('-7 days'));
        if ($reportDate < $minDate || $reportDate > $today) {
            errorResponse('Tanggal harus antara ' . $minDate . ' sampai ' . $today, null, 400);
        }
        $query = "UPDATE pet_reports SET pet_name = ?, species = ?, species_detail = ?, location = ?, location_description = ?, description = ?, event_date = ?, image_url = ?, latitude = ?, longitude = ? WHERE id = ?";
        $result = executeQuery($connection, $query, [$petName, $species, $speciesDetail, $location, $locationDescription, $description, $reportDate, $imageUrl, $latitude !== '' ? floatval($latitude) : null, $longitude !== '' ? floatval($longitude) : null, $reportId]);
    } else {
        $query = "UPDATE pet_reports SET pet_name = ?, species = ?, species_detail = ?, location = ?, location_description = ?, description = ?, image_url = ?, latitude = ?, longitude = ? WHERE id = ?";
        $result = executeQuery($connection, $query, [$petName, $species, $speciesDetail, $location, $locationDescription, $description, $imageUrl, $latitude !== '' ? floatval($latitude) : null, $longitude !== '' ? floatval($longitude) : null, $reportId]);
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