<?php
// =============================================
// SYNC DATA DARI TABEL USER KE TEST_RESULTS
// =============================================
// Ambil semua user yang punya nilai_test tapi belum ada di test_results
$sync_query = "SELECT u.id_user, u.nama_lengkap, u.nilai_test, u.status_pendaftaran, u.tgl_test 
               FROM user u 
               LEFT JOIN test_results tr ON u.id_user = tr.id_user 
               WHERE tr.id_test IS NULL 
               AND u.nilai_test IS NOT NULL 
               AND u.nilai_test > 0";

$sync_result = $conn->query($sync_query);

while ($row = $sync_result->fetch_assoc()) {
    // Hitung perkiraan jawaban benar berdasarkan nilai (asumsi)
    $total_soal = $conn->query("SELECT COUNT(*) as total FROM soal_test WHERE aktif = 'Y'")->fetch_assoc()['total'];
    $jawaban_benar = round(($row['nilai_test'] / 100) * $total_soal);
    
    $insert_sync = "INSERT INTO test_results (
        id_user, 
        tanggal_test, 
        total_soal, 
        soal_terjawab, 
        jawaban_benar, 
        nilai, 
        status_test, 
        created_at
    ) VALUES (
        ?, 
        COALESCE(?, NOW()),
        ?,
        ?,
        ?,
        ?,
        ?,
        NOW()
    )";
    
    $stmt_sync = $conn->prepare($insert_sync);
    $status_test = $row['status_pendaftaran'] === 'lulus' ? 'lulus' : 'tidak_lulus';
    $tgl_test = $row['tgl_test'] ?? date('Y-m-d H:i:s');
    
    $stmt_sync->bind_param("isiiiis", 
        $row['id_user'],
        $tgl_test,
        $total_soal,
        $total_soal,
        $jawaban_benar,
        $row['nilai_test'],
        $status_test
    );
    $stmt_sync->execute();
}

// Lanjutkan dengan kode asli test_results.php...
require_once '../config/config.php';
checkAdminLogin();
// ... dst
?>