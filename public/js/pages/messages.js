/**
 * Messages page script
 * Handles contact listing, real-time messaging, blocking/unblocking users, and managing chat history
 */

// Global variables (initialized by inline script in PHP: currentUser)
let activeChatId = null;
let activeChatUser = null;
let chatContacts = [];

// Load contacts on page load
document.addEventListener('DOMContentLoaded', () => {
    loadChatContacts();
    setupLogout(); // from utils.js
    setupChatForm();
    setupSearch();
    updateChatInputState();
});

// Load chat contacts
async function loadChatContacts() {
    try {
        const response = await fetch('../api/messages.php?action=contacts', { credentials: 'same-origin' });
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
                            const historyResponse = await fetch(`../api/messages.php?action=history&contact_id=${contactId}`, { credentials: 'same-origin' });
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
    if (!container) return;
    
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
        const profileResponse = await fetch(`../api/messages.php?action=user&user_id=${contactId}`, { credentials: 'same-origin' });
        const profileData = await profileResponse.json();
        if (profileData.status === 'success' && profileData.data.contact) {
            activeChatUser = { ...activeChatUser, ...profileData.data.contact };
        }

        const response = await fetch(`../api/messages.php?action=history&contact_id=${contactId}`, { credentials: 'same-origin' });
        const data = await response.json();
        
        if (data.status === 'success') {
            renderMessages(data.data.messages || []);
            updateChatHeader();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal memuat chat: ' + error.message, 'error');
    }
}

function updateChatHeader() {
    const avatarEl = document.getElementById('active-chat-avatar');
    if (avatarEl) {
        avatarEl.src = activeChatUser?.avatar;
        avatarEl.alt = activeChatUser?.contactName ? `${activeChatUser.contactName} avatar` : 'Avatar kontak';
    }
    const nameEl = document.getElementById('active-chat-name');
    if (nameEl) {
        nameEl.textContent = activeChatUser?.contactName || 'Kontak Terpilih';
    }
    const statusEl = document.getElementById('active-chat-status');
    if (statusEl) {
        if (activeChatUser?.isBlocked) {
            statusEl.textContent = 'Pengguna diblokir';
        } else {
            statusEl.textContent = activeChatUser?.bio ? activeChatUser.bio : 'Siap mengobrol';
        }
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

// Update menu options based on block status
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
    if (!chatInput || !sendBtn) return;
    
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
    const modal = document.getElementById('contact-profile-modal');
    if (modal) modal.classList.remove('show');
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

    const avatarEl = document.getElementById('profile-modal-avatar');
    if (avatarEl) avatarEl.src = activeChatUser?.avatar;

    const nameEl = document.getElementById('profile-modal-name');
    if (nameEl) nameEl.textContent = activeChatUser?.contactName || 'Pengguna';
    
    const bioEl = document.getElementById('profile-modal-bio');
    if (bioEl) bioEl.textContent = activeChatUser?.bio || 'Belum ada informasi profil.';
    
    const emailEl = document.getElementById('profile-modal-email');
    if (emailEl) emailEl.textContent = activeChatUser?.email || '-';
    
    const phoneEl = document.getElementById('profile-modal-phone');
    if (phoneEl) phoneEl.textContent = activeChatUser?.phone || '-';
    
    const modal = document.getElementById('contact-profile-modal');
    if (modal) modal.classList.add('show');
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
            credentials: 'same-origin',
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
            credentials: 'same-origin',
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
            credentials: 'same-origin',
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

// Click outside dropdown handler
document.addEventListener('click', (event) => {
    const dropdown = document.getElementById('chat-menu-dropdown');
    const button = document.getElementById('btn-chat-options');
    if (!dropdown || !button) return;
    if (button.contains(event.target)) return;
    if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Escape key handler
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
    if (!container) return;
    
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
    const chatForm = document.getElementById('chat-form');
    if (!chatForm) return;

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!activeChatId) {
            showToast('Pilih kontak terlebih dahulu', 'error');
            return;
        }
        
        const messageInput = document.getElementById('chat-input');
        const message = messageInput ? messageInput.value.trim() : '';
        if (!message) return;
        
        try {
            const response = await fetch('../api/messages.php?action=send', {
                method: 'POST',
                credentials: 'same-origin',
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
                if (messageInput) messageInput.value = '';
                
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
