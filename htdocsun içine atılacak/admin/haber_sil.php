<?php
include 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: index.php");
  exit;
}

$id = $_GET['id'];
$conn->query("DELETE FROM haberler WHERE id = $id");
header("Location: haber_listesi.php");
exit;
