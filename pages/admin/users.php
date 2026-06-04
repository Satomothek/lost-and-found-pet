<?php
/**
 * Admin Users Management
 * PetFounds - Pet Finder Network
 */

session_start();
require_once dirname(__FILE__) . '/../../lib/admin_auth.php';
require_once dirname(__FILE__) . '/../../config/database.php';

requireAdminLogin();
$admin = getAdminInfo();

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
    DB_USER,
    DB_PASS
);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'suspend' && $user_id > 0) {
        $stmt = $pdo->prepare('UPDATE users SET is_suspended = 1 WHERE id = ?');
        $stmt->execute([$user_id]);
        logAdminAction($admin['id'], 'SUSPEND_USER', "Suspend user ID $user_id", 'users', $user_id);
    } elseif ($action === 'activate' && $user_id > 0) {
        $stmt = $pdo->prepare('UPDATE users SET is_suspended = 0 WHERE id = ?');
        $stmt->execute([$user_id]);
        logAdminAction($admin['id'], 'ACTIVATE_USER', "Activate user ID $user_id", 'users', $user_id);
    } elseif ($action === 'delete' && $user_id > 0 && isSuperAdmin()) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        logAdminAction($admin['id'], 'DELETE_USER', "Delete user ID $user_id", 'users', $user_id);
    }

    header('Location: users.php');
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // all, active, suspended

// Build query
$query = 'SELECT id, name, email, phone, avatar_url, is_suspended, created_at FROM users WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'suspended') {
    $query .= ' AND is_suspended = 1';
} elseif ($filter === 'active') {
    $query .= ' AND is_suspended = 0';
}

$query .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user detail if requested
$user_detail = null;
if (isset($_GET['detail'])) {
    $detail_id = intval($_GET['detail']);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$detail_id]);
    $user_detail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_detail) {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM pet_reports WHERE user_id = ?');
        $stmt->execute([$detail_id]);
        $user_detail['reports_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin PetFounds</title>
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
            <a href="users.php" class="nav-item active">
                <i class="fa-solid fa-users"></i>
                <span>Kelola Pengguna</span>
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fa-solid fa-file-lines"></i>
                <span>Kelola Laporan</span>
            </a>
            <a href="logs.php" class="nav-item">
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
            <h1><i class="fa-solid fa-users"></i> Kelola Pengguna</h1>
            <p class="text-muted">Kelola akun dan status pengguna PetFounds</p>
        </header>

        <!-- Filters and Search -->
        <div class="admin-toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari pengguna..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="filter">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Semua Pengguna</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Pengguna Aktif</option>
                    <option value="suspended" <?php echo $filter === 'suspended' ? 'selected' : ''; ?>>Pengguna Suspend</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search"></i> Cari
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <section class="content-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($user['is_suspended']): ?>
                                <span class="badge badge-danger">Suspend</span>
                                <?php else: ?>
                                <span class="badge badge-success">Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?detail=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <?php if ($user['is_suspended']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Aktifkan Akun" onclick="return confirm('Aktifkan akun pengguna ini?')">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="suspend">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Suspend Akun" onclick="return confirm('Suspend akun pengguna ini?')">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (isSuperAdmin()): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Akun" onclick="return confirm('Hapus akun pengguna ini? Tindakan ini tidak dapat dibatalkan.')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada pengguna ditemukan</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- User Detail Modal -->
        <?php if ($user_detail): ?>
        <div class="modal" id="userDetailModal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Detail Pengguna</h2>
                    <a href="users.php" class="close">&times;</a>
                </div>
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <img src="<?php echo htmlspecialchars($user_detail['avatar_url']); ?>" alt="Avatar" style="width: 150px; height: 150px; border-radius: 50%; margin-bottom: 15px;">
                            <h3><?php echo htmlspecialchars($user_detail['name']); ?></h3>
                        </div>
                        <div>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_detail['email']); ?></p>
                            <p><strong>Telepon:</strong> <?php echo htmlspecialchars($user_detail['phone'] ?? 'N/A'); ?></p>
                            <p><strong>Status:</strong> <span class="badge <?php echo $user_detail['is_suspended'] ? 'badge-danger' : 'badge-success'; ?>"><?php echo $user_detail['is_suspended'] ? 'Suspend' : 'Aktif'; ?></span></p>
                            <p><strong>Bergabung:</strong> <?php echo date('d M Y H:i', strtotime($user_detail['created_at'])); ?></p>
                            <p><strong>Total Laporan:</strong> <?php echo $user_detail['reports_count']; ?></p>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <h4>Bio</h4>
                        <p><?php echo htmlspecialchars($user_detail['bio'] ?? 'Tidak ada bio'); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="users.php" class="btn btn-secondary">Tutup</a>
                </div>
            </div>
        </div>
        <style>
            .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
            .modal-content { background-color: white; margin: 50px auto; padding: 0; border-radius: 8px; width: 90%; max-width: 600px; }
            .modal-header { padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
            .modal-header h2 { margin: 0; }
            .modal-body { padding: 20px; }
            .modal-footer { padding: 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end; }
            .close { color: #aaa; cursor: pointer; font-size: 28px; font-weight: bold; }
            .close:hover { color: black; }
        </style>
        <?php endif; ?>
    </main>
</body>
</html>
