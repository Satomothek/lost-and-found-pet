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
                <a href="post_report.php" class="nav-item active">
                    <i class="fa-solid fa-compass"></i>
                    <span>Jelajahi</span>
                </a>
                <a href="post_report.php?page=create" class="nav-item">
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
        <main class="main-content page-pt">
            <div class="container-fluid">
                <!-- Search Bar -->
                <div class="search-modern" style="margin-bottom: 30px;">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="search-feed" placeholder="Cari nama hewan, lokasi, atau deskripsi...">
                </div>

                <!-- Feed Grid -->
                <div class="feed-grid" id="feed-container">
                    <!-- Loaded via JavaScript -->
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
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        
        // Load feed on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadFeed();
            setupSearch();
            setupLogout();
        });

        // Load feed from API
        async function loadFeed(search = '') {
            try {
                const url = new URL('../api/reports.php', window.location.origin);
                if (search) url.searchParams.append('search', search);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderFeed(data.data.reports);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
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

            container.innerHTML = reports.map(report => `
                <div class="feed-card" onclick="openModal(${report.id})">
                    <div class="card-img-box">
                        <div class="card-badge ${report.type === 'found' ? 'badge-found' : 'badge-lost'}">
                            ${report.type === 'found' ? 'FOUND' : 'LOST'}
                        </div>
                        <img src="${report.image}" alt="Pet Image" loading="lazy">
                    </div>
                    <div class="card-body">
                        <h3>${report.petName !== 'Unknown' ? report.petName : 'Seekor ' + report.species}</h3>
                        <div class="card-meta">
                            <i class="fa-solid fa-map-marker-alt"></i> ${report.location}
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="author-box">
                            <img src="${report.authorImg}" class="author-img" alt="Author">
                            <div class="author-text">
                                <span class="author-name">${report.author}</span><br>
                                <span style="font-size:0.75rem; color:var(--text-muted);">${report.date}</span>
                            </div>
                        </div>
                        <button class="btn-like action-btn ${report.isLiked ? 'liked' : ''}" onclick="toggleLike(${report.id}, event)">
                            <i class="${report.isLiked ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
                        </button>
                    </div>
                </div>
            `).join('');
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
                    const response = await fetch('../api/logout.php');
                    await response.json();
                    showToast('Logout berhasil', 'success');
                    setTimeout(() => {
                        window.location.href = '../pages/login.php';
                    }, 1000);
                } catch (error) {
                    showToast('Gagal logout: ' + error.message, 'error');
                }
            });
        }
    </script>
</body>
</html>
