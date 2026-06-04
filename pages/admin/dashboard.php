<?php
/**
 * Admin Dashboard
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

// Get dashboard statistics
$stats = [];

// Total users
$stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active users
$stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE is_suspended = 0');
$stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Suspended users
$stats['suspended_users'] = $stats['total_users'] - $stats['active_users'];

// Total reports
$stmt = $pdo->query('SELECT COUNT(*) as total FROM pet_reports');
$stats['total_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Lost reports
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE type = 'lost'");
$stats['lost_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Found reports
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE type = 'found'");
$stats['found_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active reports
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE status = 'active'");
$stats['active_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Resolved reports
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pet_reports WHERE status = 'resolved'");
$stats['resolved_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Verified reports
$stmt = $pdo->query('SELECT COUNT(*) as total FROM pet_reports WHERE is_verified = 1');
$stats['verified_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent reports
$stmt = $pdo->query(
    'SELECT pr.id, pr.pet_name, pr.species, pr.type, pr.status, pr.created_at, u.name, u.email
     FROM pet_reports pr
     JOIN users u ON pr.user_id = u.id
     ORDER BY pr.created_at DESC
     LIMIT 10'
);
$recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly statistics
$stmt = $pdo->query(
    'SELECT DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, type
     FROM pet_reports
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY month, type
     ORDER BY month DESC'
);
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PetFounds</title>
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
            <a href="dashboard.php" class="nav-item active">
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
            <a href="logs.php" class="nav-item">
                <i class="fa-solid fa-history"></i>
                <span>Log Aktivitas</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-profile" style="padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px; margin-bottom: 15px;">
                <p style="font-size: 0.85rem; color: #ccc; margin: 0 0 5px 0;">Tersimpan sebagai:</p>
                <p style="margin: 0; color: white; font-weight: 600;"><?php echo htmlspecialchars($admin['name']); ?></p>
                <p style="font-size: 0.85rem; color: #ccc; margin: 2px 0 0 0;"><?php echo ucfirst($admin['role']); ?></p>
            </div>
            <a href="logout.php" class="btn btn-danger" style="display: block; text-align: center; padding: 10px; margin-top: 10px;">
                <i class="fa-solid fa-sign-out-alt"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <h1><i class="fa-solid fa-chart-line"></i> Dashboard</h1>
            <p class="text-muted">Ringkasan aktivitas PetFounds</p>
        </header>

        <!-- Statistics Grid -->
        <div class="admin-grid">
            <div class="stat-card">
                <div class="stat-icon bg-primary-light text-primary">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['total_users']; ?></h3>
                    <p class="stat-label">Total Pengguna</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-success-light text-success">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['active_users']; ?></h3>
                    <p class="stat-label">Pengguna Aktif</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-danger-light text-danger">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['suspended_users']; ?></h3>
                    <p class="stat-label">Pengguna Suspend</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-info-light text-info">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['total_reports']; ?></h3>
                    <p class="stat-label">Total Laporan</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-warning-light text-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['lost_reports']; ?></h3>
                    <p class="stat-label">Laporan Hilang</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #d4edda; color: #155724;">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['found_reports']; ?></h3>
                    <p class="stat-label">Laporan Ditemukan</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-primary-light text-primary">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['active_reports']; ?></h3>
                    <p class="stat-label">Laporan Aktif</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #cfe2ff; color: #084298;">
                    <i class="fa-solid fa-badge-check"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?php echo $stats['verified_reports']; ?></h3>
                    <p class="stat-label">Laporan Terverifikasi</p>
                </div>
            </div>
        </div>

        <!-- Recent Reports Section -->
        <section class="content-section">
            <h2 class="section-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Laporan Terbaru
            </h2>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Hewan</th>
                        <th>Tipe</th>
                        <th>Spesies</th>
                        <th>Status</th>
                        <th>Pelapor</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_reports): ?>
                        <?php foreach ($recent_reports as $report): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($report['pet_name'] ?? 'N/A'); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo $report['type'] === 'lost' ? 'badge-warning' : 'badge-success'; ?>">
                                    <?php echo $report['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($report['species']); ?></td>
                            <td>
                                <span class="badge <?php echo $report['status'] === 'active' ? 'badge-primary' : 'badge-info'; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($report['name']); ?><br><?php echo htmlspecialchars($report['email']); ?></small>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($report['created_at'])); ?></td>
                            <td>
                                <a href="reports.php?detail=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada laporan</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <a href="reports.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fa-solid fa-arrow-right"></i> Lihat Semua Laporan
            </a>
        </section>
    </main>
</body>
</html>
