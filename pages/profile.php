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
                        <button class="tab-btn" data-tab="stats">
                            <i class="fa-solid fa-chart-bar"></i> Statistik
                        </button>
                    </div>
                </div>

                <!-- Profile Content -->
                <div style="margin-top: 30px;">
                    <!-- Activity Tab -->
                    <div id="activity-tab" class="profile-tab-content">
                        <div style="margin-bottom: 20px;">
                            <h3 style="color: var(--secondary); margin-bottom: 15px;">Laporan Terbaru</h3>
                        </div>
                        <div class="activity-list" id="profile-activity-list">
                            <!-- Loaded via JavaScript -->
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
            setupLogout();
        });

        // Load profile activity
        async function loadProfileActivity() {
            try {
                const response = await fetch('../api/profile.php?action=reports');
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderActivity(data.data.reports || []);
                }
            } catch (error) {
                showToast('Gagal memuat aktivitas: ' + error.message, 'error');
            }
        }

        // Render activity
        function renderActivity(reports) {
            const container = document.getElementById('profile-activity-list');
            
            if (!reports || reports.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);">Belum ada laporan</div>';
                return;
            }

            container.innerHTML = reports.map(report => {
                const isFound = report.type === 'found';
                const badgeClass = isFound ? 'found' : 'lost';
                const statusText = isFound ? 'Ditemukan' : 'Hilang';
                const petName = report.petName !== 'Unknown' ? report.petName : 'Seekor ' + report.species;
                const petType = report.species ? report.species : 'Jenis tidak diketahui';
                
                return `
                <div class="activity-card">
                    <img src="${report.image}" alt="${petName}">
                    <div class="activity-card-content">
                        <div class="activity-info">
                            <h4>${petName}</h4>
                            <span class="activity-type">${petType}</span>
                        </div>
                        <div class="activity-meta">
                            <span><i class="fa-solid fa-map-marker-alt"></i> ${report.location}</span>
                            <span><i class="fa-solid fa-calendar"></i> ${report.date}</span>
                        </div>
                        <p class="activity-description">${report.description ? report.description.substring(0, 100) + (report.description.length > 100 ? '...' : '') : ''}</p>
                    </div>
                    <div class="activity-status ${badgeClass}">${statusText}</div>
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
    </script>
</body>
</html>
