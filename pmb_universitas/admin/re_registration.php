<?php
require_once '../config/config.php';
checkAdminLogin();

// Di file admin re_registration.php
if (isset($_GET['approve']) && isset($_GET['id'])) {
    $du_id = $_GET['id'];
    
    // Update status pembayaran menjadi lunas
    $update_sql = "UPDATE daftar_ulang SET status_pembayaran = 'lunas', updated_at = NOW() WHERE id_daftar_ulang = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $du_id);
    
    if ($update_stmt->execute()) {
        // Ambil user_id dari daftar_ulang
        $du_sql = "SELECT id_user FROM daftar_ulang WHERE id_daftar_ulang = ?";
        $du_stmt = $conn->prepare($du_sql);
        $du_stmt->bind_param("i", $du_id);
        $du_stmt->execute();
        $du_result = $du_stmt->get_result();
        $du_data = $du_result->fetch_assoc();
        $user_id = $du_data['id_user'];
        
        // Generate NIM otomatis
        $tahun = date('y');
        $user_sql = "SELECT program_studi FROM user WHERE id_user = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();
        
        $nim = generateNIM($tahun, $user['program_studi']);
        
        // Update user dengan NIM dan status selesai
        $final_sql = "UPDATE user SET nomor_induk = ?, status_pendaftaran = 'selesai', updated_at = NOW() WHERE id_user = ?";
        $final_stmt = $conn->prepare($final_sql);
        $final_stmt->bind_param("si", $nim, $user_id);
        $final_stmt->execute();
        
        $_SESSION['message'] = "Pembayaran approved! NIM: " . $nim . " telah digenerate otomatis.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal approve pembayaran.";
        $_SESSION['message_type'] = "error";
    }
    
    header('Location: re_registration.php');
    exit();
}

// Cek dan perbaiki tabel daftar_ulang
function checkAndFixTables($conn) {
    // Cek apakah tabel daftar_ulang ada
    $table_check = $conn->query("SHOW TABLES LIKE 'daftar_ulang'");
    
    if ($table_check->num_rows == 0) {
        // Buat tabel daftar_ulang dengan struktur yang benar
        $create_table_sql = "
        CREATE TABLE daftar_ulang (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_user INT NOT NULL,
            tanggal_daftar_ulang DATE NOT NULL,
            jenis_pembayaran VARCHAR(50) NOT NULL,
            nominal_pembayaran DECIMAL(15,2) NOT NULL,
            status_pembayaran VARCHAR(20) NOT NULL DEFAULT 'pending',
            bukti_pembayaran VARCHAR(255),
            keterangan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table_sql)) {
            die("Error creating daftar_ulang table: " . $conn->error);
        }
    }
    
    // Pastikan kolom id_user ada
    $check_id_user = $conn->query("SHOW COLUMNS FROM daftar_ulang LIKE 'id_user'");
    if ($check_id_user->num_rows == 0) {
        $conn->query("ALTER TABLE daftar_ulang ADD COLUMN id_user INT NOT NULL");
    }
    
    // Pastikan kolom lainnya ada
    $columns = [
        'tanggal_daftar_ulang' => "ALTER TABLE daftar_ulang ADD COLUMN tanggal_daftar_ulang DATE NOT NULL",
        'jenis_pembayaran' => "ALTER TABLE daftar_ulang ADD COLUMN jenis_pembayaran VARCHAR(50) NOT NULL",
        'nominal_pembayaran' => "ALTER TABLE daftar_ulang ADD COLUMN nominal_pembayaran DECIMAL(15,2) NOT NULL DEFAULT 0",
        'status_pembayaran' => "ALTER TABLE daftar_ulang ADD COLUMN status_pembayaran VARCHAR(20) NOT NULL DEFAULT 'pending'",
        'bukti_pembayaran' => "ALTER TABLE daftar_ulang ADD COLUMN bukti_pembayaran VARCHAR(255)",
        'keterangan' => "ALTER TABLE daftar_ulang ADD COLUMN keterangan TEXT"
    ];
    
    foreach ($columns as $column => $alter_sql) {
        $check = $conn->query("SHOW COLUMNS FROM daftar_ulang LIKE '$column'");
        if ($check->num_rows == 0) {
            $conn->query($alter_sql);
        }
    }
    
    // Buat tabel system_settings jika belum ada
    $settings_check = $conn->query("SHOW TABLES LIKE 'system_settings'");
    if ($settings_check->num_rows == 0) {
        $create_settings_sql = "
        CREATE TABLE system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($create_settings_sql);
        
        // Insert default settings
        $default_settings = [
            ['deadline_daftar_ulang', '2024-01-31', 'Batas waktu daftar ulang'],
            ['biaya_daftar_ulang', '2000000', 'Biaya daftar ulang'],
            ['rekening_pembayaran', 'Bank Mandiri 123-456-7890 a/n Universitas', 'Rekening untuk pembayaran']
        ];
        
        $stmt = $conn->prepare("INSERT INTO system_settings (name, value, description) VALUES (?, ?, ?)");
        foreach ($default_settings as $setting) {
            $stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// Panggil fungsi untuk cek dan perbaiki tabel
checkAndFixTables($conn);

// Get re-registration data with user information
$query = "SELECT 
    dr.id,
    dr.id_user,
    u.nama_lengkap,
    u.email,
    u.no_hp,
    u.nomor_test,
    u.status_pendaftaran,
    dr.tanggal_daftar_ulang,
    dr.jenis_pembayaran,
    dr.nominal_pembayaran,
    dr.status_pembayaran,
    dr.bukti_pembayaran,
    dr.keterangan,
    dr.created_at
FROM daftar_ulang dr
LEFT JOIN user u ON dr.id_user = u.id_user
ORDER BY dr.created_at DESC";

$result = $conn->query($query);

if (!$result) {
    die("Error query: " . $conn->error);
}

$total_daftar_ulang = $result->num_rows;

// Get statistics dengan pengecekan error
$stats = [];
$stats['total_daftar_ulang'] = $total_daftar_ulang;

$lunas_result = $conn->query("SELECT COUNT(*) as total FROM daftar_ulang WHERE status_pembayaran = 'lunas'");
$stats['lunas'] = $lunas_result ? $lunas_result->fetch_assoc()['total'] : 0;

$belum_result = $conn->query("SELECT COUNT(*) as total FROM daftar_ulang WHERE status_pembayaran = 'belum_lunas'");
$stats['belum_lunas'] = $belum_result ? $belum_result->fetch_assoc()['total'] : 0;

$pending_result = $conn->query("SELECT COUNT(*) as total FROM daftar_ulang WHERE status_pembayaran = 'pending' OR status_pembayaran IS NULL");
$stats['pending'] = $pending_result ? $pending_result->fetch_assoc()['total'] : 0;

$nominal_result = $conn->query("SELECT SUM(nominal_pembayaran) as total FROM daftar_ulang WHERE status_pembayaran = 'lunas'");
$total_nominal = $nominal_result ? $nominal_result->fetch_assoc() : ['total' => 0];
$stats['total_nominal'] = $total_nominal['total'] ?: 0;

// Get users eligible for re-registration
$eligible_query = "SELECT * FROM user WHERE status_pendaftaran = 'lulus' AND id_user NOT IN (SELECT COALESCE(id_user, 0) FROM daftar_ulang)";
$eligible_result = $conn->query($eligible_query);
$total_eligible = $eligible_result ? $eligible_result->num_rows : 0;

// Get system settings
$system_settings = [];
$settings_result = $conn->query("SELECT name, value FROM system_settings");
if ($settings_result) {
    while($setting = $settings_result->fetch_assoc()) {
        $system_settings[$setting['name']] = $setting['value'];
    }
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
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
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

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes glow {
            0% { box-shadow: 0 0 5px rgba(79,70,229,0.2); }
            50% { box-shadow: 0 0 20px rgba(79,70,229,0.4); }
            100% { box-shadow: 0 0 5px rgba(79,70,229,0.2); }
        }

        .animate-fade-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-left {
            animation: fadeInLeft 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-fade-right {
            animation: fadeInRight 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-scale {
            animation: scaleIn 0.5s ease-out forwards;
            opacity: 0;
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
            transition: all 0.3s ease;
            overflow-y: auto;
            animation: fadeInLeft 0.6s ease-out;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h4 {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .sidebar-header small {
            font-size: 0.7rem;
            opacity: 0.7;
        }

        .admin-info {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

        .admin-avatar:hover {
            transform: scale(1.1);
            animation: pulse 0.5s ease;
        }

        .admin-info h5 {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .admin-info small {
            opacity: 0.7;
            font-size: 0.7rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link i {
            width: 22px;
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .sidebar .nav-link:hover i {
            transform: translateX(5px);
        }

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

        /* ===== STAT CARDS MODERN ===== */
        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 35px -12px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
            color: #1f2937;
            background: linear-gradient(135deg, #1f2937, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
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

        /* ===== BADGES MODERN ===== */
        .badge-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .badge-modern:hover {
            transform: scale(1.05);
        }

        .badge-success { background: #d1fae5; color: #059669; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-primary { background: #e0e7ff; color: #4f46e5; }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 100px;
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-lunas { background: #d1fae5; color: #059669; }
        .status-belum_lunas { background: #fee2e2; color: #dc2626; }
        .status-pending { background: #fef3c7; color: #d97706; animation: glow 2s infinite; }

        /* ===== PAYMENT METHOD BADGE ===== */
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f3f4f6;
            color: #374151;
        }

        /* ===== AMOUNT BADGE ===== */
        .amount-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e0e7ff;
            color: #4f46e5;
        }

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
            background: transparent;
        }

        .action-btn-view { color: #3b82f6; background: #eff6ff; }
        .action-btn-view:hover { background: #3b82f6; color: white; transform: scale(1.1) rotate(5deg); }

        .action-btn-edit { color: #f59e0b; background: #fffbeb; }
        .action-btn-edit:hover { background: #f59e0b; color: white; transform: scale(1.1) rotate(5deg); }

        .action-btn-verify { color: #10b981; background: #d1fae5; }
        .action-btn-verify:hover { background: #10b981; color: white; transform: scale(1.1) rotate(5deg); }

        .action-btn-delete { color: #ef4444; background: #fef2f2; }
        .action-btn-delete:hover { background: #ef4444; color: white; transform: scale(1.1) rotate(5deg); }

        /* ===== SEARCH WRAPPER ===== */
        .search-wrapper {
            background: white;
            border-radius: 16px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .search-wrapper:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        .search-wrapper i {
            padding: 0 12px;
            color: #94a3b8;
            transition: color 0.3s ease;
        }

        .search-wrapper:focus-within i {
            color: #4f46e5;
        }

        .search-wrapper input {
            border: none;
            padding: 10px 0;
            flex: 1;
            outline: none;
            background: transparent;
        }

        /* ===== BUTTON PRIMARY ===== */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 14px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79,70,229,0.4);
        }

        .btn-outline-primary {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            color: #475569;
            background: white;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            border: none;
            border-radius: 14px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* ===== TABLE MODERN ===== */
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
            transition: all 0.3s ease;
        }

        .data-table tbody td {
            padding: 16px 20px;
            font-size: 0.85rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            transition: all 0.3s ease;
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
            transform: translateX(5px);
        }

        /* ===== MODAL MODERN ===== */
        .modal-content {
            border-radius: 24px;
            border: none;
            animation: scaleIn 0.3s ease-out;
        }

        .modal-header {
            padding: 25px 30px 0;
            border-bottom: none;
        }

        .modal-footer {
            padding: 0 30px 25px;
            border-top: none;
        }

        /* ===== ELIGIBLE HIGHLIGHT ===== */
        .eligible-highlight {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            transition: all 0.3s ease;
        }

        .eligible-highlight:hover {
            background: #dcfce7;
        }

        /* ===== LOADING SPINNER ===== */
        .spinner-border {
            animation: rotate 1s linear infinite;
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
        }

        /* ===== GLASS MORPHISM EFFECT ===== */
        .glass-effect {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* ===== HOVER GLOW ===== */
        .hover-glow {
            transition: all 0.3s ease;
        }

        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(79,70,229,0.3);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar .nav-link span,
            .sidebar-header small,
            .admin-info h5,
            .admin-info small {
                display: none;
            }
            .sidebar .nav-link i {
                margin: 0;
            }
            .admin-avatar {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Modern -->
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
                <li class="nav-item stagger-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li class="nav-item stagger-item"><a href="manage_users.php" class="nav-link"><i class="fas fa-user-plus"></i> <span>Kelola Calon</span></a></li>
                <li class="nav-item stagger-item"><a href="manage_questions.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Soal Test</span></a></li>
                <li class="nav-item stagger-item"><a href="registered_users.php" class="nav-link"><i class="fas fa-users"></i> <span>User Terdaftar</span></a></li>
                <li class="nav-item stagger-item"><a href="test_results.php" class="nav-link"><i class="fas fa-chart-line"></i> <span>Hasil Test</span></a></li>
                <li class="nav-item stagger-item"><a href="re_registration.php" class="nav-link active"><i class="fas fa-clipboard-list"></i> <span>Daftar Ulang</span></a></li>
                <li class="nav-item stagger-item"><a href="generate_nim.php" class="nav-link"><i class="fas fa-id-card"></i> <span>Generate NIM</span></a></li>
                <li class="nav-item stagger-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> <span>Laporan</span></a></li>
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div class="animate-fade-up" style="animation-delay: 0.1s">
                <h1 class="display-6 fw-bold mb-0" style="font-size: 1.8rem;">
                    <i class="fas fa-clipboard-list text-primary me-2"></i>Daftar Ulang Calon Mahasiswa
                </h1>
                <p class="text-muted mt-1 mb-0">Monitoring dan verifikasi pembayaran daftar ulang</p>
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

        <?php 
        if (isset($_SESSION['message'])) {
            $type = $_SESSION['message_type'] == 'success' ? 'success' : 'danger';
            echo '<div class="alert alert-' . $type . ' alert-dismissible fade show animate-fade-up" role="alert">
                    <i class="fas ' . ($type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle') . ' me-2"></i>
                    ' . $_SESSION['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6 stagger-item">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_eligible; ?></div>
                    <div class="stat-label">Eligible Daftar Ulang</div>
                    <div class="stat-trend text-success">
                        <i class="fas fa-user-check"></i> <span>Calon baru eligible</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 stagger-item">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['lunas']; ?></div>
                    <div class="stat-label">Pembayaran Lunas</div>
                    <div class="stat-trend text-success">
                        <i class="fas fa-percent"></i> <span><?php echo $stats['total_daftar_ulang'] > 0 ? round(($stats['lunas'] / $stats['total_daftar_ulang']) * 100, 1) : 0; ?>% dari total</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 stagger-item">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending Verifikasi</div>
                    <div class="stat-trend text-warning">
                        <i class="fas fa-spinner"></i> <span>Menunggu konfirmasi</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 stagger-item">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="fas fa-money-bill-wave text-white"></i>
                    </div>
                    <div class="stat-number">Rp <?php echo number_format($stats['total_nominal'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Pembayaran Masuk</div>
                    <div class="stat-trend text-primary">
                        <i class="fas fa-chart-line"></i> <span>Total keseluruhan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eligible Users Section -->
        <?php if($total_eligible > 0 && $eligible_result): ?>
        <div class="card-modern mb-4 animate-fade-up" style="animation-delay: 0.2s">
            <div class="card-header-modern">
                <h5>
                    <i class="fas fa-user-check text-success"></i>
                    Calon Mahasiswa Eligible untuk Daftar Ulang
                    <span class="badge-modern badge-success ms-2"><?php echo $total_eligible; ?> orang</span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Test</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($eligible = $eligible_result->fetch_assoc()): ?>
                        <tr class="eligible-highlight">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-graduate" style="color: #10b981;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($eligible['nama_lengkap']); ?></div>
                                    </div>
                                </div>
                             </div>
                            <td><?php echo htmlspecialchars($eligible['email']); ?> </div>
                            <td><?php echo $eligible['nomor_test'] ?: '-'; ?> </div>
                            <td>
                                <button class="btn btn-primary btn-sm process-registration" 
                                        data-id="<?php echo $eligible['id_user']; ?>"
                                        data-name="<?php echo htmlspecialchars($eligible['nama_lengkap']); ?>">
                                    <i class="fas fa-clipboard-check me-1"></i>Proses Daftar Ulang
                                </button>
                             </div>
                         </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card-modern animate-fade-up" style="animation-delay: 0.25s">
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
                                <li><a class="dropdown-item filter-status" href="#" data-status="lunas">✅ Lunas</a></li>
                                <li><a class="dropdown-item filter-status" href="#" data-status="belum_lunas">❌ Belum Lunas</a></li>
                                <li><a class="dropdown-item filter-status" href="#" data-status="pending">⏳ Pending</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-primary" id="addPaymentBtn">
                            <i class="fas fa-plus me-2"></i>Tambah Pembayaran
                        </button>
                        <span class="text-muted ms-2">
                            <i class="fas fa-database me-1"></i>
                            <span id="resultsCount"><?php echo $total_daftar_ulang; ?></span> data
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Table -->
        <div class="card-modern animate-scale" style="animation-delay: 0.3s">
            <div class="table-responsive">
                <table class="data-table" id="registrationTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Peserta</th>
                            <th>Tgl Daftar Ulang</th>
                            <th>Jenis Pembayaran</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($total_daftar_ulang > 0 && $result): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user-graduate" style="color: #4f46e5;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['nama_lengkap'] ?? 'Tidak Diketahui'); ?></div>
                                            <small class="text-muted"><?php echo $row['nomor_test'] ?? '-'; ?></small>
                                        </div>
                                    </div>
                                 </div>
                                 <td><?php echo date('d/m/Y', strtotime($row['tanggal_daftar_ulang'])); ?> </div>
                                 <td>
                                    <span class="payment-method-badge">
                                        <i class="fas fa-credit-card me-1"></i>
                                        <?php echo $row['jenis_pembayaran']; ?>
                                    </span>
                                  </div>
                                 <td>
                                    <span class="amount-badge">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        Rp <?php echo number_format($row['nominal_pembayaran'], 0, ',', '.'); ?>
                                    </span>
                                  </div>
                                 <td>
                                    <span class="status-badge status-<?php echo $row['status_pembayaran']; ?>">
                                        <i class="fas <?php echo $row['status_pembayaran'] == 'lunas' ? 'fa-check-circle' : ($row['status_pembayaran'] == 'belum_lunas' ? 'fa-times-circle' : 'fa-spinner'); ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $row['status_pembayaran'])); ?>
                                    </span>
                                  </div>
                                 <td>
                                    <div class="d-flex gap-1">
                                        <button class="action-btn action-btn-edit edit-payment" data-id="<?php echo $row['id']; ?>" title="Edit Pembayaran">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn action-btn-verify verify-payment" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" title="Verifikasi Pembayaran">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn action-btn-delete delete-registration" data-id="<?php echo $row['id']; ?>" title="Hapus Data">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                  </div>
                             </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    <p class="text-muted mb-0">Belum ada data daftar ulang</p>
                                 </div>
                             </div>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Information Section -->
        <div class="row g-4 mt-2 animate-fade-up" style="animation-delay: 0.35s">
            <div class="col-md-12">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5>
                            <i class="fas fa-info-circle text-primary"></i>
                            Informasi Daftar Ulang
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-calendar me-2 text-primary"></i>Batas Waktu Daftar Ulang
                                    </small>
                                    <span class="fw-semibold">
                                        <?php 
                                            echo isset($system_settings['deadline_daftar_ulang']) ? 
                                                date('d F Y', strtotime($system_settings['deadline_daftar_ulang'])) : 
                                                '31 Januari ' . date('Y');
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-money-bill me-2 text-primary"></i>Biaya Daftar Ulang
                                    </small>
                                    <span class="fw-semibold">
                                        <?php 
                                            echo isset($system_settings['biaya_daftar_ulang']) ? 
                                                'Rp ' . number_format($system_settings['biaya_daftar_ulang'], 0, ',', '.') : 
                                                'Rp 2.000.000';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-university me-2 text-primary"></i>Rekening Pembayaran
                                    </small>
                                    <span class="fw-semibold">
                                        <?php 
                                            echo isset($system_settings['rekening_pembayaran']) ? 
                                                $system_settings['rekening_pembayaran'] : 
                                                'Bank Mandiri 123-456-7890 a/n Universitas';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Registration Modal -->
    <div class="modal fade" id="processRegistrationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-clipboard-check me-2 text-primary"></i>Proses Daftar Ulang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 20px 30px;">
                    <form id="registrationForm">
                        <input type="hidden" id="userId" name="user_id">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Calon Mahasiswa</label>
                            <input type="text" class="form-control" id="userName" readonly style="background: #f8fafc;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Daftar Ulang <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggalDaftarUlang" name="tanggal_daftar_ulang" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select" id="jenisPembayaran" name="jenis_pembayaran" required>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="Tunai">Tunai</option>
                                <option value="Kartu Kredit">Kartu Kredit</option>
                                <option value="Virtual Account">Virtual Account</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Pembayaran <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="nominalPembayaran" name="nominal_pembayaran" 
                                   value="<?php echo isset($system_settings['biaya_daftar_ulang']) ? $system_settings['biaya_daftar_ulang'] : 2000000; ?>" 
                                   placeholder="Masukkan nominal pembayaran" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select" id="statusPembayaran" name="status_pembayaran" required>
                                <option value="pending">Pending</option>
                                <option value="lunas">Lunas</option>
                                <option value="belum_lunas">Belum Lunas</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                      placeholder="Masukkan keterangan tambahan jika ada"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveRegistrationBtn">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            <?php if($total_daftar_ulang > 0): ?>
            // Initialize DataTable
            var table = $('#registrationTable').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "",
                    "searchPlaceholder": "Cari...",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "zeroRecords": "Data tidak ditemukan",
                    "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    "infoEmpty": "Tidak ada data",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "paginate": {
                        "first": "«",
                        "last": "»",
                        "next": "›",
                        "previous": "‹"
                    }
                },
                "dom": '<"top"f>rt<"bottom"lip>',
                "columnDefs": [
                    { "orderable": false, "targets": [6] }
                ]
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
            
            // Process registration
            $('.process-registration').on('click', function() {
                var userId = $(this).data('id');
                var userName = $(this).data('name');
                
                $('#userId').val(userId);
                $('#userName').val(userName);
                
                $('#processRegistrationModal').modal('show');
            });
            
            // Save registration
            $('#saveRegistrationBtn').on('click', function() {
                var formData = $('#registrationForm').serialize();
                
                $.ajax({
                    url: 'process_registration.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        alert('✅ Daftar ulang berhasil diproses!');
                        $('#processRegistrationModal').modal('hide');
                        location.reload();
                    },
                    error: function() {
                        alert('❌ Terjadi kesalahan pada server.');
                    }
                });
            });
            
            // Edit payment
            $('.edit-payment').on('click', function() {
                var registrationId = $(this).data('id');
                var newStatus = prompt('Masukkan status baru (lunas/belum_lunas/pending):', 'lunas');
                if(newStatus && ['lunas', 'belum_lunas', 'pending'].includes(newStatus.toLowerCase())) {
                    $.ajax({
                        url: 'update_payment_status.php',
                        type: 'POST',
                        data: { id: registrationId, status: newStatus.toLowerCase() },
                        success: function(response) {
                            alert('✅ Status berhasil diperbarui!');
                            location.reload();
                        },
                        error: function() {
                            alert('❌ Terjadi kesalahan saat memperbarui status.');
                        }
                    });
                }
            });
            
            // Verify payment
            $('.verify-payment').on('click', function() {
                var registrationId = $(this).data('id');
                var userName = $(this).data('name');
                
                if(confirm('Verifikasi pembayaran dari ' + userName + ' sebagai LUNAS?')) {
                    $.ajax({
                        url: 'verify_payment.php',
                        type: 'POST',
                        data: { id: registrationId },
                        success: function(response) {
                            alert('✅ Pembayaran berhasil diverifikasi!');
                            location.reload();
                        },
                        error: function() {
                            alert('❌ Terjadi kesalahan saat memverifikasi pembayaran.');
                        }
                    });
                }
            });
            
            // Delete registration
            $('.delete-registration').on('click', function() {
                var registrationId = $(this).data('id');
                
                if(confirm('⚠️ Apakah Anda yakin ingin menghapus data daftar ulang ini?')) {
                    $.ajax({
                        url: 'delete_registration.php',
                        type: 'POST',
                        data: { id: registrationId },
                        success: function(response) {
                            alert('✅ Data berhasil dihapus!');
                            location.reload();
                        },
                        error: function() {
                            alert('❌ Terjadi kesalahan saat menghapus data.');
                        }
                    });
                }
            });
            
            // Add payment button
            $('#addPaymentBtn').on('click', function() {
                $('#userId').val('');
                $('#userName').val('');
                $('#registrationForm')[0].reset();
                $('#tanggalDaftarUlang').val('<?php echo date('Y-m-d'); ?>');
                $('#nominalPembayaran').val(<?php echo isset($system_settings['biaya_daftar_ulang']) ? $system_settings['biaya_daftar_ulang'] : 2000000; ?>);
                $('#processRegistrationModal').modal('show');
            });
            
            // Refresh button
            $('#refreshBtn').on('click', function() {
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
                setTimeout(function() {
                    location.reload();
                }, 500);
            });
            
            // Export
            $('#exportExcel').on('click', function(e) {
                e.preventDefault();
                alert('Fitur export Excel akan segera hadir!');
            });
            
            $('#exportPrint').on('click', function(e) {
                e.preventDefault();
                window.print();
            });
            
            // Add animation to table rows on load
            $('.data-table tbody tr').each(function(index) {
                $(this).css('animation', 'fadeInUp 0.3s ease-out forwards');
                $(this).css('animation-delay', (index * 0.02) + 's');
                $(this).css('opacity', '0');
            });
        });
    </script>
</body>
</html>