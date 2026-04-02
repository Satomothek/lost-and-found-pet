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
            <div class="chat-wrapper" style="margin: 0 20px;">
                <!-- Chat List Panel -->
                <div class="chat-list-panel" id="chat-contacts-panel">
                    <div class="chat-contacts" id="chat-contact-list">
                        <!-- Loaded via JavaScript -->
                    </div>
                </div>

                <!-- Chat Active Panel -->
                <div class="chat-active-panel">
                    <div class="chat-header">
                        <div>
                            <img id="active-chat-avatar" src="" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 15px;">
                            <span id="active-chat-name" style="font-size: 1rem; font-weight: 700;">Pilih kontak</span>
                        </div>
                    </div>

                    <div class="chat-messages" id="chat-messages-area">
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                            Pilih percakapan untuk mulai chat
                        </div>
                    </div>

                    <div class="chat-input-area">
                        <form id="chat-form" style="display: flex; width: 100%; gap: 10px;">
                            <input type="text" id="chat-input" placeholder="Tulis pesan..." autocomplete="off">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                                <i class="fa-solid fa-send"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/functions.js"></script>
    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        let activeChatId = null;

        // Load contacts on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadChatContacts();
            setupLogout();
            setupChatForm();
        });

        // Load chat contacts
        async function loadChatContacts() {
            try {
                const response = await fetch('../api/messages.php?action=contacts');
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderContacts(data.data.contacts || []);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memuat kontak: ' + error.message, 'error');
            }
        }

        // Render contacts
        function renderContacts(contacts) {
            const container = document.getElementById('chat-contact-list');
            
            if (!contacts || contacts.length === 0) {
                container.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-muted);">Belum ada percakapan</div>';
                return;
            }

            container.innerHTML = contacts.map(contact => `
                <div class="contact-item" onclick="selectChat(${contact.contact_id}, this)">
                    <img src="${contact.avatar}" class="contact-avatar">
                    <div class="contact-info">
                        <div class="contact-name">
                            ${contact.contactName} 
                            <span class="contact-time">${contact.time}</span>
                        </div>
                        <div class="contact-preview">${contact.text}</div>
                    </div>
                </div>
            `).join('');
        }

        // Select chat
        async function selectChat(contactId, element) {
            activeChatId = contactId;
            
            // Update active state
            document.querySelectorAll('.contact-item').forEach(el => {
                el.classList.remove('active');
            });
            element.classList.add('active');
            
            // Load chat history
            try {
                const response = await fetch(`../api/messages.php?action=history&contact_id=${contactId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderMessages(data.data.messages || []);
                    
                    // Get contact info
                    const contactInfo = document.querySelector('.contact-item.active');
                    if (contactInfo) {
                        const avatar = contactInfo.querySelector('.contact-avatar').src;
                        const name = contactInfo.querySelector('.contact-name').textContent.split(/\d{1,2}:\d{2}/)[0];
                        
                        document.getElementById('active-chat-avatar').src = avatar;
                        document.getElementById('active-chat-name').textContent = name.trim();
                    }
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memuat chat: ' + error.message, 'error');
            }
        }

        // Render messages
        function renderMessages(messages) {
            const container = document.getElementById('chat-messages-area');
            
            if (!messages || messages.length === 0) {
                container.innerHTML = '<div style="padding: 20px;">Belum ada pesan</div>';
                return;
            }

            container.innerHTML = '<div class="chat-date-divider"><span>Hari Ini</span></div>' + 
                messages.map(msg => `
                <div class="msg-bubble ${msg.sender === 'me' ? 'msg-sent' : 'msg-received'}">
                    ${msg.text}
                    <span class="msg-time">${msg.time}</span>
                </div>
            `).join('');
            
            // Scroll ke bawah
            container.scrollTop = container.scrollHeight;
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
