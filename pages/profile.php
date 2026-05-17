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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <style>
        /* ── Modal Map Fix (sama persis dengan explore.php) ── */
        #report-detail-modal.modal-overlay { z-index: 1100 !important; }
        #report-detail-modal .modal-content { overflow: hidden !important; overflow-y: auto !important; }
        .modal-map-clipper {
            width: 100%; height: 260px;
            border-radius: 12px; overflow: hidden;
            position: relative; background: #e8edf2;
            border: 1px solid var(--border, #e2e8f0);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-top: 10px;
        }
        #profile-modal-map {
            width: 100% !important; height: 100% !important;
            position: absolute !important; inset: 0 !important;
            overflow: visible !important; background: #e8edf2 !important;
        }
        #profile-modal-map .leaflet-pane    { z-index: 400 !important; }
        #profile-modal-map .leaflet-top,
        #profile-modal-map .leaflet-bottom  { z-index: 401 !important; }

        /* ── Card footer (author box + action buttons) — sama seperti explore ── */
        .feed-card .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px 14px;
            border-top: 1.5px solid rgba(226,232,240,0.6);
            gap: 8px;
        }
        .feed-card .author-box {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .feed-card .author-img {
            width: 30px; height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(226,232,240,0.8);
            flex-shrink: 0;
        }
        .feed-card .author-text {
            min-width: 0;
        }
        .feed-card .author-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--secondary);
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        .feed-card .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .feed-card .action-btn {
            width: 30px; height: 30px;
            border-radius: 50%;
            border: 1.5px solid rgba(226,232,240,0.8);
            background: rgba(248,250,252,0.9);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .feed-card .action-btn:hover { background: #fff; border-color: var(--primary); color: var(--primary); }
        .feed-card .btn-like.liked { color: var(--primary); border-color: var(--primary); background: rgba(79,70,229,0.08); }
        .feed-card .btn-chat:hover { color: #10b981; border-color: #10b981; background: rgba(16,185,129,0.08); }
    </style>
    <style>
        /* ══════════════════════════════════════════════════
           PROFILE PAGE — Fixed & Enhanced Design
           Semua aturan di sini menimpa style.css secara eksplisit
           untuk menghindari konflik / double-render.
        ══════════════════════════════════════════════════ */

        /* ── Page container ── */
        .profile-page-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto 60px;
        }

        /* ── Header card ── */
        .profile-header-card {
            background: #fff;
            border-radius: 28px;
            border: 1.5px solid rgba(226,232,240,0.85);
            box-shadow: 0 20px 60px -12px rgba(15,23,42,0.12);
            overflow: hidden;
            margin-bottom: 24px;
        }

        /* Cover banner */
        .profile-cover {
            height: 160px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
            position: relative;
            overflow: hidden;
        }
        .profile-cover::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(255,255,255,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(168,85,247,0.4) 0%, transparent 50%);
        }

        /* Info wrapper */
        .profile-info-wrapper {
            display: flex !important;
            align-items: flex-end !important;
            gap: 24px;
            padding: 0 32px 28px;
            flex-wrap: wrap;
            margin-top: 0 !important; /* override style.css responsive rule */
        }

        /* Avatar */
        .profile-avatar-large {
            position: relative;
            margin-top: -56px;
            flex-shrink: 0;
        }
        .profile-avatar-large img {
            width: 112px; height: 112px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 8px 28px rgba(79,70,229,0.22);
            display: block;
        }
        .online-indicator {
            position: absolute;
            bottom: 8px; right: 8px;
            width: 18px; height: 18px;
            background: #10b981;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.3);
        }
        .edit-avatar-btn {
            position: absolute;
            top: 4px; right: 4px;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--primary);
            border: 2.5px solid #fff;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 0.75rem;
            transition: transform 0.18s ease, background 0.18s ease;
            box-shadow: 0 4px 12px rgba(79,70,229,0.35);
        }
        .edit-avatar-btn:hover { transform: scale(1.12); background: var(--primary-hover); }

        /* Profile details */
        .profile-details {
            flex: 1;
            min-width: 0;
            padding-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .profile-main-info h2 {
            font-size: 1.6rem; font-weight: 800;
            color: var(--secondary); margin: 0 0 4px;
            letter-spacing: -0.02em;
        }
        .profile-email {
            font-size: 0.88rem; color: var(--text-muted);
            display: flex; align-items: center; gap: 6px;
            margin: 0 0 2px;
        }
        .profile-email::before { content: '✉'; font-size: 0.8rem; }
        .profile-phone {
            font-size: 0.88rem; color: var(--text-muted);
            display: flex; align-items: center; gap: 6px;
            margin: 0;
        }
        .profile-phone::before { content: '📞'; font-size: 0.8rem; }
        .profile-bio-box {
            margin-top: 10px;
            background: #f8fafd;
            border: 1.5px solid rgba(226,232,240,0.85);
            border-radius: 12px;
            padding: 10px 16px;
            max-width: 520px;
        }
        .profile-bio {
            font-size: 0.88rem; color: var(--text-muted);
            line-height: 1.6; margin: 0;
        }

        /* Actions */
        .profile-actions {
            padding-top: 16px;
            flex-shrink: 0;
            margin-left: auto; /* dorong ke kanan */
        }
        .profile-actions .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 22px;
            font-size: 0.9rem; font-weight: 600;
            border-radius: var(--radius-full);
            box-shadow: 0 8px 20px rgba(79,70,229,0.25);
        }

        /* ── Stats strip ── */
        .profile-stats-strip {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            border: 1.5px solid rgba(226,232,240,0.85);
            border-radius: 20px;
            padding: 22px 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(15,23,42,0.05);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(15,23,42,0.10); }
        .stat-card-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin: 0 auto 12px;
        }
        .stat-card-icon.indigo { background: rgba(79,70,229,0.1); color: var(--primary); }
        .stat-card-icon.red    { background: rgba(239,68,68,0.1);  color: var(--danger); }
        .stat-card-icon.green  { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-card-value {
            font-size: 2.2rem; font-weight: 800;
            line-height: 1; margin-bottom: 6px;
            letter-spacing: -0.03em;
        }
        .stat-card-value.indigo { color: var(--primary); }
        .stat-card-value.red    { color: var(--danger); }
        .stat-card-value.green  { color: #10b981; }
        .stat-card-label {
            font-size: 0.8rem; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* ── Tabs — override style.css .tab-btn sepenuhnya ── */
        .profile-tabs {
            display: flex !important;
            flex-wrap: wrap;
            gap: 8px;
            padding: 20px 32px 24px;
            border-top: 1.5px solid rgba(226,232,240,0.85);
        }
        .profile-tabs .tab-btn {
            /* Reset semua rule dari style.css */
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px;
            padding: 9px 20px !important;
            border-radius: 999px !important;
            border: 1.5px solid rgba(226,232,240,0.85) !important;
            border-bottom: 1.5px solid rgba(226,232,240,0.85) !important; /* override border-bottom: 3px solid transparent */
            background: transparent !important;
            color: var(--text-muted) !important;
            font-size: 0.86rem !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.18s ease !important;
            font-family: 'Outfit', sans-serif;
            white-space: nowrap;
        }
        .profile-tabs .tab-btn:hover {
            border-color: var(--primary) !important;
            color: var(--primary) !important;
            background: rgba(79,70,229,0.05) !important;
            transform: none !important; /* override btn hover transform */
        }
        .profile-tabs .tab-btn.active {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            border-bottom-color: var(--primary) !important;
            color: #fff !important;
            box-shadow: 0 6px 18px rgba(79,70,229,0.28) !important;
        }
        .profile-tabs .tab-btn i { font-size: 0.8rem; }

        /* ── Content wrapper ── */
        .profile-content-wrapper {
            background: #fff;
            border: 1.5px solid rgba(226,232,240,0.85);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(15,23,42,0.05);
        }

        /* ── Activity section header — override style.css ── */
        .profile-activity-section {
            width: 100%;
            display: block; /* override flex di style.css */
            align-items: unset;
            gap: unset;
        }
        .profile-activity-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            text-align: left !important; /* override text-align: center dari style.css */
            width: 100% !important;
            max-width: 100% !important;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1.5px solid rgba(226,232,240,0.85);
        }
        .profile-activity-header h3 {
            font-size: 1rem !important;
            font-weight: 700 !important;
            color: var(--secondary) !important;
            margin: 0 !important;
        }

        /* ── Activity list — override style.css sepenuhnya ── */
        .activity-list {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
            gap: 18px !important;
            margin-top: 0 !important; /* override margin-top: 40px dari style.css */
            width: 100%;
        }

        /* ── Activity card (feed-card) — sesuai HTML yang di-render JS ── */
        .feed-card {
            position: relative;
            background: #fff;
            border: 1.5px solid rgba(226,232,240,0.85);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15,23,42,0.05);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        .feed-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(15,23,42,0.12);
            border-color: rgba(79,70,229,0.3);
        }

        /* Image box */
        .feed-card .card-img-box {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .feed-card .card-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .feed-card:hover .card-img-box img { transform: scale(1.05); }

        /* Status badge di dalam gambar */
        .feed-card .card-badge {
            position: absolute;
            top: 10px; left: 10px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .feed-card .badge-lost  { background: rgba(239,68,68,0.92);  color: #fff; }
        .feed-card .badge-found { background: rgba(16,185,129,0.92); color: #fff; }

        /* Card body */
        .feed-card .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }
        .feed-card .card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .feed-card .card-title-row h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .feed-card .card-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            background: rgba(226,232,240,0.6);
            padding: 3px 10px;
            border-radius: 999px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .feed-card .card-description {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .feed-card .card-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 8px;
            margin-top: 4px;
        }
        .feed-card .info-item {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .feed-card .info-item i { color: var(--primary); font-size: 0.7rem; }
        .feed-card .info-spacer { display: none; }

        /* Options button di pojok kanan atas gambar */
        .feed-card .activity-options {
            position: absolute !important;
            top: 10px !important;
            right: 10px !important;
            bottom: auto !important;
            width: 28px !important;
            height: 28px !important;
            border-radius: 50% !important;
            background: rgba(255,255,255,0.92) !important;
            border: 1.5px solid rgba(226,232,240,0.8) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer;
            font-size: 0.78rem !important;
            color: var(--text-muted) !important;
            box-shadow: 0 2px 6px rgba(15,23,42,0.1) !important;
            z-index: 5 !important;
            transition: all 0.15s ease !important;
        }
        .feed-card .activity-options:hover {
            background: #fff !important;
            color: var(--primary) !important;
            border-color: var(--primary) !important;
            transform: none !important;
        }

        /* Dropdown pada card */
        .feed-card .activity-dropdown {
            position: absolute !important;
            top: 44px !important;
            right: 10px !important;
            bottom: auto !important;
            left: auto !important;
            min-width: 155px;
            background: #fff !important;
            border: 1.5px solid rgba(226,232,240,0.9) !important;
            border-radius: 14px !important;
            box-shadow: 0 12px 32px rgba(15,23,42,0.13) !important;
            padding: 5px !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-6px) scale(0.97) !important;
            transition: all 0.15s ease !important;
            z-index: 20 !important;
            pointer-events: none !important;
        }
        .feed-card .activity-dropdown.show {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) scale(1) !important;
            pointer-events: auto !important;
        }
        .feed-card .activity-dropdown-item {
            padding: 9px 13px !important;
            border-radius: 9px !important;
            border-bottom: none !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: var(--text-main) !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            cursor: pointer;
            transition: background 0.12s ease !important;
        }
        .feed-card .activity-dropdown-item:hover {
            background: rgba(79,70,229,0.07) !important;
            color: var(--primary) !important;
        }
        .feed-card .activity-dropdown-item.danger { color: var(--danger) !important; }
        .feed-card .activity-dropdown-item.danger:hover {
            background: rgba(239,68,68,0.08) !important;
        }

        /* Bookmark button */
        .feed-card .bookmark-action {
            position: absolute;
            top: 10px; right: 10px;
            background: rgba(255,255,255,0.92);
            border: 1.5px solid rgba(226,232,240,0.8);
            border-radius: 50%;
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem;
            color: var(--primary);
            cursor: pointer;
            z-index: 5;
            transition: all 0.15s ease;
        }
        .feed-card .bookmark-action:hover { background: #fff; transform: scale(1.1); }

        /* Empty state */
        .profile-empty-wrapper {
            grid-column: 1 / -1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 60px 24px !important;
            width: 100% !important;
        }
        .profile-empty-state {
            text-align: center !important;
            color: var(--text-muted) !important;
            font-size: 0.95rem !important;
            font-weight: 500 !important;
            border: none !important;
            background: none !important;
            max-width: none !important;
        }

        /* Stats bento */
        .stats-bento {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 18px !important;
        }
        .stats-bento > div {
            border-radius: 20px !important;
            border: 1.5px solid rgba(226,232,240,0.85) !important;
            box-shadow: 0 4px 16px rgba(15,23,42,0.05) !important;
            transition: transform 0.18s ease;
        }
        .stats-bento > div:hover { transform: translateY(-3px); }

        /* ── Edit Profile Modal ── */
        #edit-profile-modal .modal-content {
            border-radius: 28px;
            padding: 36px 32px 32px;
            max-width: 480px;
        }
        #edit-profile-modal h2 {
            font-size: 1.3rem; font-weight: 800;
            color: var(--secondary); margin-bottom: 24px;
        }
        #edit-profile-form .input-modern { margin-bottom: 14px; }
        #edit-profile-form textarea { resize: none; min-height: 100px; }
        #edit-profile-form .btn-block {
            width: 100%; justify-content: center;
            border-radius: var(--radius-full);
            padding: 14px !important; font-size: 0.95rem; font-weight: 700;
            margin-top: 8px !important;
            box-shadow: 0 10px 24px rgba(79,70,229,0.28);
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .profile-info-wrapper { flex-direction: column !important; align-items: flex-start !important; gap: 16px; padding: 0 20px 24px; }
            .profile-actions { margin-left: 0; }
            .profile-stats-strip { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .profile-tabs { padding: 16px 20px 20px; }
            .stats-bento { grid-template-columns: 1fr !important; }
            .profile-content-wrapper { padding: 20px 16px; }
            .activity-list { grid-template-columns: 1fr !important; }
            .card-img-box { height: 160px; }
        }
        @media (max-width: 480px) {
            .profile-stats-strip { grid-template-columns: 1fr !important; }
        }
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


    <script src="../js/functions.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        let activeProfileModalMap = null;

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
                const typeText = isFound ? 'FOUND' : 'LOST';
                const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
                const petType = report.species || 'Jenis tidak diketahui';
                const speciesDetailText = report.speciesDetail || report.species_detail || '';
                const rawDescription = report.description || report.desc || '';
                const descriptionSnippet = rawDescription ? rawDescription.substring(0, 90) + (rawDescription.length > 90 ? '...' : '') : 'Tidak ada deskripsi tambahan.';
                const descriptionHtml = speciesDetailText
                    ? '<strong>' + escapeHtml(speciesDetailText) + '</strong> ' + escapeHtml(descriptionSnippet)
                    : escapeHtml(descriptionSnippet);
                const eventDate = report.eventDate || null;
                const createdRelative = report.createdRelative || report.date || 'Baru saja';
                const updatedRelative = report.updatedRelative || null;
                const createdUpdatedLabel = updatedRelative ? updatedRelative : createdRelative;
                const locationDescription = report.location_description ? escapeHtml(report.location_description) : '';
                const locationText = escapeHtml(report.location || 'Lokasi tidak tersedia');
                const locationDisplay = locationDescription ? locationText + ' - ' + locationDescription : locationText;
                const conditionText = locationDescription || '-';
                const hasCoords = report.latitude !== null && report.latitude !== undefined
                               && report.longitude !== null && report.longitude !== undefined;
                const locationLabel = hasCoords ? 'Memuat alamat...' : getShortLocation(locationDisplay);
                const locationData = hasCoords
                    ? ` data-latitude="${report.latitude}" data-longitude="${report.longitude}" data-fallback="${locationDisplay}"`
                    : '';
                const authorImg = report.authorImg || report.avatar_url || 'https://i.pravatar.cc/48?img=68';
                const authorName = report.author || report.name || 'Anonim';
                const isLiked = report.isLiked ? true : false;
                const iconClass = isLiked ? 'fa-solid' : 'fa-regular';
                const likeClass = isLiked ? 'liked' : '';
                const petImage = report.image || 'https://via.placeholder.com/600x400?text=Pet+Image';

                return `
                <div class="feed-card" data-report-id="${report.id}" onclick="openReportDetail(${report.id})">
                    <div class="card-img-box">
                        <img src="${petImage}" alt="${escapeHtml(petName)}" loading="lazy" onerror="this.src='https://via.placeholder.com/600x400?text=Pet+Image'">
                        <span class="card-badge badge-${badgeClass}">${typeText}</span>
                        ${!isBookmarkTab ? `
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
                        ` : ''}
                    </div>
                    <div class="card-body">
                        <div class="card-title-row">
                            <h3>${escapeHtml(petName)}</h3>
                            <span class="card-label">${escapeHtml(petType)}</span>
                        </div>
                        <p class="card-description">${descriptionHtml}</p>
                        <div class="card-info-grid">
                            <span class="info-item info-event"><i class="fa-solid fa-calendar"></i> ${escapeHtml(eventDate || '-')}</span>
                            <span class="info-item info-location"${locationData}><i class="fa-solid fa-map-marker-alt"></i> ${escapeHtml(locationLabel)}</span>
                            <span class="info-item info-created"><i class="fa-solid fa-clock"></i> ${createdUpdatedLabel}</span>
                            <span class="info-item info-condition"><i class="fa-solid fa-map-pin"></i> ${conditionText}</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="author-box">
                            <img src="${escapeHtml(authorImg)}" class="author-img" alt="Author" onerror="this.src='https://i.pravatar.cc/48?img=68'">
                            <div class="author-text">
                                <span class="author-name">${escapeHtml(authorName)}</span>
                                <span style="font-size:0.75rem; color:var(--text-muted);">${createdUpdatedLabel}</span>
                            </div>
                        </div>
                        <div class="action-buttons">
                            ${isBookmarkTab ? `
                                <button class="btn-like action-btn liked" title="Hapus bookmark" onclick="toggleBookmark(event, ${report.id})">
                                    <i class="fa-solid fa-bookmark"></i>
                                </button>
                            ` : `
                                <button class="btn-like action-btn ${likeClass}" title="Simpan ke Bookmarks" onclick="toggleBookmarkCard(event, ${report.id})">
                                    <i class="${iconClass} fa-bookmark"></i>
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;
            }).join('');

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

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        async function toggleBookmarkCard(event, reportId) {
            event.stopPropagation();
            try {
                const response = await fetch('../api/likes.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: reportId })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    loadProfileActivity();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
            }
        }

        // Load profile stats
        async function loadProfileStats() {
            try {
                const response = await fetch('../api/profile.php');
                const data = await response.json();
                
                if (data.status === 'success') {
                    const profile = data.data;
                    const total  = profile.reports_count ?? 0;
                    const lost   = profile.lost_count   ?? 0;
                    const found  = profile.found_count  ?? 0;
                    // Stats tab
                    document.getElementById('stat-reports').textContent = total;
                    document.getElementById('stat-lost').textContent    = lost;
                    document.getElementById('stat-found').textContent   = found;
                    // Stats tab only
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
            // Destroy peta lama sebelum inject HTML baru
            if (activeProfileModalMap) {
                activeProfileModalMap.remove();
                activeProfileModalMap = null;
            }

            const container = document.getElementById('report-modal-body');
            const typeText = report.type === 'found' ? 'DITEMUKAN' : 'HILANG';
            const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
            const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
            const speciesDetail = report.speciesDetail ? ` (${report.speciesDetail})` : '';
            const createdUpdatedText = report.updatedAt ? `Diperbarui ${report.updatedAt}` : `Dipublikasikan ${report.createdAt}`;
            const isActivityTab = document.querySelector('.tab-btn.active')?.dataset.tab === 'activity';

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }

            const hasCoords = report.latitude !== null && report.latitude !== undefined
                           && report.longitude !== null && report.longitude !== undefined;

            container.innerHTML = `
                <div class="modal-report-detail">
                    <div class="modal-header-section">
                        <div class="modal-image-container">
                            <img src="${report.image}" alt="${escapeHtml(petName)}" class="modal-pet-image">
                            <div class="modal-badge ${badgeClass}">${typeText}</div>
                        </div>
                        <div class="modal-info-section">
                            <h2 class="modal-pet-name">${escapeHtml(petName)}</h2>
                            <p class="modal-pet-species">${escapeHtml(report.species)}${escapeHtml(speciesDetail)}</p>
                            <div class="modal-meta-info">
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-map-marker-alt"></i>
                                    <span>${escapeHtml(report.location || 'Lokasi tidak tersedia')}</span>
                                </div>
                                ${report.location_description ? `
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-map-pin"></i>
                                    <span>${escapeHtml(report.location_description)}</span>
                                </div>` : ''}
                                <div class="modal-meta-item">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>${escapeHtml(report.eventDate || 'Tanggal tidak diketahui')}</span>
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
                                    <span>Edited: ${report.updated_at ? formatDate(report.updated_at) : '-'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-description-section">
                        <h3>Detail Laporan</h3>
                        <p class="modal-description">${(report.description || '').replace(/\n/g, '<br>')}</p>
                    </div>
                    ${hasCoords ? `
                    <div class="modal-map-section">
                        <h3>Lokasi pada Peta</h3>
                        <div class="modal-map-clipper">
                            <div id="profile-modal-map"></div>
                        </div>
                    </div>
                    ` : ''}
                    <div class="modal-author-section">
                        <div class="modal-author-info">
                            <img src="${escapeHtml(report.authorImg || 'https://i.pravatar.cc/48?img=68')}" alt="Author" class="modal-author-avatar">
                            <div class="modal-author-details">
                                <span class="modal-author-name">${escapeHtml(report.author)}</span>
                                <span class="modal-author-role">Pelapor</span>
                            </div>
                        </div>
                        <div class="modal-actions">
                            ${isActivityTab ? `
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
                                    <span>${report.likes || 0}</span>
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;

            // Inisialisasi Leaflet — sama persis dengan explore.php
            if (hasCoords && window.L) {
                delete L.Icon.Default.prototype._getIconUrl;
                L.Icon.Default.mergeOptions({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                });

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const mapEl = document.getElementById('profile-modal-map');
                        if (!mapEl) return;

                        activeProfileModalMap = L.map('profile-modal-map', {
                            zoomControl: true,
                            scrollWheelZoom: false
                        }).setView([report.latitude, report.longitude], 15);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            maxZoom: 19,
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>'
                        }).addTo(activeProfileModalMap);

                        L.marker([report.latitude, report.longitude]).addTo(activeProfileModalMap);

                        activeProfileModalMap.invalidateSize();
                        setTimeout(() => { activeProfileModalMap.invalidateSize(); }, 250);
                    });
                });
            }
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

            function closeModal() {
                modal.classList.remove('show');
                document.querySelectorAll('.modal-action-dropdown.show').forEach(d => d.classList.remove('show'));
                if (activeProfileModalMap) {
                    activeProfileModalMap.remove();
                    activeProfileModalMap = null;
                }
            }

            closeButton.addEventListener('click', closeModal);

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.modal-action-wrapper')) {
                    document.querySelectorAll('.modal-action-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
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
            window.location.href = `create_report.php?edit=${reportId}`;
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