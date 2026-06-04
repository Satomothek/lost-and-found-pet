<?php
/**
 * Login Page
 * /pages/login.php
 */

require_once '../lib/auth.php';
require_once '../config/database.php';
require_once '../lib/functions.php';

// If already logged in, redirect to dashboard
requireGuest();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke PetFounds | Profesional Network</title>
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
                Welcome
            </h2>   
            <p class="text-center text-muted" style="margin-bottom: 35px;">
                Masuk untuk mengakses jaringan PetFounds.
            </p>
            
            <form id="login-form">
                <div class="input-modern">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" id="login-email" placeholder="Alamat Email" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="login-password" placeholder="Kata Sandi" required>
                </div>
                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="forgot_password.php" class="text-primary font-bold" style="font-size: 0.85rem; text-decoration: none;">Lupa sandi?</a>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; margin-top: 20px;">
                    Login
                </button>
            </form>
            
            <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.9rem;">
                Belum punya akun? <a href="register.php" class="text-primary font-bold register-link">Daftar Akun</a>
            </p>
        </div>
    </main>

    <script src="../public/js/utils.js"></script>
    <script src="../public/js/pages/login.js"></script>
</body>
</html>
