<?php
require_once '../config/config.php';
checkAdminLogin();

// Get filter parameters
$tahun_akademik = isset($_GET['tahun_akademik']) ? $_GET['tahun_akademik'] : date('Y');
$jenis_laporan = isset($_GET['jenis_laporan']) ? $_GET['jenis_laporan'] : 'pendaftar';

// Get statistics for report
$stats = [];
$stats['total_pendaftar'] = $conn->query("SELECT COUNT(*) as total FROM user")->fetch_assoc()['total'] ?? 0;
$stats['total_diterima'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran IN ('lulus', 'daftar_ulang', 'selesai')")->fetch_assoc()['total'] ?? 0;
$stats['total_lulus'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'lulus'")->fetch_assoc()['total'] ?? 0;
$stats['total_daftar_ulang'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'daftar_ulang'")->fetch_assoc()['total'] ?? 0;

// Get data based on report type
$report_data = [];
switch($jenis_laporan) {
    case 'pendaftar':
        $query = "SELECT 
                    status_pendaftaran,
                    COUNT(*) as jumlah,
                    DATE_FORMAT(created_at, '%Y-%m') as bulan
                  FROM user 
                  WHERE YEAR(created_at) = ?
                  GROUP BY status_pendaftaran, DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY bulan DESC, status_pendaftaran";
        break;
    case 'test':
        $query = "SELECT 
                    status_pendaftaran,
                    COUNT(*) as jumlah_test,
                    AVG(nilai_test) as rata_nilai,
                    MAX(nilai_test) as nilai_tertinggi,
                    MIN(nilai_test) as nilai_terendah
                  FROM user 
                  WHERE status_pendaftaran IN ('lulus', 'tidak_lulus')
                  AND YEAR(created_at) = ?
                  AND nilai_test IS NOT NULL
                  GROUP BY status_pendaftaran";
        break;
    case 'statistik':
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as bulan,
                    status_pendaftaran,
                    COUNT(*) as jumlah
                  FROM user 
                  WHERE YEAR(created_at) = ?
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m'), status_pendaftaran
                  ORDER BY bulan DESC, status_pendaftaran";
        break;
}

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tahun_akademik);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $report_data[] = $row;
}

// Get monthly registration data for chart
$monthly_data_query = "SELECT 
                         DATE_FORMAT(created_at, '%Y-%m') as bulan,
                         COUNT(*) as jumlah
                       FROM user 
                       WHERE YEAR(created_at) = ?
                       GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                       ORDER BY bulan";
$monthly_stmt = $conn->prepare($monthly_data_query);
$monthly_stmt->bind_param("s", $tahun_akademik);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
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
            --gray: #6b7280;
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

        /* ===== CARD ===== */
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

        .card-header-modern h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===== TABLE ===== */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            background: #f8fafc;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 0.75rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .data-table tbody td {
            padding: 16px 20px;
            font-size: 0.85rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
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
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-primary { background: #e0e7ff; color: #4f46e5; }
        .badge-dark { background: #e5e7eb; color: #1f2937; }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            transform: translateY(-2px);
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            transform: translateY(-2px);
        }

        /* ===== FORM ===== */
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

        /* ===== PROGRESS ===== */
        .progress {
            height: 6px;
            border-radius: 10px;
            background: #e5e7eb;
        }

        .progress-bar {
            background: linear-gradient(90deg, #4f46e5, #818cf8);
            border-radius: 10px;
        }

        /* ===== CHART ===== */
        .chart-container {
            height: 320px;
            position: relative;
        }

        /* ===== REPORT HEADER ===== */
        .report-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 24px;
            padding: 25px 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .report-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 25px;
        }

        /* ===== RESPONSIVE ===== */
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
            .stat-value { font-size: 1.5rem; }
            .action-buttons { flex-wrap: wrap; justify-content: center; }
        }

        @media print {
            .sidebar, .action-buttons, .filter-card { display: none !important; }
            .main-content { margin-left: 0; padding: 0; }
            .stat-card, .card-modern { box-shadow: none; border: 1px solid #ddd; }
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
                <li class="nav-item stagger-item"><a href="manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Soal Test</span></a></li>
                <li class="nav-item stagger-item"><a href="registered_users.php" class="nav-link"><i class="fas fa-users"></i> <span>User Terdaftar</span></a></li>
                <li class="nav-item stagger-item"><a href="test_results.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Hasil Test</span></a></li>
                <li class="nav-item stagger-item"><a href="re_registration.php" class="nav-link"><i class="fas fa-clipboard-list"></i> <span>Daftar Ulang</span></a></li>
                <li class="nav-item stagger-item"><a href="generate_nim.php" class="nav-link"><i class="fas fa-id-card"></i> <span>Generate NIM</span></a></li>
                <li class="nav-item stagger-item"><a href="reports.php" class="nav-link active"><i class="fas fa-chart-pie"></i> <span>Laporan</span></a></li>
            </ul>
        </div>
        
        <div class="p-3 border-top animate-fade-left" style="animation-delay: 0.5s; border-top: 1px solid rgba(255,255,255,0.1) !important;">
            <a href="logout.php" class="btn btn-outline-light w-100">
                <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="report-header animate-fade-up" style="animation-delay: 0.1s">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="display-6 fw-bold mb-0" style="font-size: 1.8rem;">
                        <i class="fas fa-chart-pie me-2"></i>Sistem Laporan PMB
                    </h1>
                    <p class="mt-1 mb-0 opacity-75">Dashboard Laporan dan Analisis Data Penerimaan Mahasiswa Baru</p>
                </div>
                <div>
                    <span class="badge-modern" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-calendar me-1"></i>Tahun: <?php echo htmlspecialchars($tahun_akademik); ?>/<?php echo $tahun_akademik+1; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stat-grid">
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_pendaftar']); ?></div>
                <div class="stat-label">Total Pendaftar</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_diterima']); ?></div>
                <div class="stat-label">Diterima</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_lulus']); ?></div>
                <div class="stat-label">Lulus Test</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                    <i class="fas fa-clipboard-check text-white"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_daftar_ulang']); ?></div>
                <div class="stat-label">Daftar Ulang</div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.25s">
            <div class="card-header-modern">
                <h5>
                    <i class="fas fa-filter text-primary me-2"></i>
                    Filter Laporan
                </h5>
            </div>
            <div class="p-4">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tahun Akademik</label>
                        <select name="tahun_akademik" class="form-select">
                            <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $tahun_akademik ? 'selected' : ''; ?>>
                                <?php echo $i; ?>/<?php echo $i+1; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Laporan</label>
                        <select name="jenis_laporan" class="form-select" onchange="this.form.submit()">
                            <option value="pendaftar" <?php echo $jenis_laporan == 'pendaftar' ? 'selected' : ''; ?>>📋 Data Pendaftar</option>
                            <option value="test" <?php echo $jenis_laporan == 'test' ? 'selected' : ''; ?>>📊 Hasil Test</option>
                            <option value="statistik" <?php echo $jenis_laporan == 'statistik' ? 'selected' : ''; ?>>📈 Statistik Bulanan</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Tampilkan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons animate-fade-up" style="animation-delay: 0.3s">
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
            <button class="btn btn-warning" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Cetak Laporan
            </button>
            <button class="btn btn-info" id="toggleChartBtn">
                <i class="fas fa-chart-bar me-2"></i>Sembunyikan Grafik
            </button>
        </div>

        <!-- Monthly Chart -->
        <div class="row mb-4" id="chartSection">
            <div class="col-md-12">
                <div class="card-modern animate-fade-up" style="animation-delay: 0.35s">
                    <div class="card-header-modern">
                        <h5>
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Statistik Pendaftaran Per Bulan
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.4s">
            <div class="card-header-modern">
                <h5>
                    <i class="fas fa-table text-primary me-2"></i>
                    <?php 
                    echo $jenis_laporan == 'pendaftar' ? 'Laporan Data Pendaftar per Status' : 
                         ($jenis_laporan == 'test' ? 'Laporan Hasil Test' : 'Statistik Pendaftaran Bulanan');
                    ?>
                    <span class="badge-modern badge-primary ms-2">
                        <?php echo count($report_data); ?> Data
                    </span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="data-table" id="reportTable">
                    <thead>
                        <tr>
                            <?php if($jenis_laporan == 'pendaftar'): ?>
                            <th>#</th>
                            <th>Status</th>
                            <th>Bulan</th>
                            <th>Jumlah</th>
                            <th>Persentase</th>
                            <?php elseif($jenis_laporan == 'test'): ?>
                            <th>#</th>
                            <th>Status</th>
                            <th>Jumlah Test</th>
                            <th>Rata-rata</th>
                            <th>Tertinggi</th>
                            <th>Terendah</th>
                            <?php else: ?>
                            <th>#</th>
                            <th>Bulan</th>
                            <th>Status</th>
                            <th>Jumlah</th>
                            <th>Persentase</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        $total_all = 0;
                        
                        if($jenis_laporan == 'pendaftar') {
                            $total_all = $stats['total_pendaftar'];
                        }
                        
                        foreach($report_data as $row): 
                            if($jenis_laporan == 'pendaftar'):
                                $persentase = $total_all > 0 ? round(($row['jumlah'] / $total_all) * 100, 2) : 0;
                                $status_class = [
                                    'registrasi' => 'warning',
                                    'test' => 'info',
                                    'lulus' => 'success',
                                    'tidak_lulus' => 'danger',
                                    'daftar_ulang' => 'primary',
                                    'selesai' => 'dark'
                                ][$row['status_pendaftaran']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><span class="badge-modern badge-<?php echo $status_class; ?>"><?php echo ucwords(str_replace('_', ' ', $row['status_pendaftaran'])); ?></span></td>
                            <td><?php echo date('F Y', strtotime($row['bulan'] . '-01')); ?></td>
                            <td class="fw-semibold"><?php echo $row['jumlah']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar" style="width: <?php echo min($persentase, 100); ?>%"></div>
                                    </div>
                                    <span class="small text-muted"><?php echo $persentase; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            elseif($jenis_laporan == 'test'):
                                $status_class = $row['status_pendaftaran'] == 'lulus' ? 'success' : 'danger';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><span class="badge-modern badge-<?php echo $status_class; ?>"><?php echo ucwords(str_replace('_', ' ', $row['status_pendaftaran'])); ?></span></td>
                            <td class="fw-semibold"><?php echo $row['jumlah_test']; ?></td>
                            <td><span class="badge-modern badge-info"><?php echo round($row['rata_nilai'], 2); ?></span></td>
                            <td><span class="badge-modern badge-success"><?php echo $row['nilai_tertinggi']; ?></span></td>
                            <td><span class="badge-modern badge-danger"><?php echo $row['nilai_terendah']; ?></span></td>
                        </tr>
                        <?php 
                            else:
                                $status_class = [
                                    'registrasi' => 'warning',
                                    'test' => 'info',
                                    'lulus' => 'success',
                                    'tidak_lulus' => 'danger',
                                    'daftar_ulang' => 'primary',
                                    'selesai' => 'dark'
                                ][$row['status_pendaftaran']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo date('F Y', strtotime($row['bulan'] . '-01')); ?></td>
                            <td><span class="badge-modern badge-<?php echo $status_class; ?>"><?php echo ucwords(str_replace('_', ' ', $row['status_pendaftaran'])); ?></span></td>
                            <td class="fw-semibold"><?php echo $row['jumlah']; ?></td>
                            <td>
                                <?php 
                                $month_total = array_sum(array_column(array_filter($report_data, function($item) use ($row) {
                                    return $item['bulan'] == $row['bulan'];
                                }), 'jumlah'));
                                $month_percent = $month_total > 0 ? round(($row['jumlah'] / $month_total) * 100, 1) : 0;
                                ?>
                                <span class="badge-modern badge-dark"><?php echo $month_percent; ?>%</span>
                            </td>
                        </tr>
                        <?php 
                            endif;
                        endforeach; 
                        
                        if(empty($report_data)):
                        ?>
                        <tr>
                            <td colspan="<?php echo $jenis_laporan == 'pendaftar' ? 5 : ($jenis_laporan == 'test' ? 6 : 5); ?>" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Tidak ada data ditemukan</h6>
                                <p class="text-muted small">Untuk tahun akademik <?php echo $tahun_akademik; ?>/<?php echo $tahun_akademik+1; ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Monthly Chart
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        let monthlyChart = null;
        
        function initChart() {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            monthlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthlyData.map(item => {
                        const [year, month] = item.bulan.split('-');
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        return `${monthNames[parseInt(month)-1]} ${year}`;
                    }),
                    datasets: [{
                        label: 'Jumlah Pendaftar',
                        data: monthlyData.map(item => item.jumlah),
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4f46e5',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: 'Inter', size: 12 } } },
                        tooltip: { backgroundColor: '#1f2937', titleFont: { family: 'Inter' }, bodyFont: { family: 'Inter' }, padding: 10, cornerRadius: 8 }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Jumlah Pendaftar', font: { family: 'Inter', weight: '600' } }, grid: { color: '#f0f0f0' } },
                        x: { title: { display: true, text: 'Bulan', font: { family: 'Inter', weight: '600' } }, grid: { display: false } }
                    }
                }
            });
        }
        
        if (monthlyData.length > 0) {
            initChart();
        } else {
            document.getElementById('chartSection').style.display = 'none';
        }
        
        // Toggle chart visibility
        const toggleBtn = document.getElementById('toggleChartBtn');
        let chartVisible = true;
        
        toggleBtn.addEventListener('click', function() {
            const chartSection = document.getElementById('chartSection');
            if (chartVisible) {
                chartSection.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-chart-bar me-2"></i>Tampilkan Grafik';
                chartVisible = false;
            } else {
                chartSection.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-chart-bar me-2"></i>Sembunyikan Grafik';
                chartVisible = true;
                setTimeout(() => { if (monthlyChart) monthlyChart.resize(); }, 100);
            }
        });
        
        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('reportTable');
            let csv = [];
            
            for (let i = 0; i < table.rows[0].cells.length; i++) {
                csv.push(`"${table.rows[0].cells[i].innerText}"`);
            }
            csv.push('\n');
            
            for (let i = 1; i < table.rows.length; i++) {
                const row = [];
                for (let j = 0; j < table.rows[i].cells.length; j++) {
                    let cellText = table.rows[i].cells[j].innerText.replace(/\n/g, ' ').trim();
                    cellText = cellText.replace(/"/g, '""');
                    row.push(`"${cellText}"`);
                }
                csv.push(row.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `laporan_pmb_<?php echo $tahun_akademik; ?>_${new Date().toISOString().slice(0,19)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            alert('✓ Laporan berhasil diexport ke CSV');
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => { const bsAlert = new bootstrap.Alert(alert); bsAlert.close(); });
        }, 5000);
    </script>
</body>
</html>