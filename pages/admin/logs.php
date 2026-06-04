<?php
/**
 * Admin Logs
 * PetFounds - Pet Finder Network
 */

session_start();
require_once dirname(__FILE__) . '/../../lib/admin_auth.php';
require_once dirname(__FILE__) . '/../../config/database.php';

requireAdminLogin();
$admin = getAdminInfo();

// Only super admins can view logs
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
    DB_USER,
    DB_PASS
);

// Get filters
$action_filter = $_GET['action'] ?? 'all';
$table_filter = $_GET['table'] ?? 'all';
$admin_filter = $_GET['admin'] ?? 'all';

// Build query
$query = 'SELECT al.*, a.name as admin_name FROM admin_logs al JOIN admins a ON al.admin_id = a.id WHERE 1=1';
$params = [];

if ($action_filter !== 'all') {
    $query .= ' AND al.action = ?';
    $params[] = $action_filter;
}

if ($table_filter !== 'all') {
    $query .= ' AND al.table_name = ?';
    $params[] = $table_filter;
}

if ($admin_filter !== 'all') {
    $query .= ' AND al.admin_id = ?';
    $params[] = $admin_filter;
}

$query .= ' ORDER BY al.created_at DESC LIMIT 500';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct actions
$stmt = $pdo->query('SELECT DISTINCT action FROM admin_logs ORDER BY action');
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get distinct tables
$stmt = $pdo->query('SELECT DISTINCT table_name FROM admin_logs WHERE table_name IS NOT NULL ORDER BY table_name');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get admin list
$stmt = $pdo->query('SELECT id, name FROM admins ORDER BY name');
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Admin PetFounds</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="app-logo flex-center" style="gap: 8px; margin-bottom: 20px;">
                <div class="logo-icon flex-center"><i class="fa-solid fa-shield"></i></div>
                <span class="logo-text">Admin</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="nav-item">
                <i class="fa-solid fa-users"></i>
                <span>Kelola Pengguna</span>
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fa-solid fa-file-lines"></i>
                <span>Kelola Laporan</span>
            </a>
            <a href="logs.php" class="nav-item active">
                <i class="fa-solid fa-history"></i>
                <span>Log Aktivitas</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-profile" style="padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px; margin-bottom: 15px;">
                <p style="font-size: 0.85rem; color: #ccc; margin: 0 0 5px 0;">Tersimpan sebagai:</p>
                <p style="margin: 0; color: white; font-weight: 600;"><?php echo htmlspecialchars($admin['name']); ?></p>
            </div>
            <a href="logout.php" class="btn btn-danger" style="display: block; text-align: center; padding: 10px;">
                <i class="fa-solid fa-sign-out-alt"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <h1><i class="fa-solid fa-history"></i> Log Aktivitas Admin</h1>
            <p class="text-muted">Pantau aktivitas administrator</p>
        </header>

        <!-- Filters -->
        <div class="admin-toolbar">
            <form method="GET" class="search-form" style="display: grid; grid-template-columns: 150px 150px 150px 120px; gap: 10px;">
                <select name="action">
                    <option value="all" <?php echo $action_filter === 'all' ? 'selected' : ''; ?>>Semua Aksi</option>
                    <?php foreach ($actions as $act): ?>
                    <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($act); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="table">
                    <option value="all" <?php echo $table_filter === 'all' ? 'selected' : ''; ?>>Semua Tabel</option>
                    <?php foreach ($tables as $table): ?>
                    <option value="<?php echo htmlspecialchars($table); ?>" <?php echo $table_filter === $table ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($table); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="admin">
                    <option value="all" <?php echo $admin_filter === 'all' ? 'selected' : ''; ?>>Semua Admin</option>
                    <?php foreach ($admins as $adm): ?>
                    <option value="<?php echo $adm['id']; ?>" <?php echo $admin_filter === (string)$adm['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($adm['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </form>
        </div>

        <!-- Logs Table -->
        <section class="content-section">
            <table class="admin-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Aksi</th>
                        <th>Tabel</th>
                        <th>Record ID</th>
                        <th>Deskripsi</th>
                        <th>IP Address</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($log['admin_name']); ?></strong></td>
                            <td>
                                <span class="badge" style="<?php
                                    if (strpos($log['action'], 'DELETE') !== false) echo 'background-color: #f8d7da; color: #721c24;';
                                    elseif (strpos($log['action'], 'SUSPEND') !== false) echo 'background-color: #fff3cd; color: #856404;';
                                    elseif (strpos($log['action'], 'VERIFY') !== false) echo 'background-color: #d1ecf1; color: #0c5460;';
                                    else echo 'background-color: #d4edda; color: #155724;';
                                ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['record_id'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($log['description'] ?? 'N/A'); ?></small></td>
                            <td><small><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                            <td><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Tidak ada log ditemukan</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
