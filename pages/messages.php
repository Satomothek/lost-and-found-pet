<?php
/**
 * Messages / Chat Page
 * /pages/messages.php
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
    <title>Pesan - PetFounds</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/pages/messages.css">
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
                <a href="messages.php" class="nav-item active">
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
                <div class="chat-page-container">
                    <div class="page-header-panel">
                        <h1>Pesan</h1>
                        <p>Percakapan dengan sesama pengguna PetFounds</p>
                    </div>
                    <div class="chat-wrapper">
                        <!-- Chat List Panel -->
                        <div class="chat-list-panel" id="chat-contacts-panel">
                            <div class="chat-panel-header">
                                <h2><i class="fa-solid fa-user-group" style="margin-right:6px;"></i>Kontak</h2>
                            </div>
                            <div class="chat-search">
                                <input type="search" id="chat-search" placeholder="Cari kontak atau pesan terakhir..." autocomplete="off">
                            </div>
                            <div class="chat-contacts" id="chat-contact-list">
                                <!-- Loaded via JavaScript -->
                            </div>
                        </div>

                        <!-- Chat Active Panel -->
                        <div class="chat-active-panel">
                            <div class="chat-header">
                                <div class="chat-header-left">
                                    <img id="active-chat-avatar" src="<?php echo htmlspecialchars(normalizeAssetUrl($currentUser['avatar'])); ?>" alt="Avatar kontak" class="chat-header-avatar">
                                    <div class="chat-header-title">
                                        <span id="active-chat-name">Pilih kontak</span>
                                        <span id="active-chat-status" class="chat-header-status">Pilih percakapan untuk mulai chat</span>
                                    </div>
                                </div>
                                <div class="chat-menu-wrapper">
                                    <button id="btn-chat-options" class="btn btn-ghost" title="Opsi percakapan" onclick="toggleChatMenu()"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div id="chat-menu-dropdown" class="chat-menu-dropdown">
                                        <div class="chat-menu-item" role="button" tabindex="0" onclick="viewContactProfile()"><i class="fa-solid fa-user"></i> Lihat profile</div>
                                        <div class="chat-menu-item" role="button" tabindex="0" onclick="deleteChatHistory()"><i class="fa-solid fa-trash"></i> Hapus chats</div>
                                        <div id="block-user-item" class="chat-menu-item danger" role="button" tabindex="0" onclick="blockChatUser()"><i class="fa-solid fa-ban"></i> Blokir pengguna</div>
                                        <div id="unblock-user-item" class="chat-menu-item danger" role="button" tabindex="0" onclick="unblockChatUser()" style="display:none;"><i class="fa-solid fa-unlock"></i> Buka blokir pengguna</div>
                                    </div>
                                </div>
                            </div>

                            <div class="chat-messages" id="chat-messages-area">
                                <div class="chat-empty-state">
                                    <i class="fa-solid fa-comments fa-2x"></i>
                                    <p>Belum ada percakapan</p>
                                    <span>Pilih kontak di sebelah kiri untuk mulai berbicara.</span>
                                </div>
                            </div>

                            <div class="chat-input-area">
                                <form id="chat-form" style="display:flex;width:100%;gap:12px;align-items:center;">
                                    <input type="text" id="chat-input" placeholder="Pilih kontak dulu untuk mulai chat" autocomplete="off" disabled>
                                    <button type="submit" id="chat-send-btn" class="btn btn-primary" disabled>
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-overlay" id="contact-profile-modal">
                    <div class="modal-window contact-profile-modal">
                        <div class="modal-profile-card">
                            <button class="modal-close" onclick="closeContactProfile()"><i class="fa-solid fa-xmark"></i></button>
                            <div class="modal-profile-header">
                                <img id="profile-modal-avatar" src="" alt="Avatar kontak">
                                <div>
                                    <h3 id="profile-modal-name">Pengguna</h3>
                                    <p id="profile-modal-bio">Informasi profil akan ditampilkan di sini.</p>
                                </div>
                            </div>
                            <div class="modal-profile-body">
                                <div class="modal-profile-details">
                                    <div><strong>Email:</strong> <span id="profile-modal-email">-</span></div>
                                    <div><strong>Telepon:</strong> <span id="profile-modal-phone">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../public/js/utils.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
    </script>
    <script src="../public/js/pages/messages.js"></script>
</body>
</html>