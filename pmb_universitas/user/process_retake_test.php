<?php
require_once '../config/config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$user_id = $_SESSION['user_id'];
$attempt_number = isset($_POST['attempt_number']) ? intval($_POST['attempt_number']) : 1;
$time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;

// Validate input
if (!isset($_POST['questions']) || empty($_POST['questions'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada jawaban yang dikirim']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check user's current status
    $user_sql = "SELECT nilai_test FROM user WHERE id_user = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if ($user['nilai_test'] >= 70) {
        throw new Exception('Anda sudah lulus test sebelumnya');
    }
    
    // Check retake attempts
    $attempt_sql = "SELECT COUNT(*) as attempt_count FROM test_attempts 
                    WHERE id_user = ? AND attempt_type = 'retake'";
    $attempt_stmt = $conn->prepare($attempt_sql);
    $attempt_stmt->bind_param("i", $user_id);
    $attempt_stmt->execute();
    $attempt_result = $attempt_stmt->get_result()->fetch_assoc();
    
    if ($attempt_result['attempt_count'] >= 3) {
        throw new Exception('Batas test ulang sudah habis');
    }
    
    // Insert test attempt record
    $insert_attempt_sql = "INSERT INTO test_attempts (id_user, attempt_type, attempt_number, start_time, end_time, time_spent) 
                           VALUES (?, 'retake', ?, NOW(), NOW(), ?)";
    $insert_attempt_stmt = $conn->prepare($insert_attempt_sql);
    $insert_attempt_stmt->bind_param("iii", $user_id, $attempt_number, $time_spent);
    $insert_attempt_stmt->execute();
    $attempt_id = $conn->insert_id;
    
    // Process answers
    $questions = $_POST['questions'];
    $total_questions = count($questions);
    $correct_answers = 0;
    $wrong_answers = 0;
    $unanswered = 0;
    
    // Get correct answers from database
    $question_ids = array();
    foreach ($questions as $question_data) {
        $question_ids[] = $question_data['id'];
    }
    
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    $answer_sql = "SELECT id_soal, jawaban_benar FROM soal_test 
                   WHERE id_soal IN ($placeholders)";
    $answer_stmt = $conn->prepare($answer_sql);
    
    $types = str_repeat('i', count($question_ids));
    $answer_stmt->bind_param($types, ...$question_ids);
    $answer_stmt->execute();
    $answer_result = $answer_stmt->get_result();
    
    $correct_answers_map = [];
    while ($row = $answer_result->fetch_assoc()) {
        $correct_answers_map[$row['id_soal']] = $row['jawaban_benar'];
    }
    
    // Save each answer to hasil_test
    $insert_sql = "INSERT INTO hasil_test (id_user, id_soal, jawaban_user, waktu_jawab, attempt_number, attempt_id) 
                   VALUES (?, ?, ?, NOW(), ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    foreach ($questions as $question_data) {
        $question_id = $question_data['id'];
        $user_answer = isset($question_data['answer']) ? $question_data['answer'] : null;
        
        // Check if answer is correct
        $is_correct = false;
        if ($user_answer && isset($correct_answers_map[$question_id])) {
            $is_correct = ($user_answer == $correct_answers_map[$question_id]);
            if ($is_correct) {
                $correct_answers++;
            } else {
                $wrong_answers++;
            }
        } else {
            $unanswered++;
        }
        
        // Insert result
        $insert_stmt->bind_param("iissi", $user_id, $question_id, $user_answer, $attempt_number, $attempt_id);
        $insert_stmt->execute();
    }
    
    // Calculate score
    $score = ($correct_answers / $total_questions) * 100;
    $score = round($score, 2);
    
    // Determine if passed
    $passed = ($score >= 70) ? 1 : 0;
    $status = ($score >= 70) ? 'lulus' : 'tidak_lulus';
    
    // Update test_attempts with score and status
    $update_attempt_sql = "UPDATE test_attempts SET score = ?, status = ? WHERE id_attempt = ?";
    $update_attempt_stmt = $conn->prepare($update_attempt_sql);
    $update_attempt_stmt->bind_param("dsi", $score, $status, $attempt_id);
    $update_attempt_stmt->execute();
    
    // Update user's nilai_test if passed or if it's their best score
    if ($passed) {
        // If passed, update nilai_test
        $update_sql = "UPDATE user SET nilai_test = ?, status_pendaftaran = 'lulus' WHERE id_user = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("di", $score, $user_id);
        $update_stmt->execute();
    } else {
        // Check if this score is better than previous
        $best_sql = "SELECT MAX(score) as best_score FROM test_attempts 
                     WHERE id_user = ? AND attempt_type = 'retake'";
        $best_stmt = $conn->prepare($best_sql);
        $best_stmt->bind_param("i", $user_id);
        $best_stmt->execute();
        $best_result = $best_stmt->get_result()->fetch_assoc();
        
        if ($score > ($best_result['best_score'] ?: 0)) {
            // Update with best score even if not passed
            $update_sql = "UPDATE user SET nilai_test = ? WHERE id_user = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("di", $score, $user_id);
            $update_stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test berhasil dikumpulkan',
        'attempt_id' => $attempt_id,
        'score' => $score,
        'status' => $status,
        'correct' => $correct_answers,
        'wrong' => $wrong_answers,
        'unanswered' => $unanswered,
        'total' => $total_questions
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>