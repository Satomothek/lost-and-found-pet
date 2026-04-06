<?php
/**
 * Helper Functions & Utilities
 * PetFounds Application
 */

// ========== HTTP RESPONSE FUNCTIONS ==========
function jsonResponse($status, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

function successResponse($message = 'Success', $data = null) {
    jsonResponse('success', $message, $data, 200);
}

function errorResponse($message = 'Error', $data = null, $httpCode = 400) {
    jsonResponse('error', $message, $data, $httpCode);
}

// ========== VALIDATION FUNCTIONS ==========
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateRequired($value) {
    return !empty(trim($value));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validatePassword($password) {
    // Minimal 6 characters, at least 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 6;
}

// ========== SECURITY FUNCTIONS ==========
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// ========== DATABASE FUNCTIONS ==========
function getLastError($connection) {
    return $connection->error;
}

function executeQuery($connection, $query, $params = []) {
    $stmt = $connection->prepare($query);
    
    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Prepare failed: ' . $connection->error
        ];
    }
    
    if (!empty($params)) {
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
            $values[] = $param;
        }
        
        $stmt->bind_param($types, ...$values);
    }
    
    if (!$stmt->execute()) {
        return [
            'success' => false,
            'error' => 'Execute failed: ' . $stmt->error
        ];
    }
    
    $result = $stmt->get_result();
    
    return [
        'success' => true,
        'result' => $result,
        'affected_rows' => $stmt->affected_rows,
        'insert_id' => $stmt->insert_id
    ];
}

function fetchAll($connection, $query, $params = []) {
    $exec = executeQuery($connection, $query, $params);
    
    if (!$exec['success']) {
        return false;
    }
    
    $data = [];
    while ($row = $exec['result']->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

function fetchOne($connection, $query, $params = []) {
    $exec = executeQuery($connection, $query, $params);
    
    if (!$exec['success']) {
        return false;
    }
    
    return $exec['result']->fetch_assoc();
}

// ========== IMAGE UPLOAD FUNCTIONS ==========
function uploadImage($file, $uploadDir = 'uploads/') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'File tidak valid'];
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowed)) {
        return ['success' => false, 'error' => 'Format file tidak didukung'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        return ['success' => false, 'error' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    // Create upload directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid('img_', true) . '.' . $fileExt;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filepath
        ];
    }
    
    return ['success' => false, 'error' => 'Gagal upload file'];
}

function normalizeAssetUrl($url) {
    if (!$url) {
        return '';
    }

    $url = trim($url);

    if (preg_match('/^(https?:)?\/\//i', $url)) {
        return $url;
    }

    if (strpos($url, '../') === 0) {
        return $url;
    }

    if ($url[0] === '/') {
        return '..' . $url;
    }

    if (strpos($url, 'uploads/') === 0) {
        return '../public/' . $url;
    }

    if (strpos($url, 'public/uploads/') === 0) {
        return '../' . $url;
    }

    if (strpos($url, 'public/') === 0) {
        return '../' . $url;
    }

    return $url;
}

// ========== DATE FORMATTING ==========
function formatDate($date, $format = 'id') {
    $dateObj = new DateTime($date);
    
    $today = new DateTime();
    $yesterday = new DateTime('yesterday');
    
    if ($dateObj->format('Y-m-d') === $today->format('Y-m-d')) {
        return $dateObj->format('H:i');
    } elseif ($dateObj->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Kemarin';
    }
    
    // Set Indonesian locale
    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $month = $months[$dateObj->format('n') - 1];
    return $dateObj->format('j') . ' ' . $month;
}

function timeAgo($date) {
    $dateObj = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($dateObj);
    
    if ($diff->days == 0) {
        if ($diff->h > 0) {
            return $diff->h . ' jam yang lalu';
        } elseif ($diff->i > 0) {
            return $diff->i . ' menit yang lalu';
        } else {
            return 'Baru saja';
        }
    } elseif ($diff->days == 1) {
        return 'Kemarin';
    } elseif ($diff->days < 7) {
        return $diff->days . ' hari yang lalu';
    } else {
        return formatDate($date);
    }
}

// ========== PAGINATION ==========
function getPagination($page = 1, $limit = 10) {
    $page = max(1, intval($page));
    $limit = max(1, intval($limit));
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

?>
