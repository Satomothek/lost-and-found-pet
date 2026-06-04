<?php
/**
 * Admin Login Page
 * PetFounds - Pet Finder Network
 */

session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once dirname(__FILE__) . '/../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password harus diisi';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PASS
            );

            $stmt = $pdo->prepare('SELECT id, name, email, password, role, is_active FROM admins WHERE email = ?');
            $stmt->execute([$email]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && $admin['is_active']) {
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];

                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Email atau password salah';
                }
            } else {
                $error = 'Admin tidak ditemukan atau tidak aktif';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PetFounds</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-animation">

    <div class="bg-ball color-1"></div>
    <div class="bg-ball color-2"></div>
    <div class="bg-ball color-3"></div>

    <a href="http://localhost/lost-and-found-pet/" class="app-logo" style="position: absolute; top: 24px; left: 24px;">
        <div class="logo-icon flex-center">
            <i class="fa-solid fa-shield"></i>
        </div>
        <span class="logo-text">Admin<span class="text-gradient">Panel</span></span>
    </a>

    <main class="auth-layout">
        <div class="auth-card">
            <h2 class="text-center" style="font-size: 2rem; font-weight: 800; color: var(--secondary); margin-bottom: 5px;">
                Admin Login
            </h2>
            <p class="text-center text-muted" style="margin-bottom: 35px;">
                Masuk untuk mengelola PetFounds.
            </p>

            <?php if ($error): ?>
            <div style="background-color: #fee; color: #c33; border: 1px solid #fcc; padding: 12px 14px; border-radius: 14px; margin-bottom: 20px; font-size: 0.95rem; display: flex; gap: 10px; align-items: center;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div style="background-color: #efe; color: #3c3; border: 1px solid #cfc; padding: 12px 14px; border-radius: 14px; margin-bottom: 20px; font-size: 0.95rem; display: flex; gap: 10px; align-items: center;">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-modern">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Admin" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; margin-top: 20px;">
                    <i class="fa-solid fa-sign-in-alt"></i> Masuk
                </button>
            </form>

            <p class="text-center text-muted" style="margin-top: 25px; font-size: 0.9rem;">
                <a href="../../index.php" class="text-primary font-bold" style="text-decoration: none;">← Kembali ke Halaman Utama</a>
            </p>
        </div>
    </main>

</body>
</html>
