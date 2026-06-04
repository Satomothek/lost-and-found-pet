<?php
/**
 * Admin API - Dashboard Statistics
 * PetFounds - Pet Finder Network
 */

header('Content-Type: application/json');
session_start();

require_once dirname(__FILE__) . '/../../lib/admin_auth.php';
require_once dirname(__FILE__) . '/../../config/database.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Validate HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );

    $action = $_GET['action'] ?? 'stats';

    switch ($action) {
        case 'stats':
            // Get overall statistics
            $stats = [];

            $stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
            $stats['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE is_suspended = 0');
            $stats['active_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query('SELECT COUNT(*) as total FROM pet_reports');
            $stats['total_reports'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE type = 'lost'");
            $stats['lost_reports'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE type = 'found'");
            $stats['found_reports'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE status = 'active'");
            $stats['active_reports'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query('SELECT COUNT(*) as total FROM pet_reports WHERE is_verified = 1');
            $stats['verified_reports'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo json_encode(['status' => 'success', 'data' => $stats]);
            break;

        case 'monthly':
            // Get monthly statistics
            $stmt = $pdo->query(
                'SELECT DATE_FORMAT(created_at, "%Y-%m") as month,
                        COUNT(*) as total,
                        SUM(CASE WHEN type = "lost" THEN 1 ELSE 0 END) as lost,
                        SUM(CASE WHEN type = "found" THEN 1 ELSE 0 END) as found
                 FROM pet_reports
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY month
                 ORDER BY month ASC'
            );
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'recent_reports':
            // Get recent reports
            $limit = min((int)($_GET['limit'] ?? 10), 100);
            $stmt = $pdo->prepare(
                'SELECT pr.id, pr.pet_name, pr.species, pr.type, pr.status, pr.created_at,
                        u.name, u.email
                 FROM pet_reports pr
                 JOIN users u ON pr.user_id = u.id
                 ORDER BY pr.created_at DESC
                 LIMIT ?'
            );
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'user_activity':
            // Get user creation activity
            $stmt = $pdo->query(
                'SELECT DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count
                 FROM users
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 GROUP BY month
                 ORDER BY month ASC'
            );
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
