<?php
/**
 * Admin Reports Management
 * PetFounds - Pet Finder Network
 */

session_start();
require_once dirname(__FILE__) . '/../../lib/admin_auth.php';
require_once dirname(__FILE__) . '/../../lib/functions.php';
require_once dirname(__FILE__) . '/../../config/database.php';

requireAdminLogin();
$admin = getAdminInfo();

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
    DB_USER,
    DB_PASS
);

// Helper function to get proper image URL for admin panel
function getImageUrl($imagePath) {
    if (!$imagePath) {
        return '';
    }

    $imagePath = trim($imagePath);

    // If already a full URL, return as is
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    // Remove leading slashes and ../
    $imagePath = ltrim($imagePath, '/');
    $imagePath = preg_replace('#^(\.\./)+#', '', $imagePath);

    // Get app root from SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/lost-and-found-pet/pages/admin/reports.php';
    $appRoot = dirname(dirname(dirname($scriptName)));

    if ($appRoot === '/' || $appRoot === '\\' || $appRoot === '') {
        return '/' . $imagePath;
    }

    return rtrim($appRoot, '/') . '/' . $imagePath;
}

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
    <link rel="stylesheet" href="../../public/css/report-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        <?php if ($report_detail):
            $petImageUrl = getImageUrl($report_detail['image_url']);
            if (!$petImageUrl) {
                $petImageUrl = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"%3E%3Crect fill="%23e0e0e0" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" font-size="24" fill="%23999" text-anchor="middle" dy=".3em"%3ENo Image%3C/text%3E%3C/svg%3E';
            }
            $avatarUrl = $report_detail['avatar_url'] ?? '';
            if (!$avatarUrl) {
                $avatarUrl = generateAvatarUrl($report_detail['user_name'] ?? 'User');
            }
        ?>
        <div class="modal" id="reportDetailModal" style="display: block;">
            <div class="modal-content-modern">
                <!-- Hero Image Section -->
                <div class="hero-image-section">
                    <img src="<?php echo htmlspecialchars($petImageUrl); ?>" alt="Pet Image" class="hero-image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22350%22 height=%22350%22 viewBox=%220 0 350 350%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22350%22 height=%22350%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2232%22 fill=%22%23999%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                    <button class="modal-close-btn" onclick="window.location.href='reports.php'">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Main Card Content -->
                <div class="modal-card-content">
                    <!-- Pet Name -->
                    <div class="pet-header-section">
                        <h2 class="pet-title"><?php echo htmlspecialchars($report_detail['pet_name'] ?? 'Hewan Peliharaan'); ?></h2>

                        <!-- Status Badges -->
                        <div class="badges-row">
                            <span class="badge badge-type badge-<?php echo $report_detail['type'] === 'lost' ? 'warning' : 'success'; ?>">
                                <i class="fa-solid fa-<?php echo $report_detail['type'] === 'lost' ? 'magnifying-glass' : 'check-circle'; ?>"></i>
                                <?php echo $report_detail['type'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                            </span>
                            <span class="badge badge-verify badge-<?php echo $report_detail['is_verified'] ? 'success' : 'secondary'; ?>">
                                <i class="fa-solid fa-<?php echo $report_detail['is_verified'] ? 'certificate' : 'question'; ?>"></i>
                                <?php echo $report_detail['is_verified'] ? 'Terverifikasi' : 'Belum Verifikasi'; ?>
                            </span>
                            <span class="badge badge-status badge-<?php echo $report_detail['status'] === 'active' ? 'primary' : 'info'; ?>">
                                <i class="fa-solid fa-<?php echo $report_detail['status'] === 'active' ? 'circle-dot' : 'check-double'; ?>"></i>
                                <?php echo $report_detail['status'] === 'active' ? 'Aktif' : 'Selesai'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Quick Info Grid -->
                    <div class="quick-info-grid">
                        <div class="info-item">
                            <span class="info-label">Spesies</span>
                            <span class="info-value"><?php echo htmlspecialchars($report_detail['species']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ras</span>
                            <span class="info-value"><?php echo htmlspecialchars($report_detail['species_detail'] ?? 'Tidak ditentukan'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Kejadian</span>
                            <span class="info-value"><?php echo $report_detail['event_date'] ? date('d M Y', strtotime($report_detail['event_date'])) : 'N/A'; ?></span>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="content-section">
                        <h3 class="section-title"><i class="fa-solid fa-align-left"></i> Deskripsi</h3>
                        <p class="section-text"><?php echo nl2br(htmlspecialchars($report_detail['description'])); ?></p>
                    </div>

                    <!-- Location Section -->
                    <div class="content-section location-section">
                        <h3 class="section-title"><i class="fa-solid fa-location-dot"></i> Lokasi Terakhir</h3>
                        <div class="location-display">
                            <p class="location-address"><?php echo htmlspecialchars($report_detail['location']); ?></p>
                            <p class="location-description"><?php echo nl2br(htmlspecialchars($report_detail['location_description'] ?? 'Tidak ada keterangan tambahan')); ?></p>
                            <?php if ($report_detail['latitude'] && $report_detail['longitude']): ?>
                            <div class="map-container" id="reportMap"></div>
                            <p class="coordinates-info">
                                <i class="fa-solid fa-satellite"></i>
                                <?php echo htmlspecialchars($report_detail['latitude']); ?>, <?php echo htmlspecialchars($report_detail['longitude']); ?>
                            </p>
                            <?php else: ?>
                            <div class="no-map-available">
                                <i class="fa-solid fa-map"></i>
                                <p>Koordinat lokasi tidak tersedia</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reporter Card -->
                    <div class="reporter-card">
                        <div class="reporter-avatar-wrapper">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="reporter-avatar-img" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2270%22 height=%2270%22 viewBox=%220 0 70 70%22%3E%3Ccircle cx=%2235%22 cy=%2235%22 r=%2235%22 fill=%224f46e5%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2228%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3E?%3C/text%3E%3C/svg%3E'">
                        </div>
                        <div class="reporter-info-wrapper">
                            <p class="reporter-name"><?php echo htmlspecialchars($report_detail['user_name']); ?></p>
                            <div class="reporter-detail-item">
                                <i class="fa-solid fa-envelope"></i>
                                <span><?php echo htmlspecialchars($report_detail['email']); ?></span>
                            </div>
                            <div class="reporter-detail-item">
                                <i class="fa-solid fa-phone"></i>
                                <span><?php echo htmlspecialchars($report_detail['phone'] ?? 'Tidak tersedia'); ?></span>
                            </div>
                            <div class="reporter-detail-item">
                                <i class="fa-solid fa-clock"></i>
                                <span><?php echo date('d M Y H:i', strtotime($report_detail['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Close Button Footer -->
                    <a href="reports.php" class="close-button-footer">
                        <i class="fa-solid fa-xmark"></i> Tutup
                    </a>
                </div>
            </div>
        </div>

        <script>
        // Initialize Leaflet map if coordinates exist
        function initializeMap() {
            const mapElement = document.getElementById('reportMap');
            if (!mapElement) {
                console.log('Map element not found');
                return;
            }

            const lat = <?php echo $report_detail['latitude'] ?? 'null'; ?>;
            const lng = <?php echo $report_detail['longitude'] ?? 'null'; ?>;
            const petName = "<?php echo addslashes(htmlspecialchars($report_detail['pet_name'] ?? 'Hewan Peliharaan')); ?>";

            console.log('Map coordinates:', lat, lng);

            if (lat && lng && typeof L !== 'undefined') {
                try {
                    // Set explicit dimensions before init
                    mapElement.style.width  = '100%';
                    mapElement.style.height = '350px';

                    const map = L.map(mapElement, {
                        center: [lat, lng],
                        zoom: 16,
                        scrollWheelZoom: false,
                        zoomControl: true
                    });

                    // CartoDB tile — tanpa {r} agar tidak blank di semua browser
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
                        maxZoom: 20,
                        subdomains: 'abcd'
                    }).addTo(map);

                    // Custom marker icon (hindari broken icon default di beberapa server)
                    const markerIcon = L.icon({
                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                        iconSize:    [25, 41],
                        iconAnchor:  [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize:  [41, 41]
                    });

                    const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);
                    marker.bindPopup('<strong>' + petName + '</strong>').openPopup();

                    // invalidateSize berlapis: segera, 300ms, 600ms, 1s
                    // — memastikan map tidak blank saat di dalam modal/container
                    map.invalidateSize();
                    [300, 600, 1000].forEach(function(delay) {
                        setTimeout(function() { map.invalidateSize(); }, delay);
                    });

                    console.log('Map initialized successfully');
                } catch (error) {
                    console.error('Error initializing map:', error);
                }
            } else {
                console.log('Missing coordinates or Leaflet library');
            }
        }

        // Jalankan setelah DOM siap; Leaflet sudah dimuat di <head>
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeMap);
        } else {
            initializeMap();
        }
        </script>
        <?php endif; ?>
    </main>
</body>
</html>