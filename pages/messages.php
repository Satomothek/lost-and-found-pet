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
                        <div>
                            <h1>Chats</h1>
                        </div>
                    </div>
                    <div class="chat-wrapper">
                        <!-- Chat List Panel -->
                        <div class="chat-list-panel" id="chat-contacts-panel">
                            <div class="chat-panel-header">
                                <div>
                                    <h2>Search</h2>
                                </div>
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
                                <form id="chat-form" style="display: flex; width: 100%; gap: 10px;">
                                    <input type="text" id="chat-input" placeholder="Pilih kontak dulu untuk mulai chat" autocomplete="off" disabled>
                                    <button type="submit" id="chat-send-btn" class="btn btn-primary" style="padding: 10px 22px;" disabled>
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
