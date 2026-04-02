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

// ========== GET CHAT CONTACTS ==========
if ($method === 'GET' && $action === 'contacts') {
    $query = "SELECT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id
                    ELSE sender_id
                END as contact_id,
                users.name as contactName,
                users.avatar_url as avatar,
                MAX(messages.message) as text,
                DATE_FORMAT(MAX(messages.created_at), '%H:%i') as time,
                MAX(messages.created_at) as last_time
              FROM messages
              JOIN users ON (
                CASE 
                    WHEN sender_id = ? THEN receiver_id = users.id
                    ELSE sender_id = users.id
                END
              )
              WHERE sender_id = ? OR receiver_id = ?
              GROUP BY contact_id
              ORDER BY last_time DESC";
    
    $contacts = fetchAll($connection, $query, [
        $currentUser['id'],
        $currentUser['id'],
        $currentUser['id'],
        $currentUser['id']
    ]);
    
    if (!$contacts) {
        $contacts = [];
    }
    
    successResponse('Chat contacts berhasil diambil', ['contacts' => $contacts]);
}

// ========== GET MESSAGES WITH SPECIFIC USER ==========
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

// ========== SEND MESSAGE ==========
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
