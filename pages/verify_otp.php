<?php
/**
 * Verify OTP Page
 * /pages/verify_otp.php
 */

require_once '../lib/auth.php';
require_once '../config/database.php';
require_once '../lib/functions.php';

// If already logged in, redirect to dashboard
requireGuest();

// Get email from session or query param
$email = $_GET['email'] ?? $_SESSION['forgot_email'] ?? '';

if (empty($email)) {
    header('Location: forgot_password.php');
    exit;
}

$_SESSION['forgot_email'] = $email;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP | PetFounds</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="bg-animation">

    <div class="bg-ball color-1"></div>
    <div class="bg-ball color-2"></div>
    <div class="bg-ball color-3"></div>

    <div id="toast-container"></div>

    <a href="http://localhost/lost-and-found-pet/" class="app-logo" style="position: absolute; top: 24px; left: 24px;">
        <div class="logo-icon flex-center">
            <i class="fa-solid fa-paw"></i>
        </div>
        <span class="logo-text">Pet<span class="text-gradient">Founds</span></span>
    </a>

    <main class="auth-layout">
        <div class="auth-card">
            <h2 class="text-center" style="font-size: 2rem; font-weight: 800; color: var(--secondary); margin-bottom: 5px;">
                Verifikasi OTP
            </h2>
            <p class="text-center text-muted" style="margin-bottom: 35px;">
                Masukkan kode OTP yang telah dikirim ke email Anda.
            </p>

            <form id="verify-otp-form">
                <input type="hidden" id="otp-email" value="<?php echo htmlspecialchars($email); ?>">
                <div class="input-modern">
                    <i class="fa-solid fa-key"></i>
                    <input type="text" id="otp-code" placeholder="Kode OTP (6 digit)" maxlength="6" inputmode="numeric" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; margin-top: 20px;">
                    Verifikasi
                </button>
            </form>

            <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.9rem;">
                Belum menerima kode? <a href="forgot_password.php" class="text-primary font-bold">Kirim ulang</a>
            </p>
        </div>
    </main>

    <script src="../public/js/utils.js"></script>
    <script src="../public/js/pages/verify_otp.js"></script>
</body>
</html>
