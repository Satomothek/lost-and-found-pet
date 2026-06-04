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
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="../public/css/pages/profile.css">
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
                <a href="explore.php" class="nav-item">
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
                <div class="profile-page-container">
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
                        <button class="tab-btn" data-tab="history">
                            <i class="fa-solid fa-history"></i> History
                        </button>
                        <button class="tab-btn" data-tab="stats">
                            <i class="fa-solid fa-chart-bar"></i> Statistik
                        </button>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="profile-content-wrapper">
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

                    <!-- History Tab -->
                    <div id="history-tab" class="profile-tab-content" style="display: none;">
                        <div class="profile-activity-section">
                            <div class="profile-activity-header">
                                <h3>Riwayat Laporan</h3>
                            </div>
                            <div class="activity-list" id="profile-history-list">
                                <!-- Loaded via JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Stats Tab -->
                    <div id="stats-tab" class="profile-tab-content" style="display: none;">
                        <div class="stats-bento">
                            <div style="background:#fff;padding:28px;border-radius:20px;text-align:center;">
                                <div style="width:48px;height:48px;border-radius:14px;background:rgba(79,70,229,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--primary);font-size:1.2rem;">
                                    <i class="fa-solid fa-file-lines"></i>
                                </div>
                                <p style="font-size:2.4rem;color:var(--primary);font-weight:800;margin:0 0 6px;letter-spacing:-0.03em;" id="stat-reports">0</p>
                                <h4 style="color:var(--text-muted);font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin:0;">Total Laporan</h4>
                            </div>
                            <div style="background:#fff;padding:28px;border-radius:20px;text-align:center;">
                                <div style="width:48px;height:48px;border-radius:14px;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--danger);font-size:1.2rem;">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                <p style="font-size:2.4rem;color:var(--danger);font-weight:800;margin:0 0 6px;letter-spacing:-0.03em;" id="stat-lost">0</p>
                                <h4 style="color:var(--text-muted);font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin:0;">Laporan Hilang</h4>
                            </div>
                            <div style="background:#fff;padding:28px;border-radius:20px;text-align:center;">
                                <div style="width:48px;height:48px;border-radius:14px;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:#10b981;font-size:1.2rem;">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <p style="font-size:2.4rem;color:#10b981;font-weight:800;margin:0 0 6px;letter-spacing:-0.03em;" id="stat-found">0</p>
                                <h4 style="color:var(--text-muted);font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin:0;">Laporan Ditemukan</h4>
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


    <script src="../public/js/utils.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
    </script>
    <script src="../public/js/pages/profile.js"></script>
</body>
</html>