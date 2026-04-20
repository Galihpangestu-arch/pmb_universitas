<?php
require_once '../config.php';
checkAdminLogin();

if(isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $tanggal_daftar_ulang = $_POST['tanggal_daftar_ulang'];
    $jenis_pembayaran = $_POST['jenis_pembayaran'];
    $nominal_pembayaran = $_POST['nominal_pembayaran'];
    $status_pembayaran = $_POST['status_pembayaran'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    try {
        // Insert into daftar_ulang table
        $stmt = $conn->prepare("
            INSERT INTO daftar_ulang 
            (id_user, tanggal_daftar_ulang, jenis_pembayaran, nominal_pembayaran, status_pembayaran, keterangan) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdss", $user_id, $tanggal_daftar_ulang, $jenis_pembayaran, $nominal_pembayaran, $status_pembayaran, $keterangan);
        
        if($stmt->execute()) {
            // Update user status
            $update_stmt = $conn->prepare("UPDATE user SET status_pendaftaran = 'daftar_ulang' WHERE id_user = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Daftar ulang berhasil diproses!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $conn->error]);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>