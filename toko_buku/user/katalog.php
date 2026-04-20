<?php
// Cek jika session belum dimulai, baru jalankan session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/koneksi.php';

// Proteksi login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user_login = $_SESSION['id_user'];

// Ambil Data User untuk Header
$ambil_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$id_user_login'");
$data_user = mysqli_fetch_assoc($ambil_user);
$first_name = !empty($data_user['nama_lengkap']) ? explode(' ', trim($data_user['nama_lengkap']))[0] : 'User';

// Logika Pencarian & Filter Kategori
$keyword = $_GET['search'] ?? ""; 
$kat_filter = $_GET['kategori'] ?? "";

$query_base = "SELECT * FROM buku WHERE 1=1";

if (!empty($keyword)) {
    $keyword = mysqli_real_escape_string($conn, $keyword);
    $query_base .= " AND (judul LIKE '%$keyword%' OR penulis LIKE '%$keyword%')";
}

if (!empty($kat_filter)) {
    $kat_filter = mysqli_real_escape_string($conn, $kat_filter);
    $query_base .= " AND kategori = '$kat_filter'";
}

$query_base .= " ORDER BY id DESC";

// Hitung Badge Keranjang
$jumlah_keranjang = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jumlah) { $jumlah_keranjang += $jumlah; }
}

// Hitung Badge Wishlist
$q_wishlist_count = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user_login'");
$jumlah_wishlist = mysqli_num_rows($q_wishlist_count);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku | BOOKSTORE</title>
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
            --bg-main: #020617; --white: #1e293b; --text-dark: #f8fafc;
            --text-muted: #94a3b8; --border: #334155; --card-bg: #0f172a;
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

        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .nav-icon-link { position: relative; color: var(--text-dark); text-decoration: none; font-size: 1.1rem; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--border); }
        .nav-badge { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; border: 2px solid var(--card-bg); font-weight: 800; }
        .theme-toggle { cursor: pointer; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); }

        /* --- CATEGORY CHIPS --- */
        .category-container { display: flex; gap: 12px; margin: 25px 50px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .category-container::-webkit-scrollbar { display: none; }
        .cat-chip { padding: 10px 24px; border-radius: 50px; background: var(--white); border: 1px solid var(--border); color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 700; white-space: nowrap; }
        .cat-chip.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 8px 15px var(--primary-glow); }

        .content-body { padding: 0 50px 40px; }
        .search-box { display: flex; background: var(--card-bg); border-radius: 20px; padding: 5px; border: 1px solid var(--border); width: 100%; max-width: 500px; margin-bottom: 35px; }
        .search-box input { border: none; padding: 12px 20px; flex: 1; outline: none; background: transparent; color: var(--text-dark); font-size: 0.95rem; }
        .btn-search { background: var(--primary); color: white; border: none; padding: 0 25px; border-radius: 15px; cursor: pointer; font-weight: 700; }

        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 30px; }
        .book-card { background: var(--card-bg); border-radius: 28px; padding: 18px; border: 1px solid var(--border); transition: 0.4s; position: relative; display: flex; flex-direction: column; }
        .book-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); border-color: var(--primary); }
        .book-img { width: 100%; height: 260px; object-fit: cover; border-radius: 20px; margin-bottom: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .book-cat { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; color: var(--primary); letter-spacing: 1px; margin-bottom: 5px; }
        .book-title { font-weight: 800; font-size: 1rem; color: var(--text-dark); margin-bottom: 10px; height: 2.6rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .book-price { font-size: 1.1rem; font-weight: 900; color: var(--text-dark); margin-bottom: 15px; }
        .btn-buy { background: var(--primary); color: white; text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; }
        
        .wish-btn { position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.9); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #cbd5e1; text-decoration: none; }
        .wish-btn.active { color: #ef4444; background: white; box-shadow: 0 5px 15px rgba(239,68,68,0.2); }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar span, .sidebar-header { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
            .top-nav { padding: 15px 25px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><a href="dashboard.php" class="logo">BOOK<span>STORE.</span></a></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-house"></i>&nbsp; <span>Beranda</span></a>
            <a href="katalog.php" class="menu-item active"><i class="fas fa-book-open"></i>&nbsp; <span>Katalog Buku</span></a>
            <a href="pesanan_saya.php" class="menu-item"><i class="fas fa-receipt"></i>&nbsp; <span>Pesanan Saya</span></a>
            <a href="pengaturan.php" class="menu-item"><i class="fas fa-user-gear"></i>&nbsp; <span>Profil Saya</span></a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;" onclick="return confirm('Keluar?')"><i class="fas fa-power-off"></i>&nbsp; <span>Keluar</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700;">Halo, <span style="color:var(--primary);"><?= htmlspecialchars((string)$first_name) ?></span></div>
            <div class="nav-right">
                <a href="wishlist.php" class="nav-icon-link"><i class="fas fa-heart"></i><?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?></a>
                <a href="keranjang.php" class="nav-icon-link"><i class="fas fa-shopping-bag"></i><?php if($jumlah_keranjang > 0): ?><span class="nav-badge"><?= $jumlah_keranjang ?></span><?php endif; ?></a>
                <div class="theme-toggle" id="themeSwitcher"><i class="fas fa-moon"></i></div>
                <img src="https://ui-avatars.com/api/?name=<?= $first_name ?>&background=6366f1&color=fff&bold=true" style="width: 35px; border-radius: 10px; margin-left: 5px;">
            </div>
        </header>

        <div class="category-container">
            <a href="katalog.php" class="cat-chip <?= empty($kat_filter) ? 'active' : '' ?>">Semua Koleksi</a>
            <?php
            $q_kat = mysqli_query($conn, "SELECT DISTINCT kategori FROM buku WHERE kategori IS NOT NULL AND kategori != ''");
            while($k = mysqli_fetch_assoc($q_kat)):
            ?>
            <a href="katalog.php?kategori=<?= urlencode($k['kategori']) ?>&search=<?= urlencode($keyword) ?>" 
               class="cat-chip <?= ($kat_filter == $k['kategori']) ? 'active' : '' ?>">
               <?= htmlspecialchars((string)$k['kategori']) ?>
            </a>
            <?php endwhile; ?>
        </div>

        <div class="content-body">
            <form action="" method="GET" class="search-box">
                <?php if(!empty($kat_filter)): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars((string)$kat_filter) ?>"><?php endif; ?>
                <input type="text" name="search" placeholder="Cari judul buku atau penulis..." value="<?= htmlspecialchars((string)$keyword) ?>">
                <button type="submit" class="btn-search">Cari</button>
            </form>

            <div class="book-grid">
                <?php
                $res_buku = mysqli_query($conn, $query_base);
                if(mysqli_num_rows($res_buku) > 0):
                    while($row = mysqli_fetch_assoc($res_buku)):
                        $id_buku = $row['id'];
                        $cek_w = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user='$id_user_login' AND id_buku='$id_buku'");
                        $is_wish = (mysqli_num_rows($cek_w) > 0);
                        $kat_display = $row['kategori'] ?? 'Umum';
                ?>
                <div class="book-card">
                    <a href="proses_wishlist.php?id=<?= $id_buku ?>" class="wish-btn <?= $is_wish ? 'active' : '' ?>"><i class="fa<?= $is_wish ? 's' : 'r' ?> fa-heart"></i></a>
                    <img src="../uploads/<?= $row['gambar'] ?>" class="book-img" onerror="this.src='https://placehold.co/200x300'">
                    <span class="book-cat"><?= htmlspecialchars((string)$kat_display) ?></span>
                    <div class="book-title"><?= htmlspecialchars((string)$row['judul']) ?></div>
                    <div class="book-price">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                    <a href="detail_buku.php?id=<?= $id_buku ?>" class="btn-buy">Lihat Detail</a>
                </div>
                <?php endwhile; else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 80px 0;">
                        <i class="fas fa-search fa-3x" style="opacity: 0.1; margin-bottom: 20px;"></i>
                        <p style="color: var(--text-muted);">Buku tidak ditemukan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        const themeSwitcher = document.getElementById('themeSwitcher');
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeSwitcher.innerHTML = '<i class="fas fa-sun"></i>';
        }
        themeSwitcher.addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeSwitcher.innerHTML = '<i class="fas fa-moon"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeSwitcher.innerHTML = '<i class="fas fa-sun"></i>';
            }
        });
    </script>
</body>
</html>