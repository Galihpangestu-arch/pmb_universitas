<?php
require_once 'config/config.php';

echo "<h2>Test Login User</h2>";

$username = 'ahmadfauzi';
$password = '123456';

$sql = "SELECT * FROM user WHERE username = '$username'";
$result = $conn->query($sql);

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    
    echo "Username: " . $user['username'] . "<br>";
    echo "Password di database: " . $user['password'] . "<br>";
    echo "MD5 dari '123456': " . md5('123456') . "<br>";
    echo "Password cocok: " . (md5($password) === $user['password'] ? '✅ YA' : '❌ TIDAK') . "<br>";
    
    if (md5($password) === $user['password']) {
        echo "<h3 style='color:green'>✅ Login berhasil!</h3>";
    } else {
        echo "<h3 style='color:red'>❌ Login gagal! Password tidak cocok.</h3>";
    }
} else {
    echo "User tidak ditemukan!";
}
?>