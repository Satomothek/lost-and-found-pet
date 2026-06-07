<?php
/**
 * Explore Page
 * /pages/explore.php
 */

require_once '../lib/auth.php';
require_once '../config/database.php';
require_once '../lib/functions.php';

// Require login
requireLogin();
$currentUser = getCurrentUser();

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

$initialReports = [];
if ($reports !== false) {
    $initialReports = array_map(function($report) {
        return [
            'id' => $report['id'],
            'type' => $report['type'],
            'author' => $report['author'] ?: 'Anonim',
            'authorImg' => $report['authorImg'] ? normalizeAssetUrl($report['authorImg']) : generateAvatarUrl($report['author'] ?: 'Anonim'),
            'petName' => $report['pet_name'],
            'species' => $report['species'],
            'speciesDetail' => $report['species_detail'] ?? null,
            'location' => $report['location'],
            'location_description' => $report['location_description'] ?? null,
            'latitude' => $report['latitude'] !== null ? floatval($report['latitude']) : null,
            'longitude' => $report['longitude'] !== null ? floatval($report['longitude']) : null,
            'date' => timeAgo($report['created_at']),
            'eventDate' => $report['event_date'] ? date('d M Y', strtotime($report['event_date'])) : null,
            'createdRelative' => timeAgo($report['created_at']),
            'updatedRelative' => $report['updated_at'] ? timeAgo($report['updated_at']) : null,
            'created_at' => $report['created_at'],
            'updated_at' => $report['updated_at'],
            'status' => $report['status'],
            'condition' => $report['status'] === 'resolved' ? 'SELESAI' : ($report['type'] === 'found' ? 'AMAN' : 'DALAM PENCARIAN'),
            'condition_state' => $report['status'] === 'resolved' ? 'resolved' : ($report['type'] === 'found' ? 'safe' : 'searching'),
            'desc' => $report['description'],
            'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
            'likes' => intval($report['likes']),
            'isLiked' => boolval($report['isLiked']),
            'user_id' => $report['user_id']
        ];
    }, $reports);
}

function renderReportCards($reports) {
    if (!$reports || count($reports) === 0) {
        return '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Tidak ada laporan.</div>';
    }

    $html = '';
    foreach ($reports as $report) {
        $typeBadge = $report['type'] === 'found' ? 'FOUND' : 'LOST';
        $badgeClass = $report['type'] === 'found' ? 'badge-found' : 'badge-lost';
        $petImage = $report['image'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image';
        $authorImage = $report['authorImg'];
        $petName = $report['petName'] !== 'Unknown' && trim($report['petName']) !== '' ? htmlspecialchars($report['petName']) : htmlspecialchars($report['species']) . ' Tanpa Nama';
        $speciesLabel = htmlspecialchars($report['species'] ?: 'Jenis tidak diketahui');
        $speciesDetailText = trim($report['species_detail'] ?? '');
        $descriptionText = trim($report['description']) ?: 'Tidak ada deskripsi tambahan.';
        $descriptionSnippet = htmlspecialchars(mb_strlen($descriptionText) > 90 ? mb_substr($descriptionText, 0, 90) . '...' : $descriptionText);
        if ($speciesDetailText !== '') {
            $descriptionSnippet = '<strong>' . htmlspecialchars($speciesDetailText) . '</strong> ' . $descriptionSnippet;
        }
        $location = htmlspecialchars($report['location'] ?: 'Lokasi tidak tersedia');
        $latitude = $report['latitude'] !== null ? htmlspecialchars($report['latitude']) : null;
        $longitude = $report['longitude'] !== null ? htmlspecialchars($report['longitude']) : null;
        $createdUpdatedLabel = $report['updated_at'] ? timeAgo($report['updated_at']) : timeAgo($report['created_at']);
        $author = htmlspecialchars($report['author']);
        $relativeTime = htmlspecialchars($report['date'] ?: 'Waktu tidak diketahui');
        $eventDate = $report['eventDate'] ? htmlspecialchars($report['eventDate']) : null;
        $conditionText = !empty($report['location_description']) ? htmlspecialchars($report['location_description']) : '-';
        $conditionClass = 'info-condition';

        $html .= "<div class=\"feed-card\" onclick=\"openModal({$report['id']})\">";
        $html .= "<div class=\"card-img-box\">";
        $html .= "<div class=\"card-badge {$badgeClass}\">{$typeBadge}</div>";
        $html .= "<img src=\"{$petImage}\" alt=\"Pet Image\" loading=\"lazy\" onerror=\"this.src='https://via.placeholder.com/600x400?text=Pet+Image'\">";
        $html .= "</div>";
        $html .= "<div class=\"card-body\">";
        $html .= "<div class=\"card-title-row\">";
        $html .= "<h3>{$petName}</h3>";
        $html .= "<span class=\"card-label\">{$speciesLabel}</span>";
        $html .= "</div>";
        $html .= "<p class=\"card-description\">{$descriptionSnippet}</p>";
        $html .= "<div class=\"card-info-grid\">";
        $locationAttrs = ($latitude !== null && $longitude !== null)
            ? " data-latitude=\"{$latitude}\" data-longitude=\"{$longitude}\" data-fallback=\"{$location}\""
            : '';
        $locationShort = $location;
        if ($location && strpos($location, ',') !== false) {
            $parts = explode(',', $location);
            $locationShort = trim($parts[0]) ?: $location;
        }
        $locationText = ($latitude !== null && $longitude !== null) ? 'Memuat alamat...' : $locationShort;

        $html .= "<span class=\"info-item info-event\"><i class=\"fa-solid fa-calendar\"></i> " . ($eventDate ?: '-') . "</span>";
        $html .= "<span class=\"info-item info-location\"{$locationAttrs}><i class=\"fa-solid fa-map-marker-alt\"></i> {$locationText}</span>";
        $html .= "<span class=\"info-item info-created\"><i class=\"fa-solid fa-clock\"></i> {$createdUpdatedLabel}</span>";
        $html .= "<span class=\"info-item {$conditionClass}\"><i class=\"fa-solid fa-map-pin\"></i> {$conditionText}</span>";
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore - PetFounds</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/pages/explore.css">
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
                <a href="explore.php" class="nav-item active">
                    <i class="fa-solid fa-compass"></i>
                    <span>Jelajahi</span>
                </a>
                <a href="create_report.php" class="nav-item">
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
                <div class="feed-page-container">
                    <!-- Search Bar + Filter -->
                    <div style="margin-bottom: 24px;">
                        <div class="search-modern" style="margin-bottom: 12px;">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="search-feed" placeholder="Cari nama hewan, lokasi, atau spesies hewan...">
                        </div>
                        <div id="filter-bar" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            <span style="font-size:0.78rem; font-weight:600; color:var(--text-muted); letter-spacing:0.05em; text-transform:uppercase; margin-right:4px;">Filter:</span>
                            <button class="filter-btn active" data-filter="all"><i class="fa-solid fa-border-all"></i> Semua</button>
                            <button class="filter-btn" data-filter="name"><i class="fa-solid fa-tag"></i> Nama Hewan</button>
                            <button class="filter-btn" data-filter="species"><i class="fa-solid fa-paw"></i> Spesies</button>
                            <button class="filter-btn" data-filter="location"><i class="fa-solid fa-map-pin"></i> Lokasi</button>
                        </div>
                    </div>

                    <!-- Feed Grid -->
                    <div class="feed-grid" id="feed-container">
                        <?php echo renderReportCards($initialReports); ?>
                    </div>
                </div>
            </div>
        </main>
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

    <script src="../public/js/utils.js"></script>
    <!-- FIX #3: Leaflet JS dimuat SEBELUM script utama, bukan setelahnya -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        const initialReports = <?php echo json_encode($initialReports); ?>;
    </script>
    <script src="../public/js/pages/explore.js"></script>
</body>
</html>