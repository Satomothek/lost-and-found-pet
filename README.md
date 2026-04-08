# Lost and Found Pet - Web Application

Platform untuk melaporkan dan mencari hewan peliharaan yang hilang atau ditemukan.

## 🚀 Fitur Utama

✅ **Autentikasi Pengguna** - Login, Register, Logout  
✅ **Database MySQL** - Menyimpan data users, laporan, messages  
✅ **Feed Interaktif** - Lihat, cari, dan filter laporan hewan  
✅ **Like System** - Berikan apresiasi ke laporan yang relevan  
✅ **Chat/Messaging** - Berkomunikasi langsung dengan pelapor  
✅ **User Profile** - Lihat profil dan laporan dari user lain  
✅ **Responsive Design** - Bekerja optimal di desktop, tablet, mobile  
✅ **Upload Avatar** - Unggah foto profil pengguna  

## 📋 Requirements

- **PHP** >= 7.4
- **MySQL** >= 5.7
- **Web Server** (Apache/Nginx) - Direkomendasikan XAMPP untuk Windows
- **Browser** Modern (Chrome, Firefox, Safari, Edge)

## 🔧 Instalasi

### 1. Setup Web Server

```bash
# Copy project ke folder web server
# Untuk XAMPP di Windows:
# Salin folder lost-and-found-pet ke C:/xampp/htdocs/
```

### 2. Buat Database

```bash
# Buka phpMyAdmin (http://localhost/phpmyadmin)
# Buat database baru dengan nama 'petfounds_db'
# Import file database/schema.sql
```

Atau jalankan query dari file `database/schema.sql` di MySQL.

### 3. Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');      // Host database
define('DB_USER', 'root');           // Username database
define('DB_PASS', '');               // Password database
define('DB_NAME', 'lost_and_found_pet');   // Nama database
```

### 4. Setup Folder Uploads

```bash
# Folder untuk upload sudah ada di public/uploads/avatars
# Pastikan permissions write untuk web server
```

### 5. Akses Aplikasi

Buka browser dan akses:

```
http://localhost/lost-and-found-pet/index.php
```

Untuk login: `http://localhost/lost-and-found-pet/pages/login.php`

## 🛠️ Penggunaan

1. **Register** akun baru atau **Login** jika sudah ada
2. **Post Report** untuk melaporkan hewan hilang/ditemukan
3. **Browse Feed** untuk melihat laporan dari user lain
4. **Like** laporan yang menarik
5. **Message** pemilik laporan untuk komunikasi
6. **Update Profile** dan upload avatar

## 🤝 Support

Untuk bantuan lebih lanjut, hubungi team development.
