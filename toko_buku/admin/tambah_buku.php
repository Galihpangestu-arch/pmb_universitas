<?php
session_start();
if ($_SESSION['role'] != 'admin') header("Location: ../login.php");
include '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    $judul    = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis  = mysqli_real_escape_string($conn, $_POST['penulis']);
    $harga    = $_POST['harga'];
    $stok     = $_POST['stok'];
    $desc     = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    $query = "INSERT INTO buku (judul, penulis, harga, stok, deskripsi) 
              VALUES ('$judul', '$penulis', '$harga', '$stok', '$desc')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Buku Berhasil Ditambahkan!'); window.location='dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Buku | Admin Pro</title>
    <style>
        body { background: #0a0b0d; color: white; font-family: sans-serif; padding: 50px; }
        .form-card { 
            background: #161b22; padding: 40px; border-radius: 20px; 
            border: 1px solid #30363d; max-width: 600px; margin: auto;
        }
        input, textarea { 
            width: 100%; padding: 12px; margin: 10px 0; background: #0d1117; 
            border: 1px solid #30363d; color: white; border-radius: 8px; 
        }
        .btn-save { 
            background: #238636; color: white; border: none; padding: 15px; 
            width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 20px;
        }
        .btn-save:hover { background: #2ea043; }
        a { color: #8b949e; text-decoration: none; display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2 style="color: #58a6ff;">➕ Tambah Koleksi Buku</h2>
        <form method="POST">
            <label>Judul Buku</label>
            <input type="text" name="judul" placeholder="Masukkan judul..." required>
            
            <label>Penulis</label>
            <input type="text" name="penulis" placeholder="Nama penulis..." required>
            
            <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" placeholder="Contoh: 50000" required>
                </div>
                <div style="flex:1;">
                    <label>Stok</label>
                    <input type="number" name="stok" placeholder="Jumlah stok..." required>
                </div>
            </div>

            <label>Deskripsi Buku</label>
            <textarea name="deskripsi" rows="4" placeholder="Sinopsis singkat..."></textarea>

            <button type="submit" name="simpan" class="btn-save">SIMPAN KE DATABASE</button>
        </form>
        <a href="dashboard.php">← Kembali ke Dashboard</a>
    </div>
</body>
</html>