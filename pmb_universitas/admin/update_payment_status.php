<?php
require_once '../config/config.php';
checkAdminLogin();

if(isset($_POST['id']) && isset($_POST['status'])) {
    $id_daftar_ulang = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE daftar_ulang SET status_pembayaran = ? WHERE id_daftar_ulang = ?");
    $stmt->bind_param("si", $status, $id_daftar_ulang);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>