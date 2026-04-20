<?php
require_once '../config/config.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? $conn->real_escape_string($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        // Cek user
        $sql = "SELECT id_user, password FROM user WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id_user'];
                // Update last_login
                $update = $conn->prepare("UPDATE user SET last_login = NOW() WHERE id_user = ?");
                $update->bind_param("i", $user['id_user']);
                $update->execute();
                redirect('dashboard.php');
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username atau email tidak ditemukan!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PMB Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fas fa-university fa-3x text-primary mb-3"></i>
            <h2>Login Mahasiswa</h2>
            <p class="text-muted">PMB Universitas Nusantara</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username atau Email</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="../register.php">Belum punya akun? Daftar</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>