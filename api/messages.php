<?php
/**
 * Messages/Chat API Endpoints
 * GET, POST /api/messages.php
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireLogin();
$currentUser = getCurrentUser();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET CHAT CONTACTS
if ($method === 'GET' && $action === 'contacts') {
    $query = "SELECT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id
                    ELSE sender_id
                END as contact_id,
                users.name as contactName,
                users.avatar_url as avatar,
                users.bio as bio,
                users.email as email,
                users.phone as phone,
                MAX(messages.message) as text,
                DATE_FORMAT(MAX(messages.created_at), '%H:%i') as time,
                MAX(messages.created_at) as last_time,
                IF(EXISTS(SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = users.id), 1, 0) as isBlocked
              FROM messages
              JOIN users ON users.id = (
                CASE 
                    WHEN sender_id = ? THEN receiver_id
                    ELSE sender_id
                END
              )
              WHERE (sender_id = ? OR receiver_id = ?)
              GROUP BY contact_id
              ORDER BY last_time DESC";
    
    $contacts = fetchAll($connection, $query, [
        $currentUser['id'],
        $currentUser['id'],
        $currentUser['id'],
        $currentUser['id'],
        $currentUser['id']
    ]);
    
    if (!$contacts) {
        $contacts = [];
    }

    foreach ($contacts as &$contact) {
        $contact['avatar'] = normalizeAssetUrl($contact['avatar']);
        $contact['isBlocked'] = !empty($contact['isBlocked']);
    }
    unset($contact);
    
    successResponse('Chat contacts berhasil diambil', ['contacts' => $contacts]);
}

//  GET MESSAGES WITH SPECIFIC USER 
elseif ($method === 'GET' && $action === 'history') {
    $contactId = intval($_GET['contact_id'] ?? 0);
    
    if (!$contactId) {
        errorResponse('Contact ID tidak valid', null, 400);
    }
    
    $query = "SELECT id, sender_id, message, 
                    DATE_FORMAT(created_at, '%H:%i') as time
              FROM messages
              WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
              ORDER BY created_at ASC
              LIMIT 50";
    
    $messages = fetchAll($connection, $query, [
        $currentUser['id'],
        $contactId,
        $contactId,
        $currentUser['id']
    ]);
    
    if (!$messages) {
        $messages = [];
    }
    
    // Format messages
    $messages = array_map(function($msg) use ($currentUser) {
        return [
            'id' => $msg['id'],
            'sender' => $msg['sender_id'] == $currentUser['id'] ? 'me' : 'them',
            'text' => $msg['message'],
            'time' => $msg['time']
        ];
    }, $messages);
    
    successResponse('Chat history berhasil diambil', ['messages' => $messages]);
}

//  GET USER PROFILE
elseif ($method === 'GET' && $action === 'user') {
    $contactId = intval($_GET['user_id'] ?? 0);
    if (!$contactId) {
        errorResponse('User ID tidak valid', null, 400);
    }

    $query = "SELECT id as contact_id, name as contactName, avatar_url as avatar, bio, phone FROM users WHERE id = ?";
    $contact = fetchOne($connection, $query, [$contactId]);

    if (!$contact) {
        errorResponse('User tidak ditemukan', null, 404);
    }

    $contact['avatar'] = normalizeAssetUrl($contact['avatar']);
    $blockedCheck = fetchOne($connection, "SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?", [$currentUser['id'], $contactId]);
    $contact['isBlocked'] = !empty($blockedCheck);

    successResponse('Profile user berhasil diambil', ['contact' => $contact]);
}

//  DELETE CHAT HISTORY 
elseif ($method === 'POST' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $contactId = intval($input['contact_id'] ?? 0);
    if (!$contactId) {
        errorResponse('Contact ID tidak valid', null, 400);
    }

    $query = "DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
    $result = executeQuery($connection, $query, [
        $currentUser['id'],
        $contactId,
        $contactId,
        $currentUser['id']
    ]);

    if ($result['success']) {
        successResponse('Riwayat chat berhasil dihapus', ['deleted_rows' => $result['affected_rows']]);
    }

    errorResponse('Gagal menghapus chat', null, 500);
}

//  BLOCK USER
elseif ($method === 'POST' && $action === 'block') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $blockedId = intval($input['blocked_id'] ?? 0);
    if (!$blockedId) {
        errorResponse('User ID tidak valid', null, 400);
    }

    if ($blockedId === $currentUser['id']) {
        errorResponse('Tidak dapat memblokir diri sendiri', null, 400);
    }

    $query = "INSERT IGNORE INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)";
    $result = executeQuery($connection, $query, [$currentUser['id'], $blockedId]);
    if (!$result['success']) {
        errorResponse('Gagal memblokir pengguna', null, 500);
    }

    successResponse('Pengguna berhasil diblokir', null);
}

//  UNBLOCK USER
elseif ($method === 'POST' && $action === 'unblock') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $blockedId = intval($input['blocked_id'] ?? 0);
    if (!$blockedId) {
        errorResponse('User ID tidak valid', null, 400);
    }

    if ($blockedId === $currentUser['id']) {
        errorResponse('Tidak dapat membuka blokir diri sendiri', null, 400);
    }

    $query = "DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?";
    $result = executeQuery($connection, $query, [$currentUser['id'], $blockedId]);

    if (!$result['success']) {
        errorResponse('Gagal membuka blokir pengguna', null, 500);
    }

    successResponse('Pengguna berhasil dibuka blokirnya', null);
}

//  SEND MESSAGE 
elseif ($method === 'POST' && $action === 'send') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $receiverId = intval($input['receiver_id'] ?? 0);
    $message = sanitizeInput($input['message'] ?? '');
    
    if (!$receiverId) {
        errorResponse('Receiver ID tidak valid', null, 400);
    }
    
    if (!$message) {
        errorResponse('Pesan tidak boleh kosong', null, 400);
    }
    
    // Check if receiver exists
    $checkQuery = "SELECT id FROM users WHERE id = ?";
    $receiver = fetchOne($connection, $checkQuery, [$receiverId]);
    
    if (!$receiver) {
        errorResponse('User penerima tidak ditemukan', null, 404);
    }

    $blockedCheck = fetchOne($connection, "SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?", [$currentUser['id'], $receiverId]);
    if (!empty($blockedCheck)) {
        errorResponse('Tidak dapat mengirim pesan ke pengguna yang diblokir.', null, 403);
    }
    
    // Insert message
    $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $result = executeQuery($connection, $query, [$currentUser['id'], $receiverId, $message]);
    
    if ($result['success']) {
        $now = new DateTime();
        successResponse('Pesan berhasil dikirim', [
            'message_id' => $result['insert_id'],
            'sender' => 'me',
            'text' => $message,
            'time' => $now->format('H:i')
        ]);
    } else {
        errorResponse('Gagal mengirim pesan', null, 500);
    }
}

else {
    errorResponse('Action tidak valid', null, 400);
}

closeConnection($connection);

?>
