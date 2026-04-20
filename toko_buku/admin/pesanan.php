<?php
session_status() === PHP_SESSION_NONE ? session_start() : null;

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Mendapatkan nama file saat ini untuk logika Sidebar Active
$current_page = basename($_SERVER['PHP_SELF']);

// Logika Update Status Pesanan & Resi
if (isset($_POST['update_pesanan'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);
    $resi = mysqli_real_escape_string($conn, $_POST['no_resi']);

    $update = mysqli_query($conn, "UPDATE pesanan SET status='$status_baru', no_resi='$resi' WHERE id='$id_pesanan'");
    if ($update) {
        echo "<script>alert('Pesanan Berhasil Diperbarui!'); window.location='pesanan.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistik Pesanan | Admin Pro</title>
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
        }

        [data-theme="dark"] {
            --bg-main: #020617; --white: #1e293b; --text-dark: #f8fafc;
            --text-muted: #94a3b8; --border: #334155; --card-bg: #0f172a;
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
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); display: block; }
        #real-date { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .theme-toggle {
            cursor: pointer; width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-main); border: 1px solid var(--border);
            color: var(--text-dark); transition: 0.3s;
        }

        /* --- CONTENT BODY --- */
        .content-body { padding: 40px 50px; }
        .card-table { background: var(--card-bg); padding: 35px; border-radius: 35px; border: 1px solid var(--border); box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .custom-table th { text-align: left; padding: 18px 15px; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid var(--border); letter-spacing: 1px; font-weight: 800; }
        .custom-table td { padding: 20px 15px; border-bottom: 1px solid var(--border); vertical-align: middle; font-weight: 600; }

        /* Status Badges */
        .badge { padding: 6px 12px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-Pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Proses { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .status-Selesai { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Batal { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Form Elements */
        .update-form { display: flex; gap: 8px; align-items: center; }
        select, .input-resi { 
            padding: 10px 12px; border-radius: 12px; border: 1.5px solid var(--border);
            background: var(--bg-main); color: var(--text-dark); font-size: 0.85rem; outline: none; font-weight: 600;
        }
        .btn-update { background: var(--primary); color: white; border: none; padding: 10px 14px; border-radius: 12px; cursor: pointer; transition: 0.3s; }
        .btn-update:hover { transform: scale(1.05); box-shadow: 0 5px 15px var(--primary-glow); }

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
                Logistik <span style="color:var(--primary);">Pesanan Toko</span>
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
            <div class="card-table">
                <div style="margin-bottom: 25px;">
                    <h2 style="font-size: 1.4rem; font-weight: 800;">Daftar Transaksi Masuk</h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Pantau pengiriman dan perbarui status pesanan pelanggan.</p>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Total Bayar</th>
                                <th>Status</th>
                                <th>Update Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = mysqli_query($conn, "SELECT pesanan.*, users.nama_lengkap FROM pesanan JOIN users ON pesanan.id_user = users.id ORDER BY pesanan.id DESC");
                            while($row = mysqli_fetch_assoc($query)) :
                                $st = $row['status'];
                            ?>
                            <tr>
                                <td>
                                    <b style="color: var(--primary);">#INV-<?= $row['id']; ?></b><br>
                                    <small style="color: var(--text-muted);"><?= date('d M Y', strtotime($row['tanggal'])); ?></small>
                                </td>
                                <td>
                                    <div style="font-weight: 800;"><?= htmlspecialchars($row['nama_lengkap']); ?></div>
                                    <a href="javascript:void(0)" onclick="openDetail('<?= $row['id'] ?>')" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">Lihat Detail</a>
                                </td>
                                <td style="color: var(--primary)">Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                <td><span class="badge status-<?= $st; ?>"><?= $st; ?></span></td>
                                <td>
                                    <form method="POST" class="update-form">
                                        <input type="hidden" name="id_pesanan" value="<?= $row['id']; ?>">
                                        <select name="status">
                                            <option value="Pending" <?= $st == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Proses" <?= $st == 'Proses' ? 'selected' : ''; ?>>Proses</option>
                                            <option value="Selesai" <?= $st == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                            <option value="Batal" <?= $st == 'Batal' ? 'selected' : ''; ?>>Batal</option>
                                        </select>
                                        <input type="text" name="no_resi" class="input-resi" placeholder="No. Resi" value="<?= $row['no_resi']; ?>">
                                        <button type="submit" name="update_pesanan" class="btn-update"><i class="fas fa-check"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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