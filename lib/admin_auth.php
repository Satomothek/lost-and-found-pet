<?php
/**
 * Admin Authentication Helper
 * PetFounds - Pet Finder Network
 */

require_once dirname(__FILE__) . '/../config/database.php';

function requireAdminLogin() {
    session_start();

    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
        header('Location: ' . dirname(dirname(__FILE__)) . '/pages/admin/login.php');
        exit;
    }
}

function getAdminInfo() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function logAdminAction($admin_id, $action, $description = null, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS
        );

        $stmt = $pdo->prepare(
            'INSERT INTO admin_logs (admin_id, action, description, table_name, record_id, old_value, new_value, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $admin_id,
            $action,
            $description,
            $table_name,
            $record_id,
            $old_value ? json_encode($old_value) : null,
            $new_value ? json_encode($new_value) : null,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return true;
    } catch (Exception $e) {
        error_log('Admin log error: ' . $e->getMessage());
        return false;
    }
}

function isSuperAdmin($admin_id = null) {
    $id = $admin_id ?? ($_SESSION['admin_id'] ?? null);

    if (!$id) return false;

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS
        );

        $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
        $stmt->execute([$id]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin && $admin['role'] === 'super_admin';
    } catch (Exception $e) {
        return false;
    }
}
