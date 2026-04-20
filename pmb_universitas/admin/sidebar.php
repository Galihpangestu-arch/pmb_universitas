<div class="sidebar d-flex flex-column">
    <div class="sidebar-header">
        <h4 class="mb-0">PMB Admin</h4>
        <small>Administrator Sistem</small>
    </div>
    
    <div class="flex-grow-1">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> Kelola Calon Maba
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_questions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_questions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle me-2"></i> Kelola Soal Test
                </a>
            </li>
            <li class="nav-item">
                <a href="registered_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'registered_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list me-2"></i> User Terdaftar
                </a>
            </li>
            <li class="nav-item">
                <a href="test_results.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'test_results.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> Hasil Test
                </a>
            </li>
            <li class="nav-item">
                <a href="re_registration.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 're_registration.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check me-2"></i> Daftar Ulang
                </a>
            </li>
            <li class="nav-item">
                <a href="generate_nim.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'generate_nim.php' ? 'active' : ''; ?>">
                    <i class="fas fa-id-card me-2"></i> Generate NIM
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie me-2"></i> Laporan
                </a>
            </li>
        </ul>
    </div>
    
    <div class="p-3 border-top border-white-10">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <small>Login sebagai:</small>
                <div class="fw-bold"><?php echo $_SESSION['admin_nama']; ?></div>
            </div>
            <a href="../logout.php" class="btn btn-sm btn-outline-light">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</div>