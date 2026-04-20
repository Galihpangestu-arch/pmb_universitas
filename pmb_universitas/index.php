<?php
    require_once 'config/config.php';
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PMB Universitas - Pendaftaran Mahasiswa Baru</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #4f46e5;
                --primary-dark: #4338ca;
                --primary-light: #818cf8;
                --secondary: #6366f1;
                --success: #22c55e;
                --warning: #f59e0b;
                --danger: #ef4444;
                --dark: #0f172a;
                --light: #f8fafc;
                --gray: #64748b;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                overflow-x: hidden;
            }

            /* Navigation Enhanced */
            .navbar {
                background: linear-gradient(135deg, var(--dark), #1e293b);
                padding: 1rem 0;
                transition: all 0.3s ease;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }

            .navbar-brand {
                font-size: 1.5rem;
                font-weight: 800;
                background: linear-gradient(135deg, #fff, var(--primary-light));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .nav-link {
                font-weight: 500;
                transition: all 0.3s ease;
                position: relative;
            }

            .nav-link:hover {
                color: var(--primary-light) !important;
                transform: translateY(-2px);
            }

            /* Hero Section Enhanced */
            .hero-section {
                background: linear-gradient(135deg, rgba(15,23,42,0.9), rgba(79,70,229,0.8)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
                background-size: cover;
                background-position: center;
                color: white;
                padding: 120px 0;
                position: relative;
                overflow: hidden;
            }

            .hero-section::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -20%;
                width: 500px;
                height: 500px;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            }

            .hero-section::after {
                content: '';
                position: absolute;
                bottom: -30%;
                left: -10%;
                width: 400px;
                height: 400px;
                background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            }

            .hero-section h1 {
                font-size: 3.5rem;
                font-weight: 800;
                margin-bottom: 1.5rem;
                animation: fadeInUp 0.8s ease;
            }

            .hero-section .lead {
                font-size: 1.25rem;
                margin-bottom: 2rem;
                animation: fadeInUp 0.8s ease 0.2s both;
            }

            .hero-section .btn {
                animation: fadeInUp 0.8s ease 0.4s both;
            }

            /* Button Enhanced */
            .btn {
                border-radius: 14px;
                font-weight: 600;
                padding: 12px 32px;
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                position: relative;
                overflow: hidden;
            }

            .btn::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                transform: translate(-50%, -50%);
                transition: width 0.6s, height 0.6s;
            }

            .btn:hover::before {
                width: 300px;
                height: 300px;
            }

            .btn-primary {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                border: none;
                box-shadow: 0 5px 20px rgba(79,70,229,0.4);
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, var(--primary-dark), var(--primary));
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(79,70,229,0.5);
            }

            .btn-outline-light {
                border: 2px solid white;
                background: transparent;
            }

            .btn-outline-light:hover {
                background: white;
                color: var(--primary);
                transform: translateY(-3px);
            }

            /* Feature Cards Enhanced */
            .feature-card {
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                border: none;
                border-radius: 24px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                background: white;
                position: relative;
                overflow: hidden;
            }

            .feature-card::before {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary), var(--secondary));
                transform: scaleX(0);
                transition: transform 0.3s ease;
            }

            .feature-card:hover::before {
                transform: scaleX(1);
            }

            .feature-card:hover {
                transform: translateY(-15px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            }

            .feature-icon {
                font-size: 3.5rem;
                margin-bottom: 1.5rem;
                display: inline-block;
                transition: all 0.3s ease;
            }

            .feature-card:hover .feature-icon {
                transform: scale(1.1) rotate(5deg);
            }

            /* Stat Cards Enhanced */
            .stat-card {
                border-radius: 20px;
                padding: 25px;
                margin-bottom: 20px;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .stat-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
                border-radius: 20px;
            }

            .stat-card:hover {
                transform: translateY(-8px);
            }

            .stat-number {
                font-size: 3rem;
                font-weight: 800;
                margin-bottom: 10px;
                position: relative;
                z-index: 1;
            }

            .stat-card div:last-child {
                font-size: 0.9rem;
                opacity: 0.9;
                position: relative;
                z-index: 1;
            }

            /* Animation */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes float {
                0%, 100% {
                    transform: translateY(0px);
                }
                50% {
                    transform: translateY(-20px);
                }
            }

            .float-animation {
                animation: float 6s ease-in-out infinite;
            }

            /* Section Titles */
            .section-title {
                font-size: 2.5rem;
                font-weight: 800;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, var(--dark), var(--primary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .section-subtitle {
                font-size: 1.1rem;
                color: var(--gray);
                margin-bottom: 3rem;
            }

            /* Footer Enhanced */
            footer {
                background: linear-gradient(135deg, var(--dark), #1e293b);
                position: relative;
                overflow: hidden;
            }

            footer::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
            }

            /* Responsive */
            @media (max-width: 768px) {
                .hero-section {
                    padding: 80px 0;
                }
                .hero-section h1 {
                    font-size: 2rem;
                }
                .section-title {
                    font-size: 1.8rem;
                }
                .stat-number {
                    font-size: 2rem;
                }
            }

            /* Custom Scrollbar */
            ::-webkit-scrollbar {
                width: 10px;
            }

            ::-webkit-scrollbar-track {
                background: var(--light);
            }

            ::-webkit-scrollbar-thumb {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-university me-2"></i>PMB Universitas
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section text-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 mb-4">Pendaftaran Mahasiswa Baru</h1>
                        <p class="lead mb-4">Selamat datang di sistem pendaftaran mahasiswa baru universitas kami. Daftarkan dirimu sekarang dan raih masa depan gemilang!</p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                            </a>
                            <a href="#features" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-info-circle me-2"></i>Pelajari Lebih Lanjut
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-5" id="features">
            <div class="container">
                <div class="row text-center mb-5">
                    <div class="col">
                        <h2 class="section-title">Fitur Sistem PMB</h2>
                        <p class="section-subtitle">Sistem yang lengkap, modern, dan mudah digunakan</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card feature-card h-100 p-4">
                            <div class="card-body text-center">
                                <div class="feature-icon text-primary">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Registrasi Online</h4>
                                <p class="text-muted">Daftar secara online kapan saja dan di mana saja tanpa harus datang ke kampus. Proses cepat dan mudah.</p>
                                <div class="mt-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i> 24/7 Akses
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card h-100 p-4">
                            <div class="card-body text-center">
                                <div class="feature-icon text-success">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Test Online</h4>
                                <p class="text-muted">Ikuti test seleksi secara online dengan sistem yang terintegrasi dan hasil langsung diketahui.</p>
                                <div class="mt-3">
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2">
                                        <i class="fas fa-clock me-1"></i> Real-time Result
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card h-100 p-4">
                            <div class="card-body text-center">
                                <div class="feature-icon text-warning">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Daftar Ulang</h4>
                                <p class="text-muted">Lakukan daftar ulang secara online setelah dinyatakan lulus test dengan proses yang mudah.</p>
                                <div class="mt-3">
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2">
                                        <i class="fas fa-upload me-1"></i> Upload Dokumen
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="py-5 bg-light">
            <div class="container">
                <div class="row text-center mb-5">
                    <div class="col">
                        <h2 class="section-title">Statistik Pendaftaran</h2>
                        <p class="section-subtitle">Data terkini pendaftaran mahasiswa baru</p>
                    </div>
                </div>
                <div class="row g-4">
                    <?php
                    $stats_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status_pendaftaran = 'registrasi' THEN 1 ELSE 0 END) as registrasi,
                        SUM(CASE WHEN status_pendaftaran = 'test' THEN 1 ELSE 0 END) as test,
                        SUM(CASE WHEN status_pendaftaran = 'lulus' THEN 1 ELSE 0 END) as lulus,
                        SUM(CASE WHEN status_pendaftaran = 'daftar_ulang' THEN 1 ELSE 0 END) as daftar_ulang
                        FROM user";
                    $stats_result = $conn->query($stats_sql);
                    $stats = $stats_result->fetch_assoc();
                    ?>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-primary text-white">
                            <div class="stat-number">
                                <i class="fas fa-users me-2"></i><?php echo number_format($stats['total']); ?>
                            </div>
                            <div>Total Pendaftar</div>
                            <i class="fas fa-chart-line position-absolute bottom-0 end-0 p-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-info text-white">
                            <div class="stat-number">
                                <i class="fas fa-edit me-2"></i><?php echo number_format($stats['registrasi']); ?>
                            </div>
                            <div>Sedang Registrasi</div>
                            <i class="fas fa-user-plus position-absolute bottom-0 end-0 p-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-warning text-white">
                            <div class="stat-number">
                                <i class="fas fa-file-alt me-2"></i><?php echo number_format($stats['test']); ?>
                            </div>
                            <div>Sedang Test</div>
                            <i class="fas fa-laptop position-absolute bottom-0 end-0 p-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-success text-white">
                            <div class="stat-number">
                                <i class="fas fa-graduation-cap me-2"></i><?php echo number_format($stats['lulus']); ?>
                            </div>
                            <div>Lulus Test</div>
                            <i class="fas fa-trophy position-absolute bottom-0 end-0 p-3 opacity-25"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="alert alert-primary border-0 rounded-4 shadow-sm">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="py-5" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
            <div class="container text-center text-white">
                <h2 class="fw-bold mb-3">Siap Memulai Perjalanan Akademik Anda?</h2>
                <p class="lead mb-4 opacity-90">Bergabunglah dengan ribuan mahasiswa lainnya dan raih masa depan cerah bersama kami</p>
                <a href="register.php" class="btn btn-light btn-lg px-5">
                    <i class="fas fa-arrow-right me-2"></i>Daftar Sekarang
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="text-white py-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-university me-2"></i>PMB Universitas
                        </h5>
                        <p class="opacity-75">Sistem Pendaftaran Mahasiswa Baru Online yang modern, cepat, dan terpercaya.</p>
                        <div class="mt-3">
                            <a href="#" class="text-white me-3 opacity-75 hover-opacity-100">
                                <i class="fab fa-facebook fa-lg"></i>
                            </a>
                            <a href="#" class="text-white me-3 opacity-75 hover-opacity-100">
                                <i class="fab fa-twitter fa-lg"></i>
                            </a>
                            <a href="#" class="text-white me-3 opacity-75 hover-opacity-100">
                                <i class="fab fa-instagram fa-lg"></i>
                            </a>
                            <a href="#" class="text-white opacity-75 hover-opacity-100">
                                <i class="fab fa-youtube fa-lg"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4 mb-md-0">
                        <h6 class="fw-bold mb-3">Tautan Cepat</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-text-white">Beranda</a></li>
                            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-text-white">Program Studi</a></li>
                            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-text-white">Fasilitas</a></li>
                            <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-text-white">Kontak</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6 class="fw-bold mb-3">Kontak</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Jl. Pendidikan No. 123</li>
                            <li class="mb-2"><i class="fas fa-phone me-2"></i> (021) 12345678</li>
                            <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@pmb.universitas.ac.id</li>
                        </ul>
                    </div>
                </div>
                <hr class="mt-4 mb-3 opacity-25">
                <div class="row">
                    <div class="col text-center">
                        <p class="mb-0 opacity-75">&copy; <?php echo date('Y'); ?> PMB Universitas. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Navbar background change on scroll
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.background = 'linear-gradient(135deg, var(--dark), #1e293b)';
                    navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.2)';
                } else {
                    navbar.style.background = 'linear-gradient(135deg, var(--dark), #1e293b)';
                }
            });

            // Animate stat numbers
            const animateNumbers = () => {
                const statNumbers = document.querySelectorAll('.stat-number');
                statNumbers.forEach(stat => {
                    const text = stat.innerText;
                    const number = parseInt(text.replace(/[^0-9]/g, ''));
                    if (number && !stat.hasAttribute('data-animated')) {
                        stat.setAttribute('data-animated', 'true');
                        let current = 0;
                        const increment = number / 50;
                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= number) {
                                current = number;
                                clearInterval(timer);
                            }
                            stat.innerHTML = stat.innerHTML.replace(/[0-9,]+/, Math.floor(current).toLocaleString());
                        }, 20);
                    }
                });
            };

            // Trigger animation when stats section is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateNumbers();
                        observer.unobserve(entry.target);
                    }
                });
            });

            const statsSection = document.querySelector('.bg-light');
            if (statsSection) {
                observer.observe(statsSection);
            }

            // Add hover effect style
            const style = document.createElement('style');
            style.textContent = `
                .hover-text-white:hover {
                    color: white !important;
                }
                .hover-opacity-100:hover {
                    opacity: 1 !important;
                }
            `;
            document.head.appendChild(style);
        </script>
    </body>
    </html>