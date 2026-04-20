<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Perbaiki path config
require_once '../config/config.php';

// Fungsi check login jika belum ada di config
if (!function_exists('checkUserLogin')) {
    function checkUserLogin() {
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
            header('Location: ../login.php');
            exit();
        }
    }
}

checkUserLogin();

// Ambil user_id dari session
$user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;

if ($user_id == 0) {
    header('Location: ../login.php');
    exit();
}

// Get user data
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['message'] = 'User tidak ditemukan';
    $_SESSION['message_type'] = 'error';
    header('Location: ../login.php');
    exit();
}

// ========== PERBAIKAN UTAMA - CEK STATUS ==========
// Jika sudah memiliki nilai test DAN status sudah final (lulus/tidak_lulus/selesai), redirect ke hasil.php
if (isset($user['nilai_test']) && $user['nilai_test'] !== null && $user['nilai_test'] > 0 &&
    in_array($user['status_pendaftaran'], ['lulus', 'tidak_lulus', 'selesai'])) {
    header('Location: hasil.php');
    exit();
}

// Jika status sudah daftar_ulang, redirect ke daftar_ulang.php
if ($user['status_pendaftaran'] == 'daftar_ulang') {
    header('Location: daftar_ulang.php');
    exit();
}

// HANYA user dengan status 'registrasi' atau 'test' (yang belum selesai) yang bisa test
$allowed_statuses = ['registrasi', 'test'];
if (!in_array($user['status_pendaftaran'], $allowed_statuses)) {
    $_SESSION['message'] = 'Anda tidak dapat mengikuti test. Status Anda: ' . $user['status_pendaftaran'];
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Update status to test (hanya untuk user yang belum pernah test)
if ($user['status_pendaftaran'] == 'registrasi') {
    $update_status = "UPDATE user SET status_pendaftaran = 'test' WHERE id_user = ? AND status_pendaftaran = 'registrasi'";
    $stmt = $conn->prepare($update_status);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Get active questions
$questions_sql = "SELECT * FROM soal_test WHERE aktif = 1 OR aktif = 'Y' ORDER BY RAND() LIMIT 10";
$questions_result = $conn->query($questions_sql);

$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}
$total_questions = count($questions);

// Jika tidak ada soal
if ($total_questions == 0) {
    $_SESSION['message'] = 'Belum ada soal test yang tersedia. Silakan hubungi admin.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

// ========== HANDLE FORM SUBMISSION ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_test'])) {
    
    // Proses jawaban dengan sederhana
    $correct = 0;
    $total = 0;
    
    foreach ($questions as $question) {
        $total++;
        $answer_key = 'question_' . $question['id_soal'];
        $user_answer = isset($_POST[$answer_key]) ? strtolower(trim($_POST[$answer_key])) : '';
        
        // Simpan jawaban
        if (in_array($user_answer, ['a', 'b', 'c', 'd'])) {
            $insert_sql = "INSERT INTO hasil_test (id_user, id_soal, jawaban_user) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if ($insert_stmt) {
                $insert_stmt->bind_param("iis", $user_id, $question['id_soal'], $user_answer);
                if (!$insert_stmt->execute()) {
                    error_log("Insert failed: " . $insert_stmt->error);
                }
            } else {
                error_log("Prepare failed: " . $conn->error);
            }
        }
        
        // Hitung benar
        if ($user_answer == $question['jawaban_benar']) {
            $correct++;
        }
    }
    
    // Hitung nilai dan update
    $score = ($total > 0) ? ($correct / $total) * 100 : 0;
    $status = ($score >= 70) ? 'lulus' : 'tidak_lulus';
    
    $update_sql = "UPDATE user SET nilai_test = ?, status_pendaftaran = ? WHERE id_user = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("dsi", $score, $status, $user_id);
        if (!$update_stmt->execute()) {
            error_log("Update failed: " . $update_stmt->error);
        }
    } else {
        error_log("Update prepare failed: " . $conn->error);
    }
    
    // Set success message
    $_SESSION['message'] = '✨ Test berhasil diselesaikan! Nilai Anda: ' . number_format($score, 2);
    $_SESSION['message_type'] = 'success';
    
    // Force redirect - clean output buffer first
    if (ob_get_level()) ob_clean();
    header("Location: hasil.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Online - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --dark-bg: #0f0c29;
            --mid-bg: #302b63;
            --light-bg: #24243e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--mid-bg) 50%, var(--light-bg) 100%);
            min-height: 100vh;
            padding: 40px 0;
            position: relative;
        }

        .bg-decoration {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 0;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
        }

        .bg-circle-1 {
            width: 500px;
            height: 500px;
            top: -200px;
            right: -200px;
            background: radial-gradient(circle, rgba(79,70,229,0.12) 0%, transparent 70%);
        }

        .bg-circle-2 {
            width: 350px;
            height: 350px;
            bottom: -150px;
            left: -150px;
            background: radial-gradient(circle, rgba(99,102,241,0.08) 0%, transparent 70%);
        }

        .container-custom {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .card-premium {
            background: rgba(255,255,255,0.98);
            border-radius: 32px;
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 55px -15px rgba(0,0,0,0.3);
        }

        .card-header-premium {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 25px 30px;
            color: white;
        }

        .timer-premium {
            font-size: 28px;
            font-weight: 800;
            background: rgba(255,255,255,0.15);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            font-family: monospace;
            letter-spacing: 2px;
        }

        .question-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .option {
            margin: 10px 0;
            padding: 12px 18px;
            border-radius: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
        }

        .option:hover {
            background: #e0e7ff;
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .option.selected {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: var(--primary);
        }

        .option.selected label {
            color: white;
        }

        .option input {
            margin-right: 12px;
            accent-color: var(--primary);
        }

        .progress-container {
            background: #e5e7eb;
            border-radius: 12px;
            height: 10px;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 12px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 14px 40px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16,185,129,0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            transform: none;
        }

        .alert-custom {
            border-radius: 16px;
            border: none;
            padding: 15px 20px;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }
            .card-header-premium {
                padding: 20px;
            }
            .timer-premium {
                font-size: 20px;
                padding: 5px 15px;
            }
            .question-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-decoration">
        <div class="bg-circle bg-circle-1"></div>
        <div class="bg-circle bg-circle-2"></div>
    </div>

    <div class="container-custom">
        <div class="card-premium">
            <div class="card-header-premium">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h3 class="mb-1 fw-bold">📝 Test Online PMB</h3>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user['nama_lengkap']); ?> 
                            | <i class="fas fa-qrcode me-1"></i> <?php echo htmlspecialchars($user['nomor_test'] ?? '-'); ?>
                        </p>
                    </div>
                    <div class="timer-premium" id="timer">
                        <i class="fas fa-hourglass-half me-2"></i>01:00:00
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-custom mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info alert-custom mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>📌 Petunjuk Pengerjaan:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Jawab semua soal dengan memilih salah satu option (A, B, C, atau D)</li>
                        <li>⏰ Waktu pengerjaan: <strong>60 menit</strong></li>
                        <li>📝 Test hanya bisa dilakukan <strong>sekali</strong></li>
                        <li>🎯 Nilai minimal kelulusan: <strong>70</strong></li>
                        <li>💡 Klik pada option untuk memilih jawaban</li>
                    </ul>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted"><i class="fas fa-chart-line me-1"></i> Progress Jawaban</small>
                        <small class="text-muted fw-bold" id="answeredCount">0/<?php echo $total_questions; ?></small>
                    </div>
                    <div class="progress-container">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>
                
                <form method="POST" id="testForm">
                    <?php $no = 1; foreach ($questions as $question): ?>
                    <div class="question-card" data-id="<?php echo $question['id_soal']; ?>">
                        <h5 class="mb-3 fw-semibold"><?php echo $no . '. ' . htmlspecialchars($question['pertanyaan']); ?></h5>
                        <div class="options">
                            <div class="option" data-question="<?php echo $question['id_soal']; ?>" data-value="a">
                                <input type="radio" name="question_<?php echo $question['id_soal']; ?>" value="a" id="q<?php echo $question['id_soal']; ?>_a">
                                <label for="q<?php echo $question['id_soal']; ?>_a"><strong>A.</strong> <?php echo htmlspecialchars($question['pilihan_a']); ?></label>
                            </div>
                            <div class="option" data-question="<?php echo $question['id_soal']; ?>" data-value="b">
                                <input type="radio" name="question_<?php echo $question['id_soal']; ?>" value="b" id="q<?php echo $question['id_soal']; ?>_b">
                                <label for="q<?php echo $question['id_soal']; ?>_b"><strong>B.</strong> <?php echo htmlspecialchars($question['pilihan_b']); ?></label>
                            </div>
                            <div class="option" data-question="<?php echo $question['id_soal']; ?>" data-value="c">
                                <input type="radio" name="question_<?php echo $question['id_soal']; ?>" value="c" id="q<?php echo $question['id_soal']; ?>_c">
                                <label for="q<?php echo $question['id_soal']; ?>_c"><strong>C.</strong> <?php echo htmlspecialchars($question['pilihan_c']); ?></label>
                            </div>
                            <div class="option" data-question="<?php echo $question['id_soal']; ?>" data-value="d">
                                <input type="radio" name="question_<?php echo $question['id_soal']; ?>" value="d" id="q<?php echo $question['id_soal']; ?>_d">
                                <label for="q<?php echo $question['id_soal']; ?>_d"><strong>D.</strong> <?php echo htmlspecialchars($question['pilihan_d']); ?></label>
                            </div>
                        </div>
                    </div>
                    <?php $no++; endforeach; ?>
                    
                    <div class="text-center mt-4 pt-3">
                        <button type="submit" name="submit_test" class="btn-submit" id="submitBtn">
                            <i class="fas fa-paper-plane me-2"></i> Selesaikan Test
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let totalSeconds = 3600;
        let timerInterval;
        const timerElement = document.getElementById('timer');
        let formSubmitted = false;
        
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        function updateTimer() {
            timerElement.innerHTML = `<i class="fas fa-hourglass-half me-2"></i>${formatTime(totalSeconds)}`;
            
            if (totalSeconds > 0) {
                totalSeconds--;
            } else {
                clearInterval(timerInterval);
                if (!formSubmitted) {
                    alert('⏰ Waktu habis! Test akan dikirim secara otomatis.');
                    document.getElementById('testForm').submit();
                }
            }
        }
        
        timerInterval = setInterval(updateTimer, 1000);
        
        function updateProgress() {
            const totalQuestions = <?php echo $total_questions; ?>;
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            const percentage = (answered / totalQuestions) * 100;
            
            // Update counter
            const answeredCount = document.getElementById('answeredCount');
            if (answeredCount) {
                answeredCount.textContent = `${answered}/${totalQuestions}`;
            }
            
            // Update progress bar
            const progressFill = document.getElementById('progressFill');
            if (progressFill) {
                progressFill.style.width = `${percentage}%`;
            }
            
            // Debug removed for production
        }
        
        // Event listener untuk option div (klik pada area option)
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function(e) {
                // Prevent double triggering
                if (e.target.tagName === 'INPUT') return;
                
                const questionId = this.dataset.question;
                const value = this.dataset.value;
                const radio = document.getElementById(`q${questionId}_${value}`);
                if (radio) {
                    radio.checked = true;
                    // Trigger change event
                    const changeEvent = new Event('change', { bubbles: true });
                    radio.dispatchEvent(changeEvent);
                }
            });
        });
        
        // Event listener untuk radio button langsung
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                updateProgress();
                
                // Update visual selection
                const questionId = this.name.replace('question_', '');
                const options = document.querySelectorAll(`[data-question="${questionId}"]`);
                options.forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                const parentOption = this.closest('.option');
                if (parentOption) {
                    parentOption.classList.add('selected');
                }
            });
        });
        
        // Initialize progress bar on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateProgress();
        });
        
        // Also call updateProgress immediately
        updateProgress();
        
        function confirmSubmit() {
            const totalQuestions = <?php echo $total_questions; ?>;
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            
            if (answered < totalQuestions) {
                return confirm(`⚠️ Anda baru menjawab ${answered} dari ${totalQuestions} soal.\n\nYakin ingin menyelesaikan test? Soal yang tidak dijawab akan dianggap salah.`);
            }
            return confirm('✅ Apakah Anda yakin ingin menyelesaikan test?\n\nJawaban tidak dapat diubah setelah disubmit.');
        }
        
        const form = document.getElementById('testForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(e) {
            if (submitBtn.disabled || formSubmitted) {
                e.preventDefault();
                return false;
            }
            if (!confirmSubmit()) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan Jawaban...';
            clearInterval(timerInterval);
        });
    </script>
</body>
</html>