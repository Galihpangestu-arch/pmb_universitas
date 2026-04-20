<?php
session_start();
require_once 'config/config.php';

// Tampilkan pesan sukses dari registrasi
if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $success = '✅ Pendaftaran berhasil! Silakan login dengan email dan password Anda.';
    
    if (isset($_SESSION['registration_nomor_test'])) {
        $success .= '<br><strong>Nomor Test Anda: ' . htmlspecialchars($_SESSION['registration_nomor_test']) . '</strong>';
        unset($_SESSION['registration_nomor_test']);
    }
    unset($_SESSION['registration_success']);
    unset($_SESSION['registration_email']);
}

// Tampilkan pesan logout jika ada
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $message = isset($_GET['message']) ? urldecode($_GET['message']) : 'Anda telah logout.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>PMB Universitas | Pendaftaran Mahasiswa Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-deep: #0a0c15;
            --primary-electric: #6366f1;
            --primary-glow: #8b5cf6;
            --accent-teal: #14b8a6;
            --accent-rose: #f43f5e;
            --text-light: #f1f5f9;
            --text-dim: #94a3b8;
            --border-glass: rgba(99, 102, 241, 0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-deep);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Gradient Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 20% 30%, rgba(99,102,241,0.15) 0%, rgba(15,23,42,1) 90%);
        }

        .animated-bg::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background: radial-gradient(circle, rgba(139,92,246,0.08) 0%, transparent 60%);
            animation: rotateGlow 28s linear infinite;
        }

        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Noise Texture */
        .noise {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: -1;
        }

        /* Background floating circles */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
        }

        .bg-circle-1 {
            width: 500px;
            height: 500px;
            top: -200px;
            right: -200px;
            background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
        }

        .bg-circle-2 {
            width: 350px;
            height: 350px;
            bottom: -150px;
            left: -150px;
            background: radial-gradient(circle, rgba(139,92,246,0.08) 0%, transparent 70%);
        }

        .bg-circle-3 {
            width: 300px;
            height: 300px;
            top: 30%;
            right: 15%;
            background: radial-gradient(circle, rgba(139,92,246,0.06) 0%, transparent 70%);
        }

        .bg-circle-4 {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 20%;
            background: radial-gradient(circle, rgba(236,72,153,0.05) 0%, transparent 70%);
        }

        .bg-grid {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: -1;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .animate-fade-up {
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-left {
            animation: fadeInLeft 0.5s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-right {
            animation: fadeInRight 0.5s ease-out forwards;
            opacity: 0;
        }

        .animate-scale {
            animation: scaleIn 0.5s ease-out forwards;
            opacity: 0;
        }

        .float-slow {
            animation: floatSlow 5s ease-in-out infinite;
        }

        /* ===== HEADER ===== */
        .header-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .univ-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.1));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.8rem;
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .header-section h1 {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, var(--primary-electric), var(--primary-glow));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .header-section p {
            color: var(--text-dim);
            font-size: 1.1rem;
        }

        /* ===== CARDS PREMIUM ===== */
        .card {
            border: none;
            border-radius: 32px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            height: 100%;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-electric), var(--primary-glow));
            transform: scaleX(0);
            transition: transform 0.5s ease;
        }

        .card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-glow), var(--primary-electric));
            transform: scaleX(0);
            transition: transform 0.5s ease 0.1s;
        }

        .card:hover::before,
        .card:hover::after {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-10px);
            border-color: rgba(139,92,246,0.5);
            box-shadow: 0 30px 45px -12px rgba(99,102,241,0.2);
        }

        .icon-bg {
            width: 110px;
            height: 110px;
            border-radius: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .icon-bg::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 38px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover .icon-bg::before {
            opacity: 1;
        }

        .icon-bg.user {
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            color: white;
            box-shadow: 0 15px 30px -8px rgba(79,70,229,0.4);
        }

        .icon-bg.admin {
            background: linear-gradient(135deg, #1e293b, #475569);
            color: white;
            box-shadow: 0 15px 30px -8px rgba(30,41,59,0.4);
        }

        .card:hover .icon-bg {
            transform: scale(1.08) rotate(5deg);
        }

        .card-title {
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #ffffff, var(--primary-electric));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .card-text {
            color: var(--text-dim);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        /* ===== FEATURE BADGES ===== */
        .feature-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(255,255,255,0.08);
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--text-dim);
            margin: 4px;
            transition: all 0.3s ease;
            border: 1px solid rgba(99,102,241,0.2);
        }

        .feature-badge:hover {
            background: rgba(99,102,241,0.15);
            transform: translateY(-2px);
            color: white;
            border-color: rgba(139,92,246,0.4);
        }

        .feature-badge i {
            font-size: 0.75rem;
        }

        /* ===== BUTTONS ===== */
        .btn-login {
            border-radius: 20px;
            padding: 14px 24px;
            font-weight: 700;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(95deg, #4f46e5, #7c3aed);
            border: none;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(95deg, #4338ca, #6d28d9);
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(79,70,229,0.5);
        }

        .btn-dark {
            background: linear-gradient(95deg, #1e293b, #334155);
            border: none;
            box-shadow: 0 8px 20px rgba(30,41,59,0.3);
        }

        .btn-dark:hover {
            background: linear-gradient(95deg, #0f172a, #1e293b);
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(30,41,59,0.5);
        }

        /* ===== STATS SECTION ===== */
        .stats-section {
            margin-top: 50px;
            padding-top: 40px;
            border-top: 1px solid rgba(99,102,241,0.2);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, var(--primary-electric));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-dim);
            margin-top: 5px;
        }

        /* ===== REGISTER LINK ===== */
        .register-link {
            text-align: center;
            margin-top: 40px;
        }

        .register-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: rgba(255,255,255,0.05);
            border-radius: 60px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99,102,241,0.3);
            font-size: 0.95rem;
        }

        .register-link a:hover {
            background: rgba(99,102,241,0.15);
            transform: translateY(-3px);
            gap: 15px;
            border-color: rgba(139,92,246,0.5);
        }

        /* ===== ALERT ===== */
        .alert-custom {
            border-radius: 20px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 30px;
            background: rgba(16,185,129,0.12);
            backdrop-filter: blur(8px);
            border-left: 4px solid #10b981;
            color: #a7f3d0;
        }

        .alert-custom i {
            color: #10b981;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 1.8rem;
            }
            .card-title {
                font-size: 1.3rem;
            }
            .icon-bg {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                border-radius: 25px;
            }
            .stats-section {
                margin-top: 40px;
            }
            .stat-number {
                font-size: 1.5rem;
            }
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        ::-webkit-scrollbar-thumb {
            background: #6366f1;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Background Decoration -->
    <div class="animated-bg"></div>
    <div class="noise"></div>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>
    <div class="bg-circle bg-circle-3"></div>
    <div class="bg-circle bg-circle-4"></div>
    <div class="bg-grid"></div>

    <div class="container">
        <!-- Header -->
        <div class="header-section animate-fade-up" style="animation-delay: 0.1s">
            <div class="univ-icon float-slow">
                <i class="fas fa-university"></i>
            </div>
            <h1>PMB Universitas</h1>
            <p>Penerimaan Mahasiswa Baru Tahun Akademik 2025/2026</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($success) && $success): ?>
        <div class="alert-custom animate-scale" style="animation-delay: 0.15s">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($message)): ?>
        <div class="alert-custom animate-scale" style="animation-delay: 0.15s">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Login Cards -->
        <div class="row justify-content-center g-5">
            <!-- User Card -->
            <div class="col-md-5 animate-fade-left" style="animation-delay: 0.2s">
                <div class="card user-card text-center p-4 h-100">
                    <div class="icon-bg user">
                        <i class="fas fa-user-graduate fa-3x"></i>
                    </div>
                    <h4 class="card-title">Mahasiswa</h4>
                    <p class="card-text">Akses dashboard untuk mengikuti test, lihat hasil, dan daftar ulang</p>
                    <div class="mb-3">
                        <span class="feature-badge"><i class="fas fa-file-alt"></i> Test Online</span>
                        <span class="feature-badge"><i class="fas fa-chart-line"></i> Lihat Hasil</span>
                        <span class="feature-badge"><i class="fas fa-clipboard-list"></i> Daftar Ulang</span>
                        <span class="feature-badge"><i class="fas fa-id-card"></i> Generate NIM</span>
                    </div>
                    <a href="user/login.php" class="btn btn-primary btn-login mt-auto">
                        <i class="fas fa-sign-in-alt"></i>Login sebagai Mahasiswa
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Admin Card -->
            <div class="col-md-5 animate-fade-right" style="animation-delay: 0.3s">
                <div class="card admin-card text-center p-4 h-100">
                    <div class="icon-bg admin">
                        <i class="fas fa-user-shield fa-3x"></i>
                    </div>
                    <h4 class="card-title">Administrator</h4>
                    <p class="card-text">Akses panel admin untuk kelola data pendaftar, soal test, dan laporan</p>
                    <div class="mb-3">
                        <span class="feature-badge"><i class="fas fa-users"></i> Kelola Data</span>
                        <span class="feature-badge"><i class="fas fa-question-circle"></i> Kelola Soal</span>
                        <span class="feature-badge"><i class="fas fa-chart-pie"></i> Laporan</span>
                        <span class="feature-badge"><i class="fas fa-cog"></i> Pengaturan</span>
                    </div>
                    <a href="admin/login.php" class="btn btn-dark btn-login mt-auto">
                        <i class="fas fa-sign-in-alt"></i>Login sebagai Admin
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="stats-section animate-fade-up" style="animation-delay: 0.35s">
            <div class="row justify-content-center">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Mahasiswa Terdaftar</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">5+</div>
                        <div class="stat-label">Program Studi</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Online System</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Akses Test</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Register Link -->
        <div class="register-link animate-fade-up" style="animation-delay: 0.4s">
            <a href="register.php">
                <i class="fas fa-user-plus"></i>
                Belum punya akun? Daftar di sini
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Footer Info -->
        <div class="text-center mt-5 animate-fade-up" style="animation-delay: 0.45s">
            <small class="text-white-50">
                <i class="fas fa-phone-alt me-1"></i> (0341) 551611
                <span class="mx-2">•</span>
                <i class="fas fa-envelope me-1"></i> info@pmb.universitas.ac.id
                <span class="mx-2">•</span>
                <i class="fas fa-clock me-1"></i> Layanan 24 Jam
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s, transform 0.5s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add ripple effect to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Only trigger ripple, not actual navigation since there are links inside
                const ripple = document.createElement('div');
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(99,102,241,0.3)';
                ripple.style.pointerEvents = 'none';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'scaleIn 0.5s ease-out';
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 500);
            });
        });
    </script>
</body>
</html>