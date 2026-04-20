<?php
require_once '../config/config.php';
checkUserLogin();

$user_id = $_SESSION['user_id'];

// Get user data from database
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get quiz statistics
$quiz_sql = "SELECT 
    COUNT(*) as total_quiz,
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END) as jawaban_benar,
    SUM(CASE WHEN ht.jawaban_user != st.jawaban_benar AND ht.jawaban_user IS NOT NULL THEN 1 ELSE 0 END) as jawaban_salah
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ?";
$quiz_stmt = $conn->prepare($quiz_sql);
$quiz_stmt->bind_param("i", $user_id);
$quiz_stmt->execute();
$quiz_stats = $quiz_stmt->get_result()->fetch_assoc();

$total_quiz = $quiz_stats['total_quiz'] ?? 0;
$jawaban_benar = $quiz_stats['jawaban_benar'] ?? 0;
$jawaban_salah = $quiz_stats['jawaban_salah'] ?? 0;
$rata_rata = $total_quiz > 0 ? round(($jawaban_benar / ($jawaban_benar + $jawaban_salah)) * 100, 1) : 0;

// Get user's test result
$nilai_test = $user['nilai_test'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Semua CSS sama seperti sebelumnya */
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #6366f1;
            --success: #22c55e;
            --success-dark: #16a34a;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #f1f5f9 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        /* ===== Sidebar Enhanced ===== */
        .sidebar {
            height: 100vh;
            position: fixed;
            width: 280px;
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            color: #fff;
            box-shadow: 8px 0 25px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h4 {
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 24px;
            margin: 5px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 24px;
            margin-right: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
            color: #fff;
            transform: translateX(5px);
        }

        /* Button Kembali Enhanced */
        .btn-kembali {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 12px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            width: calc(100% - 30px);
            margin: 15px;
        }

        .btn-kembali:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(-5px);
            color: white;
        }

        /* ===== Main Content ===== */
        .main-content {
            margin-left: 280px;
            padding: 35px;
            min-height: 100vh;
        }

        /* ===== Profile Header Enhanced ===== */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 28px;
            padding: 35px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        /* ===== Stat Cards Enhanced ===== */
        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* ===== Cards Enhanced ===== */
        .card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        /* ===== Form Enhanced ===== */
        .form-control, .form-select {
            border-radius: 14px;
            border: 2px solid var(--gray-light);
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        /* ===== Buttons Enhanced ===== */
        .btn {
            border-radius: 14px;
            font-weight: 600;
            padding: 10px 24px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.4);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .btn-light {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: none;
            color: white;
        }

        .btn-light:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        /* ===== Activity Item Enhanced ===== */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid var(--gray-light);
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .activity-item:hover {
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            transform: translateX(5px);
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .activity-correct {
            background: linear-gradient(135deg, rgba(34,197,94,0.1), rgba(34,197,94,0.05));
            color: var(--success);
        }

        .activity-quiz {
            background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(79,70,229,0.05));
            color: var(--primary);
        }

        /* ===== Badge Enhanced ===== */
        .badge {
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning), #f39c12);
        }

        /* ===== Achievement Items Enhanced ===== */
        .achievement-item {
            text-align: center;
            padding: 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
        }

        .achievement-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .achievement-item i {
            transition: all 0.3s ease;
        }

        .achievement-item:hover i {
            transform: scale(1.1);
        }

        /* ===== Animation ===== */
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

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        /* ===== Notification Toast ===== */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border-radius: 16px;
            padding: 15px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid var(--success);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            .sidebar .nav-link span {
                display: none;
            }
            .sidebar .nav-link i {
                margin-right: 0;
            }
            .btn-kembali span {
                display: none;
            }
            .btn-kembali i {
                margin-right: 0;
            }
            .main-content {
                margin-left: 80px;
                padding: 20px;
            }
            .profile-header {
                padding: 25px;
            }
            .profile-avatar {
                width: 70px;
                height: 70px;
            }
            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header text-center">
        <h4>🎓 PMB</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-link active" href="#">
            <i class="fas fa-user me-2"></i>
            <span>Profil</span>
        </a>
        <a class="nav-link" href="statistik.php">
            <i class="fas fa-chart-line me-2"></i>
            <span>Statistik</span>
        </a>
    </nav>
    
    <!-- Button Kembali Enhanced -->
    <div class="mt-auto">
        <a href="dashboard.php" class="btn btn-kembali">
            <i class="fas fa-arrow-left me-2"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Header Profil -->
    <div class="profile-header animate-fadeInUp">
        <div class="row align-items-center">
            <div class="col-auto">
                <img src="https://ui-avatars.com/api/?background=4f46e5&color=fff&bold=true&size=100&name=<?php echo urlencode($user['nama_lengkap']); ?>" 
                     alt="Avatar" class="profile-avatar" id="avatar">
            </div>
            <div class="col">
                <h3 class="fw-bold mb-1" id="namaPengguna"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h3>
                <p class="mb-1">
                    <i class="fas fa-envelope me-2"></i>
                    <span id="emailPengguna"><?php echo htmlspecialchars($user['email']); ?></span>
                </p>
                <p class="mb-1">
                    <i class="fas fa-id-card me-2"></i>
                    <span>Nomor Test: <?php echo htmlspecialchars($user['nomor_test'] ?? 'Belum ada'); ?></span>
                </p>
                <p class="mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Bergabung: <span id="tanggalBergabung"><?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                </p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" onclick="editProfil()">
                    <i class="fas fa-edit me-2"></i>Edit Profil
                </button>
            </div>
        </div>
    </div>

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3 animate-fadeInUp" style="animation-delay: 0.1s;">
            <div class="stat-card">
                <div class="stat-value text-primary" id="totalQuiz"><?php echo $total_quiz; ?></div>
                <div class="stat-label">Total Quiz</div>
                <i class="fas fa-book-open position-absolute bottom-0 end-0 p-3 opacity-25"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3 animate-fadeInUp" style="animation-delay: 0.2s;">
            <div class="stat-card">
                <div class="stat-value text-success" id="benar"><?php echo $jawaban_benar; ?></div>
                <div class="stat-label">Jawaban Benar</div>
                <i class="fas fa-check-circle position-absolute bottom-0 end-0 p-3 opacity-25"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3 animate-fadeInUp" style="animation-delay: 0.3s;">
            <div class="stat-card">
                <div class="stat-value text-danger" id="salah"><?php echo $jawaban_salah; ?></div>
                <div class="stat-label">Jawaban Salah</div>
                <i class="fas fa-times-circle position-absolute bottom-0 end-0 p-3 opacity-25"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3 animate-fadeInUp" style="animation-delay: 0.4s;">
            <div class="stat-card">
                <div class="stat-value text-warning" id="rataRata"><?php echo $rata_rata; ?>%</div>
                <div class="stat-label">Rata-rata Nilai</div>
                <i class="fas fa-chart-line position-absolute bottom-0 end-0 p-3 opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Informasi Pribadi & Aktivitas -->
    <div class="row">
        <div class="col-md-6 mb-4 animate-fadeInUp" style="animation-delay: 0.5s;">
            <div class="card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-user-circle me-2 text-primary"></i>
                    Informasi Pribadi
                </h5>
                <div id="infoTampil">
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">Nama Lengkap</small>
                        <strong class="fs-5" id="tampilNama"><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong>
                    </div>
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">Email</small>
                        <strong class="fs-5" id="tampilEmail"><?php echo htmlspecialchars($user['email']); ?></strong>
                    </div>
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">Username</small>
                        <strong class="fs-5" id="tampilUsername"><?php echo htmlspecialchars($user['username']); ?></strong>
                    </div>
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">No. Telepon</small>
                        <strong class="fs-5" id="tampilTelepon"><?php echo htmlspecialchars($user['no_hp'] ?? '-'); ?></strong>
                    </div>
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">Program Studi</small>
                        <strong class="fs-5"><?php echo htmlspecialchars($user['program_studi'] ?? '-'); ?></strong>
                    </div>
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">Nomor Test</small>
                        <strong class="fs-5"><?php echo htmlspecialchars($user['nomor_test'] ?? 'Belum ada'); ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Status Pendaftaran</small>
                        <strong class="fs-5">
                            <span class="badge bg-<?php 
                                echo match($user['status_pendaftaran']) {
                                    'registrasi' => 'warning',
                                    'test' => 'info',
                                    'lulus' => 'success',
                                    'tidak_lulus' => 'danger',
                                    'daftar_ulang' => 'primary',
                                    'selesai' => 'dark',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucwords(str_replace('_', ' ', $user['status_pendaftaran'])); ?>
                            </span>
                        </strong>
                    </div>
                </div>
                
                <!-- Form Edit (Hidden) -->
                <div id="formEdit" style="display: none;">
                    <form action="update_profile.php" method="POST" id="updateProfileForm">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" id="editNama" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="editUsername" value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Telepon</label>
                            <input type="tel" class="form-control" name="no_hp" id="editTelepon" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="3" id="editAlamat"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-primary me-2" onclick="batalEdit()">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4 animate-fadeInUp" style="animation-delay: 0.6s;">
            <div class="card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-history me-2 text-primary"></i>
                    Aktivitas Terbaru
                </h5>
                <div class="activity-list">
                    <?php
                    // Get recent activities
                    $activity_sql = "SELECT 
                        ht.waktu_jawab,
                        st.pertanyaan,
                        CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 'benar' ELSE 'salah' END as status
                        FROM hasil_test ht
                        JOIN soal_test st ON ht.id_soal = st.id_soal
                        WHERE ht.id_user = ?
                        ORDER BY ht.waktu_jawab DESC
                        LIMIT 5";
                    $activity_stmt = $conn->prepare($activity_sql);
                    $activity_stmt->bind_param("i", $user_id);
                    $activity_stmt->execute();
                    $activities = $activity_stmt->get_result();
                    
                    if ($activities->num_rows > 0):
                        while ($activity = $activities->fetch_assoc()):
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $activity['status'] == 'benar' ? 'activity-correct' : 'activity-quiz'; ?>">
                            <i class="fas <?php echo $activity['status'] == 'benar' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?php echo substr(htmlspecialchars($activity['pertanyaan']), 0, 50); ?>...</div>
                            <div class="small text-muted"><?php echo date('d M Y, H:i', strtotime($activity['waktu_jawab'])); ?></div>
                        </div>
                        <span class="badge <?php echo $activity['status'] == 'benar' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $activity['status'] == 'benar' ? 'Benar' : 'Salah'; ?>
                        </span>
                    </div>
                    <?php 
                        endwhile;
                    else:
                        // Check if user can take test
                        $nilai_test = isset($user['nilai_test']) && $user['nilai_test'] !== null && $user['nilai_test'] !== '' ? (float)$user['nilai_test'] : null;
                        $can_take_test = ($user['status_pendaftaran'] == 'registrasi') || ($user['status_pendaftaran'] == 'test' && ($nilai_test === null || $nilai_test == 0));
                        
                        if ($can_take_test):
                    ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada aktivitas quiz</p>
                        <a href="take_test.php" class="btn btn-primary btn-sm">Mulai Test Sekarang</a>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">Test sudah selesai dikerjakan</p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pencapaian -->
    <div class="card p-4 animate-fadeInUp" style="animation-delay: 0.7s;">
        <h5 class="fw-bold mb-4">
            <i class="fas fa-trophy me-2 text-warning"></i>
            Pencapaian
        </h5>
        <div class="row">
            <?php
            // Calculate achievements based on actual data
            $perfect_score = 0;
            $quiz_streak = 0;
            $total_quizzes = $total_quiz;
            
            // Get perfect scores
            $perfect_sql = "SELECT COUNT(*) as perfect FROM hasil_test ht 
                            JOIN soal_test st ON ht.id_soal = st.id_soal 
                            WHERE ht.id_user = ? AND ht.jawaban_user = st.jawaban_benar";
            $perfect_stmt = $conn->prepare($perfect_sql);
            $perfect_stmt->bind_param("i", $user_id);
            $perfect_stmt->execute();
            $perfect_result = $perfect_stmt->get_result()->fetch_assoc();
            $perfect_score = $perfect_result['perfect'] ?? 0;
            ?>
            <div class="col-md-3 col-6 mb-3">
                <div class="achievement-item">
                    <i class="fas fa-star fa-3x text-warning mb-2"></i>
                    <div class="fw-semibold">Nilai Sempurna</div>
                    <small class="text-muted"><?php echo $perfect_score; ?>x diraih</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="achievement-item">
                    <i class="fas fa-bolt fa-3x text-primary mb-2"></i>
                    <div class="fw-semibold">Quiz Cepat</div>
                    <small class="text-muted">Mulai test sekarang</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="achievement-item">
                    <i class="fas fa-fire fa-3x text-danger mb-2"></i>
                    <div class="fw-semibold">Semangat Belajar</div>
                    <small class="text-muted">Terus belajar!</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="achievement-item">
                    <i class="fas fa-crown fa-3x text-warning mb-2"></i>
                    <div class="fw-semibold">Total Quiz</div>
                    <small class="text-muted"><?php echo $total_quizzes; ?> quiz selesai</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showNotification(message, type = 'success') {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();
        
        const notification = document.createElement('div');
        notification.className = 'toast-notification';
        notification.style.borderLeftColor = type === 'success' ? '#22c55e' : '#ef4444';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} fa-lg me-2" style="color: ${type === 'success' ? '#22c55e' : '#ef4444'}"></i>
                <div>${message}</div>
            </div>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    function editProfil() {
        document.getElementById('infoTampil').style.display = 'none';
        document.getElementById('formEdit').style.display = 'block';
    }
    
    function batalEdit() {
        document.getElementById('infoTampil').style.display = 'block';
        document.getElementById('formEdit').style.display = 'none';
    }
    
    // Handle form submission with AJAX
    document.getElementById('updateProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update displayed values
                document.getElementById('tampilNama').innerText = formData.get('nama_lengkap');
                document.getElementById('tampilEmail').innerText = formData.get('email');
                document.getElementById('tampilUsername').innerText = formData.get('username');
                document.getElementById('tampilTelepon').innerText = formData.get('no_hp');
                document.getElementById('namaPengguna').innerText = formData.get('nama_lengkap');
                document.getElementById('emailPengguna').innerText = formData.get('email');
                
                // Update avatar
                const avatar = document.getElementById('avatar');
                const namaUntukAvatar = formData.get('nama_lengkap').replace(/ /g, '+');
                avatar.src = `https://ui-avatars.com/api/?background=4f46e5&color=fff&bold=true&size=100&name=${namaUntukAvatar}`;
                
                batalEdit();
                showNotification('Profil berhasil diperbarui!', 'success');
            } else {
                showNotification(data.message || 'Gagal memperbarui profil', 'error');
            }
        })
        .catch(error => {
            showNotification('Terjadi kesalahan', 'error');
        });
    });
    
    // Add slideOutRight animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>