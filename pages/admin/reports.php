<?php
/**
 * Admin Reports Management
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
    $report_id = intval($_POST['report_id'] ?? 0);

    if ($action === 'verify' && $report_id > 0) {
        $stmt = $pdo->prepare('UPDATE pet_reports SET is_verified = 1, verified_by = ?, verified_at = NOW() WHERE id = ?');
        $stmt->execute([$admin['id'], $report_id]);
        logAdminAction($admin['id'], 'VERIFY_REPORT', "Verify report ID $report_id", 'pet_reports', $report_id);
    } elseif ($action === 'resolve' && $report_id > 0) {
        $stmt = $pdo->prepare('UPDATE pet_reports SET status = "resolved" WHERE id = ?');
        $stmt->execute([$report_id]);
        logAdminAction($admin['id'], 'RESOLVE_REPORT', "Resolve report ID $report_id", 'pet_reports', $report_id);
    } elseif ($action === 'activate' && $report_id > 0) {
        $stmt = $pdo->prepare('UPDATE pet_reports SET status = "active" WHERE id = ?');
        $stmt->execute([$report_id]);
        logAdminAction($admin['id'], 'ACTIVATE_REPORT', "Activate report ID $report_id", 'pet_reports', $report_id);
    } elseif ($action === 'delete' && $report_id > 0) {
        $stmt = $pdo->prepare('DELETE FROM pet_reports WHERE id = ?');
        $stmt->execute([$report_id]);
        logAdminAction($admin['id'], 'DELETE_REPORT', "Delete report ID $report_id", 'pet_reports', $report_id);
    }

    header('Location: reports.php');
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all'; // all, lost, found
$status_filter = $_GET['status'] ?? 'all'; // all, active, resolved
$verified_filter = $_GET['verified'] ?? 'all'; // all, verified, unverified

// Build query
$query = 'SELECT pr.*, u.name as user_name, u.email FROM pet_reports pr JOIN users u ON pr.user_id = u.id WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (pr.pet_name LIKE ? OR pr.location LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter !== 'all') {
    $query .= ' AND pr.type = ?';
    $params[] = $type_filter;
}

if ($status_filter !== 'all') {
    $query .= ' AND pr.status = ?';
    $params[] = $status_filter;
}

if ($verified_filter === 'verified') {
    $query .= ' AND pr.is_verified = 1';
} elseif ($verified_filter === 'unverified') {
    $query .= ' AND pr.is_verified = 0';
}

$query .= ' ORDER BY pr.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report detail if requested
$report_detail = null;
if (isset($_GET['detail'])) {
    $detail_id = intval($_GET['detail']);
    $stmt = $pdo->prepare('SELECT pr.*, u.name as user_name, u.email, u.phone, u.avatar_url FROM pet_reports pr JOIN users u ON pr.user_id = u.id WHERE pr.id = ?');
    $stmt->execute([$detail_id]);
    $report_detail = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - Admin PetFounds</title>
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
            <a href="reports.php" class="nav-item active">
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
            <h1><i class="fa-solid fa-file-lines"></i> Kelola Laporan</h1>
            <p class="text-muted">Verifikasi, edit, dan kelola laporan hewan</p>
        </header>

        <!-- Filters and Search -->
        <div class="admin-toolbar">
            <form method="GET" class="search-form" style="display: grid; grid-template-columns: 1fr 150px 150px 150px 120px; gap: 10px;">
                <input type="text" name="search" placeholder="Cari laporan..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="type">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Semua Tipe</option>
                    <option value="lost" <?php echo $type_filter === 'lost' ? 'selected' : ''; ?>>Hilang</option>
                    <option value="found" <?php echo $type_filter === 'found' ? 'selected' : ''; ?>>Ditemukan</option>
                </select>
                <select name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Terselesaikan</option>
                </select>
                <select name="verified">
                    <option value="all" <?php echo $verified_filter === 'all' ? 'selected' : ''; ?>>Semua Verifikasi</option>
                    <option value="verified" <?php echo $verified_filter === 'verified' ? 'selected' : ''; ?>>Terverifikasi</option>
                    <option value="unverified" <?php echo $verified_filter === 'unverified' ? 'selected' : ''; ?>>Belum Verifikasi</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search"></i> Cari
                </button>
            </form>
        </div>

        <!-- Reports Table -->
        <section class="content-section">
            <table class="admin-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Hewan</th>
                        <th>Tipe</th>
                        <th>Lokasi</th>
                        <th>Pelapor</th>
                        <th>Status</th>
                        <th>Verifikasi</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports): ?>
                        <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($report['pet_name'] ?? 'N/A'); ?></strong><br><small><?php echo htmlspecialchars($report['species']); ?></small></td>
                            <td>
                                <span class="badge <?php echo $report['type'] === 'lost' ? 'badge-warning' : 'badge-success'; ?>">
                                    <?php echo $report['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                                </span>
                            </td>
                            <td><small><?php echo htmlspecialchars($report['location']); ?></small></td>
                            <td><small><?php echo htmlspecialchars($report['user_name']); ?><br><?php echo htmlspecialchars($report['email']); ?></small></td>
                            <td>
                                <span class="badge <?php echo $report['status'] === 'active' ? 'badge-primary' : 'badge-info'; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($report['is_verified']): ?>
                                <span class="badge badge-success">✓ Terverifikasi</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Belum Verifikasi</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons" style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="?detail=<?php echo $report['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <?php if (!$report['is_verified']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Verifikasi" onclick="return confirm('Verifikasi laporan ini?')">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($report['status'] === 'active'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="resolve">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Tandai Terselesaikan" onclick="return confirm('Tandai laporan ini sebagai terselesaikan?')">
                                            <i class="fa-solid fa-flag-checkered"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Aktifkan Kembali" onclick="return confirm('Aktifkan kembali laporan ini?')">
                                            <i class="fa-solid fa-redo"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Hapus laporan ini? Tindakan ini tidak dapat dibatalkan.')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada laporan ditemukan</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Report Detail Modal -->
        <?php if ($report_detail): ?>
        <div class="modal" id="reportDetailModal" style="display: block;">
            <div class="modal-content" style="max-width: 700px;">
                <div class="modal-header">
                    <h2>Detail Laporan</h2>
                    <a href="reports.php" class="close">&times;</a>
                </div>
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 20px; margin-bottom: 20px;">
                        <img src="<?php echo htmlspecialchars($report_detail['image_url'] ?? 'https://via.placeholder.com/200'); ?>" alt="Pet Image" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                        <div>
                            <h3><?php echo htmlspecialchars($report_detail['pet_name'] ?? 'N/A'); ?></h3>
                            <p><strong>Tipe:</strong> <span class="badge <?php echo $report_detail['type'] === 'lost' ? 'badge-warning' : 'badge-success'; ?>"><?php echo $report_detail['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?></span></p>
                            <p><strong>Spesies:</strong> <?php echo htmlspecialchars($report_detail['species']); ?> (<?php echo htmlspecialchars($report_detail['species_detail']); ?>)</p>
                            <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($report_detail['location']); ?></p>
                            <p><strong>Tanggal Kejadian:</strong> <?php echo $report_detail['event_date'] ? date('d M Y', strtotime($report_detail['event_date'])) : 'N/A'; ?></p>
                            <p><strong>Status:</strong> <span class="badge <?php echo $report_detail['status'] === 'active' ? 'badge-primary' : 'badge-info'; ?>"><?php echo ucfirst($report_detail['status']); ?></span></p>
                            <p><strong>Verifikasi:</strong> <?php echo $report_detail['is_verified'] ? '✓ Terverifikasi' : 'Belum Verifikasi'; ?></p>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h4>Deskripsi</h4>
                        <p><?php echo htmlspecialchars($report_detail['description']); ?></p>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h4>Lokasi Detail</h4>
                        <p><?php echo htmlspecialchars($report_detail['location_description'] ?? 'Tidak ada deskripsi tambahan'); ?></p>
                        <?php if ($report_detail['latitude'] && $report_detail['longitude']): ?>
                        <p><strong>Koordinat:</strong> <?php echo htmlspecialchars($report_detail['latitude']); ?>, <?php echo htmlspecialchars($report_detail['longitude']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <h4>Info Pelapor</h4>
                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                            <img src="<?php echo htmlspecialchars($report_detail['avatar_url']); ?>" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%;">
                            <div>
                                <p><strong><?php echo htmlspecialchars($report_detail['user_name']); ?></strong></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($report_detail['email']); ?></p>
                                <p><strong>Telepon:</strong> <?php echo htmlspecialchars($report_detail['phone'] ?? 'N/A'); ?></p>
                                <p><strong>Dilaporkan:</strong> <?php echo date('d M Y H:i', strtotime($report_detail['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="reports.php" class="btn btn-secondary">Tutup</a>
                </div>
            </div>
        </div>
        <style>
            .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
            .modal-content { background-color: white; margin: 30px auto; padding: 0; border-radius: 8px; width: 90%; }
            .modal-header { padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
            .modal-header h2 { margin: 0; }
            .modal-body { padding: 20px; max-height: calc(100vh - 200px); overflow-y: auto; }
            .modal-footer { padding: 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end; }
            .close { color: #aaa; cursor: pointer; font-size: 28px; font-weight: bold; }
            .close:hover { color: black; }
        </style>
        <?php endif; ?>
    </main>
</body>
</html>
