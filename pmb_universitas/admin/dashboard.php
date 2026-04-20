<?php
require_once '../config/config.php';
checkAdminLogin();

// Get statistics with error handling
$stats = [];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as total FROM user")->fetch_assoc()['total'] ?? 0;
$stats['registered'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'registrasi'")->fetch_assoc()['total'] ?? 0;
$stats['test'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'test'")->fetch_assoc()['total'] ?? 0;
$stats['passed'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'lulus'")->fetch_assoc()['total'] ?? 0;
$stats['failed'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'tidak_lulus'")->fetch_assoc()['total'] ?? 0;
$stats['re_registered'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'daftar_ulang'")->fetch_assoc()['total'] ?? 0;
$stats['completed'] = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'selesai'")->fetch_assoc()['total'] ?? 0;
$stats['total_questions'] = $conn->query("SELECT COUNT(*) as total FROM soal_test")->fetch_assoc()['total'] ?? 0;
$stats['active_questions'] = $conn->query("SELECT COUNT(*) as total FROM soal_test WHERE aktif = 'Y'")->fetch_assoc()['total'] ?? 0;

// Get recent registrations
$recent_query = "SELECT * FROM user ORDER BY created_at DESC LIMIT 5";
$recent_result = $conn->query($recent_query);

// Get today's registrations
$today = date('Y-m-d');
$today_count = $conn->query("SELECT COUNT(*) as total FROM user WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'] ?? 0;

// Get week's registrations
$week_ago = date('Y-m-d', strtotime('-7 days'));
$week_count = $conn->query("SELECT COUNT(*) as total FROM user WHERE DATE(created_at) >= '$week_ago'")->fetch_assoc()['total'] ?? 0;

// Get passing rate
$passing_rate = $stats['total_users'] > 0 ? round(($stats['passed'] / $stats['total_users']) * 100, 1) : 0;

// Get total admin count
$admin_count = $conn->query("SELECT COUNT(*) as total FROM admin")->fetch_assoc();
$total_admins = $admin_count['total'] ?? 1;

// Get last update time (from any table)
$last_update = $conn->query("SELECT MAX(updated_at) as last FROM user")->fetch_assoc();
$last_update_time = $last_update['last'] ?? date('Y-m-d H:i:s');

// Get server info
$db_size_query = $conn->query("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
$db_size = $db_size_query->fetch_assoc()['size'] ?? 0;
$db_size_mb = round($db_size / 1024 / 1024, 2);

// Get PHP version
$php_version = phpversion();

// Get last 7 days data for chart
$labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($date));
    $count = $conn->query("SELECT COUNT(*) as total FROM user WHERE DATE(created_at) = '$date'")->fetch_assoc()['total'] ?? 0;
    $chart_data[] = $count;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PMB Universitas</title>
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
            --success-dark: #059669;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f1f5f9 100%);
            overflow-x: hidden;
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

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .animate-fade-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-left {
            animation: fadeInLeft 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-pulse:hover {
            animation: pulse 0.5s ease-in-out;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .stagger-item {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .stagger-item:nth-child(1) { animation-delay: 0.05s; }
        .stagger-item:nth-child(2) { animation-delay: 0.1s; }
        .stagger-item:nth-child(3) { animation-delay: 0.15s; }
        .stagger-item:nth-child(4) { animation-delay: 0.2s; }
        .stagger-item:nth-child(5) { animation-delay: 0.25s; }
        .stagger-item:nth-child(6) { animation-delay: 0.3s; }
        .stagger-item:nth-child(7) { animation-delay: 0.35s; }
        .stagger-item:nth-child(8) { animation-delay: 0.4s; }

        /* ===== SIDEBAR MODERN ===== */
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            color: white;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

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

        .sidebar-header small { font-size: 0.7rem; opacity: 0.7; }

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
            transition: all 0.3s ease;
        }

        .admin-avatar:hover { transform: scale(1.1) rotate(5deg); }

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
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before { left: 100%; }

        .sidebar .nav-link i { width: 22px; font-size: 1.1rem; transition: transform 0.3s; }
        .sidebar .nav-link:hover i { transform: translateX(3px); }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
            color: white;
            transform: translateX(5px);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* ===== WELCOME CARD ===== */
        .welcome-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(79,70,229,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(16,185,129,0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .greeting h1 { font-size: 1.8rem; font-weight: 800; color: #1f2937; margin-bottom: 8px; }
        .greeting p { color: #6b7280; }

        .date-badge {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            padding: 12px 24px;
            border-radius: 20px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
        }

        /* ===== STAT CARDS MODERN ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 22px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #818cf8);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .stat-card:hover::after { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 25px 35px -15px rgba(0,0,0,0.15); }

        .stat-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .stat-icon-wrapper i { font-size: 28px; color: white; }

        .stat-value { font-size: 2.2rem; font-weight: 800; color: #1f2937; margin-bottom: 5px; letter-spacing: -0.02em; }
        .stat-label { font-size: 0.85rem; color: #6b7280; font-weight: 500; margin-bottom: 12px; }

        .stat-trend {
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }

        /* ===== CARD MODERN ===== */
        .card-modern {
            background: white;
            border-radius: 24px;
            border: none;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .card-modern:hover {
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
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

        /* ===== TABLE STYLES ===== */
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
            padding: 15px 20px;
            font-size: 0.85rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table tbody tr { transition: all 0.3s; }
        .data-table tbody tr:hover { background: #f8fafc; }

        /* ===== BADGES ===== */
        .badge-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.7rem;
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
            border-radius: 14px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79,70,229,0.35);
            color: white;
        }

        .btn-outline-primary {
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #374151;
            text-decoration: none;
        }

        .btn-outline-primary:hover {
            border-color: #4f46e5;
            color: #4f46e5;
            transform: translateY(-2px);
            background: #f8fafc;
        }

        /* ===== PROGRESS BAR ===== */
        .progress-custom {
            height: 8px;
            border-radius: 10px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-bar-custom {
            background: linear-gradient(90deg, #4f46e5, #818cf8);
            border-radius: 10px;
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== INFO LIST ===== */
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }

        .info-list li:hover {
            background: #f8fafc;
            padding-left: 8px;
            padding-right: 8px;
            margin: 0 -8px;
            border-radius: 12px;
        }

        .info-list li:last-child { border-bottom: none; }

        .info-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.85rem;
            background: #f8fafc;
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* ===== CHART CONTAINER ===== */
        .chart-container {
            height: 280px;
            position: relative;
            padding: 10px;
        }

        /* ===== DASHBOARD GRID ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
        }

        /* ===== GLASS MORPHISM ===== */
        .glass-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar .nav-link span,
            .sidebar-header small,
            .admin-info h5,
            .admin-info small { display: none; }
            .sidebar .nav-link i { margin: 0; }
            .admin-avatar { width: 45px; height: 45px; font-size: 1.2rem; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .stat-value { font-size: 1.5rem; }
            .greeting h1 { font-size: 1.3rem; }
            .welcome-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Modern -->
    <div class="sidebar">
        <div class="sidebar-header animate-fade-left" style="animation-delay: 0.1s">
            <h4 class="mb-0">🎓 PMB</h4>
            <small>Administrator Panel</small>
        </div>
        
        <div class="admin-info animate-fade-left" style="animation-delay: 0.15s">
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['admin_nama'] ?? 'Administrator'); ?></h5>
            <small>Administrator</small>
        </div>
        
        <div class="flex-grow-1">
            <ul class="nav flex-column">
                <li class="nav-item stagger-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li class="nav-item stagger-item"><a href="manage_users.php" class="nav-link"><i class="fas fa-user-plus"></i> <span>Kelola Calon</span></a></li>
                <li class="nav-item stagger-item"><a href="manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Soal Test</span></a></li>
                <li class="nav-item stagger-item"><a href="registered_users.php" class="nav-link"><i class="fas fa-users"></i> <span>User Terdaftar</span></a></li>
                <li class="nav-item stagger-item"><a href="test_results.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Hasil Test</span></a></li>
                <li class="nav-item stagger-item"><a href="re_registration.php" class="nav-link"><i class="fas fa-clipboard-list"></i> <span>Daftar Ulang</span></a></li>
                <li class="nav-item stagger-item"><a href="generate_nim.php" class="nav-link"><i class="fas fa-id-card"></i> <span>Generate NIM</span></a></li>
                <li class="nav-item stagger-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> <span>Laporan</span></a></li>
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
        <!-- Welcome Section -->
        <div class="welcome-card animate-fade-up" style="animation-delay: 0.1s">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="greeting">
                    <h1>Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin'); ?>! 👋</h1>
                    <p class="mb-0">Berikut ringkasan data Penerimaan Mahasiswa Baru</p>
                </div>
                <div class="date-badge animate-float">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </div>

        <!-- Statistics Grid - Row 1 -->
        <div class="stat-grid">
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Calon Maba</div>
                <div class="stat-trend bg-success bg-opacity-10 text-success">
                    <i class="fas fa-arrow-up"></i> +<?php echo $today_count; ?> hari ini
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['registered']); ?></div>
                <div class="stat-label">Sedang Registrasi</div>
                <div class="stat-trend bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-clock"></i> Menunggu Verifikasi
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['test']); ?></div>
                <div class="stat-label">Sedang Test</div>
                <div class="stat-trend bg-info bg-opacity-10 text-info">
                    <i class="fas fa-spinner fa-pulse"></i> Proses Test
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['passed']); ?></div>
                <div class="stat-label">Lulus Test</div>
                <div class="stat-trend bg-success bg-opacity-10 text-success">
                    <i class="fas fa-trophy"></i> Berhak Daftar Ulang
                </div>
            </div>
        </div>

        <!-- Statistics Grid - Row 2 -->
        <div class="stat-grid">
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['failed']); ?></div>
                <div class="stat-label">Tidak Lulus</div>
                <div class="stat-trend bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-frown"></i> Perlu Perbaikan
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['re_registered']); ?></div>
                <div class="stat-label">Daftar Ulang</div>
                <div class="stat-trend bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-check-circle"></i> Verifikasi Berkas
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #1f2937, #374151);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
                <div class="stat-label">Selesai</div>
                <div class="stat-trend bg-success bg-opacity-10 text-success">
                    <i class="fas fa-check-double"></i> Proses Selesai
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                    <i class="fas fa-percent"></i>
                </div>
                <div class="stat-value"><?php echo $passing_rate; ?>%</div>
                <div class="stat-label">Tingkat Kelulusan</div>
                <div class="progress-custom mt-2">
                    <div class="progress-bar-custom" style="width: <?php echo $passing_rate; ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Registrations -->
            <div class="card-modern animate-fade-up" style="animation-delay: 0.3s">
                <div class="card-header-modern">
                    <h5>
                        <i class="fas fa-history text-primary"></i>
                        Pendaftaran Terbaru
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>No. Test</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_result && $recent_result->num_rows > 0): ?>
                                <?php while($row = $recent_result->fetch_assoc()): 
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
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td><code><?php echo $row['nomor_test'] ?? '-'; ?></code></td>
                                    <td>
                                        <span class="badge-modern badge-<?php echo $status_class; ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $row['status_pendaftaran'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Belum ada pendaftaran
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Chart -->
                <div class="card-modern animate-fade-up" style="animation-delay: 0.35s">
                    <div class="card-header-modern">
                        <h5>
                            <i class="fas fa-chart-line text-primary"></i>
                            Statistik Pendaftaran 7 Hari Terakhir
                        </h5>
                    </div>
                    <div class="p-3">
                        <div class="chart-container">
                            <canvas id="registrationChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card-modern animate-fade-up" style="animation-delay: 0.4s">
                    <div class="card-header-modern">
                        <h5>
                            <i class="fas fa-bolt text-warning"></i>
                            Aksi Cepat
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="d-grid gap-3">
                            <a href="manage_questions.php" class="btn-primary animate-pulse">
                                <i class="fas fa-plus-circle"></i> Tambah Soal Test
                            </a>
                            <a href="registered_users.php" class="btn-outline-primary">
                                <i class="fas fa-list"></i> Lihat Semua Pendaftar
                            </a>
                            <a href="generate_nim.php" class="btn-outline-primary">
                                <i class="fas fa-id-card"></i> Generate NIM
                            </a>
                            <a href="reports.php" class="btn-outline-primary">
                                <i class="fas fa-chart-pie"></i> Buat Laporan
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- System Information - DIPERBAIKI -->
                <div class="card-modern animate-fade-up" style="animation-delay: 0.45s">
                    <div class="card-header-modern">
                        <h5>
                            <i class="fas fa-server text-info"></i>
                            Informasi Sistem
                        </h5>
                    </div>
                    <div class="p-4">
                        <ul class="info-list">
                            <li>
                                <span class="info-label"><i class="fas fa-database me-2"></i>Database</span>
                                <span class="info-value"><?php echo DB_NAME; ?></span>
                            </li>
                            <li>
                                <span class="info-label"><i class="fas fa-hdd me-2"></i>Ukuran Database</span>
                                <span class="info-value"><?php echo $db_size_mb; ?> MB</span>
                            </li>
                            <li>
                                <span class="info-label"><i class="fas fa-calendar-alt me-2"></i>Tahun Akademik</span>
                                <span class="info-value"><?php echo date('Y/'.(date('Y')+1)); ?></span>
                            </li>
                            <li>
                                <span class="info-label"><i class="fas fa-users me-2"></i>Total Admin</span>
                                <span class="info-value"><?php echo $total_admins; ?> orang</span>
                            </li>
                            <li>
                                <span class="info-label"><i class="fab fa-php me-2"></i>Versi PHP</span>
                                <span class="info-value"><?php echo $php_version; ?></span>
                            </li>
                            <li>
                                <span class="info-label"><i class="fas fa-clock me-2"></i>Last Update</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($last_update_time)); ?></span>
                            </li>
                            <li>
                                <span class="info-label"><i class="fas fa-globe me-2"></i>Versi Sistem</span>
                                <span class="info-value"><span class="badge-modern badge-primary">v4.1.0</span></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Registration Chart
        const ctx = document.getElementById('registrationChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Jumlah Pendaftar',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#4f46e5',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            font: { family: 'Inter', size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: 'Inter', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 12 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb', drawBorder: false },
                        title: {
                            display: true,
                            text: 'Jumlah Pendaftar',
                            color: '#6b7280',
                            font: { family: 'Inter', size: 12, weight: '500' }
                        },
                        ticks: { font: { family: 'Inter', size: 11 } }
                    },
                    x: {
                        grid: { display: false },
                        title: {
                            display: true,
                            text: 'Tanggal',
                            color: '#6b7280',
                            font: { family: 'Inter', size: 12, weight: '500' }
                        },
                        ticks: { font: { family: 'Inter', size: 11 } }
                    }
                }
            }
        });
        
        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar-custom');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html> 