<?php
/**
 * Authentication Module
 * Handles login, register, logout, and session management
 */

// Include PHPMailer
require_once dirname(__DIR__) . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data from session
function getCurrentUser() {
    if (isLoggedIn()) {
        $avatar = $_SESSION['user_avatar'] ?? '';
        if (!$avatar) {
            $avatar = generateAvatarUrl($_SESSION['user_name']);
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'avatar' => $avatar,
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
    
    // Verify password using bcrypt
    if (password_verify($password, $user['password'])) {
        
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

    // Insert new user - avatar_url will be NULL, generated on demand
    $query = "INSERT INTO users (name, email, password, avatar_url) VALUES (?, ?, ?, ?)";
    $result = executeQuery($connection, $query, [
        $name,
        $email,
        $hashedPassword,
        null
    ]);

    if ($result['success']) {
        // Auto login after registration
        $_SESSION['user_id'] = $result['insert_id'];
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_avatar'] = '';
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
        header('Location: explore.php');
        exit;
    }
}

// Forgot password - generate reset token and send email
function forgotPassword($connection, $email) {
    $email = sanitizeInput($email);

    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Format email tidak valid'];
    }

    // Check if email exists
    $query = "SELECT id FROM users WHERE email = ?";
    $user = fetchOne($connection, $query, [$email]);

    if (!$user) {
        // Don't reveal if email exists or not (security)
        return ['success' => true, 'message' => 'Jika email terdaftar, link pemulihan akan dikirim'];
    }

    // Generate reset token
    $resetToken = generateToken(32);
    $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Update user with reset token
    $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?";
    $result = executeQuery($connection, $updateQuery, [$resetToken, $expiryTime, $user['id']]);

    if (!$result['success']) {
        return ['success' => false, 'message' => 'Gagal memproses permintaan'];
    }

    // Send email with reset link
    $resetLink = APP_URL . '/pages/reset_password.php?token=' . $resetToken;
    $subject = 'PetFounds - Pemulihan Sandi';
    $message = "Halo,\n\nAnda telah meminta untuk mengatur ulang sandi Anda.\n\n";
    $message .= "Klik link di bawah untuk mengatur ulang sandi Anda:\n";
    $message .= $resetLink . "\n\n";
    $message .= "Link ini berlaku selama 1 jam.\n\n";
    $message .= "Jika Anda tidak meminta ini, abaikan email ini.\n\n";
    $message .= "Terima kasih,\nTim PetFounds";

    $headers = "From: noreply@petfounds.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send email (untuk testing bisa diganti dengan log)
    if (function_exists('mail')) {
        @mail($email, $subject, $message, $headers);
    }

    return ['success' => true, 'message' => 'Jika email terdaftar, link pemulihan akan dikirim'];
}

// Reset password using token
function resetPassword($connection, $token, $password, $passwordConfirm) {
    if (empty($token)) {
        return ['success' => false, 'message' => 'Token tidak valid'];
    }

    if (!validatePassword($password)) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'message' => 'Password tidak cocok'];
    }

    // Find user with valid reset token
    $query = "SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()";
    $user = fetchOne($connection, $query, [$token]);

    if (!$user) {
        return ['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa'];
    }

    // Hash new password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Update password and clear reset token
    $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
    $result = executeQuery($connection, $updateQuery, [$hashedPassword, $user['id']]);

    if ($result['success']) {
        return ['success' => true, 'message' => 'Sandi berhasil diatur ulang'];
    }

    return ['success' => false, 'message' => 'Gagal mengatur ulang sandi'];
}

// Generate and send OTP
function requestOTP($connection, $email) {
    $email = sanitizeInput($email);

    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Format email tidak valid'];
    }

    // Check if email exists
    $query = "SELECT id FROM users WHERE email = ?";
    $user = fetchOne($connection, $query, [$email]);

    if (!$user) {
        // Don't reveal if email exists or not (security)
        return ['success' => true, 'message' => 'Jika email terdaftar, kode OTP akan dikirim'];
    }

    // Generate OTP
    $otpLength = intval($_ENV['OTP_LENGTH'] ?? 6);
    $otp = str_pad(random_int(0, pow(10, $otpLength) - 1), $otpLength, '0', STR_PAD_LEFT);
    $otpExpiry = date('Y-m-d H:i:s', strtotime('+' . ($_ENV['OTP_EXPIRY'] ?? 600) . ' seconds'));

    // Update user with OTP
    $updateQuery = "UPDATE users SET otp = ?, otp_expires = ? WHERE id = ?";
    $result = executeQuery($connection, $updateQuery, [$otp, $otpExpiry, $user['id']]);

    if (!$result['success']) {
        return ['success' => false, 'message' => 'Gagal memproses permintaan'];
    }

    // Send OTP email
    $sent = sendOTPEmail($email, $otp);

    if (!$sent) {
        return ['success' => false, 'message' => 'Gagal mengirim OTP'];
    }

    return ['success' => true, 'message' => 'Jika email terdaftar, kode OTP akan dikirim'];
}

// Verify OTP and allow password reset
function verifyOTP($connection, $email, $otp) {
    error_log("=== Verify OTP Start ===");
    error_log("Email: $email, OTP: $otp");

    $email = sanitizeInput($email);
    // Don't sanitize OTP - it's only 6 digits
    $otp = trim($otp);

    error_log("After sanitize - Email: $email, OTP: $otp");

    if (empty($email) || empty($otp)) {
        error_log("Empty email or OTP");
        return ['success' => false, 'message' => 'Email dan OTP tidak boleh kosong'];
    }

    if (!validateEmail($email)) {
        error_log("Invalid email format");
        return ['success' => false, 'message' => 'Format email tidak valid'];
    }

    // Find user with valid OTP
    error_log("Querying for user with email=$email and otp=$otp");
    $query = "SELECT id FROM users WHERE email = ? AND otp = ? AND otp_expires > NOW()";
    $user = fetchOne($connection, $query, [$email, $otp]);

    if (!$user) {
        error_log("User not found with valid OTP");
        // Debug: check what's in DB
        $debugQuery = "SELECT id, email, otp, otp_expires FROM users WHERE email = ?";
        $debugUser = fetchOne($connection, $debugQuery, [$email]);
        if ($debugUser) {
            error_log("Debug - User found but OTP mismatch: DB_OTP=" . $debugUser['otp'] . ", INPUT_OTP=$otp, EXPIRES=" . $debugUser['otp_expires']);
        }
        return ['success' => false, 'message' => 'Kode OTP tidak valid atau sudah kadaluarsa'];
    }

    error_log("User found, generating reset token");

    // Generate reset token for password change
    $resetToken = generateToken(32);
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Update user with reset token and clear OTP
    $updateQuery = "UPDATE users SET reset_token = ?, reset_token_expires = ?, otp = NULL, otp_expires = NULL WHERE id = ?";
    $result = executeQuery($connection, $updateQuery, [$resetToken, $tokenExpiry, $user['id']]);

    if ($result['success']) {
        error_log("✅ OTP verified and reset token generated");
        return ['success' => true, 'message' => 'OTP terverifikasi', 'token' => $resetToken];
    }

    error_log("Failed to update user with reset token");
    return ['success' => false, 'message' => 'Gagal verifikasi OTP'];
}


// Send OTP email using PHPMailer
function sendOTPEmail($email, $otp) {
    error_log("=== OTP Email Send Start ===");
    error_log("To: $email, OTP: $otp");

    try {
        $mail = new PHPMailer(true);

        // SMTP configuration
        error_log("Configuring SMTP...");
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
        $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = intval($_ENV['MAIL_PORT'] ?? 587);
        $mail->SMTPDebug = 0; // Set to 2 for debug

        error_log("SMTP Config: Host=" . $mail->Host . ", Port=" . $mail->Port . ", User=" . $mail->Username);

        // Email details
        error_log("Setting email content...");
        $mail->setFrom($_ENV['MAIL_USERNAME'] ?? '', $_ENV['MAIL_FROM_NAME'] ?? 'PetFounds');
        $mail->addAddress($email);
        $mail->Subject = 'PetFounds - Kode OTP Pemulihan Sandi';
        $mail->isHTML(false);
        $mail->Body = "Kode OTP Anda: $otp\n\nBerlaku 10 menit.\n\nJangan bagikan kode ini kepada siapapun.\n\nTerima kasih,\nTim PetFounds";

        error_log("Sending email to: $email");
        if ($mail->send()) {
            error_log("✅ OTP Email sent successfully to $email");
            return true;
        } else {
            error_log("❌ Email send failed: " . $mail->ErrorInfo);
            return false;
        }

    } catch (Exception $e) {
        error_log("❌ PHPMailer Exception: " . $e->getMessage());
        error_log("Error Info: " . (isset($mail) ? $mail->ErrorInfo : 'N/A'));
        return false;
    }
}

?>
