<?php
session_start();
// Proteksi: Hanya Admin yang bisa masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Mendapatkan nama file saat ini untuk logika Sidebar Active
$current_page = basename($_SERVER['PHP_SELF']);

$username_session = $_SESSION['username'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_session'");
$data = mysqli_fetch_assoc($query);

// --- LOGIKA UPDATE PROFIL ---
if (isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_baru = mysqli_real_escape_string($conn, $_POST['username']);

    $update = mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama', email='$email', username='$user_baru' WHERE username='$username_session'");
    if ($update) {
        $_SESSION['username'] = $user_baru; // Update session jika username ganti
        echo "<script>alert('Profil Berhasil Diperbarui!'); window.location='pengaturan.php';</script>";
    }
}

// --- LOGIKA UPDATE PASSWORD ---
if (isset($_POST['update_password'])) {
    $pw_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    if ($pw_baru === $konfirmasi) {
        $password_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $update_pw = mysqli_query($conn, "UPDATE users SET password='$password_hash' WHERE username='$username_session'");
        if ($update_pw) {
            echo "<script>alert('Password Berhasil Diganti!'); window.location='pengaturan.php';</script>";
        }
    } else {
        echo "<script>alert('Konfirmasi Password Tidak Cocok!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun | Admin Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --sidebar-grad: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            --sidebar-width: 280px;
            --bg-main: #f8fafc;
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --input-bg: #fcfcfc;
            --danger: #ef4444;
        }

        [data-theme="dark"] {
            --bg-main: #020617; --white: #1e293b; --text-dark: #f8fafc;
            --text-muted: #94a3b8; --border: #334155; --card-bg: #0f172a; --input-bg: #020617;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s, color 0.3s; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- SIDEBAR (SAMA PERSIS) --- */
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-grad); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 45px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; }
        .logo span { color: #38bdf8; }
        .sidebar-menu { padding: 30px 15px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; }
        .menu-item i { width: 30px; font-size: 1.1rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }

        .nav-right { display: flex; align-items: center; gap: 20px; }
        .clock-container { text-align: right; border-right: 1px solid var(--border); padding-right: 20px; }
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); }
        #real-date { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .theme-toggle {
            cursor: pointer; width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-main); border: 1px solid var(--border);
            color: var(--text-dark); transition: 0.3s;
        }

        /* --- SETTINGS GRID --- */
        .content-body { padding: 40px 50px; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
        .card { background: var(--card-bg); padding: 35px; border-radius: 32px; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .card h3 { font-size: 1.3rem; font-weight: 800; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; }
        .subtitle { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 30px; display: block; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; font-weight: 800; margin-bottom: 8px; color: var(--text-muted); text-transform: uppercase; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        input { 
            width: 100%; padding: 14px 15px 14px 45px; border-radius: 14px; border: 1.5px solid var(--border); 
            background: var(--input-bg); color: var(--text-dark); outline: none; font-size: 0.95rem;
        }
        input:focus { border-color: var(--primary); }

        .btn-submit { 
            background: var(--primary); color: white; border: none; padding: 16px; border-radius: 14px; 
            font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px var(--primary-glow); }
        .btn-danger { background: var(--danger); }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; } .sidebar span, .sidebar-header { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><a href="dashboard.php" class="logo">ADMIN<span>PRO.</span></a></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
            </a>
            <a href="kategori.php" class="menu-item <?= ($current_page == 'kategori.php') ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> <span>Kategori Buku</span>
            </a>
            <a href="kelola_buku.php" class="menu-item <?= ($current_page == 'kelola_buku.php') ? 'active' : '' ?>">
                <i class="fas fa-book-bookmark"></i> <span>Koleksi Buku</span>
            </a>
            <a href="kelola_pelanggan.php" class="menu-item <?= ($current_page == 'kelola_pelanggan.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> <span>Database User</span>
            </a>
            <a href="pesanan.php" class="menu-item <?= ($current_page == 'pesanan.php') ? 'active' : '' ?>">
                <i class="fas fa-shopping-bag"></i> <span>Order Masuk</span>
            </a>
            <a href="laporan.php" class="menu-item <?= ($current_page == 'laporan.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> <span>Laporan Omzet</span>
            </a>
            <a href="pengaturan.php" class="menu-item <?= ($current_page == 'pengaturan.php') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700; font-size: 1.1rem;">
                Account <span style="color:var(--primary);">Settings</span>
            </div>
            
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00:00:00</span>
                    <span id="real-date">Memuat...</span>
                </div>
                <div class="theme-toggle" id="themeSwitcher">
                    <i class="fas fa-moon"></i>
                </div>
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);">
            </div>
        </header>

        <div class="content-body">
            <div class="settings-grid">
                <div class="card">
                    <h3><i class="fas fa-user-circle" style="color: var(--primary);"></i> Profil Admin</h3>
                    <span class="subtitle">Kelola informasi data diri administrator.</span>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <div class="input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?= htmlspecialchars($data['email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" value="<?= htmlspecialchars($data['username']); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_profil" class="btn-submit">Update Profil</button>
                    </form>
                </div>

                <div class="card">
                    <h3><i class="fas fa-shield-alt" style="color: var(--danger);"></i> Keamanan</h3>
                    <span class="subtitle">Ubah password secara berkala untuk keamanan.</span>
                    <form method="POST">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key"></i>
                                <input type="password" name="password_baru" placeholder="Masukkan password baru" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="konfirmasi_password" placeholder="Ulangi password baru" required>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="btn-submit btn-danger">Ganti Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function runClock() {
            const now = new Date();
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(runClock, 1000); runClock();

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