<?php
include 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: index.php");
  exit;
}

$id = $_GET['id'];
$haber = $conn->query("SELECT * FROM haberler WHERE id = $id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $baslik = $_POST['baslik'];
  $kategori = $_POST['kategori'];
  $icerik = $_POST['icerik'];
  $gorsel_url = $_POST['gorsel_url'];

  $stmt = $conn->prepare("UPDATE haberler SET baslik=?, kategori=?, icerik=?, gorsel_url=? WHERE id=?");
  $stmt->bind_param("ssssi", $baslik, $kategori, $icerik, $gorsel_url, $id);
  $stmt->execute();

  header("Location: haber_listesi.php");
}
?>
<h2>Haberi Düzenle</h2>
<form method="POST">
  <input name="baslik" value="<?= htmlspecialchars($haber['baslik']) ?>" placeholder="Başlık"><br><br>
  <input name="kategori" value="<?= $haber['kategori'] ?>" placeholder="Kategori"><br><br>
  <input name="gorsel_url" value="<?= $haber['gorsel_url'] ?>" placeholder="Görsel URL"><br><br>
  <textarea name="icerik" rows="10" cols="60"><?= htmlspecialchars($haber['icerik']) ?></textarea><br><br>
  <button type="submit">Güncelle</button>
</form>
