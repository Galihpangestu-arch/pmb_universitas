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

// Check if user has taken the test and failed
if (!$user['nilai_test']) {
    redirectWithMessage('dashboard.php', 'error', 'Anda belum mengikuti test. Silakan ikuti test terlebih dahulu.');
}

// Check if user can retake the test (only if failed)
if ($user['nilai_test'] >= 70) {
    redirectWithMessage('dashboard.php', 'error', 'Anda sudah lulus test. Tidak dapat mengulang test.');
}

// Check retake attempts
$retake_sql = "SELECT COUNT(*) as attempt_count, MAX(attempt_date) as last_attempt 
               FROM test_attempts 
               WHERE id_user = ? AND attempt_type = 'retake'";
$retake_stmt = $conn->prepare($retake_sql);
$retake_stmt->bind_param("i", $user_id);
$retake_stmt->execute();
$retake_result = $retake_stmt->get_result()->fetch_assoc();

$attempt_count = $retake_result['attempt_count'] ?: 0;
$max_attempts = 3; // Maximum 3 retake attempts
$can_retake = $attempt_count < $max_attempts;

// Check cooldown period (7 days between retakes)
$cooldown_days = 7;
$can_retake_now = true;
if ($retake_result['last_attempt']) {
    $last_attempt = strtotime($retake_result['last_attempt']);
    $next_allowed = $last_attempt + ($cooldown_days * 24 * 60 * 60);
    $can_retake_now = time() >= $next_allowed;
    
    if (!$can_retake_now) {
        $days_left = ceil(($next_allowed - time()) / (24 * 60 * 60));
    }
}

// Get available questions (exclude questions already answered correctly in previous attempts?)
$question_sql = "SELECT st.* FROM soal_test st 
                 WHERE st.aktif = 1 
                 ORDER BY RAND() 
                 LIMIT 50"; // Assume 50 questions per test
$question_result = $conn->query($question_sql);
$total_questions = $question_result->num_rows;

if ($total_questions == 0) {
    redirectWithMessage('dashboard.php', 'error', 'Soal test belum tersedia. Silakan hubungi admin.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Ulang - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .retake-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .rules-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 5px solid #f5576c;
        }
        
        .attempt-badge {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
        }
        
        .countdown-timer {
            background: #2c3e50;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .countdown-timer i {
            margin-right: 10px;
            color: #f1c40f;
        }
        
        .question-nav {
            background: white;
            border-radius: 15px;
            padding: 20px;
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .question-number {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 5px;
        }
        
        .question-number.answered {
            background: #28a745;
            color: white;
        }
        
        .question-number.current {
            background: #007bff;
            color: white;
            transform: scale(1.1);
        }
        
        .question-number.unanswered {
            background: #e9ecef;
            color: #495057;
        }
        
        .question-number:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .question-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .option-item {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-item:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        
        .option-item.selected {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        
        .option-prefix {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            text-align: center;
            line-height: 30px;
            margin-right: 15px;
            font-weight: bold;
        }
        
        .option-item.selected .option-prefix {
            background: #28a745;
            color: white;
        }
        
        .test-actions {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            position: sticky;
            bottom: 20px;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
        }
        
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .floating-warning {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            background: white;
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-left: 5px solid #dc3545;
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            .question-nav {
                position: static;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if (!$can_retake): ?>
            <!-- Max attempts reached -->
            <div class="retake-header">
                <h2><i class="fas fa-exclamation-triangle me-3"></i>Batas Test Ulang Habis</h2>
                <p class="mb-0">Anda telah mencapai batas maksimum test ulang (<?php echo $max_attempts; ?> kali). Silakan hubungi admin untuk informasi lebih lanjut.</p>
            </div>
            <div class="alert alert-danger">
                <h5>Anda tidak dapat mengikuti test ulang lagi.</h5>
                <p>Silakan datang ke kampus untuk konsultasi lebih lanjut atau daftar di periode pendaftaran berikutnya.</p>
                <a href="dashboard.php" class="btn btn-primary mt-3">Kembali ke Dashboard</a>
            </div>
        <?php elseif (!$can_retake_now): ?>
            <!-- Cooldown period -->
            <div class="retake-header">
                <h2><i class="fas fa-clock me-3"></i>Masa Tenggang Test Ulang</h2>
                <p class="mb-0">Anda harus menunggu <?php echo $days_left; ?> hari lagi untuk dapat mengikuti test ulang.</p>
            </div>
            <div class="alert alert-warning">
                <h5><i class="fas fa-hourglass-half me-2"></i>Test Ulang Belum Tersedia</h5>
                <p>Test ulang hanya dapat dilakukan setiap <?php echo $cooldown_days; ?> hari. Silakan kembali lagi pada:</p>
                <strong><?php echo date('d F Y H:i', $next_allowed); ?></strong>
                <br><br>
                <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        <?php else: ?>
            <!-- Retake test form -->
            <div class="retake-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2"><i class="fas fa-redo-alt me-3"></i>Test Ulang PMB</h2>
                        <p class="mb-0">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['nama_lengkap']); ?> | 
                            <i class="fas fa-id-card me-2"></i><?php echo $user['nomor_test']; ?> |
                            <i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($user['program_studi']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="attempt-badge">
                            <i class="fas fa-chart-line me-2"></i>
                            Percobaan ke-<?php echo $attempt_count + 1; ?>/<?php echo $max_attempts; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rules Card -->
            <div class="rules-card">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-info-circle fa-2x text-info me-3"></i>
                    <h4 class="mb-0">Aturan Test Ulang</h4>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Waktu pengerjaan: <strong>90 menit</strong>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Jumlah soal: <strong><?php echo $total_questions; ?> soal</strong>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Nilai minimal lulus: <strong>70</strong>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                Maksimal test ulang: <strong><?php echo $max_attempts; ?> kali</strong>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                Tenggang waktu: <strong><?php echo $cooldown_days; ?> hari</strong>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                Jawaban tidak bisa diubah setelah dikirim
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Timer and Progress -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="countdown-timer" id="timer">
                    <i class="fas fa-hourglass-half"></i>
                    <span id="hours">01</span>:<span id="minutes">30</span>:<span id="seconds">00</span>
                </div>
                <div class="progress flex-grow-1 ms-4" style="height: 10px;">
                    <div class="progress-bar bg-success" id="progressBar" style="width: 0%;"></div>
                </div>
                <span class="ms-3" id="answeredCount">0/<?php echo $total_questions; ?></span>
            </div>
            
            <!-- Floating warning for unanswered questions -->
            <div class="floating-warning" id="floatingWarning">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                <span id="unansweredCount"><?php echo $total_questions; ?></span> soal belum dijawab!
            </div>
            
            <form id="testForm" action="process_retake_test.php" method="POST">
                <input type="hidden" name="attempt_number" value="<?php echo $attempt_count + 1; ?>">
                <div class="row">
                    <!-- Question Navigation -->
                    <div class="col-md-3">
                        <div class="question-nav">
                            <h5 class="mb-3">Navigasi Soal</h5>
                            <div class="row g-2" id="questionNav">
                                <?php 
                                $question_number = 1;
                                mysqli_data_seek($question_result, 0);
                                while ($question = $question_result->fetch_assoc()): 
                                ?>
                                <div class="col-3">
                                    <div class="question-number unanswered" 
                                         data-question="<?php echo $question_number; ?>"
                                         onclick="jumpToQuestion(<?php echo $question_number; ?>)">
                                        <?php echo $question_number; ?>
                                    </div>
                                </div>
                                <?php 
                                $question_number++;
                                endwhile; 
                                ?>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between small">
                                <span><span class="badge bg-success">Terjawab</span></span>
                                <span><span class="badge bg-primary">Sekarang</span></span>
                                <span><span class="badge bg-secondary">Belum</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Questions -->
                    <div class="col-md-9">
                        <div id="questionsContainer">
                            <?php 
                            mysqli_data_seek($question_result, 0);
                            $question_number = 1;
                            while ($question = $question_result->fetch_assoc()): 
                            ?>
                            <div class="question-container" id="question<?php echo $question_number; ?>" data-question="<?php echo $question_number; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="mb-0">
                                        <span class="badge bg-primary me-2">Soal #<?php echo $question_number; ?></span>
                                    </h5>
                                    <small class="text-muted">
                                        <i class="fas fa-flag"></i> Bobot: 2 poin
                                    </small>
                                </div>
                                
                                <p class="mb-4"><?php echo nl2br(htmlspecialchars($question['pertanyaan'])); ?></p>
                                
                                <input type="hidden" name="questions[<?php echo $question_number; ?>][id]" value="<?php echo $question['id_soal']; ?>">
                                
                                <div class="options">
                                    <div class="option-item" onclick="selectOption(<?php echo $question_number; ?>, 'a')">
                                        <input type="radio" name="questions[<?php echo $question_number; ?>][answer]" 
                                               value="a" id="q<?php echo $question_number; ?>a" style="display: none;">
                                        <div>
                                            <span class="option-prefix">A</span>
                                            <?php echo htmlspecialchars($question['pilihan_a']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="option-item" onclick="selectOption(<?php echo $question_number; ?>, 'b')">
                                        <input type="radio" name="questions[<?php echo $question_number; ?>][answer]" 
                                               value="b" id="q<?php echo $question_number; ?>b" style="display: none;">
                                        <div>
                                            <span class="option-prefix">B</span>
                                            <?php echo htmlspecialchars($question['pilihan_b']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="option-item" onclick="selectOption(<?php echo $question_number; ?>, 'c')">
                                        <input type="radio" name="questions[<?php echo $question_number; ?>][answer]" 
                                               value="c" id="q<?php echo $question_number; ?>c" style="display: none;">
                                        <div>
                                            <span class="option-prefix">C</span>
                                            <?php echo htmlspecialchars($question['pilihan_c']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="option-item" onclick="selectOption(<?php echo $question_number; ?>, 'd')">
                                        <input type="radio" name="questions[<?php echo $question_number; ?>][answer]" 
                                               value="d" id="q<?php echo $question_number; ?>d" style="display: none;">
                                        <div>
                                            <span class="option-prefix">D</span>
                                            <?php echo htmlspecialchars($question['pilihan_d']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 d-flex justify-content-between">
                                    <?php if ($question_number > 1): ?>
                                    <button type="button" class="btn btn-outline-secondary" onclick="jumpToQuestion(<?php echo $question_number - 1; ?>)">
                                        <i class="fas fa-arrow-left me-2"></i>Sebelumnya
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($question_number < $total_questions): ?>
                                    <button type="button" class="btn btn-outline-primary ms-auto" onclick="jumpToQuestion(<?php echo $question_number + 1; ?>)">
                                        Selanjutnya<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-success ms-auto" onclick="scrollToSubmit()">
                                        Selesai<i class="fas fa-check ms-2"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php 
                            $question_number++;
                            endwhile; 
                            ?>
                        </div>
                        
                        <!-- Submit Section -->
                        <div class="test-actions" id="submitSection">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="warning-message" id="submitWarning" style="display: none;">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <span id="unansweredWarning">0</span> soal belum dijawab. Yakin ingin mengumpulkan?
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="button" class="btn btn-secondary me-2" onclick="resetUnanswered()">
                                        <i class="fas fa-undo me-2"></i>Cek Kembali
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="confirmSubmit()">
                                        <i class="fas fa-paper-plane me-2"></i>Kumpulkan Jawaban
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Konfirmasi Pengumpulan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                    <p class="mb-0 text-danger">
                        <strong>Perhatian:</strong> Jawaban tidak dapat diubah setelah dikumpulkan!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cek Kembali
                    </button>
                    <button type="button" class="btn btn-success" onclick="submitTest()">
                        <i class="fas fa-check me-2"></i>Ya, Kumpulkan
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        const totalQuestions = <?php echo $total_questions; ?>;
        let currentQuestion = 1;
        let answers = {};
        let timeLeft = 90 * 60; // 90 minutes in seconds
        let timerInterval;
        
        // Timer function
        function startTimer() {
            timerInterval = setInterval(function() {
                timeLeft--;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert('Waktu habis! Jawaban akan otomatis dikumpulkan.');
                    submitTest();
                }
                
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
                
                // Change color when time is running low
                if (timeLeft <= 300) { // 5 minutes
                    document.getElementById('timer').style.backgroundColor = '#dc3545';
                } else if (timeLeft <= 600) { // 10 minutes
                    document.getElementById('timer').style.backgroundColor = '#ffc107';
                }
            }, 1000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            startTimer();
            showQuestion(1);
            updateProgress();
            
            // Load saved answers from localStorage if any (for recovery)
            loadSavedAnswers();
            
            // Prevent accidental navigation
            window.addEventListener('beforeunload', function(e) {
                const unanswered = getUnansweredCount();
                if (unanswered > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        });
        
        // Show specific question
        function showQuestion(number) {
            // Hide all questions
            document.querySelectorAll('.question-container').forEach(q => {
                q.style.display = 'none';
            });
            
            // Show selected question
            document.getElementById('question' + number).style.display = 'block';
            
            // Update navigation
            document.querySelectorAll('.question-number').forEach(q => {
                q.classList.remove('current');
            });
            document.querySelector(`.question-number[data-question="${number}"]`).classList.add('current');
            
            currentQuestion = number;
        }
        
        // Jump to question
        function jumpToQuestion(number) {
            showQuestion(number);
        }
        
        // Select option
        function selectOption(questionNumber, option) {
            // Update radio
            document.getElementById(`q${questionNumber}${option}`).checked = true;
            
            // Update visual
            const container = document.getElementById('question' + questionNumber);
            container.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            
            // Save answer
            answers[questionNumber] = option;
            
            // Update navigation
            document.querySelector(`.question-number[data-question="${questionNumber}"]`).classList.remove('unanswered');
            document.querySelector(`.question-number[data-question="${questionNumber}"]`).classList.add('answered');
            
            // Update progress
            updateProgress();
            
            // Save to localStorage
            saveAnswers();
        }
        
        // Update progress bar and counters
        function updateProgress() {
            const answered = Object.keys(answers).length;
            const percentage = (answered / totalQuestions) * 100;
            
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('answeredCount').textContent = `${answered}/${totalQuestions}`;
            
            const unanswered = totalQuestions - answered;
            if (unanswered > 0) {
                document.getElementById('floatingWarning').style.display = 'flex';
                document.getElementById('unansweredCount').textContent = unanswered;
            } else {
                document.getElementById('floatingWarning').style.display = 'none';
            }
            
            // Update submit warning
            if (unanswered > 0) {
                document.getElementById('submitWarning').style.display = 'block';
                document.getElementById('unansweredWarning').textContent = unanswered;
            } else {
                document.getElementById('submitWarning').style.display = 'none';
            }
        }
        
        // Get unanswered count
        function getUnansweredCount() {
            return totalQuestions - Object.keys(answers).length;
        }
        
        // Confirm submit
        function confirmSubmit() {
            const unanswered = getUnansweredCount();
            const modal = new bootstrap.Modal(document.getElementById('submitModal'));
            
            let message = '';
            if (unanswered === 0) {
                message = 'Semua soal telah dijawab. Yakin ingin mengumpulkan?';
            } else {
                message = `Masih ada <strong>${unanswered}</strong> soal yang belum dijawab. Yakin ingin mengumpulkan?`;
            }
            
            document.getElementById('modalMessage').innerHTML = message;
            modal.show();
        }
        
        // Submit test
        function submitTest() {
            clearInterval(timerInterval);
            
            // Get form data
            const formData = new FormData(document.getElementById('testForm'));
            
            // Add time spent
            const timeSpent = (90 * 60) - timeLeft;
            formData.append('time_spent', timeSpent);
            
            // Submit via AJAX
            fetch('process_retake_test.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear localStorage
                    localStorage.removeItem('retakeTestAnswers');
                    
                    // Redirect to result page
                    window.location.href = `retake_result.php?attempt=${data.attempt_id}`;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }
        
        // Reset unanswered check
        function resetUnanswered() {
            jumpToQuestion(1);
        }
        
        // Scroll to submit section
        function scrollToSubmit() {
            document.getElementById('submitSection').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Save answers to localStorage (for recovery)
        function saveAnswers() {
            localStorage.setItem('retakeTestAnswers', JSON.stringify(answers));
        }
        
        // Load saved answers from localStorage
        function loadSavedAnswers() {
            const saved = localStorage.getItem('retakeTestAnswers');
            if (saved) {
                if (confirm('Ada jawaban tersimpan. Ingin melanjutkan dari terakhir kali?')) {
                    answers = JSON.parse(saved);
                    
                    // Restore selections
                    for (let q in answers) {
                        const option = answers[q];
                        document.getElementById(`q${q}${option}`).checked = true;
                        
                        const container = document.getElementById('question' + q);
                        if (container) {
                            // Find and select the option
                            container.querySelectorAll('.option-item').forEach(item => {
                                if (item.querySelector(`input[value="${option}"]`)) {
                                    item.classList.add('selected');
                                }
                            });
                        }
                        
                        // Update navigation
                        document.querySelector(`.question-number[data-question="${q}"]`).classList.remove('unanswered');
                        document.querySelector(`.question-number[data-question="${q}"]`).classList.add('answered');
                    }
                    
                    updateProgress();
                } else {
                    localStorage.removeItem('retakeTestAnswers');
                }
            }
        }
    </script>
</body>
</html>