<?php
include 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: index.php");
  exit;
}
$haberler = $conn->query("SELECT * FROM haberler ORDER BY tarih DESC");
?>
<h2>Haber Listesi</h2>
<table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse; background: #fff;">
  <tr style="background-color:#eee;">
    <th>ID</th>
    <th>Başlık</th>
    <th>Kategori</th>
    <th>Tarih</th>
    <th>İşlem</th>
  </tr>
  <?php while($haber = $haberler->fetch_assoc()): ?>
    <tr>
      <td><?= $haber['id'] ?></td>
      <td><?= htmlspecialchars($haber['baslik']) ?></td>
      <td><?= $haber['kategori'] ?></td>
      <td><?= $haber['tarih'] ?></td>
      <td>
        <a href="haber_duzenle.php?id=<?= $haber['id'] ?>">Düzenle</a> |
        <a href="haber_sil.php?id=<?= $haber['id'] ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
      </td>
    </tr>
  <?php endwhile; ?>
</table>
