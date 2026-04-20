<?php
require_once '../config/config.php';
checkAdminLogin();

if(isset($_POST['id'])) {
    $id_daftar_ulang = $_POST['id'];
    
    $stmt = $conn->prepare("UPDATE daftar_ulang SET status_pembayaran = 'lunas' WHERE id_daftar_ulang = ?");
    $stmt->bind_param("i", $id_daftar_ulang);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil diverifikasi!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memverifikasi pembayaran']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>