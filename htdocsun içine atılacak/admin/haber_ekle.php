<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $baslik = $_POST['baslik'];
  $kategori = $_POST['kategori'];
  $icerik = $_POST['icerik'];
  $gorsel = $_POST['gorsel_url'];

  $sql = "INSERT INTO haberler (baslik, kategori, icerik, gorsel_url) VALUES (?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssss", $baslik, $kategori, $icerik, $gorsel);
  $stmt->execute();

  echo "<p style='color:green;'>Haber başarıyla eklendi!</p>";
}

?>

<form method="POST" style="margin: 30px;">
  <label>Haber Başlığı</label><br>
  <input type="text" name="baslik" required style="width: 100%; padding: 8px;"><br><br>

  <label>Kategori</label><br>
  <input type="text" name="kategori" required style="width: 100%; padding: 8px;"><br><br>

  <label>Görsel URL</label><br>
  <input type="text" name="gorsel_url" style="width: 100%; padding: 8px;"><br><br>

  <label>İçerik</label><br>
  <textarea name="icerik" rows="6" style="width: 100%; padding: 8px;"></textarea><br><br>

  <button type="submit" style="padding: 10px 20px;">Haberi Ekle</button>
</form>

