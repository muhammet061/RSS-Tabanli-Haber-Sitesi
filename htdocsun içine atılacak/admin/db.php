<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'minnak_haber';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Veritabanına bağlanılamadı: " . $conn->connect_error);
}
?>
