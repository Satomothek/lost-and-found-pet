<?php
/**
 * Admin Setup - Create First Admin
 * PetFounds - Pet Finder Network
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';

session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validation
        if (!$name) {
            $error = 'Nama admin harus diisi';
        } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email harus diisi dengan benar';
        } elseif (!$password || strlen($password) < 6) {
            $error = 'Password harus minimal 6 karakter';
        } elseif ($password !== $password_confirm) {
            $error = 'Password tidak cocok';
        } else {
            try {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                    DB_USER,
                    DB_PASS
                );

                // Check if admin already exists
                $stmt = $pdo->query('SELECT COUNT(*) as count FROM admins');
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($count > 0) {
                    $error = 'Admin sudah ada. Hubungi administrator untuk menambah admin baru.';
                } else {
                    // Check email uniqueness
                    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM admins WHERE email = ?');
                    $stmt->execute([$email]);
                    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    if ($exists) {
                        $error = 'Email sudah terdaftar';
                    } else {
                        // Create admin
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare(
                            'INSERT INTO admins (name, email, password, role, is_active)
                             VALUES (?, ?, ?, ?, 1)'
                        );
                        $stmt->execute([$name, $email, $hashed_password, 'super_admin']);

                        $success = 'Admin berhasil dibuat! Silakan login ke <a href="login.php">Admin Panel</a>';
                    }
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Check if any admin exists
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM admins');
    $admin_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($admin_count > 0) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    // Database error, show form anyway
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin - PetFounds</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/pages/admin-setup.css">
</head>
<body class="bg-animation">

    <div class="bg-ball color-1"></div>
    <div class="bg-ball color-2"></div>
    <div class="bg-ball color-3"></div>

    <a href="http://localhost/lost-and-found-pet/" class="app-logo setup-logo">
        <div class="logo-icon flex-center">
            <i class="fa-solid fa-shield"></i>
        </div>
        <span class="logo-text">Admin<span class="text-gradient">Setup</span></span>
    </a>

    <main class="auth-layout">
        <div class="auth-card">
            <h2 class="text-center setup-title">
                Setup Admin Pertama
            </h2>
            <p class="text-center text-muted setup-subtitle">
                Buat akun admin untuk mengelola PetFounds
            </p>

            <?php if ($error): ?>
            <div class="setup-alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="setup-alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                <div class="input-modern">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="name" placeholder="Nama Lengkap" required>
                </div>
                <div class="input-modern">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Admin" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password (min 6 karakter)" required>
                </div>
                <div class="input-modern">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password_confirm" placeholder="Konfirmasi Password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block setup-submit-btn">
                    <i class="fa-solid fa-shield-halved"></i> Daftar Admin
                </button>
            </form>
            <?php endif; ?>

            <p class="text-center text-muted setup-footer">
                <a href="../../index.php" class="text-primary font-bold setup-footer-link">← Kembali ke Halaman Utama</a>
            </p>
        </div>
    </main>

</body>
</html>
