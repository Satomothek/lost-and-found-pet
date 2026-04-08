<?php
/**
 * Post Report / Feed Page
 * /pages/post_report.php
 */

require_once '../lib/auth.php';
require_once '../config/database.php';
require_once '../lib/functions.php';

// Require login
requireLogin();
$currentUser = getCurrentUser();

$page = $_GET['page'] ?? '';
$isCreatePage = $page === 'create';

// Handle edit mode
$editReportId = intval($_GET['edit'] ?? 0);
$editReport = null;
if ($editReportId > 0) {
    $query = "SELECT * FROM pet_reports WHERE id = ? AND user_id = ?";
    $editReport = fetchOne($connection, $query, [$editReportId, $currentUser['id']]);
    if ($editReport) {
        $isCreatePage = true;
    }
}

$createReportError = $_SESSION['create_report_error'] ?? null;
$createReportSuccess = $_SESSION['create_report_success'] ?? null;
unset($_SESSION['create_report_error'], $_SESSION['create_report_success']);

function renderReportCards($reports) {
    if (!$reports || count($reports) === 0) {
        return '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Tidak ada laporan di radar area ini.</div>';
    }

    $html = '';
    foreach ($reports as $report) {
        $typeBadge = $report['type'] === 'found' ? 'FOUND' : 'LOST';
        $badgeClass = $report['type'] === 'found' ? 'badge-found' : 'badge-lost';
        $petImage = $report['image'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image';
        $authorImage = $report['authorImg'] ?: 'https://i.pravatar.cc/48?img=68';
        $petName = $report['petName'] !== 'Unknown' && trim($report['petName']) !== '' ? htmlspecialchars($report['petName']) : htmlspecialchars($report['species']) . ' Tanpa Nama';
        $speciesLabel = htmlspecialchars($report['species'] ?: 'Jenis tidak diketahui');
        $speciesDetailText = trim($report['species_detail'] ?? '');
        $descriptionText = trim($report['description']) ?: 'Tidak ada deskripsi tambahan.';
        $descriptionSnippet = htmlspecialchars(mb_strlen($descriptionText) > 90 ? mb_substr($descriptionText, 0, 90) . '...' : $descriptionText);
        if ($speciesDetailText !== '') {
            $descriptionSnippet = '<strong>' . htmlspecialchars($speciesDetailText) . '</strong> ' . $descriptionSnippet;
        }
        $location = htmlspecialchars($report['location'] ?: 'Lokasi tidak tersedia');
        $createdUpdatedLabel = $report['updated_at'] ? timeAgo($report['updated_at']) : timeAgo($report['created_at']);
        $author = htmlspecialchars($report['author']);
        $relativeTime = htmlspecialchars($report['date'] ?: 'Waktu tidak diketahui');        $eventDate = $report['eventDate'] ? htmlspecialchars($report['eventDate']) : null;

        $html .= "<div class=\"feed-card\" onclick=\"openModal({$report['id']})\">";
        $html .= "<div class=\"card-img-box\">";
        $html .= "<div class=\"card-badge {$badgeClass}\">{$typeBadge}</div>";
        $html .= "<img src=\"{$petImage}\" alt=\"Pet Image\" loading=\"lazy\">";
        $html .= "</div>";
        $html .= "<div class=\"card-body\">";
        $html .= "<div class=\"card-title-row\">";
        $html .= "<h3>{$petName}</h3>";
        $html .= "<span class=\"card-label\">{$speciesLabel}</span>";
        $html .= "</div>";
        $html .= "<p class=\"card-description\">{$descriptionSnippet}</p>";
        $html .= "<div class=\"card-info-grid\">";
        $html .= "<span class=\"info-item info-event\"><i class=\"fa-solid fa-calendar\"></i> " . ($eventDate ?: '-') . "</span>";
        $html .= "<span class=\"info-item info-location\"><i class=\"fa-solid fa-map-marker-alt\"></i> {$location}</span>";
        $html .= "<span class=\"info-item info-created\"><i class=\"fa-solid fa-clock\"></i> {$createdUpdatedLabel}</span>";
        $html .= "<span class=\"info-item info-spacer\"></span>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class=\"card-footer\">";
        $html .= "<div class=\"author-box\">";
        $html .= "<img src=\"{$authorImage}\" class=\"author-img\" alt=\"Author\">";
        $html .= "<div class=\"author-text\">";
        $html .= "<span class=\"author-name\">{$author}</span><br>";
        $html .= "<span style=\"font-size:0.75rem; color:var(--text-muted);\">{$relativeTime}</span>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class=\"action-buttons\">";
        if ($report['user_id'] != $currentUser['id']) {
            $html .= "<button class=\"btn-chat action-btn\" title=\"Chat dengan pembuat laporan\" onclick=\"startChat({$report['user_id']}, event)\">";
            $html .= "<i class=\"fa-solid fa-comments\"></i>";
            $html .= "</button>";
        }
        $likeClass = $report['isLiked'] ? 'liked' : '';
        $heartStyle = $report['isLiked'] ? 'fa-solid' : 'fa-regular';
        $html .= "<button class=\"btn-like action-btn {$likeClass}\" title=\"Simpan ke Bookmarks\" onclick=\"toggleLike({$report['id']}, event)\">";
        $html .= "<i class=\"{$heartStyle} fa-bookmark\"></i>";
        $html .= "</button>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";
    }

    return $html;
}

$initialReports = [];
if (!$isCreatePage) {
    $pagination = getPagination(1, 12);
    $query = "SELECT pr.*, users.name as author, users.avatar_url as authorImg,
              (SELECT COUNT(*) FROM likes WHERE report_id = pr.id) as likes,
              (SELECT COUNT(*) FROM likes WHERE report_id = pr.id AND user_id = ?) as isLiked
              FROM pet_reports pr
              JOIN users ON pr.user_id = users.id
              WHERE pr.status = 'active'
              ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";
    $params = [$currentUser['id'], $pagination['limit'], $pagination['offset']];
    $reports = fetchAll($connection, $query, $params);
    if ($reports !== false) {
        $initialReports = array_map(function($report) {
            return [
                'id' => $report['id'],
                'type' => $report['type'],
                'author' => $report['author'] ?: 'Anonim',
                'authorImg' => normalizeAssetUrl($report['authorImg'] ?: 'https://i.pravatar.cc/150?img=68'),
                'petName' => $report['pet_name'],
                'species' => $report['species'],
                'speciesDetail' => $report['species_detail'] ?? null,
                'location' => $report['location'],
                'date' => timeAgo($report['created_at']),
                'eventDate' => $report['event_date'] ? date('d M Y', strtotime($report['event_date'])) : null,
                'createdRelative' => timeAgo($report['created_at']),
                'updatedRelative' => $report['updated_at'] ? timeAgo($report['updated_at']) : null,
                'created_at' => $report['created_at'],
                'updated_at' => $report['updated_at'],
                'desc' => $report['description'],
                'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
                'likes' => intval($report['likes']),
                'isLiked' => boolval($report['isLiked']),
                'user_id' => $report['user_id']
            ];
        }, $reports);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isCreatePage) {
    $type = sanitizeInput($_POST['type'] ?? '');
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
        $speciesDetail = sanitizeInput($_POST['species_detail'] ?? '');

    if (!$type || !$species || !$location || !$description || !$reportDate) {
        $_SESSION['create_report_error'] = 'Semua field wajib diisi';
        $redirectUrl = $reportId > 0 ? "post_report.php?page=create&edit={$reportId}" : 'post_report.php?page=create';
        header('Location: ' . $redirectUrl);
        exit;
    }

    if (!in_array($type, ['lost', 'found'])) {
        $_SESSION['create_report_error'] = 'Tipe laporan tidak valid';
        $redirectUrl = $reportId > 0 ? "post_report.php?page=create&edit={$reportId}" : 'post_report.php?page=create';
        header('Location: ' . $redirectUrl);
        exit;
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $reportDate);
    $today = new DateTime('today');
    $minDate = (clone $today)->modify('-7 days');

    if (!$dateObj || $dateObj < $minDate || $dateObj > $today) {
        $_SESSION['create_report_error'] = 'Tanggal harus antara ' . $minDate->format('Y-m-d') . ' sampai ' . $today->format('Y-m-d');
        $redirectUrl = $reportId > 0 ? "post_report.php?page=create&edit={$reportId}" : 'post_report.php?page=create';
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Handle edit mode
    if ($reportId > 0) {
        // Check ownership
        $checkQuery = "SELECT user_id, image_url FROM pet_reports WHERE id = ?";
        $existingReport = fetchOne($connection, $checkQuery, [$reportId]);
        
        if (!$existingReport) {
            $_SESSION['create_report_error'] = 'Laporan tidak ditemukan';
            header('Location: post_report.php?page=create&edit=' . $reportId);
            exit;
        }
        
        if ($existingReport['user_id'] != $currentUser['id']) {
            $_SESSION['create_report_error'] = 'Anda tidak memiliki akses untuk mengubah laporan ini';
            header('Location: post_report.php?page=create&edit=' . $reportId);
            exit;
        }

        // Handle image upload (optional for update)
        $imageUrl = $existingReport['image_url'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['image'], '../public/uploads/');
            if ($upload['success']) {
                $imageUrl = 'public/uploads/' . $upload['filename'];
            }
        }

        $query = "UPDATE pet_reports SET type = ?, pet_name = ?, species = ?, species_detail = ?, location = ?, description = ?, event_date = ?, image_url = ? WHERE id = ?";
        $result = executeQuery($connection, $query, [
            $type,
            $petName,
            $species,
            $speciesDetail,
            $location,
            $description,
            $reportDate,
            $imageUrl,
            $reportId
        ]);

        if ($result['success']) {
            $_SESSION['create_report_success'] = 'Laporan berhasil diperbarui';
            header('Location: post_report.php');
            exit;
        }

        $_SESSION['create_report_error'] = 'Gagal memperbarui laporan: ' . $result['error'];
        header('Location: post_report.php?page=create&edit=' . $reportId);
        exit;
    } else {
        // Handle create mode
        $imageUrl = 'https://via.placeholder.com/600x400?text=Pet+Image';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['image'], '../public/uploads/');
            if ($upload['success']) {
                $imageUrl = 'public/uploads/' . $upload['filename'];
            }
        }

        $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, species_detail, location, description, image_url, event_date)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $result = executeQuery($connection, $query, [
            $currentUser['id'],
            $type,
            $petName,
            $species,
            $speciesDetail,
            $location,
            $description,
            $imageUrl,
            $reportDate
        ]);

        if ($result['success']) {
            $_SESSION['create_report_success'] = 'Laporan berhasil dibuat';
            header('Location: post_report.php');
            exit;
        }

        $_SESSION['create_report_error'] = 'Gagal membuat laporan: ' . $result['error'];
        header('Location: post_report.php?page=create');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editReport ? 'Edit Laporan - PetFounds' : 'Feed - PetFounds'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="bg-animation">
        <div class="bg-ball color-1"></div>
        <div class="bg-ball color-2"></div>
        <div class="bg-ball color-3"></div>
    </div>

    <div id="toast-container"></div>

    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar bg-surface">
            <div class="sidebar-header">
                <div class="app-logo">
                    <div class="logo-icon flex-center"><i class="fa-solid fa-paw"></i></div>
                    <span class="logo-text">Pet<span class="text-gradient">Founds</span></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="post_report.php" class="nav-item <?php echo !$isCreatePage ? 'active' : ''; ?>">
                    <i class="fa-solid fa-compass"></i>
                    <span>Jelajahi</span>
                </a>
                <a href="post_report.php?page=create" class="nav-item <?php echo $isCreatePage ? 'active' : ''; ?>">
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

        <!-- Main Content -->
        <?php if ($isCreatePage): ?>
        <main class="main-content page-pt">
            <div class="container-fluid">
                <div class="report-create-wrapper glass-panel">
                    <div class="page-header" style="margin-bottom: 30px;">
                        <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;"><?php echo $editReport ? 'Edit Laporan' : 'Publikasikan Laporan Jaringan'; ?></h1>
                        <p style="color: var(--text-muted);"><?php echo $editReport ? 'Perbarui detail laporan Anda.' : 'Isi detail laporan untuk membantu komunitas menemukan atau melaporkan hewan.'; ?></p>
                    </div>

                    <form id="form-create-report" class="glass-panel-form" enctype="multipart/form-data" method="POST" action="<?php echo $editReport ? '../api/reports.php?action=update&id=' . $editReportId : '?page=create'; ?>">
                    <?php if ($editReport): ?>
                        <input type="hidden" name="report_id" value="<?php echo $editReportId; ?>">
                    <?php endif; ?>
                    <label class="form-label" style="display:block; margin-bottom:10px; font-weight:600; color:var(--text-muted);">Pilih Jenis Laporan</label>
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

                    <div class="upload-card" style="border: 1px dashed var(--surface-border); border-radius: 24px; padding: 36px; display: grid; place-items: center; text-align: center; margin-bottom: 24px; background: var(--bg-surface);">
                        <label for="report-image" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 18px;">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.5rem; color: var(--primary);"></i>
                            <strong style="font-size: 1rem; color: var(--text-dark);">Tarik foto atau klik untuk unggah <span style="color: var(--danger);">*</span></strong>
                            <span style="color: var(--text-muted);">Rasio kotak (1:1) direkomendasikan</span>
                            <input type="file" id="report-image" name="image" accept="image/*" style="display:none;" <?php echo $editReport ? '' : 'required'; ?>>
                            <span id="selected-file-name" style="color: var(--text-muted);">Tidak ada file dipilih</span>
                        </label>
                    </div>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
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

                    <div id="custom-species-container" class="input-modern" style="display: none; margin-bottom: 16px;">
                        <i class="fa-solid fa-paw"></i>
                        <input type="text" id="custom-species" placeholder="Sebutkan spesies hewan...">
                    </div>

                    <div class="input-modern" style="margin-bottom: 16px;">
                        <i class="fa-solid fa-dna"></i>
                        <input type="text" id="species-detail" name="species_detail" placeholder="Detail spesies / ras (opsional)">
                    </div>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="input-modern">
                            <i class="fa-regular fa-calendar-days"></i>
                            <input type="date" id="report-date" name="date" placeholder="Tanggal" required>
                        </div>
                        <div class="input-modern">
                            <i class="fa-solid fa-location-dot"></i>
                            <input type="text" id="location" name="location" placeholder="Lokasi Terakhir / Ditemukan" required>
                        </div>
                    </div>

                    <div class="input-modern" style="margin-bottom: 24px;">
                        <i class="fa-solid fa-align-left"></i>
                        <textarea id="description" name="description" rows="5" placeholder="Detail ciri-ciri khusus, kronologi, warna kalung, dll..." required style="min-height: 160px;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg form-submit-btn" style="width: 100%;"><?php echo $editReport ? 'Perbarui Laporan' : 'Publikasikan Laporan'; ?></button>
                </form>
                </div>
            </div>
        </main>
        <?php else: ?>
        <main class="main-content page-pt">
            <div class="container-fluid">
                <div class="feed-page-container">
                    <!-- Search Bar -->
                    <div class="search-modern" style="margin-bottom: 30px;">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="search-feed" placeholder="Cari nama hewan, lokasi, atau spesies hewan...">
                    </div>

                    <!-- Feed Grid -->
                    <div class="feed-grid" id="feed-container">
                        <?php echo renderReportCards($initialReports); ?>
                    </div>
                </div>
            </div>
        </main>
        <?php endif; ?>
    </div>

    <!-- Modal Detail -->
    <div class="modal-overlay" id="post-modal">
        <div class="modal-content">
            <button id="close-modal" class="btn-close-modal">
                <i class="fa-solid fa-times"></i>
            </button>
            <div id="modal-body-content"></div>
        </div>
    </div>

    <script src="../js/functions.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        const currentPage = '<?php echo $page; ?>';
        const createReportError = <?php echo json_encode($createReportError); ?>;
        const createReportSuccess = <?php echo json_encode($createReportSuccess); ?>;
        const initialReports = <?php echo json_encode($initialReports); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            if (createReportSuccess) {
                showToast(createReportSuccess, 'success');
            }
            if (createReportError) {
                showToast(createReportError, 'error');
            }

            if (currentPage === 'create') {
                setupCreateForm();
                setReportDateRange();
            } else {
                if (initialReports && initialReports.length > 0) {
                    renderFeed(initialReports);
                } else {
                    loadFeed();
                }
                setupSearch();
                setupModal();
            }
            setupLogout();
        });

        // Load feed from API
        async function loadFeed(search = '') {
            const container = document.getElementById('feed-container');
            if (container) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Memuat laporan...</div>';
            }

            try {
                let url = '../api/reports.php';
                if (search) {
                    url += '?search=' + encodeURIComponent(search);
                }
                
                const response = await fetch(url, {
                    credentials: 'same-origin'
                });
                const rawText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseError) {
                    throw new Error('Respons API tidak valid: ' + rawText);
                }
                console.log('loadFeed response', data);
                
                if (data.status === 'success') {
                    renderFeed(data.data.reports);
                } else {
                    if (container) {
                        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--danger);">' + data.message + '</div>';
                    }
                    showToast(data.message, 'error');
                }
            } catch (error) {
                if (container) {
                    container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--danger);">Gagal memuat laporan. Silakan coba lagi.</div>';
                }
                showToast('Gagal memuat feed: ' + error.message, 'error');
            }
        }

        // Render feed items
        function renderFeed(reports) {
            const container = document.getElementById('feed-container');
            
            if (!reports || reports.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Tidak ada laporan di radar area ini.</div>';
                return;
            }

            let html = '';
            reports.forEach(report => {
                const petImageSrc = report.image || 'https://via.placeholder.com/600x400?text=Pet+Image';
                const authorImageSrc = report.authorImg || 'https://i.pravatar.cc/48?img=68';
                const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
                const typeText = report.type === 'found' ? 'FOUND' : 'LOST';
                const title = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
                const speciesLabel = report.species || 'Jenis tidak diketahui';
                const speciesDetailText = report.speciesDetail ? report.speciesDetail : '';
                const rawDescription = report.description || report.desc || '';
                const descriptionSnippet = rawDescription ? rawDescription.substring(0, 90) + (rawDescription.length > 90 ? '...' : '') : 'Tidak ada deskripsi tambahan.';
                const descriptionHtml = speciesDetailText ? '<strong>' + escapeHtml(speciesDetailText) + '</strong> ' + escapeHtml(descriptionSnippet) : escapeHtml(descriptionSnippet);
                const whenText = report.date || 'Waktu tidak diketahui';
                const eventDateText = report.eventDate ? report.eventDate : null;
                const createdRelative = report.createdRelative || 'Baru saja';
                const updatedRelative = report.updatedRelative || null;
                const createdUpdatedLabel = updatedRelative ? updatedRelative : createdRelative;
                const likeClass = report.isLiked ? 'liked' : '';
                const iconClass = report.isLiked ? 'fa-solid' : 'fa-regular';

                html += '<div class="feed-card" onclick="openModal(' + report.id + ')">';
                html += '<div class="card-img-box">';
                html += '<div class="card-badge ' + badgeClass + '">' + typeText + '</div>';
                html += '<img src="' + petImageSrc + '" alt="Pet Image" loading="lazy">';
                html += '</div>';
                html += '<div class="card-body">';
                html += '<div class="card-title-row">';
                html += '<h3>' + title + '</h3>';
                html += '<span class="card-label">' + speciesLabel + '</span>';
                html += '</div>';
                html += '<p class="card-description">' + descriptionHtml + '</p>';
                html += '<div class="card-info-grid">';
                html += '<span class="info-item info-event"><i class="fa-solid fa-calendar"></i> ' + escapeHtml(eventDateText || '-') + '</span>';
                html += '<span class="info-item info-location"><i class="fa-solid fa-map-marker-alt"></i> ' + escapeHtml(report.location || 'Lokasi tidak tersedia') + '</span>';
                html += '<span class="info-item info-created"><i class="fa-solid fa-clock"></i> ' + createdUpdatedLabel + '</span>';
                html += '<span class="info-item info-spacer"></span>';
                html += '</div>';
                html += '</div>';
                html += '<div class="card-footer">';
                html += '<div class="author-box">';
                html += '<img src="' + authorImageSrc + '" class="author-img" alt="Author">';
                html += '<div class="author-text">';
                html += '<span class="author-name">' + report.author + '</span>';
                html += '</div>';
                html += '</div>';
                html += '<div class="action-buttons">';
                if (report.user_id != currentUser.id) {
                    html += '<button class="btn-chat action-btn" title="Chat dengan pembuat laporan" onclick="startChat(' + report.user_id + ', event)">';
                    html += '<i class="fa-solid fa-comments"></i>';
                    html += '</button>';
                }
                html += '<button class="btn-like action-btn ' + likeClass + '" title="Simpan ke Bookmarks" onclick="toggleLike(' + report.id + ', event)">';
                html += '<i class="' + iconClass + ' fa-bookmark"></i>';
                html += '</button>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });

            container.innerHTML = html;
        }

        function setupCreateForm() {
            // Handle edit mode
            <?php if ($editReport): ?>
            const editData = <?php echo json_encode($editReport); ?>;
            document.getElementById('pet-name').value = editData.pet_name || '';
            document.getElementById('species').value = editData.species || '';
            document.getElementById('species-detail').value = editData.species_detail || '';
            document.getElementById('location').value = editData.location || '';
            document.getElementById('description').value = editData.description || '';
            
            // Set report type
            const typeRadio = document.querySelector(`input[name="type"][value="${editData.type}"]`);
            if (typeRadio) {
                typeRadio.checked = true;
                setReportType(editData.type);
            }

            const knownSpeciesOptions = Array.from(document.querySelectorAll('#species option')).map(option => option.value);
            if (editData.species) {
                if (knownSpeciesOptions.includes(editData.species)) {
                    document.getElementById('species').value = editData.species;
                } else {
                    document.getElementById('species').value = 'Lainnya';
                    document.getElementById('custom-species-container').style.display = 'block';
                    document.getElementById('custom-species').value = editData.species;
                }
            }
            
            // Prefill report date if available and keep it within the allowed 7-day range
            const reportDateInput = document.getElementById('report-date');
            if (reportDateInput) {
                let dateToUse = editData.event_date || editData.created_at;
                const editDate = new Date(dateToUse);
                const today = new Date();
                const minDate = new Date(today);
                minDate.setDate(minDate.getDate() - 7);

                // Format as YYYY-MM-DD for input[type="date"]
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
                if (editDate < minDate || editDate > today) {
                    reportDateInput.value = maxDate;
                } else {
                    reportDateInput.value = formattedEditDate;
                }
            }
            
            // Update form action for edit
            document.querySelector('.form-submit-btn').textContent = 'Perbarui Laporan';
            <?php endif; ?>

            const reportTypeRadios = document.querySelectorAll('input[name="type"]');
            const reportImage = document.getElementById('report-image');
            const selectedFileName = document.getElementById('selected-file-name');
            const reportForm = document.getElementById('form-create-report');
            const speciesSelect = document.getElementById('species');
            const customSpeciesContainer = document.getElementById('custom-species-container');
            const customSpeciesInput = document.getElementById('custom-species');
            const isEditMode = <?php echo $editReport ? 'true' : 'false'; ?>;

            if (isEditMode && reportImage) {
                reportImage.required = false;
            }

            // Handle species change untuk tampilkan/sembunyikan custom species input
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

            // Tampilkan custom species field jika sudah ada nilai saat edit mode
            if (speciesSelect.value === 'Lainnya') {
                customSpeciesContainer.style.display = 'block';
            }

            reportImage.addEventListener('change', () => {
                selectedFileName.textContent = reportImage.files.length > 0 ? reportImage.files[0].name : 'Tidak ada file dipilih';
            });

            reportForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                // Validasi field wajib
                const type = document.querySelector('input[name="type"]:checked');
                const species = speciesSelect.value.trim();
                const customSpecies = customSpeciesInput.value.trim();
                const location = document.getElementById('location').value.trim();
                const description = document.getElementById('description').value.trim();
                const reportDate = document.getElementById('report-date').value.trim();
                const isEdit = <?php echo $editReport ? 'true' : 'false'; ?>;

                // Validasi upload gambar (wajib untuk create, opsional untuk edit)
                if (!isEdit && (!reportImage.files || reportImage.files.length === 0)) {
                    showToast('Upload foto hewan terlebih dahulu', 'error');
                    reportImage.focus();
                    return;
                }

                // Jika ada file yang diupload, validasi tipe dan ukuran
                if (reportImage.files && reportImage.files.length > 0) {
                    // Validasi tipe file
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(reportImage.files[0].type)) {
                        showToast('Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WebP', 'error');
                        return;
                    }

                    // Validasi ukuran file (max 5MB)
                    if (reportImage.files[0].size > 5 * 1024 * 1024) {
                        showToast('Ukuran foto terlalu besar (max 5MB)', 'error');
                        return;
                    }
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
                if (!location) {
                    showToast('Masukkan lokasi hewan ditemukan/hilang', 'error');
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
                // Jika spesies adalah 'Lainnya', gunakan custom species
                if (species === 'Lainnya') {
                    formData.set('species', customSpecies);
                }

                try {
                    const url = isEdit ? '../api/reports.php?action=update&id=<?php echo $editReportId; ?>' : '../api/reports.php?action=create';
                    const method = isEdit ? 'POST' : 'POST'; // Both use POST for file uploads
                    
                    const response = await fetch(url, {
                        method: method,
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
                        showToast(isEdit ? 'Laporan berhasil diperbarui' : data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'profile.php';
                        }, 1200);
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('Gagal mengirim laporan: ' + error.message, 'error');
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

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        // Setup search
        function setupSearch() {
            const searchInput = document.getElementById('search-feed');
            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadFeed(e.target.value);
                }, 300);
            });
        }

        // Setup modal
        function setupModal() {
            const modal = document.getElementById('post-modal');
            const closeBtn = document.getElementById('close-modal');
            
            // Close modal when clicking close button
            closeBtn.addEventListener('click', () => {
                modal.classList.remove('show');
            });
            
            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });
            
            // Close modal on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    modal.classList.remove('show');
                }
            });
        }

        // Toggle like
        async function toggleLike(reportId, event) {
            event.stopPropagation();
            
            try {
                const response = await fetch('../api/likes.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ report_id: reportId })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    loadFeed();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengupdate like: ' + error.message, 'error');
            }
        }

        // Start chat with report author
        function startChat(userId, event) {
            event.stopPropagation();
            window.location.href = 'messages.php?contact=' + userId;
        }

        // Open modal
        async function openModal(reportId) {
            console.log('Opening modal for report:', reportId);
            
            try {
                const response = await fetch(`../api/reports.php?id=${reportId}`, {
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderModalContent(data.data);
                    document.getElementById('post-modal').classList.add('show');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memuat detail laporan: ' + error.message, 'error');
            }
        }

        // Render modal content
        function renderModalContent(report) {
            const modalBody = document.getElementById('modal-body-content');
            const typeText = report.type === 'found' ? 'DITEMUKAN' : 'HILANG';
            const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
            const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' 
                ? report.petName 
                : report.species + ' Tanpa Nama';
            const speciesDetail = report.speciesDetail ? ` (${report.speciesDetail})` : '';
            const createdUpdatedText = report.updatedAt ? `Diperbarui ${report.updatedAt}` : `Dipublikasikan ${report.createdAt}`;
            
            let html = `
                <div class="modal-report-detail">
                    <div class="modal-header-section">
                        <div class="modal-image-container">
                            <img src="${report.image}" alt="Pet Image" class="modal-pet-image">
                            <div class="modal-badge ${badgeClass}">${typeText}</div>
                        </div>
                        <div class="modal-info-section">
                            <h2 class="modal-pet-name">${petName}</h2>
                            <p class="modal-pet-species">${report.species}${speciesDetail}</p>
                            <div class="modal-meta-info">
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-map-marker-alt"></i>
                                    <span>${report.location}</span>
                                </div>
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>${report.eventDate || 'Tanggal tidak diketahui'}</span>
                                </div>
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>${createdUpdatedText}</span>
                                </div>
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Created: ${formatDate(report.created_at)}</span>
                                </div>
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-edit"></i>
                                    <span>Edited: ${report.updated_at ? formatDate(report.updated_at) : ''}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-description-section">
                        <h3>Detail Laporan</h3>
                        <p class="modal-description">${report.description.replace(/\n/g, '<br>')}</p>
                    </div>
                    
                    <div class="modal-author-section">
                        <div class="modal-author-info">
                            <img src="${report.authorImg}" alt="Author" class="modal-author-avatar">
                            <div class="modal-author-details">
                                <span class="modal-author-name">${report.author}</span>
                                <span class="modal-author-role">Pelapor</span>
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button class="btn btn-like action-btn ${report.isLiked ? 'liked' : ''}" title="Simpan ke Bookmarks" onclick="toggleLikeFromModal(${report.id}, event)">
                                <i class="${report.isLiked ? 'fa-solid' : 'fa-regular'} fa-bookmark"></i>
                                <span>${report.likes}</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            modalBody.innerHTML = html;
        }

        // Toggle like from modal
        async function toggleLikeFromModal(reportId, event) {
            event.stopPropagation();
            
            try {
                const response = await fetch('../api/likes.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ report_id: reportId })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Refresh modal content
                    openModal(reportId);
                    // Also refresh feed
                    loadFeed();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
            }
        }

        // Setup logout
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
