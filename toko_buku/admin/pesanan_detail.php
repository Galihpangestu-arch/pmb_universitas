<?php
session_status() === PHP_SESSION_NONE ? session_start() : null;

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    header("Location: pesanan.php");
    exit;
}
$id_pesanan = mysqli_real_escape_string($conn, $_GET['id']);

// Query data pesanan & pelanggan
$query_p = mysqli_query($conn, "SELECT pesanan.*, users.nama_lengkap, users.email, users.no_telp 
                                FROM pesanan 
                                JOIN users ON pesanan.id_user = users.id 
                                WHERE pesanan.id = '$id_pesanan'");
$data = mysqli_fetch_assoc($query_p);

if (!$data) {
    echo "<script>alert('Data transaksi tidak ditemukan'); window.location='pesanan.php';</script>";
    exit;
}

// Fallback Alamat
$alamat_kirim = $data['alamat'] ?? 'Alamat tidak dicantumkan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Invoice #<?= $id_pesanan ?> | Admin Pro</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s, color 0.3s, width 0.3s; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- SIDEBAR (SESUAI KONSEP PREMIUM) --- */
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-grad); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; overflow: hidden; }
        .sidebar-header { padding: 45px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; text-transform: uppercase; }
        .logo span { color: #38bdf8; }
        .sidebar-menu { padding: 30px 18px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; white-space: nowrap; }
        .menu-item i { width: 32px; font-size: 1.1rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }
        .sidebar-footer { padding: 25px; border-top: 1px solid rgba(255,255,255,0.05); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; min-width: 0; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }

        .nav-right { display: flex; align-items: center; gap: 15px; }
        .clock-container { text-align: right; border-right: 1px solid var(--border); padding-right: 15px; }
        #real-clock { font-weight: 800; font-size: 1rem; color: var(--text-dark); }
        .theme-toggle { cursor: pointer; width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); }

        /* --- CONTENT BODY --- */
        .content-body { padding: 40px; max-width: 1100px; margin: 0 auto; }
        .btn-back { display: inline-flex; align-items: center; gap: 10px; color: var(--primary); text-decoration: none; font-weight: 700; margin-bottom: 25px; transition: 0.3s; }
        .btn-back:hover { transform: translateX(-5px); }

        /* --- INVOICE CARD --- */
        .invoice-card { background: var(--card-bg); border-radius: 35px; border: 1px solid var(--border); padding: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); position: relative; }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 25px; border-bottom: 2px dashed var(--border); }
        .invoice-header h2 { font-size: 1.6rem; font-weight: 900; }
        
        .status-badge { padding: 8px 20px; border-radius: 100px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .status-Selesai { background: #dcfce7; color: #15803d; }
        .status-Pending { background: #fef9c3; color: #a16207; }
        .status-Proses { background: #e0e7ff; color: #4338ca; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .info-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; margin-bottom: 8px; letter-spacing: 1px; }
        .info-value { font-weight: 700; color: var(--text-dark); line-height: 1.6; }

        .item-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .item-table th { text-align: left; padding: 15px; background: rgba(0,0,0,0.02); color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; }
        .item-table td { padding: 20px 15px; border-bottom: 1px solid var(--border); }
        
        .book-meta { display: flex; align-items: center; gap: 15px; }
        .book-meta img { width: 50px; height: 70px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .total-box { margin-top: 40px; padding-top: 30px; border-top: 2px solid var(--border); text-align: right; }
        .total-box h3 { font-size: 2.2rem; font-weight: 900; color: var(--primary); letter-spacing: -1.5px; }

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
            <a href="dashboard.php" class="menu-item"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <a href="kelola_buku.php" class="menu-item"><i class="fas fa-book-bookmark"></i> <span>Koleksi Buku</span></a>
            <a href="kelola_pelanggan.php" class="menu-item"><i class="fas fa-users"></i> <span>Data Pelanggan</span></a>
            <a href="pesanan.php" class="menu-item active"><i class="fas fa-shopping-bag"></i> <span>Pesanan Toko</span></a>
            <a href="laporan.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Laporan Omzet</span></a>
            <a href="pengaturan.php" class="menu-item"><i class="fas fa-cog"></i> <span>Pengaturan</span></a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 600;">
                <span style="color: var(--text-muted);">Transaksi</span> / <b style="color: var(--primary);">Detail Invoice</b>
            </div>
            <div class="nav-right">
                <div class="clock-container"><span id="real-clock">00.00.00</span></div>
                <div class="theme-toggle" id="themeSwitcher"><i class="fas fa-moon"></i></div>
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff&bold=true" style="width: 35px; border-radius: 10px;">
            </div>
        </header>

        <div class="content-body">
            <a href="pesanan.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan</a>

            <div class="invoice-card">
                <div class="invoice-header">
                    <h2>#INV-<?= $data['id']; ?></h2>
                    <span class="status-badge status-<?= $data['status']; ?>"><?= $data['status']; ?></span>
                </div>

                <div class="info-grid">
                    <div>
                        <div class="info-label">Tujuan Pengiriman</div>
                        <div class="info-value" style="font-size: 1.1rem;"><?= htmlspecialchars($data['nama_lengkap']); ?></div>
                        <div class="info-value" style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">
                            <?= $data['no_telp']; ?><br>
                            <?= nl2br(htmlspecialchars($alamat_kirim)); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="info-label">Waktu Transaksi</div>
                        <div class="info-value">
                            <?= date('d F Y', strtotime($data['tanggal'])); ?><br>
                            <?= date('H:i', strtotime($data['tanggal'])); ?> WIB
                        </div>
                    </div>
                </div>

                <div class="info-label">Daftar Pembelian</div>
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Judul Buku</th>
                            <th>Harga</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_item = mysqli_query($conn, "SELECT pd.*, b.judul, b.gambar FROM pesanan_detail pd JOIN buku b ON pd.id_buku = b.id WHERE pd.id_pesanan = '$id_pesanan'");
                        while($item = mysqli_fetch_assoc($q_item)):
                        ?>
                        <tr>
                            <td>
                                <div class="book-meta">
                                    <img src="../uploads/<?= $item['gambar']; ?>" onerror="this.src='https://placehold.co/100x150'">
                                    <div style="font-weight: 700;"><?= htmlspecialchars($item['judul']); ?></div>
                                </div>
                            </td>
                            <td>Rp <?= number_format($item['subtotal'] / $item['jumlah'], 0, ',', '.'); ?></td>
                            <td style="text-align: center; font-weight: 700;">x<?= $item['jumlah']; ?></td>
                            <td style="text-align: right; font-weight: 800;">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="total-box">
                    <div class="info-label">Total Pembayaran</div>
                    <h3>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </main>

    <script>
        function runClock() {
            const now = new Date();
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false }).replace(/:/g, '.');
        }
        setInterval(runClock, 1000); runClock();

        const themeSwitcher = document.getElementById('themeSwitcher');
        if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        themeSwitcher.addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>