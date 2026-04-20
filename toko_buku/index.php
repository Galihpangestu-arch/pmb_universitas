<?php
// Mulai session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config/koneksi.php';

// Jika sudah login, lempar ke dashboard sesuai role
if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

// 1. Ambil data asli dari Database untuk Statistik
$res_buku = mysqli_query($conn, "SELECT id FROM buku");
$total_buku = ($res_buku) ? mysqli_num_rows($res_buku) : 0;

$res_user = mysqli_query($conn, "SELECT id FROM users WHERE role='user'");
$total_user = ($res_user) ? mysqli_num_rows($res_user) : 0;

$res_pesanan = mysqli_query($conn, "SELECT id FROM pesanan");
$total_pesanan = ($res_pesanan) ? mysqli_num_rows($res_pesanan) : 0;

// 2. Ambil Cover Buku untuk Slider (Terbaru)
$query_slider = mysqli_query($conn, "SELECT gambar FROM buku ORDER BY id DESC LIMIT 12");
$list_buku = [];
if ($query_slider) {
    while($row = mysqli_fetch_assoc($query_slider)){
        $list_buku[] = $row['gambar'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore Pro | Koleksi Buku Digital Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg-body: #f8fafc;
            --dark: #0f172a;
            --white: #ffffff;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --subnav-bg: rgba(248, 250, 252, 0.8);
        }

        [data-theme="dark"] {
            --bg-body: #020617;
            --white: #1e293b;
            --dark: #f8fafc;
            --text-muted: #94a3b8;
            --border: #1e293b;
            --nav-bg: rgba(15, 23, 42, 0.9);
            --subnav-bg: rgba(30, 41, 59, 0.6);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: all 0.3s ease; }
        body { background-color: var(--bg-body); color: var(--dark); overflow-x: hidden; scroll-behavior: smooth; }

        /* --- NAVIGATION --- */
        .header-nav-wrapper { position: sticky; top: 0; z-index: 10000; backdrop-filter: blur(20px); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .navbar-main { padding: 1rem 8%; display: flex; justify-content: space-between; align-items: center; background: var(--nav-bg); }
        .logo { font-size: 1.6rem; font-weight: 800; color: var(--dark); text-decoration: none; letter-spacing: -1.5px; }
        .logo span { color: var(--primary); }

        .auth-buttons { display: flex; gap: 15px; align-items: center; }
        .btn-register { text-decoration: none; color: var(--text-muted); font-weight: 700; font-size: 0.85rem; }
        .btn-login { text-decoration: none; background: var(--primary); color: white !important; font-weight: 700; font-size: 0.85rem; padding: 10px 24px; border-radius: 12px; box-shadow: 0 5px 15px var(--primary-glow); }
        .theme-toggle { cursor: pointer; color: var(--text-muted); font-size: 1.2rem; margin-left: 10px; }

        /* --- CONTACT BAR --- */
        .navbar-contact { padding: 0.6rem 8%; background: var(--subnav-bg); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); display: flex; gap: 30px; justify-content: center; }
        .contact-link { text-decoration: none; color: var(--text-muted); font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px; text-transform: uppercase; }
        
        /* Contact Hover Effects */
        .contact-link:hover.ig { color: #E1306C; }
        .contact-link:hover.wa { color: #25D366; }
        .contact-link:hover.mail { color: #ef4444; }
        .contact-link:hover { transform: translateY(-2px); }

        /* --- HERO --- */
        .hero { text-align: center; padding: 100px 8% 60px; background: radial-gradient(circle at 50% 0%, var(--primary-glow) 0%, transparent 70%); }
        .hero h1 { font-size: 3.8rem; font-weight: 900; letter-spacing: -2px; line-height: 1.1; margin-bottom: 25px; }
        .hero h1 span { background: linear-gradient(90deg, var(--primary), #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { color: var(--text-muted); font-size: 1.2rem; max-width: 700px; margin: 0 auto; line-height: 1.6; }

        /* --- SLIDER --- */
        .marquee-wrapper { margin: 40px 0; display: flex; overflow: hidden; gap: 30px; mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent); }
        .marquee-content { display: flex; gap: 30px; animation: scroll 45s linear infinite; }
        .book-item { width: 190px; height: 270px; flex-shrink: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 4px solid var(--white); background: #eee; }
        .book-item img { width: 100%; height: 100%; object-fit: cover; }
        @keyframes scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }

        /* --- STATS --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; padding: 0 8% 100px; max-width: 1300px; margin: 0 auto; }
        .stat-card { background: var(--white); padding: 40px 20px; border-radius: 30px; text-align: center; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .stat-card i { font-size: 2.2rem; color: var(--primary); margin-bottom: 15px; display: block; }
        .stat-card h3 { font-size: 2.5rem; font-weight: 800; margin-bottom: 5px; }
        .stat-card p { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; }

        /* --- FOOTER --- */
        .footer { background: var(--dark); padding: 80px 8% 40px; color: white; border-radius: 60px 60px 0 0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 50px; margin-bottom: 50px; }
        .footer h4 { margin-bottom: 20px; font-size: 1.2rem; color: #fff; }
        .footer p { color: #94a3b8; font-size: 0.9rem; line-height: 1.8; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .navbar-contact { display: none; }
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="header-nav-wrapper">
        <nav class="navbar-main">
            <a href="index.php" class="logo">
                <i class="fas fa-book-bookmark"></i> BOOK<span>STORE.</span>
            </a>

            <div class="nav-right-group">
                <div class="auth-buttons">
                    <a href="register.php" class="btn-register">Daftar Akun</a>
                    <a href="login.php" class="btn-login">Masuk</a>
                    <div class="theme-toggle" id="themeBtn"><i class="fas fa-moon"></i></div>
                </div>
            </div>
        </nav>

        <div class="navbar-contact">
            <a href="https://www.instagram.com/16.pngestu?igsh=dm5uaWtoODcxeHhi" target="_blank" class="contact-link ig">
                <i class="fab fa-instagram"></i> <span>Instagram</span>
            </a>
            <a href="https://wa.me/qr/LEHWIZDWEBOWF1" target="_blank" class="contact-link wa">
                <i class="fab fa-whatsapp"></i> <span>WhatsApp Kami</span>
            </a>
            <a href="galihagengpangestu@gmail.com" class="contact-link mail">
                <i class="far fa-envelope"></i> <span>Email Layanan</span>
            </a>
        </div>
    </div>

    <header class="hero">
        <h1>Buka Jendela <span>Dunia</span> <br>Lewat Setiap Halaman.</h1>
        <p>Platform perpustakaan digital terpercaya dengan koleksi buku terlengkap. Nikmati pengalaman membaca premium dengan antarmuka modern dan sistem keamanan terbaik.</p>
    </header>

    <div class="marquee-wrapper">
        <div class="marquee-content">
            <?php 
            if(!empty($list_buku)){
                $scroll_list = array_merge($list_buku, $list_buku);
                foreach($scroll_list as $img){
                    echo '<div class="book-item"><img src="uploads/'.htmlspecialchars((string)$img).'" onerror="this.src=\'https://images.unsplash.com/photo-1543004223-249a4a2725ad?q=80&w=400\'"></div>';
                }
            } else {
                for($i=1; $i<=12; $i++) echo '<div class="book-item"><img src="https://picsum.photos/200/300?random='.$i.'"></div>';
            }
            ?>
        </div>
    </div>

    <section class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-book-open"></i>
            <h3><?= number_format($total_buku) ?>+</h3>
            <p>Koleksi Buku</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h3><?= number_format($total_user) ?></h3>
            <p>Pembaca Aktif</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-shopping-basket"></i>
            <h3><?= number_format($total_pesanan) ?></h3>
            <p>Total Transaksi</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-shield-halved"></i>
            <h3>Aman</h3>
            <p>Sistem Terproteksi</p>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-grid">
            <div>
                <a href="#" class="logo" style="color: white; margin-bottom: 20px; display: block;">BOOK<span>STORE.</span></a>
                <p>Menyediakan akses bacaan berkualitas untuk meningkatkan literasi masyarakat Indonesia di era digital secara praktis dan modern.</p>
            </div>
            <div>
                <h4>Navigasi</h4>
                <p>Katalog Buku</p>
                <p>Tentang Kami</p>
                <p>Syarat & Ketentuan</p>
                <p>Kebijakan Privasi</p>
            </div>
            <div>
                <h4>Kantor Pusat</h4>
                <p>Gedung Literasi Digital</p>
                <p>Lantai 12, Jakarta Timur</p>
                <p>DKI Jakarta, Indonesia</p>
            </div>
        </div>
        <div style="text-align:center; padding-top: 30px; border-top: 1px solid #1e293b; font-size: 0.8rem; color: #64748b;">
            &copy; 2026 BookStore Pro. Hak Cipta Dilindungi Undang-Undang.
        </div>
    </footer>

    <script>
        const themeBtn = document.getElementById('themeBtn');
        const html = document.documentElement;

        if (localStorage.getItem('theme') === 'dark') {
            html.setAttribute('data-theme', 'dark');
            themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
        }

        themeBtn.addEventListener('click', () => {
            if (html.getAttribute('data-theme') === 'dark') {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
            }
        });
    </script>
</body>
</html>