<?php
session_start();
// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../config/koneksi.php';

// Pastikan ada parameter ID yang dikirim melalui URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. (Opsional) Cek apakah pelanggan ini memiliki transaksi yang masih menggantung
    // Jika sistem kamu sangat ketat, biasanya pelanggan yang punya pesanan tidak boleh dihapus.
    // Tapi jika ingin langsung hapus, gunakan query di bawah:

    // 2. Eksekusi penghapusan akun dari tabel users
    $query = mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND role = 'user'");

    if ($query) {
        // Jika berhasil, tampilkan alert dan kembali ke halaman kelola pelanggan
        echo "<script>
                alert('Pelanggan berhasil dihapus dari sistem.');
                window.location = 'kelola_pelanggan.php';
              </script>";
    } else {
        // Jika gagal karena kendala database (misal: Constraint/Relasi database)
        echo "<script>
                alert('Gagal menghapus pelanggan. Akun ini mungkin masih terikat dengan data pesanan.');
                window.location = 'kelola_pelanggan.php';
              </script>";
    }
} else {
    // Jika diakses tanpa ID, lempar balik ke daftar pelanggan
    header("Location: kelola_pelanggan.php");
    exit;
}
?>  