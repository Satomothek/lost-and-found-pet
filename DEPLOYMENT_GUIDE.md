# Deployment & Implementation Guide

Panduan lengkap untuk deployment aplikasi PetFounds ke production dan checklist implementasi.

---

## 📋 Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Local Development Setup](#local-development-setup)
3. [Testing Guide](#testing-guide)
4. [Production Deployment](#production-deployment)
5. [Performance Optimization](#performance-optimization)
6. [Security Hardening](#security-hardening)
7. [Troubleshooting](#troubleshooting)

---

## ✅ Pre-Deployment Checklist

### Code Quality

- [ ] All PHP files follow consistent naming convention
- [ ] Database queries use prepared statements (no SQL injection)
- [ ] All user inputs are sanitized
- [ ] Error messages don't expose sensitive info
- [ ] No hardcoded passwords or API keys
- [ ] Code commented for complex logic
- [ ] No console.log() statements in production JS
- [ ] CSS classes follow BEM naming convention

### Security

- [ ] Password hashing using bcrypt (verified in auth.php)
- [ ] Session configuration secure (cookie flags set)
- [ ] HTTPS ready (no mixed content)
- [ ] CSRF tokens implemented (for forms)
- [ ] XSS protection (output encoding)
- [ ] Rate limiting configured
- [ ] File upload validation in place
- [ ] .htaccess configured properly

### Database

- [ ] All tables have primary keys
- [ ] Foreign key relationships defined
- [ ] Indexes created on frequently queried columns
- [ ] Backup strategy documented
- [ ] Database user has minimal required permissions
- [ ] No sensitive data stored in plain text

### Testing

- [ ] Login/Register flow tested
- [ ] All CRUD operations verified
- [ ] Search functionality works correctly
- [ ] Like/unlike system tested
- [ ] Chat messaging tested
- [ ] Profile updates tested
- [ ] File uploads validated
- [ ] Error cases handled gracefully

### Documentation

- [ ] API documentation complete (API_DOCUMENTATION.md)
- [ ] Setup guide created (SETUP.md)
- [ ] README with features and requirements
- [ ] Inline comments for complex functions
- [ ] Database schema documented
- [ ] Configuration file documented

---

## 🚀 Local Development Setup

### Step 1: Install Prerequisites

**Windows (XAMPP):**
```bash
# Download from https://www.apachefriends.org/
# Install XAMPP with Apache, MySQL, PHP 7.4+

# Set XAMPP to autostart (optional)
cd C:\xampp
xampp-control.exe
```

**Mac (MAMP):**
```bash
# Download from https://www.mamp.info/
# Install and configure PHP version
```

**Linux (Manual):**
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install apache2 mysql-server php7.4 php7.4-mysql php7.4-curl php7.4-gd
```

### Step 2: Project Setup

```bash
# Clone/Extract project to htdocs
cd C:\xampp\htdocs  # Windows
# or: /Applications/MAMP/htdocs  # Mac

# If extracted:
# Extract to: petfounds folder

# Directory structure should be:
# htdocs/
#   ├── petfounds/
#   │   ├── api/
#   │   ├── config/
#   │   ├── css/
#   │   ├── database/
#   │   ├── js/
#   │   ├── lib/
#   │   ├── pages/
#   │   ├── public/
#   │   │   └── uploads/
#   │   ├── index.php
#   │   ├── README.md
#   │   └── SETUP.md
```

### Step 3: Database Setup

**Method 1: phpMyAdmin GUI**

```
1. Open http://localhost/phpmyadmin
2. Click "New" to create database
3. Name: petfounds_db
4. Collation: utf8mb4_unicode_ci
5. Click Create
6. Select petfounds_db database
7. Click "Import" tab
8. Upload database/schema.sql
9. Click Go to import
```

**Method 2: MySQL CLI**

```bash
# Open MySQL command line
mysql -u root -p

# In MySQL:
CREATE DATABASE petfounds_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petfounds_db;
SOURCE C:/xampp/htdocs/petfounds/database/schema.sql;
```

### Step 4: Configuration

Edit `config/database.php`:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty password for XAMPP
define('DB_NAME', 'petfounds_db');
```

### Step 5: Start Services

**XAMPP:**
```
1. Open XAMPP Control Panel
2. Click "Start" for Apache
3. Click "Start" for MySQL
4. Open http://localhost/petfounds/
```

---

## 🧪 Testing Guide

### Test Scenarios

#### 1. Authentication Flow

**Register New User:**
```
1. Go to http://localhost/petfounds/pages/login.php
2. Click "Belum punya akun? Daftar di sini"
3. Fill form:
   - Name: Test User
   - Email: testuser@example.com
   - Password: Test@123
   - Confirm: Test@123
4. Click Register
5. Should redirect to login with success message
```

**Login:**
```
1. Email: admin@petfounds.pro
2. Password: admin123
3. Click Login
4. Should show dashboard feed
```

#### 2. Pet Reports

**Create Report:**
```
1. After login, click "Buat Laporan"
2. Fill form:
   - Type: Lost atau Found
   - Pet Name: Milo
   - Species: Kucing
   - Location: Jakarta, Indonesia
   - Description: Hilang kucing persia putih
3. Upload image (optional)
4. Click Submit
5. Report should appear in feed
```

**Search Reports:**
```
1. In dashboard, search for "kucing"
2. Should show filtered results
3. Try different searches: "Jakarta", "anjing", etc.
```

**Like Report:**
```
1. Click heart icon on report
2. Heart should change color
3. Like count should increment
```

#### 3. Messaging

**Start Chat:**
```
1. Go to Messages tab
2. Select a contact from left panel
3. Chat history should appear on right
4. Type message and send
5. Message should appear immediately
```

#### 4. Profile

**Edit Profile:**
```
1. Go to Profile tab
2. Click "Edit Profile"
3. Update name, phone, bio
4. Click Save
5. Changes should reflect immediately
```

**Upload Avatar:**
```
1. In profile, click avatar or "Edit Avatar"
2. Select image file
3. Click upload
4. Avatar should update immediately
```

### Browser DevTools Testing

**Check Network Requests:**
```javascript
// Open DevTools (F12)
// Network tab
// Perform action
// Verify API calls return 200 status
// Check JSON response format
```

**Check Console Errors:**
```javascript
// Open DevTools (F12)
// Console tab
// Perform actions
// Should see no JavaScript errors
// Only warnings acceptable
```

---

## 🌍 Production Deployment

### Server Requirements

- **Web Server:** Apache 2.4+ (with mod_rewrite)
- **PHP:** 7.4 - 8.2
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **SSL:** Valid SSL certificate required

### Hosting Options

#### Option 1: Shared Hosting

**Popular Providers:**
- Hostinger, DreamHost, Bluehost, SiteGround

**Setup Steps:**
```
1. Upload files via FTP:
   - Host: ftp.yoursite.com
   - User: your-username
   - Password: your-password
   - Protocol: SFTP (secure)

2. Upload directory structure to public_html/:
   - config/
   - api/
   - css/
   - database/
   - js/
   - lib/
   - pages/
   - public/
   - index.php
   - .htaccess

3. Create database in hosting panel

4. Update config/database.php

5. Import schema.sql via phpMyAdmin
```

**Key Steps:**
- [ ] Upload all files via FTP
- [ ] Set correct file permissions (755 for dirs, 644 for files)
- [ ] Create database with UTF-8 collation
- [ ] Update database credentials
- [ ] Import schema.sql
- [ ] Test at http://yourdomain.com

#### Option 2: VPS (Recommended)

**Setup Steps:**

```bash
# 1. Connect to VPS via SSH
ssh root@your_vps_ip

# 2. Update system
apt-get update
apt-get upgrade -y

# 3. Install LAMP stack
apt-get install -y apache2 mysql-server php7.4 php7.4-mysql php7.4-curl php7.4-gd php7.4-mbstring

# 4. Enable mod_rewrite
a2enmod rewrite

# 5. Clone project
cd /var/www/html
git clone https://repo.com/petfounds.git
# OR upload via SCP
scp -r petfounds/* root@your_vps_ip:/var/www/html/petfounds/

# 6. Set permissions
chmod -R 755 /var/www/html/petfounds
chmod -R 777 /var/www/html/petfounds/public/uploads

# 7. Create database
mysql -u root -e "CREATE DATABASE petfounds_db CHARACTER SET utf8mb4;"

# 8. Import schema
mysql -u root petfounds_db < /var/www/html/petfounds/database/schema.sql

# 9. Update config
nano /var/www/html/petfounds/config/database.php
# Set correct database credentials

# 10. Setup SSL (Let's Encrypt)
apt-get install -y certbot python3-certbot-apache
certbot --apache -d yourdomain.com

# 11. Restart Apache
systemctl restart apache2
```

#### Option 3: Docker (Advanced)

**Dockerfile:**
```dockerfile
FROM php:7.4-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable rewrite module
RUN a2enmod rewrite

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

EXPOSE 80
```

**docker-compose.yml:**
```yaml
version: '3'
services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
  
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: petfounds_db
    ports:
      - "3306:3306"
```

### Environment Configuration

**Production config/database.php:**
```php
<?php
// Use environment variables for sensitive data
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'petfounds_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'petfounds_db');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't show errors to users
ini_set('log_errors', 1);
ini_set('error_log', '/var/logs/php-error.log');

// Session configuration
ini_set('session.cookie_secure', 1);  // HTTPS only
ini_set('session.cookie_httponly', 1);  // No JS access
ini_set('session.cookie_samesite', 'Strict');
```

### Production .htaccess

```apache
<IfModule mod_ssl.c>
    # Force HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
</IfModule>

# Disable directory listing
Options -Indexes

# General rewrite
RewriteEngine On
RewriteBase /petfounds/

# Prevent access to protected directories
RewriteRule ^(config|lib|database)/ - [F,L]

# Route to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?request=$1 [QSA,L]
```

---

## ⚡ Performance Optimization

### Database Optimization

```sql
-- Add indexes for frequently searched columns
ALTER TABLE pet_reports ADD INDEX idx_type (type);
ALTER TABLE pet_reports ADD INDEX idx_location (location);
ALTER TABLE pet_reports ADD INDEX idx_created_at (created_at);
ALTER TABLE messages ADD INDEX idx_receiver_id (receiver_id);
ALTER TABLE messages ADD INDEX idx_sender_id (sender_id);

-- Analyze tables
ANALYZE TABLE pet_reports;
ANALYZE TABLE users;
ANALYZE TABLE messages;

-- Check query performance
EXPLAIN SELECT * FROM pet_reports WHERE type='lost' AND location LIKE '%Jakarta%';
```

### Caching

**Browser Caching (already in .htaccess):**
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
</IfModule>
```

**Server-side Caching (Redis optional):**
```php
// In lib/functions.php - add caching layer
function getCachedReports($page = 1) {
    $cache_key = "reports_page_" . $page;
    
    // Check cache
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cache_key);
        if ($cached) return $cached;
    }
    
    // Get from database
    $reports = fetchAll("SELECT * FROM pet_reports LIMIT ?, ?", 
                       "ii", [(($page-1)*10), 10]);
    
    // Store in cache for 5 minutes
    if (function_exists('apcu_store')) {
        apcu_store($cache_key, $reports, 300);
    }
    
    return $reports;
}
```

### Code Optimization

**Minify CSS/JS:**
```bash
# Use online tools or npm packages
npm install -g csso-cli uglify-js

csso css/style.css -o css/style.min.css
uglifyjs js/functions.js -o js/functions.min.js
```

**Update HTML references:**
```html
<!-- Use minified versions in production -->
<link rel="stylesheet" href="css/style.min.css">
<script src="js/functions.min.js"></script>
```

### Image Optimization

```bash
# Compress images using ImageMagick
convert image.jpg -quality 85 image-compressed.jpg

# Or use online tools:
# TinyPNG, ImageOptim, Squoosh
```

---

## 🔒 Security Hardening

### 1. SQL Injection Prevention

✅ **Already implemented** - All queries use prepared statements

```php
// GOOD - Safe
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// BAD - Never do this
$query = "SELECT * FROM users WHERE email = '$email'";
```

### 2. XSS Prevention

**Implemented in output:**
```php
// Always escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// Or use JSON for API responses (already done)
header('Content-Type: application/json');
echo json_encode($data);
```

### 3. CSRF Protection

**Add to forms:**
```php
// In pages, add token to forms
session_start();
$token = md5(uniqid(mt_rand(), true));
$_SESSION['csrf_token'] = $token;
?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $token ?>">
    <!-- form fields -->
</form>

// Validate in PHP
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

### 4. Rate Limiting

**Add to api/functions.php:**
```php
function checkRateLimit($user_id, $action, $limit = 10, $window = 60) {
    $key = "rate_limit_" . $user_id . "_" . $action;
    $attempts = getFromStorage($key) ?? 0;
    
    if ($attempts >= $limit) {
        errorResponse("Too many requests. Try again later.", 429);
    }
    
    setToStorage($key, $attempts + 1, $window);
    return true;
}

// Use in API:
checkRateLimit($_SESSION['user_id'], 'create_report');
```

### 5. File Upload Security

**Already implemented:**
```php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024;  // 5MB

if (!in_array($file_type, $allowed_types)) {
    errorResponse("File type not allowed");
}
if ($file_size > $max_size) {
    errorResponse("File too large");
}

// Rename to random filename
$new_filename = uniqid() . "." . pathinfo($file_name, PATHINFO_EXTENSION);
```

### 6. Environment Variables

**Create .env file:**
```
DB_HOST=localhost
DB_USER=petfounds_user
DB_PASS=secure_password_here
DB_NAME=petfounds_db
APP_DEBUG=false
APP_ENV=production
```

**Load in config/database.php:**
```php
if (file_exists(__DIR__ . '/../.env')) {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}
```

### 7. API Key Management (Optional)

**For external API calls:**
```php
// Use environment variables
$api_key = getenv('EXTERNAL_API_KEY');

// Never hardcode
// $api_key = "abc123xyz...";  // WRONG!
```

---

## 🐛 Troubleshooting

### Common Issues

#### Issue: Database Connection Error

**Symptoms:** "Connection refused" error

**Solution:**
```php
// config/database.php - Add error logging
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    // Log error but don't expose to user
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Service temporarily unavailable");
}

// Check:
1. MySQL service running
2. Correct DB credentials
3. Database exists
4. User has permissions
```

#### Issue: 404 - Page Not Found

**Solution:**
```
1. Check .htaccess exists and readable
2. Enable mod_rewrite: a2enmod rewrite
3. Restart Apache: systemctl restart apache2
4. Check file paths are correct
5. Verify folder permissions: chmod 755
```

#### Issue: File Upload Fails

**Solutions:**
```
1. Check uploads folder exists: public/uploads/
2. Set permissions: chmod 777 public/uploads/
3. Check file size limit: ini_set('upload_max_filesize', '20M')
4. Check MIME types: file_info extension enabled
5. Check temporary upload dir: /tmp/ has space
```

#### Issue: Session Not Persisting

**Solution:**
```php
// Ensure session_start() at top of every page
session_start();

// Check session directory writable
if (!is_writable(session_save_path())) {
    mkdir(session_save_path(), 0777, true);
}

// Verify in php.ini
session.save_path = "/var/lib/php/sessions"
session.gc_maxlifetime = 1440
```

#### Issue: JavaScript Console Errors

**Solutions:**
```javascript
// Check:
1. API endpoints return valid JSON
2. No CORS errors - check headers
3. Correct API URLs in js/functions.js
4. Check for syntax errors - use browser console
5. Verify all dependencies loaded

// Common fixes:
- apiCall() function uses correct baseURL
- Use absolute paths: /petfounds/api/
- Check Content-Type headers
```

### Debug Mode

**Enable for development only:**
```php
// config/database.php
define('DEBUG_MODE', getenv('APP_ENV') === 'development');

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}
```

**In lib/functions.php:**
```php
function debugLog($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[DEBUG] " . $message);
        if ($data) {
            error_log(json_encode($data));
        }
    }
}
```

---

## 📊 Monitoring Checklist

**Post-Deployment:**

- [ ] Test all login scenarios
- [ ] Verify database backups working
- [ ] Monitor error logs daily
- [ ] Check disk space regularly
- [ ] Monitor database performance
- [ ] Update PHP/MySQL when patches available
- [ ] Review security headers with tool
- [ ] Test SSL certificate validity
- [ ] Monitor uptime with external service
- [ ] Check for broken links
- [ ] Test file uploads working
- [ ] Verify email notifications (if added)

---

## 🔄 Continuous Deployment

**GitHub Actions workflow (.github/workflows/deploy.yml):**
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Deploy with rsync
      env:
        DEPLOY_KEY: ${{ secrets.DEPLOY_KEY }}
        DEPLOY_USER: ${{ secrets.DEPLOY_USER }}
        DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
      run: |
        mkdir -p ~/.ssh
        echo "$DEPLOY_KEY" > ~/.ssh/deploy_key
        chmod 600 ~/.ssh/deploy_key
        rsync -avz -e "ssh -i ~/.ssh/deploy_key -o StrictHostKeyChecking=no" \
          --exclude='.env' \
          --exclude='node_modules' \
          ./ $DEPLOY_USER@$DEPLOY_HOST:/var/www/petfounds/
```

---

**Last Updated:** March 2026  
**Version:** 1.0.0
