<?php
session_start();
include '../config/koneksi.php';

// Proteksi: Harus Login & Keranjang Tidak Boleh Kosong
if (!isset($_SESSION['id_user']) || empty($_SESSION['keranjang'])) {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil Data User untuk Header & Form
$res_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($res_user);
$full_name = $user['nama_lengkap'] ?? 'User';
$first_name = !empty($full_name) ? explode(' ', trim($full_name))[0] : 'User';

// Hitung Badge Wishlist & Keranjang untuk Header
$jumlah_keranjang = 0;
foreach ($_SESSION['keranjang'] as $jumlah) { $jumlah_keranjang += $jumlah; }

$q_wishlist_count = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user'");
$jumlah_wishlist = mysqli_num_rows($q_wishlist_count);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan | BookStore Pro</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-main); color: var(--text-dark); min-height: 100vh; }

        /* --- NAVIGATION --- */
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
        .nav-badge { 
            position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; 
            font-size: 0.7rem; padding: 3px 7px; border-radius: 50%; border: 3px solid var(--card-bg); font-weight: 800; 
        }
        .theme-toggle { cursor: pointer; width: 45px; height: 45px; border-radius: 15px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); }

        /* --- CONTAINER --- */
        .container { padding: 40px 8%; max-width: 1400px; margin: 0 auto; }

        /* Hero Banner */
        .checkout-hero { 
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            border-radius: 40px; padding: 40px 50px; margin-bottom: 40px; color: white;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 20px 40px var(--primary-glow);
        }
        .hero-text h1 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; }
        .hero-text p { opacity: 0.9; font-size: 1rem; }

        /* Checkout Grid */
        .checkout-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; align-items: start; }

        .card { 
            background: var(--card-bg); padding: 40px; border-radius: 35px; 
            border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .card h2 { font-size: 1.4rem; font-weight: 800; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
        .card h2 i { color: var(--primary); }

        /* Form Styling */
        .form-group { margin-bottom: 25px; }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        
        input, textarea, select { 
            width: 100%; padding: 16px 20px; border-radius: 18px; border: 2px solid var(--border); 
            background: var(--bg-main); color: var(--text-dark); font-family: inherit; font-weight: 600; outline: none;
        }
        input:focus, textarea:focus, select:focus { border-color: var(--primary); background: var(--card-bg); box-shadow: 0 0 0 4px var(--primary-glow); }

        /* Summary List */
        .summary-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed var(--border); }
        .summary-item:last-child { border-bottom: none; }
        .item-name { font-weight: 700; color: var(--text-dark); }
        .item-price { font-weight: 800; color: var(--primary); }

        .total-box { 
            margin-top: 25px; padding: 25px; border-radius: 25px; 
            background: var(--bg-main); border: 2px solid var(--primary);
            display: flex; justify-content: space-between; align-items: center;
        }
        .total-label { font-weight: 800; font-size: 1.1rem; }
        .total-amount { font-size: 1.8rem; font-weight: 900; color: var(--primary); letter-spacing: -1px; }

        .btn-order { 
            width: 100%; background: var(--primary); color: white; padding: 20px; 
            border: none; border-radius: 20px; font-weight: 800; font-size: 1.1rem;
            cursor: pointer; margin-top: 30px; box-shadow: 0 10px 20px var(--primary-glow);
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        .btn-order:hover { transform: translateY(-5px); filter: brightness(1.1); box-shadow: 0 15px 30px var(--primary-glow); }

        @media (max-width: 992px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .checkout-hero { flex-direction: column; text-align: center; gap: 20px; }
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <a href="dashboard.php" class="logo">BOOK<span>STORE.</span></a>
        
        <div class="nav-right">
            <div class="nav-clock-section">
                <span id="real-clock">00:00:00</span>
                <span id="real-date">...</span>
            </div>

            <a href="wishlist.php" class="nav-icon-link" title="Wishlist">
                <i class="fas fa-heart"></i>
                <?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?>
            </a>

            <a href="keranjang.php" class="nav-icon-link" title="Keranjang">
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
        <div class="checkout-hero">
            <div class="hero-text">
                <h1>Finalisasi Pesanan</h1>
                <p>Sedikit lagi buku impianmu akan segera dikirim, <b><?= htmlspecialchars($first_name) ?></b>!</p>
            </div>
            <i class="fas fa-box-open fa-3x" style="opacity: 0.3;"></i>
        </div>

        <form action="proses_checkout.php" method="POST" class="checkout-grid">
            <div class="card">
                <h2><i class="fas fa-map-location-dot"></i> Informasi Pengiriman</h2>
                <div class="form-group">
                    <label>Nama Penerima</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required placeholder="Masukkan nama lengkap">
                </div>
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" name="telepon" value="<?= htmlspecialchars($user['no_telp']) ?>" required placeholder="Contoh: 0812xxxx">
                </div>
                <div class="form-group">
                    <label>Alamat Pengiriman Lengkap</label>
                    <textarea name="alamat" rows="4" placeholder="Nama Jalan, No. Rumah, RT/RW, Kecamatan, Kota, Kode Pos" required></textarea>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode_bayar">
                        <option value="Transfer Bank">Transfer Bank (BCA / Mandiri)</option>
                        <option value="E-Wallet">E-Wallet (Dana / Gopay / OVO)</option>
                        <option value="COD">Bayar di Tempat (COD)</option>
                    </select>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-receipt"></i> Ringkasan Belanja</h2>
                <div style="margin-bottom: 20px;">
                    <?php 
                    $total_final = 0;
                    foreach($_SESSION['keranjang'] as $id => $jumlah): 
                        $q = mysqli_query($conn, "SELECT * FROM buku WHERE id=$id");
                        $d = mysqli_fetch_assoc($q);
                        $sub = $d['harga'] * $jumlah;
                        $total_final += $sub;
                    ?>
                    <div class="summary-item">
                        <div>
                            <p class="item-name"><?= htmlspecialchars($d['judul']) ?></p>
                            <p style="font-size: 0.8rem; color: var(--text-muted);"><?= $jumlah ?>x @ Rp <?= number_format($d['harga']) ?></p>
                        </div>
                        <span class="item-price">Rp <?= number_format($sub) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-box">
                    <span class="total-label">Total Pembayaran</span>
                    <span class="total-amount">Rp <?= number_format($total_final) ?></span>
                </div>
                
                <input type="hidden" name="total_bayar" value="<?= $total_final ?>">
                
                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 25px 0; text-align: center; line-height: 1.5;">
                    <i class="fas fa-shield-check" style="color: var(--primary);"></i> Transaksi aman & terenkripsi. Dengan menekan tombol di bawah, Anda menyetujui syarat & ketentuan kami.
                </p>
                
                <button type="submit" class="btn-order">
                    Konfirmasi & Buat Pesanan <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
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