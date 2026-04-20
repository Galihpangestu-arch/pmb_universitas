<?php
session_start();
if ($_SESSION['role'] != 'admin') header("Location: ../login.php");
include '../config/koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = mysqli_query($conn, "DELETE FROM buku WHERE id = '$id'");

    if ($query) {
        echo "<script>alert('Buku Terhapus!'); window.location='kelola_buku.php';</script>";
    } else {
        echo "Gagal menghapus: " . mysqli_error($conn);
    }
}
?>