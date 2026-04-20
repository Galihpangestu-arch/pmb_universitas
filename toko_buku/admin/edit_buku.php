<?php
session_start();
// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    header("Location: kelola_buku.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM buku WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: kelola_buku.php");
    exit;
}

if (isset($_POST['update_buku'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']); // Ambil Kategori baru
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    if ($_FILES['gambar']['name'] != "") {
        $nama_asli = $_FILES['gambar']['name'];
        $source = $_FILES['gambar']['tmp_name'];
        $nama_file_baru = time() . "_" . $nama_asli;
        move_uploaded_file($source, '../uploads/' . $nama_file_baru);
        
        // Hapus gambar lama
        if(file_exists("../uploads/".$data['gambar'])) unlink("../uploads/".$data['gambar']);
        
        // Update dengan Gambar + Kategori
        $sql = "UPDATE buku SET judul='$judul', penulis='$penulis', kategori='$kategori', deskripsi='$deskripsi', harga='$harga', stok='$stok', gambar='$nama_file_baru' WHERE id='$id'";
    } else {
        // Update tanpa ganti gambar, tapi tetap update Kategori
        $sql = "UPDATE buku SET judul='$judul', penulis='$penulis', kategori='$kategori', deskripsi='$deskripsi', harga='$harga', stok='$stok' WHERE id='$id'";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data Berhasil Diperbarui!'); window.location='kelola_buku.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku | Admin Pro</title>
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
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-main); 
            color: var(--text-dark); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .edit-card { 
            background: var(--card-bg); 
            width: 100%;
            max-width: 900px;
            border-radius: 35px; 
            padding: 50px; 
            border: 1px solid var(--border); 
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 1px dashed var(--border);
            padding-bottom: 25px;
        }

        .header-section h2 { font-size: 1.8rem; font-weight: 800; letter-spacing: -1px; }
        
        .btn-back {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .btn-back:hover { color: var(--primary); transform: translateX(-5px); }

        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 25px; 
        }
        .full-width { grid-column: span 2; }

        .input-group { display: flex; flex-direction: column; gap: 10px; }
        
        label { 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            color: var(--text-muted); 
            letter-spacing: 1px;
            padding-left: 5px;
        }

        input, textarea, select { 
            background: var(--bg-main); 
            border: 2px solid var(--border); 
            color: var(--text-dark); 
            padding: 15px 20px; 
            border-radius: 18px; 
            outline: none; 
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            transition: 0.3s;
        }
        
        input:focus, textarea:focus, select:focus { 
            border-color: var(--primary); 
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .image-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 20px;
            background: var(--bg-main);
            padding: 20px;
            border-radius: 22px;
            border: 2px dashed var(--border);
        }
        
        .preview-img { 
            width: 80px; 
            height: 110px; 
            object-fit: cover; 
            border-radius: 12px; 
            border: 2px solid var(--white);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .btn-submit { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 20px; 
            border-radius: 20px; 
            font-weight: 800; 
            font-size: 1rem;
            cursor: pointer; 
            margin-top: 20px; 
            width: 100%; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 25px var(--primary-glow);
        }
        .btn-submit:hover { 
            transform: translateY(-5px); 
            filter: brightness(1.1);
        }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .edit-card { padding: 30px; }
        }
    </style>
</head>
<body>

<div class="edit-card">
    <div class="header-section">
        <h2>Edit Detail Buku</h2>
        <a href="kelola_buku.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="input-group">
                <label>Judul Buku</label>
                <input type="text" name="judul" value="<?= htmlspecialchars($data['judul']) ?>" required>
            </div>

            <div class="input-group">
                <label>Penulis</label>
                <input type="text" name="penulis" value="<?= htmlspecialchars($data['penulis']) ?>" required>
            </div>

            <div class="input-group">
                <label>Kategori Buku</label>
                <select name="kategori" required>
                    <option value="Novel" <?= ($data['kategori'] == 'Novel') ? 'selected' : '' ?>>Novel</option>
                    <option value="Edukasi" <?= ($data['kategori'] == 'Edukasi') ? 'selected' : '' ?>>Edukasi</option>
                    <option value="Komik" <?= ($data['kategori'] == 'Komik') ? 'selected' : '' ?>>Komik</option>
                    <option value="Teknologi" <?= ($data['kategori'] == 'Teknologi') ? 'selected' : '' ?>>Teknologi</option>
                    <option value="Bisnis" <?= ($data['kategori'] == 'Bisnis') ? 'selected' : '' ?>>Bisnis</option>
                    <option value="Religi" <?= ($data['kategori'] == 'Religi') ? 'selected' : '' ?>>Religi</option>
                </select>
            </div>

            <div class="input-group">
                <label>Harga (Rp)</label>
                <input type="number" name="harga" value="<?= $data['harga'] ?>" required>
            </div>

            <div class="input-group">
                <label>Stok Tersedia</label>
                <input type="number" name="stok" value="<?= $data['stok'] ?>" required>
            </div>

            <div class="input-group full-width">
                <label>Deskripsi Buku</label>
                <textarea name="deskripsi" rows="4" required><?= htmlspecialchars($data['deskripsi']) ?></textarea>
            </div>

            <div class="input-group full-width">
                <label>Cover Buku</label>
                <div class="image-upload-wrapper">
                    <?php if($data['gambar']): ?>
                        <img src="../uploads/<?= $data['gambar'] ?>" class="preview-img" alt="Current Cover">
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <input type="file" name="gambar" style="width: 100%; border: none; background: transparent; padding: 0;">
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 10px;">
                            *Biarkan kosong jika tidak ingin mengubah cover buku.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" name="update_buku" class="btn-submit">
            <i class="fas fa-save"></i> Perbarui Data Buku
        </button>
    </form>
</div>

<script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
</script>

</body>
</html>