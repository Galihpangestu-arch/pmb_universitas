<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Mendapatkan nama file saat ini untuk logika Sidebar Active
$current_page = basename($_SERVER['PHP_SELF']);

// --- 1. LOGIKA TAMBAH KATEGORI ---
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($conn, trim($_POST['nama_kategori']));
    $cek_dulu = mysqli_query($conn, "SELECT * FROM kategori WHERE LOWER(nama_kategori) = LOWER('$nama_kategori')");
    
    if (mysqli_num_rows($cek_dulu) > 0) {
        echo "<script>alert('Gagal! Nama Kategori tersebut sudah terdaftar.'); window.location='kategori.php';</script>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')");
        if ($insert) {
            echo "<script>alert('Kategori Berhasil Ditambahkan!'); window.location='kategori.php';</script>";
        }
    }
}

// --- 2. LOGIKA UPDATE KATEGORI (EDIT) ---
if (isset($_POST['edit_kategori'])) {
    $id = $_POST['id'];
    $nama_baru = mysqli_real_escape_string($conn, trim($_POST['nama_kategori']));
    
    $update = mysqli_query($conn, "UPDATE kategori SET nama_kategori = '$nama_baru' WHERE id = '$id'");
    if ($update) {
        // Update kategori di tabel buku agar tetap sinkron
        mysqli_query($conn, "UPDATE buku SET kategori = '$nama_baru' WHERE kategori = (SELECT nama_kategori FROM kategori WHERE id='$id')");
        echo "<script>alert('Kategori Berhasil Diperbarui!'); window.location='kategori.php';</script>";
    }
}

// --- 3. LOGIKA HAPUS KATEGORI ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kategori WHERE id = '$id_hapus'");
    header("Location: kategori.php");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Buku | Admin Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --sidebar-grad: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            
            /* Light Mode */
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

        /* --- SIDEBAR (SAMA PERSIS DENGAN DASHBOARD) --- */
        .sidebar { width: var(--sidebar-width); background: var(--sidebar-grad); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 45px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: white; text-decoration: none; letter-spacing: -1px; }
        .logo span { color: #38bdf8; }
        .sidebar-menu { padding: 30px 15px; flex-grow: 1; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255, 255, 255, 0.4); text-decoration: none; border-radius: 18px; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; }
        .menu-item i { width: 30px; font-size: 1.1rem; }
        .menu-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
        .menu-item.active { background: var(--primary); color: white; font-weight: 700; box-shadow: 0 10px 20px -5px var(--primary-glow); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); flex-grow: 1; }
        .top-nav { background: var(--card-bg); opacity: 0.95; backdrop-filter: blur(15px); padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }

        /* Clock & Nav Right */
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

        /* --- CONTENT BODY --- */
        .content-body { padding: 40px 50px; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 32px; border: 1px solid var(--border); margin-bottom: 30px; }
        
        input { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--bg-main); color: var(--text-dark); outline: none; margin-top: 8px; font-weight: 600; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 14px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; width: 100%; margin-top: 15px; }

        /* Table Style */
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        .custom-table td { padding: 20px 15px; border-bottom: 1px solid var(--border); vertical-align: middle; font-weight: 600; }

        /* Visuals */
        .cover-stack { display: flex; align-items: center; }
        .cover-item { width: 40px; height: 55px; object-fit: cover; border-radius: 6px; border: 2px solid white; box-shadow: 2px 0 5px rgba(0,0,0,0.1); margin-left: -12px; }
        .cover-item:first-child { margin-left: 0; }
        .count-badge { background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; }

        .btn-edit { color: #38bdf8; background: rgba(56, 189, 248, 0.1); width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; margin-right: 5px; }
        .btn-delete { color: #ef4444; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }
        
        /* Modal Style */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); }
        .modal-content { background: var(--card-bg); margin: 10% auto; padding: 40px; border-radius: 35px; width: 450px; border: 1px solid var(--border); animation: cardEntrance 0.4s ease; }
        @keyframes cardEntrance { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><a href="dashboard.php" class="logo">ADMIN<span>PRO.</span></a></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
            </a>
            <a href="kategori.php" class="menu-item <?= ($current_page == 'kategori.php') ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> <span>Kategori Buku</span>
            </a>
            <a href="kelola_buku.php" class="menu-item <?= ($current_page == 'kelola_buku.php') ? 'active' : '' ?>">
                <i class="fas fa-book-bookmark"></i> <span>Koleksi Buku</span>
            </a>
            <a href="kelola_pelanggan.php" class="menu-item <?= ($current_page == 'kelola_pelanggan.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> <span>Database User</span>
            </a>
            <a href="pesanan.php" class="menu-item <?= ($current_page == 'pesanan.php') ? 'active' : '' ?>">
                <i class="fas fa-shopping-bag"></i> <span>Order Masuk</span>
            </a>
            <a href="laporan.php" class="menu-item <?= ($current_page == 'laporan.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> <span>Laporan Omzet</span>
            </a>
            <a href="pengaturan.php" class="menu-item <?= ($current_page == 'pengaturan.php') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
        </nav>
        <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="../logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div style="font-weight: 700; font-size: 1.1rem;">
                Manajemen <span style="color:var(--primary);">Kategori Buku</span>
            </div>
            
            <div class="nav-right">
                <div class="clock-container">
                    <span id="real-clock">00:00:00</span>
                    <span id="real-date">Memuat Hari...</span>
                </div>
                <div class="theme-toggle" id="themeSwitcher">
                    <i class="fas fa-moon"></i>
                </div>
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff&bold=true" style="width: 40px; border-radius: 12px; border: 2px solid var(--border);" alt="">
            </div>
        </header>
        
        <div class="content-body">
            <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: start;">
                
                <div class="card">
                    <h3 style="font-weight: 800; margin-bottom: 10px; font-size: 1.1rem;">Tambah Kategori</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 20px;">Pisahkan jenis koleksi buku Anda.</p>
                    <form method="POST">
                        <label style="font-size:0.7rem; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Nama Kategori</label>
                        <input type="text" name="nama_kategori" placeholder="Contoh: Komik, Novel..." required>
                        <button type="submit" name="tambah_kategori" class="btn-submit">Simpan Kategori</button>
                    </form>
                </div>

                <div class="card">
                    <h3 style="font-weight: 800; margin-bottom: 20px; font-size: 1.1rem;">Daftar Kategori Aktif</h3>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Sampel Koleksi</th>
                                <th style="text-align: center;">Total</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT MAX(id) as id, nama_kategori FROM kategori GROUP BY nama_kategori ORDER BY nama_kategori ASC");
                            while($k = mysqli_fetch_assoc($res)): 
                                $nama_kat = $k['nama_kategori'];
                                $sampel_buku = mysqli_query($conn, "SELECT gambar FROM buku WHERE kategori = '$nama_kat' LIMIT 3");
                                $total_buku = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM buku WHERE kategori = '$nama_kat'"));
                            ?>
                            <tr>
                                <td style="font-size: 1rem;"><?= $nama_kat ?></td>
                                <td>
                                    <div class="cover-stack">
                                        <?php if($total_buku > 0): ?>
                                            <?php while($s = mysqli_fetch_assoc($sampel_buku)): ?>
                                                <img src="../uploads/<?= $s['gambar'] ?>" class="cover-item" onerror="this.src='https://placehold.co/40x55?text=No+Img'">
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Belum ada buku</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: center;"><span class="count-badge"><?= $total_buku ?> Item</span></td>
                                <td style="text-align: center;">
                                    <a href="javascript:void(0)" class="btn-edit" onclick="openEditModal('<?= $k['id'] ?>', '<?= $nama_kat ?>')">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?hapus=<?= $k['id'] ?>" class="btn-delete" onclick="return confirm('Hapus kategori ini?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="font-weight: 800; margin-bottom: 20px;">Edit Kategori</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <label style="font-size:0.7rem; font-weight:800; color:var(--text-muted);">NAMA BARU</label>
                <input type="text" name="nama_kategori" id="edit_nama" required>
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" onclick="closeModal()" style="background:#cbd5e1; color:#475569; border:none; padding:12px; border-radius:12px; flex:1; cursor:pointer; font-weight:700;">Batal</button>
                    <button type="submit" name="edit_kategori" style="background:var(--primary); color:white; border:none; padding:12px; border-radius:12px; flex:2; cursor:pointer; font-weight:700;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Jam Real-time
        function runClock() {
            const now = new Date();
            document.getElementById('real-clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('real-date').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(runClock, 1000); runClock();

        // Dark Mode Toggle
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

        // Modal
        function openEditModal(id, nama) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeModal() { document.getElementById('editModal').style.display = 'none'; }
        window.onclick = function(e) { if (e.target == document.getElementById('editModal')) closeModal(); }
    </script>
</body>
</html>