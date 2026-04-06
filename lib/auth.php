<?php
/**
 * Authentication Module
 * Handles login, register, logout, and session management
 */

session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data from session
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'avatar' => $_SESSION['user_avatar'] ?? 'https://i.pravatar.cc/150?img=68',
            'phone' => $_SESSION['user_phone'] ?? '',
            'bio' => $_SESSION['user_bio'] ?? ''
        ];
    }
    return null;
}

// Login user
function login($connection, $email, $password) {
    $email = sanitizeInput($email);
    
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Format email tidak valid'];
    }
    
    if (empty($password)) {
        return ['success' => false, 'message' => 'Password tidak boleh kosong'];
    }
    
    // Get user from database
    $query = "SELECT id, name, email, password, avatar_url, phone, bio FROM users WHERE email = ?";
    $user = fetchOne($connection, $query, [$email]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email atau password salah'];
    }
    
    // Verify password (using SHA2 from database)
    if (sha1($password) === $user['password'] || 
        hash('sha256', $password) === $user['password'] ||
        password_verify($password, $user['password'])) {
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar_url'];
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['user_bio'] = $user['bio'] ?? '';
        $_SESSION['login_time'] = time();
        
        return [
            'success' => true,
            'message' => 'Login berhasil',
            'user' => getCurrentUser()
        ];
    }
    
    return ['success' => false, 'message' => 'Email atau password salah'];
}

// Register new user
function register($connection, $name, $email, $password, $passwordConfirm) {
    $name = sanitizeInput($name);
    $email = sanitizeInput($email);
    
    // Validations
    if (!validateRequired($name)) {
        return ['success' => false, 'message' => 'Nama tidak boleh kosong'];
    }
    
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Format email tidak valid'];
    }
    
    if (!validatePassword($password)) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter'];
    }
    
    if ($password !== $passwordConfirm) {
        return ['success' => false, 'message' => 'Password tidak cocok'];
    }
    
    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $existing = fetchOne($connection, $checkQuery, [$email]);
    
    if ($existing) {
        return ['success' => false, 'message' => 'Email sudah terdaftar'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert new user
    $query = "INSERT INTO users (name, email, password, avatar_url) VALUES (?, ?, ?, ?)";
    $result = executeQuery($connection, $query, [
        $name,
        $email,
        $hashedPassword,
        'https://i.pravatar.cc/150?img=68'
    ]);
    
    if ($result['success']) {
        // Auto login after registration
        $_SESSION['user_id'] = $result['insert_id'];
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_avatar'] = 'https://i.pravatar.cc/150?img=68';
        $_SESSION['user_phone'] = '';
        $_SESSION['user_bio'] = '';
        
        return [
            'success' => true,
            'message' => 'Registrasi berhasil, silakan login'
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal mendaftar: ' . $result['error']];
}

// Logout user
function logout() {
    session_destroy();
    return ['success' => true, 'message' => 'Logout berhasil'];
}

// Check if email exists
function emailExists($connection, $email) {
    $query = "SELECT id FROM users WHERE email = ?";
    return fetchOne($connection, $query, [$email]) !== null;
}

// Get user by ID
function getUserById($connection, $userId) {
    $query = "SELECT id, name, email, avatar_url, bio, phone, created_at FROM users WHERE id = ?";
    return fetchOne($connection, $query, [$userId]);
}

// Update user profile
function updateUserProfile($connection, $userId, $data) {
    $name = sanitizeInput($data['name'] ?? '');
    $bio = sanitizeInput($data['bio'] ?? '');
    $phone = sanitizeInput($data['phone'] ?? '');
    
    $query = "UPDATE users SET name = ?, bio = ?, phone = ? WHERE id = ?";
    $result = executeQuery($connection, $query, [$name, $bio, $phone, $userId]);
    
    if ($result['success']) {
        $_SESSION['user_name'] = $name;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_bio'] = $bio;
    }

    return $result['success'];
}

// Update user avatar
function updateUserAvatar($connection, $userId, $avatarUrl) {
    $query = "UPDATE users SET avatar_url = ? WHERE id = ?";
    $result = executeQuery($connection, $query, [$avatarUrl, $userId]);
    
    if ($result['success']) {
        $_SESSION['user_avatar'] = $avatarUrl;
    }
    
    return $result['success'];
}

// Require login (redirect to login page if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Require guest (redirect to dashboard if already logged in)
function requireGuest() {
    if (isLoggedIn()) {
        header('Location: post_report.php');
        exit;
    }
}

?>
