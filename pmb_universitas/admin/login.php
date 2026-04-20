<?php
// admin/login.php - Login khusus admin
require_once '../config/config.php';

// Jika sudah login sebagai admin, redirect ke dashboard admin
if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Cek di tabel admin
    $sql = "SELECT * FROM admin WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        // Periksa password (asumsi plain text atau MD5)
        if ($admin['password'] == $password || password_verify($password, $admin['password'])) {
            // Set session admin
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            $_SESSION['role'] = 'admin';
            
            // Hapus session user jika ada
            unset($_SESSION['user_id']);
            
            setFlash('success', 'Selamat datang, Admin ' . $admin['nama_lengkap'] . '!');
            redirect('dashboard.php');
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username admin tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --dark-bg: #0f0c29;
            --mid-bg: #302b63;
            --light-bg: #24243e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--mid-bg) 50%, var(--light-bg) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 60px 0;
            position: relative;
        }

        /* ===== ANIMATIONS ===== */
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

        /* ===== BACKGROUND DECORATION ===== */
        .bg-decoration {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 0;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
        }

        .bg-circle-1 {
            width: 500px;
            height: 500px;
            top: -200px;
            right: -200px;
            background: radial-gradient(circle, rgba(79,70,229,0.12) 0%, transparent 70%);
        }

        .bg-circle-2 {
            width: 350px;
            height: 350px;
            bottom: -150px;
            left: -150px;
            background: radial-gradient(circle, rgba(99,102,241,0.08) 0%, transparent 70%);
        }

        .bg-circle-3 {
            width: 280px;
            height: 280px;
            top: 50%;
            right: 5%;
            background: radial-gradient(circle, rgba(139,92,246,0.06) 0%, transparent 70%);
        }

        .bg-circle-4 {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 10%;
            background: radial-gradient(circle, rgba(236,72,153,0.05) 0%, transparent 70%);
        }

        /* ===== LOGIN CARD PREMIUM ===== */
        .login-card {
            background: rgba(255,255,255,0.98);
            border-radius: 48px;
            box-shadow: 0 40px 60px -20px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.2);
            overflow: hidden;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 50px 70px -25px rgba(0,0,0,0.35);
        }

        /* Corner装饰 */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(79,70,229,0.05) 0%, transparent 100%);
            border-radius: 0 0 150px 0;
            pointer-events: none;
        }

        .login-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(315deg, rgba(79,70,229,0.05) 0%, transparent 100%);
            border-radius: 150px 0 0 0;
            pointer-events: none;
        }

        /* ===== FORM STYLES ===== */
        .login-body {
            padding: 50px;
        }

        .form-group-modern {
            margin-bottom: 25px;
        }

        .form-label-modern {
            font-weight: 600;
            font-size: 0.75rem;
            margin-bottom: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
        }

        .required-field::after {
            content: " *";
            color: #ef4444;
            font-weight: bold;
        }

        .input-group-modern {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            z-index: 2;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .form-control-modern {
            width: 100%;
            padding: 15px 18px 15px 52px;
            border: 2px solid #eef2ff;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: #fafbff;
            font-family: 'Inter', sans-serif;
        }

        .form-control-modern:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(79,70,229,0.08);
            outline: none;
        }

        .form-control-modern:focus + .input-icon {
            color: var(--primary);
        }

        .form-control-modern:hover {
            border-color: #c7d2fe;
        }

        /* ===== BUTTON PREMIUM ===== */
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 20px;
            padding: 16px 24px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.2s ease;
            width: 100%;
            color: white;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -8px rgba(79,70,229,0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ===== INFO SIDE PREMIUM ===== */
        .info-side {
            background: linear-gradient(135deg, #1e1b4b 0%, #2d2a6e 100%);
            color: white;
            padding: 50px;
            height: 100%;
            position: relative;
        }

        .info-side::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="2"/><circle cx="50" cy="50" r="60" fill="none" stroke="rgba(255,255,255,0.02)" stroke-width="1"/></svg>') repeat;
            opacity: 0.5;
            pointer-events: none;
        }

        .feature-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 16px 18px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .feature-card:hover {
            background: rgba(255,255,255,0.12);
            transform: translateX(6px);
            border-color: rgba(255,255,255,0.2);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.05));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        /* ===== ALERT STYLES ===== */
        .alert-custom {
            border-radius: 20px;
            border: none;
            padding: 16px 20px;
            animation: slideDown 0.4s ease-out;
            margin-bottom: 25px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        /* ===== TITLE STYLES ===== */
        .login-title {
            background: linear-gradient(135deg, #1f2937, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        .badge-admin {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 8px 24px;
            border-radius: 60px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            color: white;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(79,70,229,0.3);
        }

        /* Divider */
        .divider-custom {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }

        .divider-custom::before,
        .divider-custom::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }

        .divider-custom span {
            padding: 0 15px;
            color: #9ca3af;
            font-size: 0.8rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .login-body, .info-side {
                padding: 35px;
            }
            .login-card {
                border-radius: 32px;
            }
        }

        @media (max-width: 768px) {
            .login-body, .info-side {
                padding: 25px;
            }
            .form-control-modern {
                padding: 12px 15px 12px 45px;
            }
            body {
                padding: 30px 0;
            }
            .login-card {
                border-radius: 28px;
            }
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Background Decoration -->
    <div class="bg-decoration">
        <div class="bg-circle bg-circle-1"></div>
        <div class="bg-circle bg-circle-2"></div>
        <div class="bg-circle bg-circle-3"></div>
        <div class="bg-circle bg-circle-4"></div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-card animate-scale">
                    <div class="row g-0">
                        <!-- Form Login -->
                        <div class="col-md-7">
                            <div class="login-body">
                                <div class="text-center mb-4 animate-fade-up">
                                    <div class="badge-admin mb-3">
                                        <i class="fas fa-user-shield me-2"></i>Administrator Panel
                                    </div>
                                    <h3 class="mt-2 fw-bold login-title" style="font-size: 1.8rem;">Login Admin</h3>
                                    <p class="text-muted mt-2">Masuk ke panel administrator PMB Universitas</p>
                                </div>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-custom alert-danger animate-fade-up">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php showFlash(); ?>
                                
                                <form method="POST" action="" class="animate-fade-up" style="animation-delay: 0.1s">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern required-field">Username Admin</label>
                                        <div class="input-group-modern">
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-control-modern" name="username" placeholder="Masukkan username admin" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-modern">
                                        <label class="form-label-modern required-field">Password</label>
                                        <div class="input-group-modern">
                                            <i class="fas fa-lock input-icon"></i>
                                            <input type="password" class="form-control-modern" name="password" placeholder="Masukkan password" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-login">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login sebagai Admin
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </form>
                                
                                <div class="divider-custom">
                                    <span>atau</span>
                                </div>
                                
                                <div class="text-center animate-fade-up" style="animation-delay: 0.2s">
                                    <a href="../index.php" class="text-muted text-decoration-none small">
                                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info Side Premium -->
                        <div class="col-md-5">
                            <div class="info-side">
                                <div class="text-center mb-4 animate-fade-right">
                                    <div class="feature-icon mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.05)); border-radius: 28px;">
                                        <i class="fas fa-university fa-3x"></i>
                                    </div>
                                    <h4 class="fw-bold mb-2" style="font-size: 1.6rem;">PMB Universitas</h4>
                                    <p class="opacity-75">Sistem Pendaftaran Mahasiswa Baru</p>
                                </div>
                                
                                <div class="mt-4 animate-fade-right" style="animation-delay: 0.1s">
                                    <h5 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2" style="color: #fbbf24;"></i> Panel Admin</h5>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon">
                                            <i class="fas fa-users fa-fw"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Kelola Calon Maba</h6>
                                            <small class="opacity-75">Tambah, edit, hapus data</small>
                                        </div>
                                    </div>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon">
                                            <i class="fas fa-question-circle fa-fw"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Kelola Soal Test</h6>
                                            <small class="opacity-75">Buat dan kelola soal</small>
                                        </div>
                                    </div>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon">
                                            <i class="fas fa-chart-line fa-fw"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Lihat Hasil Test</h6>
                                            <small class="opacity-75">Analisis hasil seleksi</small>
                                        </div>
                                    </div>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon">
                                            <i class="fas fa-chart-pie fa-fw"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Laporan Lengkap</h6>
                                            <small class="opacity-75">Export data ke Excel/PDF</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-3 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                                    <div class="d-flex gap-3 flex-wrap justify-content-between">
                                        <div>
                                            <i class="fas fa-phone-alt me-2"></i>
                                            <small>(0341) 551611</small>
                                        </div>
                                        <div>
                                            <i class="fas fa-envelope me-2"></i>
                                            <small>admin@pmb.ac.id</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>