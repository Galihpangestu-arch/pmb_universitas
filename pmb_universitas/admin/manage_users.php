<?php
require_once '../config/config.php';
checkAdminLogin();

// Handle action delete (Tetap ada untuk membersihkan data sampah/spam)
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = $_GET['id'] ?? 0;
    $sql = "DELETE FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Data calon mahasiswa berhasil dihapus';
        $_SESSION['message_type'] = 'success';
    }
    header('Location: manage_users.php');
    exit();
}

// Get Statistics untuk Dashboard Monitoring
$total_users = $conn->query("SELECT COUNT(*) as total FROM user")->fetch_assoc()['total'];
$total_registrasi = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'registrasi'")->fetch_assoc()['total'];
$total_test = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'test'")->fetch_assoc()['total'];
$total_lulus = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'lulus'")->fetch_assoc()['total'];
$total_selesai = $conn->query("SELECT COUNT(*) as total FROM user WHERE status_pendaftaran = 'selesai'")->fetch_assoc()['total'];

// Get Data User (Urutkan dari yang terbaru daftar)
$result = $conn->query("SELECT * FROM user ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Calon Maba | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --dark-blue: #1e1b4b;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; }

        /* SIDEBAR TETAP LENGKAP */
        .sidebar {
            height: 100vh; width: 280px; position: fixed; left: 0; top: 0;
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            color: white; z-index: 1000; overflow-y: auto;
        }
        .sidebar-header { padding: 30px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7); padding: 12px 20px; margin: 5px 15px;
            border-radius: 12px; display: flex; align-items: center; gap: 12px; transition: 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1); color: white; transform: translateX(5px);
        }
        .sidebar .nav-link.active { border-left: 4px solid #818cf8; }

        /* MAIN CONTENT */
        .main-content { margin-left: 280px; padding: 40px; }

        /* STATS CARD */
        .stat-card {
            background: white; border-radius: 20px; padding: 20px; border: none;
            box-shadow: var(--card-shadow); transition: 0.3s;
        }
        .stat-icon {
            width: 45px; height: 45px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; margin-bottom: 10px;
        }

        /* TABLE CUSTOM */
        .card-modern { background: white; border-radius: 24px; border: none; box-shadow: var(--card-shadow); overflow: hidden; }
        .data-table thead th { background: #f8fafc; padding: 18px 20px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; }
        .data-table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

        /* BADGE STATUS OTOMATIS */
        .badge-status { padding: 6px 12px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .bg-registrasi { background: #fff7ed; color: #c2410c; }
        .bg-test { background: #eff6ff; color: #1d4ed8; }
        .bg-lulus { background: #f0fdf4; color: #15803d; }
        .bg-selesai { background: #faf5ff; color: #7e22ce; }

        .action-btn { width: 35px; height: 35px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; border: none; text-decoration: none; }
        .btn-view { background: #eff6ff; color: #3b82f6; }
        .btn-delete { background: #fef2f2; color: #ef4444; }
        .btn-view:hover { background: #3b82f6; color: white; }
        .btn-delete:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">🎓 PMB ADMIN</h4>
            <small class="opacity-50">Monitoring System</small>
        </div>
        
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_users.php" class="nav-link active"><i class="fas fa-user-graduate"></i> Monitoring Calon</a>
            <a href="manage_questions.php" class="nav-link"><i class="fas fa-file-alt"></i> Soal Test</a>
            <a href="registered_users.php" class="nav-link"><i class="fas fa-users"></i> User Terdaftar</a>
            <a href="test_results.php" class="nav-link"><i class="fas fa-chart-line"></i> Hasil Test</a>
            <a href="re_registration.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Daftar Ulang</a>
            <a href="generate_nim.php" class="nav-link"><i class="fas fa-id-card"></i> Generate NIM</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-print"></i> Laporan</a>
            
            <div class="mt-5 px-4">
                <a href="logout.php" class="btn btn-danger w-100 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="fw-bold mb-0">Status Calon Mahasiswa Baru</h2>
                <p class="text-muted">Pemantauan otomatis progres pendaftaran calon mahasiswa secara real-time.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-dark shadow-sm p-2 rounded-3 border">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Tahun Akademik 2026/2027
                </span>
            </div>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 animate-slide-down">
                <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary text-white"><i class="fas fa-users"></i></div>
                    <h3 class="fw-bold mb-0"><?= $total_users ?></h3>
                    <small class="text-muted">Pendaftar Masuk</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info text-white"><i class="fas fa-pen-fancy"></i></div>
                    <h3 class="fw-bold mb-0"><?= $total_test ?></h3>
                    <small class="text-muted">Tahap Ujian</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success text-white"><i class="fas fa-user-check"></i></div>
                    <h3 class="fw-bold mb-0"><?= $total_lulus ?></h3>
                    <small class="text-muted">Lulus Seleksi</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-bottom: 4px solid #a855f7;">
                    <div class="stat-icon bg-purple text-white" style="background: #a855f7;"><i class="fas fa-graduation-cap"></i></div>
                    <h3 class="fw-bold mb-0"><?= $total_selesai ?></h3>
                    <small class="text-muted">Mahasiswa Aktif</small>
                </div>
            </div>
        </div>

        <div class="card-modern">
            <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Aktivitas Pendaftaran</h5>
                <input type="text" id="mabaSearch" class="form-control form-control-sm" placeholder="Cari nama atau nomor test..." style="width: 280px; border-radius: 12px;">
            </div>
            <div class="table-responsive">
                <table class="data-table w-100" id="mabaTable">
                    <thead>
                        <tr>
                            <th>Identitas</th>
                            <th>No. Test</th>
                            <th>Progres</th>
                            <th>Skor Ujian</th>
                            <th>Waktu Daftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): 
                            $st = $row['status_pendaftaran'];
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                <div class="text-muted small"><?= $row['email'] ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border">#<?= $row['nomor_test'] ?: 'WAIT' ?></span></td>
                            <td>
                                <span class="badge-status bg-<?= $st ?>">
                                    <i class="fas fa-sync fa-spin" style="font-size: 0.6rem;"></i>
                                    <?= strtoupper(str_replace('_', ' ', $st)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['nilai_test']): ?>
                                    <span class="fw-bold <?= $row['nilai_test'] >= 70 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($row['nilai_test'], 0) ?>/100
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted italic small">Belum Ujian</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <button class="action-btn btn-view" onclick="showDetail(<?= $row['id_user'] ?>)" title="Lihat Profil">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="manage_users.php?action=delete&id=<?= $row['id_user'] ?>" 
                                   class="action-btn btn-delete" 
                                   onclick="return confirm('Hapus data pendaftar ini secara permanen?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom-0 p-4">
                    <h5 class="fw-bold mb-0">Informasi Detail Calon Maba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0" id="userData">
                    </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Real-time Search Table
        $("#mabaSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#mabaTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // AJAX View Detail
        function showDetail(id) {
            $('#userData').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Memuat data...</p></div>');
            $('#userModal').modal('show');
            $.get('get_user_details.php?id=' + id, function(data) {
                $('#userData').html(data);
            });
        }
    </script>
</body>
</html>