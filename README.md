# PetFounds - Pet Finder Network (PHP Version)

Platform profesional untuk mencari dan melaporkan hewan peliharaan yang hilang atau ditemukan.

## 🚀 Fitur Utama

✅ **Autentikasi Pengguna** - Login, Register, Logout  
✅ **Database MySQL** - Menyimpan data users, laporan, messages  
✅ **Feed Interaktif** - Lihat, cari, dan filter laporan hewan  
✅ **Like System** - Berikan apresiasi ke laporan yang relevan  
✅ **Chat/Messaging** - Berkomunikasi langsung dengan pelapor  
✅ **User Profile** - Lihat profil dan laporan dari user lain  
✅ **Responsive Design** - Bekerja optimal di desktop, tablet, mobile  

## 📋 Requirements

- **PHP** >= 7.4
- **MySQL** >= 5.7
- **Web Server** (Apache/Nginx)
- **Browser** Modern (Chrome, Firefox, Safari, Edge)

## 🔧 Instalasi

### 1. Setup Web Server

```bash
# Copy project ke folder web server
# Misal untuk XAMPP:
cp -r lost-and-found-pet C:/xampp/htdocs/petfounds
```

### 2. Buat Database

```bash
# Buka phpMyAdmin atau MySQL Command Line
# Import file database
SOURCE path/to/database/schema.sql;
```

Atau jalankan query manual dari file `database/schema.sql`

### 3. Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');      // Host database
define('DB_USER', 'root');           // Username database
define('DB_PASS', '');               // Password database
define('DB_NAME', 'petfounds_db');   // Nama database
```

### 4. Setup Folder Uploads

```bash
# Buat folder untuk upload files
mkdir -p public/uploads
mkdir -p public/uploads/avatars

# Set permissions (Linux/Mac)
chmod 755 public/uploads
chmod 755 public/uploads/avatars
```

### 5. Akses Aplikasi

Buka browser dan akses:

```
http://localhost/petfounds/pages/login.php
```

## 📁 Struktur Folder

```
lost-and-found-pet/
├── config/
│   └── database.php          # Konfigurasi database
├── lib/
│   ├── auth.php              # Authentication logic
│   └── functions.php         # Helper functions
├── api/
│   ├── login.php             # Login endpoint
│   ├── register.php          # Register endpoint
│   ├── logout.php            # Logout endpoint
│   ├── reports.php           # CRUD pet reports
│   ├── likes.php             # Like/unlike reports
│   ├── messages.php          # Chat messages
│   └── profile.php           # User profile
├── pages/
│   ├── login.php             # Login page
│   ├── register.php          # Register page
│   ├── post_report.php       # Feed/Dashboard
│   ├── messages.php          # Chat page
│   └── profile.php           # Profile page
├── css/
│   └── style.css             # Main styles
├── js/
│   └── functions.js          # Utility functions
├── public/
│   ├── uploads/              # User uploaded files
│   └── uploads/avatars/      # User avatars
├── database/
│   └── schema.sql            # Database schema
└── Pet Web App/              # Original HTML files (backup)
```

## 🔐 Test Credentials

Akun default untuk testing:

**Email:** admin@petfounds.pro  
**Password:** admin123

Atau buat akun baru melalui halaman register.

## 🌐 API Endpoints

### Authentication
```
POST /api/login.php          - Login user
POST /api/register.php       - Register user baru
POST /api/logout.php         - Logout user
```

### Pet Reports
```
GET /api/reports.php         - Get semua reports
POST /api/reports.php?action=create    - Create report baru
PUT /api/reports.php?action=update     - Update report
DELETE /api/reports.php?action=delete  - Delete/resolve report
```

### Likes
```
POST /api/likes.php          - Toggle like report
```

### Messages
```
GET /api/messages.php?action=contacts      - Get chat contacts
GET /api/messages.php?action=history       - Get chat history
POST /api/messages.php?action=send         - Send message
```

### Profile
```
GET /api/profile.php         - Get current user profile
GET /api/profile.php?action=user&id=X     - Get user by ID
POST /api/profile.php?action=update       - Update profile
POST /api/profile.php?action=avatar       - Update avatar
```

## 🔄 Deployment

### Production Checklist

- [ ] Update `config/database.php` dengan credentials production
- [ ] Set `error_reporting(E_ALL)` menjadi `error_reporting(0)` di production
- [ ] Setup HTTPS/SSL Certificate
- [ ] Set proper file permissions (600 untuk secrets, 755 untuk folders)
- [ ] Use strong database passwords
- [ ] Enable password hashing yang lebih kuat
- [ ] Setup backup database regular
- [ ] Monitor server logs

### Deployment ke Server

```bash
# Contoh deployment ke shared hosting via SFTP/FTP
# 1. Compress project
zip -r petfounds.zip lost-and-found-pet/

# 2. Upload ke server
# Gunakan FileZilla atau CLI

# 3. Extract di server
unzip petfounds.zip

# 4. Konfigurasi database
# Edit config/database.php dengan production credentials

# 5. Import schema database
# Via phpMyAdmin atau command line
```

## 🐛 Troubleshooting

### Database Connection Error

```
Error: Koneksi database gagal
```

**Solusi:**
- Pastikan MySQL server running
- Cek username dan password di `config/database.php`
- Cek database name sudah sesuai

### Session Error

```
Warning: Cannot modify header information
```

**Solusi:**
- Pastikan tidak ada output sebelum `session_start()`
- Hapus BOM dari file PHP

### Upload File Error

```
Error: Gagal upload file
```

**Solusi:**
- Cek folder `public/uploads/` writeable
- Cek max_upload_size di php.ini
- Cek file format adalah image (jpg, png, gif, webp)

### Permission Denied

```
Error: Permission denied
```

**Solusi:**
```bash
# Linux/Mac
chmod 755 -R public/uploads
chmod 644 public/uploads/*.*
```

## 📚 Documentation

- [PHP Manual](https://www.php.net/manual/en/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [HTML/CSS Reference](https://developer.mozilla.org/en-US/docs/Web/HTML)
- [JavaScript Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

## 📝 License

Project ini adalah educational purpose. Silakan modify sesuai kebutuhan.

## 🤝 Support

Untuk bantuan lebih lanjut, hubungi team development.

---

**Last Updated:** March 2026  
**Version:** 1.0.0
