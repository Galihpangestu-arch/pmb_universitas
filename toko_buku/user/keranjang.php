<?php
session_start();
include '../config/koneksi.php';

// Proteksi: Jika belum login, tidak bisa akses keranjang
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user_login = $_SESSION['id_user'];

// Ambil Data User untuk Header
$ambil_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$id_user_login'");
$data_user = mysqli_fetch_assoc($ambil_user);
$full_name = $data_user['nama_lengkap'] ?? 'User';
$first_name = !empty($full_name) ? explode(' ', trim($full_name))[0] : 'User';

// Logika Hitung Badge Keranjang
$jumlah_keranjang = 0;
$keranjang_kosong = true;
if (isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
    $keranjang_kosong = false;
    foreach ($_SESSION['keranjang'] as $jumlah) { 
        $jumlah_keranjang += $jumlah; 
    }
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
    <title>Keranjang Belanja | BookStore Pro</title>
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

        /* --- NAVIGATION --- */
        .top-nav { 
            background: var(--card-bg); opacity: 0.98; backdrop-filter: blur(20px); 
            padding: 15px 8%; display: flex; justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 30px rgba(0,0,0,0.03);
        }
        
        .logo { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); text-decoration: none; letter-spacing: -1.5px; }
        .logo span { color: var(--primary); }

        .nav-right { display: flex; align-items: center; gap: 20px; }
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

        /* --- HERO SECTION --- */
        .container { padding: 40px 8%; max-width: 1500px; margin: 0 auto; }

        .cart-hero { 
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
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

        /* --- CART CONTENT --- */
        .cart-frame { 
            background: var(--card-bg); border-radius: 35px; padding: 45px; 
            border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; padding: 20px; color: var(--text-muted); 
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px;
            border-bottom: 2px solid var(--bg-main); font-weight: 800;
        }
        td { padding: 25px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        
        .book-info { display: flex; align-items: center; gap: 25px; }
        .book-info img { width: 70px; height: 100px; object-fit: cover; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .book-name { font-weight: 800; color: var(--text-dark); font-size: 1.1rem; }

        .qty-badge { background: var(--bg-main); padding: 10px 20px; border-radius: 15px; font-weight: 800; color: var(--primary); border: 1px solid var(--border); }

        .btn-hapus { 
            width: 45px; height: 45px; border-radius: 15px; border: 1px solid rgba(239, 68, 68, 0.2);
            display: flex; align-items: center; justify-content: center;
            color: var(--danger); text-decoration: none; transition: 0.3s;
        }
        .btn-hapus:hover { background: var(--danger); color: white; transform: rotate(15deg) scale(1.1); }

        /* Summary Section */
        .summary-box { margin-top: 50px; display: flex; justify-content: flex-end; }
        .summary-content { text-align: right; background: var(--bg-main); padding: 40px; border-radius: 30px; border: 1px solid var(--border); min-width: 400px; }
        .grand-total-label { color: var(--text-muted); font-weight: 700; font-size: 0.9rem; margin-bottom: 10px; display: block; text-transform: uppercase; }
        .grand-total-price { font-size: 2.5rem; font-weight: 900; color: var(--text-dark); display: block; margin-bottom: 30px; letter-spacing: -1.5px; }
        
        .btn-checkout { 
            background: var(--primary); color: white; padding: 20px 50px; 
            border-radius: 20px; text-decoration: none; font-weight: 800; 
            display: inline-flex; align-items: center; justify-content: center; gap: 15px; transition: 0.3s; 
            font-size: 1.1rem; box-shadow: 0 10px 20px var(--primary-glow); width: 100%;
        }
        .btn-checkout:hover { transform: translateY(-5px); filter: brightness(1.1); box-shadow: 0 15px 30px var(--primary-glow); }

        .empty-visual { text-align: center; padding: 80px 0; }
        .empty-visual i { font-size: 6rem; background: linear-gradient(var(--primary), #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 30px; }

        @media (max-width: 768px) {
            .cart-hero { padding: 30px; flex-direction: column; text-align: center; gap: 20px; }
            .summary-content { min-width: 100%; }
            .book-info img { display: none; }
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <header class="top-nav">
            <a href="dashboard.php" class="logo">ADMIN<span>PRO.</span></a>
            
            <div class="nav-right">
                <div style="text-align: right; border-right: 1px solid var(--border); padding-right: 20px;">
                    <span id="real-clock" style="font-weight: 800; font-size: 1rem; color: var(--text-dark); display: block;">00:00:00</span>
                    <span id="real-date" style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">...</span>
                </div>

                <a href="wishlist.php" class="nav-icon-link" title="Wishlist Saya">
                    <i class="fas fa-heart"></i>
                    <?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?>
                </a>

                <a href="keranjang.php" class="nav-icon-link" title="Keranjang Belanja" style="color: var(--primary); border-color: var(--primary);">
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
            <div class="cart-hero">
                <div class="hero-text">
                    <h1 id="greeting-text">Selamat Pagi</h1>
                    <p>Ada <strong><?= $jumlah_keranjang ?> item</strong> di keranjangmu yang siap diproses.</p>
                </div>
                <a href="katalog.php" class="btn-back-main">
                    <i class="fas fa-arrow-left"></i> Lanjut Belanja
                </a>
            </div>

            <div class="cart-frame">
                <?php if($keranjang_kosong): ?>
                    <div class="empty-visual">
                        <i class="fas fa-shopping-basket"></i>
                        <h2 style="font-weight: 800; margin-bottom: 10px;">Keranjangmu Masih Kosong!</h2>
                        <p style="color: var(--text-muted); margin-bottom: 40px;">Sepertinya kamu belum menemukan buku yang cocok.</p>
                        <a href="katalog.php" class="btn-checkout" style="width: auto; padding: 18px 60px;">Mulai Cari Buku</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Buku</th>
                                <th>Harga Satuan</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach($_SESSION['keranjang'] as $id_buku => $jumlah): 
                                $q = mysqli_query($conn, "SELECT * FROM buku WHERE id=$id_buku");
                                $d = mysqli_fetch_assoc($q);
                                if(!$d) continue; 
                                $subtotal = $d['harga'] * $jumlah;
                                $total += $subtotal;
                            ?>
                            <tr>
                                <td>
                                    <div class="book-info">
                                        <img src="../uploads/<?= $d['gambar'] ?>" onerror="this.src='https://placehold.co/100x150?text=Cover'">
                                        <div class="book-name"><?= htmlspecialchars($d['judul']) ?></div>
                                    </div>
                                </td>
                                <td style="font-weight: 700; font-size: 1rem;">Rp <?= number_format($d['harga'], 0, ',', '.') ?></td>
                                <td><span class="qty-badge"><?= $jumlah ?> Pcs</span></td>
                                <td style="color: var(--primary); font-weight: 800; font-size: 1.1rem;">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                <td>
                                    <a href="hapus_item.php?id=<?= $id_buku ?>" class="btn-hapus" onclick="return confirm('Hapus item ini?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="summary-box">
                        <div class="summary-content">
                            <span class="grand-total-label">Ringkasan Pesanan</span>
                            <span class="grand-total-price">Rp <?= number_format($total, 0, ',', '.') ?></span>
                            <a href="checkout.php" class="btn-checkout">
                                Lanjut Pembayaran <i class="fas fa-credit-card"></i>
                            </a>
                            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 20px; text-align: center;">
                                <i class="fas fa-shield-alt"></i> Pembayaran Aman & Terenkripsi
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // SCRIPT JAM & SAPAAN DASHBOARD-STYLE
        function updateClock() {
            const now = new Date();
            const hour = now.getHours();
            
            // Time & Date
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);

            // Dynamic Greeting
            let greet = (hour >= 5 && hour < 12) ? "Selamat Pagi" : 
                        (hour >= 12 && hour < 17) ? "Selamat Siang" : 
                        (hour >= 17 && hour < 20) ? "Selamat Sore" : "Selamat Malam";
            
            document.getElementById('greeting-text').innerText = greet + ", <?= htmlspecialchars($first_name) ?>!";
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