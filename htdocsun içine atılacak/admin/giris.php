<?php
session_start(); // 🔴 EKLENDİ
include 'db.php';

$kullanici = $_POST['kullanici_adi'];
$sifre = $_POST['sifre'];

$sql = "SELECT * FROM adminler WHERE kullanici_adi = ? AND sifre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $kullanici, $sifre);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $_SESSION['admin'] = true; // 🔴 OTURUM TANIMLANDI
    header("Location: panel.php");
} else {
    header("Location: index.php?error=1");
}
exit;
?>
