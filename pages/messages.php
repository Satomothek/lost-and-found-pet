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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* ══════════════════════════════════════════════════
           MESSAGES PAGE — redesign selaras create_report.php
           Card putih bersih, bg blur ball, tipografi Outfit
        ══════════════════════════════════════════════════ */

        /* ── Wrapper utama ── */
        .chat-page-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto 40px;
            padding: 32px 36px 36px;
            background: rgba(255,255,255,0.97);
            border: 1px solid rgba(226,232,240,0.85);
            border-radius: 28px;
            box-shadow: 0 24px 64px -12px rgba(15,23,42,0.13);
        }

        .page-header-panel {
            margin-bottom: 28px;
        }
        .page-header-panel h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--secondary);
            letter-spacing: -0.02em;
            margin: 0 0 4px;
        }
        .page-header-panel p {
            color: var(--text-muted);
            font-size: 0.92rem;
            margin: 0;
        }

        /* ── Chat wrapper ── */
        .chat-wrapper {
            display: flex;
            border-radius: 20px;
            overflow: hidden;
            border: 1.5px solid rgba(226,232,240,0.9);
            box-shadow: 0 4px 20px rgba(15,23,42,0.06);
            min-height: calc(100vh - 300px);
            background: #fff;
            width: 100%;
        }

        /* ── LEFT PANEL: Kontak ── */
        .chat-list-panel {
            width: 320px;
            min-width: 280px;
            border-right: 1.5px solid rgba(226,232,240,0.9);
            display: flex;
            flex-direction: column;
            background: #f8fafd;
            flex-shrink: 0;
        }

        .chat-panel-header {
            padding: 24px 20px 0;
        }
        .chat-panel-header h2 {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin: 0 0 14px;
        }

        /* Search bar kontak */
        .chat-search {
            padding: 0 16px 16px;
        }
        .chat-search input {
            width: 100%;
            padding: 11px 16px 11px 40px;
            border-radius: var(--radius-full);
            border: 1.5px solid rgba(226,232,240,0.95);
            background: #fff;
            font-size: 0.88rem;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            outline: none;
            transition: var(--transition);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 14px center;
        }
        .chat-search input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.10);
        }
        .chat-search input::placeholder { color: var(--text-muted); }

        /* Daftar kontak */
        .chat-contacts {
            flex: 1;
            overflow-y: auto;
        }
        .chat-contacts::-webkit-scrollbar { width: 4px; }
        .chat-contacts::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.3); border-radius: 4px; }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 14px 18px;
            cursor: pointer;
            transition: background 0.15s ease;
            border-bottom: 1px solid rgba(226,232,240,0.6);
            position: relative;
        }
        .contact-item:hover { background: rgba(79,70,229,0.05); }
        .contact-item.active {
            background: rgba(79,70,229,0.08);
            border-left: 3px solid var(--primary);
        }
        .contact-item.blocked-contact { opacity: 0.6; }
        .contact-item.blocked-contact:hover { background: rgba(239,68,68,0.05); }
        .contact-item.blocked-contact.active { background: rgba(239,68,68,0.08); border-left-color: var(--danger); }

        .contact-avatar {
            width: 46px; height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(79,70,229,0.15);
            flex-shrink: 0;
        }
        .contact-item.blocked-contact .contact-avatar { border-color: rgba(239,68,68,0.25); }

        .contact-info { flex: 1; min-width: 0; }
        .contact-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
        }
        .contact-time {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .contact-preview {
            font-size: 0.82rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .blocked-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 0.72rem; color: var(--danger);
            background: rgba(239,68,68,0.1);
            padding: 2px 8px; border-radius: var(--radius-full);
            font-weight: 600;
        }

        /* ── RIGHT PANEL: Area chat ── */
        .chat-active-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            min-width: 0;
        }

        /* Header chat */
        .chat-header {
            padding: 18px 24px;
            border-bottom: 1.5px solid rgba(226,232,240,0.9);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            gap: 16px;
        }
        .chat-header-left {
            display: flex; align-items: center; gap: 13px;
        }
        .chat-header-avatar {
            width: 44px; height: 44px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(79,70,229,0.15);
        }
        .chat-header-title {
            display: flex; flex-direction: column; gap: 2px;
        }
        .chat-header-title #active-chat-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--secondary);
        }
        .chat-header-status {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* Options button */
        .chat-menu-wrapper { position: relative; }
        #btn-chat-options {
            width: 36px !important; height: 36px !important;
            border-radius: 50% !important;
            background: #f1f5f9 !important;
            border: 1.5px solid rgba(226,232,240,0.9) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: var(--transition) !important;
            box-shadow: none !important;
            color: var(--text-muted) !important;
        }
        #btn-chat-options:hover {
            background: #fff !important;
            border-color: var(--primary) !important;
            color: var(--primary) !important;
            box-shadow: 0 4px 12px rgba(79,70,229,0.12) !important;
        }

        /* Dropdown menu */
        .chat-menu-dropdown {
            position: absolute;
            top: calc(100% + 8px); right: 0;
            min-width: 180px;
            background: #fff;
            border: 1.5px solid rgba(226,232,240,0.9);
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(15,23,42,0.13);
            padding: 6px;
            opacity: 0; visibility: hidden;
            transform: translateY(-8px) scale(0.97);
            transition: all 0.17s ease;
            z-index: 30;
            pointer-events: none;
        }
        .chat-menu-dropdown.show {
            opacity: 1; visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .chat-menu-item {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.88rem;
            color: var(--text-main);
            display: flex; align-items: center; gap: 10px;
            cursor: pointer;
            transition: background 0.14s ease;
            border: none; background: transparent;
        }
        .chat-menu-item:hover { background: rgba(79,70,229,0.07); }
        .chat-menu-item.danger { color: var(--danger); }
        .chat-menu-item.danger:hover { background: rgba(239,68,68,0.08); }

        /* Area pesan */
        .chat-messages {
            flex: 1;
            padding: 24px 28px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #fafbff;
        }
        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.3); border-radius: 4px; }

        /* Date divider */
        .chat-date-divider {
            text-align: center; margin: 6px 0;
            display: flex; align-items: center; gap: 12px;
        }
        .chat-date-divider::before,
        .chat-date-divider::after {
            content: ''; flex: 1;
            height: 1px; background: rgba(226,232,240,0.9);
        }
        .chat-date-divider span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 600;
            background: #f1f5f9;
            padding: 4px 14px;
            border-radius: var(--radius-full);
            white-space: nowrap;
        }

        /* Bubble pesan */
        .msg-bubble {
            max-width: 68%;
            padding: 12px 16px;
            font-size: 0.9rem;
            line-height: 1.55;
            position: relative;
            animation: fadeUp 0.22s ease-out;
            word-break: break-word;
        }
        .msg-received {
            background: #fff;
            color: var(--text-main);
            align-self: flex-start;
            border-radius: 18px 18px 18px 4px;
            box-shadow: 0 2px 10px rgba(15,23,42,0.07);
            border: 1.5px solid rgba(226,232,240,0.8);
        }
        .msg-sent {
            background: var(--primary);
            color: #fff;
            align-self: flex-end;
            border-radius: 18px 18px 4px 18px;
            box-shadow: 0 8px 24px rgba(79,70,229,0.22);
        }
        .msg-time {
            font-size: 0.68rem;
            margin-top: 6px;
            display: block;
            text-align: right;
            opacity: 0.6;
        }

        /* Empty state */
        .chat-empty-state {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 10px; height: 100%; text-align: center;
            color: var(--text-muted);
            padding: 40px 24px;
        }
        .chat-empty-state i { font-size: 2.5rem; opacity: 0.35; margin-bottom: 4px; }
        .chat-empty-state p { font-size: 1rem; font-weight: 700; color: var(--secondary); margin: 0; }
        .chat-empty-state span { font-size: 0.86rem; color: var(--text-muted); }

        /* Input area */
        .chat-input-area {
            padding: 16px 20px;
            background: #fff;
            border-top: 1.5px solid rgba(226,232,240,0.9);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        #chat-input {
            flex: 1;
            padding: 14px 20px;
            border-radius: var(--radius-full);
            border: 1.5px solid rgba(226,232,240,0.95);
            background: #f8fafd;
            font-size: 0.92rem;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            outline: none;
            transition: var(--transition);
        }
        #chat-input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.10);
        }
        #chat-input:disabled {
            opacity: 0.6; cursor: not-allowed; background: #f1f5f9;
        }
        #chat-input::placeholder { color: var(--text-muted); font-size: 0.88rem; }

        #chat-send-btn {
            width: 48px !important; height: 48px !important;
            border-radius: 50% !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: var(--primary) !important;
            box-shadow: 0 8px 20px rgba(79,70,229,0.30) !important;
            border: none !important;
            flex-shrink: 0;
            transition: transform 0.15s ease, box-shadow 0.15s ease !important;
        }
        #chat-send-btn:hover:not(:disabled) {
            transform: scale(1.07) !important;
            box-shadow: 0 12px 28px rgba(79,70,229,0.38) !important;
        }
        #chat-send-btn:disabled {
            opacity: 0.5 !important; cursor: not-allowed !important;
            box-shadow: none !important;
        }

        /* ── Profile modal ── */
        .contact-profile-modal {
            max-width: 420px; width: 100%;
        }
        .modal-profile-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(15,23,42,0.15);
            padding: 32px 28px 28px;
            position: relative;
        }
        .modal-close {
            position: absolute; top: 18px; right: 18px;
            width: 36px; height: 36px; border-radius: 50%;
            background: #f1f5f9;
            border: 1.5px solid rgba(226,232,240,0.9);
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--text-muted);
            transition: var(--transition);
            font-size: 0.9rem;
        }
        .modal-close:hover { background: #fff; color: var(--secondary); transform: scale(1.05); }

        .modal-profile-header {
            display: flex; align-items: center; gap: 16px;
            margin-bottom: 24px;
        }
        .modal-profile-header img {
            width: 68px; height: 68px; border-radius: 50%;
            object-fit: cover;
            border: 2.5px solid rgba(79,70,229,0.2);
            box-shadow: 0 4px 14px rgba(79,70,229,0.15);
        }
        .modal-profile-header h3 { margin: 0 0 4px; font-size: 1.15rem; font-weight: 700; color: var(--secondary); }
        .modal-profile-header p { margin: 0; color: var(--text-muted); font-size: 0.87rem; }

        .modal-profile-body {
            background: #f8fafd;
            border: 1.5px solid rgba(226,232,240,0.9);
            border-radius: 18px;
            padding: 16px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .modal-profile-details { display: flex; flex-direction: column; gap: 10px; }
        .modal-profile-details div {
            display: flex; justify-content: space-between; align-items: center;
            background: #fff;
            border: 1.5px solid rgba(226,232,240,0.9);
            border-radius: 12px;
            padding: 11px 16px;
            font-size: 0.9rem;
        }
        .modal-profile-details strong { color: var(--text-muted); font-weight: 600; }
        .modal-profile-details span { color: var(--secondary); font-weight: 600; }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-page-container { padding: 20px 16px 24px; border-radius: 20px; }
            .chat-wrapper { flex-direction: column; min-height: calc(100vh - 240px); }
            .chat-list-panel { width: 100%; min-width: unset; height: 38%; border-right: none; border-bottom: 1.5px solid rgba(226,232,240,0.9); }
            .chat-active-panel { height: 62%; }
            .msg-bubble { max-width: 85%; }
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
                                <img id="profile-modal-avatar" src="https://i.pravatar.cc/150?img=68" alt="Avatar kontak">
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

    <script src="../js/functions.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        let activeChatId = null;
        let activeChatUser = null;
        let chatContacts = [];

        // Load contacts on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadChatContacts();
            setupLogout();
            setupChatForm();
            setupSearch();
            updateChatInputState();
        });

        // Load chat contacts
        async function loadChatContacts() {
            try {
                const response = await fetch('../api/messages.php?action=contacts');
                const data = await response.json();
                
                if (data.status === 'success') {
                    chatContacts = data.data.contacts || [];
                    renderContacts(chatContacts);
                    
                    // Check for contact parameter in URL (from feed chat button)
                    const urlParams = new URLSearchParams(window.location.search);
                    const contactId = urlParams.get('contact');
                    if (contactId) {
                        const contactInList = chatContacts.find(c => String(c.contact_id) === String(contactId));
                        const contactElement = document.querySelector(`.contact-item[data-contact-id="${contactId}"]`);
                        
                        if (contactElement && contactInList) {
                            // Contact already exists in chat list
                            selectChat(contactId, contactElement);
                        } else {
                            // Contact not in chat list yet - load profile and auto-add
                            const contact = await loadContactProfile(contactId);
                            if (contact) {
                                activeChatId = contactId;
                                activeChatUser = contact;
                                
                                // Auto-add contact to chat list if not exists
                                if (!contactInList) {
                                    const newContact = {
                                        contact_id: contactId,
                                        contactName: contact.contactName,
                                        avatar: contact.avatar,
                                        text: contact.bio || 'Mulai percakapan baru',
                                        time: '-',
                                        last_time: new Date(),
                                        isBlocked: contact.isBlocked || false
                                    };
                                    chatContacts.unshift(newContact);
                                    renderContacts(chatContacts);
                                    
                                    // Re-select the contact to mark it active
                                    const newContactElement = document.querySelector(`.contact-item[data-contact-id="${contactId}"]`);
                                    if (newContactElement) {
                                        newContactElement.classList.add('active');
                                    }
                                }
                                
                                updateChatHeader();
                                updateChatInputState();
                                updateChatMenuOptions();
                                
                                // Load chat history
                                try {
                                    const historyResponse = await fetch(`../api/messages.php?action=history&contact_id=${contactId}`);
                                    const historyData = await historyResponse.json();
                                    if (historyData.status === 'success') {
                                        renderMessages(historyData.data.messages || []);
                                    }
                                } catch (err) {
                                    console.warn('Gagal load history:', err);
                                    renderMessages([]);
                                }
                            }
                        }
                    }
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memuat kontak: ' + error.message, 'error');
            }
        }

        function setupSearch() {
            const searchInput = document.getElementById('chat-search');
            if (!searchInput) return;
            searchInput.addEventListener('input', (e) => {
                filterContacts(e.target.value);
            });
        }

        function filterContacts(query) {
            const normalized = query.trim().toLowerCase();
            if (!normalized) {
                renderContacts(chatContacts);
                return;
            }
            const filtered = chatContacts.filter(contact => {
                return contact.contactName.toLowerCase().includes(normalized)
                    || (contact.text || '').toLowerCase().includes(normalized);
            });
            renderContacts(filtered);
        }

        // Render contacts
        function renderContacts(contacts) {
            const container = document.getElementById('chat-contact-list');
            
            if (!contacts || contacts.length === 0) {
                container.innerHTML = '<div class="chat-empty-state"><i class="fa-solid fa-user-friends fa-2x"></i><p>Belum ada percakapan</p><span>Mulai chat dengan klik tombol chat pada laporan.</span></div>';
                return;
            }

            container.innerHTML = contacts.map(contact => {
                const isBlocked = contact.isBlocked ? 'blocked-contact' : '';
                const blockedBadge = contact.isBlocked ? '<span class="blocked-badge"><i class="fa-solid fa-ban"></i> Diblokir</span>' : '';
                return `
                <div class="contact-item ${isBlocked}" data-contact-id="${contact.contact_id}" onclick="selectChat(${contact.contact_id}, this)">
                    <img src="${contact.avatar}" class="contact-avatar">
                    <div class="contact-info">
                        <div class="contact-name">
                            <span>${contact.contactName}</span>
                            <span class="contact-time">${contact.time || '-'}</span>
                        </div>
                        <div class="contact-preview">${blockedBadge || contact.text || contact.bio || 'Mulai percakapan baru'}</div>
                    </div>
                </div>
            `;
            }).join('');
        }

        // Select chat
        async function selectChat(contactId, element) {
            activeChatId = contactId;
            activeChatUser = chatContacts.find(contact => String(contact.contact_id) === String(contactId)) || activeChatUser || null;
            
            document.querySelectorAll('.contact-item').forEach(el => {
                el.classList.remove('active');
            });
            if (element) element.classList.add('active');
            
            updateChatInputState();
            
            try {
                const profileResponse = await fetch(`../api/messages.php?action=user&user_id=${contactId}`);
                const profileData = await profileResponse.json();
                if (profileData.status === 'success' && profileData.data.contact) {
                    activeChatUser = { ...activeChatUser, ...profileData.data.contact };
                }

                const response = await fetch(`../api/messages.php?action=history&contact_id=${contactId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderMessages(data.data.messages || []);
                    updateChatHeader();                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memuat chat: ' + error.message, 'error');
            }
        }

        function updateChatHeader() {
            const avatarEl = document.getElementById('active-chat-avatar');
            if (avatarEl) {
                avatarEl.src = activeChatUser?.avatar || 'https://i.pravatar.cc/150?img=68';
                avatarEl.alt = activeChatUser?.contactName ? `${activeChatUser.contactName} avatar` : 'Avatar kontak';
            }
            document.getElementById('active-chat-name').textContent = activeChatUser?.contactName || 'Kontak Terpilih';
            if (activeChatUser?.isBlocked) {
                document.getElementById('active-chat-status').textContent = 'Pengguna diblokir';
            } else {
                document.getElementById('active-chat-status').textContent = activeChatUser?.bio ? activeChatUser.bio : 'Siap mengobrol';
            }
            updateChatMenuOptions();
        }

        async function loadContactProfile(contactId) {
            try {
                const response = await fetch(`../api/messages.php?action=user&user_id=${contactId}`);
                const data = await response.json();
                if (data.status === 'success') {
                    return data.data.contact;
                }
            } catch (error) {
                console.warn('Tidak dapat memuat profil kontak:', error);
            }
            return null;
        }

        function updateChatMenuOptions() {
            const blockItem = document.getElementById('block-user-item');
            const unblockItem = document.getElementById('unblock-user-item');
            if (!blockItem || !unblockItem) return;

            const isBlocked = Boolean(activeChatUser?.isBlocked);
            blockItem.style.display = isBlocked ? 'none' : 'flex';
            unblockItem.style.display = isBlocked ? 'flex' : 'none';
        }

        function updateChatInputState() {
            const chatInput = document.getElementById('chat-input');
            const sendBtn = document.getElementById('chat-send-btn');
            if (!activeChatId || activeChatUser?.isBlocked) {
                chatInput.disabled = true;
                sendBtn.disabled = true;
                chatInput.placeholder = activeChatUser?.isBlocked ? 'Kontak diblokir, tidak dapat mengirim pesan' : 'Pilih kontak dulu untuk mulai chat';
            } else {
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.placeholder = 'Tulis pesan...';
            }
        }

        function toggleChatMenu() {
            const dropdown = document.getElementById('chat-menu-dropdown');
            if (!dropdown) return;
            dropdown.classList.toggle('show');
        }

        function closeChatMenu() {
            const dropdown = document.getElementById('chat-menu-dropdown');
            if (!dropdown) return;
            dropdown.classList.remove('show');
        }

        function closeContactProfile() {
            document.getElementById('contact-profile-modal').classList.remove('show');
        }

        async function viewContactProfile() {
            closeChatMenu();
            if (!activeChatId) {
                showToast('Pilih kontak terlebih dahulu', 'error');
                return;
            }

            const contactProfile = await loadContactProfile(activeChatId);
            if (contactProfile) {
                activeChatUser = { ...activeChatUser, ...contactProfile };
            }

            document.getElementById('profile-modal-avatar').src = activeChatUser?.avatar || 'https://i.pravatar.cc/150?img=68';
            document.getElementById('profile-modal-name').textContent = activeChatUser?.contactName || 'Pengguna';
            document.getElementById('profile-modal-bio').textContent = activeChatUser?.bio || 'Belum ada informasi profil.';
            document.getElementById('profile-modal-email').textContent = activeChatUser?.email || '-';
            document.getElementById('profile-modal-phone').textContent = activeChatUser?.phone || '-';
            document.getElementById('contact-profile-modal').classList.add('show');
        }

        async function deleteChatHistory() {
            closeChatMenu();
            if (!activeChatId) {
                showToast('Pilih kontak terlebih dahulu', 'error');
                return;
            }
            if (!confirm('Hapus semua pesan dengan kontak ini?')) return;

            try {
                const response = await fetch('../api/messages.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ contact_id: activeChatId })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    renderMessages([]);
                    showToast('Riwayat chat berhasil dihapus.', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal menghapus chat: ' + error.message, 'error');
            }
        }

        async function blockChatUser() {
            closeChatMenu();
            if (!activeChatId) {
                showToast('Pilih kontak terlebih dahulu', 'error');
                return;
            }
            if (!confirm('Blokir pengguna ini? Mereka tidak akan bisa menghubungi Anda lagi.')) return;

            try {
                const response = await fetch('../api/messages.php?action=block', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ blocked_id: activeChatId })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    if (activeChatUser) {
                        activeChatUser.isBlocked = true;
                    }
                    
                    // Update contact in chatContacts array
                    const contactInList = chatContacts.find(c => c.contact_id === activeChatId);
                    if (contactInList) {
                        contactInList.isBlocked = true;
                    }
                    
                    const contactElement = document.querySelector(`.contact-item[data-contact-id="${activeChatId}"]`);
                    if (contactElement) {
                        contactElement.classList.add('blocked-contact');
                        // Update preview to show "Diblokir" badge
                        const previewEl = contactElement.querySelector('.contact-preview');
                        if (previewEl) {
                            previewEl.innerHTML = '<span class="blocked-badge"><i class="fa-solid fa-ban"></i> Diblokir</span>';
                        }
                    }
                    updateChatHeader();
                    updateChatInputState();
                    updateChatMenuOptions();
                    showToast('Pengguna telah diblokir.', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memblokir pengguna: ' + error.message, 'error');
            }
        }

        async function unblockChatUser() {
            closeChatMenu();
            if (!activeChatId) {
                showToast('Pilih kontak terlebih dahulu', 'error');
                return;
            }
            if (!confirm('Buka blokir pengguna ini?')) return;

            try {
                const response = await fetch('../api/messages.php?action=unblock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ blocked_id: activeChatId })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    if (activeChatUser) {
                        activeChatUser.isBlocked = false;
                    }
                    
                    // Update contact in chatContacts array
                    const contactInList = chatContacts.find(c => c.contact_id === activeChatId);
                    if (contactInList) {
                        contactInList.isBlocked = false;
                    }
                    
                    // Update contact element in list
                    const contactElement = document.querySelector(`.contact-item[data-contact-id="${activeChatId}"]`);
                    if (contactElement) {
                        contactElement.classList.remove('blocked-contact');
                        // Update preview text to show last message
                        const previewEl = contactElement.querySelector('.contact-preview');
                        if (previewEl && contactInList) {
                            previewEl.innerHTML = contactInList.text || contactInList.bio || 'Mulai percakapan baru';
                        }
                    }
                    
                    updateChatHeader();
                    updateChatInputState();
                    updateChatMenuOptions();
                    showToast('Pengguna berhasil dibuka blokirnya.', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal membuka blokir pengguna: ' + error.message, 'error');
            }
        }

        document.addEventListener('click', (event) => {
            const dropdown = document.getElementById('chat-menu-dropdown');
            const button = document.getElementById('btn-chat-options');
            if (!dropdown || !button) return;
            if (button.contains(event.target)) return;
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' || event.key === 'Esc') {
                closeContactProfile();
                const dropdown = document.getElementById('chat-menu-dropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // Render messages
        function renderMessages(messages) {
            const container = document.getElementById('chat-messages-area');
            
            if (!messages || messages.length === 0) {
                container.innerHTML = '<div class="chat-empty-state"><i class="fa-solid fa-comment-dots fa-2x"></i><p>Belum ada pesan</p><span>Kirim pesan pertama untuk memulai percakapan.</span></div>';
                return;
            }

            container.innerHTML = '<div class="chat-date-divider"><span>Hari Ini</span></div>' + 
                messages.map(msg => `
                <div class="msg-bubble ${msg.sender === 'me' ? 'msg-sent' : 'msg-received'}">
                    <p>${escapeHtml(msg.text)}</p>
                    <span class="msg-time">${msg.time}</span>
                </div>
            `).join('');
            
            container.scrollTop = container.scrollHeight;
        }

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Setup chat form
        function setupChatForm() {
            document.getElementById('chat-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!activeChatId) {
                    showToast('Pilih kontak terlebih dahulu', 'error');
                    return;
                }
                
                const message = document.getElementById('chat-input').value.trim();
                if (!message) return;
                
                try {
                    const response = await fetch('../api/messages.php?action=send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            receiver_id: activeChatId,
                            message: message
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        document.getElementById('chat-input').value = '';
                        
                        // Update contact in chat list with latest message
                        const contactInList = chatContacts.find(c => c.contact_id === activeChatId);
                        if (contactInList) {
                            contactInList.text = message;
                            contactInList.time = data.data?.time || new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                            contactInList.last_time = new Date();
                            
                            // Move contact to top and re-render
                            const index = chatContacts.indexOf(contactInList);
                            if (index > 0) {
                                chatContacts.splice(index, 1);
                                chatContacts.unshift(contactInList);
                            }
                            renderContacts(chatContacts);
                            
                            // Re-select and mark active
                            const contactElement = document.querySelector(`.contact-item[data-contact-id="${activeChatId}"]`);
                            if (contactElement) {
                                contactElement.classList.add('active');
                            }
                        } else {
                            // If contact doesn't exist in list (fallback), reload contacts
                            await loadChatContacts();
                        }
                        
                        // Reload chat history
                        selectChat(activeChatId, document.querySelector('.contact-item.active'));
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('Gagal mengirim pesan: ' + error.message, 'error');
                }
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