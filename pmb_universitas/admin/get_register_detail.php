<?php
require_once '../config.php';
checkAdminLogin();

if(isset($_GET['id'])) {
    $id_daftar_ulang = $_GET['id'];
    
    $query = "SELECT 
        dr.*,
        u.nama_lengkap,
        u.email,
        u.no_hp,
        u.nomor_test,
        u.alamat,
        u.status_pendaftaran
    FROM daftar_ulang dr
    LEFT JOIN user u ON dr.id_user = u.id_user
    WHERE dr.id_daftar_ulang = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_daftar_ulang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()):
?>
<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Informasi Calon Mahasiswa</h5>
        <table class="table table-bordered">
            <tr>
                <th width="40%">Nama Lengkap</th>
                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
            </tr>
            <tr>
                <th>No. HP</th>
                <td><?php echo $row['no_hp']; ?></td>
            </tr>
            <tr>
                <th>No. Test</th>
                <td><?php echo $row['nomor_test'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>Status Pendaftaran</th>
                <td>
                    <span class="badge bg-success">
                        <?php echo ucwords(str_replace('_', ' ', $row['status_pendaftaran'])); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h5 class="mb-3">Informasi Pembayaran</h5>
        <table class="table table-bordered">
            <tr>
                <th width="40%">Tanggal Daftar Ulang</th>
                <td><?php echo date('d F Y', strtotime($row['tanggal_daftar_ulang'])); ?></td>
            </tr>
            <tr>
                <th>Jenis Pembayaran</th>
                <td>
                    <span class="badge bg-info">
                        <?php echo $row['jenis_pembayaran']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Nominal</th>
                <td class="fw-bold text-success">
                    Rp <?php echo number_format($row['nominal_pembayaran'], 0, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <th>Status Pembayaran</th>
                <td>
                    <?php 
                    $status_color = $row['status_pembayaran'] == 'lunas' ? 'success' : 
                                  ($row['status_pembayaran'] == 'belum_lunas' ? 'danger' : 'warning');
                    ?>
                    <span class="badge bg-<?php echo $status_color; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $row['status_pembayaran'])); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Bukti Pembayaran</th>
                <td>
                    <?php if($row['bukti_pembayaran']): ?>
                    <span class="badge bg-secondary"><?php echo $row['bukti_pembayaran']; ?></span>
                    <?php else: ?>
                    <span class="text-muted">Tidak ada bukti</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if($row['keterangan']): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <h5 class="mb-3">Keterangan</h5>
        <div class="card">
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($row['keterangan'])); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Informasi:</strong> Data ini dicatat pada 
            <?php echo date('d F Y H:i', strtotime($row['created_at'])); ?>
        </div>
    </div>
</div>
<?php
    else:
        echo '<div class="alert alert-danger">Data tidak ditemukan!</div>';
    endif;
    
    $stmt->close();
} else {
    echo '<div class="alert alert-danger">ID tidak valid!</div>';
}
?>