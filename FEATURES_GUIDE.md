# PetFounds - Feature & Implementation Guide

Dokumentasi lengkap semua fitur dan cara implementasinya di aplikasi PetFounds.

---

## 📑 Daftar Isi

1. [Fitur Utama](#fitur-utama)
2. [User Authentication](#user-authentication)
3. [Pet Reports Management](#pet-reports-management)
4. [Messaging System](#messaging-system)
5. [User Profile](#user-profile)
6. [Like & Engagement](#like--engagement)
7. [Advanced Features](#advanced-features-roadmap)

---

## 🎯 Fitur Utama

### 1. User Authentication

**Tujuan:** Memastikan hanya user terdaftar yang bisa akses aplikasi

**Fitur:**
- ✅ Register akun baru dengan validasi email
- ✅ Login dengan email & password
- ✅ Session management otomatis
- ✅ Logout & session termination
- ✅ Password hashing dengan bcrypt
- ✅ Auto-login setelah registrasi

**File:** `lib/auth.php`, `api/login.php`, `api/register.php`, `pages/login.php`

**Implementasi:**
```php
// lib/auth.php
function login($conn, $email, $password) {
    $email = sanitizeInput($email);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        return ['status' => 'error', 'message' => 'Email tidak valid'];
    }
    
    $user = fetchOne($conn, "SELECT * FROM users WHERE email = ?", "s", $email);
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        return ['status' => 'error', 'message' => 'Email atau password salah'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    
    return ['status' => 'success', 'user' => $user];
}
```

### 2. Pet Reports Management

**Tujuan:** User bisa posting laporan hewan hilang/ditemukan

**Fitur:**
- ✅ Create laporan baru dengan kategori (lost/found)
- ✅ Upload image otomatis
- ✅ Deskripsi detail (lokasi, spesies, nama)
- ✅ View semua reports dalam feed
- ✅ Search reports by nama/lokasi/deskripsi
- ✅ Edit laporan sendiri
- ✅ Delete/resolve laporan
- ✅ Pagination untuk feed

**File:** `api/reports.php`, `pages/post_report.php`

**Implementasi:**
```php
// api/reports.php - Create
$type = sanitizeInput($_POST['type']);
$species = sanitizeInput($_POST['species']);
$location = sanitizeInput($_POST['location']);
$description = sanitizeInput($_POST['description']);

// Validate
if (!$type || !$species || !$location) {
    errorResponse("Semua field wajib diisi");
}

// Handle image upload
$image_url = null;
if (!empty($_FILES['image'])) {
    $upload = uploadImage($_FILES['image']);
    if ($upload['success']) {
        $image_url = $upload['url'];
    }
}

// Insert to database
$query = "INSERT INTO pet_reports 
          (user_id, type, pet_name, species, location, description, image_url, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
          
executeQuery($conn, $query, "sissss", 
    $_SESSION['user_id'], $type, $pet_name, $species, $location, $description, $image_url);
```

### 3. Messaging System

**Tujuan:** Users bisa komunikasi satu sama lain untuk tanya/follow up

**Fitur:**
- ✅ Get daftar chat contacts
- ✅ Load chat history dengan contact
- ✅ Send pesan real-time
- ✅ Message timestamp & formatting
- ✅ Read/unread indicator
- ✅ Contact preview (last message)

**File:** `api/messages.php`, `pages/messages.php`

**Implementasi:**
```php
// api/messages.php - Get contacts
$query = "SELECT DISTINCT 
          CASE 
              WHEN sender_id = ? THEN receiver_id 
              ELSE sender_id 
          END as contact_id,
          (SELECT name FROM users WHERE id = contact_id) as name,
          (SELECT message FROM messages 
           WHERE (sender_id = ? AND receiver_id = contact_id) OR 
                 (sender_id = contact_id AND receiver_id = ?)
           ORDER BY created_at DESC LIMIT 1) as last_msg
          FROM messages
          WHERE sender_id = ? OR receiver_id = ?
          ORDER BY created_at DESC";

$contacts = fetchAll($conn, $query, "iiiii", 
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id']);
```

### 4. User Profile

**Tujuan:** User bisa manage profil dan lihat activity

**Fitur:**
- ✅ View profile info (name, email, bio, phone)
- ✅ Upload/change avatar
- ✅ Edit profil (name, bio, phone)
- ✅ View user statistics (total reports, lost vs found)
- ✅ See recent activity/reports
- ✅ View other user profiles

**File:** `api/profile.php`, `pages/profile.php`

**Implementasi:**
```php
// api/profile.php - Get profile
$user_id = $_SESSION['user_id'];

$user = fetchOne($conn, "SELECT * FROM users WHERE id = ?", "i", $user_id);

// Get statistics
$stats = fetchOne($conn, 
    "SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN type='lost' THEN 1 ELSE 0 END) as lost_count,
        SUM(CASE WHEN type='found' THEN 1 ELSE 0 END) as found_count
    FROM pet_reports WHERE user_id = ?", "i", $user_id);

// Get recent reports
$recent = fetchAll($conn, 
    "SELECT * FROM pet_reports WHERE user_id = ? 
     ORDER BY created_at DESC LIMIT 3", "i", $user_id);

return [
    'user' => $user,
    'stats' => $stats,
    'recent_reports' => $recent
];
```

### 5. Like & Engagement

**Tujuan:** User bisa engage dengan reports via like

**Fitur:**
- ✅ Toggle like/unlike report
- ✅ Like counter per report
- ✅ Visual indicator (liked/not liked)
- ✅ Prevent duplicate likes

**File:** `api/likes.php`

**Implementasi:**
```php
// api/likes.php - Toggle like
$report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
$user_id = $_SESSION['user_id'];

// Check if already liked
$existing = fetchOne($conn, 
    "SELECT id FROM likes WHERE user_id = ? AND report_id = ?", 
    "ii", $user_id, $report_id);

if ($existing) {
    // Unlike
    executeQuery($conn, 
        "DELETE FROM likes WHERE user_id = ? AND report_id = ?", 
        "ii", $user_id, $report_id);
    $message = "Like dihapus";
} else {
    // Like
    executeQuery($conn, 
        "INSERT INTO likes (user_id, report_id, created_at) VALUES (?, ?, NOW())", 
        "ii", $user_id, $report_id);
    $message = "Like ditambahkan";
}

// Update like count in reports table
$count = fetchOne($conn, 
    "SELECT COUNT(*) as total FROM likes WHERE report_id = ?", 
    "i", $report_id);
    
executeQuery($conn, 
    "UPDATE pet_reports SET likes_count = ? WHERE id = ?", 
    "ii", $count['total'], $report_id);

successResponse($message);
```

---

## 🔑 User Authentication

### Registration Flow

**Step 1: User fills registration form**
```html
<form id="registerForm">
    <input type="text" name="name" placeholder="Nama Lengkap" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="password_confirm" placeholder="Konfirmasi Password" required>
    <button type="submit">Register</button>
</form>
```

**Step 2: JavaScript validates & sends**
```javascript
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Client validation
    if (data.password !== data.password_confirm) {
        showToast('Password tidak cocok', 'error');
        return;
    }
    
    // API call
    const response = await fetch('/api/register.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    if (result.status === 'success') {
        showToast('Registrasi berhasil, silakan login', 'success');
        setTimeout(() => window.location.href = '/pages/login.php', 1500);
    }
});
```

**Step 3: PHP processes registration**
```php
// api/register.php
$name = sanitizeInput($_POST['name']);
$email = sanitizeInput($_POST['email']);
$password = $_POST['password'];
$pass_confirm = $_POST['password_confirm'];

// Validations
if (strlen($password) < 6) {
    errorResponse("Password minimal 6 karakter");
}

// Check duplicate
$existing = fetchOne($conn, 
    "SELECT id FROM users WHERE email = ?", "s", $email);
if ($existing) {
    errorResponse("Email sudah terdaftar");
}

// Hash password
$hashed = hashPassword($password);

// Insert user
executeQuery($conn, 
    "INSERT INTO users (name, email, password, created_at) 
     VALUES (?, ?, ?, NOW())", 
    "sss", $name, $email, $hashed);

// Auto login
$new_user = fetchOne($conn, 
    "SELECT * FROM users WHERE email = ?", "s", $email);
$_SESSION['user_id'] = $new_user['id'];

successResponse("Registrasi berhasil");
```

### Login Flow

1. User enters email & password → JavaScript sends to API
2. PHP validates credentials & checks database
3. If valid → create session & return user data
4. JavaScript redirects to dashboard
5. On next page load → PHP checks session & allows access

---

## 📋 Pet Reports Management

### Create Report Flow

**Step 1: User navigates to "Buat Laporan"**
```javascript
// pages/post_report.php
function showCreateReportModal() {
    const modal = document.getElementById('createReportModal');
    modal.style.display = 'flex';
    
    // Setup form submission
    document.getElementById('createReportForm').addEventListener('submit', 
        submitCreateReport);
}

async function submitCreateReport(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Validate
    if (!formData.get('type') || !formData.get('species')) {
        showToast('Semua field wajib diisi', 'error');
        return;
    }
    
    // Upload
    const response = await fetch('/api/reports.php?action=create', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if (result.status === 'success') {
        showToast('Laporan berhasil dibuat', 'success');
        loadFeed();  // Refresh feed
    }
}
```

**Step 2: Image Upload & Validation**
```php
// lib/functions.php - uploadImage()
function uploadImage($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024;  // 5MB
    
    // Validations
    if (!in_array($file['type'], $allowed)) {
        return ['success' => false, 'error' => 'Format file tidak didukung'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File terlalu besar'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . "." . $extension;
    $upload_path = __DIR__ . "/../public/uploads/" . $new_filename;
    
    // Create directory if not exists
    if (!is_dir(dirname($upload_path))) {
        mkdir(dirname($upload_path), 0777, true);
    }
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true,
            'url' => '/petfounds/public/uploads/' . $new_filename
        ];
    }
    
    return ['success' => false, 'error' => 'Gagal upload file'];
}
```

**Step 3: Database Insertion**
```sql
INSERT INTO pet_reports 
(user_id, type, pet_name, species, location, description, image_url, likes_count, status, created_at)
VALUES
(1, 'lost', 'Milo', 'Kucing Persia', 'Jakarta Selatan', 'Hilang kucing putih', 'public/uploads/abc123.jpg', 0, 'active', NOW());
```

### Search & Filter

**Frontend search (with debounce):**
```javascript
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    
    searchInput.addEventListener('input', debounce(async (e) => {
        const query = e.target.value;
        const response = await fetch(`/api/reports.php?search=${query}`);
        const result = await response.json();
        renderFeed(result.data.reports);
    }, 300));
}

function debounce(func, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), delay);
    };
}
```

**Backend search:**
```php
// api/reports.php
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($search) {
    $query = "SELECT * FROM pet_reports 
              WHERE (pet_name LIKE ? 
                 OR location LIKE ? 
                 OR description LIKE ?)
                AND status = 'active'
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";
    
    $search_term = "%$search%";
    $reports = fetchAll($conn, $query, "sssii", 
        $search_term, $search_term, $search_term, $limit, $offset);
} else {
    // Get all
    $query = "SELECT * FROM pet_reports 
              WHERE status = 'active'
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";
    
    $reports = fetchAll($conn, $query, "ii", $limit, $offset);
}
```

---

## 💬 Messaging System

### Chat Interface Structure

```html
<div class="chat-container">
    <!-- Left Panel: Contacts -->
    <div class="chat-sidebar">
        <div class="chat-header">
            <h3>Pesan</h3>
            <button onclick="startNewChat()">+</button>
        </div>
        <div id="chatContacts" class="contacts-list">
            <!-- Loaded via JavaScript -->
        </div>
    </div>
    
    <!-- Right Panel: Messages -->
    <div class="chat-panel">
        <div class="chat-header" id="chatHeader">
            <!-- Shows selected contact info -->
        </div>
        <div id="messagesList" class="messages-list">
            <!-- Messages loaded here -->
        </div>
        <form id="chatForm" onsubmit="sendMessage(event)">
            <input type="text" id="messageInput" placeholder="Ketik pesan..." required>
            <button type="submit">Kirim</button>
        </form>
    </div>
</div>
```

### Message Flow

**Load Contacts:**
```javascript
async function loadChatContacts() {
    const response = await apiCall('/api/messages.php?action=contacts');
    
    if (response.data && response.data.contacts) {
        renderContacts(response.data.contacts);
    }
}

function renderContacts(contacts) {
    const container = document.getElementById('chatContacts');
    container.innerHTML = '';
    
    contacts.forEach(contact => {
        const el = document.createElement('div');
        el.className = 'contact-item';
        el.innerHTML = `
            <img src="${contact.avatar}" alt="${contact.contactName}">
            <div class="contact-info">
                <p class="contact-name">${contact.contactName}</p>
                <p class="contact-preview">${contact.text.substring(0, 40)}...</p>
            </div>
            <span class="contact-time">${contact.time}</span>
        `;
        el.onclick = () => selectChat(contact.contact_id, el);
        container.appendChild(el);
    });
}
```

**Send Message:**
```javascript
async function sendMessage(e) {
    e.preventDefault();
    
    const message = document.getElementById('messageInput').value;
    const receiver_id = activeChatId;
    
    const response = await apiCall('/api/messages.php?action=send', {
        method: 'POST',
        body: JSON.stringify({receiver_id, message})
    });
    
    if (response.status === 'success') {
        document.getElementById('messageInput').value = '';
        // Add message to UI immediately
        addMessageToChat({
            sender: 'me',
            text: message,
            time: new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})
        });
    }
}

async function addMessageToChat(msg) {
    const msgEl = document.createElement('div');
    msgEl.className = 'message msg-' + msg.sender;
    msgEl.innerHTML = `
        <div class="message-text">${msg.text}</div>
        <div class="message-time">${msg.time}</div>
    `;
    document.getElementById('messagesList').appendChild(msgEl);
    
    // Auto scroll to bottom
    document.getElementById('messagesList').scrollTop = 
        document.getElementById('messagesList').scrollHeight;
}
```

---

## 👤 User Profile

### Profile Display

```html
<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-card">
            <div class="avatar-wrapper">
                <img id="profileAvatar" src="avatar.jpg" alt="Avatar">
                <label for="avatarInput" class="edit-avatar">📷</label>
                <input type="file" id="avatarInput" accept="image/*" hidden>
            </div>
            
            <div class="profile-info">
                <h2 id="profileName">User Name</h2>
                <p id="profileEmail">user@email.com</p>
                <p id="profileBio">User bio/description</p>
                
                <button onclick="openEditModal()">Edit Profile</button>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="profile-tabs">
        <button class="tab-btn active" onclick="switchTab('activity')">Activity</button>
        <button class="tab-btn" onclick="switchTab('stats')">Stats</button>
    </div>
    
    <!-- Tab Content -->
    <div id="activityTab" class="tab-content active">
        <!-- Recent reports loaded here -->
    </div>
    
    <div id="statsTab" class="tab-content">
        <!-- Statistics displayed here -->
    </div>
</div>
```

### Load Profile Data

```javascript
async function loadProfile() {
    const response = await apiCall('/api/profile.php');
    const user = response.data;
    
    // Populate UI
    document.getElementById('profileName').textContent = user.name;
    document.getElementById('profileEmail').textContent = user.email;
    document.getElementById('profileBio').textContent = user.bio || '';
    document.getElementById('profileAvatar').src = user.avatar;
    
    // Load activity
    loadProfileActivity(user.recent_reports);
    
    // Load stats
    loadProfileStats(user.stats);
}

function renderProfileStats(stats) {
    const html = `
        <div class="stat-item">
            <h3>${stats.total_reports}</h3>
            <p>Total Laporan</p>
        </div>
        <div class="stat-item">
            <h3>${stats.lost_count}</h3>
            <p>Hilang</p>
        </div>
        <div class="stat-item">
            <h3>${stats.found_count}</h3>
            <p>Ditemukan</p>
        </div>
    `;
    
    document.getElementById('statsTab').innerHTML = html;
}
```

---

## ❤️ Like & Engagement System

### Like Toggle

**Frontend:**
```javascript
async function toggleLike(reportId, likeBtn) {
    const isLiked = likeBtn.classList.contains('liked');
    
    const response = await apiCall('/api/likes.php', {
        method: 'POST',
        body: JSON.stringify({
            report_id: reportId,
            action: isLiked ? 'unlike' : 'like'
        })
    });
    
    if (response.status === 'success') {
        likeBtn.classList.toggle('liked');
        
        // Update like count
        const counter = likeBtn.querySelector('.like-count');
        let count = parseInt(counter.textContent);
        counter.textContent = isLiked ? --count : ++count;
    }
}
```

### Like Count Display

```html
<div class="report-card">
    <!-- Report content -->
    
    <div class="report-footer">
        <button class="like-btn" onclick="toggleLike(${reportId}, this)">
            <span class="like-icon">❤️</span>
            <span class="like-count">${report.likes}</span>
        </button>
    </div>
</div>
```

---

## 🚀 Advanced Features Roadmap

### Phase 2 Features (Recommended Next)

**1. Create Report Form Page**
- [ ] Dedicated `/pages/create_report.php`
- [ ] Multi-step form (Type → Details → Image → Preview)
- [ ] Pet species dropdown/autocomplete
- [ ] Location picker (maps integration)
- [ ] Image preview before upload
- [ ] Auto-save draft

**2. Found Pets Listing Page**
- [ ] `/pages/found_pets.php` - dedicated page for found reports
- [ ] Filter by species, date range, location
- [ ] Featured/highlighted reports
- [ ] "Might be yours?" matching system

**3. Report Detail Modal**
- [ ] Full image gallery (multiple images per report)
- [ ] Contact owner button
- [ ] Report metadata (date posted, location details)
- [ ] Share options (social media, direct link)
- [ ] Similar reports suggestion

**4. Advanced Search**
- [ ] Filter by date range
- [ ] Filter by location radius (using latitude/longitude)
- [ ] Pet type/species filtering
- [ ] Active vs resolved status
- [ ] Saved searches/favorites

### Phase 3 Features (Enhancement)

**5. Notifications**
- [ ] In-app notifications for new messages
- [ ] Notification bell with counter
- [ ] Email notifications (optional)
- [ ] Browser push notifications

**6. User Matching**
- [ ] AI matching lost/found reports
- [ ] Suggest similar reports to user
- [ ] Automatic email when potential match found
- [ ] Match confidence score

**7. User Reputation**
- [ ] Star rating system
- [ ] User reviews/testimonials
- [ ] Community badges (helper, consistent reporter, etc.)
- [ ] Trust indicators

**8. Report Management**
- [ ] Report status tracking (lost → found → reunited)
- [ ] Happy reunited stories/photos
- [ ] Report archiving
- [ ] Statistics dashboard

### Phase 4 Features (Advanced)

**9. Admin Dashboard**
- [ ] User management
- [ ] Report moderation/verification
- [ ] Statistics analytics
- [ ] System health monitoring
- [ ] User support panel

**10. API Authentication**
- [ ] OAuth 2.0 implementation
- [ ] API key management
- [ ] Rate limiting per user/API key
- [ ] Usage statistics

**11. Mobile App**
- [ ] React Native or Flutter app
- [ ] Native push notifications
- [ ] Camera integration
- [ ] Location services

**12. Map Integration**
- [ ] Google Maps/Leaflet integration
- [ ] Pin reports on map
- [ ] Search by map region
- [ ] Route to location

---

## 📊 Feature Implementation Benefits

| Feature | User Benefit | Technical Complexity |
|---------|--------------|---------------------|
| Create Report Form | Better UX for uploading | Low |
| Advanced Search | Find reports easier | Medium |
| Notifications | Stay updated | Medium |
| User Matching | Increase reunification | High |
| Admin Dashboard | Better moderation | High |
| Mobile App | On-the-go access | Very High |

---

## ✨ Estimated Timeline

Based on feature scope:

- **Phase 1 (Current):** Complete ✅
- **Phase 2:** 2-3 weeks (Create form, Found page, Detail modal)
- **Phase 3:** 3-4 weeks (Notifications, Matching, Rating)
- **Phase 4:** 4-6 weeks (Admin, OAuth, Mobile prep)

---

**Documentation Version:** 1.0  
**Last Updated:** March 2026
