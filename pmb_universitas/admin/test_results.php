<?php
// =============================================
// SYNC DATA DARI TABEL USER KE TEST_RESULTS
// =============================================
require_once '../config/config.php';
checkAdminLogin();

// Fungsi untuk sync data dari user ke test_results
function syncUserTestResults($conn) {
    // Cek kolom yang tersedia di tabel user
    $columns = [];
    $col_query = $conn->query("SHOW COLUMNS FROM user");
    while ($col = $col_query->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    
    // Bangun query berdasarkan kolom yang ada
    $select_fields = "u.id_user, u.nama_lengkap, u.email, u.no_hp, u.nomor_test, 
                      u.nilai_test, u.status_pendaftaran";
    
    // Tambahkan tgl_test hanya jika kolomnya ada
    if (in_array('tgl_test', $columns)) {
        $select_fields .= ", u.tgl_test";
    }
    
    $sync_query = "SELECT $select_fields 
                   FROM user u 
                   LEFT JOIN test_results tr ON u.id_user = tr.id_user 
                   WHERE tr.id_test IS NULL 
                   AND u.nilai_test IS NOT NULL 
                   AND u.nilai_test > 0";
    
    $sync_result = $conn->query($sync_query);
    
    if (!$sync_result) {
        writeLog("Sync query error: " . $conn->error);
        return 0;
    }
    
    $synced_count = 0;
    
    // Ambil total soal aktif
    $total_soal = 40; // default
    $soal_query = $conn->query("SELECT COUNT(*) as total FROM soal_test WHERE aktif = 'Y'");
    if ($soal_query && $soal_query->num_rows > 0) {
        $total_soal = $soal_query->fetch_assoc()['total'];
    }
    
    while ($row = $sync_result->fetch_assoc()) {
        // Hitung perkiraan jawaban benar berdasarkan nilai
        $jawaban_benar = round(($row['nilai_test'] / 100) * $total_soal);
        
        // Tentukan status test
        $status_test = $row['status_pendaftaran'];
        if ($status_test == 'lulus') {
            $status_test = 'lulus';
        } elseif ($status_test == 'tidak_lulus') {
            $status_test = 'tidak_lulus';
        } else {
            $status_test = ($row['nilai_test'] >= 60) ? 'lulus' : 'tidak_lulus';
        }
        
        // Gunakan tanggal sekarang jika kolom tgl_test tidak ada
        $tgl_test = isset($row['tgl_test']) && !empty($row['tgl_test']) 
                    ? $row['tgl_test'] 
                    : date('Y-m-d H:i:s');
        $durasi = 3600; // default 60 menit
        
        $insert_sync = "INSERT INTO test_results (
            id_user, 
            tanggal_test, 
            total_soal, 
            soal_terjawab, 
            jawaban_benar, 
            nilai, 
            status_test, 
            durasi_test,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt_sync = $conn->prepare($insert_sync);
        $stmt_sync->bind_param("isiiidsi", 
            $row['id_user'],
            $tgl_test,
            $total_soal,
            $total_soal,
            $jawaban_benar,
            $row['nilai_test'],
            $status_test,
            $durasi
        );
        
        if ($stmt_sync->execute()) {
            $synced_count++;
        }
        $stmt_sync->close();
    }
    
    if ($synced_count > 0) {
        writeLog("Synced $synced_count user test results to test_results table");
    }
    
    return $synced_count;
}

// Jalankan sync
syncUserTestResults($conn);

// Get test results with user information
$query = "SELECT 
    t.id_test,
    t.id_user,
    u.nama_lengkap,
    u.email,
    u.no_hp,
    u.nomor_test,
    t.tanggal_test,
    t.total_soal,
    t.soal_terjawab,
    t.jawaban_benar,
    t.nilai,
    t.status_test,
    t.durasi_test,
    t.created_at
FROM test_results t
LEFT JOIN user u ON t.id_user = u.id_user
ORDER BY t.created_at DESC";

$result = $conn->query($query);
$total_results = $result ? $result->num_rows : 0;

// Get statistics
$stats = [];
$total_tests_query = $conn->query("SELECT COUNT(*) as total FROM test_results");
$stats['total_tests'] = ($total_tests_query && $total_tests_query->num_rows > 0) ? $total_tests_query->fetch_assoc()['total'] : 0;

$passed_query = $conn->query("SELECT COUNT(*) as total FROM test_results WHERE status_test = 'lulus'");
$stats['passed'] = ($passed_query && $passed_query->num_rows > 0) ? $passed_query->fetch_assoc()['total'] : 0;

$failed_query = $conn->query("SELECT COUNT(*) as total FROM test_results WHERE status_test = 'tidak_lulus'");
$stats['failed'] = ($failed_query && $failed_query->num_rows > 0) ? $failed_query->fetch_assoc()['total'] : 0;

$ongoing_query = $conn->query("SELECT COUNT(*) as total FROM test_results WHERE status_test = 'sedang_berlangsung'");
$stats['ongoing'] = ($ongoing_query && $ongoing_query->num_rows > 0) ? $ongoing_query->fetch_assoc()['total'] : 0;

$avg_result = $conn->query("SELECT AVG(nilai) as avg FROM test_results WHERE nilai IS NOT NULL AND nilai > 0");
$stats['avg_score'] = ($avg_result && $avg_result->num_rows > 0) ? $avg_result->fetch_assoc()['avg'] : 0;

$avg_duration_result = $conn->query("SELECT AVG(durasi_test) as avg FROM test_results WHERE durasi_test IS NOT NULL AND durasi_test > 0");
$stats['avg_duration'] = ($avg_duration_result && $avg_duration_result->num_rows > 0) ? $avg_duration_result->fetch_assoc()['avg'] : 0;

// Calculate pass percentage
$pass_percentage = $stats['total_tests'] > 0 ? round(($stats['passed'] / $stats['total_tests']) * 100, 1) : 0;

// Get max and min scores
$max_score_query = $conn->query("SELECT MAX(nilai) as max FROM test_results WHERE nilai IS NOT NULL AND nilai > 0");
$max_score = ($max_score_query && $max_score_query->num_rows > 0) ? $max_score_query->fetch_assoc()['max'] : null;

$min_score_query = $conn->query("SELECT MIN(nilai) as min FROM test_results WHERE nilai IS NOT NULL AND nilai > 0");
$min_score = ($min_score_query && $min_score_query->num_rows > 0) ? $min_score_query->fetch_assoc()['min'] : null;

// Get top performers
$top_query = "SELECT 
    u.nama_lengkap,
    t.nilai,
    t.tanggal_test
FROM test_results t
LEFT JOIN user u ON t.id_user = u.id_user
WHERE t.nilai IS NOT NULL AND t.nilai > 0 AND t.status_test = 'lulus'
ORDER BY t.nilai DESC
LIMIT 5";
$top_result = $conn->query($top_query);

// Get user count yang sudah test
$user_tested = $conn->query("SELECT COUNT(DISTINCT id_user) as total FROM test_results WHERE nilai IS NOT NULL AND nilai > 0");
$user_tested_count = ($user_tested && $user_tested->num_rows > 0) ? $user_tested->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Test - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #6366f1;
            --success: #10b981;
            --success-dark: #059669;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --dark: #1f2937;
            --dark-light: #374151;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f1f5f9 100%);
            overflow-x: hidden;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .animate-fade-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
        .animate-fade-left { animation: fadeInLeft 0.6s ease-out forwards; opacity: 0; }
        .animate-fade-right { animation: fadeInRight 0.6s ease-out forwards; opacity: 0; }
        .animate-scale { animation: scaleIn 0.5s ease-out forwards; opacity: 0; }

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

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        .sidebar-header { padding: 30px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h4 { font-weight: 800; font-size: 1.5rem; background: linear-gradient(135deg, #fff, #c7d2fe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }
        .sidebar-header small { font-size: 0.7rem; opacity: 0.7; }

        .admin-info { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-avatar { width: 70px; height: 70px; background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 2rem; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; }
        .admin-avatar:hover { transform: scale(1.1); }
        .admin-info h5 { font-weight: 600; font-size: 1rem; margin-bottom: 4px; }
        .admin-info small { opacity: 0.7; font-size: 0.7rem; }

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

        .sidebar .nav-link i { width: 22px; font-size: 1.1rem; transition: transform 0.3s ease; }
        .sidebar .nav-link:hover i { transform: translateX(5px); }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05)); color: white; transform: translateX(5px); }

        .main-content { margin-left: 280px; padding: 30px; min-height: 100vh; }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
            cursor: pointer;
        }

        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 25px 35px -12px rgba(0,0,0,0.15); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .stat-number { font-size: 2rem; font-weight: 800; margin-bottom: 5px; color: #1f2937; }
        .stat-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; }

        .card-modern {
            background: white;
            border-radius: 24px;
            border: none;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header-modern {
            padding: 20px 25px;
            background: white;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-header-modern h5 { font-weight: 700; font-size: 1.1rem; margin: 0; display: flex; align-items: center; gap: 10px; }

        .badge-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .score-excellent { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
        .score-good { background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; }
        .score-average { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
        .score-poor { background: linear-gradient(135deg, #ef4444, #f87171); color: white; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 130px;
        }

        .status-lulus { background: #d1fae5; color: #059669; }
        .status-tidak_lulus { background: #fee2e2; color: #dc2626; }
        .status-sedang_berlangsung { background: #dbeafe; color: #2563eb; }

        .top-performer-card {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .top-performer-card:hover { transform: translateX(8px) scale(1.02); }

        .rank-badge {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .action-btn-view { color: #3b82f6; background: #eff6ff; }
        .action-btn-view:hover { background: #3b82f6; color: white; transform: scale(1.1); }

        .action-btn-delete { color: #ef4444; background: #fef2f2; }
        .action-btn-delete:hover { background: #ef4444; color: white; transform: scale(1.1); }

        .search-wrapper {
            background: white;
            border-radius: 16px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }

        .search-wrapper:focus-within { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        .search-wrapper i { padding: 0 12px; color: #94a3b8; }
        .search-wrapper input { border: none; padding: 10px 0; flex: 1; outline: none; background: transparent; }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 14px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79,70,229,0.4); }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            background: #f8fafc;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tbody td {
            padding: 16px 20px;
            font-size: 0.85rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .data-table tbody tr:hover { background: #f8fafc; }

        .progress { height: 6px; border-radius: 10px; background: #e2e8f0; overflow: hidden; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar .nav-link span, .sidebar-header small, .admin-info h5, .admin-info small { display: none; }
            .sidebar .nav-link i { margin: 0; }
            .admin-avatar { width: 45px; height: 45px; font-size: 1.2rem; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .stat-number { font-size: 1.5rem; }
        }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #4f46e5, #818cf8); border-radius: 10px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">🎓 PMB</h4>
            <small>Administrator Panel</small>
        </div>
        
        <div class="admin-info">
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin'); ?></h5>
            <small>Administrator</small>
        </div>
        
        <div class="flex-grow-1">
            <ul class="nav flex-column">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li class="nav-item"><a href="manage_users.php" class="nav-link"><i class="fas fa-user-plus"></i> <span>Kelola Calon</span></a></li>
                <li class="nav-item"><a href="manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Soal Test</span></a></li>
                <li class="nav-item"><a href="registered_users.php" class="nav-link"><i class="fas fa-users"></i> <span>User Terdaftar</span></a></li>
                <li class="nav-item"><a href="test_results.php" class="nav-link active"><i class="fas fa-chart-line"></i> <span>Hasil Test</span></a></li>
                <li class="nav-item"><a href="re_registration.php" class="nav-link"><i class="fas fa-clipboard-list"></i> <span>Daftar Ulang</span></a></li>
                <li class="nav-item"><a href="generate_nim.php" class="nav-link"><i class="fas fa-id-card"></i> <span>Generate NIM</span></a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> <span>Laporan</span></a></li>
            </ul>
        </div>
        
        <div class="p-3 border-top" style="border-top: 1px solid rgba(255,255,255,0.1) !important;">
            <a href="logout.php" class="btn btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div class="animate-fade-up" style="animation-delay: 0.1s">
                <h1 class="display-6 fw-bold mb-0" style="font-size: 1.8rem;">
                    <i class="fas fa-chart-line text-primary me-2"></i>Hasil Test Calon Maba
                </h1>
                <p class="text-muted mt-1 mb-0">Monitoring dan analisis hasil ujian seleksi</p>
            </div>
            <div class="d-flex gap-2 animate-fade-right" style="animation-delay: 0.1s">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel me-2"></i>Export Excel</a></li>
                        <li><a class="dropdown-item" href="#" id="exportPrint"><i class="fas fa-print me-2"></i>Cetak</a></li>
                    </ul>
                </div>
                <button class="btn btn-primary" id="refreshBtn">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
        </div>

        <?php if (function_exists('displayMessage')) displayMessage(); ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                        <i class="fas fa-clipboard-list text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_tests']; ?></div>
                    <div class="stat-label">Total Test</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['passed']; ?></div>
                    <div class="stat-label">Lulus</div>
                    <div class="stat-trend text-success"><i class="fas fa-percent"></i> <?php echo $pass_percentage; ?>%</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="fas fa-times-circle text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['failed']; ?></div>
                    <div class="stat-label">Tidak Lulus</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $user_tested_count; ?></div>
                    <div class="stat-label">Peserta Test</div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8 animate-fade-up" style="animation-delay: 0.2s">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5><i class="fas fa-chart-pie text-primary"></i> Distribusi Hasil Test</h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">Lulus</span>
                                        <span><?php echo $stats['passed']; ?> (<?php echo $pass_percentage; ?>%)</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo $pass_percentage; ?>%;"></div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">Tidak Lulus</span>
                                        <span><?php echo $stats['failed']; ?> (<?php echo $stats['total_tests'] > 0 ? round(($stats['failed'] / $stats['total_tests']) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" style="width: <?php echo $stats['total_tests'] > 0 ? ($stats['failed'] / $stats['total_tests']) * 100 : 0; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <div class="mb-4 p-3 bg-light rounded-4">
                                        <div class="text-muted mb-2">Rata-rata Nilai</div>
                                        <h2 class="text-primary mb-0"><?php echo number_format((float)$stats['avg_score'], 1); ?></h2>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="p-3 bg-light rounded-4">
                                                <div class="text-muted mb-2">Tertinggi</div>
                                                <h4 class="text-success mb-0"><?php echo ($max_score !== null) ? number_format((float)$max_score, 1) : '0.0'; ?></h4>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-light rounded-4">
                                                <div class="text-muted mb-2">Terendah</div>
                                                <h4 class="text-danger mb-0"><?php echo ($min_score !== null) ? number_format((float)$min_score, 1) : '0.0'; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 animate-fade-right" style="animation-delay: 0.25s">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5><i class="fas fa-trophy text-warning"></i> Top 5 Performer</h5>
                    </div>
                    <div class="p-4">
                        <?php if($top_result && $top_result->num_rows > 0): $rank = 1; ?>
                            <?php while($top = $top_result->fetch_assoc()): ?>
                                <div class="top-performer-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rank-badge"><?php echo $rank++; ?></div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($top['nama_lengkap'] ?? 'Tidak Diketahui'); ?></div>
                                            <small class="opacity-75">Skor: <?php echo number_format((float)$top['nilai'], 1); ?></small>
                                        </div>
                                        <i class="fas fa-crown fa-lg opacity-50"></i>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-3x mb-3 opacity-25"></i>
                                <p>Belum ada data hasil test</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.3s">
            <div class="card-header-modern">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari berdasarkan nama atau nomor test...">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="dropdown d-inline-block me-2">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-2"></i>Filter Status
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item filter-status" href="#" data-status="all">📊 Semua Status</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item filter-status" href="#" data-status="lulus">✅ Lulus</a></li>
                                <li><a class="dropdown-item filter-status" href="#" data-status="tidak_lulus">❌ Tidak Lulus</a></li>
                            </ul>
                        </div>
                        <span class="text-muted">
                            <i class="fas fa-database me-1"></i>
                            <span id="resultsCount"><?php echo $total_results; ?></span> data
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card-modern animate-scale" style="animation-delay: 0.35s">
            <div class="table-responsive">
                <table class="data-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Peserta</th>
                            <th>No. Test</th>
                            <th>Tanggal</th>
                            <th>Nilai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $score_class = 'score-average';
                                $nilai = $row['nilai'] ?? 0;
                                if ($nilai >= 80) $score_class = 'score-excellent';
                                elseif ($nilai >= 70) $score_class = 'score-good';
                                elseif ($nilai >= 50) $score_class = 'score-average';
                                else $score_class = 'score-poor';
                            ?>
                            <tr>
                                <td><?php echo $row['id_test']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user-graduate" style="color: #4f46e5;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['nama_lengkap'] ?? 'Tidak Diketahui'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['nomor_test'] ?? '-'); ?></small>
                                        </div>
                                    </div>
                                  </div>
                                 </td>
                                 <td><?php echo $row['nomor_test'] ?? '-'; ?></td>
                                 <td><?php echo date('d/m/Y', strtotime($row['tanggal_test'])); ?></td>
                                 <td>
                                    <span class="badge-modern <?php echo $score_class; ?>">
                                        <?php echo ($row['nilai'] !== null && $row['nilai'] !== '') ? number_format((float)$row['nilai'], 1) : '-'; ?>
                                    </span>
                                 </td>
                                 <td>
                                    <span class="status-badge status-<?php echo $row['status_test']; ?>">
                                        <i class="fas <?php echo $row['status_test'] == 'lulus' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $row['status_test'] ?? 'unknown')); ?>
                                    </span>
                                 </td>
                                 <td>
                                    <div class="d-flex gap-1">
                                        <button class="action-btn action-btn-view view-result" data-id="<?php echo $row['id_test']; ?>" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn action-btn-delete delete-result" data-id="<?php echo $row['id_test']; ?>" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                 </td>
                             </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    <p class="text-muted mb-0">Belum ada data hasil test</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Result Modal -->
    <div class="modal fade" id="viewResultModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 24px;">
                <div class="modal-header border-0" style="padding: 25px 30px 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-chart-line me-2 text-primary"></i>Detail Hasil Test
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="resultDetails" style="padding: 20px 30px;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0" style="padding: 0 30px 25px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="printResult">
                        <i class="fas fa-print me-2"></i>Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            <?php if($total_results > 0): ?>
            var table = $('#resultsTable').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "zeroRecords": "Data tidak ditemukan",
                    "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    "infoEmpty": "Tidak ada data",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "paginate": { "first": "«", "last": "»", "next": "›", "previous": "‹" }
                },
                "dom": '<"top"f>rt<"bottom"lip>',
                "columnDefs": [{ "orderable": false, "targets": [6] }]
            });
            
            $('.dataTables_filter').addClass('d-none');
            
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
                $('#resultsCount').text(table.rows({ search: 'applied' }).count());
            });
            
            $('.filter-status').on('click', function(e) {
                e.preventDefault();
                var status = $(this).data('status');
                if (status === 'all') {
                    table.columns(5).search('').draw();
                } else {
                    table.columns(5).search(status).draw();
                }
                $('#resultsCount').text(table.rows({ search: 'applied' }).count());
            });
            <?php endif; ?>
            
            $('#refreshBtn').on('click', function() { 
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
                setTimeout(function() { location.reload(); }, 500);
            });
            
            $('#exportExcel').on('click', function(e) { 
                e.preventDefault(); 
                window.location.href = 'export_results.php?format=excel'; 
            });
            
            $('#exportPrint').on('click', function(e) { 
                e.preventDefault(); 
                window.print(); 
            });
            
            $('.view-result').on('click', function() {
                var testId = $(this).data('id');
                $('#resultDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
                $.ajax({
                    url: 'get_result_details.php',
                    type: 'GET',
                    data: { id: testId },
                    success: function(response) {
                        $('#resultDetails').html(response);
                        $('#viewResultModal').modal('show');
                    },
                    error: function() {
                        $('#resultDetails').html('<div class="alert alert-danger">Gagal memuat detail hasil test.</div>');
                    }
                });
            });
            
            $('.delete-result').on('click', function() {
                var testId = $(this).data('id');
                if (confirm('⚠️ Apakah Anda yakin ingin menghapus hasil test ini?')) {
                    $.ajax({
                        url: 'delete_test_result.php',
                        type: 'POST',
                        data: { id: testId },
                        success: function(response) {
                            alert('✅ Hasil test berhasil dihapus!');
                            location.reload();
                        },
                        error: function() {
                            alert('❌ Gagal menghapus hasil test.');
                        }
                    });
                }
            });
            
            $('#printResult').on('click', function() {
                var printContent = $('#resultDetails').html();
                var printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Detail Hasil Test</title>');
                printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
                printWindow.document.write('</head><body>' + printContent + '</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
        });
    </script>
</body>
</html>