<?php
session_start();
include '../config/koneksi.php';

// Proteksi: Wajib Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil Data User untuk Header
$ambil_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($ambil_user);
$full_name = $data_user['nama_lengkap'] ?? 'User';
$first_name = !empty($full_name) ? explode(' ', trim($full_name))[0] : 'User';

// Logika Hitung Badge Keranjang
$jumlah_keranjang = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jumlah) { 
        $jumlah_keranjang += $jumlah; 
    }
}

// Hitung Badge Wishlist
$q_wishlist_count = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user'");
$jumlah_wishlist = mysqli_num_rows($q_wishlist_count);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Saya | BookStore Pro</title>
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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

        .sidebar { width: var(--sidebar-width); background: var(--sidebar-grad); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 45px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; }
        .logo span { color: #38bdf8; }
        .sidebar-menu { padding: 30px 15px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }

        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .nav-icon-link { position: relative; color: var(--text-dark); text-decoration: none; font-size: 1.1rem; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--border); }
        .nav-badge { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; border: 2px solid var(--card-bg); font-weight: 800; }
        
        .clock-container { text-align: right; border-right: 1px solid var(--border); padding-right: 20px; }
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); display: block; }
        #real-date { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .theme-toggle { cursor: pointer; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); }

        .content-body { padding: 40px 50px; }
        .order-frame { background: var(--card-bg); border-radius: 32px; padding: 45px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .frame-header { margin-bottom: 35px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .frame-header h2 { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; color: var(--text-dark); }

        .order-card { background: var(--bg-main); border-radius: 24px; padding: 25px; margin-bottom: 25px; border: 1px solid var(--border); transition: 0.3s; }
        .order-card:hover { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        .order-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .order-id { font-weight: 800; color: var(--primary); font-size: 0.95rem; }
        
        .status-badge { padding: 6px 14px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .status-proses { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .status-selesai { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-batal { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .item-row { display: flex; gap: 20px; padding: 15px 0; border-bottom: 1px solid var(--border); }
        .item-row:last-of-type { border-bottom: none; }
        .item-row img { width: 60px; height: 85px; object-fit: cover; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .item-info { flex: 1; }
        .item-info h4 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; color: var(--text-dark); }
        .item-info p { font-size: 0.8rem; color: var(--text-muted); }

        /* STYLE BARU: BOX ULASAN */
        .review-display { background: var(--white); padding: 15px; border-radius: 15px; margin-top: 10px; border-left: 4px solid #facc15; }
        .review-stars { color: #facc15; font-size: 0.8rem; margin-bottom: 5px; }
        .review-text { font-size: 0.85rem; font-style: italic; color: var(--text-dark); }

        .order-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border); }
        .total-price { font-size: 1.25rem; font-weight: 900; color: var(--text-dark); }
        
        .btn-ulasan { background: var(--primary); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-ulasan:hover { transform: translateY(-2px); box-shadow: 0 8px 15px var(--primary-glow); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><a href="dashboard.php" class="logo">BOOK<span>STORE.</span></a></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-house"></i>&nbsp; <span>Beranda</span></a>
            <a href="katalog.php" class="menu-item"><i class="fas fa-book-open"></i>&nbsp; <span>Katalog Buku</span></a>
            <a href="pesanan_saya.php" class="menu-item active"><i class="fas fa-receipt"></i>&nbsp; <span>Transaksi Saya</span></a>
            <a href="pengaturan.php" class="menu-item"><i class="fas fa-user-gear"></i>&nbsp; <span>Pengaturan</span></a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i>&nbsp; <span>Keluar</span></a>
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
                <a href="wishlist.php" class="nav-icon-link"><i class="fas fa-heart"></i><?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?></a>
                <a href="keranjang.php" class="nav-icon-link"><i class="fas fa-shopping-bag"></i><?php if($jumlah_keranjang > 0): ?><span class="nav-badge"><?= $jumlah_keranjang ?></span><?php endif; ?></a>
                <div class="theme-toggle" id="themeSwitcher"><i class="fas fa-moon"></i></div>
                <img src="https://ui-avatars.com/api/?name=<?= $first_name ?>&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);" alt="User">
            </div>
        </header>

        <div class="content-body">
            <section class="order-frame">
                <div class="frame-header">
                    <h2><i class="fas fa-receipt" style="color: var(--primary);"></i> Riwayat Pesanan Saya</h2>
                </div>

                <?php
                $q_pesanan = mysqli_query($conn, "SELECT * FROM pesanan WHERE id_user = '$id_user' ORDER BY id DESC");
                if(mysqli_num_rows($q_pesanan) > 0) {
                    while ($row = mysqli_fetch_assoc($q_pesanan)) {
                        $id_p = $row['id'];
                        $status_low = strtolower($row['status']);
                ?>
                <div class="order-card">
                    <div class="order-meta">
                        <div>
                            <span class="order-id">INV/<?= $id_p ?></span>
                            <span style="color:var(--text-muted); font-size:0.8rem; margin-left:10px;">• <?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                        </div>
                        <span class="status-badge status-<?= $status_low ?>"><?= $row['status'] ?></span>
                    </div>

                    <div class="item-list">
                        <?php
                        $q_detail = mysqli_query($conn, "SELECT pesanan_detail.*, buku.judul, buku.gambar, buku.id as id_buku 
                                                         FROM pesanan_detail 
                                                         JOIN buku ON pesanan_detail.id_buku = buku.id 
                                                         WHERE id_pesanan = '$id_p'");
                        while ($item = mysqli_fetch_assoc($q_detail)) {
                            $id_buku_item = $item['id_buku'];
                            
                            // PERBAIKAN: Ambil data ulasan untuk buku ini dari user yang login
                            $q_review = mysqli_query($conn, "SELECT * FROM ulasan WHERE id_user = '$id_user' AND id_buku = '$id_buku_item' LIMIT 1");
                            $review = mysqli_fetch_assoc($q_review);
                        ?>
                        <div class="item-row">
                            <img src="../uploads/<?= $item['gambar'] ?>" onerror="this.src='https://placehold.co/100x150?text=Buku'">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['judul']) ?></h4>
                                <p><?= $item['jumlah'] ?> pcs x Rp <?= number_format($item['subtotal']/$item['jumlah'], 0, ',', '.') ?></p>
                                
                                <?php if($review): ?>
                                <div class="review-display">
                                    <div class="review-stars">
                                        <?php 
                                        for($i=1; $i<=5; $i++) {
                                            echo ($i <= $review['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <p class="review-text">"<?= htmlspecialchars($review['komentar']) ?>"</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <div class="order-footer">
                        <div>
                            <p style="font-size: 0.7rem; color: var(--text-muted); text-transform:uppercase; font-weight:700;">Total Pembayaran</p>
                            <span class="total-price">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></span>
                        </div>
                        <?php if ($row['status'] == 'Selesai'): ?>
                            <a href="tulis_ulasan.php?id=<?= $id_p ?>" class="btn-ulasan">
                                <i class="fas fa-star"></i> <?= ($review) ? 'Edit Ulasan' : 'Beri Ulasan' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    } 
                } else {
                    echo "<div style='text-align:center; padding: 60px 0;'><h3>Belum ada transaksi</h3></div>";
                }
                ?>
            </section>
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