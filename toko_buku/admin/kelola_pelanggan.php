<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Mendapatkan nama file saat ini untuk logika Sidebar Active
$current_page = basename($_SERVER['PHP_SELF']);

// --- LOGIKA HAPUS PELANGGAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Proteksi: Admin tidak bisa menghapus akun dengan role admin
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id_hapus' AND role = 'user'");
    echo "<script>alert('Data pelanggan berhasil dihapus!'); window.location='kelola_pelanggan.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database User | Admin Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --sidebar-grad: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            
            /* Light Mode */
            --bg-main: #f8fafc;
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --sidebar-width: 280px;
        }

        /* Dark Mode */
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

        /* --- SIDEBAR (IDENTIK) --- */
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
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); display: block; }
        #real-date { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .theme-toggle {
            cursor: pointer; width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-main); border: 1px solid var(--border);
            color: var(--text-dark); transition: 0.3s;
        }

        /* --- TABLE STYLE --- */
        .content-body { padding: 40px 50px; }
        .recent-card { background: var(--card-bg); border-radius: 32px; padding: 35px; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .custom-table th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid var(--border); letter-spacing: 1px; }
        .custom-table td { padding: 20px 15px; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 0.9rem; vertical-align: middle; }

        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar { width: 45px; height: 45px; border-radius: 14px; border: 2px solid var(--border); }
        
        code { background: rgba(99, 102, 241, 0.1); padding: 4px 10px; border-radius: 8px; color: var(--primary); font-family: inherit; font-size: 0.85rem; }

        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px 18px; border-radius: 12px; text-decoration: none; font-size: 0.8rem; font-weight: 800; transition: 0.3s; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-delete:hover { background: #ef4444; color: white; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }

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
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700; font-size: 1.1rem;">
                Database <span style="color:var(--primary);">User Pelanggan</span>
            </div>
            
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00:00:00</span>
                    <span id="real-date">Memuat...</span>
                </div>
                <div class="theme-toggle" id="themeSwitcher">
                    <i class="fas fa-moon"></i>
                </div>
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);" alt="">
            </div>
        </header>

        <div class="content-body">
            <div class="recent-card">
                <div style="margin-bottom: 25px;">
                    <h2 style="font-size: 1.4rem; font-weight: 800;">Daftar Pengguna Aktif</h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Manajemen akun pembeli yang terdaftar di sistem BookStore Pro.</p>
                </div>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Profil Pelanggan</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_user = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY id DESC");
                            if(mysqli_num_rows($q_user) > 0):
                                while($row = mysqli_fetch_assoc($q_user)):
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['nama_lengkap']) ?>&background=random&bold=true" class="user-avatar">
                                        <div>
                                            <div style="color: var(--text-dark);"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">ID: #USR-<?= $row['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><code><?= htmlspecialchars($row['username']) ?></code></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= !empty($row['no_telp']) ? htmlspecialchars($row['no_telp']) : '<span style="opacity:0.3">-</span>' ?></td>
                                <td style="text-align: center;">
                                    <a href="kelola_pelanggan.php?hapus=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Peringatan: Menghapus user akan menghapus seluruh histori pesanannya. Lanjutkan?')">
                                        <i class="fas fa-trash-can"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 60px; color: var(--text-muted);">
                                    <i class="fas fa-user-slash" style="font-size: 3rem; opacity: 0.2; display: block; margin-bottom: 15px;"></i>
                                    Belum ada pelanggan terdaftar.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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