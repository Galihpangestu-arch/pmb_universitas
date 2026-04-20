<?php
include 'config/koneksi.php';

if (isset($_POST['register'])) {
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $user  = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']); // Tambahkan no_telp sesuai form kamu
    $pass  = $_POST['password'];

    // 1. CEK APAKAH USERNAME ATAU EMAIL SUDAH ADA
    // Kita cek dua-duanya sekaligus biar gak capek
    $cek_database = mysqli_query($conn, "SELECT username, email FROM users WHERE username = '$user' OR email = '$email'");
    
    if (mysqli_num_rows($cek_database) > 0) {
        $row = mysqli_fetch_assoc($cek_database);
        if ($row['username'] == $user) {
            echo "<script>alert('Gagal! Username sudah digunakan, cari yang lain.'); window.history.back();</script>";
        } else {
            echo "<script>alert('Gagal! Email sudah terdaftar, gunakan email lain.'); window.history.back();</script>";
        }
        exit;
    }

    // 2. Hash password agar aman
    $password_hashed = password_hash($pass, PASSWORD_DEFAULT);

    // 3. Masukkan ke database (Sesuaikan urutan kolom dengan tabel users kamu)
    // Pastikan kolom 'no_telp' ada di database, kalau gak ada silakan dihapus bagian no_telp nya
    $query = "INSERT INTO users (nama_lengkap, username, email, no_telp, password, role) 
              VALUES ('$nama', '$user', '$email', '$no_telp', '$password_hashed', 'user')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('Pendaftaran Berhasil! Silahkan login dengan akun baru Anda.');
                window.location.href = 'login.php';
              </script>";
    } else {
        // Tampilkan error aslinya kalau masih gagal buat debugging
        echo "Error Database: " . mysqli_error($conn);
    }
} else {
    header("Location: register.php");
    exit;
}
?>