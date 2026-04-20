<?php
require_once '../config/config.php';
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_POST['id_user'] ?? 0;
    $username = sanitize($_POST['username']);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $no_hp = sanitize($_POST['no_hp']);
    $alamat = sanitize($_POST['alamat']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
    $asal_sekolah = sanitize($_POST['asal_sekolah']);
    $tahun_lulus = sanitize($_POST['tahun_lulus']);
    $program_studi = sanitize($_POST['program_studi']);
    $status_pendaftaran = sanitize($_POST['status_pendaftaran'] ?? 'registrasi');
    $nomor_test = sanitize($_POST['nomor_test']) ?? generateNomorTest();
    
    if ($id_user > 0) {
        // Update existing user
        $sql = "UPDATE user SET 
                username = ?, nama_lengkap = ?, email = ?, no_hp = ?, alamat = ?,
                tanggal_lahir = ?, jenis_kelamin = ?, asal_sekolah = ?, tahun_lulus = ?,
                program_studi = ?, status_pendaftaran = ?, nomor_test = ?
                WHERE id_user = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssi", $username, $nama_lengkap, $email, $no_hp, $alamat,
                         $tanggal_lahir, $jenis_kelamin, $asal_sekolah, $tahun_lulus,
                         $program_studi, $status_pendaftaran, $nomor_test, $id_user);
    } else {
        // Insert new user
        $password = sanitize($_POST['password']);
        $hashed_password = hashPassword($password);
        
        $sql = "INSERT INTO user (username, password, nama_lengkap, email, no_hp, alamat,
                tanggal_lahir, jenis_kelamin, asal_sekolah, tahun_lulus, program_studi,
                status_pendaftaran, nomor_test) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssss", $username, $hashed_password, $nama_lengkap, $email, $no_hp,
                         $alamat, $tanggal_lahir, $jenis_kelamin, $asal_sekolah, $tahun_lulus,
                         $program_studi, $status_pendaftaran, $nomor_test);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = $id_user > 0 ? 'User berhasil diupdate' : 'User berhasil ditambahkan';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: manage_users.php');
    exit();
}
?>