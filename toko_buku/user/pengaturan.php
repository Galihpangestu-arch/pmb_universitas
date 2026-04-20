<?php
session_start();
include '../config/koneksi.php';

// Proteksi: Wajib Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil Data User
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);
$full_name = $data_user['nama_lengkap'] ?? 'User';
$first_name = !empty($full_name) ? explode(' ', trim($full_name))[0] : 'User';

// Logika Update Profil
if (isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);

    $update = mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama', email='$email', no_telp='$no_telp' WHERE id='$id_user'");
    if ($update) {
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='pengaturan.php';</script>";
    }
}

// Logika Update Password
if (isset($_POST['update_password'])) {
    $pass_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    if ($pass_baru === $konfirmasi) {
        $pass_hashed = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$pass_hashed' WHERE id='$id_user'");
        echo "<script>alert('Password berhasil diganti!'); window.location='pengaturan.php';</script>";
    } else {
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    }
}

// Hitung Badge Keranjang & Wishlist
$jumlah_keranjang = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jumlah) { $jumlah_keranjang += $jumlah; }
}
$q_wishlist_count = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user'");
$jumlah_wishlist = mysqli_num_rows($q_wishlist_count);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan | BookStore Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --sidebar-grad: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            --bg-main: #f8fafc;
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --sidebar-width: 280px;
        }

        [data-theme="dark"] {
            --bg-main: #020617;
            --white: #1e293b;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --card-bg: #0f172a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s, color 0.3s; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-grad); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 45px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; text-transform: uppercase; }
        .logo span { color: #38bdf8; }
        
        .sidebar-menu { padding: 30px 15px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }

        /* --- NAV RIGHT --- */
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .nav-icon-link { position: relative; color: var(--text-dark); text-decoration: none; font-size: 1.1rem; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--border); transition: 0.3s; }
        .nav-badge { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; border: 2px solid var(--card-bg); font-weight: 800; }

        .clock-container { text-align: right; border-right: 1px solid var(--border); padding-right: 20px; margin-right: 5px; }
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); display: block; }
        #real-date { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .theme-toggle { cursor: pointer; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); }

        /* --- CONTENT BODY --- */
        .content-body { padding: 40px 50px; max-width: 1200px; }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }

        .glass-card { background: var(--card-bg); border-radius: 32px; padding: 40px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .card-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; color: var(--text-dark); }
        .card-title i { width: 45px; height: 45px; background: rgba(99, 102, 241, 0.1); color: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; }

        /* --- FORM --- */
        .form-group { margin-bottom: 24px; }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        
        input { 
            width: 100%; padding: 16px 20px 16px 52px; border-radius: 18px; border: 2px solid var(--border); 
            background: var(--bg-main); transition: 0.3s; font-size: 0.95rem; outline: none; color: var(--text-dark);
        }
        input:focus { border-color: var(--primary); background: var(--card-bg); box-shadow: 0 0 0 4px var(--primary-glow); }

        .btn-premium { 
            width: 100%; padding: 18px; border: none; border-radius: 18px; background: var(--primary); 
            color: white; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 10px 20px -5px var(--primary-glow);
        }
        .btn-premium:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px var(--primary-glow); }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar span, .sidebar-header { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
            .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">BOOK<span>STORE.</span></a>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-house"></i>&nbsp; <span>Beranda</span></a>
            <a href="katalog.php" class="menu-item"><i class="fas fa-book-open"></i>&nbsp; <span>Katalog Buku</span></a>
            <a href="pesanan_saya.php" class="menu-item"><i class="fas fa-receipt"></i>&nbsp; <span>Pesanan Saya</span></a>
            <a href="pengaturan.php" class="menu-item active"><i class="fas fa-user-gear"></i>&nbsp; <span>Profil Saya</span></a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i>&nbsp; <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700; font-size: 1.1rem;">
                <span id="greeting-text">Halo</span>, <span style="color:var(--primary);"><?= htmlspecialchars($first_name) ?></span>
            </div>
            
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00:00:00</span>
                    <span id="real-date">Memuat Hari...</span>
                </div>
                <a href="wishlist.php" class="nav-icon-link" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?>
                </a>
                <a href="keranjang.php" class="nav-icon-link" title="Keranjang">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if($jumlah_keranjang > 0): ?><span class="nav-badge"><?= $jumlah_keranjang ?></span><?php endif; ?>
                </a>
                <div class="theme-toggle" id="themeSwitcher"><i class="fas fa-moon"></i></div>
                <img src="https://ui-avatars.com/api/?name=<?= $first_name ?>&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);" alt="User">
            </div>
        </header>

        <div class="content-body">
            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800;">Pengaturan Akun</h1>
                <p style="color: var(--text-muted);">Kelola informasi profil dan keamanan akun Anda.</p>
            </div>

            <div class="settings-grid">
                <section class="glass-card">
                    <div class="card-title"><i class="fas fa-id-card"></i> Informasi Profil</div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <div class="input-wrapper"><input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data_user['nama_lengkap']); ?>" required><i class="fas fa-user"></i></div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-wrapper"><input type="email" name="email" value="<?= htmlspecialchars($data_user['email']); ?>" required><i class="fas fa-envelope"></i></div>
                        </div>
                        <div class="form-group">
                            <label>Nomor Telepon</label>
                            <div class="input-wrapper"><input type="text" name="no_telp" value="<?= htmlspecialchars($data_user['no_telp']); ?>" required><i class="fas fa-phone"></i></div>
                        </div>
                        <button type="submit" name="update_profil" class="btn-premium"><i class="fas fa-save"></i> Simpan Profil</button>
                    </form>
                </section>

                <section class="glass-card">
                    <div class="card-title"><i class="fas fa-shield-halved"></i> Keamanan</div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <div class="input-wrapper"><input type="password" name="password_baru" placeholder="Minimal 8 karakter" required><i class="fas fa-lock"></i></div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <div class="input-wrapper"><input type="password" name="konfirmasi_password" placeholder="Ulangi password" required><i class="fas fa-check-double"></i></div>
                        </div>
                        <button type="submit" name="update_password" class="btn-premium" style="background: var(--text-dark);"><i class="fas fa-key"></i> Ganti Password</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <script>
        function runClock() {
            const now = new Date();
            const hour = now.getHours();
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);
            let greet = (hour >= 5 && hour < 12) ? "Selamat Pagi" : (hour >= 12 && hour < 17) ? "Selamat Siang" : (hour >= 17 && hour < 20) ? "Selamat Sore" : "Selamat Malam";
            document.getElementById('greeting-text').innerText = greet;
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