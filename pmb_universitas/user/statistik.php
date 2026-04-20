<?php
require_once '../config/config.php';
checkUserLogin();

$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ============================================
// 1. STATISTIK UMUM USER
// ============================================

// Total soal yang sudah dikerjakan
$total_soal_sql = "SELECT COUNT(*) as total FROM hasil_test WHERE id_user = ?";
$total_soal_stmt = $conn->prepare($total_soal_sql);
$total_soal_stmt->bind_param("i", $user_id);
$total_soal_stmt->execute();
$total_soal = $total_soal_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Total jawaban benar dan salah
$stat_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END) as jawaban_benar,
    SUM(CASE WHEN ht.jawaban_user != st.jawaban_benar AND ht.jawaban_user IS NOT NULL THEN 1 ELSE 0 END) as jawaban_salah,
    SUM(CASE WHEN ht.jawaban_user IS NULL THEN 1 ELSE 0 END) as tidak_dijawab
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ?";
$stat_stmt = $conn->prepare($stat_sql);
$stat_stmt->bind_param("i", $user_id);
$stat_stmt->execute();
$stats = $stat_stmt->get_result()->fetch_assoc();

$jawaban_benar = $stats['jawaban_benar'] ?? 0;
$jawaban_salah = $stats['jawaban_salah'] ?? 0;
$tidak_dijawab = $stats['tidak_dijawab'] ?? 0;
$total_jawaban = $jawaban_benar + $jawaban_salah;

// Persentase
$persentase_benar = $total_jawaban > 0 ? round(($jawaban_benar / $total_jawaban) * 100, 1) : 0;
$persentase_salah = $total_jawaban > 0 ? round(($jawaban_salah / $total_jawaban) * 100, 1) : 0;

// Nilai test
$nilai_test = $user['nilai_test'] ?? 0;
$status_kelulusan = $user['status_pendaftaran'] ?? 'registrasi';

// Status text
$status_text = [
    'registrasi' => 'Belum Test',
    'test' => 'Sedang Test',
    'lulus' => 'Lulus Test',
    'tidak_lulus' => 'Tidak Lulus',
    'daftar_ulang' => 'Daftar Ulang',
    'selesai' => 'Selesai'
];

// ============================================
// 2. STATISTIK PER HARI (7 hari terakhir)
// ============================================
$harian_sql = "SELECT 
    DATE(ht.waktu_jawab) as tanggal,
    COUNT(*) as total,
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END) as benar
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ? AND ht.waktu_jawab >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(ht.waktu_jawab)
    ORDER BY tanggal ASC";
$harian_stmt = $conn->prepare($harian_sql);
$harian_stmt->bind_param("i", $user_id);
$harian_stmt->execute();
$stat_harian = $harian_stmt->get_result();

$harian_labels = [];
$harian_data = [];
while ($row = $stat_harian->fetch_assoc()) {
    $harian_labels[] = date('d M', strtotime($row['tanggal']));
    $harian_data[] = $row['benar'];
}

// ============================================
// 3. STATISTIK PER MATA PELAJARAN (TOP 5)
// ============================================
$mapel_sql = "SELECT 
    'Matematika' as mata_pelajaran,
    COUNT(*) as total,
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END) as benar
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ? AND st.id_soal BETWEEN 1 AND 10
    UNION ALL
    SELECT 
    'Bahasa Indonesia',
    COUNT(*),
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END)
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ? AND st.id_soal BETWEEN 11 AND 20
    UNION ALL
    SELECT 
    'Bahasa Inggris',
    COUNT(*),
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END)
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ? AND st.id_soal BETWEEN 21 AND 30
    UNION ALL
    SELECT 
    'IPA',
    COUNT(*),
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END)
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ? AND st.id_soal BETWEEN 31 AND 40
    UNION ALL
    SELECT 
    'IPS',
    COUNT(*),
    SUM(CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 1 ELSE 0 END)
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ? AND st.id_soal BETWEEN 41 AND 50";
$mapel_stmt = $conn->prepare($mapel_sql);
$mapel_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$mapel_stmt->execute();
$stat_mapel = $mapel_stmt->get_result();

$mapel_labels = [];
$mapel_persentase = [];
while ($row = $stat_mapel->fetch_assoc()) {
    if ($row['total'] > 0) {
        $mapel_labels[] = $row['mata_pelajaran'];
        $mapel_persentase[] = round(($row['benar'] / $row['total']) * 100, 1);
    }
}

// ============================================
// 4. SOAL TERSUSAH (Paling banyak salah)
// ============================================
$soal_sulit_sql = "SELECT 
    st.id_soal,
    st.pertanyaan,
    COUNT(*) as total_jawaban,
    SUM(CASE WHEN ht.jawaban_user != st.jawaban_benar THEN 1 ELSE 0 END) as jumlah_salah
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ?
    GROUP BY st.id_soal, st.pertanyaan
    HAVING jumlah_salah > 0
    ORDER BY (jumlah_salah / total_jawaban) DESC
    LIMIT 5";
$soal_sulit_stmt = $conn->prepare($soal_sulit_sql);
$soal_sulit_stmt->bind_param("i", $user_id);
$soal_sulit_stmt->execute();
$soal_sulit = $soal_sulit_stmt->get_result();

// ============================================
// 5. AKTIVITAS TERBARU
// ============================================
$aktivitas_sql = "SELECT 
    ht.waktu_jawab,
    st.pertanyaan,
    ht.jawaban_user,
    st.jawaban_benar,
    CASE WHEN ht.jawaban_user = st.jawaban_benar THEN 'benar' ELSE 'salah' END as status
    FROM hasil_test ht
    JOIN soal_test st ON ht.id_soal = st.id_soal
    WHERE ht.id_user = ?
    ORDER BY ht.waktu_jawab DESC
    LIMIT 10";
$aktivitas_stmt = $conn->prepare($aktivitas_sql);
$aktivitas_stmt->bind_param("i", $user_id);
$aktivitas_stmt->execute();
$aktivitas = $aktivitas_stmt->get_result();

// ============================================
// 6. PREDIKSI KELULUSAN
// ============================================
$prediksi_sql = "SELECT AVG(nilai_test) as rata_rata FROM user WHERE program_studi = ? AND nilai_test IS NOT NULL";
$prediksi_stmt = $conn->prepare($prediksi_sql);
$prediksi_stmt->bind_param("s", $user['program_studi']);
$prediksi_stmt->execute();
$rata_rata_prodi = $prediksi_stmt->get_result()->fetch_assoc()['rata_rata'] ?? 0;

$prediksi_lulus = ($nilai_test >= 70) ? true : false;
$di_atas_rata_rata = ($nilai_test > $rata_rata_prodi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Belajar Saya - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #0f172a;
            --gray: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            border: none;
        }

        .chart-container {
            height: 250px;
            padding: 15px;
        }

        /* Badge */
        .badge {
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success), #16a34a);
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }

        .badge-info {
            background: linear-gradient(135deg, var(--info), #2563eb);
        }

        /* Activity Item */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-icon.benar {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .activity-icon.salah {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Soal Item */
        .soal-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate {
            animation: fadeInUp 0.5s ease forwards;
        }

        /* Progress Bar */
        .progress-custom {
            height: 8px;
            border-radius: 10px;
            background: #e2e8f0;
        }

        .progress-custom .progress-bar {
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .stat-value {
                font-size: 1.5rem;
            }
            .chart-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Profil -->
        <div class="header animate">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="col">
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?> |
                        <i class="fas fa-code me-2 ms-2"></i><?php echo htmlspecialchars($user['program_studi'] ?? 'Belum pilih'); ?> |
                        <i class="fas fa-id-card me-2 ms-2"></i><?php echo htmlspecialchars($user['nomor_test'] ?? 'Belum ada'); ?>
                    </p>
                </div>
                <div class="col-auto">
                    <span class="badge <?php 
                        echo match($user['status_pendaftaran']) {
                            'lulus' => 'bg-success',
                            'tidak_lulus' => 'bg-danger',
                            'daftar_ulang' => 'bg-info',
                            'selesai' => 'bg-success',
                            default => 'bg-warning'
                        };
                    ?> fs-6">
                        <i class="fas fa-circle me-1"></i>
                        <?php echo $status_text[$user['status_pendaftaran']] ?? 'Registrasi'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3 animate" style="animation-delay: 0.1s;">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?php echo $total_soal; ?></div>
                    <div class="stat-label">Total Soal Dikerjakan</div>
                    <i class="fas fa-book-open text-primary mt-2 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3 animate" style="animation-delay: 0.2s;">
                <div class="stat-card">
                    <div class="stat-value text-success"><?php echo $persentase_benar; ?>%</div>
                    <div class="stat-label">Tingkat Keberhasilan</div>
                    <i class="fas fa-chart-line text-success mt-2 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3 animate" style="animation-delay: 0.3s;">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?php echo $jawaban_benar; ?></div>
                    <div class="stat-label">Jawaban Benar</div>
                    <i class="fas fa-check-circle text-warning mt-2 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3 animate" style="animation-delay: 0.4s;">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?php echo $jawaban_salah; ?></div>
                    <div class="stat-label">Jawaban Salah</div>
                    <i class="fas fa-times-circle text-danger mt-2 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6 mb-4 animate" style="animation-delay: 0.5s;">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i> Ringkasan Jawaban
                    </div>
                    <div class="chart-container">
                        <canvas id="answerChart"></canvas>
                    </div>
                    <div class="card-body bg-light">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-success fw-bold fs-4"><?php echo $jawaban_benar; ?></div>
                                <small class="text-muted">Benar</small>
                            </div>
                            <div class="col-4">
                                <div class="text-danger fw-bold fs-4"><?php echo $jawaban_salah; ?></div>
                                <small class="text-muted">Salah</small>
                            </div>
                            <div class="col-4">
                                <div class="text-warning fw-bold fs-4"><?php echo $tidak_dijawab; ?></div>
                                <small class="text-muted">Tidak Dijawab</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4 animate" style="animation-delay: 0.6s;">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i> Perkembangan 7 Hari Terakhir
                    </div>
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapel & Soal Sulit Row -->
        <div class="row">
            <div class="col-md-6 mb-4 animate" style="animation-delay: 0.7s;">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-book me-2"></i> Per Mata Pelajaran
                    </div>
                    <div class="card-body">
                        <?php if (count($mapel_labels) > 0): ?>
                            <?php for ($i = 0; $i < count($mapel_labels); $i++): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-semibold"><?php echo $mapel_labels[$i]; ?></span>
                                        <span class="text-muted"><?php echo $mapel_persentase[$i]; ?>%</span>
                                    </div>
                                    <div class="progress-custom">
                                        <div class="progress-bar bg-<?php 
                                            echo $mapel_persentase[$i] >= 70 ? 'success' : ($mapel_persentase[$i] >= 50 ? 'warning' : 'danger'); 
                                        ?>" style="width: <?php echo $mapel_persentase[$i]; ?>%"></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-simple fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada data per mata pelajaran</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4 animate" style="animation-delay: 0.8s;">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle me-2"></i> Soal Tersulit untuk Anda
                    </div>
                    <div class="card-body p-0">
                        <?php if ($soal_sulit->num_rows > 0): ?>
                            <?php while ($soal = $soal_sulit->fetch_assoc()): ?>
                                <div class="soal-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">
                                                <?php echo htmlspecialchars(substr($soal['pertanyaan'], 0, 80)); ?>...
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-chart-simple me-1"></i>
                                                Tingkat kesalahan: <?php echo round(($soal['jumlah_salah'] / $soal['total_jawaban']) * 100); ?>%
                                                (<?php echo $soal['jumlah_salah']; ?>/<?php echo $soal['total_jawaban']; ?>)
                                            </small>
                                        </div>
                                        <span class="badge bg-danger ms-2">Sulit</span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-smile fa-3x text-success mb-3"></i>
                                <p class="text-muted">Semua soal sudah Anda kuasai dengan baik! 🎉</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prediksi Kelulusan -->
        <?php if ($nilai_test > 0): ?>
        <div class="card animate" style="animation-delay: 0.9s;">
            <div class="card-header">
                <i class="fas fa-chart-simple me-2"></i> Prediksi Kelulusan
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="text-center mb-3 mb-md-0">
                            <div class="display-1 fw-bold <?php echo $prediksi_lulus ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $nilai_test; ?>
                            </div>
                            <div class="text-muted">Nilai Test Anda</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Nilai Minimal Lulus</span>
                                <span class="fw-bold">70</span>
                            </div>
                            <div class="progress-custom">
                                <div class="progress-bar bg-primary" style="width: 70%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Rata-rata Prodi <?php echo htmlspecialchars($user['program_studi'] ?? 'Anda'); ?></span>
                                <span class="fw-bold"><?php echo round($rata_rata_prodi, 1); ?></span>
                            </div>
                            <div class="progress-custom">
                                <div class="progress-bar bg-info" style="width: <?php echo min(100, $rata_rata_prodi); ?>%"></div>
                            </div>
                        </div>
                        <?php if ($prediksi_lulus): ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-trophy me-2"></i>
                                <strong>Selamat!</strong> Nilai Anda sudah memenuhi syarat kelulusan.
                                <?php if ($di_atas_rata_rata): ?>
                                    Nilai Anda juga di atas rata-rata program studi!
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Perlu Belajar Lebih Giat!</strong> Nilai Anda masih di bawah standar kelulusan.
                                Silakan ikuti test ulang.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Aktivitas Terbaru -->
        <div class="card animate" style="animation-delay: 1s;">
            <div class="card-header">
                <i class="fas fa-history me-2"></i> Aktivitas Terbaru
            </div>
            <div class="card-body p-0">
                <?php if ($aktivitas->num_rows > 0): ?>
                    <?php while ($act = $aktivitas->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $act['status']; ?>">
                                <i class="fas <?php echo $act['status'] == 'benar' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?php echo htmlspecialchars(substr($act['pertanyaan'], 0, 70)); ?>...</div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('d F Y, H:i', strtotime($act['waktu_jawab'])); ?>
                                    | Jawaban: <strong><?php echo strtoupper($act['jawaban_user'] ?? '-'); ?></strong>
                                    (Kunci: <?php echo strtoupper($act['jawaban_benar']); ?>)
                                </small>
                            </div>
                            <span class="badge <?php echo $act['status'] == 'benar' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $act['status'] == 'benar' ? 'Benar' : 'Salah'; ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: 
                    // Check if user can take test
                    $nilai_test = isset($user['nilai_test']) && $user['nilai_test'] !== null && $user['nilai_test'] !== '' ? (float)$user['nilai_test'] : null;
                    $can_take_test = ($user['status_pendaftaran'] == 'registrasi') || ($user['status_pendaftaran'] == 'test' && ($nilai_test === null || $nilai_test == 0));
                    
                    if ($can_take_test):
                ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada aktivitas belajar</p>
                        <a href="take_test.php" class="btn btn-primary">
                            <i class="fas fa-play me-2"></i> Mulai Test Sekarang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">Test sudah selesai dikerjakan</p>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tombol Navigasi -->
        <div class="text-center mt-3 animate" style="animation-delay: 1.1s;">
            <a href="profile.php" class="btn btn-light px-4">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Profile
            </a>
            <?php if ($user['status_pendaftaran'] == 'lulus' && $user['status_pendaftaran'] != 'daftar_ulang'): ?>
                <a href="re_registration.php" class="btn btn-success px-4 ms-2">
                    <i class="fas fa-clipboard-list me-2"></i> Daftar Ulang Sekarang
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart 1: Ringkasan Jawaban (Pie Chart)
        const answerCtx = document.getElementById('answerChart').getContext('2d');
        new Chart(answerCtx, {
            type: 'pie',
            data: {
                labels: ['Benar', 'Salah', 'Tidak Dijawab'],
                datasets: [{
                    data: [<?php echo $jawaban_benar; ?>, <?php echo $jawaban_salah; ?>, <?php echo $tidak_dijawab; ?>],
                    backgroundColor: ['#22c55e', '#ef4444', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Chart 2: Perkembangan 7 Hari
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($harian_labels); ?>,
                datasets: [{
                    label: 'Jawaban Benar',
                    data: <?php echo json_encode($harian_data); ?>,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>