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
            'authorImg' => normalizeAssetUrl($report['authorImg'] ?: 'https://i.pravatar.cc/150?img=68'),
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /*
         * FIX MODAL MAP:
         * Masalah 1 — .modal-content overflow:visible + .modal-overlay overflow-y:auto
         *             menciptakan stacking context baru → Leaflet tile terpotong/hilang.
         * Masalah 2 — #modal-map tidak punya height eksplisit → Leaflet render di container 0px.
         * Masalah 3 — .modal-overlay z-index:1000 tapi Leaflet pane z-index default 400
         *             kadang bentrok dengan elemen lain di dalam modal.
         *
         * Solusi: pakai pola clipper wrapper yang sama dengan create_report.php.
         */

        /* Pastikan modal di atas sidebar (z-index:50) dan semua elemen lain */
        #post-modal.modal-overlay {
            z-index: 1100 !important;
        }

        /* Modal content: overflow harus hidden agar konten tidak bocor keluar */
        #post-modal .modal-content {
            overflow: hidden !important;
            overflow-y: auto !important;    /* scroll konten panjang secara vertikal */
        }

        /* Wrapper clipper untuk map — dia yang menentukan ukuran & memotong konten */
        .modal-map-clipper {
            width: 100%;
            height: 260px;
            border-radius: 12px;
            overflow: hidden;               /* urung semua tile Leaflet */
            position: relative;
            background: #e8edf2;
            border: 1px solid var(--border, #e2e8f0);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-top: 10px;
        }

        /* Map element sendiri: overflow visible agar Leaflet bebas render tile */
        #modal-map {
            width: 100% !important;
            height: 100% !important;
            position: absolute !important;
            inset: 0 !important;
            overflow: visible !important;
            border-radius: 0 !important;
            background: #e8edf2 !important;
        }

        /* Z-index internal Leaflet di dalam modal */
        #modal-map .leaflet-pane    { z-index: 400 !important; }
        #modal-map .leaflet-top,
        #modal-map .leaflet-bottom  { z-index: 401 !important; }
    </style>
    <style>
        /* ── FILTER BUTTONS ── */
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 999px;
            border: 1.5px solid var(--border, #e2e8f0);
            background: var(--surface, #fff);
            color: var(--text-muted, #64748b);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s ease;
            white-space: nowrap;
        }
        .filter-btn:hover {
            border-color: var(--primary, #6366f1);
            color: var(--primary, #6366f1);
            background: rgba(99,102,241,0.06);
        }
        .filter-btn.active {
            background: var(--primary, #6366f1);
            border-color: var(--primary, #6366f1);
            color: #fff;
            box-shadow: 0 2px 8px rgba(99,102,241,0.25);
        }
        .filter-btn i { font-size: 0.8rem; }

        /* ── MODAL MAP FIX ── */
        #post-modal.modal-overlay { z-index: 1100 !important; }
        #post-modal .modal-content { overflow: hidden !important; overflow-y: auto !important; }
        .modal-map-clipper {
            width: 100%; height: 260px;
            border-radius: 12px; overflow: hidden;
            position: relative; background: #e8edf2;
            border: 1px solid var(--border, #e2e8f0);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-top: 10px;
        }
        #modal-map {
            width: 100% !important; height: 100% !important;
            position: absolute !important; inset: 0 !important;
            overflow: visible !important; background: #e8edf2 !important;
        }
        #modal-map .leaflet-pane    { z-index: 400 !important; }
        #modal-map .leaflet-top,
        #modal-map .leaflet-bottom  { z-index: 401 !important; }
    </style>
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

    <script src="../js/functions.js"></script>
    <!-- FIX #3: Leaflet JS dimuat SEBELUM script utama, bukan setelahnya -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        const initialReports = <?php echo json_encode($initialReports); ?>;

        // FIX #2: Variabel global untuk menyimpan referensi instance peta modal
        let activeModalMap = null;
        let allReportsCache = [];
        let activeFilter = 'all';

        document.addEventListener('DOMContentLoaded', () => {
            if (initialReports && initialReports.length > 0) {
                allReportsCache = initialReports;
                renderFeed(initialReports);
            } else {
                loadFeed();
            }
            setupSearch();
            setupModal();
            setupLogout();
        });

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

        function renderFeed(reports) {
            const container = document.getElementById('feed-container');
            if (!reports || reports.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Tidak ada laporan di radar area ini.</div>';
                return;
            }
            // Update cache hanya saat tidak ada query aktif (load penuh)
            if (!document.getElementById('search-feed').value.trim()) {
                allReportsCache = reports;
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
                const eventDateText = report.eventDate ? report.eventDate : null;
                const createdRelative = report.createdRelative || 'Baru saja';
                const updatedRelative = report.updatedRelative || null;
                const createdUpdatedLabel = updatedRelative ? updatedRelative : createdRelative;
                const likeClass = report.isLiked ? 'liked' : '';
                const iconClass = report.isLiked ? 'fa-solid' : 'fa-regular';
                const locationDescription = report.location_description ? escapeHtml(report.location_description) : '';
                const locationText = escapeHtml(report.location || 'Lokasi tidak tersedia');
                const locationDisplay = locationDescription ? locationText + ' - ' + locationDescription : locationText;
                const conditionText = locationDescription || '-';
                const conditionClass = 'info-condition';
                const conditionIcon = 'fa-map-pin';
                const hasCoords = report.latitude !== null && report.longitude !== null;
                const locationLabel = hasCoords ? 'Memuat alamat...' : getShortLocation(locationDisplay);
                const locationData = hasCoords ? ' data-latitude="' + report.latitude + '" data-longitude="' + report.longitude + '" data-fallback="' + locationDisplay + '"' : '';

                html += '<div class="feed-card" onclick="openModal(' + report.id + ')">';
                html += '<div class="card-img-box">';
                html += '<div class="card-badge ' + badgeClass + '">' + typeText + '</div>';
                html += '<img src="' + petImageSrc + '" alt="Pet Image" loading="lazy" onerror="this.src=\'https://via.placeholder.com/600x400?text=Pet+Image\'">';
                html += '</div>';
                html += '<div class="card-body">';
                html += '<div class="card-title-row">';
                html += '<h3>' + title + '</h3>';
                html += '<span class="card-label">' + speciesLabel + '</span>';
                html += '</div>';
                html += '<p class="card-description">' + descriptionHtml + '</p>';
                html += '<div class="card-info-grid">';
                html += '<span class="info-item info-event"><i class="fa-solid fa-calendar"></i> ' + escapeHtml(eventDateText || '-') + '</span>';
                html += '<span class="info-item info-location"' + locationData + '><i class="fa-solid fa-map-marker-alt"></i> ' + escapeHtml(locationLabel) + '</span>';
                html += '<span class="info-item info-created"><i class="fa-solid fa-clock"></i> ' + createdUpdatedLabel + '</span>';
                html += '<span class="info-item ' + conditionClass + '"><i class="fa-solid ' + conditionIcon + '"></i> ' + conditionText + '</span>';
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
            updateMapAddresses();
        }

        function getShortLocation(text) {
            if (!text) return text;
            const parts = text.split(',');
            return parts[0].trim() || text;
        }

        function updateMapAddresses() {
            const nodes = document.querySelectorAll('.info-item.info-location[data-latitude][data-longitude]');
            nodes.forEach(async (node) => {
                const lat = node.dataset.latitude;
                const lng = node.dataset.longitude;
                const fallback = node.dataset.fallback || node.textContent.trim();
                if (!lat || !lng) return;

                const address = await reverseGeocode(lat, lng);
                const display = address || fallback || 'Lokasi tidak tersedia';
                node.innerHTML = '<i class="fa-solid fa-map-marker-alt"></i> ' + escapeHtml(getShortLocation(display));
            });
        }

        async function reverseGeocode(lat, lng) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`;
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return null;
                const data = await response.json();
                return data.display_name || null;
            } catch (error) {
                return null;
            }
        }

        function setupSearch() {
            const searchInput = document.getElementById('search-feed');
            let searchTimeout;

            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilterAndSearch, 250);
            });

            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    activeFilter = btn.dataset.filter;
                    applyFilterAndSearch();
                });
            });
        }

        function applyFilterAndSearch() {
            const query = (document.getElementById('search-feed').value || '').trim().toLowerCase();

            if (allReportsCache.length === 0) { loadFeed(query); return; }
            if (!query) { renderFeed(allReportsCache); return; }

            const filtered = allReportsCache.filter(report => {
                const name    = (report.petName || '').toLowerCase();
                const species = (report.species || '').toLowerCase();
                const loc     = (report.location || '').toLowerCase();
                const locDesc = (report.location_description || '').toLowerCase();
                switch (activeFilter) {
                    case 'name':     return name.includes(query);
                    case 'species':  return species.includes(query);
                    case 'location': return loc.includes(query) || locDesc.includes(query);
                    default:         return name.includes(query) || species.includes(query) || loc.includes(query) || locDesc.includes(query);
                }
            });
            renderFeed(filtered);
        }

        function destroyModalMap() {
            // FIX #2: Selalu destroy instance Leaflet lama sebelum membuat yang baru
            if (activeModalMap) {
                activeModalMap.remove();
                activeModalMap = null;
            }
        }

        function setupModal() {
            const modal = document.getElementById('post-modal');
            const closeBtn = document.getElementById('close-modal');

            closeBtn.addEventListener('click', () => {
                modal.classList.remove('show');
                // FIX #2: Destroy peta saat modal ditutup
                destroyModalMap();
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                    // FIX #2: Destroy peta saat modal ditutup via klik backdrop
                    destroyModalMap();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    // FIX #2: Destroy peta saat modal ditutup via Escape
                    destroyModalMap();
                }
            });
        }

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

        function startChat(userId, event) {
            event.stopPropagation();
            window.location.href = 'messages.php?contact=' + userId;
        }

        async function openModal(reportId) {
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

        function renderModalContent(report) {
            // FIX #2: Destroy peta lama sebelum inject HTML baru
            destroyModalMap();

            const modalBody = document.getElementById('modal-body-content');
            const typeText = report.type === 'found' ? 'DITEMUKAN' : 'HILANG';
            const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
            const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
            const speciesDetail = report.speciesDetail ? ` (${report.speciesDetail})` : '';
            const createdUpdatedText = report.updatedAt ? `Diperbarui ${report.updatedAt}` : `Dipublikasikan ${report.createdAt}`;

            modalBody.innerHTML = `
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
                                ${report.location_description ? `
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-map-pin"></i>
                                    <span>${report.location_description}</span>
                                </div>` : ''}
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
                    ${report.latitude !== null && report.longitude !== null ? `
                    <div class="modal-map-section">
                        <h3>Lokasi pada Peta</h3>
                        <div class="modal-map-clipper">
                            <div id="modal-map"></div>
                        </div>
                    </div>
                    ` : ''}
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

            // Inisialisasi Leaflet di modal — urutan penting:
            // 1. HTML harus sudah di-inject ke DOM (dilakukan di atas)
            // 2. Modal harus visible (dilakukan di openModal setelah renderModalContent)
            // 3. Leaflet harus bisa membaca ukuran container yang sudah non-zero
            if (report.latitude !== null && report.longitude !== null && window.L) {
                // Fix icon path yang sering broken di PHP project dengan subfolder
                delete L.Icon.Default.prototype._getIconUrl;
                L.Icon.Default.mergeOptions({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                });

                // Tunggu modal benar-benar visible + layout selesai sebelum init Leaflet.
                // requestAnimationFrame pertama: DOM updated.
                // requestAnimationFrame kedua (nested): browser sudah paint, container punya ukuran nyata.
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const mapEl = document.getElementById('modal-map');
                        if (!mapEl) return;

                        activeModalMap = L.map('modal-map', {
                            zoomControl: true,
                            scrollWheelZoom: false   // nonaktifkan scroll zoom di modal agar tidak ganggu scroll halaman
                        }).setView([report.latitude, report.longitude], 15);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            maxZoom: 19,
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>'
                        }).addTo(activeModalMap);

                        L.marker([report.latitude, report.longitude]).addTo(activeModalMap);

                        // Panggil invalidateSize 2x: segera + setelah 250ms (untuk animasi modal)
                        activeModalMap.invalidateSize();
                        setTimeout(() => { activeModalMap.invalidateSize(); }, 250);
                    });
                });
            }
        }

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
                    openModal(reportId);
                    loadFeed();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
            }
        }

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
    </script>
</body>
</html>