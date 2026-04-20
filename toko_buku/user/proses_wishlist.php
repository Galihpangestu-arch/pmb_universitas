<?php
session_start();
include '../config/koneksi.php';

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Silahkan login terlebih dahulu'); window.location='../login.php';</script>";
    exit;
}

$id_user = $_SESSION['id_user'];

// 2. Cek apakah ID Buku ada di URL
if (isset($_GET['id'])) {
    $id_buku = mysqli_real_escape_string($conn, $_GET['id']);

    // 3. Cek apakah buku ini sudah ada di wishlist user tersebut
    $cek = mysqli_query($conn, "SELECT * FROM wishlist WHERE id_user = '$id_user' AND id_buku = '$id_buku'");

    if (mysqli_num_rows($cek) > 0) {
        // Jika SUDAH ADA, maka hapus dari wishlist
        $query = mysqli_query($conn, "DELETE FROM wishlist WHERE id_user = '$id_user' AND id_buku = '$id_buku'");
        $pesan = "Buku dihapus dari wishlist";
    } else {
        // Jika BELUM ADA, maka tambahkan ke wishlist
        $query = mysqli_query($conn, "INSERT INTO wishlist (id_user, id_buku) VALUES ('$id_user', '$id_buku')");
        $pesan = "Buku berhasil ditambah ke wishlist";
    }

    // 4. Balikkan ke halaman sebelumnya (Dashboard) agar tidak stuck di halaman putih
    if ($query) {
        echo "<script>window.location='wishlist.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

} else {
    // Jika tidak ada ID di URL, balik ke dashboard
    header("Location: dashboard.php");
    exit;
}