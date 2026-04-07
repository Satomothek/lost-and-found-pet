# PetFounds API Documentation

Dokumentasi lengkap semua API endpoints yang tersedia dalam aplikasi PetFounds.

## Base URL

```
http://localhost/petfounds/api/
```

---

## 🔐 Authentication Endpoints

### 1. Login User

**Endpoint:** `POST /api/login.php`

**Description:** Login dengan email dan password

**Request Body:**
```json
{
    "email": "admin@petfounds.pro",
    "password": "admin123"
}
```

**Response Success:**
```json
{
    "status": "success",
    "message": "Login berhasil",
    "data": {
        "id": 1,
        "name": "Admin PetFounds",
        "email": "admin@petfounds.pro",
        "avatar": "https://i.pravatar.cc/150?img=68"
    }
}
```

**Response Error:**
```json
{
    "status": "error",
    "message": "Email atau password salah"
}
```

---

### 2. Register User

**Endpoint:** `POST /api/register.php`

**Description:** Membuat akun baru

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirm": "password123"
}
```

**Response Success:**
```json
{
    "status": "success",
    "message": "Registrasi berhasil, silakan login"
}
```

**Response Error:**
```json
{
    "status": "error",
    "message": "Email sudah terdaftar"
}
```

---

### 3. Logout User

**Endpoint:** `GET /api/logout.php`

**Description:** Logout dan destroy session

**Response:**
```json
{
    "status": "success",
    "message": "Logout berhasil"
}
```

---

## 📋 Pet Reports Endpoints

### 1. Get All Reports

**Endpoint:** `GET /api/reports.php`

**Description:** Ambil semua pet reports dengan filter dan pagination

**Query Parameters:**
- `search` (optional) - Cari berdasarkan nama, lokasi, atau deskripsi
- `type` (optional) - Filter by type: 'lost' atau 'found'
- `page` (optional) - Halaman (default: 1)

**Example:**
```
GET /api/reports.php?search=kucing&type=lost&page=1
```

**Response:**
```json
{
    "status": "success",
    "message": "Data laporan hewan berhasil diambil",
    "data": {
        "reports": [
            {
                "id": 1,
                "type": "lost",
                "author": "Alex Turner",
                "authorImg": "https://i.pravatar.cc/150?img=11",
                "petName": "Milo",
                "species": "Kucing",
                "location": "Borobudur, Magelang",
                "date": "2 jam yang lalu",
                "desc": "Hilang kucing persia...",
                "image": "https://images.unsplash.com/...",
                "likes": 24,
                "isLiked": false
            }
        ],
        "page": 1
    }
}
```

---

### 2. Create Report

**Endpoint:** `POST /api/reports.php?action=create`

**Description:** Membuat laporan hewan baru

**Request Type:** `multipart/form-data`

**Form Data:**
```
type: "lost" atau "found" (required)
pet_name: "Milo" (optional)
species: "Kucing" (required)
location: "Borobudur, Magelang" (required)
description: "Hilang kucing persia..." (required)
image: <file> (optional)
```

**Response Success:**
```json
{
    "status": "success",
    "message": "Laporan berhasil dibuat",
    "data": {
        "report_id": 5
    }
}
```

---

### 3. Update Report

**Endpoint:** `PUT /api/reports.php?action=update`

**Description:** Update laporan yang sudah ada (hanya user yang membuat)

**Form Data:**
```
report_id: 5 (required)
pet_name: "Milo"
species: "Kucing Persia"
location: "Borobudur, Magelang"
description: "Deskripsi baru..."
```

**Response:**
```json
{
    "status": "success",
    "message": "Laporan berhasil diperbarui"
}
```

---

### 4. Delete Report

**Endpoint:** `DELETE /api/reports.php?action=delete&id=5`

**Description:** Hapus/resolve laporan (hanya user yang membuat)

**Response:**
```json
{
    "status": "success",
    "message": "Laporan berhasil dihapus"
}
```

---

## ❤️ Likes Endpoints

### Toggle Like

**Endpoint:** `POST /api/likes.php`

**Description:** Like atau unlike sebuah report

**Request Body:**
```json
{
    "report_id": 1,
    "action": "toggle"
}
```

**Actions:**
- `toggle` - Toggle status like (default)
- `like` - Force like
- `unlike` - Force unlike

**Response:**
```json
{
    "status": "success",
    "message": "Like ditambahkan"
}
```

---

## 💬 Messages Endpoints

### 1. Get Chat Contacts

**Endpoint:** `GET /api/messages.php?action=contacts`

**Description:** Ambil daftar chat contacts (termasuk yang diblokir)

**Response:**
```json
{
    "status": "success",
    "message": "Chat contacts berhasil diambil",
    "data": {
        "contacts": [
            {
                "contact_id": 2,
                "contactName": "Alex Turner",
                "avatar": "https://i.pravatar.cc/150?img=11",
                "text": "Apakah kucing Anda sudah ketemu?",
                "time": "14:35",
                "isBlocked": false
            }
        ]
    }
}
```

---

### 2. Get Chat History

**Endpoint:** `GET /api/messages.php?action=history&contact_id=2`

**Description:** Ambil history chat dengan user tertentu

**Query Parameters:**
- `contact_id` (required) - User ID yang ingin dilihat history-nya

**Response:**
```json
{
    "status": "success",
    "message": "Chat history berhasil diambil",
    "data": {
        "messages": [
            {
                "id": 1,
                "sender": "them",
                "text": "Apakah kucing Anda sudah ketemu?",
                "time": "14:35"
            },
            {
                "id": 2,
                "sender": "me",
                "text": "Belum, masih mencari nih",
                "time": "14:36"
            }
        ]
    }
}
```

---

### 3. Block User

**Endpoint:** `POST /api/messages.php?action=block`

**Description:** Blokir pengguna (mencegah mereka mengirim pesan, history tetap tersimpan)

**Request Body:**
```json
{
    "blocked_id": 2
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Pengguna berhasil diblokir"
}
```

---

### 4. Unblock User

**Endpoint:** `POST /api/messages.php?action=unblock`

**Description:** Buka blokir pengguna

**Request Body:**
```json
{
    "blocked_id": 2
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Pengguna berhasil dibuka blokirnya"
}
```

---

### 5. Send Message

**Endpoint:** `POST /api/messages.php?action=send`

**Description:** Mengirim pesan ke user

**Request Body:**
```json
{
    "receiver_id": 2,
    "message": "Halo, apakah kucing Anda sudah ketemu?"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Pesan berhasil dikirim",
    "data": {
        "message_id": 5,
        "sender": "me",
        "text": "Halo, apakah kucing Anda sudah ketemu?",
        "time": "14:37"
    }
}
```

---

## 👤 Profile Endpoints

### 1. Get Current User Profile

**Endpoint:** `GET /api/profile.php`

**Description:** Ambil profil user yang sedang login

**Response:**
```json
{
    "status": "success",
    "message": "Profil berhasil diambil",
    "data": {
        "id": 1,
        "name": "Admin PetFounds",
        "email": "admin@petfounds.pro",
        "avatar": "https://i.pravatar.cc/150?img=68",
        "bio": "Admin aplikasi PetFounds",
        "phone": "081234567890",
        "joined": "15 Maret",
        "reports_count": 5,
        "recent_reports": [
            {
                "id": 1,
                "type": "lost",
                "petName": "Milo",
                "species": "Kucing",
                "description": "Hilang kucing persia...",
                "image": "https://images.unsplash.com/...",
                "date": "2 jam yang lalu"
            }
        ]
    }
}
```

---

### 2. Get User Profile by ID

**Endpoint:** `GET /api/profile.php?action=user&id=2`

**Description:** Ambil profil user tertentu

**Query Parameters:**
- `id` (required) - User ID

**Response:**
```json
{
    "status": "success",
    "message": "Data user berhasil diambil",
    "data": {
        "id": 2,
        "name": "Alex Turner",
        "email": "alex@example.com",
        "avatar": "https://i.pravatar.cc/150?img=11",
        "bio": "Pecinta kucing dan anjing",
        "phone": "081234567890",
        "joined": "10 Januari"
    }
}
```

---

### 3. Update Profile

**Endpoint:** `POST /api/profile.php?action=update`

**Description:** Update profil user (hanya user yang login)

**Request Type:** `multipart/form-data`

**Form Data:**
```
name: "Admin PetFounds" (required)
bio: "Admin aplikasi PetFounds"
phone: "081234567890"
```

**Response:**
```json
{
    "status": "success",
    "message": "Profil berhasil diperbarui",
    "data": {
        "name": "Admin PetFounds",
        "bio": "Admin aplikasi PetFounds",
        "phone": "081234567890"
    }
}
```

---

### 4. Update Avatar

**Endpoint:** `POST /api/profile.php?action=avatar`

**Description:** Update avatar/foto profil

**Request Type:** `multipart/form-data`

**Form Data:**
```
avatar: <image-file> (required)
```

**Response:**
```json
{
    "status": "success",
    "message": "Avatar berhasil diperbarui",
    "data": {
        "avatar_url": "public/uploads/avatars/img_xxx.jpg"
    }
}
```

---

### 5. Get User Reports

**Endpoint:** `GET /api/profile.php?action=reports&id=2&page=1`

**Description:** Ambil semua laporan dari user tertentu

**Query Parameters:**
- `id` (optional) - User ID (default: current user)
- `page` (optional) - Halaman untuk pagination (default: 1)

**Response:**
```json
{
    "status": "success",
    "message": "Laporan user berhasil diambil",
    "data": {
        "reports": [
            {
                "id": 1,
                "type": "lost",
                "petName": "Milo",
                "species": "Kucing",
                "location": "Borobudur, Magelang",
                "description": "Hilang kucing persia...",
                "image": "https://images.unsplash.com/...",
                "date": "2 jam yang lalu"
            }
        ],
        "page": 1
    }
}
```

---

## 🔄 Error Responses

Semua endpoint dapat mengembalikan error response dengan format:

```json
{
    "status": "error",
    "message": "Deskripsi error",
    "data": null
}
```

**Common Error Messages:**
- `Method not allowed` - HTTP method tidak sesuai
- `Semua field wajib diisi` - Ada field yang kosong
- `Email sudah terdaftar` - Email sudah ada di database
- `Email atau password salah` - Credential tidak valid
- `Laporan tidak ditemukan` - Report ID tidak valid
- `Anda tidak memiliki akses` - Authorization failed
- `Gagal upload file` - File upload error

---

## 🔐 Authentication Notes

Semua endpoint (kecuali login dan register) memerlukan user sudah login dengan session yang valid.

**Session Management:**
- Login/Register membuat session
- Session disimpan di server (tidak di client)
- Logout menghapus session
- Session otomatis expire setelah period tertentu (default: 1440 menit)

---

## 📊 Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 500 | Server Error |

---

## 🧪 Testing dengan cURL

```bash
# Login
curl -X POST http://localhost/petfounds/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@petfounds.pro","password":"admin123"}'

# Get Reports
curl -X GET "http://localhost/petfounds/api/reports.php?search=kucing"

# Create Report (require authentication)
curl -X POST http://localhost/petfounds/api/reports.php?action=create \
  -F "type=lost" \
  -F "species=Kucing" \
  -F "location=Jakarta" \
  -F "description=Kucing hilang" \
  -F "image=@/path/to/image.jpg"
```

---

## 💡 Best Practices

1. **Always validate input** - Jangan percaya data dari client
2. **Use HTTPS** - Terutama untuk production
3. **Rate limiting** - Implement untuk prevent abuse
4. **Error handling** - Jangan expose sensitive info
5. **Logging** - Log semua request penting untuk debugging
6. **CORS** - Setup proper CORS headers jika needed
7. **Pagination** - Always paginate untuk large datasets

---

**Last Updated:** March 2026  
**API Version:** 1.0.0
