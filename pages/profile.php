<?php
/**
 * User Profile Page
 * /pages/profile.php
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
    <title>Profil - PetFounds</title>
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
                <a href="post_report.php" class="nav-item">
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
                <a href="profile.php" class="nav-item active">
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
                <!-- Profile Header Card -->
                <div class="profile-header-card">
                    <div class="profile-cover"></div>
                    
                    <div class="profile-info-wrapper">
                        <div class="profile-avatar-large">
                            <div class="online-indicator"></div>
                            <img id="profile-avatar" src="<?php echo htmlspecialchars(normalizeAssetUrl($currentUser['avatar'])); ?>" alt="Avatar">
                            <button id="edit-avatar-btn" class="edit-avatar-btn" title="Ubah Avatar">
                                <i class="fa-solid fa-camera"></i>
                            </button>
                            <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                        </div>
                        
                        <div class="profile-details">
                            <div class="profile-main-info">
                                <h2><?php echo htmlspecialchars($currentUser['name']); ?></h2>
                                <p class="profile-email"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                <?php if (!empty($currentUser['phone'])): ?>
                                    <p class="profile-phone"><?php echo htmlspecialchars($currentUser['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($currentUser['bio'])): ?>
                                <div class="profile-bio-box">
                                    <p class="profile-bio"><?php echo htmlspecialchars($currentUser['bio']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="profile-actions">
                            <button class="btn btn-primary" id="edit-profile-btn">
                                <i class="fa-solid fa-edit"></i> Edit Profil
                            </button>
                        </div>
                    </div>

                    <!-- Profile Tabs -->
                    <div class="profile-tabs">
                        <button class="tab-btn active" data-tab="activity">
                            <i class="fa-solid fa-list"></i> Aktivitas
                        </button>
                        <button class="tab-btn" data-tab="bookmarks">
                            <i class="fa-solid fa-bookmark"></i> Bookmarks
                        </button>
                        <button class="tab-btn" data-tab="stats">
                            <i class="fa-solid fa-chart-bar"></i> Statistik
                        </button>
                    </div>
                </div>

                <!-- Profile Content -->
                <div style="margin-top: 30px;">
                    <!-- Activity Tab -->
                    <div id="activity-tab" class="profile-tab-content">
                        <div class="profile-activity-section">
                            <div class="profile-activity-header">
                                <h3>Laporan Terbaru</h3>
                            </div>
                            <div class="activity-list" id="profile-activity-list">
                                <!-- Loaded via JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Bookmarks Tab -->
                    <div id="bookmarks-tab" class="profile-tab-content" style="display: none;">
                        <div class="profile-activity-section">
                            <div class="profile-activity-header">
                                <h3>Bookmarks</h3>
                            </div>
                            <div class="activity-list" id="profile-bookmarks-list">
                                <!-- Loaded via JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Stats Tab -->
                    <div id="stats-tab" class="profile-tab-content" style="display: none;">
                        <div class="stats-bento">
                            <div style="background: var(--surface); padding: 30px; border-radius: 16px; text-align: center; border: 1px solid var(--border);">
                                <h4 style="color: var(--text-muted); font-size: 0.9rem;">Total Laporan</h4>
                                <p style="font-size: 2.5rem; color: var(--primary); font-weight: 800; margin-top: 10px;" id="stat-reports">0</p>
                            </div>
                            <div style="background: var(--surface); padding: 30px; border-radius: 16px; text-align: center; border: 1px solid var(--border);">
                                <h4 style="color: var(--text-muted); font-size: 0.9rem;">Laporan Hilang</h4>
                                <p style="font-size: 2.5rem; color: var(--danger); font-weight: 800; margin-top: 10px;" id="stat-lost">0</p>
                            </div>
                            <div style="background: var(--surface); padding: 30px; border-radius: 16px; text-align: center; border: 1px solid var(--border);">
                                <h4 style="color: var(--text-muted); font-size: 0.9rem;">Laporan Ditemukan</h4>
                                <p style="font-size: 2.5rem; color: var(--success); font-weight: 800; margin-top: 10px;" id="stat-found">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal-overlay" id="edit-profile-modal">
        <div class="modal-content">
            <button id="close-edit-modal" class="btn-close-modal">
                <i class="fa-solid fa-times"></i>
            </button>
            <h2 style="margin-bottom: 20px;">Edit Profil</h2>
            
            <form id="edit-profile-form">
                <div class="input-modern">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="edit-name" placeholder="Nama Lengkap" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" id="edit-phone" placeholder="Nomor Telepon">
                </div>
                <div class="input-modern no-icon">
                    <textarea id="edit-bio" placeholder="Bio / Deskripsi Singkat" rows="4" style="padding: 15px 20px;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 15px;">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Report Detail Modal -->
    <div class="modal-overlay" id="report-detail-modal">
        <div class="modal-content">
            <button id="close-report-modal" class="btn-close-modal">
                <i class="fa-solid fa-times"></i>
            </button>
            <div id="report-modal-body"></div>
        </div>
    </div>

    <script src="../js/functions.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;

        // Load profile on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadProfileActivity();
            loadProfileStats();
            setupProfileForm();
            setupAvatarUpload();
            setupTabSwitching();
            setupReportDetailModal();
            setupLogout();
        });

        // Load profile activity
        async function loadProfileActivity() {
            try {
                const response = await fetch('../api/profile.php?action=reports');
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderActivity(data.data.reports || [], 'profile-activity-list');
                }
            } catch (error) {
                showToast('Gagal memuat aktivitas: ' + error.message, 'error');
            }
        }

        // Load profile bookmarks
        async function loadProfileBookmarks() {
            try {
                const response = await fetch('../api/profile.php?action=bookmarks');
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderActivity(data.data.bookmarks || [], 'profile-bookmarks-list');
                }
            } catch (error) {
                showToast('Gagal memuat bookmarks: ' + error.message, 'error');
            }
        }

        // Render activity
        function renderActivity(reports, containerId = 'profile-activity-list') {
            const container = document.getElementById(containerId);
            const isBookmarkTab = containerId === 'profile-bookmarks-list';
            
            if (!reports || reports.length === 0) {
                const emptyText = isBookmarkTab ? 'Belum ada bookmarks' : 'Belum ada laporan';
                container.innerHTML = '<div class="profile-empty-wrapper"><div class="profile-empty-state">' + emptyText + '</div></div>';
                return;
            }

            container.innerHTML = reports.map(report => {
                const isFound = report.type === 'found';
                const badgeClass = isFound ? 'found' : 'lost';
                const statusText = isFound ? 'Ditemukan' : 'Hilang';
                const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
                const petType = report.species ? report.species : 'Jenis tidak diketahui';
                const description = report.description ? report.description.substring(0, 120) + (report.description.length > 120 ? '...' : '') : '';
                const eventDate = report.eventDate ? report.eventDate : null;
                const createdAt = report.createdRelative || report.date || 'Waktu tidak diketahui';

                return `
                <div class="feed-card" data-report-id="${report.id}" onclick="openReportDetail(${report.id})">
                    <div class="card-img-box">
                        <img src="${report.image}" alt="${petName}">
                        <span class="card-badge badge-${badgeClass}">${statusText}</span>
                    </div>
                    <div class="card-body">
                        <div class="card-title-row">
                            <h3>${petName}</h3>
                            <span class="card-label">${petType}</span>
                        </div>
                        <p class="card-description">${description}</p>
                        <div class="card-info-grid">
                            <div class="info-item info-event"><i class="fa-solid fa-calendar"></i> ${eventDate || 'Tanggal tidak tersedia'}</div>
                            <div class="info-item"><i class="fa-solid fa-map-marker-alt"></i> ${report.location || 'Lokasi tidak tersedia'}</div>
                            <div class="info-item info-created"><i class="fa-solid fa-clock"></i> ${createdAt}</div>
                            <div class="info-item info-spacer">&nbsp;</div>
                        </div>
                    </div>
                    ${isBookmarkTab ? `
                        <button class="btn-like action-btn bookmark-action liked" title="Hapus bookmark" onclick="toggleBookmark(event, ${report.id})">
                            <i class="fa-solid fa-bookmark"></i>
                        </button>
                    ` : `
                        <div class="activity-options" onclick="toggleActivityMenu(event, ${report.id})">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </div>
                        <div class="activity-dropdown" id="dropdown-${report.id}">
                            <div class="activity-dropdown-item" onclick="editReport(event, ${report.id})">
                                <i class="fa-solid fa-edit"></i> Edit
                            </div>
                            <div class="activity-dropdown-item" onclick="markAsDone(event, ${report.id})">
                                <i class="fa-solid fa-check"></i> Mark as Done
                            </div>
                            <div class="activity-dropdown-item danger" onclick="deleteReport(event, ${report.id})">
                                <i class="fa-solid fa-trash"></i> Delete
                            </div>
                        </div>
                    `}
                </div>
            `;
            }).join('');
        }

        // Load profile stats
        async function loadProfileStats() {
            try {
                const response = await fetch('../api/profile.php');
                const data = await response.json();
                
                if (data.status === 'success') {
                    const profile = data.data;
                    document.getElementById('stat-reports').textContent = profile.reports_count;
                    document.getElementById('stat-lost').textContent = profile.lost_count ?? 0;
                    document.getElementById('stat-found').textContent = profile.found_count ?? 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Setup profile form
        function setupProfileForm() {
            document.getElementById('edit-profile-btn').addEventListener('click', () => {
                // Load current values
                document.getElementById('edit-name').value = currentUser.name;
                document.getElementById('edit-phone').value = currentUser.phone || '';
                document.getElementById('edit-bio').value = currentUser.bio || '';
                
                document.getElementById('edit-profile-modal').classList.add('show');
            });

            document.getElementById('close-edit-modal').addEventListener('click', () => {
                document.getElementById('edit-profile-modal').classList.remove('show');
            });

            document.getElementById('edit-profile-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('name', document.getElementById('edit-name').value);
                formData.append('phone', document.getElementById('edit-phone').value);
                formData.append('bio', document.getElementById('edit-bio').value);
                
                try {
                    const response = await fetch('../api/profile.php?action=update', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        showToast('Profil berhasil diperbarui', 'success');
                        document.getElementById('edit-profile-modal').classList.remove('show');
                        currentUser.name = data.data.name;
                        currentUser.bio = data.data.bio;
                        currentUser.phone = data.data.phone;
                        location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('Gagal memperbarui profil: ' + error.message, 'error');
                }
            });
        }

        // Setup avatar upload
        function setupAvatarUpload() {
            document.getElementById('edit-avatar-btn').addEventListener('click', () => {
                document.getElementById('avatar-input').click();
            });

            document.getElementById('avatar-input').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('avatar', file);
                
                try {
                    const response = await fetch('../api/profile.php?action=avatar', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        showToast('Avatar berhasil diperbarui', 'success');
                        document.getElementById('profile-avatar').src = data.data.avatar_url;
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('Gagal upload avatar: ' + error.message, 'error');
                }
            });
        }

        // Setup tab switching
        function setupTabSwitching() {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabName = btn.dataset.tab;
                    
                    // Remove active from all tabs
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.profile-tab-content').forEach(t => t.style.display = 'none');
                    
                    // Add active to clicked tab
                    btn.classList.add('active');
                    document.getElementById(tabName + '-tab').style.display = 'block';

                    if (tabName === 'activity') {
                        loadProfileActivity();
                    } else if (tabName === 'bookmarks') {
                        loadProfileBookmarks();
                    }
                });
            });
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

        // Activity card options functions
        function toggleActivityMenu(event, reportId) {
            event.stopPropagation();
            
            // Close all other dropdowns
            document.querySelectorAll('.activity-dropdown').forEach(dropdown => {
                if (dropdown.id !== `dropdown-${reportId}`) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            const dropdown = document.getElementById(`dropdown-${reportId}`);
            dropdown.classList.toggle('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.activity-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        });

        // Unbookmark from bookmarks list
        async function toggleBookmark(event, reportId) {
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
                    showToast('Bookmark dihapus', 'success');
                    loadProfileBookmarks();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
            }
        }

        // Open report detail modal from profile
        async function openReportDetail(reportId) {
            try {
                const response = await fetch(`../api/reports.php?id=${reportId}`, {
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.status !== 'success') {
                    showToast(data.message || 'Gagal memuat detail laporan', 'error');
                    return;
                }

                renderReportDetail(data.data);
                document.getElementById('report-detail-modal').classList.add('show');
            } catch (error) {
                showToast('Gagal memuat detail laporan: ' + error.message, 'error');
            }
        }

        function renderReportDetail(report) {
            const container = document.getElementById('report-modal-body');
            const typeText = report.type === 'found' ? 'DITEMUKAN' : 'HILANG';
            const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
            const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
            const speciesDetail = report.speciesDetail ? ` (${report.speciesDetail})` : '';
            const createdUpdatedText = report.updatedAt ? `Diperbarui ${report.updatedAt}` : `Dipublikasikan ${report.createdAt}`;

            container.innerHTML = `
                <div class="modal-report-detail">
                    <div class="modal-header-section">
                        <div class="modal-image-container">
                            <img src="${report.image}" alt="${petName}" class="modal-pet-image">
                            <div class="modal-badge ${badgeClass}">${typeText}</div>
                        </div>
                        <div class="modal-info-section">
                            <h2 class="modal-pet-name">${petName}</h2>
                            <p class="modal-pet-species">${report.species}${speciesDetail}</p>
                            <div class="modal-meta-info">
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-map-marker-alt"></i>
                                    <span>${report.location || 'Lokasi tidak tersedia'}</span>
                                </div>
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>${report.eventDate || 'Tanggal tidak diketahui'}</span>
                                </div>
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>${createdUpdatedText}</span>
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
                            ${document.querySelector('.tab-btn.active')?.dataset.tab === 'activity' ? `
                                <div class="modal-action-wrapper">
                                    <button class="btn btn-icon modal-action-btn" title="Opsi laporan" onclick="toggleModalActionMenu(event, ${report.id})">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="modal-action-dropdown" id="modal-dropdown-${report.id}">
                                        <div class="modal-action-dropdown-item" onclick="editReport(event, ${report.id})">
                                            <i class="fa-solid fa-edit"></i> Edit
                                        </div>
                                        <div class="modal-action-dropdown-item" onclick="markAsDone(event, ${report.id})">
                                            <i class="fa-solid fa-check"></i> Mark as Done
                                        </div>
                                        <div class="modal-action-dropdown-item danger" onclick="deleteReport(event, ${report.id})">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </div>
                                    </div>
                                </div>
                            ` : `
                                <button class="btn btn-like action-btn ${report.isLiked ? 'liked' : ''}" title="Simpan ke Bookmarks" onclick="toggleBookmarkModal(event, ${report.id})">
                                    <i class="${report.isLiked ? 'fa-solid' : 'fa-regular'} fa-bookmark"></i>
                                    <span>${report.likes}</span>
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;
        }

        async function toggleBookmarkModal(event, reportId) {
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
                    openReportDetail(reportId);
                    loadProfileBookmarks();
                    loadProfileActivity();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
            }
        }

        function setupReportDetailModal() {
            const modal = document.getElementById('report-detail-modal');
            const closeButton = document.getElementById('close-report-modal');

            closeButton.addEventListener('click', () => {
                modal.classList.remove('show');
                document.querySelectorAll('.modal-action-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                    document.querySelectorAll('.modal-action-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
                }
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.modal-action-wrapper')) {
                    document.querySelectorAll('.modal-action-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    document.querySelectorAll('.modal-action-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
                }
            });
        }

        function toggleModalActionMenu(event, reportId) {
            event.stopPropagation();
            const dropdown = document.getElementById(`modal-dropdown-${reportId}`);
            if (!dropdown) return;
            document.querySelectorAll('.modal-action-dropdown').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            dropdown.classList.toggle('show');
        }

        // Edit report function
        async function editReport(event, reportId) {
            event.stopPropagation();
            window.location.href = `post_report.php?page=create&edit=${reportId}`;
        }

        // Mark as done function
        async function markAsDone(event, reportId) {
            event.stopPropagation();
            try {
                const response = await fetch(`../api/reports.php?id=${reportId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ status: 'completed' })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('Laporan berhasil ditandai sebagai selesai', 'success');
                    loadProfileActivity(); // Reload activity
                } else {
                    showToast(data.message || 'Gagal menandai laporan sebagai selesai', 'error');
                }
            } catch (error) {
                showToast('Gagal menandai laporan sebagai selesai: ' + error.message, 'error');
            }
        }

        // Delete report function
        async function deleteReport(event, reportId) {
            event.stopPropagation();
            try {
                const response = await fetch(`../api/reports.php?action=delete&id=${reportId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('Laporan berhasil dihapus', 'success');
                    loadProfileActivity(); // Reload activity
                } else {
                    showToast(data.message || 'Gagal menghapus laporan', 'error');
                }
            } catch (error) {
                showToast('Gagal menghapus laporan: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
