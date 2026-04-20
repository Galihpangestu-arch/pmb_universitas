<?php
require_once '../config/config.php';
checkUserLogin();

$user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;

if ($user_id == 0) {
    header('Location: ../login.php');
    exit();
}

// Ambil data user
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

// Auto update status berdasarkan nilai test (jika masih 'test')
function autoUpdateStatusByNilai($conn, $user_id, $nilai_test) {
    $passing_grade = 60;
    $current_status = $user['status_pendaftaran'] ?? 'test';
    
    if ($current_status === 'test' && $nilai_test !== null && $nilai_test > 0) {
        $new_status = ($nilai_test >= $passing_grade) ? 'lulus' : 'tidak_lulus';
        if ($new_status !== $current_status) {
            $update = $conn->prepare("UPDATE user SET status_pendaftaran = ? WHERE id_user = ?");
            $update->bind_param("si", $new_status, $user_id);
            $update->execute();
            return $new_status;
        }
    }
    return $current_status;
}

// Update status jika perlu
$status = autoUpdateStatusByNilai($conn, $user_id, $user['nilai_test'] ?? null);
$is_lulus = ($status == 'lulus');
$score = $user['nilai_test'] ?? 0;

// Cek daftar ulang
$daftar_ulang = null;
$table_check = $conn->query("SHOW TABLES LIKE 'daftar_ulang'");
if ($table_check && $table_check->num_rows > 0) {
    $query_du = "SELECT * FROM daftar_ulang WHERE id_user = ? ORDER BY created_at DESC LIMIT 1";
    $stmt_du = $conn->prepare($query_du);
    $stmt_du->bind_param("i", $user_id);
    $stmt_du->execute();
    $daftar_ulang = $stmt_du->get_result()->fetch_assoc();
}

// Jika belum test, redirect ke take_test.php
if ($score === null || $score == 0) {
    header('Location: take_test.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Test - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --success: #10b981;
            --danger: #ef4444;
            --primary: #4f46e5;
            --warning: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', sans-serif;
            padding: 40px 0;
        }

        .result-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .result-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .result-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 3px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .score-circle:hover {
            transform: scale(1.05);
        }

        .score-number {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
        }

        .score-number small {
            font-size: 14px;
            font-weight: normal;
            opacity: 0.8;
        }

        .result-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            margin-top: 15px;
        }

        .lulus {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .tidak-lulus {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
        }

        .detail-info {
            background: #f8fafc;
            border-radius: 20px;
            padding: 15px;
            margin: 20px 0;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 16px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(79,70,229,0.4);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 16px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16,185,129,0.4);
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f59e0b;
            position: absolute;
            animation: confetti-fall 3s linear forwards;
        }

        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <div class="result-header">
                <div class="score-circle">
                    <div class="text-center">
                        <div class="score-number"><?php echo number_format((float)$score, 1); ?></div>
                        <small>Nilai Anda</small>
                    </div>
                </div>
                
                <div class="result-badge <?php echo $is_lulus ? 'lulus' : 'tidak-lulus'; ?>">
                    <i class="fas <?php echo $is_lulus ? 'fa-trophy' : 'fa-frown'; ?> me-2"></i>
                    <?php echo $is_lulus ? 'SELAMAT! ANDA LULUS' : 'MAAF, ANDA TIDAK LULUS'; ?>
                </div>
                
                <p class="mb-0 mt-3 opacity-75">
                    <?php if ($is_lulus): ?>
                        <i class="fas fa-check-circle me-1"></i> Selamat! Anda berhasil melewati seleksi.
                    <?php else: ?>
                        <i class="fas fa-info-circle me-1"></i> Tingkatkan belajar Anda untuk gelombang berikutnya.
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="p-4">
                <div class="detail-info">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-muted small">Nilai Akhir</div>
                            <h4 class="fw-bold mb-0" style="color: var(--primary);"><?php echo number_format((float)$score, 2); ?></h4>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Status Kelulusan</div>
                            <h4 class="fw-bold mb-0 <?php echo $is_lulus ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $is_lulus ? 'LULUS' : 'TIDAK LULUS'; ?>
                            </h4>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-muted small">Nomor Test</div>
                            <p class="fw-bold mb-0"><?php echo htmlspecialchars($user['nomor_test'] ?? '-'); ?></p>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Nama Peserta</div>
                            <p class="fw-bold mb-0"><?php echo htmlspecialchars($user['nama_lengkap'] ?? '-'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <?php if ($is_lulus): ?>
                        <?php if (!$daftar_ulang): ?>
                            <a href="re_registration.php" class="btn btn-success-custom text-white d-inline-flex align-items-center gap-2">
                                <i class="fas fa-clipboard-list"></i>
                                Lanjut ke Daftar Ulang
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-dashboard text-white d-inline-flex align-items-center gap-2">
                                <i class="fas fa-home"></i>
                                Kembali ke Dashboard
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-dashboard text-white d-inline-flex align-items-center gap-2">
                            <i class="fas fa-home"></i>
                            Kembali ke Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confetti effect for passed users
        <?php if ($is_lulus): ?>
        function createConfetti() {
            const colors = ['#10b981', '#34d399', '#f59e0b', '#fbbf24', '#ef4444', '#f87171', '#4f46e5', '#6366f1'];
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = Math.random() * 8 + 4 + 'px';
                confetti.style.height = Math.random() * 8 + 4 + 'px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * window.innerWidth + 'px';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';
                confetti.style.zIndex = '9999';
                confetti.style.animation = `confetti-fall ${Math.random() * 2 + 2}s linear forwards`;
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 4000);
            }
        }
        
        // Trigger confetti on load
        setTimeout(createConfetti, 500);
        <?php endif; ?>
    </script>
</body>
</html>