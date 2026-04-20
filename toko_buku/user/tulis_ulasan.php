<?php
session_start();
include '../config/koneksi.php';

// 1. Proteksi login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

// Pastikan ada ID Pesanan di URL
if (!isset($_GET['id'])) {
    header("Location: pesanan_saya.php");
    exit;
}

$id_pesanan = mysqli_real_escape_string($conn, $_GET['id']);
$id_user = $_SESSION['id_user'];

// 2. Ambil data buku dari pesanan ini
// Kita butuh join agar mendapatkan ID Buku untuk disimpan di tabel ulasan
$q_buku = mysqli_query($conn, "SELECT pesanan_detail.*, buku.judul, buku.gambar, buku.id as id_buku 
                               FROM pesanan_detail 
                               JOIN buku ON pesanan_detail.id_buku = buku.id 
                               WHERE id_pesanan = '$id_pesanan' LIMIT 1");
$data = mysqli_fetch_assoc($q_buku);

// Cegah error jika data pesanan tidak ditemukan
if (!$data) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location='pesanan_saya.php';</script>";
    exit;
}

$id_buku = $data['id_buku'];

// 3. Cek apakah user sudah pernah memberikan ulasan untuk buku ini
$q_cek = mysqli_query($conn, "SELECT * FROM ulasan WHERE id_user = '$id_user' AND id_buku = '$id_buku' LIMIT 1");
$ulasan_lama = mysqli_fetch_assoc($q_cek);

// 4. PROSES KIRIM / UPDATE ULASAN
if (isset($_POST['kirim_ulasan'])) {
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    $tanggal = date('Y-m-d');

    if ($ulasan_lama) {
        // JIKA SUDAH ADA -> UPDATE
        $id_ulasan = $ulasan_lama['id'];
        $sql = "UPDATE ulasan SET rating = '$rating', komentar = '$komentar', tanggal_ulasan = '$tanggal' WHERE id = '$id_ulasan'";
        $pesan = "Ulasan berhasil diperbarui!";
    } else {
        // JIKA BELUM ADA -> INSERT BARU
        $sql = "INSERT INTO ulasan (id_user, id_buku, rating, komentar, tanggal_ulasan) 
                VALUES ('$id_user', '$id_buku', '$rating', '$komentar', '$tanggal')";
        $pesan = "Terima kasih atas ulasannya!";
    }
    
    $eksekusi = mysqli_query($conn, $sql);
    
    if ($eksekusi) {
        echo "<script>alert('$pesan'); window.location='pesanan_saya.php';</script>";
    } else {
        // Tampilkan error database jika gagal agar kita tahu kolom mana yang salah
        echo "<script>alert('Gagal: " . mysqli_error($conn) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Ulasan | BookStore Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #6366f1; 
            --bg: #f8fafc; 
            --white: #ffffff;
            --text: #0f172a;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        
        .ulasan-card { 
            background: var(--white); padding: 40px; border-radius: 32px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); width: 100%; max-width: 500px; text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .book-preview { width: 120px; height: 170px; object-fit: cover; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        h2 { font-size: 1.5rem; font-weight: 800; color: var(--text); margin-bottom: 10px; }
        
        .rating-input { display: flex; flex-direction: row-reverse; justify-content: center; gap: 12px; margin: 25px 0; }
        .rating-input input { display: none; }
        .rating-input label { font-size: 38px; color: #e2e8f0; cursor: pointer; transition: 0.3s; }
        .rating-input input:checked ~ label, 
        .rating-input label:hover, 
        .rating-input label:hover ~ label { color: #facc15; }

        textarea { 
            width: 100%; padding: 18px; border-radius: 18px; border: 2px solid #f1f5f9; 
            background: #f8fafc; font-family: inherit; resize: none; margin-bottom: 25px; 
            box-sizing: border-box; font-size: 1rem; outline: none; transition: 0.3s;
        }
        textarea:focus { border-color: var(--primary); background: white; }

        .btn-submit { 
            background: var(--primary); color: white; border: none; padding: 18px; 
            border-radius: 18px; font-weight: 800; cursor: pointer; width: 100%; 
            transition: 0.3s; font-size: 1rem; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(99, 102, 241, 0.3); filter: brightness(1.1); }
        
        .btn-cancel { display: block; margin-top: 20px; color: #94a3b8; text-decoration: none; font-weight: 700; font-size: 0.9rem; }
        .btn-cancel:hover { color: #ef4444; }
    </style>
</head>
<body>

<div class="ulasan-card">
    <img src="../uploads/<?= $data['gambar'] ?>" class="book-preview" onerror="this.src='https://placehold.co/120x170?text=Buku'">
    <h2><?= $ulasan_lama ? 'Edit Review' : 'Beri Rating' ?></h2>
    <p style="color: #64748b; font-size: 0.95rem;">Pesanan: <span style="color: var(--primary); font-weight: 700;">#<?= $id_pesanan ?></span></p>

    <form action="tulis_ulasan.php?id=<?= $id_pesanan ?>" method="POST">
        <div class="rating-input">
            <?php for($i=5; $i>=1; $i--): ?>
                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" 
                <?= ($ulasan_lama && $ulasan_lama['rating'] == $i) ? 'checked' : '' ?> required>
                <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
            <?php endfor; ?>
        </div>

        <textarea name="komentar" rows="5" placeholder="Gimana bukunya? Ceritain dong..." required><?= $ulasan_lama ? htmlspecialchars($ulasan_lama['komentar']) : '' ?></textarea>

        <button type="submit" name="kirim_ulasan" class="btn-submit">
            <i class="fas fa-paper-plane"></i> <?= $ulasan_lama ? 'Simpan Perubahan' : 'Kirim Review' ?>
        </button>
        <a href="pesanan_saya.php" class="btn-cancel">Kembali</a>
    </form>
</div>

</body>
</html>