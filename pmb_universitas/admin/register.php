<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
</head>
<body>

<h2>Registrasi Admin</h2>

<form action="simpan_admin.php" method="POST">
    <label>Nama Admin</label><br>
    <input type="text" name="nama_admin" required><br><br>

    <label>Username</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit" name="simpan">Daftar</button>
</form>

</body>
</html>
