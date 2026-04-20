<?php 
session_start();
include 'config/koneksi.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | BOOKSTORE Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg-body: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.85);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
        }

        [data-theme="dark"] {
            --bg-body: #020617;
            --card-bg: rgba(15, 23, 42, 0.85);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --white: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s, color 0.3s, border 0.3s; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Latar Belakang Dekoratif */
        .bg-blobs { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
        .blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%); border-radius: 50%; filter: blur(60px); animation: move 20s infinite alternate linear; }
        .blob-1 { top: -200px; right: -100px; }
        .blob-2 { bottom: -200px; left: -100px; opacity: 0.6; }
        @keyframes move { from { transform: translate(0, 0) scale(1); } to { transform: translate(100px, 50px) scale(1.1); } }

        /* Auth Card */
        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 40px;
            padding: 50px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            animation: cardEntrance 0.8s ease;
            position: relative;
        }
        @keyframes cardEntrance { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .theme-switch { position: absolute; top: 30px; right: 30px; width: 45px; height: 45px; border-radius: 15px; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .auth-header { text-align: center; margin-bottom: 35px; }
        .logo-circle { width: 60px; height: 60px; background: var(--primary); color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem; box-shadow: 0 10px 20px var(--primary-glow); transform: rotate(-10deg); }
        .auth-header h2 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1.5px; margin-bottom: 5px; }
        .auth-header p { color: var(--text-muted); font-weight: 500; }

        /* Form styling */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 8px; padding-left: 5px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; pointer-events: none; }
        .input-wrapper input { width: 100%; background: var(--bg-body); border: 2px solid var(--border); padding: 15px 15px 15px 48px; border-radius: 18px; color: var(--text-main); font-size: 0.95rem; font-weight: 600; outline: none; transition: 0.3s; }
        .input-wrapper input:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px var(--primary-glow); }

        /* Buttons */
        .btn-register { width: 100%; background: var(--primary); color: white; padding: 18px; border: none; border-radius: 20px; font-size: 1rem; font-weight: 800; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 20px var(--primary-glow); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-register:hover { transform: translateY(-3px); filter: brightness(1.1); }

        /* Button Kembali (Menyesuaikan login.php) */
        .btn-index { 
            width: 100%; 
            background: var(--white); 
            border: 2px solid var(--border); 
            padding: 15px; 
            border-radius: 20px; 
            color: var(--text-main); 
            font-weight: 700; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px; 
            transition: 0.3s;
        }
        .btn-index:hover { background: var(--text-main); color: var(--bg-body); border-color: var(--text-main); transform: translateY(-2px); }

        .auth-footer { margin-top: 30px; text-align: center; padding-top: 25px; border-top: 1px dashed var(--border); color: var(--text-muted); font-weight: 600; }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 800; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="auth-card">
        <div class="theme-switch" id="themeSwitcher" title="Ganti Mode">
            <i class="fas fa-moon"></i>
        </div>

        <div class="auth-header">
            <div class="logo-circle"><i class="fas fa-user-plus"></i></div>
            <h2>Daftar Akun</h2>
            <p>Mulai pengalaman membaca premium Anda</p>
        </div>

        <form action="proses_register.php" method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="far fa-id-card"></i>
                        <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="far fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="far fa-envelope"></i>
                        <input type="email" name="email" placeholder="email@mail.com" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Nomor Telepon / WhatsApp</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="no_telp" placeholder="Contoh: 08123456789" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Kata Sandi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Minimal 8 karakter" required>
                    </div>
                </div>
            </div>

            <button type="submit" name="register" class="btn-register">
                Buat Akun Sekarang <i class="fas fa-arrow-right"></i>
            </button>

            <a href="index.php" class="btn-index">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>

    <script>
        const themeSwitcher = document.getElementById('themeSwitcher');
        const htmlEl = document.documentElement;

        if (localStorage.getItem('theme') === 'dark') {
            htmlEl.setAttribute('data-theme', 'dark');
            themeSwitcher.innerHTML = '<i class="fas fa-sun"></i>';
        }

        themeSwitcher.addEventListener('click', () => {
            if (htmlEl.getAttribute('data-theme') === 'dark') {
                htmlEl.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeSwitcher.innerHTML = '<i class="fas fa-moon"></i>';
            } else {
                htmlEl.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeSwitcher.innerHTML = '<i class="fas fa-sun"></i>';
            }
        });
    </script>
</body>
</html>