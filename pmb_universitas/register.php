<?php
// register.php
require_once 'config/config.php';

// Jika sudah login, redirect ke dashboard masing-masing
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CEK APAKAH DATA POST ADA SEBELUM DIGUNAKAN
    $username = isset($_POST['username']) ? $conn->real_escape_string($_POST['username']) : '';
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $nama_lengkap = isset($_POST['nama_lengkap']) ? $conn->real_escape_string($_POST['nama_lengkap']) : '';
    
    // Field tambahan - cek keberadaan data
    $no_hp = isset($_POST['no_hp']) ? $conn->real_escape_string($_POST['no_hp']) : '';
    $alamat = isset($_POST['alamat']) ? $conn->real_escape_string($_POST['alamat']) : '';
    $tanggal_lahir = isset($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '';
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $conn->real_escape_string($_POST['jenis_kelamin']) : '';
    $asal_sekolah = isset($_POST['asal_sekolah']) ? $conn->real_escape_string($_POST['asal_sekolah']) : '';
    $tahun_lulus = isset($_POST['tahun_lulus']) ? (int)$_POST['tahun_lulus'] : 0;
    $program_studi = isset($_POST['program_studi']) ? $conn->real_escape_string($_POST['program_studi']) : 'Teknik Informatika';
    
    // Validasi semua field wajib diisi
    if (empty($username) || empty($email) || empty($password) || empty($nama_lengkap) ||
        empty($no_hp) || empty($alamat) || empty($tanggal_lahir) || empty($jenis_kelamin) ||
        empty($asal_sekolah) || empty($tahun_lulus)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek username/email sudah terdaftar
        $check = $conn->query("SELECT id_user FROM user WHERE username = '$username' OR email = '$email'");
        
        if ($check->num_rows > 0) {
            $error = 'Username atau email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO user (username, password, email, nama_lengkap, no_hp, alamat, tanggal_lahir, jenis_kelamin, asal_sekolah, tahun_lulus, program_studi, status_pendaftaran) 
                    VALUES ('$username', '$hashed_password', '$email', '$nama_lengkap', '$no_hp', '$alamat', '$tanggal_lahir', '$jenis_kelamin', '$asal_sekolah', '$tahun_lulus', '$program_studi', 'registrasi')";
            
            if ($conn->query($sql)) {
                setFlash('success', 'Registrasi berhasil! Silahkan login untuk melanjutkan.');
                redirect('login.php');
            } else {
                $error = 'Registrasi gagal: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Daftar | PMB Universitas Nusantara</title>
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

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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

        .animate-fade-right {
            animation: fadeInRight 0.5s ease-out forwards;
            opacity: 0;
        }

        .animate-scale {
            animation: scaleIn 0.5s ease-out forwards;
            opacity: 0;
        }

        /* Glass Card Premium */
        .register-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(16px);
            border-radius: 48px;
            border: 1px solid var(--border-glass);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.4);
        }

        .register-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139,92,246,0.5);
            box-shadow: 0 30px 55px -12px rgba(99,102,241,0.2);
        }

        /* Form Styles */
        .register-body {
            padding: 48px;
        }

        .form-group-modern {
            margin-bottom: 20px;
        }

        .form-label-modern {
            font-weight: 600;
            font-size: 0.75rem;
            margin-bottom: 8px;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
        }

        .required-field::after {
            content: " *";
            color: var(--accent-rose);
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
            color: var(--text-dim);
            z-index: 2;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .form-control-modern, .form-select-modern {
            width: 100%;
            padding: 14px 18px 14px 52px;
            border: 1.5px solid rgba(99, 102, 241, 0.3);
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: rgba(10, 12, 21, 0.6);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
        }

        textarea.form-control-modern {
            padding: 14px 18px 14px 52px;
            min-height: 90px;
            resize: vertical;
        }

        .form-control-modern:focus, .form-select-modern:focus {
            border-color: var(--primary-electric);
            background: rgba(10, 12, 21, 0.8);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.15);
            outline: none;
        }

        .form-control-modern:hover, .form-select-modern:hover {
            border-color: rgba(139,92,246,0.5);
        }

        .form-control-modern:focus + .input-icon,
        .form-select-modern:focus + .input-icon {
            color: var(--primary-electric);
        }

        .form-select-modern {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 18px center;
            background-repeat: no-repeat;
            background-size: 18px;
            padding-right: 45px;
            cursor: pointer;
        }

        /* Enhanced Select Option Styling */
        .form-select-modern option {
            background: #1e293b;
            color: #f1f5f9;
            padding: 12px;
            font-size: 0.9rem;
        }

        /* Custom Select Wrapper for better UX */
        .select-enhanced {
            position: relative;
        }

        /* Button Premium */
        .btn-register {
            background: linear-gradient(95deg, #4f46e5, #7c3aed);
            border: none;
            border-radius: 60px;
            padding: 14px 24px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            color: white;
            margin-top: 15px;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(79,70,229,0.5);
            color: white;
        }

        /* Info Side */
        .info-side {
            background: linear-gradient(135deg, rgba(79,70,229,0.15), rgba(139,92,246,0.08));
            backdrop-filter: blur(8px);
            padding: 48px;
            height: 100%;
            border-left: 1px solid rgba(99,102,241,0.3);
        }

        .feature-card {
            background: rgba(255,255,255,0.05);
            border-radius: 24px;
            padding: 16px 18px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            border: 1px solid rgba(99,102,241,0.2);
        }

        .feature-card:hover {
            background: rgba(99,102,241,0.12);
            transform: translateX(6px);
            border-color: rgba(139,92,246,0.4);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.1));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary-electric);
        }

        /* Alert Styles */
        .alert-custom {
            border-radius: 20px;
            border: none;
            padding: 16px 20px;
            animation: slideDown 0.4s ease-out;
            margin-bottom: 25px;
            background: rgba(239,68,68,0.15);
            backdrop-filter: blur(8px);
            color: #fecaca;
            border-left: 4px solid #ef4444;
        }

        .badge-new {
            background: linear-gradient(135deg, var(--primary-electric), var(--primary-glow));
            padding: 8px 24px;
            border-radius: 60px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            color: white;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }

        .registration-title {
            background: linear-gradient(135deg, #ffffff, var(--primary-electric), var(--primary-glow));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 800;
        }

        .divider-custom {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
        }

        .divider-custom::before,
        .divider-custom::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(99,102,241,0.3);
        }

        .divider-custom span {
            padding: 0 15px;
            color: var(--text-dim);
            font-size: 0.8rem;
        }

        /* Program Studi Cards Style for better visual */
        .prodi-option-preview {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .register-body, .info-side { padding: 35px; }
            .register-card { border-radius: 32px; }
        }

        @media (max-width: 768px) {
            .register-body, .info-side { padding: 25px; }
            .form-control-modern, .form-select-modern { padding: 12px 15px 12px 45px; }
            body { padding: 20px 0; }
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #6366f1; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    <div class="noise"></div>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="register-card animate-scale">
                    <div class="row g-0">
                        <!-- Form Registrasi -->
                        <div class="col-md-7">
                            <div class="register-body">
                                <div class="text-center mb-4 animate-fade-up">
                                    <div class="badge-new mb-3">
                                        <i class="fas fa-graduation-cap me-2"></i>Pendaftaran Online
                                    </div>
                                    <h3 class="mt-2 fw-bold registration-title" style="font-size: 1.8rem;">Daftar Calon Mahasiswa</h3>
                                    <p class="text-white-50 mt-2">Isi data dengan benar untuk membuat akun pendaftaran</p>
                                </div>
                                
                                <?php if ($error): ?>
                                    <div class="alert-custom animate-fade-up">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php showFlash(); ?>
                                
                                <form method="POST" action="" class="animate-fade-up" style="animation-delay: 0.1s">
                                    <div class="form-group-modern">
                                        <label class="form-label-modern required-field">Nama Lengkap</label>
                                        <div class="input-group-modern">
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-control-modern" name="nama_lengkap" placeholder="Masukkan nama lengkap Anda" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Username</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-at input-icon"></i>
                                                    <input type="text" class="form-control-modern" name="username" placeholder="Masukkan username" required>
                                                </div>
                                                <small class="text-white-50" style="font-size: 0.7rem;">Username akan digunakan untuk login</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Email</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-envelope input-icon"></i>
                                                    <input type="email" class="form-control-modern" name="email" placeholder="contoh: email@domain.com" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">No. HP</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-phone input-icon"></i>
                                                    <input type="text" class="form-control-modern" name="no_hp" placeholder="Masukkan nomor HP aktif" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Tanggal Lahir</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-calendar-alt input-icon"></i>
                                                    <input type="date" class="form-control-modern" name="tanggal_lahir" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Jenis Kelamin</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-venus-mars input-icon"></i>
                                                    <select class="form-select-modern" name="jenis_kelamin" required>
                                                        <option value="" disabled selected>Pilih Jenis Kelamin</option>
                                                        <option value="L">👨‍🦱 Laki-laki</option>
                                                        <option value="P">👩‍🦰 Perempuan</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Program Studi</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-graduation-cap input-icon"></i>
                                                    <select class="form-select-modern" name="program_studi" required>
                                                        <option value="" disabled selected>🎓 Pilih Program Studi</option>
                                                        <option value="Teknik Informatika">💻 Teknik Informatika</option>
                                                        <option value="Sistem Informasi">📊 Sistem Informasi</option>
                                                        <option value="Manajemen">📈 Manajemen</option>
                                                        <option value="Akuntansi">💰 Akuntansi</option>
                                                        <option value="Hukum">⚖️ Hukum</option>
                                                        <option value="Psikologi">🧠 Psikologi</option>
                                                        <option value="Desain Komunikasi Visual">🎨 Desain Komunikasi Visual</option>
                                                        <option value="Teknik Sipil">🏗️ Teknik Sipil</option>
                                                        <option value="Teknik Elektro">⚡ Teknik Elektro</option>
                                                        <option value="Kedokteran">🩺 Kedokteran</option>
                                                    </select>
                                                </div>
                                                <small class="text-white-50" style="font-size: 0.7rem;">Pilih program studi yang sesuai dengan minat Anda</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-modern">
                                        <label class="form-label-modern required-field">Alamat</label>
                                        <div class="input-group-modern">
                                            <i class="fas fa-home input-icon"></i>
                                            <textarea class="form-control-modern" name="alamat" rows="2" placeholder="Masukkan alamat lengkap" required></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Asal Sekolah</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-school input-icon"></i>
                                                    <input type="text" class="form-control-modern" name="asal_sekolah" placeholder="Nama sekolah asal" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Tahun Lulus</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-calendar-check input-icon"></i>
                                                    <select class="form-select-modern" name="tahun_lulus" required>
                                                        <option value="" disabled selected>Pilih Tahun Lulus</option>
                                                        <?php for($year = 2025; $year >= 2015; $year--): ?>
                                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Password</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-lock input-icon"></i>
                                                    <input type="password" class="form-control-modern" name="password" placeholder="Minimal 6 karakter" required>
                                                </div>
                                                <small class="text-white-50" style="font-size: 0.7rem;">Minimal 6 karakter</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label class="form-label-modern required-field">Konfirmasi Password</label>
                                                <div class="input-group-modern">
                                                    <i class="fas fa-check-circle input-icon"></i>
                                                    <input type="password" class="form-control-modern" name="confirm_password" placeholder="Ulangi password" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-register">
                                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </form>
                                
                                <div class="divider-custom">
                                    <span>atau</span>
                                </div>
                                
                                <div class="text-center animate-fade-up" style="animation-delay: 0.2s">
                                    <p>Sudah punya akun? <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--primary-electric);">Login disini</a></p>
                                    <a href="index.php" class="text-white-50 text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info Side Premium -->
                        <div class="col-md-5">
                            <div class="info-side">
                                <div class="text-center mb-4 animate-fade-right">
                                    <div class="feature-icon mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.15)); border-radius: 28px;">
                                        <i class="fas fa-university fa-3x"></i>
                                    </div>
                                    <h4 class="fw-bold mb-2" style="font-size: 1.6rem;">PMB Universitas</h4>
                                    <p class="text-white-50">Sistem Pendaftaran Mahasiswa Baru</p>
                                </div>
                                
                                <div class="mt-4 animate-fade-right" style="animation-delay: 0.1s">
                                    <h5 class="fw-bold mb-3"><i class="fas fa-star me-2" style="color: #fbbf24;"></i> Keuntungan Mendaftar</h5>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon"><i class="fas fa-bolt fa-fw"></i></div>
                                        <div><h6 class="fw-bold mb-1">Proses Cepat & Mudah</h6><small class="text-white-50">Registrasi hanya 5 menit</small></div>
                                    </div>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon"><i class="fas fa-laptop-code fa-fw"></i></div>
                                        <div><h6 class="fw-bold mb-1">Tes Online Terintegrasi</h6><small class="text-white-50">Tes langsung setelah registrasi</small></div>
                                    </div>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon"><i class="fas fa-chart-line fa-fw"></i></div>
                                        <div><h6 class="fw-bold mb-1">Hasil Langsung Diketahui</h6><small class="text-white-50">Nilai test langsung keluar</small></div>
                                    </div>
                                    
                                    <div class="feature-card d-flex align-items-center gap-3">
                                        <div class="feature-icon"><i class="fas fa-id-card fa-fw"></i></div>
                                        <div><h6 class="fw-bold mb-1">Dapat NIM Langsung</h6><small class="text-white-50">Setelah lulus daftar ulang</small></div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-3 border-top" style="border-color: rgba(99,102,241,0.2) !important;">
                                    <div class="d-flex gap-3 flex-wrap justify-content-between">
                                        <div><i class="fas fa-phone-alt me-2"></i><small>(0341) 551611</small></div>
                                        <div><i class="fas fa-envelope me-2"></i><small>pmb@universitas.ac.id</small></div>
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
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-custom, .alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>