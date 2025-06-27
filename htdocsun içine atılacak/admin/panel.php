<?php // panel.php – Giriş sonrası admin paneli ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Yönetim Paneli</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      background-color: #f4f4f4;
    }

    header {
      background-color: #d32f2f;
      padding: 20px;
      color: white;
      text-align: center;
      font-size: 24px;
    }

    nav {
      background-color: #333;
      display: flex;
      justify-content: space-around;
      padding: 15px 0;
    }

    nav a {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }

    nav a:hover {
      color: #ff5252;
    }

    .container {
      padding: 30px;
    }

    .panel {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <header>Admin Paneline Hoş Geldin</header>

  <nav>
    <a href="haber_ekle.php">Haber Ekle</a>
    <a href="haber_listesi.php">Haber Listesi</a>
    <a href="#">Yorumlar</a>
    <a href="#">Çıkış Yap</a>
  </nav>

  <div class="container">
    <div class="panel">
      <h2>Bugünkü İşlemler</h2>
      <p>Buraya haber ekleme, silme, güncelleme seçenekleri gelecek.</p>
    </div>
  </div>

</body>
</html>
