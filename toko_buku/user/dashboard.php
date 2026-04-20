<?php
session_start();
include '../config/koneksi.php';

// Proteksi login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil Nama User
$ambil_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$id_user'");
$data_user = mysqli_fetch_assoc($ambil_user);
$full_name = $data_user['nama_lengkap'] ?? 'User';
$first_name = !empty($full_name) ? explode(' ', trim($full_name))[0] : 'User';

// Hitung Badge Keranjang
$jumlah_keranjang = 0;
if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jumlah) { $jumlah_keranjang += $jumlah; }
}

// Hitung Badge Wishlist
$q_wishlist = mysqli_query($conn, "SELECT id FROM wishlist WHERE id_user = '$id_user'");
$jumlah_wishlist = mysqli_num_rows($q_wishlist);

// Ambil Statistik User
$total_pesanan_user = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pesanan WHERE id_user = '$id_user'"));

// Ambil Cover Buku untuk Slider
$query_marquee = mysqli_query($conn, "SELECT gambar FROM buku ORDER BY id DESC LIMIT 10");
$list_buku = [];
while($row = mysqli_fetch_assoc($query_marquee)){
    $list_buku[] = $row['gambar'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | BookStore Pro</title>
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

        /* --- SIDEBAR (KEMBALI KE DESAIN AWAL) --- */
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-grad); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 45px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; text-transform: uppercase; }
        .logo span { color: #38bdf8; }
        .sidebar-menu { padding: 30px 15px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white !important; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }

        /* --- WELCOME HERO (UNGU LEBAR) --- */
        .welcome-hero {
            margin: 30px 50px 20px; padding: 45px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border-radius: 35px; color: white; position: relative; overflow: hidden;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }
        .welcome-hero h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 10px; }
        .welcome-hero p { font-size: 1.1rem; opacity: 0.9; max-width: 600px; line-height: 1.6; }
        .welcome-hero i.bg-icon { position: absolute; right: -20px; bottom: -30px; font-size: 12rem; opacity: 0.15; transform: rotate(-15deg); }

        /* --- STATS GRID --- */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; padding: 20px 50px 40px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 24px; border: 1px solid var(--border); display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .stat-icon { width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: rgba(99, 102, 241, 0.1); color: var(--primary); font-size: 1.2rem; }

        /* --- MARQUEE SLIDER --- */
        .scrolling-frame { margin: 10px 50px 40px; background: var(--card-bg); padding: 30px; border-radius: 32px; border: 1px solid var(--border); overflow: hidden; }
        .marquee-wrapper { display: flex; overflow: hidden; gap: 30px; mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); }
        .marquee-content { display: flex; gap: 30px; animation: scroll 40s linear infinite; }
        .book-item { width: 140px; height: 200px; flex-shrink: 0; border-radius: 15px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .book-item img { width: 100%; height: 100%; object-fit: cover; }
        @keyframes scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }

        /* --- NAV RIGHT --- */
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .nav-icon-link { position: relative; color: var(--text-dark); text-decoration: none; font-size: 1.1rem; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--border); background: none; cursor: pointer; }
        .nav-badge { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; border: 2px solid var(--card-bg); font-weight: 800; }
        .clock-container { text-align: right; border-right: 1px solid var(--border); padding-right: 20px; }
        #real-clock { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); display: block; }
        .theme-toggle { cursor: pointer; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--bg-main); border: 1px solid var(--border); color: var(--text-dark); }

        /* --- LIVE CHAT & MODAL --- */
        .chat-widget { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .chat-btn { width: 60px; height: 60px; background: var(--primary); color: white; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; cursor: pointer; box-shadow: 0 10px 25px var(--primary-glow); transition: 0.3s; }
        .chat-window { position: absolute; bottom: 80px; right: 0; width: 300px; background: var(--card-bg); border-radius: 25px; border: 1px solid var(--border); display: none; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15); animation: slideUp 0.3s ease; }
        .chat-header { background: var(--primary); padding: 15px; color: white; display: flex; align-items: center; gap: 12px; }
        .wa-chat-btn { background: #25d366; color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 12px; font-weight: 700; margin: 15px; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 2000; }
        .modal-card { background: var(--card-bg); width: 90%; max-width: 500px; padding: 40px; border-radius: 30px; border: 1px solid var(--border); text-align: center; position: relative; animation: slideUp 0.4s ease; }
        .close-modal { position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-muted); }

        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; } .sidebar span, .sidebar-header { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .welcome-hero, .stats-grid, .scrolling-frame { margin-left: 20px; margin-right: 20px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><a href="dashboard.php" class="logo">BOOK<span>STORE.</span></a></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active"><i class="fas fa-house"></i>&nbsp; <span>Beranda</span></a>
            <a href="katalog.php" class="menu-item"><i class="fas fa-book-open"></i>&nbsp; <span>Katalog Buku</span></a>
            <a href="pesanan_saya.php" class="menu-item"><i class="fas fa-receipt"></i>&nbsp; <span>Pesanan Saya</span></a>
            <a href="pengaturan.php" class="menu-item"><i class="fas fa-user-gear"></i>&nbsp; <span>Profil Saya</span></a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i>&nbsp; <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700; color: var(--text-muted);">Dashboard Pengguna</div>
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00.00.00</span>
                    <span id="real-date" style="font-size: 0.6rem; color: var(--text-muted); font-weight: 600;">...</span>
                </div>

                <button onclick="toggleAbout()" class="nav-icon-link" title="Tentang Kami">
                    <i class="fas fa-circle-info"></i>
                </button>

                <a href="wishlist.php" class="nav-icon-link" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <?php if($jumlah_wishlist > 0): ?><span class="nav-badge"><?= $jumlah_wishlist ?></span><?php endif; ?>
                </a>

                <a href="keranjang.php" class="nav-icon-link" title="Keranjang">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if($jumlah_keranjang > 0): ?><span class="nav-badge"><?= $jumlah_keranjang ?></span><?php endif; ?>
                </a>

                <div class="theme-toggle" id="themeSwitcher"><i class="fas fa-moon"></i></div>
                <img src="https://ui-avatars.com/api/?name=<?= $first_name ?>&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);">
            </div>
        </header>

        <section class="welcome-hero">
            <i class="fas fa-book-reader bg-icon"></i>
            <h1 id="greeting-text">Selamat Datang</h1>
            <p>Halo, <strong><?= htmlspecialchars($first_name) ?></strong>! Senang melihatmu kembali. Temukan koleksi buku favoritmu hari ini.</p>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <p style="font-size:0.65rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Pesanan</p>
                    <h3 style="font-size: 1.4rem;"><?= $total_pesanan_user ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#10b981; background:rgba(16, 185, 129, 0.1);"><i class="fas fa-heart"></i></div>
                <div class="stat-info">
                    <p style="font-size:0.65rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Wishlist</p>
                    <h3 style="font-size: 1.4rem;"><?= $jumlah_wishlist ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#f59e0b; background:rgba(245, 158, 11, 0.1);"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <p style="font-size:0.65rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Poin</p>
                    <h3 style="font-size: 1.4rem;">1.250</h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#38bdf8; background:rgba(56, 189, 248, 0.1);"><i class="fas fa-certificate"></i></div>
                <div class="stat-info">
                    <p style="font-size:0.65rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Status</p>
                    <h3 style="font-size: 1.4rem;">Premium</h3>
                </div>
            </div>
        </section>

        <div style="margin: 0 55px; font-weight: 800; font-size: 1.1rem; margin-bottom: 5px;">Rekomendasi Buku</div>
        <section class="scrolling-frame">
            <div class="marquee-wrapper">
                <div class="marquee-content">
                    <?php 
                    $display_list = array_merge($list_buku, $list_buku);
                    foreach($display_list as $img): ?>
                        <div class="book-item">
                            <img src="../uploads/<?= $img ?>" onerror="this.src='https://placehold.co/150x220'">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <div class="chat-widget">
        <div class="chat-window" id="chatWindow">
            <div class="chat-header">
                <img src="https://ui-avatars.com/api/?name=Admin&background=fff&color=6366f1" style="width: 35px; border-radius: 50%;">
                <div style="font-weight: 800; font-size: 0.8rem;">Customer Service</div>
            </div>
            <div style="padding: 15px; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                Halo! Ada yang bisa kami bantu hari ini?
            </div>
            <a href="https://wa.me/628123456789" target="_blank" class="wa-chat-btn">
                <i class="https://wa.me/qr/LEHWIZDWEBOWF1"></i> Chat WhatsApp
            </a>
        </div>
        <div class="chat-btn" onclick="toggleChat()" id="chatBtn"><i class="fas fa-comment-dots"></i></div>
    </div>

    <div id="aboutModal" class="modal-overlay" onclick="toggleAbout()">
        <div class="modal-card" onclick="event.stopPropagation()">
            <span class="close-modal" onclick="toggleAbout()">&times;</span>
            <h2 style="font-weight: 800; margin-bottom: 15px; color: var(--primary);">BookStore Pro</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.7;">
                BookStore Pro adalah perpustakaan digital premium yang menghadirkan koleksi buku terbaik untuk pengalaman membaca yang modern dan menyenangkan.
            </p>
        </div>
    </div>

    <script>
        function toggleChat() {
            const chat = document.getElementById('chatWindow');
            chat.style.display = (chat.style.display === 'block') ? 'none' : 'block';
        }
        function toggleAbout() {
            const modal = document.getElementById('aboutModal');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }
        function runClock() {
            const now = new Date();
            const hour = now.getHours();
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false }).replace(/:/g, '.');
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            let greet = (hour >= 5 && hour < 12) ? "Selamat Pagi" : (hour >= 12 && hour < 15) ? "Selamat Siang" : (hour >= 15 && hour < 18) ? "Selamat Sore" : "Selamat Malam";
            document.getElementById('greeting-text').innerText = greet + ", <?= $first_name ?>!";
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