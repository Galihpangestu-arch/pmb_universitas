<?php
session_start();
include '../config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: katalog.php");
    exit;
}

$id_buku = mysqli_real_escape_string($conn, $_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM buku WHERE id = '$id_buku'");
$row = mysqli_fetch_assoc($query);

if (!$row) {
    header("Location: katalog.php");
    exit;
}

$id_user_login = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;

// Ambil Nama User untuk Header
$first_name = 'User';
if ($id_user_login) {
    $ambil_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$id_user_login'");
    $data_user = mysqli_fetch_assoc($ambil_user);
    $first_name = !empty($data_user['nama_lengkap']) ? explode(' ', trim($data_user['nama_lengkap']))[0] : 'User';
}

// Hitung Badge Keranjang
$jumlah_keranjang = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jumlah) { $jumlah_keranjang += $jumlah; }
}

// Hitung Badge Wishlist
$jumlah_wishlist = 0;
if ($id_user_login) {
    $q_wishlist_count = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user_login'");
    $jumlah_wishlist = mysqli_num_rows($q_wishlist_count);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $row['judul'] ?> | BookStore Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg-main: #f8fafc;
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --danger: #ef4444;
            --success: #10b981;
        }

        [data-theme="dark"] {
            --bg-main: #020617;
            --white: #1e293b;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --card-bg: #0f172a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: all 0.3s ease; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-main); color: var(--text-dark); min-height: 100vh; overflow-x: hidden; }

        /* --- NAVIGATION (Identical to Wishlist/Cart) --- */
        .top-nav { 
            background: var(--card-bg); opacity: 0.98; backdrop-filter: blur(20px); 
            padding: 15px 8%; display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000;
        }
        
        .logo { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); text-decoration: none; letter-spacing: -1.5px; }
        .logo span { color: var(--primary); }

        .nav-right { display: flex; align-items: center; gap: 20px; }
        .nav-clock-section { text-align: right; border-right: 1px solid var(--border); padding-right: 20px; }
        #real-clock { font-weight: 800; font-size: 1rem; color: var(--text-dark); display: block; }
        #real-date { font-size: 0.65rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }

        .nav-icon-link { 
            position: relative; color: var(--text-dark); text-decoration: none; font-size: 1.2rem; 
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; 
            border-radius: 15px; border: 1px solid var(--border); background: var(--bg-main);
        }
        .nav-icon-link:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-3px); }
        .nav-badge { 
            position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; 
            font-size: 0.7rem; padding: 3px 7px; border-radius: 50%; border: 3px solid var(--card-bg); font-weight: 800; 
        }
        
        .theme-toggle { cursor: pointer; width: 45px; height: 45px; border-radius: 15px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); }

        /* --- CONTENT LAYOUT --- */
        .container { padding: 50px 8%; max-width: 1400px; margin: 0 auto; }

        .btn-back-main { 
            display: inline-flex; align-items: center; gap: 10px; color: var(--text-muted); 
            text-decoration: none; font-weight: 700; margin-bottom: 30px; transition: 0.3s;
        }
        .btn-back-main:hover { color: var(--primary); transform: translateX(-5px); }

        /* --- DETAIL PREMIUM CARD --- */
        .detail-wrapper { 
            background: var(--card-bg); border-radius: 40px; padding: 50px; 
            border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            display: grid; grid-template-columns: 400px 1fr; gap: 60px;
        }

        .image-container { position: sticky; top: 120px; }
        .main-img { 
            width: 100%; border-radius: 30px; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.2); 
            object-fit: cover;
        }

        /* --- INFO SECTION --- */
        .detail-info h1 { font-size: 3rem; font-weight: 800; margin-bottom: 10px; color: var(--text-dark); line-height: 1.1; }
        .author-tag { font-size: 1.1rem; color: var(--primary); font-weight: 600; display: block; margin-bottom: 30px; }

        .stats-row { display: flex; gap: 30px; margin-bottom: 40px; }
        .stat-item { display: flex; flex-direction: column; gap: 5px; }
        .stat-label { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-weight: 700; font-size: 1rem; color: var(--text-dark); }

        .price-section { 
            background: var(--bg-main); padding: 30px; border-radius: 25px; 
            border: 1px solid var(--border); margin-bottom: 40px; display: inline-block; min-width: 300px;
        }
        .price-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; display: block; }
        .price-value { font-size: 2.2rem; font-weight: 900; color: var(--primary); }

        .synopsis-box { margin-bottom: 40px; }
        .synopsis-title { font-size: 1rem; font-weight: 800; margin-bottom: 15px; text-transform: uppercase; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
        .synopsis-text { line-height: 1.8; color: var(--text-muted); font-size: 1.05rem; text-align: justify; }

        /* --- ACTION AREA --- */
        .action-card { 
            display: flex; gap: 20px; align-items: center; 
            padding-top: 30px; border-top: 1.5px dashed var(--border);
        }

        .qty-control { 
            display: flex; align-items: center; background: var(--bg-main); 
            border-radius: 18px; border: 1px solid var(--border); padding: 5px;
        }
        .qty-control input { 
            width: 60px; border: none; background: transparent; text-align: center; 
            font-weight: 800; font-size: 1.1rem; color: var(--text-dark); outline: none;
        }

        .btn-cart-premium { 
            flex: 1; background: var(--primary); color: white; padding: 20px; 
            border-radius: 20px; font-weight: 800; border: none; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; gap: 12px;
            box-shadow: 0 10px 25px var(--primary-glow); font-size: 1rem;
        }
        .btn-cart-premium:hover { transform: translateY(-5px); filter: brightness(1.1); box-shadow: 0 15px 35px var(--primary-glow); }
        .btn-cart-premium:disabled { background: var(--text-muted); box-shadow: none; cursor: not-allowed; }

        .btn-wish-premium {
            width: 60px; height: 60px; border-radius: 20px; border: 2px solid var(--border);
            background: var(--card-bg); color: var(--danger); font-size: 1.3rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center; text-decoration: none;
        }
        .btn-wish-premium:hover { border-color: var(--danger); background: rgba(239, 68, 68, 0.05); }

        @media (max-width: 992px) {
            .detail-wrapper { grid-template-columns: 1fr; padding: 30px; }
            .image-container { position: relative; top: 0; max-width: 350px; margin: 0 auto; }
            .detail-info h1 { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <header class="top-nav">
            <a href="dashboard.php" class="logo">ADMIN<span>PRO.</span></a>
            
            <div class="nav-right">
                <div class="nav-clock-section">
                    <span id="real-clock">00:00:00</span>
                    <span id="real-date">...</span>
                </div>

                <a href="wishlist.php" class="nav-icon-link" title="Wishlist Saya">
                    <i class="fas fa-heart"></i>
                    <?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?>
                </a>

                <a href="keranjang.php" class="nav-icon-link" title="Keranjang Belanja">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if($jumlah_keranjang > 0): ?><span class="nav-badge"><?= $jumlah_keranjang ?></span><?php endif; ?>
                </a>

                <div class="theme-toggle" id="themeSwitcher">
                    <i class="fas fa-moon"></i>
                </div>
                
                <img src="https://ui-avatars.com/api/?name=<?= $first_name ?>&background=6366f1&color=fff&bold=true" style="width: 42px; border-radius: 14px; border: 2px solid var(--primary);" alt="User">
            </div>
        </header>

        <div class="container">
            <a href="katalog.php" class="btn-back-main">
                <i class="fas fa-arrow-left"></i> Kembali ke Eksplorasi
            </a>

            <div class="detail-wrapper">
                <div class="image-container">
                    <img src="../uploads/<?= $row['gambar'] ?>" class="main-img" onerror="this.src='https://images.unsplash.com/photo-1543004223-249a4a2725ad?q=80&w=400'">
                </div>

                <div class="detail-info">
                    <h1><?= htmlspecialchars($row['judul']) ?></h1>
                    <span class="author-tag">Karya Masterpiece: <?= htmlspecialchars($row['penulis']) ?></span>

                    <div class="stats-row">
                        <div class="stat-item">
                            <span class="stat-label">Kategori</span>
                            <span class="stat-value"><?= $row['kategori'] ?? 'Premium Collection' ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Kondisi</span>
                            <span class="stat-value">Baru / Original</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Ketersediaan</span>
                            <span class="stat-value" style="color: <?= ($row['stok'] > 0) ? 'var(--success)' : 'var(--danger)' ?>">
                                <i class="fas fa-circle" style="font-size: 0.6rem;"></i> 
                                <?= ($row['stok'] > 0) ? $row['stok'] . ' Buku Tersedia' : 'Stok Habis' ?>
                            </span>
                        </div>
                    </div>

                    <div class="price-section">
                        <span class="price-label">Penawaran Eksklusif</span>
                        <div class="price-value">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                    </div>

                    <div class="synopsis-box">
                        <div class="synopsis-title"><i class="fas fa-align-left" style="color: var(--primary);"></i> Sinopsis Buku</div>
                        <p class="synopsis-text">
                            <?= nl2br(htmlspecialchars($row['deskripsi'])) ?>
                        </p>
                    </div>

                    <form action="proses_keranjang.php" method="GET">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="aksi" value="tambah">
                        
                        <div class="action-card">
                            <div class="qty-section">
                                <label class="stat-label" style="display:block; margin-bottom:10px;">Jumlah</label>
                                <div class="qty-control">
                                    <input type="number" name="qty" value="1" min="1" max="<?= $row['stok'] ?>" <?= ($row['stok'] <= 0) ? 'disabled' : '' ?>>
                                </div>
                            </div>

                            <button type="submit" class="btn-cart-premium" <?= ($row['stok'] <= 0) ? 'disabled' : '' ?>>
                                <i class="fas fa-cart-plus"></i> Tambahkan ke Keranjang
                            </button>

                            <a href="proses_wishlist.php?id=<?= $row['id'] ?>" class="btn-wish-premium" title="Tambah ke Wishlist">
                                <i class="fas fa-heart"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // SCRIPT JAM & SAPAAN REAL-TIME
        function updateClock() {
            const now = new Date();
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // THEME TOGGLE ENGINE
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