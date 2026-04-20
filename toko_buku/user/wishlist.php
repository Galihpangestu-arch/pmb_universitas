<?php
session_start();
include '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Silahkan login untuk melihat wishlist Anda'); window.location='../login.php';</script>";
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil Data User
$ambil_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($ambil_user);
$full_name = $data_user['nama_lengkap'] ?? 'User';
$first_name = !empty($full_name) ? explode(' ', trim($full_name))[0] : 'User';

// Badge Keranjang
$jumlah_keranjang = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jumlah) { $jumlah_keranjang += $jumlah; }
}

// Badge Wishlist
$q_wishlist_count = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user'");
$jumlah_wishlist = mysqli_num_rows($q_wishlist_count);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Wishlist | BookStore Pro</title>
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

        /* --- NAVIGATION (Identical to Cart) --- */
        .top-nav { 
            background: var(--card-bg); opacity: 0.98; backdrop-filter: blur(20px); 
            padding: 15px 8%; display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 30px rgba(0,0,0,0.03);
        }
        
        .logo { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); text-decoration: none; letter-spacing: -1.5px; }
        .logo span { color: var(--primary); }

        .nav-right { display: flex; align-items: center; gap: 20px; }
        
        /* Clock Style from Cart */
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
        .user-avatar { width: 42px; border-radius: 14px; border: 2px solid var(--primary); padding: 2px; }

        /* --- CONTENT SECTION --- */
        .container { padding: 40px 8%; max-width: 1500px; margin: 0 auto; }

        /* Hero Banner from Cart Style */
        .wishlist-hero { 
            background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%);
            border-radius: 40px; padding: 50px; margin-bottom: 50px; color: white;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 20px 40px var(--primary-glow);
            position: relative; overflow: hidden;
        }

        .hero-text h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -1px; }
        .hero-text p { opacity: 0.9; font-size: 1.1rem; }

        .btn-back-main { 
            background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; 
            border-radius: 20px; text-decoration: none; font-weight: 700; backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; gap: 10px;
        }
        .btn-back-main:hover { background: white; color: var(--primary); transform: translateX(-10px); }

        /* --- GRID & CARDS --- */
        .wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 40px; }
        
        .book-card { 
            background: var(--card-bg); border-radius: 35px; padding: 25px; 
            border: 1px solid var(--border); position: relative; display: flex; flex-direction: column;
        }
        .book-card:hover { transform: translateY(-15px); border-color: var(--primary); box-shadow: 0 30px 60px rgba(0,0,0,0.1); }
        
        .img-holder { position: relative; width: 100%; height: 380px; border-radius: 25px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        .book-img { width: 100%; height: 100%; object-fit: cover; }
        
        .remove-icon { 
            position: absolute; top: 15px; right: 15px; background: var(--danger); 
            color: white; width: 45px; height: 45px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; text-decoration: none; 
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); z-index: 10; opacity: 0; transform: scale(0.5);
        }
        .book-card:hover .remove-icon { opacity: 1; transform: scale(1); }
        
        .info-box h4 { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; line-height: 1.3; }
        .price-tag { 
            background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 8px 15px; 
            border-radius: 12px; font-weight: 800; font-size: 1.2rem; display: inline-block; margin-bottom: 25px; 
        }
        
        .btn-action-primary { 
            width: 100%; padding: 18px; background: var(--primary); color: white; 
            border: none; border-radius: 20px; font-weight: 800; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; gap: 12px; text-decoration: none; 
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        @media (max-width: 768px) {
            .wishlist-hero { padding: 30px; flex-direction: column; text-align: center; gap: 20px; }
            .nav-clock-section { display: none; }
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

                <a href="wishlist.php" class="nav-icon-link" title="Wishlist Saya" style="color: var(--primary); border-color: var(--primary);">
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
                
                <img src="https://ui-avatars.com/api/?name=<?= $first_name ?>&background=6366f1&color=fff&bold=true" class="user-avatar" alt="User">
            </div>
        </header>

        <div class="container">
            <div class="wishlist-hero">
                <div class="hero-text">
                    <h1 id="greeting-text">Selamat Pagi</h1>
                    <p>Kamu punya <strong><?= $jumlah_wishlist ?> buku</strong> impian yang siap untuk dibawa pulang.</p>
                </div>
                <a href="katalog.php" class="btn-back-main">
                    <i class="fas fa-arrow-left"></i> Lanjut Belanja
                </a>
            </div>

            <div class="wishlist-grid">
                <?php
                $query = mysqli_query($conn, "SELECT buku.* FROM wishlist 
                                             JOIN buku ON wishlist.id_buku = buku.id 
                                             WHERE wishlist.id_user = '$id_user' 
                                             ORDER BY wishlist.id DESC");
                
                if (mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                ?>
                <div class="book-card">
                    <a href="proses_wishlist.php?id=<?= $row['id'] ?>" class="remove-icon" onclick="return confirm('Hapus dari wishlist?')" title="Hapus Permanen">
                        <i class="fas fa-trash"></i>
                    </a>
                    
                    <div class="img-holder">
                        <img src="../uploads/<?= $row['gambar'] ?>" class="book-img" onerror="this.src='https://images.unsplash.com/photo-1543004223-249a4a2725ad?q=80&w=400'">
                    </div>

                    <div class="info-box">
                        <h4><?= htmlspecialchars($row['judul']) ?></h4>
                        <div class="price-tag">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                        
                        <a href="proses_keranjang.php?id=<?= $row['id'] ?>&aksi=tambah" class="btn-action-primary">
                            <i class="fas fa-cart-arrow-down"></i> Check Out Sekarang
                        </a>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo "<div style='text-align: center; grid-column: 1/-1; padding: 100px 0;'>
                            <i class='fas fa-heart-broken' style='font-size: 5rem; color: var(--primary); opacity: 0.2; margin-bottom: 20px; display: block;'></i>
                            <h2>Belum ada buku impian.</h2>
                            <p style='color: var(--text-muted);'>Mungkin katalog kami punya sesuatu yang menarik?</p>
                          </div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const hour = now.getHours();
            
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);

            let greet = (hour >= 5 && hour < 12) ? "Selamat Pagi" : 
                        (hour >= 12 && hour < 17) ? "Selamat Siang" : 
                        (hour >= 17 && hour < 20) ? "Selamat Sore" : "Selamat Malam";
            
            document.getElementById('greeting-text').innerText = greet + ", <?= htmlspecialchars($first_name) ?>!";
        }
        setInterval(updateClock, 1000);
        updateClock();

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