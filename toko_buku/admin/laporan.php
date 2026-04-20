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

// Inisialisasi variabel tanggal (Default: Bulan Berjalan)
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

// Query Utama: Data pesanan selesai
$query = mysqli_query($conn, "SELECT pesanan.*, users.nama_lengkap 
                              FROM pesanan 
                              JOIN users ON pesanan.id_user = users.id 
                              WHERE DATE(pesanan.tanggal) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                              AND pesanan.status = 'Selesai'
                              ORDER BY pesanan.tanggal DESC");

// Query Statistik Tambahan
$stat_omzet = mysqli_query($conn, "SELECT SUM(total_bayar) as total, COUNT(id) as jml_order FROM pesanan WHERE status='Selesai' AND DATE(tanggal) BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$data_stat = mysqli_fetch_assoc($stat_omzet);

$total_pendapatan = $data_stat['total'] ?? 0;
$total_transaksi = $data_stat['jml_order'] ?? 0;
$rata_rata = ($total_transaksi > 0) ? ($total_pendapatan / $total_transaksi) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Omzet | Admin Pro</title>
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
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); }
        .theme-toggle { cursor: pointer; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); transition: 0.3s; }

        /* --- REPORT CONTENT --- */
        .content-body { padding: 40px 50px; }
        .report-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-box { background: var(--card-bg); padding: 25px; border-radius: 28px; border: 1px solid var(--border); }
        .stat-box p { font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 8px; }
        .stat-box h4 { font-size: 1.3rem; font-weight: 900; color: var(--text-dark); }

        .filter-card { background: var(--card-bg); padding: 30px; border-radius: 32px; border: 1px solid var(--border); margin-bottom: 30px; }
        .filter-form { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
        .input-group label { display: block; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
        input[type="date"] { padding: 12px 18px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--bg-main); color: var(--text-dark); font-weight: 600; outline: none; }

        .btn { padding: 12px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; }
        .btn-filter { background: var(--primary); color: white; }
        .btn-print { background: var(--white); color: var(--text-dark); border: 1px solid var(--border); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .card-table { background: var(--card-bg); border-radius: 32px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: rgba(0,0,0,0.02); padding: 20px 25px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--border); font-weight: 800; }
        .custom-table td { padding: 20px 25px; border-bottom: 1px solid var(--border); font-size: 0.95rem; font-weight: 600; }
        .total-row { background: rgba(16, 185, 129, 0.05); }

        @media print { 
            .sidebar, .filter-card, .top-nav, .btn-filter, .theme-toggle { display: none !important; } 
            .main-content { margin-left: 0 !important; width: 100% !important; } 
            .content-body { padding: 0 !important; }
            .stat-box, .card-table { border: 1px solid #000 !important; border-radius: 0 !important; }
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
                Rekapitulasi <span style="color:var(--primary);">Omzet Penjualan</span>
            </div>
            
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00:00:00</span>
                </div>
                <div class="theme-toggle" id="themeSwitcher">
                    <i class="fas fa-moon"></i>
                </div>
                <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Cetak Laporan</button>
            </div>
        </header>

        <div class="content-body">
            <div class="report-stats">
                <div class="stat-box" style="border-left: 5px solid #10b981;">
                    <p>Total Pendapatan</p>
                    <h4 style="color: #10b981;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h4>
                </div>
                <div class="stat-box">
                    <p>Total Transaksi</p>
                    <h4><?= number_format($total_transaksi) ?> Pesanan</h4>
                </div>
                <div class="stat-box">
                    <p>Rata-rata Penjualan</p>
                    <h4>Rp <?= number_format($rata_rata, 0, ',', '.') ?></h4>
                </div>
            </div>

            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="input-group">
                        <label>Mulai Tanggal</label>
                        <input type="date" name="tgl_mulai" value="<?= $tgl_mulai ?>">
                    </div>
                    <div class="input-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="tgl_selesai" value="<?= $tgl_selesai ?>">
                    </div>
                    <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Terapkan Filter</button>
                    <a href="laporan.php" style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none; font-weight: 800;">Reset</a>
                </form>
            </div>

            <div class="card-table">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th width="80">No</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama Pelanggan</th>
                                <th style="text-align: right;">Total Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query) > 0):
                                while($row = mysqli_fetch_assoc($query)): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td><b style="color: var(--primary);">#INV-<?= $row['id'] ?></b></td>
                                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td style="text-align: right;">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 50px; color: var(--text-muted);">Tidak ada data transaksi ditemukan.</td></tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="4" style="text-align: right; font-weight: 800; color: var(--text-muted);">TOTAL AKUMULASI PERIODE INI :</td>
                                <td style="text-align: right; color: #10b981; font-size: 1.1rem; font-weight: 900;">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                            </tr>
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