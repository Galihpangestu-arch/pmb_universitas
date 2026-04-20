<?php
session_start();
// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Mendapatkan nama file saat ini untuk logika Sidebar Active
$current_page = basename($_SERVER['PHP_SELF']);

// --- 1. LOGIKA TAMBAH BUKU ---
if (isset($_POST['tambah_buku'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']); 
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    
    $nama_asli = $_FILES['gambar']['name'];
    $source = $_FILES['gambar']['tmp_name'];
    $nama_file_baru = time() . "_" . str_replace(' ', '_', $nama_asli); 
    $folder = '../uploads/';
    
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    if (move_uploaded_file($source, $folder . $nama_file_baru)) {
        $insert = mysqli_query($conn, "INSERT INTO buku (judul, penulis, kategori, deskripsi, harga, stok, gambar) 
                                       VALUES ('$judul', '$penulis', '$kategori', '$deskripsi', '$harga', '$stok', '$nama_file_baru')");
        if ($insert) {
            echo "<script>alert('Buku Berhasil Ditambahkan!'); window.location='kelola_buku.php';</script>";
        }
    }
}

// --- 2. LOGIKA HAPUS BUKU ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $cek = mysqli_query($conn, "SELECT gambar FROM buku WHERE id = '$id_hapus'");
    $data = mysqli_fetch_assoc($cek);
    if ($data['gambar'] && file_exists("../uploads/" . $data['gambar'])) unlink("../uploads/" . $data['gambar']);
    mysqli_query($conn, "DELETE FROM buku WHERE id = '$id_hapus'");
    header("Location: kelola_buku.php");
}

$filter_kat = isset($_GET['cari_kategori']) ? mysqli_real_escape_string($conn, $_GET['cari_kategori']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Koleksi | Admin Pro</title>
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

        /* --- SIDEBAR (SAMA PERSIS) --- */
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
        .card { background: var(--card-bg); padding: 30px; border-radius: 32px; border: 1px solid var(--border); margin-bottom: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--bg-main); color: var(--text-dark); outline: none; font-weight: 600; }
        
        .btn-submit { background: var(--primary); color: white; border: none; padding: 16px; border-radius: 15px; font-weight: 700; cursor: pointer; width: 100%; margin-top: 10px; box-shadow: 0 10px 20px var(--primary-glow); }
        
        /* Table Style */
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.7rem; border-bottom: 2px solid var(--border); text-transform: uppercase; }
        .custom-table td { padding: 15px; border-bottom: 1px solid var(--border); font-weight: 600; vertical-align: middle; }
        .img-book { width: 50px; height: 70px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .badge-kat { background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; }

        .btn-edit { background: #38bdf8; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; margin-right: 5px; font-size: 0.9rem; }
        .btn-hapus { background: #ef4444; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; }
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
                Manajemen <span style="color:var(--primary);">Katalog Buku</span>
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
            <div class="card">
                <h3 style="margin-bottom:20px; font-weight: 800;"><i class="fas fa-plus-circle"></i> Tambah Koleksi</h3>
                <form method="POST" enctype="multipart/form-data" class="form-grid">
                    <div style="grid-column: span 2"><label>Judul Buku</label><input type="text" name="judul" required></div>
                    <div><label>Penulis</label><input type="text" name="penulis" required></div>
                    <div>
                        <label>Kategori</label>
                        <select name="kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php
                            $res = mysqli_query($conn, "SELECT DISTINCT nama_kategori FROM kategori ORDER BY nama_kategori ASC");
                            while($k = mysqli_fetch_assoc($res)) echo "<option value='".$k['nama_kategori']."'>".$k['nama_kategori']."</option>";
                            ?>
                        </select>
                    </div>
                    <div><label>Harga Jual</label><input type="number" name="harga" required></div>
                    <div><label>Stok</label><input type="number" name="stok" required></div>
                    <div style="grid-column: span 2"><label>Cover</label><input type="file" name="gambar" required></div>
                    <div style="grid-column: span 2"><label>Deskripsi</label><textarea name="deskripsi" rows="3"></textarea></div>
                    <button type="submit" name="tambah_buku" class="btn-submit">Simpan ke Katalog</button>
                </form>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
                    <h3 style="margin:0; font-weight: 800;">Katalog Inventaris</h3>
                    <form method="GET" style="display:flex; gap:10px;">
                        <select name="cari_kategori" style="padding:10px 15px; border-radius: 10px;">
                            <option value="">Semua Kategori</option>
                            <?php
                            $res2 = mysqli_query($conn, "SELECT DISTINCT nama_kategori FROM kategori ORDER BY nama_kategori ASC");
                            while($k2 = mysqli_fetch_assoc($res2)){
                                $sel = ($filter_kat == $k2['nama_kategori']) ? 'selected' : '';
                                echo "<option value='".$k2['nama_kategori']."' $sel>".$k2['nama_kategori']."</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" style="background:var(--primary); color:white; border:none; padding:0 20px; border-radius:10px; cursor:pointer; font-weight: 700;">Filter</button>
                    </form>
                </div>
                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Buku</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM buku";
                            if($filter_kat != '') $query .= " WHERE kategori = '$filter_kat'";
                            $query .= " ORDER BY id DESC";
                            $run = mysqli_query($conn, $query);
                            while($row = mysqli_fetch_assoc($run)): ?>
                            <tr>
                                <td style="display:flex; align-items:center; gap:15px">
                                    <img src="../uploads/<?= $row['gambar'] ?>" class="img-book" onerror="this.src='https://placehold.co/50x70'">
                                    <div>
                                        <div style="font-weight:800"><?= $row['judul'] ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted)"><?= $row['penulis'] ?></div>
                                    </div>
                                </td>
                                <td><span class="badge-kat"><?= $row['kategori'] ?></span></td>
                                <td style="color: var(--primary)">Rp <?= number_format($row['harga'],0,',','.') ?></td>
                                <td><?= $row['stok'] ?></td>
                                <td>
                                    <a href="edit_buku.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

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
    </script>
</body>
</html>