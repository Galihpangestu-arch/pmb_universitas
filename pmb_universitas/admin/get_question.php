<?php
require_once '../config.php';
checkAdminLogin();

$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM soal_test WHERE id_soal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
}
?>