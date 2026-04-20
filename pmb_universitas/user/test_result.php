<?php
require_once '../config/config.php';
checkUserLogin();

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

// Jika belum test, redirect ke test
if ($user['nilai_test'] === null || $user['nilai_test'] == 0) {
    header('Location: take_test.php');
    exit();
}

$score = $user['nilai_test'];
$status = $user['status_pendaftaran'];
$is_passed = ($status == 'lulus');

// Cek daftar ulang
$daftar_ulang = null;
$table_check = $conn->query("SHOW TABLES LIKE 'daftar_ulang'");
if ($table_check->num_rows > 0) {
    $query_du = "SELECT * FROM daftar_ulang WHERE id_user = ? ORDER BY created_at DESC LIMIT 1";
    $stmt_du = $conn->prepare($query_du);
    $stmt_du->bind_param("i", $user_id);
    $stmt_du->execute();
    $daftar_ulang = $stmt_du->get_result()->fetch_assoc();
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
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }

        .result-card {
            background: white;
            border-radius: 32px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
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

        .score-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            font-size: 48px;
            font-weight: 800;
            position: relative;
        }

        .score-circle.passed {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            box-shadow: 0 10px 30px rgba(16,185,129,0.3);
        }

        .score-circle.failed {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
            box-shadow: 0 10px 30px rgba(239,68,68,0.3);
        }

        .score-circle small {
            font-size: 14px;
            font-weight: normal;
            opacity: 0.9;
        }

        .btn-custom {
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            color: white;
        }

        .detail-info {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="result-card">
                    <i class="fas fa-clipboard-list fa-4x" style="color: var(--primary); margin-bottom: 15px;"></i>
                    <h2 class="fw-bold mb-2">Hasil Test Seleksi</h2>
                    <p class="text-muted">PMB Universitas <?php echo date('Y'); ?></p>
                    
                    <div class="score-circle <?php echo $is_passed ? 'passed' : 'failed'; ?>">
                        <?php echo round($score); ?>
                        <small>Nilai</small>
                    </div>
                    
                    <h3 class="mb-3">
                        <?php if ($is_passed): ?>
                            <span style="color: var(--success);">🎉 SELAMAT! 🎉</span>
                        <?php else: ?>
                            <span style="color: var(--danger);">😔 MOHON MAAF</span>
                        <?php endif; ?>
                    </h3>
                    
                    <div class="detail-info">
                        <p class="mb-2">
                            <i class="fas fa-user me-2"></i>
                            <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-id-card me-2"></i>
                            No. Test: <?php echo htmlspecialchars($user['nomor_test'] ?? '-'); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Nilai Akhir: <strong><?php echo number_format($score, 2); ?></strong>
                        </p>
                    </div>
                    
                    <div class="mt-4">
                        <?php if ($is_passed): ?>
                            <?php if (!$daftar_ulang): ?>
                                <a href="re_registration.php" class="btn btn-success-custom btn-custom d-inline-flex align-items-center gap-2">
                                    <i class="fas fa-clipboard-check"></i>
                                    Lanjutkan Daftar Ulang
                                </a>
                            <?php else: ?>
                                <a href="dashboard.php" class="btn btn-primary-custom btn-custom d-inline-flex align-items-center gap-2">
                                    <i class="fas fa-home"></i>
                                    Kembali ke Dashboard
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-primary-custom btn-custom d-inline-flex align-items-center gap-2">
                                <i class="fas fa-home"></i>
                                Kembali ke Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>