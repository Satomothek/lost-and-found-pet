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
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body class="bg-animation">
    
    <div class="bg-ball color-1"></div>
    <div class="bg-ball color-2"></div>
    <div class="bg-ball color-3"></div>

    <div id="toast-container"></div>

    <main class="auth-layout">
        <div class="auth-card">
            <div class="flex-center" style="margin-bottom: 30px;">
                <div class="logo-icon flex-center" style="width: 70px; height: 70px; border-radius: 20px; font-size: 2.5rem;">
                    <i class="fa-solid fa-paw"></i>
                </div>
            </div>
            
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
                <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; margin-top: 20px;">
                    Login
                </button>
            </form>
            
            <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.9rem;">
                Belum punya akun? <a href="register.php" class="text-primary font-bold">Daftar</a>
            </p>
        </div>
    </main>

    <script src="../js/functions.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            try {
                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('Otorisasi Jaringan Berhasil.', 'success');
                    setTimeout(() => {
                        window.location.href = 'post_report.php';
                    }, 1000);
                } else {
                    showToast(data.message || 'Login gagal', 'error');
                }
            } catch (error) {
                showToast('Terjadi kesalahan: ' + error.message, 'error');
            }
        });
    </script>
</body>
</html>
