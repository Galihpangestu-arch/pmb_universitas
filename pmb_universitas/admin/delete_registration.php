<?php
require_once '../config.php';
checkAdminLogin();

if(isset($_POST['id'])) {
    $id_daftar_ulang = $_POST['id'];
    
    // Delete from daftar_ulang
    $stmt = $conn->prepare("DELETE FROM daftar_ulang WHERE id_daftar_ulang = ?");
    $stmt->bind_param("i", $id_daftar_ulang);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>