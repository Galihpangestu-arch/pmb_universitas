<?php
// config/config.php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Laragon default password kosong
define('DB_NAME', 'pmb_universitas');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']) || (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// ================= FLASH MESSAGE FUNCTIONS =================

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        
        $alertClass = '';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            case 'info':
                $alertClass = 'alert-info';
                break;
            default:
                $alertClass = 'alert-info';
        }
        
        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

// Alias untuk showFlash
function displayMessage() {
    showFlash();
}

// ================= AUTH FUNCTIONS =================

function checkLogin() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        redirect('../login.php');
        exit();
    }
}

function checkAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        redirect('../admin/login.php');
        exit();
    }
}

function checkUserLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('../login.php');
        exit();
    }
}

// ================= OTHER HELPER FUNCTIONS =================

function sanitize($data) {
    global $conn;
    return htmlspecialchars(trim($conn->real_escape_string($data)));
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function formatTanggal($date, $format = 'd F Y') {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $hari = date('d', $timestamp);
    $bln = (int)date('m', $timestamp);
    $tahun = date('Y', $timestamp);
    
    if ($format == 'd F Y') {
        return $hari . ' ' . $bulan[$bln] . ' ' . $tahun;
    }
    
    return date($format, $timestamp);
}

// Fungsi untuk generate nomor test
function generateNomorTest() {
    return 'TEST-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Fungsi untuk generate NIM
function generateNIM($tahun, $program_studi) {
    $kode = strtoupper(substr($program_studi, 0, 3));
    return $kode . substr($tahun, -2) . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
}
?>