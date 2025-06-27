<?php
include 'admin/db.php';

// Get news ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get news details
$sql = "SELECT * FROM haberler WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

// If news not found, redirect to homepage
if (!$news) {
  header("Location: /");
  exit();
}

// Format date
$date = new DateTime($news['tarih']);
$formattedDate = $date->format('d.m.Y H:i');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($news['baslik']); ?> - Minnak Haber</title>
  <link href="https://unpkg.com/lucide-icons/dist/umd/lucide.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
      --primary-color: #cc0000;
      --text-primary: #1f2937;
      --text-secondary: #4b5563;
      --bg-primary: #ffffff;
      --bg-secondary: #f3f4f6;
      --border-color: #e5e7eb;
    }

    [data-theme="dark"] {
      --text-primary: #f3f4f6;
      --text-secondary: #d1d5db;
      --bg-primary: #111827;
      --bg-secondary: #1f2937;
      --border-color: #374151;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      line-height: 1.6;
      color: var(--text-primary);
      background: var(--bg-primary);
    }

    .container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .header {
      background-color: var(--bg-primary);
      padding: 1rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
      border-bottom: 1px solid var(--border-color);
    }

    .header-container {
      max-width: 1280px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary-color);
      text-decoration: none;
      letter-spacing: -1px;
    }

    .nav-menu {
      display: flex;
      gap: 2rem;
      align-items: center;
    }

    .nav-menu a {
      color: var(--text-primary);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .nav-menu a:hover {
      color: var(--primary-color);
    }

    .theme-toggle {
      background: none;
      border: none;
      color: var(--text-primary);
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .info-bar {
      background: var(--bg-secondary);
      padding: 0.75rem;
      font-size: 0.875rem;
      color: var(--text-secondary);
      border-bottom: 1px solid var(--border-color);
    }

    .info-container {
      max-width: 1280px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .currency-item {
      display: inline-flex;
      align-items: center;
      margin-right: 1rem;
      gap: 0.25rem;
    }

    .currency-up {
      color: #10b981;
    }

    .currency-down {
      color: #ef4444;
    }

    .news-header {
      margin-bottom: 2rem;
    }

    .category {
      display: inline-block;
      background: var(--primary-color);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.875rem;
      margin-bottom: 1rem;
    }

    .title {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      line-height: 1.2;
      color: var(--text-primary);
    }

    .meta {
      color: var(--text-secondary);
      font-size: 0.875rem;
      margin-bottom: 2rem;
    }

    .featured-image {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: 0.5rem;
      margin-bottom: 2rem;
    }

    .content {
      font-size: 1.125rem;
      line-height: 1.8;
      color: var(--text-primary);
    }

    .content p {
      margin-bottom: 1.5rem;
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
      margin-bottom: 2rem;
    }

    .back-button:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .nav-menu {
        display: none;
      }

      .info-container {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
      }

      .title {
        font-size: 2rem;
      }

      .featured-image {
        height: 300px;
      }
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-container">
      <a href="/" class="logo">MINNAK HABER</a>
      <nav class="nav-menu">
        <a href="/son-dakika">Son Dakika</a>
        <a href="/gundem">Gündem</a>
        <a href="/ekonomi">Ekonomi</a>
        <a href="/spor">Spor</a>
        <a href="/teknoloji">Teknoloji</a>
      </nav>
      <button class="theme-toggle" id="themeToggle" aria-label="Tema değiştir">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="moon-icon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
      </button>
    </div>
  </header>

  <div class="info-bar">
    <div class="info-container">
      <div id="doviz">Döviz kurları yükleniyor...</div>
      <div id="hava">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
        <span>İstanbul: Parçalı Bulutlu, 18°C</span>
      </div>
    </div>
  </div>

  <div class="container">
    <a href="/" class="back-button">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Ana Sayfaya Dön
    </a>

    <article class="news-header">
      <span class="category"><?php echo htmlspecialchars($news['kategori']); ?></span>
      <h1 class="title"><?php echo htmlspecialchars($news['baslik']); ?></h1>
      <div class="meta">
        Yayınlanma Tarihi: <?php echo $formattedDate; ?>
      </div>
      <img src="<?php echo htmlspecialchars($news['gorsel_url']); ?>" alt="<?php echo htmlspecialchars($news['baslik']); ?>" class="featured-image">
      <div class="content">
        <?php echo nl2br(htmlspecialchars($news['icerik'])); ?>
      </div>
    </article>
  </div>

  <script>
    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const moonIcon = themeToggle.querySelector('svg');

    function toggleTheme() {
      const currentTheme = html.getAttribute('data-theme') || 'light';
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      html.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      
      if (newTheme === 'dark') {
        moonIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
      } else {
        moonIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
      }
    }

    themeToggle.addEventListener('click', toggleTheme);

    // Initialize theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    if (savedTheme === 'dark') {
      moonIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
    }

    // Exchange rates functionality
    let previousRates = {};

    async function fetchExchangeRates() {
      try {
        const response = await fetch('https://api.frankfurter.app/latest?from=USD&to=TRY,EUR,GBP');
        if (!response.ok) throw new Error('Döviz verileri alınamadı');
        
        const data = await response.json();
        const currentRates = {
          USD: data.rates.TRY.toFixed(2),
          EUR: (data.rates.TRY / data.rates.EUR).toFixed(2),
          GBP: (data.rates.TRY / data.rates.GBP).toFixed(2)
        };

        const getTrendIcon = (current, previous, currency) => {
          if (!previous[currency]) return '';
          return parseFloat(current) > parseFloat(previous[currency])
            ? '<svg class="currency-up" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>'
            : '<svg class="currency-down" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>';
        };

        document.getElementById("doviz").innerHTML = `
          <div class="currency-item">
            <strong>USD/TRY:</strong> ${currentRates.USD} ${getTrendIcon(currentRates.USD, previousRates, 'USD')}
          </div>
          <div class="currency-item">
            <strong>EUR/TRY:</strong> ${currentRates.EUR} ${getTrendIcon(currentRates.EUR, previousRates, 'EUR')}
          </div>
          <div class="currency-item">
            <strong>GBP/TRY:</strong> ${currentRates.GBP} ${getTrendIcon(currentRates.GBP, previousRates, 'GBP')}
          </div>
        `;

        previousRates = currentRates;
      } catch (error) {
        console.error('Döviz kuru hatası:', error);
        document.getElementById("doviz").innerText = "Döviz kurları şu anda görüntülenemiyor.";
      }
    }

    // Initialize exchange rates
    fetchExchangeRates();
    setInterval(fetchExchangeRates, 60000); // Update every minute
  </script>
</body>
</html>