<?php
session_start();

$id = $_GET['id'];
$aksi = $_GET['aksi'];
$qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($aksi == "tambah") {
    // Jika buku sudah ada di keranjang, tambah jumlahnya
    if (isset($_SESSION['keranjang'][$id])) {
        $_SESSION['keranjang'][$id] += $qty;
    } else {
        // Jika belum ada, masukkan buku baru
        $_SESSION['keranjang'][$id] = $qty;
    }
}

header("Location: keranjang.php");
exit;
$id_user = $_SESSION['id_user'];
$total = $_POST['total_bayar'];
$alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
$metode = mysqli_real_escape_string($conn, $_POST['metode_bayar']);
$tanggal = date("Y-m-d H:i:s");

// Masukkan ke tabel pesanan (SESUAIKAN NAMA KOLOM DENGAN DATABASE)
$query_pesanan = mysqli_query($conn, "INSERT INTO pesanan (id_user, tanggal, total_bayar, alamat_pengiriman, status, metode_pembayaran) 
                                      VALUES ('$id_user', '$tanggal', '$total', '$alamat', 'Pending', '$metode')");

if ($query_pesanan) {
    $id_pesanan_baru = mysqli_insert_id($conn);

    foreach ($_SESSION['keranjang'] as $id_buku => $jumlah) {
        $q_buku = mysqli_query($conn, "SELECT harga FROM buku WHERE id='$id_buku'");
        $d_buku = mysqli_fetch_assoc($q_buku);
        $subtotal = $d_buku['harga'] * $jumlah;

        // Masukkan ke detail pesanan
        mysqli_query($conn, "INSERT INTO pesanan_detail (id_pesanan, id_buku, jumlah, subtotal) 
                            VALUES ('$id_pesanan_baru', '$id_buku', '$jumlah', '$subtotal')");
        
        // Kurangi stok
        mysqli_query($conn, "UPDATE buku SET stok = stok - $jumlah WHERE id = '$id_buku'");
    }

    // Kosongkan keranjang
    unset($_SESSION['keranjang']);

    echo "<script>alert('Pesanan Berhasil! Silahkan cek riwayat pesanan.'); window.location='pesanan_saya.php';</script>";
} else {
    echo "Gagal: " . mysqli_error($conn);
}