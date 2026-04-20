<?php
require_once 'config/config.php';

// Ambil data dari form
$nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
$nama = mysqli_real_escape_string($conn, $_POST['nama']);
$tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
$tanggal_lahir = $_POST['tanggal_lahir'];
$jenis_kelamin = $_POST['jenis_kelamin'];
$alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
$orangtua = mysqli_real_escape_string($conn, $_POST['orangtua']);
$telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
$gelombang = $_POST['gelombang'];

// Validasi
if(empty($nisn) || empty($nama)) {
    header("Location: re_registration.php?error=empty");
    exit;
}

// Cek apakah NISN sudah terdaftar
$check = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$nisn'");
if(mysqli_num_rows($check) > 0) {
    header("Location: re_registration.php?error=nisn_exists");
    exit;
}

// Insert ke tabel siswa
$query_siswa = "INSERT INTO siswa (nisn, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, nama_orangtua, no_telepon) 
                VALUES ('$nisn', '$nama', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$alamat', '$orangtua', '$telepon')";

if(mysqli_query($conn, $query_siswa)) {
    // Insert ke tabel daftar_ulang dengan status LANGSUNG 'lunas' (tanpa bayar)
    $query_daftar = "INSERT INTO daftar_ulang (nisn, gelombang, status, tgl_daftar_ulang) 
                     VALUES ('$nisn', '$gelombang', 'lunas', CURDATE())";
    
    if(mysqli_query($conn, $query_daftar)) {
        header("Location: re_registration.php?success=daftar");
    } else {
        header("Location: re_registration.php?error=db_error");
    }
} else {
    header("Location: re_registration.php?error=db_error");
}

mysqli_close($conn);
?>