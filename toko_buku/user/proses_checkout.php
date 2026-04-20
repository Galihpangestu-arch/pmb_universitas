<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || empty($_SESSION['keranjang'])) {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$nama = mysqli_real_escape_string($conn, $_POST['nama']);
$telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
$alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
$metode = $_POST['metode_bayar'];
$total = $_POST['total_bayar'];
$tanggal = date("Y-m-d H:i:s");

// 1. Simpan ke tabel pesanan
$query_pesanan = mysqli_query($conn, "INSERT INTO pesanan (id_user, tanggal, total_bayar, alamat_pengiriman, status, metode_pembayaran) 
                                      VALUES ('$id_user', '$tanggal', '$total', '$alamat', 'Pending', '$metode')");

if ($query_pesanan) {
    // Ambil ID pesanan yang baru saja masuk
    $id_pesanan_baru = mysqli_insert_id($conn);

    // 2. Simpan setiap item keranjang ke tabel pesanan_detail
    foreach ($_SESSION['keranjang'] as $id_buku => $jumlah) {
        // Ambil harga buku saat ini
        $q_buku = mysqli_query($conn, "SELECT harga FROM buku WHERE id='$id_buku'");
        $d_buku = mysqli_fetch_assoc($q_buku);
        $subtotal = $d_buku['harga'] * $jumlah;

        mysqli_query($conn, "INSERT INTO pesanan_detail (id_pesanan, id_buku, jumlah, subtotal) 
                            VALUES ('$id_pesanan_baru', '$id_buku', '$jumlah', '$subtotal')");
        
        // 3. Kurangi stok buku
        mysqli_query($conn, "UPDATE buku SET stok = stok - $jumlah WHERE id = '$id_buku'");
    }

    // 4. Kosongkan keranjang belanja
    unset($_SESSION['keranjang']);

    echo "<script>alert('Pesanan Berhasil Dibuat!'); window.location='pesanan_saya.php';</script>";
} else {
    echo "Gagal membuat pesanan: " . mysqli_error($conn);
}