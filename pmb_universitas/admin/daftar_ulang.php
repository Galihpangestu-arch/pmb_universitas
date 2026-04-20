<?php
require_once '../config.php';
checkAdminLogin();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'verify':
            if (isset($_POST['status'])) {
                $status = sanitize($_POST['status']);
                $catatan = sanitize($_POST['catatan'] ?? '');
                
                $sql = "UPDATE daftar_ulang SET 
                        status = ?, 
                        catatan = ?, 
                        verified_by = ?, 
                        verified_at = NOW() 
                        WHERE id_daftar_ulang = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $status, $catatan, $_SESSION['admin_id'], $id);
                
                if ($stmt->execute()) {
                    // Update user status if verified
                    if ($status == 'diverifikasi') {
                        $update_user_sql = "UPDATE user SET status_pendaftaran = 'daftar_ulang' 
                                          WHERE id_user = (SELECT id_user FROM daftar_ulang WHERE id_daftar_ulang = ?)";
                        $update_stmt = $conn->prepare($update_user_sql);
                        $update_stmt->bind_param("i", $id);
                        $update_stmt->execute();
                    }
                    
                    $_SESSION['message'] = 'Status daftar ulang berhasil diupdate';
                    $_SESSION['message_type'] = 'success';
                }
            }
            break;
            
        case 'delete':
            $sql = "DELETE FROM daftar_ulang WHERE id_daftar_ulang = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Data daftar ulang berhasil dihapus';
                $_SESSION['message_type'] = 'success';
            }
            break;
    }
    
    header('Location: re_registration.php');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Build query
$sql = "SELECT du.*, u.nama_lengkap, u.program_studi, u.nomor_test, u.email, u.nomor_induk,
               a.nama_lengkap as verifikator
        FROM daftar_ulang du
        JOIN user u ON du.id_user = u.id_user
        LEFT JOIN admin a ON du.verified_by = a.id_admin
        WHERE DATE(du.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];
$types = "ss";

if ($status_filter && $status_filter != 'all') {
    $sql .= " AND du.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY du.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status = 'diverifikasi' THEN 1 ELSE 0 END) as diverifikasi,
    SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
    SUM(jumlah_pembayaran) as total_pembayaran
    FROM daftar_ulang 
    WHERE DATE(created_at) BETWEEN ? AND ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ss", $start_date, $end_date);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Daftar Ulang - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-menunggu { background-color: #ffc107; color: black; }
        .status-diverifikasi { background-color: #28a745; color: white; }
        .status-ditolak { background-color: #dc3545; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .modal-xl {
            max-width: 800px;
        }
        
        .proof-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Kelola Daftar Ulang</h2>
        </div>
        
        <?php displayMessage(); ?>
        
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Data
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="menunggu" <?php echo $status_filter == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="diverifikasi" <?php echo $status_filter == 'diverifikasi' ? 'selected' : ''; ?>>Diverifikasi</option>
                            <option value="ditolak" <?php echo $status_filter == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h4>
                            <small>Total Daftar Ulang</small>
                        </div>
                        <i class="fas fa-clipboard-check fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['menunggu'] ?? 0; ?></h4>
                            <small>Menunggu Verifikasi</small>
                        </div>
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['diverifikasi'] ?? 0; ?></h4>
                            <small>Diverifikasi</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Rp <?php echo number_format($stats['total_pembayaran'] ?? 0, 0, ',', '.'); ?></h4>
                            <small>Total Pembayaran</small>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Daftar Ulang</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Mahasiswa</th>
                                <th>Program Studi</th>
                                <th>No. Test</th>
                                <th>Tanggal Daftar Ulang</th>
                                <th>Jumlah Pembayaran</th>
                                <th>Status</th>
                                <th>Verifikasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $result->fetch_assoc()): 
                                $status_class = 'status-' . $row['status'];
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['program_studi']); ?></td>
                                <td><?php echo $row['nomor_test']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal_daftar_ulang'])); ?></td>
                                <td>Rp <?php echo number_format($row['jumlah_pembayaran'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['verifikator']): ?>
                                        <small><?php echo $row['verifikator']; ?></small><br>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['verified_at'])); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewDetail(<?php echo $row['id_daftar_ulang']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($row['status'] == 'menunggu'): ?>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="verifyRegistration(<?php echo $row['id_daftar_ulang']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <a href="re_registration.php?action=delete&id=<?php echo $row['id_daftar_ulang']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Daftar Ulang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailBody">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Modal -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="re_registration.php?action=verify&id=0" id="verifyForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Verifikasi Daftar Ulang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status Verifikasi</label>
                            <select class="form-select" name="status" required>
                                <option value="diverifikasi">Diverifikasi</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" name="catatan" rows="3" 
                                      placeholder="Berikan catatan jika diperlukan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetail(id) {
            fetch(`get_registration_detail.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detailBody').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('detailModal')).show();
                });
        }
        
        function verifyRegistration(id) {
            document.getElementById('verifyForm').action = `re_registration.php?action=verify&id=${id}`;
            new bootstrap.Modal(document.getElementById('verifyModal')).show();
        }
    </script>
</body>
</html>