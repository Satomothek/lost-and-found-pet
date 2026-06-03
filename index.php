<?php
/**
 * PetFounds - Dynamic Landing Page
 */
session_start();
$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetFounds | Profesional Network Pencarian Hewan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="bg-animation">
        <div class="bg-ball color-1"></div>
        <div class="bg-ball color-2"></div>
        <div class="bg-ball color-3"></div>
        <div class="grid-overlay"></div>
    </div>

    <header class="guest-header">
        <div class="container-fluid flex-between">
            <div class="app-logo">
                <div class="logo-icon flex-center"><i class="fa-solid fa-paw"></i></div>
                <span class="logo-text">Pet<span class="text-gradient">Founds</span></span>
            </div>
            <div class="hero-nav flex-center" style="gap: 15px;">
                <?php if ($loggedIn): ?>
                    <a href="pages/explore.php" class="btn btn-primary shadow-primary">Dashboard</a>
                    <a href="pages/logout.php" class="btn btn-ghost font-bold">Keluar</a>
                <?php else: ?>
                    <a href="pages/login.php" class="btn btn-ghost font-bold">Masuk</a>
                    <a href="pages/register.php" class="btn btn-primary shadow-primary">Daftar Sekarang</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content full-width" style="padding: 0;">
        <section class="hero-section container-fluid">
            <div class="hero-text fade-up" style="animation-delay: 0.1s;">
                <div class="badge-glass mb-4">
                    <i class="fa-solid fa-sparkles text-warning"></i> Platform Pencarian Hewan
                </div>
                <h1 class="hero-title">Satukan Kembali <br><span class="text-gradient">Keluarga Anda.</span></h1>
                <p class="hero-desc" style="color: black;">Jangan panik saat mereka hilang. Laporkan kehilangan atau temukan pemilik hewan tersesat di sekitarmu secara real-time dengan teknologi pencocokan cepat kami.</p>
                <div class="hero-actions">
                    <a href="pages/login.php" class="btn btn-primary btn-lg">Mulai Pencarian <i class="fa-solid fa-arrow-right"></i></a>
                    <a href="#features" class="btn btn-glass btn-lg"><i class="fa-regular fa-compass"></i> Cara Kerja</a>
                </div>
                <div class="social-proof mt-4">
                    <div class="avatar-group">
                        <img src="https://i.pravatar.cc/100?img=1" alt="User">
                        <img src="https://i.pravatar.cc/100?img=2" alt="User">
                        <img src="https://i.pravatar.cc/100?img=3" alt="User">
                        <img src="https://i.pravatar.cc/100?img=4" alt="User">
                    </div>
                    <p class="text-muted text-sm">Terpercaya.</p>
                </div>
            </div>

            <div class="hero-image flex-center fade-in" style="animation-delay: 0.3s; position: relative;">
                <div class="mockup-wrapper">
                    <img src="https://images.unsplash.com/photo-1543852786-1cf6624b9987?auto=format&fit=crop&q=80&w=800" alt="Happy Pet" class="main-hero-img">
                    <div class="floating-badge badge-1">
                        <div class="icon-circle bg-success"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <strong style="color: var(--secondary); display: block;">Milo ditemukan!</strong>
                            <span class="text-muted text-sm">2 menit yang lalu</span>
                        </div>
                    </div>
                    <div class="floating-badge badge-2">
                        <div class="icon-circle bg-warning"><i class="fa-solid fa-exclamation-triangle"></i></div>
                        <div>
                            <strong style="color: var(--secondary); display: block;">Heru Hilang!</strong>
                            <span class="text-muted text-sm">5 menit yang lalu</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="features-section container-fluid">
            <div class="text-center" style="margin-bottom: 60px;">
                <h2 style="font-size: 2.8rem; font-weight: 800; color: var(--secondary);">Dirancang untuk <span class="text-gradient">Kecepatan.</span></h2>
                <p class="text-muted" style="font-size: 1.1rem; max-width: 600px; margin: 10px auto 0;">Setiap detik sangat berharga. Kami menyediakan alat terbaik untuk mempercepat proses pencarian hewan Anda.</p>
            </div>
            <div class="bento-landing-grid">
                <div class="bento-feature glass-panel">
                    <div class="feature-content">
                        <div class="feature-icon bg-primary-light text-primary"><i class="fa-solid fa-chart-line"></i></div>
                        <h3>Mengelola Laporan</h3>
                        <p>Simpan dan kelola laporan Anda dengan mudah menggunakan fitur manajemen laporan yang intuitif.</p>
                    </div>
                </div>
                <div class="bento-feature glass-panel">
                    <div class="feature-icon bg-success-light text-success"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Pesan Terenkripsi</h3>
                    <p>Berkomunikasi langsung dengan penemu hewan tanpa harus membagikan nomor telepon pribadi Anda.</p>
                </div>
                <div class="bento-feature glass-panel">
                    <div class="feature-icon bg-warning-light text-warning"><i class="fa-solid fa-robot"></i></div>
                    <h3>Pencocokan Cerdas</h3>
                    <p>Sistem kami mencocokkan ciri-ciri laporan hilang dan laporan ditemukan secara otomatis.</p>
                </div>
                <div class="bento-feature glass-panel">
                    <div class="feature-icon text-danger" style="background: #cf4040a1; color: white;"><i class="fa-solid fa-bookmark"></i></div>
                    <h3>Bookmarks</h3>
                    <p>Simpan laporan yang penting untuk referensi di masa depan.</p>
                </div>
            </div>
        </section>

        <section class="cta-section container-fluid">
            <div class="cta-box glass-panel text-center">
                <h2 style="font-size: 3rem; font-weight: 800; color: white; margin-bottom: 20px;">Siap Menjadi Pahlawan?</h2>
                <p style="color: rgba(255,255,255,0.8); font-size: 1.2rem; max-width: 600px; margin: 0 auto 40px;">Bantu komunitas dan satukan kembali hewan peliharaan dengan keluarga mereka. Bergabung gratis sekarang.</p>
                <a href="pages/login.php" class="btn bg-surface text-primary btn-lg shadow-lg" style="font-size: 1.2rem; padding: 20px 40px;">Buka Akses Dashboard <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </section>

        <footer class="app-footer">
            <div class="container-fluid flex-between footer-content">
                <div class="footer-brand">
                    <div class="app-logo mb-4">
                        <div class="logo-icon flex-center"><i class="fa-solid fa-paw"></i></div>
                        <span class="logo-text">Pet<span class="text-gradient">Founds</span></span>
                    </div>
                    <p class="text-muted">© 2026 PetFounds Inc.<br>Developed by Kelompok 1 Praktikum Web.</p>
                </div>
                <div class="footer-links">
                    <a href="https://github.com/Satomothek/lost-and-found-pet" target="_blank">
                        <i class="fa-brands fa-github"></i>
                    </a>
                </div>
            </div>
        </footer>
    </main>

    
</body>
</html>

