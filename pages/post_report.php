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
        $petName = $report['petName'] !== 'Unknown' ? htmlspecialchars($report['petName']) : 'Seekor ' . htmlspecialchars($report['species']);
        $location = htmlspecialchars($report['location']);
        $author = htmlspecialchars($report['author']);
        $date = htmlspecialchars($report['date']);

        $html .= "<div class=\"feed-card\" onclick=\"openModal({$report['id']})\">";
        $html .= "<div class=\"card-img-box\">";
        $html .= "<div class=\"card-badge {$badgeClass}\">{$typeBadge}</div>";
        $html .= "<img src=\"{$petImage}\" alt=\"Pet Image\" loading=\"lazy\">";
        $html .= "</div>";
        $html .= "<div class=\"card-body\">";
        $html .= "<h3>{$petName}</h3>";
        $html .= "<div class=\"card-meta\"><i class=\"fa-solid fa-map-marker-alt\"></i> {$location}</div>";
        $html .= "</div>";
        $html .= "<div class=\"card-footer\">";
        $html .= "<div class=\"author-box\">";
        $html .= "<img src=\"{$authorImage}\" class=\"author-img\" alt=\"Author\">";
        $html .= "<div class=\"author-text\">";
        $html .= "<span class=\"author-name\">{$author}</span><br>";
        $html .= "<span style=\"font-size:0.75rem; color:var(--text-muted);\">{$date}</span>";
        $html .= "</div>";
        $html .= "</div>";
        $likeClass = $report['isLiked'] ? 'liked' : '';
        $heartStyle = $report['isLiked'] ? 'fa-solid' : 'fa-regular';
        $html .= "<button class=\"btn-like action-btn {$likeClass}\" onclick=\"toggleLike({$report['id']}, event)\">";
        $html .= "<i class=\"{$heartStyle} fa-heart\"></i>";
        $html .= "</button>";
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
                'location' => $report['location'],
                'date' => timeAgo($report['created_at']),
                'desc' => $report['description'],
                'image' => normalizeAssetUrl($report['image_url'] ?: 'https://via.placeholder.com/600x400?text=Pet+Image'),
                'likes' => intval($report['likes']),
                'isLiked' => boolval($report['isLiked'])
            ];
        }, $reports);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isCreatePage) {
    $type = sanitizeInput($_POST['type'] ?? '');
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $reportDate = sanitizeInput($_POST['date'] ?? '');

    if (!$type || !$species || !$location || !$description || !$reportDate) {
        $_SESSION['create_report_error'] = 'Semua field wajib diisi';
        header('Location: post_report.php?page=create');
        exit;
    }

    if (!in_array($type, ['lost', 'found'])) {
        $_SESSION['create_report_error'] = 'Tipe laporan tidak valid';
        header('Location: post_report.php?page=create');
        exit;
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $reportDate);
    $today = new DateTime('today');
    $minDate = (clone $today)->modify('-7 days');

    if (!$dateObj || $dateObj < $minDate || $dateObj > $today) {
        $_SESSION['create_report_error'] = 'Tanggal harus antara ' . $minDate->format('Y-m-d') . ' sampai ' . $today->format('Y-m-d');
        header('Location: post_report.php?page=create');
        exit;
    }

    $imageUrl = 'https://via.placeholder.com/600x400?text=Pet+Image';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image'], '../public/uploads/');
        if ($upload['success']) {
            $imageUrl = 'public/uploads/' . $upload['filename'];
        }
    }

    $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, location, description, image_url)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $result = executeQuery($connection, $query, [
        $currentUser['id'],
        $type,
        $petName,
        $species,
        $location,
        $description,
        $imageUrl
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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - PetFounds</title>
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
                <div class="page-header" style="margin-bottom: 30px;">
                    <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Publikasikan Laporan Jaringan</h1>
                    <p style="color: var(--text-muted);">Isi detail laporan untuk membantu komunitas menemukan atau melaporkan hewan.</p>
                </div>

                <form id="form-create-report" class="glass-panel-form" enctype="multipart/form-data" method="POST" action="?page=create">
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
                            <strong style="font-size: 1rem; color: var(--text-dark);">Tarik foto atau klik untuk unggah</strong>
                            <span style="color: var(--text-muted);">Rasio kotak (1:1) direkomendasikan</span>
                            <input type="file" id="report-image" name="image" accept="image/*" style="display:none;">
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

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Publikasikan Laporan</button>
                </form>
            </div>
        </main>
        <?php else: ?>
        <main class="main-content page-pt">
            <div class="container-fluid">
                <!-- Search Bar -->
                <div class="search-modern" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="search-feed" placeholder="Cari nama hewan, lokasi, atau deskripsi...">
                </div>

                <!-- Feed Grid -->
                <div class="feed-grid" id="feed-container">
                    <?php echo renderReportCards($initialReports); ?>
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
                const title = report.petName !== 'Unknown' ? report.petName : 'Seekor ' + report.species;
                const likeClass = report.isLiked ? 'liked' : '';
                const iconClass = report.isLiked ? 'fa-solid' : 'fa-regular';

                html += '<div class="feed-card" onclick="openModal(' + report.id + ')">';
                html += '<div class="card-img-box">';
                html += '<div class="card-badge ' + badgeClass + '">' + typeText + '</div>';
                html += '<img src="' + petImageSrc + '" alt="Pet Image" loading="lazy">';
                html += '</div>';
                html += '<div class="card-body">';
                html += '<h3>' + title + '</h3>';
                html += '<div class="card-meta"><i class="fa-solid fa-map-marker-alt"></i> ' + report.location + '</div>';
                html += '</div>';
                html += '<div class="card-footer">';
                html += '<div class="author-box">';
                html += '<img src="' + authorImageSrc + '" class="author-img" alt="Author">';
                html += '<div class="author-text">';
                html += '<span class="author-name">' + report.author + '</span><br>';
                html += '<span style="font-size:0.75rem; color:var(--text-muted);">' + report.date + '</span>';
                html += '</div>';
                html += '</div>';
                html += '<button class="btn-like action-btn ' + likeClass + '" onclick="toggleLike(' + report.id + ', event)">';
                html += '<i class="' + iconClass + ' fa-heart"></i>';
                html += '</button>';
                html += '</div>';
                html += '</div>';
            });

            container.innerHTML = html;
        }

        function setupCreateForm() {
            const reportTypeRadios = document.querySelectorAll('input[name="type"]');
            const reportImage = document.getElementById('report-image');
            const selectedFileName = document.getElementById('selected-file-name');
            const reportForm = document.getElementById('form-create-report');

            reportTypeRadios.forEach((radio) => {
                radio.addEventListener('change', () => {
                    setReportType(radio.value);
                });
            });

            const initialType = document.querySelector('input[name="type"]:checked');
            if (initialType) {
                setReportType(initialType.value);
            }

            reportImage.addEventListener('change', () => {
                selectedFileName.textContent = reportImage.files.length > 0 ? reportImage.files[0].name : 'Tidak ada file dipilih';
            });

            reportForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const formData = new FormData(reportForm);

                try {
                    const response = await fetch('../api/reports.php?action=create', {
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
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'post_report.php?refresh=' + Date.now();
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

        // Open modal
        function openModal(reportId) {
            console.log('Opening modal for report:', reportId);
            // Implementation untuk menampilkan detail
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
