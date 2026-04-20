<?php
require_once '../config.php';
checkAdminLogin();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'delete':
            $sql = "DELETE FROM soal_test WHERE id_soal = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Soal berhasil dihapus';
                $_SESSION['message_type'] = 'success';
            }
            break;
            
        case 'toggle_active':
            $sql = "UPDATE soal_test SET aktif = IF(aktif = 'Y', 'N', 'Y') WHERE id_soal = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
    }
    header('Location: manage_questions.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_soal = $_POST['id_soal'] ?? 0;
    $pertanyaan = sanitize($_POST['pertanyaan']);
    $pilihan_a = sanitize($_POST['pilihan_a']);
    $pilihan_b = sanitize($_POST['pilihan_b']);
    $pilihan_c = sanitize($_POST['pilihan_c']);
    $pilihan_d = sanitize($_POST['pilihan_d']);
    $jawaban_benar = sanitize($_POST['jawaban_benar']);
    $kategori = sanitize($_POST['kategori']);
    $tingkat_kesulitan = sanitize($_POST['tingkat_kesulitan']);
    
    if ($id_soal > 0) {
        $sql = "UPDATE soal_test SET 
                pertanyaan = ?, pilihan_a = ?, pilihan_b = ?, pilihan_c = ?, pilihan_d = ?,
                jawaban_benar = ?, kategori = ?, tingkat_kesulitan = ? WHERE id_soal = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, 
                         $pilihan_d, $jawaban_benar, $kategori, $tingkat_kesulitan, $id_soal);
    } else {
        $sql = "INSERT INTO soal_test (pertanyaan, pilihan_a, pilihan_b, pilihan_c, 
                pilihan_d, jawaban_benar, kategori, tingkat_kesulitan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, 
                         $pilihan_d, $jawaban_benar, $kategori, $tingkat_kesulitan);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = $id_soal > 0 ? 'Soal berhasil diupdate' : 'Soal berhasil ditambahkan';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: manage_questions.php');
    exit();
}

// Get questions
$result = $conn->query("SELECT * FROM soal_test ORDER BY created_at DESC");

// Get statistics
$total_questions = $conn->query("SELECT COUNT(*) as count FROM soal_test")->fetch_assoc()['count'];
$active_questions = $conn->query("SELECT COUNT(*) as count FROM soal_test WHERE aktif = 'Y'")->fetch_assoc()['count'];
$inactive_questions = $total_questions - $active_questions;
$used_questions = $conn->query("SELECT COUNT(DISTINCT id_soal) as count FROM hasil_test")->fetch_assoc()['count'] ?? 0;
$new_today = $conn->query("SELECT COUNT(*) as count FROM soal_test WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

// Get total admin count
$admin_count = $conn->query("SELECT COUNT(*) as total FROM admin")->fetch_assoc()['total'] ?? 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal Test - PMB Universitas</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f1f5f9 100%);
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-fade-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-left {
            animation: fadeInLeft 0.6s ease-out forwards;
            opacity: 0;
        }

        .stagger-item {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .stagger-item:nth-child(1) { animation-delay: 0.05s; }
        .stagger-item:nth-child(2) { animation-delay: 0.1s; }
        .stagger-item:nth-child(3) { animation-delay: 0.15s; }
        .stagger-item:nth-child(4) { animation-delay: 0.2s; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h4 {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .admin-info {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 2rem;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* ===== STAT CARDS ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 22px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1);
        }

        .stat-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* ===== QUESTION CARDS ===== */
        .question-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            height: 100%;
        }

        .question-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -12px rgba(0,0,0,0.1);
            border-color: #e0e7ff;
        }

        .option-item {
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .option-item:hover {
            background: #f1f5f9;
        }

        .option-letter {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            margin-right: 10px;
        }

        .letter-a { background: #fee2e2; color: #dc2626; }
        .letter-b { background: #dbeafe; color: #2563eb; }
        .letter-c { background: #d1fae5; color: #059669; }
        .letter-d { background: #fef3c7; color: #d97706; }

        .correct-badge {
            background: #10b981;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* ===== BADGES ===== */
        .badge-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: #d1fae5; color: #059669; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .badge-info { background: #dbeafe; color: #2563eb; }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 14px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            border: none;
            border-radius: 14px;
            padding: 10px 24px;
            font-weight: 600;
        }

        /* ===== ALERT ===== */
        .alert-custom {
            border-radius: 20px;
            border: none;
            padding: 16px 24px;
            margin-bottom: 25px;
            animation: fadeInUp 0.5s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        /* ===== MODAL ===== */
        .modal-content-modern {
            border: none;
            border-radius: 24px;
            overflow: hidden;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: #374151;
        }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar .nav-link span,
            .admin-info h5,
            .admin-info small { display: none; }
            .sidebar .nav-link i { margin: 0; }
            .admin-avatar { width: 45px; height: 45px; font-size: 1.2rem; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 15px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header animate-fade-left" style="animation-delay: 0.1s">
            <h4 class="mb-0">🎓 PMB</h4>
            <small>Administrator Panel</small>
        </div>
        
        <div class="admin-info animate-fade-left" style="animation-delay: 0.15s">
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['admin_nama']); ?></h5>
            <small>Administrator</small>
        </div>
        
        <div class="flex-grow-1">
            <ul class="nav flex-column">
                <li class="nav-item stagger-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li class="nav-item stagger-item"><a href="manage_users.php" class="nav-link"><i class="fas fa-user-plus"></i> <span>Kelola Calon</span></a></li>
                <li class="nav-item stagger-item"><a href="manage_questions.php" class="nav-link active"><i class="fas fa-question-circle"></i> <span>Soal Test</span></a></li>
                <li class="nav-item stagger-item"><a href="registered_users.php" class="nav-link"><i class="fas fa-users"></i> <span>User Terdaftar</span></a></li>
                <li class="nav-item stagger-item"><a href="test_results.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Hasil Test</span></a></li>
                <li class="nav-item stagger-item"><a href="re_registration.php" class="nav-link"><i class="fas fa-clipboard-list"></i> <span>Daftar Ulang</span></a></li>
                <li class="nav-item stagger-item"><a href="generate_nim.php" class="nav-link"><i class="fas fa-id-card"></i> <span>Generate NIM</span></a></li>
                <li class="nav-item stagger-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> <span>Laporan</span></a></li>
            </ul>
        </div>
        
        <div class="p-3 border-top animate-fade-left" style="animation-delay: 0.5s">
            <a href="logout.php" class="btn btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div class="animate-fade-up" style="animation-delay: 0.1s">
                <h1 class="display-6 fw-bold mb-0" style="font-size: 1.8rem;">
                    <i class="fas fa-question-circle text-primary me-2"></i>Kelola Soal Test
                </h1>
                <p class="text-muted mt-1 mb-0">Tambah, edit, dan kelola soal ujian seleksi</p>
            </div>
            <button class="btn btn-primary animate-fade-up" style="animation-delay: 0.1s" data-bs-toggle="modal" data-bs-target="#questionModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Tambah Soal Baru
            </button>
        </div>

        <!-- Alert Messages -->
        <?php 
        if(isset($_SESSION['message'])) {
            $type = $_SESSION['message_type'] == 'success' ? 'success' : 'danger';
            echo '<div class="alert alert-custom alert-' . $type . ' alert-dismissible fade show animate-fade-up" role="alert" style="animation-delay: 0.15s">
                    <i class="fas ' . ($type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle') . ' me-2"></i>
                    ' . $_SESSION['message'] . '
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <!-- Statistics Cards -->
        <div class="stat-grid">
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                    <i class="fas fa-database text-white"></i>
                </div>
                <div class="stat-value"><?php echo $total_questions; ?></div>
                <div class="stat-label">Total Soal</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div class="stat-value"><?php echo $active_questions; ?></div>
                <div class="stat-label">Soal Aktif</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-file-alt text-white"></i>
                </div>
                <div class="stat-value"><?php echo $used_questions; ?></div>
                <div class="stat-label">Soal Terpakai</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <i class="fas fa-calendar-day text-white"></i>
                </div>
                <div class="stat-value"><?php echo $new_today; ?></div>
                <div class="stat-label">Soal Baru Hari Ini</div>
            </div>
        </div>

        <!-- Questions List -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.25s">
            <div class="card-header-modern">
                <h5>
                    <i class="fas fa-list text-primary"></i>
                    Daftar Soal Test
                    <span class="badge-modern badge-info ms-2"><?php echo $total_questions; ?> Soal</span>
                </h5>
            </div>
            <div class="p-4">
                <div class="row">
                    <?php 
                    $counter = 1;
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()): 
                        $optionColors = ['a' => 'letter-a', 'b' => 'letter-b', 'c' => 'letter-c', 'd' => 'letter-d'];
                    ?>
                    <div class="col-lg-6 mb-4">
                        <div class="question-card p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <span class="fw-bold text-primary">#<?php echo $counter++; ?></span>
                                    </div>
                                    <div>
                                        <span class="badge-modern <?php echo $row['aktif'] == 'Y' ? 'badge-success' : 'badge-secondary'; ?>">
                                            <i class="fas <?php echo $row['aktif'] == 'Y' ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                            <?php echo $row['aktif'] == 'Y' ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 32px; height: 32px;" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" onclick="editQuestion(<?php echo $row['id_soal']; ?>)">
                                                <i class="fas fa-edit me-2 text-warning"></i>Edit Soal
                                            </button>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="manage_questions.php?action=toggle_active&id=<?php echo $row['id_soal']; ?>">
                                                <?php if ($row['aktif'] == 'Y'): ?>
                                                    <i class="fas fa-toggle-off me-2 text-danger"></i>Nonaktifkan
                                                <?php else: ?>
                                                    <i class="fas fa-toggle-on me-2 text-success"></i>Aktifkan
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" 
                                               href="manage_questions.php?action=delete&id=<?php echo $row['id_soal']; ?>"
                                               onclick="return confirm('⚠️ Apakah Anda yakin ingin menghapus soal ini?')">
                                                <i class="fas fa-trash me-2"></i>Hapus Soal
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="fw-semibold mb-2" style="font-size: 1rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($row['pertanyaan'])); ?></p>
                            </div>
                            
                            <div class="options">
                                <?php foreach (['a', 'b', 'c', 'd'] as $opt): 
                                    $pilihan = $row['pilihan_' . $opt];
                                    $is_correct = ($row['jawaban_benar'] == $opt);
                                ?>
                                <div class="option-item d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="option-letter <?php echo $optionColors[$opt]; ?>">
                                            <?php echo strtoupper($opt); ?>
                                        </span>
                                        <span class="ms-2"><?php echo htmlspecialchars($pilihan); ?></span>
                                    </div>
                                    <?php if ($is_correct): ?>
                                        <span class="correct-badge">
                                            <i class="fas fa-check-circle me-1"></i>Jawaban Benar
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Dibuat: <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i>
                                    <?php echo !empty($row['kategori']) ? $row['kategori'] : 'Umum'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($total_questions == 0): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada soal test</h5>
                            <p class="text-muted">Klik tombol "Tambah Soal Baru" untuk mulai membuat soal</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Question Modal -->
    <div class="modal fade" id="questionModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-content-modern">
                <form method="POST" action="manage_questions.php" id="questionForm">
                    <input type="hidden" name="id_soal" id="id_soal" value="0">
                    <div class="modal-header border-0" style="padding: 25px 30px 0;">
                        <h5 class="modal-title fw-bold" id="modalTitle">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Tambah Soal Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 20px 30px;">
                        <div class="mb-3">
                            <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="pertanyaan" rows="4" required placeholder="Masukkan pertanyaan soal..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pilihan A <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pilihan_a" required placeholder="Pilihan jawaban A">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pilihan B <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pilihan_b" required placeholder="Pilihan jawaban B">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pilihan C <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pilihan_c" required placeholder="Pilihan jawaban C">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pilihan D <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pilihan_d" required placeholder="Pilihan jawaban D">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jawaban Benar <span class="text-danger">*</span></label>
                                <select class="form-select" name="jawaban_benar" required>
                                    <option value="">Pilih Jawaban Benar</option>
                                    <option value="a">Pilihan A</option>
                                    <option value="b">Pilihan B</option>
                                    <option value="c">Pilihan C</option>
                                    <option value="d">Pilihan D</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="kategori">
                                    <option value="Umum">Umum</option>
                                    <option value="Matematika">Matematika</option>
                                    <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                                    <option value="Bahasa Inggris">Bahasa Inggris</option>
                                    <option value="IPA">IPA</option>
                                    <option value="IPS">IPS</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tingkat Kesulitan</label>
                            <select class="form-select" name="tingkat_kesulitan">
                                <option value="mudah">Mudah</option>
                                <option value="sedang">Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 0 30px 25px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('questionForm').reset();
            document.getElementById('id_soal').value = 0;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2 text-primary"></i>Tambah Soal Baru';
        }
        
        function editQuestion(id) {
            fetch(`get_question.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('id_soal').value = data.id_soal;
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2 text-primary"></i>Edit Soal';
                    document.querySelector('[name="pertanyaan"]').value = data.pertanyaan;
                    document.querySelector('[name="pilihan_a"]').value = data.pilihan_a;
                    document.querySelector('[name="pilihan_b"]').value = data.pilihan_b;
                    document.querySelector('[name="pilihan_c"]').value = data.pilihan_c;
                    document.querySelector('[name="pilihan_d"]').value = data.pilihan_d;
                    document.querySelector('[name="jawaban_benar"]').value = data.jawaban_benar;
                    document.querySelector('[name="kategori"]').value = data.kategori || 'Umum';
                    document.querySelector('[name="tingkat_kesulitan"]').value = data.tingkat_kesulitan || 'sedang';
                    
                    new bootstrap.Modal(document.getElementById('questionModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat data soal');
                });
        }

        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>