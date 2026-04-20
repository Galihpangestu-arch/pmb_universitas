<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Ambil statistik
$total_buku = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM buku"));
$total_user = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='user'"));
$total_pesanan = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pesanan"));

$q_pendapatan = mysqli_query($conn, "SELECT SUM(total_bayar) as total FROM pesanan WHERE status='Selesai'");
$res_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$total_pendapatan = $res_pendapatan['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Admin | BookStore Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --sidebar-grad: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            
            /* Light Mode (Default) */
            --bg-main: #f8fafc;
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --sidebar-width: 280px;
        }

        /* Dark Mode Colors */
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
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; }
        .logo span { color: #38bdf8; }
        .sidebar-menu { padding: 30px 15px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }

        /* --- REAL TIME CLOCK & THEME --- */
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .clock-container { text-align: right; border-right: 1px solid var(--border); padding-right: 20px; }
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); display: block; }
        #real-date { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .theme-toggle {
            cursor: pointer; width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-main); border: 1px solid var(--border);
            color: var(--text-dark); font-size: 1rem; transition: 0.3s;
        }
        .theme-toggle:hover { border-color: var(--primary); color: var(--primary); }

        /* --- STATS --- */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; padding: 40px 50px; }
        .stat-card { background: var(--card-bg); padding: 30px; border-radius: 32px; border: 1px solid var(--border); transition: 0.4s; }
        .stat-card:hover { transform: translateY(-10px); border-color: var(--primary); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-icon { width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; background: rgba(99, 102, 241, 0.1); color: var(--primary); margin-bottom: 15px; font-size: 1.2rem; }

        /* --- TABLE --- */
        .recent-section { padding: 0 50px 50px; }
        .recent-card { background: var(--card-bg); border-radius: 32px; padding: 35px; border: 1px solid var(--border); }
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .custom-table th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        .custom-table td { padding: 18px 15px; border-bottom: 1px solid var(--border); font-weight: 600; }
        
        .status-pill { padding: 6px 14px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-selesai { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        /* --- MODAL --- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 2000; }
        .modal-card { background: var(--card-bg); color: var(--text-dark); width: 90%; max-width: 750px; border-radius: 35px; padding: 45px; position: relative; border: 1px solid var(--border); animation: cardEntrance 0.4s ease; }
        @keyframes cardEntrance { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .close-btn { position: absolute; top: 30px; right: 35px; font-size: 1.8rem; cursor: pointer; color: var(--text-muted); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><a href="dashboard.php" class="logo">ADMIN<span>PRO.</span></a></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active"><i class="fas fa-chart-pie"></i>&nbsp; Dashboard</a>
            <a href="kategori.php" class="menu-item <?= ($current_page == 'kategori.php') ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> <span>Kategori Buku</span>
            </a>
            <a href="kelola_buku.php" class="menu-item"><i class="fas fa-book-bookmark"></i>&nbsp; Koleksi Buku</a>
            <a href="kelola_pelanggan.php" class="menu-item"><i class="fas fa-users"></i>&nbsp; Database User</a>
            <a href="pesanan.php" class="menu-item"><i class="fas fa-bag-shopping"></i>&nbsp; Order Masuk</a>
            <a href="laporan.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Laporan Omzet</span></a>
            <a href="pengaturan.php" class="menu-item"><i class="fas fa-cog"></i>&nbsp; Settings</a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i>&nbsp; Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700; font-size: 1.1rem;">
                <span id="greeting-text">Halo</span>, <span style="color:var(--primary);">Administrator</span>
            </div>
            
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00:00:00</span>
                    <span id="real-date">Memuat Hari...</span>
                </div>
                <div class="theme-toggle" id="themeSwitcher" title="Ganti Mode">
                    <i class="fas fa-moon"></i>
                </div>
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);" alt="">
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <div class="stat-info"><p style="font-size:0.7rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Katalog</p><h3><?= number_format($total_buku) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#10b981"><i class="fas fa-user-check"></i></div>
                <div class="stat-info"><p style="font-size:0.7rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Users</p><h3><?= number_format($total_user) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#f59e0b"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info"><p style="font-size:0.7rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Orders</p><h3><?= number_format($total_pesanan) ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#6366f1"><i class="fas fa-wallet"></i></div>
                <div class="stat-info"><p style="font-size:0.7rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Revenue</p><h3>Rp <?= number_format($total_pendapatan,0,',','.') ?></h3></div>
            </div>
        </section>

        <div class="recent-section">
            <div class="recent-card">
                <h2 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 20px;">Transaksi Terbaru</h2>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_recent = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM pesanan p JOIN users u ON p.id_user = u.id ORDER BY p.id DESC LIMIT 5");
                        while($row = mysqli_fetch_assoc($q_recent)):
                        ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td style="color: var(--primary)">Rp <?= number_format($row['total_bayar'],0,',','.') ?></td>
                            <td><span class="status-pill status-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td><button onclick="openDetail('<?= $row['id'] ?>')" style="background:var(--primary); color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:700;">Detail</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="detailModal" class="modal-overlay">
        <div class="modal-card">
            <span class="close-btn" onclick="closeDetail()">&times;</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        // 1. JAM REAL-TIME & SAPAAN OTOMATIS
        function runClock() {
            const now = new Date();
            const hour = now.getHours();
            
            // Update Jam
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            
            // Update Tanggal
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);

            // Update Sapaan
            let greet = "";
            if (hour >= 5 && hour < 12) greet = "Selamat Pagi";
            else if (hour >= 12 && hour < 17) greet = "Selamat Siang";
            else if (hour >= 17 && hour < 20) greet = "Selamat Sore";
            else greet = "Selamat Malam";
            document.getElementById('greeting-text').innerText = greet;
        }
        setInterval(runClock, 1000);
        runClock();

        // 2. NIGHT MODE TOGGLE
        const themeSwitcher = document.getElementById('themeSwitcher');
        const htmlEl = document.documentElement;

        // Cek storage
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

        // 3. DETAIL MODAL AJAX
        function openDetail(id) {
            document.getElementById('detailModal').style.display = 'flex';
            fetch('get_pesanan_detail.php?id=' + id)
                .then(res => res.text())
                .then(html => { document.getElementById('modalBody').innerHTML = html; });
        }
        function closeDetail() { document.getElementById('detailModal').style.display = 'none'; }
    </script>
</body>
</html>