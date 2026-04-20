<?php
    require_once '../config/config.php';
    checkUserLogin();

    // Ambil user_id dari session
    $user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;

    if ($user_id == 0) {
        header('Location: ../login.php');
        exit();
    }

    // =============================================
    // FUNGSI AUTO UPDATE STATUS BERDASARKAN NILAI TEST
    // =============================================
    function autoUpdateStatusByNilai($conn, $user_id) {
        // Ambil data user terbaru
        $sql = "SELECT nilai_test, status_pendaftaran FROM user WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) return false;
        
        $nilai_test = ($user['nilai_test'] !== null && $user['nilai_test'] !== '') 
            ? (float)$user['nilai_test'] 
            : null;
        $current_status = $user['status_pendaftaran'];
        $passing_grade = 60;
        
        // Tentukan status baru berdasarkan nilai
        $new_status = null;
        if ($nilai_test !== null && $nilai_test > 0 && $current_status === 'test') {
            $new_status = ($nilai_test >= $passing_grade) ? 'lulus' : 'tidak_lulus';
        }
        
        // Update jika perlu
        if ($new_status && $new_status !== $current_status) {
            $update = $conn->prepare("UPDATE user SET status_pendaftaran = ? WHERE id_user = ?");
            $update->bind_param("si", $new_status, $user_id);
            return $update->execute();
        }
        
        return false;
    }

    // Jalankan auto update status
    autoUpdateStatusByNilai($conn, $user_id);

    // Get user data (ambil ulang setelah auto update)
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

    // Hitung progress berdasarkan status
    $progress = 0;
    $status = $user['status_pendaftaran'] ?? 'registrasi';
    switch ($status) {
        case 'registrasi':   $progress = 25;  break;
        case 'test':         $progress = 50;  break;
        case 'lulus':        $progress = 75;  break;
        case 'tidak_lulus':  $progress = 50;  break;
        case 'daftar_ulang': $progress = 90;  break;
        case 'selesai':      $progress = 100; break;
        default:             $progress = 0;
    }

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

    // Ambil nilai_test dengan null-safe check
    $nilai_test = (isset($user['nilai_test']) && $user['nilai_test'] !== null && $user['nilai_test'] !== '')
        ? (float)$user['nilai_test']
        : null;

    // Helper function untuk class step progress
    function getStepClass(string $currentStatus, array $completedWhen, string $activeWhen): string {
        if (in_array($currentStatus, $completedWhen)) {
            return 'step-completed';
        } elseif ($currentStatus === $activeWhen) {
            return 'step-active';
        } else {
            return 'step-pending';
        }
    }

    // Step 1 - Registrasi
    $step1_class = getStepClass(
        $status,
        ['test', 'lulus', 'tidak_lulus', 'daftar_ulang', 'selesai'],
        'registrasi'
    );

    // Step 2 - Test Online
    $step2_class = getStepClass(
        $status,
        ['lulus', 'tidak_lulus', 'daftar_ulang', 'selesai'],
        'test'
    );

    // Step 3 - Kelulusan
    $step3_class = getStepClass(
        $status,
        ['daftar_ulang', 'selesai'],
        'lulus'
    );
    // Override: tidak_lulus juga dianggap step-active di step kelulusan
    if ($status === 'tidak_lulus') {
        $step3_class = 'step-active';
    }

    // Step 4 - Daftar Ulang
    $step4_class = getStepClass(
        $status,
        ['selesai'],
        'daftar_ulang'
    );

    // Status icon mapping
    $status_icon = [
        'registrasi'  => '📝',
        'test'        => '✍️',
        'lulus'       => '🎉',
        'tidak_lulus' => '😔',
        'daftar_ulang'=> '📋',
        'selesai'     => '✅'
    ];

    $status_class = [
        'registrasi'  => 'warning',
        'test'        => 'info',
        'lulus'       => 'success',
        'tidak_lulus' => 'danger',
        'daftar_ulang'=> 'primary',
        'selesai'     => 'dark'
    ];
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - PMB Universitas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
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

            * { margin: 0; padding: 0; box-sizing: border-box; }

            body {
                background: linear-gradient(135deg, #f5f7fa 0%, #f1f5f9 100%);
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                min-height: 100vh;
            }

            /* ===== Sidebar ===== */
            .sidebar {
                height: 100vh;
                position: fixed;
                width: 280px;
                background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
                color: #fff;
                box-shadow: 8px 0 25px rgba(0,0,0,0.1);
                z-index: 1000;
                transition: all 0.3s ease;
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

            .sidebar-header small { font-size: 0.75rem; opacity: 0.7; }

            .user-info {
                padding: 25px;
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }

            .user-info i {
                font-size: 3.5rem;
                margin-bottom: 10px;
                background: linear-gradient(135deg, #fff, #c7d2fe);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .user-info h5 { font-weight: 600; margin-bottom: 5px; }
            .user-info small { opacity: 0.7; font-size: 0.75rem; }

            .sidebar .nav-link {
                color: rgba(255,255,255,0.8);
                padding: 12px 24px;
                margin: 5px 15px;
                border-radius: 12px;
                transition: all 0.3s ease;
                font-weight: 500;
            }

            .sidebar .nav-link i { width: 24px; margin-right: 12px; }

            .sidebar .nav-link:hover,
            .sidebar .nav-link.active {
                background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
                color: #fff;
                transform: translateX(5px);
            }

            /* ===== Main Content ===== */
            .main-content { margin-left: 280px; padding: 35px; min-height: 100vh; }

            /* ===== Welcome Card ===== */
            .welcome-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 28px;
                box-shadow: 0 20px 40px rgba(102,126,234,0.3);
                position: relative;
                overflow: hidden;
                margin-bottom: 35px;
            }

            .welcome-card::before {
                content: '';
                position: absolute;
                top: -50%; right: -20%;
                width: 300px; height: 300px;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            }

            .welcome-card .card-title { font-weight: 800; font-size: 1.8rem; }

            .badge-status {
                padding: 8px 20px;
                border-radius: 30px;
                font-weight: 600;
                font-size: 0.85rem;
                background: rgba(255,255,255,0.2);
                backdrop-filter: blur(10px);
            }

            /* ===== Cards ===== */
            .card { border: none; border-radius: 24px; overflow: hidden; transition: all 0.3s ease; }

            .card-header {
                background: white;
                border-bottom: 2px solid var(--gray-light);
                padding: 20px 25px;
            }

            .card-header h5 { font-weight: 700; color: var(--dark); }

            .info-card {
                background: #ffffff;
                border-radius: 24px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                position: relative;
                overflow: hidden;
            }

            .info-card::before {
                content: '';
                position: absolute;
                bottom: 0; left: 0; right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary), var(--secondary));
                transform: scaleX(0);
                transition: transform 0.3s ease;
            }

            .info-card:hover::before { transform: scaleX(1); }

            .info-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            }

            .info-card .card-body { padding: 30px; }

            /* ===== Progress Steps ===== */
            .progress-step {
                background: linear-gradient(135deg, #f8fafc, #ffffff);
                padding: 20px;
                border-radius: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
                text-align: center;
            }

            .progress-step:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }

            .step-number {
                width: 55px; height: 55px;
                border-radius: 50%;
                font-weight: 800; font-size: 1.2rem;
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 15px auto;
                transition: all 0.3s ease;
            }

            .step-completed {
                background: linear-gradient(135deg, var(--success), #2ecc71);
                color: white;
                box-shadow: 0 5px 15px rgba(34,197,94,0.3);
            }

            .step-active {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: white;
                box-shadow: 0 5px 15px rgba(79,70,229,0.3);
                animation: pulse 2s infinite;
            }

            .step-pending {
                background: linear-gradient(135deg, #94a3b8, #cbd5e1);
                color: white;
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }

            /* ===== Progress Bar ===== */
            .progress {
                height: 12px; border-radius: 10px;
                background: var(--gray-light); overflow: hidden;
            }

            .progress-bar {
                background: linear-gradient(90deg, var(--primary), var(--secondary));
                border-radius: 10px;
                transition: width 1.2s ease;
            }

            /* ===== Buttons ===== */
            .btn {
                border-radius: 14px; font-weight: 600;
                padding: 12px 28px;
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                position: relative; overflow: hidden;
            }

            .btn::before {
                content: '';
                position: absolute;
                top: 50%; left: 50%;
                width: 0; height: 0;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                transform: translate(-50%, -50%);
                transition: width 0.6s, height 0.6s;
            }

            .btn:hover::before { width: 300px; height: 300px; }

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

            .btn-success {
                background: linear-gradient(135deg, var(--success), var(--success-dark));
                border: none;
                box-shadow: 0 5px 15px rgba(34,197,94,0.3);
            }

            .btn-success:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(34,197,94,0.4);
            }

            .btn-outline-primary {
                border: 2px solid var(--primary);
                color: var(--primary);
            }

            .btn-outline-primary:hover {
                background: var(--primary); color: white;
                transform: translateY(-2px);
            }

            /* ===== Quick Action ===== */
            .quick-action-btn {
                text-align: center; padding: 20px;
                border-radius: 20px;
                transition: all 0.3s ease;
                background: linear-gradient(135deg, #f8fafc, #ffffff);
                border: 1px solid var(--gray-light);
            }

            .quick-action-btn:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            }

            .quick-action-btn i { font-size: 2.5rem; margin-bottom: 10px; }

            /* ===== Animation ===== */
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(30px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            .animate-fadeInUp { animation: fadeInUp 0.6s ease forwards; }

            /* ===== Responsive ===== */
            @media (max-width: 768px) {
                .sidebar { width: 80px; }
                .sidebar .nav-link span,
                .sidebar-header small,
                .user-info h5,
                .user-info small { display: none; }
                .user-info i { font-size: 2rem; }
                .sidebar .nav-link i { margin-right: 0; }
                .main-content { margin-left: 80px; padding: 20px; }
                .welcome-card .card-title { font-size: 1.2rem; }
                .progress-step { margin-bottom: 15px; }
            }
        </style>
    </head>
    <body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <div class="sidebar-header text-center">
            <h4 class="mb-0">🎓 PMB</h4>
            <small>Universitas</small>
        </div>

        <div class="user-info text-center">
            <i class="fas fa-user-circle"></i>
            <h5 class="mb-1"><?php echo htmlspecialchars($user['nama_lengkap'] ?? 'User'); ?></h5>
            <small><?php echo htmlspecialchars($user['nomor_test'] ?? '-'); ?></small>
        </div>

        <div class="flex-grow-1">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-home me-2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <?php if (in_array($status, ['registrasi']) || ($status == 'test' && ($nilai_test === null || $nilai_test == 0))): ?>
                <li class="nav-item">
                    <a href="take_test.php" class="nav-link">
                        <i class="fas fa-file-alt me-2"></i> <span>Test Online</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($status, ['lulus', 'tidak_lulus', 'daftar_ulang', 'selesai'])): ?>
                <li class="nav-item">
                    <a href="hasil.php" class="nav-link">
                        <i class="fas fa-chart-line me-2"></i> <span>Hasil Test</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($status == 'lulus'): ?>
                <li class="nav-item">
                    <a href="re_registration.php" class="nav-link">
                        <i class="fas fa-clipboard-check me-2"></i> <span>Daftar Ulang</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user me-2"></i> <span>Profil Saya</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Welcome Card -->
        <div class="card welcome-card animate-fadeInUp">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="card-title text-white">
                            Selamat Datang, <?php echo htmlspecialchars($user['nama_lengkap'] ?? 'User'); ?>! 👋
                        </h2>
                        <p class="text-white mb-2">
                            Status pendaftaran Anda:
                            <span class="badge-status ms-2">
                                <?php
                                    $icon = $status_icon[$status] ?? '📌';
                                    $label = ucwords(str_replace('_', ' ', $status));
                                    echo htmlspecialchars($icon . ' ' . $label);
                                ?>
                            </span>
                        </p>
                        <p class="text-white mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Terdaftar sejak: <?php echo date('d F Y', strtotime($user['created_at'] ?? 'now')); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-user-graduate fa-5x opacity-25 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php if (function_exists('displayMessage')) displayMessage(); ?>

        <!-- PROGRESS STEPS -->
        <div class="card mb-4 animate-fadeInUp" style="animation-delay: 0.1s;">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-road me-2 text-primary"></i>
                    Proses Pendaftaran
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Step 1: Registrasi -->
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="progress-step">
                            <div class="step-number <?php echo $step1_class; ?>">
                                <?php echo ($step1_class === 'step-completed') ? '<i class="fas fa-check"></i>' : '1'; ?>
                            </div>
                            <h6 class="mb-0 fw-bold">Registrasi</h6>
                            <small class="text-muted">Pendaftaran awal</small>
                        </div>
                    </div>

                    <!-- Step 2: Test Online -->
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="progress-step">
                            <div class="step-number <?php echo $step2_class; ?>">
                                <?php echo ($step2_class === 'step-completed') ? '<i class="fas fa-check"></i>' : '2'; ?>
                            </div>
                            <h6 class="mb-0 fw-bold">Test Online</h6>
                            <small class="text-muted">Ujian seleksi</small>
                        </div>
                    </div>

                    <!-- Step 3: Kelulusan -->
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="progress-step">
                            <div class="step-number <?php echo $step3_class; ?>">
                                <?php echo ($step3_class === 'step-completed') ? '<i class="fas fa-check"></i>' : '3'; ?>
                            </div>
                            <h6 class="mb-0 fw-bold">Kelulusan</h6>
                            <small class="text-muted">Hasil test</small>
                        </div>
                    </div>

                    <!-- Step 4: Daftar Ulang -->
                    <div class="col-md-3">
                        <div class="progress-step">
                            <div class="step-number <?php echo $step4_class; ?>">
                                <?php echo ($step4_class === 'step-completed') ? '<i class="fas fa-check"></i>' : '4'; ?>
                            </div>
                            <h6 class="mb-0 fw-bold">Daftar Ulang</h6>
                            <small class="text-muted">Registrasi akhir</small>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress mt-4">
                    <div class="progress-bar" role="progressbar"
                        style="width: 0%"
                        data-target="<?php echo $progress; ?>"
                        aria-valuenow="<?php echo $progress; ?>"
                        aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        <i class="fas fa-chart-line me-1"></i>
                        Proses: <strong><?php echo $progress; ?>%</strong> selesai
                    </small>
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="row">
            <!-- Informasi Pribadi -->
            <div class="col-md-4 mb-4 animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="card info-card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex p-3">
                                <i class="fas fa-id-card fa-3x text-primary"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Informasi Pribadi</h5>
                        <p class="text-muted mb-3">Data diri dan kontak Anda</p>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-2"></i>Lihat Profil
                        </a>
                    </div>
                </div>
            </div>

            <!-- HASIL TEST CARD -->
            <div class="col-md-4 mb-4 animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="card info-card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex p-3">
                                <i class="fas fa-file-alt fa-3x text-success"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Hasil Test</h5>

                        <div class="mb-3">
                            <?php if ($nilai_test !== null && $nilai_test > 0): ?>
                                <!-- Nilai sudah ada -->
                                <p class="text-muted mb-1">Nilai Anda:</p>
                                <span class="fs-2 fw-bold text-primary"><?php echo number_format($nilai_test, 2); ?></span>
                                <br>
                                <?php
                                    $passing_grade = 60;
                                    if ($nilai_test >= $passing_grade): ?>
                                    <span class="badge bg-success mt-1">
                                        <i class="fas fa-check-circle me-1"></i>Lulus
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger mt-1">
                                        <i class="fas fa-times-circle me-1"></i>Tidak Lulus
                                    </span>
                                <?php endif; ?>

                            <?php elseif (in_array($status, ['registrasi', 'test'])): ?>
                                <span class="badge bg-secondary fs-6 px-3 py-2">
                                    <i class="fas fa-clock me-1"></i>Belum mengikuti test
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                    <i class="fas fa-hourglass-half me-1"></i>Menunggu penilaian
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Tombol aksi -->
                        <?php if ($nilai_test !== null && $nilai_test > 0): ?>
                            <a href="hasil.php" class="btn btn-success">
                                <i class="fas fa-chart-line me-2"></i>Lihat Detail
                            </a>
                        <?php elseif (in_array($status, ['registrasi']) || ($status == 'test' && ($nilai_test === null || $nilai_test == 0))): ?>
                            <a href="take_test.php" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i>Mulai Test
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-ban me-2"></i>Tidak Tersedia
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Status Pendaftaran -->
            <div class="col-md-4 mb-4 animate-fadeInUp" style="animation-delay: 0.4s;">
                <div class="card info-card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex p-3">
                                <i class="fas fa-graduation-cap fa-3x text-warning"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold">Status Pendaftaran</h5>
                        <div class="mb-3">
                            <span class="badge bg-<?php echo $status_class[$status] ?? 'secondary'; ?> fs-6 px-3 py-2">
                                <?php echo ($status_icon[$status] ?? '📌') . ' ' . ucwords(str_replace('_', ' ', $status)); ?>
                            </span>
                        </div>

                        <?php if ($status === 'lulus' && !$daftar_ulang): ?>
                            <a href="re_registration.php" class="btn btn-warning mt-2">
                                <i class="fas fa-clipboard-list me-2"></i>Daftar Ulang Sekarang
                            </a>
                        <?php elseif ($status === 'tidak_lulus'): ?>
                            <p class="text-danger small mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Mohon maaf, Anda tidak memenuhi syarat kelulusan.
                            </p>
                        <?php endif; ?>

                        <?php if ($daftar_ulang && isset($daftar_ulang['status_pembayaran'])): ?>
                            <span class="badge bg-<?php echo $daftar_ulang['status_pembayaran'] == 'lunas' ? 'success' : 'warning'; ?> px-3 py-2 d-block mt-2">
                                <i class="fas <?php echo $daftar_ulang['status_pembayaran'] == 'lunas' ? 'fa-check-circle' : 'fa-clock'; ?> me-1"></i>
                                Daftar Ulang: <?php echo ucfirst(htmlspecialchars($daftar_ulang['status_pembayaran'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aksi Cepat -->
        <div class="card animate-fadeInUp" style="animation-delay: 0.5s;">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2 text-warning"></i>
                    Aksi Cepat
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (in_array($status, ['registrasi']) || ($status == 'test' && ($nilai_test === null || $nilai_test == 0))): ?>
                    <div class="col-md-3 mb-3">
                        <a href="take_test.php" class="quick-action-btn d-block text-decoration-none text-dark">
                            <i class="fas fa-file-alt text-primary d-block"></i>
                            <div class="fw-bold mt-2">Test Online</div>
                            <small class="text-muted">Mulai ujian</small>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($status, ['lulus', 'tidak_lulus', 'daftar_ulang', 'selesai'])): ?>
                    <div class="col-md-3 mb-3">
                        <a href="hasil.php" class="quick-action-btn d-block text-decoration-none text-dark">
                            <i class="fas fa-chart-bar text-success d-block"></i>
                            <div class="fw-bold mt-2">Hasil Test</div>
                            <small class="text-muted">Lihat nilai</small>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($status === 'lulus' && !$daftar_ulang): ?>
                    <div class="col-md-3 mb-3">
                        <a href="re_registration.php" class="quick-action-btn d-block text-decoration-none text-dark">
                            <i class="fas fa-clipboard-check text-success d-block"></i>
                            <div class="fw-bold mt-2">Daftar Ulang</div>
                            <small class="text-muted">Lengkapi registrasi</small>
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-3 mb-3">
                        <a href="profile.php" class="quick-action-btn d-block text-decoration-none text-dark">
                            <i class="fas fa-user-edit text-info d-block"></i>
                            <div class="fw-bold mt-2">Edit Profil</div>
                            <small class="text-muted">Update data diri</small>
                        </a>
                    </div>

                    <div class="col-md-3 mb-3">
                        <a href="logout.php" class="quick-action-btn d-block text-decoration-none text-dark">
                            <i class="fas fa-sign-out-alt text-danger d-block"></i>
                            <div class="fw-bold mt-2">Logout</div>
                            <small class="text-muted">Keluar sistem</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animasi progress bar on load
        document.addEventListener('DOMContentLoaded', function () {
            const bar = document.querySelector('.progress-bar');
            if (bar) {
                const target = bar.getAttribute('data-target') || '0';
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = target + '%';
                }, 200);
            }
        });
    </script>
    </body>
    </html>