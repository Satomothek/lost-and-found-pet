/**
 * PETFOUNDS - Profesional MPA Javascript Engine
 */

const DB_KEY = 'petfounds_pro_db_v1';
const USER_KEY = 'petfounds_pro_user_v1';

// --- dummy database ---
const dummyData = [
    {
        id: 1708890000000, type: 'lost', author: 'Alex Turner', authorImg: 'https://i.pravatar.cc/150?img=11',
        petName: 'Milo si Persia', species: 'Kucing', location: 'Borobudur, Magelang',
        date: '2 Jam yang lalu', desc: 'Hilang kucing persia medium abu-abu. Kalung merah lonceng perak. Terakhir terlihat melompat keluar dari area homestay.',
        image: 'https://images.unsplash.com/photo-1513360371669-4adf3dd7dff8?auto=format&fit=crop&q=80&w=600',
        likes: 24, isLiked: false
    },
    {
        id: 1708976400000, type: 'found', author: 'Sarena Design', authorImg: 'https://i.pravatar.cc/150?img=33',
        petName: 'Unknown', species: 'Anjing', location: 'Alun-Alun Magelang',
        date: '10 Menit yang lalu', desc: 'Ditemukan anjing Golden Retriever berkeliaran di dekat patung. Sangat jinak. Saat ini amankan di ruko. Hubungi jika ini milik Anda.',
        image: 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&q=80&w=600',
        likes: 58, isLiked: true
    }
];

let appData = JSON.parse(localStorage.getItem(DB_KEY)) || dummyData;
localStorage.setItem(DB_KEY, JSON.stringify(appData));
let currentUser = JSON.parse(localStorage.getItem(USER_KEY)) || null;

// --- global handlers ---
function checkAuth() {
    const publicPages = ['index.html', '', 'login.html'];
    const currentPage = window.location.pathname.split("/").pop();
    
    if (!currentUser && !publicPages.includes(currentPage)) {
        window.location.href = 'login.html'; 
    }
    if (currentUser && (currentPage === 'login.html' || currentPage === 'index.html')) {
        window.location.href = 'post_report.html'; 
    }
}

// --- Page Specific Logic ---
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    
    // 1. LOGIN
    const loginForm = document.getElementById('login-form');
    if(loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            localStorage.setItem(USER_KEY, JSON.stringify({ name: 'Authorized User', email: email }));
            showToast('Otorisasi Jaringan Berhasil.', 'success');
            setTimeout(() => { window.location.href = 'post_report.html'; }, 1000);
        });
    }

    // 2. LOGOUT
    const btnLogout = document.getElementById('btn-logout');
    if(btnLogout) {
        btnLogout.addEventListener('click', () => {
            localStorage.removeItem(USER_KEY);
            window.location.href = 'index.html';
        });
    }

    // 3. FEED RENDERER
    const feedContainer = document.getElementById('feed-container');
    if(feedContainer) renderFeed(feedContainer, appData);

    const searchInput = document.getElementById('search-feed');
    if(searchInput) {
        searchInput.addEventListener('input', (e) => renderFeed(feedContainer, appData, e.target.value));
    }

    // 4. REPORT FORM
    setupReportForm();
});

// --- Feed Engine ---
function renderFeed(container, data, searchQuery = '') {
    container.innerHTML = '';
    
    let filteredData = data.filter(post => {
        const query = searchQuery.toLowerCase();
        return post.desc.toLowerCase().includes(query) || post.location.toLowerCase().includes(query) || post.petName.toLowerCase().includes(query);
    });

    if (filteredData.length === 0) {
        container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Tidak ada laporan di radar area ini.</div>`;
        return;
    }

    filteredData.sort((a,b) => b.id - a.id).forEach(post => {
        const isFound = post.type === 'found';
        const card = document.createElement('div');
        card.className = 'feed-card';
        card.innerHTML = `
            <div class="card-img-box">
                <div class="card-badge ${isFound ? 'badge-found' : 'badge-lost'}">${isFound ? 'FOUND' : 'LOST'}</div>
                <img src="${post.image}" alt="Pet Image" loading="lazy">
            </div>
            <div class="card-body">
                <h3>${post.petName !== 'Unknown' ? post.petName : `Seekor ${post.species}`}</h3>
                <div class="card-meta"><i class="fa-solid fa-map-marker-alt"></i> ${post.location}</div>
            </div>
            <div class="card-footer">
                <div class="author-box">
                    <img src="${post.authorImg}" class="author-img" alt="Author">
                    <div class="author-text"><span class="author-name">${post.author}</span><br><span style="font-size:0.75rem; color:var(--text-muted);">${post.date}</span></div>
                </div>
                <button class="btn-like action-btn ${post.isLiked ? 'liked' : ''}" onclick="toggleLike(${post.id}, event)">
                    <i class="${post.isLiked ? 'fa-solid' : 'fa-regular'} fa-heart"></i>
                </button>
            </div>
        `;
        card.addEventListener('click', (e) => { if(!e.target.closest('.action-btn')) openModal(post); });
        container.appendChild(card);
    });
}

window.toggleLike = function(postId, event) {
    event.stopPropagation();
    const post = appData.find(p => p.id === postId);
    if(post) {
        post.isLiked = !post.isLiked;
        post.likes += post.isLiked ? 1 : -1;
        localStorage.setItem(DB_KEY, JSON.stringify(appData));
        const feedContainer = document.getElementById('feed-container');
        renderFeed(feedContainer, appData, document.getElementById('search-feed').value);
    }
}

// --- Modal Logic ---
const modal = document.getElementById('post-modal');
const modalContent = document.getElementById('modal-body-content');

function openModal(post) {
    if(!modal) return;
    const isFound = post.type === 'found';
    modalContent.innerHTML = `
        <img src="${post.image}" style="width:100%; height:300px; object-fit:cover; border-radius: 12px; margin-bottom: 20px;">
        <h2 style="font-size: 1.8rem; color: var(--secondary); font-weight:800;">${post.petName !== 'Unknown' ? post.petName : `Seekor ${post.species}`}</h2>
        <span class="card-badge ${isFound?'badge-found':'badge-lost'}" style="position:relative; top:0; left:0; display:inline-block; margin: 10px 0;">${isFound ? 'FOUND' : 'LOST'}</span>
        <div style="color: var(--primary); font-weight: 600; margin-bottom: 20px;"><i class="fa-solid fa-map-location-dot"></i> ${post.location}</div>
        <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 25px;">${post.desc}</p>
        <a href="messages.html" class="btn btn-primary btn-block" style="border-radius:12px; padding:15px;">Hubungi Pelapor di Jaringan</a>
    `;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

const closeModal = document.getElementById('close-modal');
if(closeModal) closeModal.addEventListener('click', () => { modal.classList.remove('show'); document.body.style.overflow = ''; });
if(modal) modal.addEventListener('click', (e) => { if(e.target === modal) closeModal.click(); });


// --- Form & Upload ---
function setupReportForm() {
    const uploadZone = document.getElementById('upload-zone');
    const imageInput = document.getElementById('image-input');
    const imagePreview = document.getElementById('image-preview');
    const btnRemove = document.getElementById('btn-remove-image');
    const placeholder = document.getElementById('upload-placeholder');
    let currentImageBase64 = null;

    if(!uploadZone) return;

    uploadZone.addEventListener('click', (e) => { if(e.target !== btnRemove) imageInput.click(); });

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file && file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = e => {
                currentImageBase64 = e.target.result;
                imagePreview.src = currentImageBase64;
                imagePreview.classList.remove('hidden');
                btnRemove.classList.remove('hidden');
                placeholder.classList.add('hidden');
                uploadZone.style.padding = '0';
            };
            reader.readAsDataURL(file);
        }
    });

    if(btnRemove) {
        btnRemove.addEventListener('click', () => {
            currentImageBase64 = null; imageInput.value = ''; imagePreview.src = '';
            imagePreview.classList.add('hidden'); btnRemove.classList.add('hidden');
            placeholder.classList.remove('hidden'); uploadZone.style.padding = '40px';
        });
    }

    const form = document.getElementById('form-create-report');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!currentImageBase64) return showToast('Harap unggah gambar hewan.', 'error');

        const newReport = {
            id: Date.now(),
            type: document.querySelector('input[name="reportType"]:checked').value,
            author: currentUser.name, authorImg: 'https://i.pravatar.cc/150?img=68',
            petName: document.getElementById('r-name').value || 'Unknown',
            species: document.getElementById('r-species').value,
            location: document.getElementById('r-location').value,
            date: 'Baru saja', desc: document.getElementById('r-desc').value,
            image: currentImageBase64, likes: 0, isLiked: false
        };

        appData.push(newReport);
        try { localStorage.setItem(DB_KEY, JSON.stringify(appData)); window.location.href = 'post_report.html'; } 
        catch (err) { alert('Ukuran foto terlalu besar. Gunakan kompresi.'); }
    });
}

// --- Toast ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000);

   // ==========================================
// 10. CHAT ENGINE (Messages)
// ==========================================
const CHAT_DB_KEY = 'petfounds_chat_db_v1';

// Data awal Chat (Dummy)
const initialChats = [
    {
        id: 1,
        contactName: 'Alex Turner',
        avatar: 'https://i.pravatar.cc/150?img=11',
        messages: [
            { sender: 'them', text: 'Halo, saya melihat laporan Anda tentang kucing Persia. Apakah masih hilang?', time: '10:30' },
            { sender: 'me', text: 'Iya benar mas, apakah Anda melihatnya?', time: '10:35' }
        ]
    },
    {
        id: 2,
        contactName: 'Sarena Design',
        avatar: 'https://i.pravatar.cc/150?img=33',
        messages: [
            { sender: 'them', text: 'Anjingnya aman di ruko saya ya. Silakan diambil kalau ada waktu.', time: 'Kemarin' }
        ]
    }
];

let chatData = JSON.parse(localStorage.getItem(CHAT_DB_KEY));
if (!chatData) {
    chatData = initialChats;
    localStorage.setItem(CHAT_DB_KEY, JSON.stringify(chatData));
}

let activeChatId = chatData[0].id; // Default buka chat pertama

function renderChatUI() {
    const contactList = document.getElementById('chat-contact-list');
    const messagesArea = document.getElementById('chat-messages-area');
    const activeName = document.getElementById('active-chat-name');
    const activeAvatar = document.getElementById('active-chat-avatar');
    
    if(!contactList || !messagesArea) return; // Cegah error kalau bukan di halaman messages.html

    // 1. Render Sidebar Kontak
    contactList.innerHTML = '';
    chatData.forEach(chat => {
        const lastMsg = chat.messages[chat.messages.length - 1];
        const isActive = chat.id === activeChatId ? 'active' : '';
        
        contactList.innerHTML += `
            <div class="contact-item ${isActive}" onclick="setActiveChat(${chat.id})">
                <img src="${chat.avatar}" class="contact-avatar">
                <div class="contact-info">
                    <div class="contact-name">
                        ${chat.contactName} <span class="contact-time">${lastMsg.time}</span>
                    </div>
                    <div class="contact-preview">${lastMsg.text}</div>
                </div>
            </div>
        `;
    });

    // 2. Render Ruang Obrolan Aktif
    const activeChat = chatData.find(c => c.id === activeChatId);
    activeName.innerText = activeChat.contactName;
    activeAvatar.src = activeChat.avatar;
    
    messagesArea.innerHTML = `<div class="chat-date-divider"><span>Hari Ini</span></div>`;
    
    activeChat.messages.forEach(msg => {
        const msgClass = msg.sender === 'me' ? 'msg-sent' : 'msg-received';
        messagesArea.innerHTML += `
            <div class="msg-bubble ${msgClass}">
                ${msg.text}
                <span class="msg-time">${msg.time}</span>
            </div>
        `;
    });

    // Auto Scroll ke pesan paling bawah
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

window.setActiveChat = function(id) {
    activeChatId = id;
    renderChatUI();
}

// 3. Form Kirim Pesan & Bot Auto-Reply
const chatForm = document.getElementById('chat-form');
if (chatForm) {
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text) return;

        // Ambil waktu saat ini (HH:MM)
        const now = new Date();
        const timeStr = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;

        // Tambah pesan kita
        const activeChat = chatData.find(c => c.id === activeChatId);
        activeChat.messages.push({ sender: 'me', text: text, time: timeStr });
        
        // Simpan & Render
        localStorage.setItem(CHAT_DB_KEY, JSON.stringify(chatData));
        input.value = '';
        renderChatUI();

        // --- SIMULASI BOT MEMBALAS PESAN (AUTO-REPLY) ---
        setTimeout(() => {
            // Animasi ngetik (opsional bisa dibuat elemen HTML, disini langsung kirim balasan aja)
            const replies = [
                "Baik, nanti saya kabari lagi ya.",
                "Oke, saya akan bantu pantau area sini.",
                "Oh begitu. Semoga cepat ketemu ya peliharaannya!",
                "Terima kasih infonya.",
                "Siap, ditunggu kabarnya."
            ];
            const randomReply = replies[Math.floor(Math.random() * replies.length)];
            
            const replyTime = new Date();
            const replyTimeStr = `${replyTime.getHours().toString().padStart(2, '0')}:${replyTime.getMinutes().toString().padStart(2, '0')}`;
            
            activeChat.messages.push({ sender: 'them', text: randomReply, time: replyTimeStr });
            localStorage.setItem(CHAT_DB_KEY, JSON.stringify(chatData));
            renderChatUI();
            
            // Notifikasi kalau chat masuk (kalau lagi nggak buka layar penuh)
            // showToast(`Pesan baru dari ${activeChat.contactName}`, 'info');
        }, 1500); // Bot balas dalam 1.5 detik
    });
}

// Panggil fungsi render jika berada di halaman chat
document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('chat-contact-list')) {
        renderChatUI();
    }
});

// ==========================================
// 11. PROFILE ENGINE (Render Aktivitas)
// ==========================================
function renderProfileActivity() {
    const activityContainer = document.getElementById('profile-activity-list');
    const statReports = document.getElementById('stat-reports');
    
    // Cegah error jika fungsi dipanggil di luar halaman profil
    if (!activityContainer) return;

    // Filter laporan yang HANYA dibuat oleh currentUser
    const myReports = appData.filter(post => post.author === currentUser.name);

    // Update Angka Statistik
    if(statReports) statReports.innerText = myReports.length;

    activityContainer.innerHTML = '';

    if (myReports.length === 0) {
        activityContainer.innerHTML = `
            <div class="text-center text-muted" style="padding: 30px;">
                <i class="fa-solid fa-folder-open text-2xl mb-2"></i>
                <p>Anda belum membuat laporan apapun.</p>
            </div>
        `;
        return;
    }

    // Tampilkan 3 aktivitas terbaru saja
    myReports.sort((a, b) => b.id - a.id).slice(0, 3).forEach(post => {
        const isFound = post.type === 'found';
        const badgeClass = isFound ? 'text-success' : 'text-danger';
        const statusText = isFound ? 'Menemukan Hewan' : 'Kehilangan Hewan';
        const title = post.petName !== 'Unknown' ? post.petName : `Seekor ${post.species}`;

        activityContainer.innerHTML += `
            <div class="activity-card" onclick="openModalFromProfile(${post.id})">
                <img src="${post.image}" alt="Pet">
                <div class="activity-info">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="${badgeClass} font-bold text-sm" style="font-size:0.75rem;"><i class="fa-solid fa-circle" style="font-size:0.4rem; vertical-align:middle; margin-right:4px;"></i> ${statusText}</span>
                        <span class="text-muted" style="font-size:0.75rem;">${post.date}</span>
                    </div>
                    <h4>${title}</h4>
                    <div class="activity-meta">
                        <span><i class="fa-solid fa-map-pin"></i> ${post.location}</span>
                        <span><i class="fa-solid fa-heart text-danger"></i> ${post.likes}</span>
                    </div>
                </div>
            </div>
        `;
    });
}

// Fungsi pembantu agar card di profil bisa diklik dan membuka modal
window.openModalFromProfile = function(postId) {
    const post = appData.find(p => p.id === postId);
    if(post && typeof openModal === 'function') {
        openModal(post);
    } else {
        // Jika sedang di halaman profil, arahkan ke feed
        window.location.href = 'post_report.html';
    }
}

// Panggil fungsi render profil jika berada di halaman profil
document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('profile-activity-list')) {
        // Beri sedikit delay agar terlihat seperti memuat dari server
        setTimeout(renderProfileActivity, 500); 
    }
});

   }
