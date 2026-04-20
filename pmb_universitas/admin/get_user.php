<?php
require_once '../config.php';
checkAdminLogin();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

if ($action == 'view') {
    $sql = "SELECT * FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $status_class = [
            'registrasi' => 'warning',
            'test' => 'info',
            'lulus' => 'success',
            'tidak_lulus' => 'danger',
            'daftar_ulang' => 'primary',
            'selesai' => 'dark'
        ][$user['status_pendaftaran']];
        ?>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Nama Lengkap</th>
                        <td>: <?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                    </tr>
                    <tr>
                        <th>Username</th>
                        <td>: <?php echo htmlspecialchars($user['username']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>: <?php echo htmlspecialchars($user['email']); ?></td>
                    </tr>
                    <tr>
                        <th>No. HP</th>
                        <td>: <?php echo htmlspecialchars($user['no_hp']); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Lahir</th>
                        <td>: <?php echo date('d/m/Y', strtotime($user['tanggal_lahir'])); ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Kelamin</th>
                        <td>: <?php echo $user['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Asal Sekolah</th>
                        <td>: <?php echo htmlspecialchars($user['asal_sekolah']); ?></td>
                    </tr>
                    <tr>
                        <th>Tahun Lulus</th>
                        <td>: <?php echo $user['tahun_lulus']; ?></td>
                    </tr>
                    <tr>
                        <th>Program Studi</th>
                        <td>: <?php echo htmlspecialchars($user['program_studi']); ?></td>
                    </tr>
                    <tr>
                        <th>Nomor Test</th>
                        <td>: <?php echo $user['nomor_test']; ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>: 
                            <span class="badge bg-<?php echo $status_class; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $user['status_pendaftaran'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Nilai Test</th>
                        <td>: 
                            <?php if ($user['nilai_test']): ?>
                                <span class="badge bg-<?php echo $user['nilai_test'] >= 70 ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($user['nilai_test'], 2); ?>%
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum test</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td>: <?php echo nl2br(htmlspecialchars($user['alamat'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
} elseif ($action == 'edit') {
    $sql = "SELECT * FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        ?>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" class="form-control" name="nama_lengkap" 
                           value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">No. HP *</label>
                    <input type="tel" class="form-control" name="no_hp" 
                           value="<?php echo htmlspecialchars($user['no_hp']); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Alamat *</label>
            <textarea class="form-control" name="alamat" rows="2" required><?php echo htmlspecialchars($user['alamat']); ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Tanggal Lahir *</label>
                    <input type="date" class="form-control" name="tanggal_lahir" 
                           value="<?php echo $user['tanggal_lahir']; ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Jenis Kelamin *</label>
                    <select class="form-select" name="jenis_kelamin" required>
                        <option value="L" <?php echo $user['jenis_kelamin'] == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="P" <?php echo $user['jenis_kelamin'] == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Asal Sekolah *</label>
                    <input type="text" class="form-control" name="asal_sekolah" 
                           value="<?php echo htmlspecialchars($user['asal_sekolah']); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Tahun Lulus *</label>
                    <input type="number" class="form-control" name="tahun_lulus" 
                           value="<?php echo $user['tahun_lulus']; ?>" min="2000" max="<?php echo date('Y'); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Program Studi *</label>
                    <select class="form-select" name="program_studi" required>
                        <option value="Teknik Informatika" <?php echo $user['program_studi'] == 'Teknik Informatika' ? 'selected' : ''; ?>>Teknik Informatika</option>
                        <option value="Sistem Informasi" <?php echo $user['program_studi'] == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
                        <option value="Teknik Elektro" <?php echo $user['program_studi'] == 'Teknik Elektro' ? 'selected' : ''; ?>>Teknik Elektro</option>
                        <option value="Teknik Mesin" <?php echo $user['program_studi'] == 'Teknik Mesin' ? 'selected' : ''; ?>>Teknik Mesin</option>
                        <option value="Akuntansi" <?php echo $user['program_studi'] == 'Akuntansi' ? 'selected' : ''; ?>>Akuntansi</option>
                        <option value="Manajemen" <?php echo $user['program_studi'] == 'Manajemen' ? 'selected' : ''; ?>>Manajemen</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Status Pendaftaran</label>
                    <select class="form-select" name="status_pendaftaran">
                        <option value="registrasi" <?php echo $user['status_pendaftaran'] == 'registrasi' ? 'selected' : ''; ?>>Registrasi</option>
                        <option value="test" <?php echo $user['status_pendaftaran'] == 'test' ? 'selected' : ''; ?>>Test</option>
                        <option value="lulus" <?php echo $user['status_pendaftaran'] == 'lulus' ? 'selected' : ''; ?>>Lulus</option>
                        <option value="tidak_lulus" <?php echo $user['status_pendaftaran'] == 'tidak_lulus' ? 'selected' : ''; ?>>Tidak Lulus</option>
                        <option value="daftar_ulang" <?php echo $user['status_pendaftaran'] == 'daftar_ulang' ? 'selected' : ''; ?>>Daftar Ulang</option>
                        <option value="selesai" <?php echo $user['status_pendaftaran'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Nomor Test</label>
                    <input type="text" class="form-control" name="nomor_test" 
                           value="<?php echo $user['nomor_test']; ?>" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" class="form-control" name="password">
                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                </div>
            </div>
        </div>
        <?php
    }
} elseif ($action == 'get_status') {
    $sql = "SELECT status_pendaftaran FROM user WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    echo json_encode(['status' => $user['status_pendaftaran']]);
}
?>