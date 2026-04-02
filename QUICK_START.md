# 🚀 PetFounds - Quick Start Guide

Panduan cepat untuk mulai menggunakan PetFounds dengan 5 menit setup!

---

## ⚡ 5-Minute Quick Start

### 1. Prerequisites

Pastikan sudah install:
- [ ] XAMPP atau WAMP atau web server lokal
- [ ] PHP 7.4 atau lebih tinggi
- [ ] MySQL 5.7 atau lebih tinggi
- [ ] Browser modern (Chrome, Firefox, Safari, Edge)

### 2. Setup (3 menit)

```bash
# 1. Extract project ke folder web server
# Windows: C:\xampp\htdocs\
# Mac: /Applications/MAMP/htdocs/
# Linux: /var/www/html/

# Folder structure harus:
# htdocs/petfounds/
#   ├── api/
#   ├── config/
#   ├── css/
#   ├── pages/
#   └── ... (files lainnya)

# 2. Buka browser
# http://localhost/phpmyadmin/

# 3. Create database
CREATE DATABASE petfounds_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Import schema
# Pilih petfounds_db → Import tab
# Upload: database/schema.sql
# Klik Go

# 5. Akses aplikasi
# http://localhost/petfounds/
```

### 3. Login (1 menit)

**Test Credentials:**
```
Email: admin@petfounds.pro
Password: admin123
```

**Atau buat akun baru:**
1. Klik "Belum punya akun?"
2. Isi form registrasi
3. Auto-login & redirected ke dashboard

### 4. Start Using! (1 menit)

- 📱 Explore feed dengan reports
- 💌 Kirim pesan ke users
- 📝 Create pet report
- 👤 Setup profile

---

## 📚 Documentation Files

| File | Purpose | Waktu Baca |
|------|---------|-----------|
| **README.md** | Overview & features | 5 menit |
| **SETUP.md** | Detailed setup guide | 10 menit |
| **API_DOCUMENTATION.md** | API endpoints reference | 15 menit |
| **DEPLOYMENT_GUIDE.md** | Production deployment | 20 menit |
| **FEATURES_GUIDE.md** | Feature explanations | 15 menit |

---

## 🎯 Common Tasks

### Task 1: Register New User

```
1. Go to: http://localhost/petfounds/pages/login.php
2. Click: "Belum punya akun? Daftar di sini"
3. Fill:
   - Name: Your Name
   - Email: your@email.com
   - Password: SecurePass123
   - Confirm: SecurePass123
4. Click: Register
5. Auto login & see dashboard
```

### Task 2: Create Pet Report

```
1. Login with your account
2. Click "Buat Laporan" in sidebar
3. Form appears:
   - Type: Select "Lost" atau "Found"
   - Species: Masukkan jenis hewan
   - Location: Lokasi hilang/ditemukan
   - Description: Detail deskripsi
   - Image: Upload foto (optional)
4. Click "Submit"
5. Report appears in feed seketika
```

### Task 3: Message Someone

```
1. Click "Pesan" in sidebar
2. Select contact dari left panel
3. Or click "+" untuk start new chat
4. Type message in input box
5. Click "Kirim"
6. Message appears dengan timestamp
```

### Task 4: Edit Profile

```
1. Click "Profil" in sidebar
2. Click "Edit Profile" button
3. Update info:
   - Name
   - Phone
   - Bio
4. Click "Save"
5. Or click avatar untuk update picture
```

### Task 5: Like a Report

```
1. In feed, find report
2. Click heart icon ❤️
3. Heart turns red = liked
4. Like count increases
5. Click again to unlike
```

---

## 🔧 Troubleshooting

### "Can't connect to database"

**Solution:**
```
1. Check MySQL running (XAMPP Control Panel)
2. Verify config/database.php credentials:
   - DB_HOST: localhost
   - DB_USER: root
   - DB_PASS: (empty for XAMPP)
   - DB_NAME: petfounds_db
3. Restart Apache & MySQL
4. Try again
```

### "404 Not Found"

**Solution:**
```
1. Check folder path: C:/xampp/htdocs/petfounds/
2. Verify URL: http://localhost/petfounds/
3. Ensure .htaccess exists in project root
4. Enable mod_rewrite: XAMPP → Apache → Modules
5. Restart Apache
```

### "Nothing shows up after login"

**Solution:**
```
1. Open browser DevTools (F12)
2. Check Console for errors
3. Check Network tab for failed API calls
4. Verify database has data:
   - phpMyAdmin → petfounds_db → pet_reports
   - Should show 2 dummy reports
5. If empty, reimport schema
```

### "Can't upload image"

**Solution:**
```
1. Check folder exists: public/uploads/
2. Set permissions: chmod 777 public/uploads/
3. Check file size < 5MB
4. Check file type: JPG, PNG, GIF, WebP only
5. Check disk space available
```

---

## 🔐 Security Notes

**IMPORTANT for Production:**
- [ ] Change default credentials
- [ ] Use strong passwords (12+ chars, mixed case, numbers, symbols)
- [ ] Enable HTTPS/SSL
- [ ] Keep software updated (PHP, MySQL)
- [ ] Use .env file for sensitive data
- [ ] Setup regular backups
- [ ] Use unique database user (not root)

---

## 📂 Project Structure

```
petfounds/
├── api/                          # API endpoints
│   ├── login.php                # User login
│   ├── register.php             # User registration
│   ├── logout.php               # User logout
│   ├── reports.php              # Pet reports CRUD
│   ├── likes.php                # Like/unlike system
│   ├── messages.php             # Chat messaging
│   └── profile.php              # User profile management
│
├── config/
│   └── database.php             # Database configuration
│
├── lib/
│   ├── auth.php                 # Authentication functions
│   └── functions.php            # Helper functions
│
├── pages/
│   ├── login.php                # Login page
│   ├── register.php             # Registration page
│   ├── post_report.php          # Dashboard/Feed
│   ├── messages.php             # Chat page
│   └── profile.php              # User profile
│
├── css/
│   └── style.css                # Main stylesheet
│
├── js/
│   └── functions.js             # Client-side utilities
│
├── database/
│   └── schema.sql               # Database schema & dummy data
│
├── public/
│   └── uploads/                 # Uploaded images directory
│
├── index.php                    # Entry point (redirect logic)
├── .htaccess                    # Server configuration
├── README.md                    # Project overview
├── SETUP.md                     # Detailed setup guide
├── DEPLOYMENT_GUIDE.md          # Production deployment
├── FEATURES_GUIDE.md            # Feature documentation
├── API_DOCUMENTATION.md         # API reference
└── QUICK_START.md              # This file
```

---

## 🌐 Environment Setup by OS

### Windows (XAMPP)

```batch
# 1. Download XAMPP: https://www.apachefriends.org/
# 2. Install to C:\xampp\
# 3. Open XAMPP Control Panel
# 4. Start Apache
# 5. Start MySQL
# 6. Extract PetFounds to C:\xampp\htdocs\petfounds\
# 7. Open http://localhost/petfounds/
```

### Mac (MAMP)

```bash
# 1. Download MAMP: https://www.mamp.info/
# 2. Install to Applications
# 3. Open MAMP app
# 4. Click "Start Servers"
# 5. Extract PetFounds to /Applications/MAMP/htdocs/petfounds/
# 6. Open http://localhost:8888/petfounds/
```

### Linux (Manual Install)

```bash
# 1. Install PHP
sudo apt-get install php7.4 php7.4-mysql php7.4-curl

# 2. Install MySQL
sudo apt-get install mysql-server

# 3. Install Apache
sudo apt-get install apache2 libapache2-mod-php7.4

# 4. Enable mod_rewrite
sudo a2enmod rewrite

# 5. Extract project to /var/www/html/petfounds/

# 6. Set permissions
sudo chown -R www-data:www-data /var/www/html/petfounds/

# 7. Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql
```

---

## 🧪 Testing Checklist

After setup, test these features:

**Authentication**
- [ ] Register new account
- [ ] Login with new account
- [ ] Logout properly
- [ ] Auto-redirect when not logged in

**Pet Reports**
- [ ] View feed shows reports
- [ ] Create new report
- [ ] Search reports works
- [ ] Image upload works

**Messaging**
- [ ] Chat contacts load
- [ ] Send message
- [ ] Receive message
- [ ] Chat history displays

**Profile**
- [ ] View own profile
- [ ] Edit profile info
- [ ] Upload avatar
- [ ] See recent reports

**Engagement**
- [ ] Like report
- [ ] Unlike report
- [ ] Like count updates

---

## 📞 Need Help?

### Check These First:

1. **Database Issues**
   - Open http://localhost/phpmyadmin/
   - Verify `petfounds_db` exists
   - Check `pet_reports` has data

2. **PHP Issues**
   - Open DevTools (F12)
   - Check Console for errors
   - Check Network for failed requests

3. **File/Folder Issues**
   - Verify all files extracted
   - Check folder permissions
   - Ensure uploads folder exists

4. **Service Issues**
   - Check Apache running
   - Check MySQL running
   - Restart services if needed

### Documentation:

- 📖 Full setup: See **SETUP.md**
- 🔌 API reference: See **API_DOCUMENTATION.md**
- 🚀 Production: See **DEPLOYMENT_GUIDE.md**
- ✨ Features: See **FEATURES_GUIDE.md**

---

## ⚡ Command Line Cheat Sheet

```bash
# Start everything (Linux/Mac)
sudo systemctl start apache2
sudo systemctl start mysql

# Check services running
sudo systemctl status apache2
sudo systemctl status mysql

# MySQL command line
mysql -u root -p
# Password: (press enter for XAMPP)

# PHP built-in server (alternative to Apache)
cd /path/to/petfounds
php -S localhost:8000

# File permissions
chmod -R 755 /var/www/html/petfounds/
chmod -R 777 /var/www/html/petfounds/public/uploads/

# Check PHP version
php -v

# Test PHP setup
php -r "phpinfo();"
```

---

## 🎓 Learning Resources

**PHP Basics:**
- https://www.php.net/manual/en/
- https://www.w3schools.com/php/

**MySQL Basics:**
- https://dev.mysql.com/doc/
- https://www.w3schools.com/sql/

**Web Development:**
- https://developer.mozilla.org/en-US/docs/Web/
- https://javascript.info/

---

## 📊 Quick Stats

| Metric | Value |
|--------|-------|
| Setup Time | ~5 minutes |
| Total Files | 22 |
| Database Tables | 6 |
| API Endpoints | 7 |
| PHP Pages | 5 |
| Helper Functions | 19 |
| Lines of Code | ~3,500 |

---

## ✅ Success Indicators

You're all set when you can:

1. ✅ Access login page at `http://localhost/petfounds/`
2. ✅ Register new account successfully
3. ✅ Login and see dashboard with reports
4. ✅ Create a new pet report
5. ✅ Send message to another user
6. ✅ Update your profile
7. ✅ Like/unlike reports
8. ✅ Search for specific reports
9. ✅ See your statistics
10. ✅ No errors in browser console (F12)

If all above work → **Congratulations! PetFounds is running! 🎉**

---

## 🚀 Next Steps

1. Review **SETUP.md** for detailed setup
2. Read **API_DOCUMENTATION.md** to understand API
3. Check **FEATURES_GUIDE.md** for feature details
4. Plan **DEPLOYMENT_GUIDE.md** for production

---

**Version:** 1.0  
**Last Updated:** March 2026  
**Happy Coding! 🐕🐈**
