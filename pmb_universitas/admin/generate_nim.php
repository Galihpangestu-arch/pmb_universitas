<?php
require_once '../config/config.php';
checkAdminLogin();

// Handle NIM generation
if (isset($_GET['generate']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Get user data
    $sql = "SELECT * FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user && $user['status_pendaftaran'] == 'daftar_ulang') {
        // Generate NIM
        $tahun = date('y');
        $nim = generateNIM($tahun, $user['program_studi']);
        
        // Update user with NIM and complete registration
        $update_sql = "UPDATE user SET nomor_induk = ?, status_pendaftaran = 'selesai', updated_at = NOW() WHERE id_user = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $nim, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = 'NIM berhasil digenerate: ' . $nim;
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal generate NIM: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'User tidak ditemukan atau belum eligible';
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: generate_nim.php');
    exit();
}

// Get users eligible for NIM generation
$sql = "SELECT * FROM user WHERE status_pendaftaran = 'daftar_ulang' ORDER BY updated_at DESC";
$result = $conn->query($sql);

// Get statistics
$total_mahasiswa = $conn->query("SELECT COUNT(*) as total FROM user WHERE nomor_induk IS NOT NULL")->fetch_assoc()['total'] ?? 0;
$total_ready = $result->num_rows;
$total_menunggu = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'daftar_ulang'")->fetch_assoc()['total'] ?? 0;
$total_hari_ini = $conn->query("SELECT COUNT(*) as total FROM user WHERE DATE(updated_at) = CURDATE() AND nomor_induk IS NOT NULL")->fetch_assoc()['total'] ?? 0;

// Get last 5 generated NIM
$nim_list = $conn->query("SELECT * FROM user WHERE nomor_induk IS NOT NULL ORDER BY updated_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate NIM - PMB Universitas</title>
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

        /* ===== NIM CARD ===== */
        .nim-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            height: 100%;
            padding: 20px;
        }

        .nim-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -12px rgba(0,0,0,0.1);
            border-color: #e0e7ff;
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
        .badge-primary { background: #e0e7ff; color: #4f46e5; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #dbeafe; color: #2563eb; }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
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
                <li class="nav-item stagger-item"><a href="generate_nim.php" class="nav-link active"><i class="fas fa-id-card"></i> <span>Generate NIM</span></a></li>
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
        <div class="animate-fade-up" style="animation-delay: 0.1s">
            <h1 class="display-6 fw-bold mb-0" style="font-size: 1.8rem;">
                <i class="fas fa-id-card text-primary me-2"></i>Generate Nomor Induk Mahasiswa
            </h1>
            <p class="text-muted mt-1 mb-4">Generate NIM untuk calon mahasiswa yang telah lulus daftar ulang</p>
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
        
        <!-- Statistics -->
        <div class="stat-grid">
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                <div class="stat-value"><?php echo $total_mahasiswa; ?></div>
                <div class="stat-label">Total Mahasiswa</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-id-card text-white"></i>
                </div>
                <div class="stat-value"><?php echo $total_ready; ?></div>
                <div class="stat-label">Siap Generate NIM</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    <i class="fas fa-clock text-white"></i>
                </div>
                <div class="stat-value"><?php echo $total_menunggu; ?></div>
                <div class="stat-label">Menunggu Generate</div>
            </div>
            
            <div class="stat-card stagger-item">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                    <i class="fas fa-calendar-day text-white"></i>
                </div>
                <div class="stat-value"><?php echo $total_hari_ini; ?></div>
                <div class="stat-label">NIM Hari Ini</div>
            </div>
        </div>
        
        <!-- Users Ready List -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.25s">
            <div class="card-header-modern">
                <h5>
                    <i class="fas fa-users text-primary me-2"></i>
                    Calon Mahasiswa Siap Generate NIM
                    <span class="badge-modern badge-info ms-2"><?php echo $total_ready; ?> Calon</span>
                </h5>
            </div>
            <div class="p-4">
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="nim-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user-graduate" style="color: #4f46e5; font-size: 0.9rem;"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['program_studi']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge-modern badge-warning">
                                        <i class="fas fa-clock me-1"></i>Ready
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Email</small>
                                    <small class="fw-semibold"><?php echo htmlspecialchars($row['email']); ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Nomor Test</small>
                                    <small><?php echo $row['nomor_test'] ?: '-'; ?></small>
                                </div>
                                
                                <div class="d-grid">
                                    <a href="generate_nim.php?generate=1&id=<?php echo $row['id_user']; ?>" 
                                       class="btn btn-primary"
                                       onclick="return confirm('Generate NIM untuk <?php echo addslashes($row['nama_lengkap']); ?>?')">
                                        <i class="fas fa-cog me-2"></i>Generate NIM
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada calon mahasiswa yang siap</h5>
                        <p class="text-muted">Calon mahasiswa dengan status "daftar_ulang" akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Generated NIM List -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.3s">
            <div class="card-header-modern">
                <h5>
                    <i class="fas fa-list-check text-primary me-2"></i>
                    NIM yang Telah Digenerate
                </h5>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Program Studi</th>
                            <th>Tanggal Generate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($nim_list && $nim_list->num_rows > 0): ?>
                            <?php while ($nim = $nim_list->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge-modern badge-primary">
                                        <i class="fas fa-id-card me-1"></i><?php echo $nim['nomor_induk']; ?>
                                    </span>
                                </td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($nim['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($nim['program_studi']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($nim['updated_at'])); ?></td>
                                <td>
                                    <span class="badge-modern badge-success">
                                        <i class="fas fa-check-circle me-1"></i>Selesai
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">Belum ada NIM yang digenerate</h6>
                                    <p class="text-muted small">Generate NIM akan muncul di sini setelah Anda melakukan generate.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
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