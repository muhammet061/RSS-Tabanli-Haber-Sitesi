<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Yönetici Girişi</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #0d1117;
      color: white;
    }
    header {
      background-color: #d32f2f;
      padding: 20px;
      text-align: left;
      font-size: 24px;
      font-weight: bold;
    }
    .login-container {
      max-width: 400px;
      margin: 80px auto;
      background-color: #1c2128;
      padding: 30px;
      border-radius: 10px;
    }
    .login-container h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    .login-container label {
      display: block;
      margin-bottom: 5px;
    }
    .login-container input[type=text],
    .login-container input[type=password] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: none;
      border-radius: 5px;
      background-color: #2d333b;
      color: white;
    }
    .login-container button {
      width: 100%;
      padding: 12px;
      background-color: #d32f2f;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }
    .login-container button:hover {
      background-color: #b71c1c;
    }
  </style>
</head>
<body>
  <header>Yönetim Paneli</header>
  <div class="login-container">
    <h2>Yönetici Girişi</h2>
    <?php if (isset($_GET['error'])): ?>
      <p style="color:#ff5c5c; text-align:center;">Hatalı kullanıcı adı veya şifre!</p>
    <?php endif; ?>
    <form method="POST" action="giris.php">
      <label for="kullanici_adi">Kullanıcı Adı</label>
      <input type="text" name="kullanici_adi" required>
      <label for="sifre">Şifre</label>
      <input type="password" name="sifre" required>
      <button type="submit">➔ Giriş Yap</button>
    </form>
  </div>
</body>
</html>
