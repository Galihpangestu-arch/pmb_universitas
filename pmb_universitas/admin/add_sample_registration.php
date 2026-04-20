<?php
require_once '../config.php';
checkAdminLogin();

// Sample data untuk daftar_ulang
$sample_data = [
    [1, '2024-01-20', 'Transfer Bank', 2000000.00, 'lunas', 'Pembayaran via Mandiri'],
    [2, '2024-01-21', 'Tunai', 2000000.00, 'pending', 'Menunggu konfirmasi'],
    [3, '2024-01-22', 'Virtual Account', 2000000.00, 'belum_lunas', 'Pembayaran sebagian'],
    [4, '2024-01-23', 'Kartu Kredit', 2000000.00, 'lunas', 'Lunas sepenuhnya']
];

$stmt = $conn->prepare("
    INSERT INTO daftar_ulang 
    (id_user, tanggal_daftar_ulang, jenis_pembayaran, nominal_pembayaran, status_pembayaran, keterangan) 
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($sample_data as $data) {
    $stmt->bind_param("issdss", 
        $data[0], // id_user
        $data[1], // tanggal_daftar_ulang
        $data[2], // jenis_pembayaran
        $data[3], // nominal_pembayaran
        $data[4], // status_pembayaran
        $data[5]  // keterangan
    );
    $stmt->execute();
}

$stmt->close();

echo "success";
?>