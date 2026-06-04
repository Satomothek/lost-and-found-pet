<?php
/**
 * Create Report Page
 * /pages/create_report.php
 */

require_once '../lib/auth.php';
require_once '../config/database.php';
require_once '../lib/functions.php';

// Require login
requireLogin();
$currentUser = getCurrentUser();

$editReportId = intval($_GET['edit'] ?? 0);
$editReport = null;
if ($editReportId > 0) {
    $query = "SELECT * FROM pet_reports WHERE id = ? AND user_id = ?";
    $editReport = fetchOne($connection, $query, [$editReportId, $currentUser['id']]);
}

$createReportError = $_SESSION['create_report_error'] ?? null;
$createReportSuccess = $_SESSION['create_report_success'] ?? null;
unset($_SESSION['create_report_error'], $_SESSION['create_report_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = $editReportId;
    $type = sanitizeInput($_POST['type'] ?? '');
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $speciesDetail = sanitizeInput($_POST['species_detail'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $locationDescription = sanitizeInput($_POST['location_description'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $reportDate = sanitizeInput($_POST['date'] ?? '');
    $latitude = sanitizeInput($_POST['latitude'] ?? '');
    $longitude = sanitizeInput($_POST['longitude'] ?? '');

    if ($latitude !== '' && !is_numeric($latitude)) {
        $_SESSION['create_report_error'] = 'Koordinat latitude tidak valid';
        header('Location: create_report.php' . ($reportId > 0 ? '?edit=' . $reportId : ''));
        exit;
    }
    if ($longitude !== '' && !is_numeric($longitude)) {
        $_SESSION['create_report_error'] = 'Koordinat longitude tidak valid';
        header('Location: create_report.php' . ($reportId > 0 ? '?edit=' . $reportId : ''));
        exit;
    }

    if (!$type || !$species || !$location || !$locationDescription || !$description || !$reportDate) {
        $_SESSION['create_report_error'] = 'Semua field wajib diisi';
        header('Location: create_report.php' . ($reportId > 0 ? '?edit=' . $reportId : ''));
        exit;
    }

    if (!in_array($type, ['lost', 'found'])) {
        $_SESSION['create_report_error'] = 'Tipe laporan tidak valid';
        header('Location: create_report.php' . ($reportId > 0 ? '?edit=' . $reportId : ''));
        exit;
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $reportDate);
    $today = new DateTime('today');
    $minDate = (clone $today)->modify('-7 days');

    if (!$dateObj || $dateObj < $minDate || $dateObj > $today) {
        $_SESSION['create_report_error'] = 'Tanggal harus antara ' . $minDate->format('Y-m-d') . ' sampai ' . $today->format('Y-m-d');
        header('Location: create_report.php' . ($reportId > 0 ? '?edit=' . $reportId : ''));
        exit;
    }

    if ($reportId > 0) {
        $checkQuery = "SELECT user_id, image_url FROM pet_reports WHERE id = ?";
        $existingReport = fetchOne($connection, $checkQuery, [$reportId]);
        if (!$existingReport) {
            $_SESSION['create_report_error'] = 'Laporan tidak ditemukan';
            header('Location: create_report.php?edit=' . $reportId);
            exit;
        }
        if ($existingReport['user_id'] != $currentUser['id']) {
            $_SESSION['create_report_error'] = 'Anda tidak memiliki akses untuk mengubah laporan ini';
            header('Location: create_report.php?edit=' . $reportId);
            exit;
        }

        $imageUrl = $existingReport['image_url'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['image'], '../public/uploads/reports/');
            if ($upload['success']) {
                $imageUrl = 'public/uploads/reports/' . $upload['filename'];
            }
        }

        $query = "UPDATE pet_reports SET type = ?, pet_name = ?, species = ?, species_detail = ?, location = ?, location_description = ?, description = ?, event_date = ?, image_url = ?, latitude = ?, longitude = ? WHERE id = ?";
        $result = executeQuery($connection, $query, [
            $type,
            $petName,
            $species,
            $speciesDetail,
            $location,
            $locationDescription,
            $description,
            $reportDate,
            $imageUrl,
            $latitude !== '' ? floatval($latitude) : null,
            $longitude !== '' ? floatval($longitude) : null,
            $reportId
        ]);

        if ($result['success']) {
            $_SESSION['create_report_success'] = 'Laporan berhasil diperbarui';
            header('Location: explore.php');
            exit;
        }

        $_SESSION['create_report_error'] = 'Gagal memperbarui laporan: ' . $result['error'];
        header('Location: create_report.php?edit=' . $reportId);
        exit;
    }

    $imageUrl = 'https://via.placeholder.com/600x400?text=Pet+Image';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image'], '../public/uploads/reports/');
        if ($upload['success']) {
            $imageUrl = 'public/uploads/reports/' . $upload['filename'];
        }
    }

    $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, species_detail, location, location_description, description, image_url, latitude, longitude, event_date)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $result = executeQuery($connection, $query, [
        $currentUser['id'],
        $type,
        $petName,
        $species,
        $speciesDetail,
        $location,
        $locationDescription,
        $description,
        $imageUrl,
        $latitude !== '' ? floatval($latitude) : null,
        $longitude !== '' ? floatval($longitude) : null,
        $reportDate
    ]);

    if ($result['success']) {
        $_SESSION['create_report_success'] = 'Laporan berhasil dibuat';
        header('Location: explore.php');
        exit;
    }

    $_SESSION['create_report_error'] = 'Gagal membuat laporan: ' . $result['error'];
    header('Location: create_report.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editReport ? 'Edit Laporan - PetFounds' : 'Buat Laporan - PetFounds'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/pages/create-report.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- TensorFlow.js + COCO-SSD untuk validasi foto hewan -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.15.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js"></script>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-ball color-1"></div>
        <div class="bg-ball color-2"></div>
        <div class="bg-ball color-3"></div>
    </div>

    <div id="toast-container"></div>

    <div class="app-wrapper">
        <aside class="sidebar bg-surface">
            <div class="sidebar-header">
                <div class="app-logo">
                    <div class="logo-icon flex-center"><i class="fa-solid fa-paw"></i></div>
                    <span class="logo-text">Pet<span class="text-gradient">Founds</span></span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="explore.php" class="nav-item">
                    <i class="fa-solid fa-compass"></i>
                    <span>Jelajahi</span>
                </a>
                <a href="create_report.php" class="nav-item active">
                    <i class="fa-solid fa-plus"></i>
                    <span>Buat Laporan</span>
                </a>
                <a href="messages.php" class="nav-item">
                    <i class="fa-solid fa-comments"></i>
                    <span>Pesan</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fa-solid fa-user-circle"></i>
                    <span>Profil</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <button id="btn-logout" class="btn btn-ghost" title="Logout">
                    <i class="fa-solid fa-sign-out-alt"></i>
                </button>
            </div>
        </aside>

        <main class="main-content page-pt">
            <div class="container-fluid">
                <div class="report-create-wrapper glass-panel">
                    <div class="page-header" style="margin-bottom: 30px;">
                        <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;"><?php echo $editReport ? 'Edit Laporan' : 'Publikasikan Laporan Jaringan'; ?></h1>
                        <p style="color: var(--text-muted);"><?php echo $editReport ? 'Perbarui detail laporan Anda.' : 'Isi detail laporan untuk membantu komunitas menemukan atau melaporkan hewan.'; ?></p>
                    </div>

                    <form id="form-create-report" class="glass-panel-form" enctype="multipart/form-data" method="POST" action="create_report.php<?php echo $editReport ? '?edit=' . $editReportId : ''; ?>">
                        <?php if ($editReport): ?>
                            <input type="hidden" name="report_id" value="<?php echo $editReportId; ?>">
                        <?php endif; ?>

                        <!-- Jenis Laporan -->
                        <div class="form-section">
                            <div class="form-section-label"><i class="fa-solid fa-flag"></i> Jenis Laporan</div>
                            <div class="report-type-selector">
                                <label class="type-card">
                                    <input type="radio" id="type-found-radio" name="type" value="found" checked>
                                    <div class="type-content found-glow">
                                        <i class="fa-solid fa-hand-holding-heart"></i>
                                        <span>Menemukan Hewan</span>
                                    </div>
                                </label>
                                <label class="type-card">
                                    <input type="radio" id="type-lost-radio" name="type" value="lost">
                                    <div class="type-content lost-glow">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span>Kehilangan Hewan</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Foto Hewan -->
                        <div class="form-section">
                            <div class="form-section-label"><i class="fa-solid fa-camera"></i> Foto Hewan</div>

                            <!-- State kosong -->
                            <div class="upload-card" id="upload-card-empty">
                                <label for="report-image" style="cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:14px;">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <strong>Tarik foto atau klik untuk unggah <span style="color:var(--danger);">*</span></strong>
                                    <span style="color:var(--text-muted);">JPG, PNG, WebP &middot; Maks. 5MB</span>
                                    <input type="file" id="report-image" name="image" accept="image/*" style="display:none;" <?php echo $editReport ? '' : 'required'; ?>>
                                    <span id="selected-file-name" style="color:var(--accent-primary); font-weight:600; font-size:0.85rem;">Tidak ada file dipilih</span>
                                </label>
                            </div>

                            <!-- State preview -->
                            <div id="upload-card-preview" style="display:none; position:relative; border-radius:18px; overflow:hidden; margin-bottom:32px; border:2px solid var(--input-border); background:#f8fafc;">
                                <img id="preview-img" src="" alt="Preview"
                                     style="width:100%; max-height:340px; object-fit:cover; display:block; border-radius:16px;">
                                <div style="position:absolute; bottom:0; left:0; right:0; height:80px;
                                            background:linear-gradient(to top,rgba(0,0,0,0.55),transparent);
                                            border-radius:0 0 16px 16px; pointer-events:none;"></div>
                                <div style="position:absolute; bottom:14px; left:16px; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-image" style="color:#fff; font-size:0.85rem;"></i>
                                    <span id="preview-file-name" style="color:#fff; font-size:0.82rem; font-weight:600;
                                          max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></span>
                                </div>
                                <button type="button" id="btn-change-photo"
                                        style="position:absolute; top:12px; right:12px;
                                               background:rgba(255,255,255,0.92); border:none; cursor:pointer;
                                               padding:7px 14px; border-radius:20px; font-size:0.8rem; font-weight:700;
                                               color:#1e293b; display:flex; align-items:center; gap:6px;
                                               box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                                    <i class="fa-solid fa-arrow-rotate-left"></i> Ganti Foto
                                </button>
                                <div id="preview-validation-badge"
                                     style="position:absolute; top:12px; left:12px; display:none;
                                            padding:6px 12px; border-radius:20px; font-size:0.78rem; font-weight:700;
                                            align-items:center; gap:6px; backdrop-filter:blur(6px);"></div>
                            </div>
                        </div>

                        <!-- Lokasi -->
                        <div class="form-section">
                            <div class="form-section-label"><i class="fa-solid fa-map-location-dot"></i> Lokasi GPS</div>
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($editReport['latitude'] ?? ''); ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($editReport['longitude'] ?? ''); ?>">
                            <input type="hidden" id="location" name="location" value="<?php echo htmlspecialchars($editReport['location'] ?? ''); ?>">

                            <!-- Pilihan mode lokasi -->
                            <div class="location-mode-selector">
                                <label class="location-mode-card">
                                    <input type="radio" name="location_mode" value="gps" id="mode-gps" checked>
                                    <div class="location-mode-content">
                                        <i class="fa-solid fa-location-crosshairs"></i>
                                        <span>Gunakan GPS<br>Sekarang</span>
                                    </div>
                                </label>
                                <label class="location-mode-card">
                                    <input type="radio" name="location_mode" value="pick" id="mode-pick">
                                    <div class="location-mode-content">
                                        <i class="fa-solid fa-map-pin"></i>
                                        <span>Pilih Sendiri<br>di Peta</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Hint saat mode pick -->
                            <div class="map-pick-hint" id="map-pick-hint">
                                <i class="fa-solid fa-hand-pointer"></i>
                                Klik di peta untuk menentukan titik lokasi. Lingkaran menunjukkan radius ~15–20 m.
                            </div>

                            <div class="report-map-section report-map-wrapper">
                                <div id="report-map" class="report-map"></div>
                            </div>
                            <div class="form-grid" style="margin-bottom: 14px;">
                                <div class="input-modern">
                                    <i class="fa-regular fa-calendar-days"></i>
                                    <input type="date" id="report-date" name="date" placeholder="Tanggal" required>
                                </div>
                                <div class="input-modern">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <input type="text" id="location-display" placeholder="Lokasi (Otomatis dari GPS)" disabled>
                                </div>
                            </div>
                            <div class="input-modern">
                                <i class="fa-solid fa-map-pin"></i>
                                <input type="text" id="location_description" name="location_description" placeholder="Deskripsi Lokasi / Landmark (ex: Dekat minimarket ABC...)" required>
                            </div>
                        </div>

                        <!-- Detail Hewan -->
                        <div class="form-section">
                            <div class="form-section-label"><i class="fa-solid fa-paw"></i> Detail Hewan</div>
                            <div class="form-grid" style="margin-bottom: 14px;">
                                <div class="input-modern">
                                    <i class="fa-solid fa-tag"></i>
                                    <input type="text" id="pet-name" name="pet_name" placeholder="Nama Hewan (Opsional)">
                                </div>
                                <div class="input-modern">
                                    <i class="fa-solid fa-paw"></i>
                                    <select id="species">
                                        <option value="" disabled selected>Spesies Hewan...</option>
                                        <option value="Anjing">Anjing</option>
                                        <option value="Kucing">Kucing</option>
                                        <option value="Burung">Burung</option>
                                        <option value="Kelinci">Kelinci</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <input type="hidden" id="species-final" name="species">
                                </div>
                            </div>
                            <div id="custom-species-container" class="input-modern" style="display: none;">
                                <i class="fa-solid fa-paw"></i>
                                <input type="text" id="custom-species" name="custom_species" placeholder="Sebutkan spesies hewan..." autocomplete="off">
                            </div>
                            <div class="input-modern">
                                <i class="fa-solid fa-dna"></i>
                                <input type="text" id="species-detail" name="species_detail" placeholder="Detail spesies / ras (opsional)">
                            </div>
                            <div class="input-modern textarea-field">
                                <i class="fa-solid fa-align-left"></i>
                                <textarea id="description" name="description" rows="5" placeholder="Detail ciri-ciri khusus, kronologi, warna kalung, dll..." required style="min-height: 140px;"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg form-submit-btn"><?php echo $editReport ? 'Perbarui Laporan' : 'Publikasikan Laporan'; ?></button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../public/js/utils.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        const currentPage = 'create';
        const createReportError = <?php echo json_encode($createReportError); ?>;
        const createReportSuccess = <?php echo json_encode($createReportSuccess); ?>;
        const editReport = <?php echo json_encode($editReport); ?>;
    </script>
    <script src="../public/js/pages/create-report.js"></script>
</body>
</html>