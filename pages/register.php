<?php
/**
 * Register Page
 * /pages/register.php
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
    <title>Daftar PetFounds | Profesional Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
                Bergabunglah Sekarang
            </h2>
            <p class="text-center text-muted" style="margin-bottom: 35px;">
                Daftar untuk mulai mencari atau melaporkan hewan peliharaan Anda.
            </p>
            
            <form id="register-form">
                <div class="input-modern">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="register-name" placeholder="Nama Lengkap" required>
                </div>
                <div class="input-modern">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" id="register-email" placeholder="Alamat Email" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="register-password" placeholder="Kata Sandi" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="register-password-confirm" placeholder="Konfirmasi Kata Sandi" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; margin-top: 20px;">
                    Daftar
                </button>
            </form>
            
            <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.9rem;">
                Sudah punya akun? <a href="login.php" class="text-primary font-bold">Masuk</a>
            </p>
        </div>
    </main>

    <script src="../js/functions.js"></script>
    <script>
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('register-name').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const passwordConfirm = document.getElementById('register-password-confirm').value;
            
            try {
                const response = await fetch('../api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, email, password, password_confirm: passwordConfirm })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('Registrasi berhasil! Silakan login', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    showToast(data.message || 'Registrasi gagal', 'error');
                }
            } catch (error) {
                showToast('Terjadi kesalahan: ' + error.message, 'error');
            }
        });
    </script>
</body>
</html>
