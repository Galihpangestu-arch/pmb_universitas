<?php
require_once '../config/config.php';
checkAdminLogin();

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'delete':
            $sql = "DELETE FROM soal_test WHERE id_soal = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Soal berhasil dihapus';
                $_SESSION['message_type'] = 'success';
            }
            break;
            
        case 'toggle_active':
            $sql = "UPDATE soal_test SET aktif = IF(aktif = 'Y', 'N', 'Y') WHERE id_soal = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
    }
    header('Location: manage_questions.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_soal = $_POST['id_soal'] ?? 0;
    $pertanyaan = sanitize($_POST['pertanyaan']);
    $pilihan_a = sanitize($_POST['pilihan_a']);
    $pilihan_b = sanitize($_POST['pilihan_b']);
    $pilihan_c = sanitize($_POST['pilihan_c']);
    $pilihan_d = sanitize($_POST['pilihan_d']);
    $jawaban_benar = sanitize($_POST['jawaban_benar']);
    
    if ($id_soal > 0) {
        $sql = "UPDATE soal_test SET 
                pertanyaan = ?, pilihan_a = ?, pilihan_b = ?, pilihan_c = ?, pilihan_d = ?,
                jawaban_benar = ? WHERE id_soal = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, 
                         $pilihan_d, $jawaban_benar, $id_soal);
    } else {
        $sql = "INSERT INTO soal_test (pertanyaan, pilihan_a, pilihan_b, pilihan_c, 
                pilihan_d, jawaban_benar) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, 
                         $pilihan_d, $jawaban_benar);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = $id_soal > 0 ? 'Soal berhasil diupdate' : 'Soal berhasil ditambahkan';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: manage_questions.php');
    exit();
}

// Get questions
$result = $conn->query("SELECT * FROM soal_test ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal Test - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .main-content {
            padding: 20px;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .question-card {
            transition: transform 0.2s;
            border: 1px solid #dee2e6;
            height: 100%;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .question-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
        }
        
        .question-body {
            padding: 15px;
        }
        
        .option {
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 5px;
            border-left: 3px solid transparent;
        }
        
        .option-correct {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        
        .action-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .modal-lg {
            max-width: 800px;
        }

        .gap-2 {
            gap: 0.5rem;
        }
        
        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header dengan Tombol Back -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">Kelola Soal Test</h2>
                    <p class="mb-0">Manajemen soal untuk ujian masuk PMB</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="manage_questions.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <button class="btn btn-light" onclick="addNewQuestion()">
                        <i class="fas fa-plus me-2"></i>Tambah Soal Baru
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Display Message -->
        <?php displayMessage(); ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number"><?php echo $result->num_rows; ?></div>
                            <div class="stat-label">Total Soal</div>
                        </div>
                        <i class="fas fa-question-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number">
                                <?php 
                                $active = $conn->query("SELECT COUNT(*) FROM soal_test WHERE aktif = 'Y'")->fetch_row()[0];
                                echo $active;
                                ?>
                            </div>
                            <div class="stat-label">Soal Aktif</div>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number">
                                <?php 
                                $used = $conn->query("SELECT COUNT(DISTINCT id_soal) FROM hasil_test")->fetch_row()[0];
                                echo $used;
                                ?>
                            </div>
                            <div class="stat-label">Soal Terpakai</div>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number">
                                <?php 
                                $new = $conn->query("SELECT COUNT(*) FROM soal_test WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];
                                echo $new;
                                ?>
                            </div>
                            <div class="stat-label">Soal Hari Ini</div>
                        </div>
                        <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Questions List -->
        <?php if ($result->num_rows == 0): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-question-circle fa-5x text-muted mb-3"></i>
                <h4 class="text-muted mb-3">Belum Ada Soal</h4>
                <p class="text-muted mb-4">Klik tombol "Tambah Soal Baru" untuk menambahkan soal pertama</p>
                <button class="btn btn-primary btn-lg" onclick="addNewQuestion()">
                    <i class="fas fa-plus me-2"></i>Tambah Soal
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <?php 
            $counter = 1;
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()): 
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card question-card position-relative">
                    <div class="question-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Soal #<?php echo $counter++; ?></h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" onclick="editQuestion(<?php echo $row['id_soal']; ?>)">
                                            <i class="fas fa-edit me-2 text-primary"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="?action=toggle_active&id=<?php echo $row['id_soal']; ?>">
                                            <?php if ($row['aktif'] == 'Y'): ?>
                                                <i class="fas fa-toggle-on me-2 text-success"></i>Nonaktifkan
                                            <?php else: ?>
                                                <i class="fas fa-toggle-off me-2 text-secondary"></i>Aktifkan
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="?action=delete&id=<?php echo $row['id_soal']; ?>"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus soal ini?')">
                                            <i class="fas fa-trash me-2"></i>Hapus
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="question-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($row['pertanyaan'], 0, 100))) . (strlen($row['pertanyaan']) > 100 ? '...' : ''); ?></p>
                        
                        <div class="options mt-3">
                            <div class="option <?php echo $row['jawaban_benar'] == 'a' ? 'option-correct' : ''; ?>">
                                <span class="badge bg-danger me-2">A</span>
                                <?php echo htmlspecialchars(substr($row['pilihan_a'], 0, 30)) . (strlen($row['pilihan_a']) > 30 ? '...' : ''); ?>
                                <?php if ($row['jawaban_benar'] == 'a'): ?>
                                    <i class="fas fa-check text-success ms-2"></i>
                                <?php endif; ?>
                            </div>
                            <div class="option <?php echo $row['jawaban_benar'] == 'b' ? 'option-correct' : ''; ?>">
                                <span class="badge bg-primary me-2">B</span>
                                <?php echo htmlspecialchars(substr($row['pilihan_b'], 0, 30)) . (strlen($row['pilihan_b']) > 30 ? '...' : ''); ?>
                                <?php if ($row['jawaban_benar'] == 'b'): ?>
                                    <i class="fas fa-check text-success ms-2"></i>
                                <?php endif; ?>
                            </div>
                            <div class="option <?php echo $row['jawaban_benar'] == 'c' ? 'option-correct' : ''; ?>">
                                <span class="badge bg-success me-2">C</span>
                                <?php echo htmlspecialchars(substr($row['pilihan_c'], 0, 30)) . (strlen($row['pilihan_c']) > 30 ? '...' : ''); ?>
                                <?php if ($row['jawaban_benar'] == 'c'): ?>
                                    <i class="fas fa-check text-success ms-2"></i>
                                <?php endif; ?>
                            </div>
                            <div class="option <?php echo $row['jawaban_benar'] == 'd' ? 'option-correct' : ''; ?>">
                                <span class="badge bg-warning me-2">D</span>
                                <?php echo htmlspecialchars(substr($row['pilihan_d'], 0, 30)) . (strlen($row['pilihan_d']) > 30 ? '...' : ''); ?>
                                <?php if ($row['jawaban_benar'] == 'd'): ?>
                                    <i class="fas fa-check text-success ms-2"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                            </small>
                            <span class="badge <?php echo $row['aktif'] == 'Y' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $row['aktif'] == 'Y' ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Question Modal (Add/Edit) -->
    <div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="questionForm">
                    <input type="hidden" name="id_soal" id="id_soal">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle me-2"></i>Tambah Soal Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pertanyaan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="pertanyaan" id="pertanyaan" rows="4" 
                                      placeholder="Masukkan pertanyaan..." required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pilihan A <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-danger text-white">A</span>
                                    <input type="text" class="form-control" name="pilihan_a" id="pilihan_a" 
                                           placeholder="Masukkan pilihan A..." required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pilihan B <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white">B</span>
                                    <input type="text" class="form-control" name="pilihan_b" id="pilihan_b" 
                                           placeholder="Masukkan pilihan B..." required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pilihan C <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">C</span>
                                    <input type="text" class="form-control" name="pilihan_c" id="pilihan_c" 
                                           placeholder="Masukkan pilihan C..." required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pilihan D <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-warning text-white">D</span>
                                    <input type="text" class="form-control" name="pilihan_d" id="pilihan_d" 
                                           placeholder="Masukkan pilihan D..." required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jawaban Benar <span class="text-danger">*</span></label>
                            <select class="form-select" name="jawaban_benar" id="jawaban_benar" required>
                                <option value="">Pilih Jawaban Benar</option>
                                <option value="a">Pilihan A</option>
                                <option value="b">Pilihan B</option>
                                <option value="c">Pilihan C</option>
                                <option value="d">Pilihan D</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Informasi:</strong> Pastikan semua field terisi dengan benar sebelum menyimpan.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i class="fas fa-save me-2"></i>Simpan Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Store modal instance
        let questionModal;
        
        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            questionModal = new bootstrap.Modal(document.getElementById('questionModal'));
            
            // Add input event for auto-resize textarea
            document.getElementById('pertanyaan').addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
        
        // Function to add new question
        function addNewQuestion() {
            // Reset form
            document.getElementById('questionForm').reset();
            document.getElementById('id_soal').value = '';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Tambah Soal Baru';
            document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save me-2"></i>Simpan Soal';
            
            // Reset textarea height
            const textarea = document.getElementById('pertanyaan');
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            
            // Show modal
            questionModal.show();
        }
        
        // Function to edit question
        function editQuestion(id) {
            // Show loading state
            Swal.fire({
                title: 'Loading...',
                text: 'Mengambil data soal',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Fetch question data
            fetch(`get_question.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    // Fill form with data
                    document.getElementById('id_soal').value = data.id_soal;
                    document.getElementById('pertanyaan').value = data.pertanyaan;
                    document.getElementById('pilihan_a').value = data.pilihan_a;
                    document.getElementById('pilihan_b').value = data.pilihan_b;
                    document.getElementById('pilihan_c').value = data.pilihan_c;
                    document.getElementById('pilihan_d').value = data.pilihan_d;
                    document.getElementById('jawaban_benar').value = data.jawaban_benar;
                    
                    // Update modal title and button
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Soal';
                    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save me-2"></i>Update Soal';
                    
                    // Adjust textarea height
                    const textarea = document.getElementById('pertanyaan');
                    textarea.style.height = 'auto';
                    textarea.style.height = (textarea.scrollHeight) + 'px';
                    
                    // Show modal
                    questionModal.show();
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    alert('Gagal mengambil data soal. Silahkan coba lagi.');
                });
        }
        
        // Form validation
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            const pertanyaan = document.getElementById('pertanyaan').value.trim();
            const pilihan_a = document.getElementById('pilihan_a').value.trim();
            const pilihan_b = document.getElementById('pilihan_b').value.trim();
            const pilihan_c = document.getElementById('pilihan_c').value.trim();
            const pilihan_d = document.getElementById('pilihan_d').value.trim();
            const jawaban = document.getElementById('jawaban_benar').value;
            
            let errorMessage = '';
            
            if (!pertanyaan) errorMessage = 'Pertanyaan harus diisi!';
            else if (!pilihan_a) errorMessage = 'Pilihan A harus diisi!';
            else if (!pilihan_b) errorMessage = 'Pilihan B harus diisi!';
            else if (!pilihan_c) errorMessage = 'Pilihan C harus diisi!';
            else if (!pilihan_d) errorMessage = 'Pilihan D harus diisi!';
            else if (!jawaban) errorMessage = 'Jawaban benar harus dipilih!';
            
            // Check for duplicate options
            const options = [pilihan_a, pilihan_b, pilihan_c, pilihan_d];
            const uniqueOptions = [...new Set(options)];
            
            if (uniqueOptions.length !== options.length) {
                errorMessage = 'Pilihan tidak boleh ada yang sama!';
            }
            
            if (errorMessage) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }
            
            return true;
        });
        
        // Clear form when modal is hidden
        document.getElementById('questionModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('questionForm').reset();
            document.getElementById('id_soal').value = '';
        });
    </script>
</body>
</html>