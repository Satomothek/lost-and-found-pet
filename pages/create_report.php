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
            $upload = uploadImage($_FILES['image'], '../public/uploads/');
            if ($upload['success']) {
                $imageUrl = 'public/uploads/' . $upload['filename'];
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
        $upload = uploadImage($_FILES['image'], '../public/uploads/');
        if ($upload['success']) {
            $imageUrl = 'public/uploads/' . $upload['filename'];
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* ── IMPORT FONT ── */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap');

        /* ── DESIGN TOKENS ── */
        :root {
            --form-bg: #ffffff;
            --form-radius: 20px;
            --accent-found: #10b981;
            --accent-lost: #f59e0b;
            --accent-primary: #6366f1;
            --accent-primary-light: #eef2ff;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-border-focus: #6366f1;
            --input-shadow-focus: 0 0 0 3px rgba(99, 102, 241, 0.15);
            --label-color: #64748b;
            --text-main: #0f172a;
            --text-muted: #94a3b8;
            --section-bg: #f8fafc;
            --divider: #f1f5f9;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(99,102,241,0.06);
            --card-shadow-hover: 0 4px 24px rgba(99,102,241,0.14);
        }

        /* ── WRAPPER & PANEL ── */
        .report-create-wrapper.glass-panel {
            background: var(--form-bg);
            border-radius: 28px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--divider);
            padding: 48px 52px;
            max-width: 760px;
            margin: 0 auto;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ── PAGE HEADER ── */
        .page-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 2rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #1e1b4b 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            color: var(--label-color);
            margin-top: 4px;
        }

        /* ── SECTION DIVIDER ── */
        .form-section {
            margin-bottom: 32px;
        }

        .form-section-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--divider);
        }

        /* ── TYPE SELECTOR ── */
        .report-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 32px;
        }

        .type-card {
            position: relative;
            cursor: pointer;
        }

        .type-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .type-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 22px 16px;
            border-radius: 16px;
            border: 2px solid var(--input-border);
            background: var(--input-bg);
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--label-color);
        }

        .type-content i {
            font-size: 1.7rem;
            transition: transform 0.22s ease;
        }

        .type-card:hover .type-content {
            border-color: var(--accent-primary);
            background: var(--accent-primary-light);
            color: var(--accent-primary);
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
        }

        .type-card:hover .type-content i {
            transform: scale(1.1);
        }

        .type-card.active .type-content.found-glow,
        .type-card input:checked + .type-content.found-glow {
            border-color: var(--accent-found);
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #065f46;
            box-shadow: 0 4px 20px rgba(16,185,129,0.18);
        }

        .type-card.active .type-content.lost-glow,
        .type-card input:checked + .type-content.lost-glow {
            border-color: var(--accent-lost);
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            color: #92400e;
            box-shadow: 0 4px 20px rgba(245,158,11,0.18);
        }

        /* ── UPLOAD AREA ── */
        .upload-card {
            border: 2px dashed var(--input-border) !important;
            border-radius: 18px !important;
            padding: 40px 24px !important;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%) !important;
            transition: all 0.22s ease !important;
            margin-bottom: 32px !important;
            position: relative;
            overflow: hidden;
        }

        .upload-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(99,102,241,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .upload-card:hover {
            border-color: var(--accent-primary) !important;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%) !important;
            box-shadow: var(--card-shadow-hover) !important;
            transform: translateY(-1px);
        }

        .upload-card label {
            cursor: pointer;
        }

        .upload-card i.fa-cloud-arrow-up {
            font-size: 3rem !important;
            color: var(--accent-primary) !important;
            filter: drop-shadow(0 4px 12px rgba(99,102,241,0.3));
        }

        .upload-card strong {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem !important;
            font-weight: 700;
            color: var(--text-main) !important;
        }

        .upload-card span {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
        }

        /* ── MAP ── */
        .report-map-wrapper {
            margin-bottom: 28px;
        }

        .report-map {
            width: 100% !important;
            height: 320px !important;
            min-height: 320px !important;
            border-radius: 16px;
            border: 2px solid var(--input-border);
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
            display: block !important;
            position: relative !important;
        }

        .leaflet-container {
            background: #e8f0fe !important;
            border-radius: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }

        /* Pastikan tile tidak ter-clip akibat overflow parent */
        #report-map .leaflet-pane {
            z-index: 400;
        }

        /* ── FORM INPUTS — override style.css .input-modern ── */
        .glass-panel-form .input-modern {
            position: static !important;
            display: flex !important;
            align-items: center;
            gap: 12px;
            background: var(--input-bg) !important;
            border: 1.5px solid var(--input-border) !important;
            border-radius: 14px !important;
            padding: 0 18px !important;
            margin-bottom: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            min-height: 52px;
            width: 100%;
            box-shadow: none !important;
        }

        .glass-panel-form .input-modern:focus-within {
            border-color: var(--input-border-focus) !important;
            box-shadow: var(--input-shadow-focus) !important;
            background: #ffffff !important;
        }

        /* Override absolute icon from style.css */
        .glass-panel-form .input-modern > i {
            position: static !important;
            transform: none !important;
            top: auto !important;
            left: auto !important;
            color: var(--text-muted);
            font-size: 1rem;
            flex-shrink: 0;
            width: 18px;
            text-align: center;
            transition: color 0.2s;
        }

        .glass-panel-form .input-modern:focus-within > i {
            color: var(--accent-primary);
        }

        .glass-panel-form .input-modern input,
        .glass-panel-form .input-modern select,
        .glass-panel-form .input-modern textarea {
            flex: 1 !important;
            border: none !important;
            outline: none !important;
            background: transparent !important;
            box-shadow: none !important;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            color: var(--text-main);
            padding: 14px 0 !important;
            width: auto !important;
            min-height: 0;
        }

        .glass-panel-form .input-modern input::placeholder,
        .glass-panel-form .input-modern textarea::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .glass-panel-form .input-modern select {
            cursor: pointer;
        }

        .glass-panel-form .input-modern select option {
            background: #fff;
            color: var(--text-main);
        }

        .glass-panel-form .input-modern.textarea-field {
            align-items: flex-start;
            padding-top: 14px !important;
            padding-bottom: 14px !important;
        }

        .glass-panel-form .input-modern.textarea-field > i {
            margin-top: 2px;
        }

        .glass-panel-form .input-modern textarea {
            resize: vertical;
            line-height: 1.6;
        }

        .glass-panel-form .input-modern input:disabled {
            color: var(--label-color);
            cursor: default;
        }

        /* ── FORM GRID ── */
        .glass-panel-form .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .glass-panel-form .form-grid .input-modern {
            margin-bottom: 0;
        }

        /* ── SUBMIT BUTTON ── */
        .form-submit-btn {
            width: 100% !important;
            padding: 16px 24px !important;
            border-radius: 16px !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            border: none !important;
            color: #fff !important;
            box-shadow: 0 4px 20px rgba(99,102,241,0.35), 0 1px 4px rgba(0,0,0,0.1) !important;
            transition: all 0.22s cubic-bezier(0.4,0,0.2,1) !important;
            cursor: pointer;
            margin-top: 8px;
        }

        .form-submit-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 28px rgba(99,102,241,0.45), 0 2px 8px rgba(0,0,0,0.12) !important;
        }

        .form-submit-btn:active {
            transform: translateY(0) !important;
        }

        /* ── FORM LABEL (inline) ── */
        .glass-panel-form > .form-label {
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted) !important;
            margin-bottom: 10px !important;
        }

        .report-map-section .form-label {
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted) !important;
        }
    </style>
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
                                    <select id="species" name="species" required>
                                        <option value="" disabled selected>Spesies Hewan...</option>
                                        <option value="Anjing">Anjing</option>
                                        <option value="Kucing">Kucing</option>
                                        <option value="Burung">Burung</option>
                                        <option value="Kelinci">Kelinci</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <div id="custom-species-container" class="input-modern" style="display: none;">
                                <i class="fa-solid fa-paw"></i>
                                <input type="text" id="custom-species" placeholder="Sebutkan spesies hewan...">
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

    <script src="../js/functions.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        const currentPage = 'create';
        const createReportError = <?php echo json_encode($createReportError); ?>;
        const createReportSuccess = <?php echo json_encode($createReportSuccess); ?>;
        const editReport = <?php echo json_encode($editReport); ?>;

        async function ensureLeafletLoaded() {
            if (window.L) {
                return true;
            }

            const existingLeafletCss = document.querySelector('link[href*="leaflet"]');
            if (!existingLeafletCss) {
                const leafletCss = document.createElement('link');
                leafletCss.rel = 'stylesheet';
                leafletCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
                leafletCss.crossOrigin = '';
                document.head.appendChild(leafletCss);
            }

            return new Promise((resolve) => {
                if (window.L) {
                    resolve(true);
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
                script.crossOrigin = '';
                script.onload = () => resolve(!!window.L);
                script.onerror = () => {
                    console.warn('Leaflet JS failed to load from fallback CDN.');
                    resolve(!!window.L);
                };
                document.head.appendChild(script);
            });
        }

        document.addEventListener('DOMContentLoaded', async () => {
            if (createReportSuccess) {
                showToast(createReportSuccess, 'success');
            }
            if (createReportError) {
                showToast(createReportError, 'error');
            }

            await ensureLeafletLoaded();
            setupCreateForm();
            setReportDateRange();
            setupLogout();
        });

        // Panggil invalidateSize lagi setelah semua asset selesai load
        window.addEventListener('load', () => {
            [200, 500, 1000].forEach(ms => {
                setTimeout(() => {
                    if (window.reportMap) window.reportMap.invalidateSize();
                }, ms);
            });
        });

        window.reportMap = null;
        let reportMap;
        let reportMarker;

        function initializeReportMap(latitude = -7.797068, longitude = 110.370529) {
            const mapEl = document.getElementById('report-map');
            if (!mapEl) {
                console.error('Map element not found');
                return;
            }

            // Force set height — override CSS padding/transform conflict
            mapEl.style.setProperty('height', '320px', 'important');
            mapEl.style.setProperty('width', '100%', 'important');
            mapEl.style.setProperty('display', 'block', 'important');
            mapEl.style.setProperty('min-height', '320px', 'important');

            if (!window.L) {
                console.error('Leaflet library not loaded');
                return;
            }

            try {
                if (!reportMap) {
                    reportMap = L.map('report-map').setView([latitude, longitude], 13);
                    window.reportMap = reportMap;
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap &copy; CARTO'
                    }).addTo(reportMap);

                    reportMap.on('click', function(e) {
                        setReportCoordinates(e.latlng.lat, e.latlng.lng);
                    });

                    // Resize map beberapa kali agar tiles pasti muncul
                    [100, 300, 600, 1000].forEach(ms => {
                        setTimeout(() => {
                            if (reportMap) reportMap.invalidateSize();
                        }, ms);
                    });
                } else {
                    reportMap.setView([latitude, longitude], 13);
                    reportMap.invalidateSize();
                }

                // Update or create marker
                if (reportMarker) {
                    reportMarker.setLatLng([latitude, longitude]);
                } else {
                    reportMarker = L.marker([latitude, longitude], {
                        draggable: true
                    }).addTo(reportMap);

                    reportMarker.on('dragend', function() {
                        const newLat = reportMarker.getLatLng().lat;
                        const newLng = reportMarker.getLatLng().lng;
                        setReportCoordinates(newLat, newLng);
                    });
                }
            } catch (error) {
                console.error('Map initialization error:', error);
            }
        }

        function setReportCoordinates(lat, lng) {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (latInput && lngInput) {
                latInput.value = lat;
                lngInput.value = lng;
            }
            initializeReportMap(lat, lng);
            reverseGeocodeCoordinates(lat, lng);
        }

        async function reverseGeocodeCoordinates(lat, lng) {
            try {
                const locationDisplay = document.getElementById('location-display');
                if (locationDisplay) {
                    locationDisplay.value = 'Mencari lokasi...';
                }

                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, {
                    headers: {
                        'Accept-Language': 'id'
                    }
                });

                if (!response.ok) {
                    throw new Error('Reverse geocoding failed');
                }

                const data = await response.json();
                const address = data.address || {};
                
                // Buat alamat dari komponen OpenStreetMap
                const addressParts = [];
                
                if (address.road || address.pedestrian || address.path) {
                    addressParts.push(address.road || address.pedestrian || address.path);
                }
                if (address.suburb || address.village) {
                    addressParts.push(address.suburb || address.village);
                }
                if (address.city || address.town || address.municipality) {
                    addressParts.push(address.city || address.town || address.municipality);
                }
                if (address.county || address.state_district) {
                    addressParts.push(address.county || address.state_district);
                }

                const fullAddress = addressParts.filter(p => p && p.length > 0).join(', ') || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                
                if (locationDisplay) {
                    locationDisplay.value = fullAddress;
                }
                const locationInput = document.getElementById('location');
                if (locationInput) {
                    locationInput.value = fullAddress;
                }

                showToast('Lokasi berhasil diperbarui', 'success');
            } catch (error) {
                console.error('Reverse geocoding error:', error);
                const locationDisplay = document.getElementById('location-display');
                if (locationDisplay) {
                    locationDisplay.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }
                const locationInput = document.getElementById('location');
                if (locationInput) {
                    locationInput.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }
            }
        }

        function requestCurrentLocation() {
            if (!navigator.geolocation) {
                initializeReportMap();
                return;
            }
            navigator.geolocation.getCurrentPosition((position) => {
                setReportCoordinates(position.coords.latitude, position.coords.longitude);
            }, (error) => {
                initializeReportMap();
                console.warn('Geolocation error', error);
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });
        }

        function setupCreateForm() {
            const existingLat = document.getElementById('latitude')?.value;
            const existingLng = document.getElementById('longitude')?.value;
            
            try {
                // Jika ada data existing, gunakan itu. Kalau tidak, minta geolocation
                if (existingLat && existingLng && existingLat !== '' && existingLng !== '') {
                    console.log('Using existing coordinates:', existingLat, existingLng);
                    initializeReportMap(parseFloat(existingLat), parseFloat(existingLng));
                    // Jika location_description tidak ada, lakukan reverse geocoding
                    const locationDisplay = document.getElementById('location-display');
                    if (locationDisplay && !locationDisplay.value) {
                        reverseGeocodeCoordinates(parseFloat(existingLat), parseFloat(existingLng));
                    }
                } else {
                    console.log('No existing coordinates, requesting geolocation');
                    requestCurrentLocation();
                }
            } catch (error) {
                console.error('Setup form error:', error);
                initializeReportMap(); // fallback
            }

            if (editReport) {
                document.getElementById('pet-name').value = editReport.pet_name || '';
                document.getElementById('species').value = editReport.species || '';
                document.getElementById('species-detail').value = editReport.species_detail || '';
                document.getElementById('location-display').value = editReport.location || '';
                document.getElementById('location_description').value = editReport.location_description || '';
                document.getElementById('description').value = editReport.description || '';
                const typeRadio = document.querySelector(`input[name="type"][value="${editReport.type}"]`);
                if (typeRadio) {
                    typeRadio.checked = true;
                    setReportType(editReport.type);
                }
                const knownSpeciesOptions = Array.from(document.querySelectorAll('#species option')).map(option => option.value);
                if (editReport.species) {
                    if (knownSpeciesOptions.includes(editReport.species)) {
                        document.getElementById('species').value = editReport.species;
                    } else {
                        document.getElementById('species').value = 'Lainnya';
                        document.getElementById('custom-species-container').style.display = 'block';
                        document.getElementById('custom-species').value = editReport.species;
                    }
                }
                const reportDateInput = document.getElementById('report-date');
                if (reportDateInput) {
                    let dateToUse = editReport.event_date || editReport.created_at;
                    const editDate = new Date(dateToUse);
                    const today = new Date();
                    const minDate = new Date(today);
                    minDate.setDate(minDate.getDate() - 7);
                    const formatDateForInput = (date) => {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };
                    const formattedEditDate = formatDateForInput(editDate);
                    const maxDate = formatDateForInput(today);
                    const minDateStr = formatDateForInput(minDate);
                    reportDateInput.min = minDateStr;
                    reportDateInput.max = maxDate;
                    reportDateInput.value = (editDate < minDate || editDate > today) ? maxDate : formattedEditDate;
                }
                document.querySelector('.form-submit-btn').textContent = 'Perbarui Laporan';

                // Preview foto existing saat mode edit
                if (editReport.image_url) {
                    const previewImg  = document.getElementById('preview-img');
                    const previewName = document.getElementById('preview-file-name');
                    if (previewImg) {
                        previewImg.src = '../' + editReport.image_url;
                        if (previewName) previewName.textContent = 'Foto saat ini';
                        document.getElementById('upload-card-empty').style.display   = 'none';
                        document.getElementById('upload-card-preview').style.display = 'block';
                        imageValidated = true; imageIsAnimal = true;
                    }
                }
            }

            const reportTypeRadios = document.querySelectorAll('input[name="type"]');
            const reportImage = document.getElementById('report-image');
            const selectedFileName = document.getElementById('selected-file-name');
            const reportForm = document.getElementById('form-create-report');
            const speciesSelect = document.getElementById('species');
            const customSpeciesContainer = document.getElementById('custom-species-container');
            const customSpeciesInput = document.getElementById('custom-species');
            const isEditMode = !!editReport;

            if (!reportImage) {
                console.error('Report image element not found');
                return;
            }

            if (isEditMode && reportImage) {
                reportImage.required = false;
            }

            speciesSelect.addEventListener('change', () => {
                if (speciesSelect.value === 'Lainnya') {
                    customSpeciesContainer.style.display = 'block';
                    customSpeciesInput.focus();
                } else {
                    customSpeciesContainer.style.display = 'none';
                    customSpeciesInput.value = '';
                }
            });

            reportTypeRadios.forEach((radio) => {
                radio.addEventListener('change', () => {
                    setReportType(radio.value);
                });
            });

            const initialType = document.querySelector('input[name="type"]:checked');
            if (initialType) {
                setReportType(initialType.value);
            }
            if (speciesSelect.value === 'Lainnya') {
                customSpeciesContainer.style.display = 'block';
            }

            reportImage.addEventListener('change', async () => {
                if (reportImage.files.length > 0) {
                    const file = reportImage.files[0];
                    showImagePreview(file);
                    document.getElementById('selected-file-name').textContent = file.name;
                    await validateImageWithTF(file);
                } else {
                    resetUploadState();
                }
            });

            // Tombol ganti foto
            const btnChangePhoto = document.getElementById('btn-change-photo');
            if (btnChangePhoto) {
                btnChangePhoto.addEventListener('click', () => {
                    reportImage.value = '';
                    resetUploadState();
                    reportImage.click();
                });
            }

            // Drag and drop
            const uploadCard = document.getElementById('upload-card-empty');
            if (uploadCard) {
                uploadCard.addEventListener('dragover', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    uploadCard.style.backgroundColor = 'rgba(59,130,246,0.1)';
                    uploadCard.style.borderColor = 'var(--primary)';
                });
                uploadCard.addEventListener('dragleave', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    uploadCard.style.backgroundColor = '';
                    uploadCard.style.borderColor = '';
                });
                uploadCard.addEventListener('drop', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    uploadCard.style.backgroundColor = '';
                    uploadCard.style.borderColor = '';
                    const files = e.dataTransfer.files;
                    if (files && files.length > 0) {
                        const dt = new DataTransfer();
                        for (let i = 0; i < files.length; i++) dt.items.add(files[i]);
                        reportImage.files = dt.files;
                        reportImage.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }

            reportForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const type = document.querySelector('input[name="type"]:checked');
                const species = speciesSelect.value.trim();
                const customSpecies = customSpeciesInput.value.trim();
                const description = document.getElementById('description').value.trim();
                const reportDate = document.getElementById('report-date').value.trim();
                const isEdit = !!editReport;

                if (!isEdit && (!reportImage.files || reportImage.files.length === 0)) {
                    showToast('Upload foto hewan terlebih dahulu', 'error');
                    if (reportImage) reportImage.focus();
                    return;
                }

                if (reportImage.files && reportImage.files.length > 0) {
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(reportImage.files[0].type)) {
                        showToast('Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WebP', 'error');
                        return;
                    }
                    if (reportImage.files[0].size > 5 * 1024 * 1024) {
                        showToast('Ukuran foto terlalu besar (max 5MB)', 'error');
                        return;
                    }

                    // ── Cek hasil validasi TensorFlow.js ──────────────────────
                    if (imageValidated && !imageIsAnimal) {
                        showToast('❌ Foto yang diupload bukan foto hewan. Mohon upload foto hewan peliharaan yang jelas.', 'error');
                        const uploadCard = document.querySelector('.upload-card');
                        if (uploadCard) {
                            uploadCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }

                    if (!imageValidated && reportImage.files.length > 0) {
                        // Model masih loading — tunggu dulu
                        showToast('Mohon tunggu, AI sedang menganalisis foto...', 'error');
                        return;
                    }
                    // ──────────────────────────────────────────────────────────
                }

                if (!type) {
                    showToast('Pilih jenis laporan (Ditemukan atau Hilang)', 'error');
                    return;
                }
                if (!species) {
                    showToast('Pilih spesies hewan', 'error');
                    return;
                }
                if (species === 'Lainnya' && !customSpecies) {
                    showToast('Masukkan nama spesies hewan', 'error');
                    customSpeciesInput.focus();
                    return;
                }
                const locationDisplay = document.getElementById('location-display').value.trim();
                if (!locationDisplay) {
                    showToast('Pilih lokasi di peta terlebih dahulu', 'error');
                    return;
                }
                const locationDescription = document.getElementById('location_description').value.trim();
                if (!locationDescription) {
                    showToast('Masukkan deskripsi lokasi', 'error');
                    return;
                }
                if (!description) {
                    showToast('Masukkan deskripsi detail hewan', 'error');
                    return;
                }
                if (!reportDate) {
                    showToast('Pilih tanggal pelaporan', 'error');
                    return;
                }

                const formData = new FormData(reportForm);
                if (species === 'Lainnya') {
                    formData.set('species', customSpecies);
                }

                // ── Loading state ────────────────────────────────────────────
                const submitBtn = reportForm.querySelector('.form-submit-btn');
                const originalBtnText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;"></i>Memverifikasi foto hewan...';

                // Tampilkan overlay status di upload card saat foto baru diupload
                const hasNewImage = reportImage.files && reportImage.files.length > 0;
                const uploadCard = document.querySelector('.upload-card');
                let validatingBadge = null;
                if (hasNewImage && uploadCard) {
                    validatingBadge = document.createElement('div');
                    validatingBadge.style.cssText = 'margin-top:12px;display:flex;align-items:center;gap:8px;color:#6366f1;font-size:0.85rem;font-weight:600;';
                    validatingBadge.innerHTML = '<i class="fa-solid fa-robot fa-spin"></i> AI sedang memverifikasi foto hewan...';
                    uploadCard.appendChild(validatingBadge);
                }
                // ─────────────────────────────────────────────────────────────

                try {
                    const url = isEdit ? '../api/reports.php?action=update&id=<?php echo $editReportId; ?>' : '../api/reports.php?action=create';
                    const response = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    });
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (parseError) {
                        showToast('Respons server tidak valid: ' + text, 'error');
                        return;
                    }

                    if (data.status === 'success') {
                        submitBtn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:8px;"></i>Berhasil! Mengalihkan...';
                        showToast(isEdit ? 'Laporan berhasil diperbarui' : data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'profile.php';
                        }, 1200);
                    } else {
                        // ── Foto bukan hewan — tampilkan error khusus ────────
                        if (response.status === 422) {
                            showToast('❌ ' + data.message, 'error');
                            // Highlight upload area
                            if (uploadCard) {
                                uploadCard.style.borderColor = '#ef4444';
                                uploadCard.style.background = 'linear-gradient(135deg, #fff5f5, #fee2e2)';
                                setTimeout(() => {
                                    uploadCard.style.borderColor = '';
                                    uploadCard.style.background = '';
                                }, 3000);
                            }
                            // Reset file input
                            reportImage.value = '';
                            selectedFileName.textContent = 'Tidak ada file dipilih';
                        } else {
                            showToast(data.message, 'error');
                        }
                        // ─────────────────────────────────────────────────────
                    }
                } catch (error) {
                    showToast('Gagal mengirim laporan: ' + error.message, 'error');
                } finally {
                    // Selalu restore tombol & hapus badge validasi
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    if (validatingBadge) validatingBadge.remove();
                }
            });
        }

        function setReportType(type) {
            document.querySelectorAll('.type-card').forEach((card) => {
                const input = card.querySelector('input[name="type"]');
                const isChecked = input && input.value === type;
                card.classList.toggle('active', isChecked);
                if (input) {
                    input.checked = isChecked;
                }
            });
        }

        function formatLocalDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function setReportDateRange() {
            const dateInput = document.getElementById('report-date');
            if (!dateInput) return;
            const today = new Date();
            const maxDate = formatLocalDate(today);
            const minDate = new Date(today);
            minDate.setDate(minDate.getDate() - 7);
            const minDateStr = formatLocalDate(minDate);
            dateInput.min = minDateStr;
            dateInput.max = maxDate;
            if (!dateInput.value) {
                dateInput.value = maxDate;
            }
        }

        // ── COCO-SSD VALIDASI FOTO HEWAN ──────────────────────────────────────
        let tfCocoSsd      = null;
        let imageValidated = false;
        let imageIsAnimal  = false;

        // Hewan yang bisa dideteksi langsung COCO-SSD (hanya 10 class hewan)
        const COCO_ANIMALS = ['bird','cat','dog','horse','sheep','cow','elephant','bear','zebra','giraffe'];

        // Keyword hewan untuk fallback via nama file
        // Reptil, ikan, kelinci, hamster dll tidak ada di COCO-SSD sama sekali
        const PET_KEYWORDS = [
            'kura','turtle','tortoise','reptil','reptile','lizard','kadal','iguana',
            'snake','ular','gecko','tokek','chameleon','bunglon','salamander',
            'fish','ikan','goldfish','koi','arwana','cupang','betta',
            'rabbit','kelinci','hamster','marmot','guinea','gerbil','chinchilla',
            'squirrel','tupai','rat','mouse','tikus',
            'parrot','beo','cockatiel','kakatua','lovebird','murai','jalak',
            'canary','kenari','finch','pigeon','merpati',
            'ferret','hedgehog','landak','axolotl','musang','sugar',
            'monkey','monyet','deer','rusa','fox','rubah',
            'cat','dog','kucing','anjing','kitten','puppy',
            'hewan','pet','animal','peliharaan',
        ];

        // Objek NON-hewan COCO-SSD — jika mendominasi foto → TOLAK
        const NON_ANIMAL_CLASSES = [
            'car','truck','bus','motorcycle','bicycle','airplane','train','boat',
            'chair','couch','bed','dining table','toilet','tv','laptop','keyboard',
            'cell phone','bottle','cup','fork','knife','spoon','bowl',
            'traffic light','fire hydrant','stop sign','bench','backpack',
            'umbrella','suitcase','sports ball','kite',
        ];

        // Cek nama file mengandung keyword hewan
        function filenameContainsPetKeyword(filename) {
            const lower = filename.toLowerCase().replace(/[_\-\.]/g, ' ');
            return PET_KEYWORDS.some(kw => lower.includes(kw));
        }

        // Preload model di background
        cocoSsd.load().then(m => { tfCocoSsd = m; console.log('COCO-SSD loaded ✓'); })
                      .catch(e => console.warn('COCO-SSD gagal load:', e));

        /* ── PREVIEW HELPERS ── */
        function showImagePreview(file) {
            const url = URL.createObjectURL(file);
            const previewImg = document.getElementById('preview-img');
            previewImg.src = url;
            previewImg.onload = () => URL.revokeObjectURL(url);
            document.getElementById('preview-file-name').textContent = file.name;
            document.getElementById('upload-card-empty').style.display   = 'none';
            document.getElementById('upload-card-preview').style.display = 'block';
        }

        function hideImagePreview() {
            document.getElementById('upload-card-empty').style.display   = 'block';
            document.getElementById('upload-card-preview').style.display = 'none';
            const b = document.getElementById('preview-validation-badge');
            b.style.display = 'none'; b.innerHTML = '';
        }

        function resetUploadState() {
            imageValidated = false; imageIsAnimal = false;
            hideImagePreview();
            const ec = document.getElementById('upload-card-empty');
            ec.style.borderColor = ''; ec.style.background = '';
            document.getElementById('selected-file-name').textContent = 'Tidak ada file dipilih';
        }

        function setPreviewBadge(isAnimal, msg) {
            const b  = document.getElementById('preview-validation-badge');
            const pc = document.getElementById('upload-card-preview');
            b.style.display    = 'flex';
            b.style.background = isAnimal ? 'rgba(16,185,129,0.90)' : 'rgba(239,68,68,0.90)';
            b.style.color      = '#fff';
            b.innerHTML = (isAnimal ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-xmark"></i> ') + msg;
            if (pc) pc.style.borderColor = isAnimal ? '#10b981' : '#ef4444';
        }

        function setPreviewBadgeLoading() {
            const b = document.getElementById('preview-validation-badge');
            b.style.display = 'flex';
            b.style.background = 'rgba(99,102,241,0.88)'; b.style.color = '#fff';
            b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> AI memverifikasi...';
        }

        /* ── VALIDASI UTAMA ── */
        async function validateImageWithTF(file) {
            setPreviewBadgeLoading();

            try {
                if (!tfCocoSsd) {
                    try { tfCocoSsd = await cocoSsd.load(); }
                    catch(e) {
                        // Model gagal load → jangan block, loloskan saja
                        console.warn('COCO-SSD tidak bisa diload:', e);
                        imageValidated = true; imageIsAnimal = true;
                        document.getElementById('preview-validation-badge').style.display = 'none';
                        return;
                    }
                }

                // Buat img element dari file
                const img = new Image();
                const objectUrl = URL.createObjectURL(file);
                img.src = objectUrl;
                await new Promise(resolve => { img.onload = resolve; });
                URL.revokeObjectURL(objectUrl);

                const detections = await tfCocoSsd.detect(img);
                console.log('COCO-SSD detections:', detections);

                // ── Pisahkan deteksi hewan vs manusia ──
                const animalDets = detections.filter(d => COCO_ANIMALS.includes(d.class) && d.score > 0.30);
                const personDets = detections.filter(d => d.class === 'person'             && d.score > 0.40);

                const hasAnimal = animalDets.length > 0;

                // Hitung rasio area manusia vs total gambar
                const imgArea    = img.naturalWidth * img.naturalHeight || img.width * img.height;
                const personArea = personDets.reduce((s, d) => s + d.bbox[2] * d.bbox[3], 0);
                const personRatio = imgArea > 0 ? personArea / imgArea : 0;

                // ── Keputusan ──────────────────────────────────────────
                if (hasAnimal) {
                    // ✅ Ada hewan terdeteksi
                    imageValidated = true; imageIsAnimal = true;
                    setPreviewBadge(true, 'Foto terverifikasi');

                } else if (personRatio > 0.20) {
                    // ❌ Foto manusia mendominasi
                    imageValidated = true; imageIsAnimal = false;
                    setPreviewBadge(false, 'Foto manusia tidak diperbolehkan');

                } else {
                    // COCO-SSD tidak mendeteksi hewan dari 10 class-nya.
                    // Tapi COCO-SSD tidak tahu kura-kura, ikan, kelinci, hamster, dll.
                    // Strategi fallback berlapis:

                    // Satu-satunya fallback yang aman: nama file mengandung keyword hewan.
                    // Layer "foto bersih" dihapus — terlalu longgar, benda seperti kardus
                    // juga tidak terdeteksi COCO-SSD sehingga ikut lolos.
                    const fileName = document.getElementById('report-image')?.files[0]?.name || '';
                    const fileHasPetKeyword = filenameContainsPetKeyword(fileName);

                    if (fileHasPetKeyword) {
                        // Nama file jelas mengandung keyword hewan → loloskan
                        imageValidated = true; imageIsAnimal = true;
                        setPreviewBadge(true, 'Foto hewan terverifikasi');
                    } else {
                        // Tidak ada hewan terdeteksi & nama file tidak menunjukkan hewan → TOLAK
                        imageValidated = true; imageIsAnimal = false;
                        setPreviewBadge(false, 'Hewan tidak terdeteksi pada foto ini');
                    }
                }

            } catch(err) {
                console.error('COCO-SSD error:', err);
                // Error teknis → jangan block user
                imageValidated = true; imageIsAnimal = true;
                document.getElementById('preview-validation-badge').style.display = 'none';
            }
        }
        // ───────────────────────────────────────────────────────────────────────

        function setupLogout() {
            document.getElementById('btn-logout').addEventListener('click', async () => {
                try {
                    const response = await fetch('../api/logout.php', {
                        credentials: 'same-origin'
                    });
                    await response.json();
                    showToast('Logout berhasil', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1000);
                } catch (error) {
                    showToast('Gagal logout: ' + error.message, 'error');
                }
            });
        }
    </script>
</body>
</html>