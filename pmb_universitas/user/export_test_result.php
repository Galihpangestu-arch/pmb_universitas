<?php
require_once '../config/config.php';
checkUserLogin();

$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if user has taken the test
if (!$user['nilai_test']) {
    redirectWithMessage('dashboard.php', 'error', 'Anda belum mengikuti test.');
}

// Get test results
$test_sql = "SELECT 
    COUNT(ht.id_soal) as total_soal,
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END) as jawaban_benar,
    SUM(CASE WHEN ht.jawaban_user IS NULL THEN 1 ELSE 0 END) as tidak_dijawab,
    MIN(ht.waktu_jawab) as mulai_test,
    MAX(ht.waktu_jawab) as selesai_test
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ?";
$test_stmt = $conn->prepare($test_sql);
$test_stmt->bind_param("i", $user_id);
$test_stmt->execute();
$test_summary = $test_stmt->get_result()->fetch_assoc();

// Get detailed answers
$detail_sql = "SELECT 
    ht.*,
    st.pertanyaan,
    st.pilihan_a,
    st.pilihan_b,
    st.pilihan_c,
    st.pilihan_d,
    st.jawaban_benar,
    CASE 
        WHEN ht.jawaban_user = st.jawaban_benar THEN 'benar'
        WHEN ht.jawaban_user IS NULL THEN 'kosong'
        ELSE 'salah'
    END as status_jawaban
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ?
    ORDER BY ht.waktu_jawab";
$detail_stmt = $conn->prepare($detail_sql);
$detail_stmt->bind_param("i", $user_id);
$detail_stmt->execute();
$detail_result = $detail_stmt->get_result();

// Calculate statistics
$total_soal = $test_summary['total_soal'] ?: 1;
$jawaban_benar = $test_summary['jawaban_benar'] ?: 0;
$jawaban_salah = $total_soal - $jawaban_benar - ($test_summary['tidak_dijawab'] ?: 0);
$nilai = ($jawaban_benar / $total_soal) * 100;
$status = $nilai >= 70 ? 'LULUS' : 'TIDAK LULUS';

// Create HTML for PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hasil Test PMB - <?php echo htmlspecialchars($user['nama_lengkap']); ?></title>
    <style>
        @page {
            margin: 20mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }
        
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .score-card {
            text-align: center;
            padding: 20px;
            border: 2px solid;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .score-pass {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .score-fail {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .score-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        
        .stat-box {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            color: white;
        }
        
        .stat-correct { background: #28a745; }
        .stat-incorrect { background: #dc3545; }
        .stat-unanswered { background: #ffc107; color: black; }
        .stat-total { background: #17a2b8; }
        
        .question-review {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        
        .question-correct { border-color: #28a745; background: #d4edda; }
        .question-incorrect { border-color: #dc3545; background: #f8d7da; }
        .question-unanswered { border-color: #ffc107; background: #fff3cd; }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .option {
            margin: 5px 0;
            padding: 8px;
            border-radius: 3px;
        }
        
        .option-correct {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        
        .