<?php
require_once '../config/config.php';
checkAdminLogin();

// Get all users data
$query = "SELECT u.*, 
          CASE 
              WHEN u.status_pendaftaran = 'registrasi' THEN 'warning'
              WHEN u.status_pendaftaran = 'test' THEN 'info'
              WHEN u.status_pendaftaran = 'lulus' THEN 'success'
              WHEN u.status_pendaftaran = 'tidak_lulus' THEN 'danger'
              WHEN u.status_pendaftaran = 'daftar_ulang' THEN 'primary'
              WHEN u.status_pendaftaran = 'selesai' THEN 'dark'
          END as badge_color,
          CASE 
              WHEN u.status_pendaftaran = 'registrasi' THEN '📝 Registrasi'
              WHEN u.status_pendaftaran = 'test' THEN '✍️ Sedang Test'
              WHEN u.status_pendaftaran = 'lulus' THEN '🎉 Lulus'
              WHEN u.status_pendaftaran = 'tidak_lulus' THEN '❌ Tidak Lulus'
              WHEN u.status_pendaftaran = 'daftar_ulang' THEN '📋 Daftar Ulang'
              WHEN u.status_pendaftaran = 'selesai' THEN '✅ Selesai'
          END as status_text
          FROM user u 
          ORDER BY u.created_at DESC";
$result = $conn->query($query);
$total_users = $result->num_rows;

// Get status counts
$status_counts = [];
$statuses = ['registrasi', 'test', 'lulus', 'tidak_lulus', 'daftar_ulang', 'selesai'];
foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE status_pendaftaran = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $status_counts[$status] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
}

// Get recent activity (last 7 days)
$recent_activity = $conn->query("SELECT COUNT(*) as count FROM user WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Get total admin count
$admin_count = $conn->query("SELECT COUNT(*) as total FROM admin")->fetch_assoc()['total'] ?? 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User Terdaftar - PMB Universitas</title>
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

        .stat-trend {
            font-size: 0.75rem;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
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

        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-success { background: #d1fae5; color: #059669; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-primary { background: #e0e7ff; color: #4f46e5; }
        .badge-dark { background: #f1f5f9; color: #475569; }

        /* ===== ACTION BUTTONS ===== */
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

        .action-btn-view { background: #eff6ff; color: #3b82f6; }
        .action-btn-view:hover { background: #3b82f6; color: white; transform: scale(1.05); }

        .action-btn-edit { background: #fffbeb; color: #f59e0b; }
        .action-btn-edit:hover { background: #f59e0b; color: white; transform: scale(1.05); }

        .action-btn-delete { background: #fee2e2; color: #ef4444; }
        .action-btn-delete:hover { background: #ef4444; color: white; transform: scale(1.05); }

        /* ===== SEARCH ===== */
        .search-wrapper {
            background: white;
            border-radius: 16px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }

        .search-wrapper i {
            padding: 0 12px;
            color: #94a3b8;
        }

        .search-wrapper input {
            border: none;
            padding: 10px 0;
            flex: 1;
            outline: none;
            background: transparent;
        }

        .filter-dropdown .dropdown-toggle {
            background: white;
            border: 1px solid #e2e8f0;
            color: #475569;
            border-radius: 12px;
            padding: 8px 20px;
            font-weight: 500;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 12px;
            padding: 8px 20px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
        }

        .btn-outline-light {
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 12px;
        }

        /* ===== MODAL ===== */
        .modal-content-modern {
            border: none;
            border-radius: 24px;
            overflow: hidden;
        }

        .progress {
            height: 4px;
            border-radius: 10px;
            background: #e5e7eb;
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
            .stat-value { font-size: 1.5rem; }
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
                <li class="nav-item stagger-item"><a href="registered_users.php" class="nav-link active"><i class="fas fa-users"></i> <span>User Terdaftar</span></a></li>
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div class="animate-fade-up" style="animation-delay: 0.1s">
                <h1 class="display-6 fw-bold mb-0" style="font-size: 1.8rem;">
                    <i class="fas fa-users text-primary me-2"></i>Data User Terdaftar
                </h1>
                <p class="text-muted mt-1 mb-0">Kelola dan pantau seluruh data calon mahasiswa</p>
            </div>
            <div class="d-flex gap-2 animate-fade-up" style="animation-delay: 0.1s">
                <div class="filter-dropdown dropdown">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-2"></i>Filter Status
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item filter-status" href="#" data-status="all">📊 Semua Status</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item filter-status" href="#" data-status="registrasi">📝 Registrasi</a></li>
                        <li><a class="dropdown-item filter-status" href="#" data-status="test">✍️ Sedang Test</a></li>
                        <li><a class="dropdown-item filter-status" href="#" data-status="lulus">🎉 Lulus</a></li>
                        <li><a class="dropdown-item filter-status" href="#" data-status="tidak_lulus">❌ Tidak Lulus</a></li>
                        <li><a class="dropdown-item filter-status" href="#" data-status="daftar_ulang">📋 Daftar Ulang</a></li>
                        <li><a class="dropdown-item filter-status" href="#" data-status="selesai">✅ Selesai</a></li>
                    </ul>
                </div>
                <button class="btn btn-primary" id="exportBtn">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stat-grid">
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total User Terdaftar</div>
                <div class="stat-trend text-success">
                    <i class="fas fa-arrow-up"></i> +<?php echo $recent_activity; ?> minggu ini
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-user-plus text-white"></i>
                </div>
                <div class="stat-value"><?php echo $status_counts['registrasi']; ?></div>
                <div class="stat-label">Registrasi Baru</div>
                <div class="stat-trend text-warning">
                    <i class="fas fa-clock"></i> Menunggu proses
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div class="stat-value"><?php echo $status_counts['lulus']; ?></div>
                <div class="stat-label">Lulus Seleksi</div>
                <div class="stat-trend text-success">
                    <i class="fas fa-trophy"></i> Berhasil
                </div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                <div class="stat-value"><?php echo $status_counts['selesai']; ?></div>
                <div class="stat-label">Selesai Daftar Ulang</div>
                <div class="stat-trend text-primary">
                    <i class="fas fa-check-double"></i> Mahasiswa Aktif
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.25s">
            <div class="card-header-modern">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari user berdasarkan nama, email, atau nomor test...">
                            <button id="searchBtn" class="btn btn-link text-primary" style="padding: 0 15px;">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <span class="text-muted me-3">
                            <i class="fas fa-database me-1"></i>
                            <span id="userCount"><?php echo $total_users; ?></span> data
                        </span>
                        <button class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.3s">
            <div class="table-responsive">
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Kontak</th>
                            <th>No. Test</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_user']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-graduate" style="color: #4f46e5; font-size: 0.9rem;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['nama_lengkap']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($row['email']); ?></div>
                                <div class="mt-1"><i class="fas fa-phone me-2 text-muted"></i><?php echo $row['no_hp'] ?: '-'; ?></div>
                            </td>
                            <td>
                                <?php if($row['nomor_test']): ?>
                                    <code><?php echo $row['nomor_test']; ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-modern badge-<?php echo $row['badge_color']; ?>">
                                    <?php echo $row['status_text']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['nilai_test']): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress" style="width: 60px;">
                                            <div class="progress-bar bg-<?php echo $row['nilai_test'] >= 70 ? 'success' : 'danger'; ?>" 
                                                 style="width: <?php echo min(100, $row['nilai_test']); ?>%"></div>
                                        </div>
                                        <span class="fw-semibold"><?php echo number_format($row['nilai_test'], 0); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="action-btn action-btn-view view-user" data-id="<?php echo $row['id_user']; ?>" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn action-btn-edit edit-user" data-id="<?php echo $row['id_user']; ?>" title="Edit Data">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="action-btn action-btn-delete delete-user" data-id="<?php echo $row['id_user']; ?>" title="Hapus User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-content-modern">
                <div class="modal-header border-0" style="padding: 25px 30px 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-user-circle me-2 text-primary"></i>Detail Calon Mahasiswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetails" style="padding: 20px 30px;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0" style="padding: 0 30px 25px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#usersTable').DataTable({
                pageLength: 10,
                language: {
                    search: "",
                    searchPlaceholder: "Cari...",
                    lengthMenu: "Tampilkan _MENU_ data",
                    zeroRecords: "Data tidak ditemukan",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    paginate: {
                        first: "«",
                        last: "»",
                        next: "›",
                        previous: "‹"
                    }
                },
                dom: '<"top"f>rt<"bottom"lip>',
                columnDefs: [{ orderable: false, targets: [7] }]
            });
            
            $('.dataTables_filter').addClass('d-none');
            
            // Search
            $('#searchBtn, #searchInput').on('keyup click', function() {
                table.search($('#searchInput').val()).draw();
                $('#userCount').text(table.rows({ search: 'applied' }).count());
            });
            
            // Filter by status
            $('.filter-status').on('click', function(e) {
                e.preventDefault();
                var status = $(this).data('status');
                if (status === 'all') {
                    table.columns(4).search('').draw();
                } else {
                    var statusMap = {
                        'registrasi': '📝 Registrasi',
                        'test': '✍️ Sedang Test',
                        'lulus': '🎉 Lulus',
                        'tidak_lulus': '❌ Tidak Lulus',
                        'daftar_ulang': '📋 Daftar Ulang',
                        'selesai': '✅ Selesai'
                    };
                    table.columns(4).search(statusMap[status]).draw();
                }
                $('#userCount').text(table.rows({ search: 'applied' }).count());
            });
            
            // Refresh
            $('#refreshBtn').on('click', function() { location.reload(); });
            
            // Export
            $('#exportBtn').on('click', function() { window.location.href = 'export_users.php'; });
            
            // View user
            $('.view-user').on('click', function() {
                var userId = $(this).data('id');
                $('#userDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
                $.ajax({
                    url: 'get_user_details.php',
                    type: 'GET',
                    data: { id: userId },
                    success: function(response) {
                        $('#userDetails').html(response);
                        $('#viewUserModal').modal('show');
                    },
                    error: function() {
                        $('#userDetails').html('<div class="alert alert-danger">Gagal memuat data user.</div>');
                    }
                });
            });
            
            // Edit user
            $('.edit-user').on('click', function() {
                window.location.href = 'edit_user.php?id=' + $(this).data('id');
            });
            
            // Delete user
            $('.delete-user').on('click', function() {
                var userId = $(this).data('id');
                if (confirm('⚠️ Apakah Anda yakin ingin menghapus user ini?')) {
                    $.ajax({
                        url: 'delete_user.php',
                        type: 'POST',
                        data: { id: userId },
                        success: function(response) {
                            alert('✅ User berhasil dihapus!');
                            location.reload();
                        },
                        error: function() {
                            alert('❌ Gagal menghapus user.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>