<?php
require_once '../config/config.php';
checkUserLogin();

$user_id = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;

if ($user_id == 0) {
    header('Location: ../login.php');
    exit();
}

// =============================================
// FIX: Ambil SEMUA kolom user yang dibutuhkan
// termasuk program_studi untuk generate NIM
// =============================================
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: ../login.php');
    exit();
}

// Pastikan hanya user dengan status 'lulus' yang bisa akses halaman ini
if ($user['status_pendaftaran'] !== 'lulus' && $user['status_pendaftaran'] !== 'daftar_ulang' && $user['status_pendaftaran'] !== 'selesai') {
    $_SESSION['message'] = 'Anda belum memenuhi syarat untuk daftar ulang.';
    $_SESSION['message_type'] = 'warning';
    header('Location: dashboard.php');
    exit();
}

// Cek apakah user sudah pernah daftar ulang
$daftar_ulang = null;
$table_check = $conn->query("SHOW TABLES LIKE 'daftar_ulang'");
if ($table_check && $table_check->num_rows > 0) {
    $du_sql = "SELECT * FROM daftar_ulang WHERE id_user = ? ORDER BY created_at DESC LIMIT 1";
    $du_stmt = $conn->prepare($du_sql);
    $du_stmt->bind_param("i", $user_id);
    $du_stmt->execute();
    $daftar_ulang = $du_stmt->get_result()->fetch_assoc();
}

$message      = '';
$message_type = '';

// =============================================
// FIX: Definisi fungsi generateNIM
// Format NIM: [2 digit tahun][kode prodi][5 digit urut]
// Contoh: 2401000001
// =============================================
function generateNIM(mysqli $conn, string $tahun, string $program_studi = ''): string {
    // Mapping kode program studi (sesuaikan dengan data di DB Anda)
    $kode_prodi = [
        'Teknik Informatika' => '01',
        'Sistem Informasi'   => '02',
        'Manajemen'          => '03',
        'Akuntansi'          => '04',
        'Hukum'              => '05',
        // Tambahkan prodi lain sesuai kebutuhan
    ];

    $kode = $kode_prodi[$program_studi] ?? '00'; // default '00' jika prodi tidak dikenali

    // Cari nomor urut terakhir berdasarkan prefix tahun + kode prodi
    $prefix = $tahun . $kode;
    $like   = $prefix . '%';

    $q = $conn->prepare("SELECT nomor_induk FROM user WHERE nomor_induk LIKE ? ORDER BY nomor_induk DESC LIMIT 1");
    $q->bind_param("s", $like);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();

    if ($row && !empty($row['nomor_induk'])) {
        // Ambil 5 digit terakhir lalu increment
        $last_seq = (int) substr($row['nomor_induk'], -5);
        $new_seq  = $last_seq + 1;
    } else {
        $new_seq = 1;
    }

    // Format: prefix + 5 digit urut (zero-padded)
    return $prefix . str_pad($new_seq, 5, '0', STR_PAD_LEFT);
}

// =============================================
// FIX: checkAndGenerateNIM menerima $conn dan
// menggunakan kolom program_studi dengan benar
// =============================================
function checkAndGenerateNIM(mysqli $conn, int $user_id): array {
    // Cek apakah ada pembayaran yang sudah lunas
    $du_sql  = "SELECT * FROM daftar_ulang WHERE id_user = ? AND status_pembayaran = 'lunas' ORDER BY created_at DESC LIMIT 1";
    $du_stmt = $conn->prepare($du_sql);
    $du_stmt->bind_param("i", $user_id);
    $du_stmt->execute();
    $du = $du_stmt->get_result()->fetch_assoc();

    if (!$du) {
        return ['success' => false]; // Belum lunas
    }

    // FIX: Ambil program_studi juga dalam satu query
    $user_sql  = "SELECT nomor_induk, status_pendaftaran, program_studi FROM user WHERE id_user = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $u = $user_stmt->get_result()->fetch_assoc();

    if (!$u) {
        return ['success' => false];
    }

    // Jika sudah punya NIM, tidak perlu generate lagi
    if (!empty($u['nomor_induk'])) {
        return ['success' => false, 'already_has_nim' => true, 'nim' => $u['nomor_induk']];
    }

    // Hanya generate jika status masih 'daftar_ulang'
    if ($u['status_pendaftaran'] !== 'daftar_ulang') {
        return ['success' => false];
    }

    $tahun          = date('y');
    $program_studi  = $u['program_studi'] ?? '';
    $nim            = generateNIM($conn, $tahun, $program_studi);

    // Update NIM dan status menjadi 'selesai'
    $update_sql  = "UPDATE user SET nomor_induk = ?, status_pendaftaran = 'selesai', updated_at = NOW() WHERE id_user = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $nim, $user_id);

    if ($update_stmt->execute()) {
        return ['success' => true, 'nim' => $nim];
    }

    return ['success' => false];
}

// Jalankan pengecekan NIM setiap kali halaman dimuat
$nim_result = checkAndGenerateNIM($conn, $user_id);
if ($nim_result['success']) {
    $_SESSION['message']      = '🎉 Selamat! NIM Anda telah digenerate: ' . $nim_result['nim'];
    $_SESSION['message_type'] = 'success';
    header('Location: dashboard.php');
    exit();
}

// =============================================
// Proses form POST daftar ulang
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi: jangan proses jika sudah daftar ulang
    if ($daftar_ulang) {
        $message      = 'Anda sudah melakukan daftar ulang sebelumnya.';
        $message_type = 'warning';
    } else {
        $nama_ortu    = trim($_POST['nama_ortu']    ?? '');
        $alamat_ortu  = trim($_POST['alamat_ortu']  ?? '');
        $no_telp_ortu = trim($_POST['no_telp_ortu'] ?? '');
        $bukti_pembayaran = null;

        // Validasi input wajib
        if (empty($nama_ortu) || empty($alamat_ortu) || empty($no_telp_ortu)) {
            $message      = 'Semua field wajib diisi.';
            $message_type = 'danger'; // FIX: Bootstrap pakai 'danger' bukan 'error'
        } else {
            // Upload bukti pembayaran
            if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "../uploads/bukti_pembayaran/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                $file_mime     = mime_content_type($_FILES['bukti_pembayaran']['tmp_name']);
                $file_ext      = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
                $max_size      = 5 * 1024 * 1024; // 5 MB

                if (!in_array($file_mime, $allowed_types)) {
                    $message      = 'Format file tidak valid. Gunakan JPG, PNG, GIF, atau PDF.';
                    $message_type = 'danger';
                } elseif ($_FILES['bukti_pembayaran']['size'] > $max_size) {
                    $message      = 'Ukuran file terlalu besar. Maksimal 5 MB.';
                    $message_type = 'danger';
                } else {
                    $bukti_pembayaran = 'bukti_' . $user_id . '_' . date('YmdHis') . '.' . $file_ext;
                    $target_file      = $target_dir . $bukti_pembayaran;

                    if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $target_file)) {
                        $message          = 'Gagal mengupload bukti pembayaran. Coba lagi.';
                        $message_type     = 'danger';
                        $bukti_pembayaran = null;
                    }
                }
            } else {
                $message      = 'Bukti pembayaran wajib diupload.';
                $message_type = 'danger';
            }

            // Simpan ke DB jika tidak ada error
            if (empty($message)) {
                $insert_sql  = "INSERT INTO daftar_ulang (id_user, nama_ortu, alamat_ortu, no_telp_ortu, bukti_pembayaran, status_pembayaran, created_at)
                                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("issss", $user_id, $nama_ortu, $alamat_ortu, $no_telp_ortu, $bukti_pembayaran);

                if ($insert_stmt->execute()) {
                    // Update status user menjadi 'daftar_ulang'
                    $upd      = $conn->prepare("UPDATE user SET status_pendaftaran = 'daftar_ulang', updated_at = NOW() WHERE id_user = ?");
                    $upd->bind_param("i", $user_id);
                    $upd->execute();

                    $_SESSION['message']      = 'Pendaftaran ulang berhasil! Silakan tunggu konfirmasi pembayaran dari admin.';
                    $_SESSION['message_type'] = 'success';
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $message      = 'Gagal menyimpan data: ' . htmlspecialchars($conn->error);
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Re-fetch daftar_ulang setelah POST (jika ada)
if ($table_check && $table_check->num_rows > 0) {
    $du_stmt2 = $conn->prepare("SELECT * FROM daftar_ulang WHERE id_user = ? ORDER BY created_at DESC LIMIT 1");
    $du_stmt2->bind_param("i", $user_id);
    $du_stmt2->execute();
    $daftar_ulang = $du_stmt2->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ulang - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #6366f1;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
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
        }

        .user-info {
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-info i {
            font-size: 3rem;
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

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
            color: #fff;
            transform: translateX(5px);
        }

        /* ===== Main ===== */
        .main-content { margin-left: 280px; padding: 40px; min-height: 100vh; }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 24px;
            padding: 35px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(102,126,234,0.3);
        }

        .page-header h2 { font-weight: 800; font-size: 1.8rem; }

        /* ===== Card ===== */
        .form-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            border: none;
        }

        .form-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 30px;
            border: none;
        }

        .form-card .card-body { padding: 35px; }

        .form-label { font-weight: 600; color: var(--dark); margin-bottom: 8px; }

        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid var(--gray-light);
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
        }

        textarea.form-control { resize: vertical; min-height: 90px; }

        /* Upload area */
        .upload-area {
            border: 2px dashed var(--gray-light);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover { border-color: var(--primary); background: rgba(79,70,229,0.03); }
        .upload-area i { font-size: 2.5rem; color: var(--primary); margin-bottom: 10px; }

        /* Status badge */
        .status-card {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px solid #86efac;
            border-radius: 20px;
            padding: 25px;
        }

        .status-card.pending {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-color: #fcd34d;
        }

        .status-card.lunas {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-color: #86efac;
        }

        .status-card.ditolak {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #fca5a5;
        }

        /* Button */
        .btn {
            border-radius: 14px;
            font-weight: 600;
            padding: 13px 30px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 5px 15px rgba(79,70,229,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.4);
        }

        /* Info box */
        .info-box {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 2px solid #93c5fd;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-box .info-title { font-weight: 700; color: #1d4ed8; margin-bottom: 5px; }
        .info-box p { color: #1e40af; margin: 0; font-size: 0.9rem; }

        /* NIM display */
        .nim-box {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            border-radius: 20px;
            padding: 30px;
            color: white;
            text-align: center;
        }

        .nim-box .nim-number {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 4px;
            background: linear-gradient(135deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .animate-in { animation: fadeInUp 0.5s ease forwards; }

        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar .nav-link span,
            .sidebar-header small,
            .user-info h5,
            .user-info small { display: none; }
            .main-content { margin-left: 80px; padding: 20px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <div class="sidebar-header text-center">
        <h4 class="mb-0">🎓 PMB</h4>
        <small style="opacity:.7;font-size:.75rem;">Universitas</small>
    </div>
    <div class="user-info text-center py-3">
        <i class="fas fa-user-circle d-block mb-2"></i>
        <h5 class="mb-0 fw-semibold"><?php echo htmlspecialchars($user['nama_lengkap'] ?? 'User'); ?></h5>
        <small style="opacity:.7;font-size:.75rem;"><?php echo htmlspecialchars($user['nomor_test'] ?? '-'); ?></small>
    </div>
    <div class="flex-grow-1">
        <ul class="nav flex-column mt-2">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home me-2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="hasil.php" class="nav-link">
                    <i class="fas fa-chart-line me-2"></i><span>Hasil Test</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="re_registration.php" class="nav-link active">
                    <i class="fas fa-clipboard-check me-2"></i><span>Daftar Ulang</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user me-2"></i><span>Profil Saya</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="p-3" style="border-top:1px solid rgba(255,255,255,.1);">
        <a href="logout.php" class="btn btn-outline-light w-100">
            <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Page Header -->
    <div class="page-header animate-in">
        <h2><i class="fas fa-clipboard-list me-3"></i>Daftar Ulang</h2>
        <p class="mb-0 mt-2" style="opacity:.85;">
            Lengkapi data dan upload bukti pembayaran untuk menyelesaikan proses pendaftaran Anda
        </p>
    </div>

    <?php if (function_exists('displayMessage')) displayMessage(); ?>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
            <i class="fas fa-<?php echo $message_type === 'danger' ? 'exclamation-circle' : 'info-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Jika sudah memiliki NIM (selesai) -->
    <?php if ($user['status_pendaftaran'] === 'selesai' && !empty($user['nomor_induk'])): ?>
        <div class="form-card animate-in">
            <div class="card-body text-center py-5">
                <div class="nim-box mx-auto mb-4" style="max-width:450px;">
                    <p class="text-white-50 mb-2 small text-uppercase letter-spacing-1">Nomor Induk Mahasiswa</p>
                    <div class="nim-number"><?php echo htmlspecialchars($user['nomor_induk']); ?></div>
                    <p class="text-white-50 mt-2 small"><?php echo htmlspecialchars($user['program_studi'] ?? ''); ?></p>
                </div>
                <h4 class="fw-bold text-success">🎉 Selamat! Pendaftaran Anda Telah Selesai</h4>
                <p class="text-muted mb-4">Anda resmi terdaftar sebagai mahasiswa baru. Simpan NIM Anda dengan baik.</p>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

    <!-- Jika sudah daftar ulang, tampilkan status -->
    <?php elseif ($daftar_ulang): ?>
        <div class="form-card animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Status Daftar Ulang</h5>
            </div>
            <div class="card-body">
                <?php
                    $sp = $daftar_ulang['status_pembayaran'] ?? 'pending';
                    $status_info = [
                        'pending'  => ['icon' => 'fa-hourglass-half', 'title' => 'Menunggu Verifikasi', 'desc'  => 'Data Anda sedang ditinjau oleh admin. Proses ini biasanya memakan waktu 1-2 hari kerja.'],
                        'lunas'    => ['icon' => 'fa-check-circle', 'title' => 'Pembayaran Dikonfirmasi', 'desc'  => 'Pembayaran Anda telah diverifikasi. NIM akan segera digenerate secara otomatis.'],
                        'ditolak'  => ['icon' => 'fa-times-circle', 'title' => 'Pembayaran Ditolak', 'desc'  => 'Bukti pembayaran Anda tidak valid. Silakan hubungi admin untuk informasi lebih lanjut.'],
                    ];
                    $info = $status_info[$sp] ?? $status_info['pending'];
                ?>
                <div class="status-card <?php echo $sp; ?> mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas <?php echo $info['icon']; ?> fa-2x me-3
                            <?php echo $sp === 'lunas' ? 'text-success' : ($sp === 'ditolak' ? 'text-danger' : 'text-warning'); ?>"></i>
                        <div>
                            <h5 class="mb-0 fw-bold"><?php echo $info['title']; ?></h5>
                            <small class="text-muted">
                                Dikirim: <?php echo date('d F Y, H:i', strtotime($daftar_ulang['created_at'])); ?> WIB
                            </small>
                        </div>
                    </div>
                    <p class="mb-0"><?php echo $info['desc']; ?></p>
                </div>

                <!-- Detail data yang dikirim -->
                <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.8rem;letter-spacing:1px;">
                    Data Yang Dikirimkan
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Nama Orang Tua/Wali</small>
                            <strong><?php echo htmlspecialchars($daftar_ulang['nama_ortu'] ?? '-'); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">No. Telepon</small>
                            <strong><?php echo htmlspecialchars($daftar_ulang['no_telp_ortu'] ?? '-'); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Bukti Pembayaran</small>
                            <?php if (!empty($daftar_ulang['bukti_pembayaran'])): ?>
                                <a href="../uploads/bukti_pembayaran/<?php echo htmlspecialchars($daftar_ulang['bukti_pembayaran']); ?>"
                                   target="_blank" class="text-primary fw-bold">
                                    <i class="fas fa-eye me-1"></i>Lihat File
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

    <!-- Form Daftar Ulang -->
    <?php else: ?>
        <!-- Info box biaya -->
        <div class="info-box animate-in">
            <p class="info-title"><i class="fas fa-info-circle me-2"></i>Informasi Daftar Ulang</p>
            <p>Silakan lengkapi formulir di bawah ini dan upload bukti transfer pembayaran biaya daftar ulang.
               Setelah diverifikasi oleh admin, NIM Anda akan digenerate secara otomatis.</p>
        </div>

        <div class="form-card animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Daftar Ulang</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="formDaftarUlang">
                    <div class="row g-4">
                        <!-- Nama Orang Tua -->
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-user me-2 text-primary"></i>Nama Orang Tua / Wali
                            </label>
                            <input type="text" class="form-control" name="nama_ortu"
                                   placeholder="Masukkan nama lengkap orang tua/wali"
                                   value="<?php echo htmlspecialchars($_POST['nama_ortu'] ?? ''); ?>"
                                   required>
                        </div>

                        <!-- No. Telepon -->
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-phone me-2 text-primary"></i>No. Telepon Orang Tua / Wali
                            </label>
                            <input type="tel" class="form-control" name="no_telp_ortu"
                                   placeholder="Contoh: 08123456789"
                                   value="<?php echo htmlspecialchars($_POST['no_telp_ortu'] ?? ''); ?>"
                                   required>
                        </div>

                        <!-- Alamat -->
                        <div class="col-12">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>Alamat Orang Tua / Wali
                            </label>
                            <textarea class="form-control" name="alamat_ortu" rows="3"
                                      placeholder="Masukkan alamat lengkap orang tua/wali"
                                      required><?php echo htmlspecialchars($_POST['alamat_ortu'] ?? ''); ?></textarea>
                        </div>

                        <!-- Upload Bukti -->
                        <div class="col-12">
                            <label class="form-label">
                                <i class="fas fa-upload me-2 text-primary"></i>Bukti Pembayaran
                            </label>
                            <div class="upload-area" onclick="document.getElementById('bukti_file').click()">
                                <i class="fas fa-cloud-upload-alt d-block"></i>
                                <p class="mb-1 fw-semibold" id="file_label">Klik untuk pilih file atau drag & drop</p>
                                <small class="text-muted">Format: JPG, PNG, GIF, PDF — Maks. 5 MB</small>
                            </div>
                            <input type="file" id="bukti_file" name="bukti_pembayaran"
                                   accept="image/*,.pdf" class="d-none" required
                                   onchange="updateFileLabel(this)">
                        </div>

                        <!-- Submit -->
                        <div class="col-12 d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Daftar Ulang
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Update label nama file yang dipilih
    function updateFileLabel(input) {
        const label = document.getElementById('file_label');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const size = (file.size / 1024 / 1024).toFixed(2);
            label.innerHTML = `<strong>${file.name}</strong> (${size} MB)`;
            label.style.color = '#4f46e5';
        }
    }

    // Drag & drop support
    const uploadArea = document.querySelector('.upload-area');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            uploadArea.style.borderColor = '#4f46e5';
            uploadArea.style.background  = 'rgba(79,70,229,0.05)';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '';
            uploadArea.style.background  = '';
        });

        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.style.borderColor = '';
            uploadArea.style.background  = '';
            const fileInput = document.getElementById('bukti_file');
            fileInput.files = e.dataTransfer.files;
            updateFileLabel(fileInput);
        });
    }
</script>
</body>
</html> 