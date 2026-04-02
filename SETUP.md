# PetFounds - Setup Guide

Panduan lengkap untuk setup dan menjalankan aplikasi PetFounds dengan PHP.

## ⚙️ Prerequisites

Sebelum memulai, pastikan sudah install:

- **PHP 7.4+** - Download dari [php.net](https://www.php.net)
- **MySQL 5.7+** - Download dari [mysql.com](https://www.mysql.com)
- **Web Server** - Apache (built-in di XAMPP) atau Nginx
- **Text Editor** - VS Code, Sublime Text, dll

### Rekomendasi Setup Environment

**Option 1: XAMPP (Recommended for Beginners)**
- Download dari: https://www.apachefriends.org/download.html
- Includes: Apache, PHP, MySQL, phpMyAdmin
- Extract dan jalankan sesuai OS Anda

**Option 2: WAMP (Windows)**
- Download dari: https://www.wampserver.com/
- Includes: Apache, PHP, MySQL

**Option 3: LAMP (Linux)**
```bash
sudo apt-get install apache2 php mysql-server
```

**Option 4: Manual Setup**
- Install PHP, MySQL, dan Apache separately
- Setup environment variables

---

## 📊 Step-by-Step Installation

### Step 1: Download & Extract Project

```bash
# Navigate ke web root folder
cd /path/to/webserver/htdocs

# Extract project (jika masih zip)
unzip petfounds.zip

# Navigate ke project
cd lost-and-found-pet
```

**Common Web Root Paths:**
- XAMPP: `C:\xampp\htdocs` (Windows) atau `/opt/lampp/htdocs` (Linux)
- WAMP: `C:\wamp64\www` (Windows)
- Standard: `/var/www/html` (Linux)

### Step 2: Setup Database

#### 2a. Via phpMyAdmin (Easy)

1. Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`
2. Click **"New"** atau **"Create database"**
3. Nama database: `petfounds_db`
4. Click **"Create"**
5. Click database `petfounds_db` yang baru dibuat
6. Click tab **"Import"**
7. Click **"Choose File"** dan pilih `database/schema.sql`
8. Click **"Go"** untuk import

#### 2b. Via MySQL Command Line

```bash
# Connect ke MySQL
mysql -u root -p

# Copy-paste isi database/schema.sql
# Atau run file langsung:
mysql -u root -p < /path/to/database/schema.sql
```

#### 2c. Verify Database Created

```bash
mysql -u root -p
use petfounds_db;
SHOW TABLES;
```

Seharusnya muncul 6 tables:
- users
- pet_reports
- likes
- messages
- chat_contacts

### Step 3: Configure Database Connection

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');      // Usually localhost
define('DB_USER', 'root');           // MySQL username (usually 'root')
define('DB_PASS', '');               // MySQL password (empty for local)
define('DB_NAME', 'petfounds_db');   // Database name
```

**Important:** Update jika menggunakan password atau host berbeda

### Step 4: Create Upload Directories

```bash
# Windows (Command Prompt)
mkdir public\uploads
mkdir public\uploads\avatars

# Linux/Mac (Terminal)
mkdir -p public/uploads
mkdir -p public/uploads/avatars
chmod 755 public/uploads
chmod 755 public/uploads/avatars
```

### Step 5: Start Web Server

**XAMPP:**
- Buka XAMPP Control Panel
- Click **Start** untuk Apache dan MySQL

**WAMP:**
- Click icon WAMP di system tray
- Pilih Apache dan MySQL Online

**Manual/Command Line:**
```bash
# PHP Built-in Server (simple testing)
php -S localhost:8000 -t public

# Apache
sudo systemctl start apache2
```

### Step 6: Access Application

Buka browser dan akses:

```
http://localhost/petfounds/pages/login.php
```

Atau jika using PHP built-in server:
```
http://localhost:8000/pages/login.php
```

---

## 🔐 Test Login

Gunakan credentials default untuk testing:

**Email:** admin@petfounds.pro  
**Password:** admin123

Atau buat akun baru melalui halaman **Register**.

---

## 📋 Konfigurasi Lanjutan (Optional)

### Untuk Production Environment

#### 1. Update Error Reporting

Edit `config/database.php`:

```php
error_reporting(0);              // Hide errors
ini_set('display_errors', 0);    // Don't display
ini_set('log_errors', 1);        // Log to file
```

#### 2. Setup HTTPS

Buat SSL certificate dan update .htaccess:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### 3. Setup Email Notifications

Add email configuration di `config/database.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

#### 4. Database Backup

Setup automatic backup:

```bash
# Linux cron job (run daily at 2 AM)
0 2 * * * mysqldump -u root -p[password] petfounds_db > /backup/petfounds_$(date +\%Y\%m\%d).sql
```

---

## 🧪 Testing Fitur

### Test Login Flow

1. **Register** - Klik "Daftar" dan buat akun baru
2. **Login** - Gunakan email dan password yang didaftar
3. **Dashboard** - Check feed, search, like reports
4. **Create Report** - Buat laporan hewan baru
5. **Messaging** - Chat dengan user lain
6. **Profile** - Update profil dan lihat laporan Anda
7. **Logout** - Logout dari aplikasi

### Test Data

Database sudah terisi dummy data:
- 3 users (admin, Alex Turner, Sarena Design)
- 2 pet reports (1 lost, 1 found)
- Dapat langsung explore setelah login

---

## 🔧 Troubleshooting

### Problem: Database Connection Failed

**Error:** `Koneksi database gagal: Unknown database 'petfounds_db'`

**Solutions:**
```bash
# Check MySQL running
mysql -u root -p

# Verify database exists
SHOW DATABASES;

# If not exists, create it
CREATE DATABASE petfounds_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import schema
mysql -u root -p petfounds_db < database/schema.sql
```

### Problem: Cannot Upload Images

**Error:** `Gagal upload file`

**Solutions:**
```bash
# Check folder exists
ls -la public/uploads/

# Fix permissions
chmod 755 public/uploads
chmod 755 public/uploads/avatars

# Check PHP upload settings in php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Problem: Session/Login Issues

**Error:** `Cannot modify header information` atau Session tidak terbaca

**Solutions:**
- Pastikan tidak ada output (echo, spaces) sebelum `<?php`
- Remove BOM dari file PHP (Editor Settings)
- Clear browser cache dan cookies
- Restart PHP/Apache

### Problem: White Page / No Content

**Error:** Halaman kosong putih

**Solutions:**
```bash
# Check error logs
# Apache: /var/log/apache2/error.log
# XAMPP: htdocs/php_errors.log

# Enable errors temporarily (debug only)
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Problem: CSS/JS Not Loading

**Error:** Halaman tampil tapi styling tidak ada

**Solutions:**
- Check file paths di source code
- Verify relative paths correct
- Clear browser cache (Ctrl+Shift+R)
- Check web server serving static files

---

## 📚 File Structure Reference

```
lost-and-found-pet/
├── config/database.php           ← DATABASE CONFIG (EDIT INI)
├── lib/
│   ├── auth.php                  ← Authentication logic
│   └── functions.php             ← Helper functions
├── api/                          ← API ENDPOINTS
│   ├── login.php                 ← POST /api/login.php
│   ├── register.php              ← POST /api/register.php
│   ├── logout.php                ← GET /api/logout.php
│   ├── reports.php               ← CRUD pet reports
│   ├── likes.php                 ← Like/unlike
│   ├── messages.php              ← Chat messages
│   └── profile.php               ← User profile
├── pages/                        ← USER PAGES
│   ├── login.php                 ← Entry point
│   ├── register.php
│   ├── post_report.php           ← Dashboard/Feed
│   ├── messages.php              ← Chat page
│   └── profile.php               ← User profile
├── public/
│   └── uploads/                  ← USER UPLOADS (MAKE WRITABLE)
├── css/                          ← Styles
├── js/                          ← Browser scripts
└── database/                     ← SQL FILES
    └── schema.sql                ← DATABASE SCHEMA
```

---

## 🚀 Next Steps

Setelah berhasil setup:

1. **Explore Code** - Review structure dan understand flow
2. **Test Features** - Try login, create report, messaging
3. **Customize** - Modify design, warna, text sesuai kebutuhan
4. **Add Features** - Integrate additional features
5. **Deploy** - Upload ke production server

---

## 📞 Support Commands

**Check PHP Version:**
```bash
php -v
```

**Check MySQL Version:**
```bash
mysql --version
```

**Check Apache Status:**
```bash
# Linux
sudo systemctl status apache2

# Mac
sudo apachectl status
```

**Restart Services:**
```bash
# Linux
sudo systemctl restart apache2 mysql

# Or via XAMPP
xampp restart
```

---

## ✅ Verification Checklist

Sebelum deploy ke production, pastikan:

- [ ] Database created dan schema imported
- [ ] `config/database.php` configured dengan benar
- [ ] Folder `public/uploads` writable
- [ ] Can login dengan credentials: admin@petfounds.pro / admin123
- [ ] Can create pet report
- [ ] Can see reports di feed
- [ ] Like button berfungsi
- [ ] Chat dapat mengirim pesan
- [ ] Profile update berfungsi
- [ ] Logout berfungsi dengan benar

---

**Happy Coding! 🎉**

Untuk pertanyaan lebih lanjut, refer ke README.md atau API documentation.
