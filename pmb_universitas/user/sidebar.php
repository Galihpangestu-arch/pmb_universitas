<div class="sidebar d-flex flex-column">
    <div class="sidebar-header text-center p-3">
        <h4 class="mb-0">PMB Mahasiswa</h4>
        <small>Dashboard Calon Mahasiswa</small>
    </div>
    
    <div class="user-info text-center p-3">
        <div class="mb-3">
            <i class="fas fa-user-circle fa-3x"></i>
        </div>
        <h5 class="mb-1"><?php echo $user['nama_lengkap']; ?></h5>
        <small><?php echo $user['nomor_test'] ?: 'Belum ada nomor test'; ?></small>
    </div>
    
    <div class="flex-grow-1">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <?php
            $nilai_test = isset($user['nilai_test']) && $user['nilai_test'] !== null && $user['nilai_test'] !== '' ? (float)$user['nilai_test'] : null;
            $can_take_test = ($user['status_pendaftaran'] == 'registrasi') || ($user['status_pendaftaran'] == 'test' && ($nilai_test === null || $nilai_test == 0));
            if ($can_take_test):
            ?>
            <li class="nav-item">
                <a href="take_test.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'take_test.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt me-2"></i> Test Online
                </a>
            </li>
            <?php endif; ?>
            <?php if ($user['status_pendaftaran'] == 'lulus' || $user['status_pendaftaran'] == 'tidak_lulus'): ?>
            <li class="nav-item">
                <a href="test_result.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'test_result.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> Hasil Test
                </a>
            </li>
            <?php endif; ?>
            <?php if ($user['status_pendaftaran'] == 'lulus'): ?>
            <li class="nav-item">
                <a href="re_registration.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 're_registration.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check me-2"></i> Daftar Ulang
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user me-2"></i> Profil Saya
                </a>
            </li>
        </ul>
    </div>
    
    <div class="p-3 border-top border-white-10">
        <a href="../logout.php" class="btn btn-outline-light w-100">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>