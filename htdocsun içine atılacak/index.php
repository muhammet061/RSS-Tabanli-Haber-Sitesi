<?php
include 'admin/db.php';



// RSS ile haber çekme - URL'ler ve kategori eşleştirmesi düzeltildi
$rss_listesi = [
    'Son Dakika' => 'https://www.trthaber.com/sondakika.rss',
    'Spor' => 'https://www.trthaber.com/spor_articles.rss',
    'Ekonomi' => 'https://www.trthaber.com/ekonomi_articles.rss',
    'Gündem' => 'https://www.trthaber.com/gundem_articles.rss',
    'Teknoloji' => 'https://www.trthaber.com/bilim_teknoloji_articles.rss',
];

// RSS haberlerini çek ve kaydet
foreach ($rss_listesi as $kategori => $rss_url) {
    $rss = @simplexml_load_file($rss_url);
    if (!$rss) {
        continue;
    }

    foreach ($rss->channel->item as $item) {
        $baslik = (string) $item->title;
        $tarih = date('Y-m-d H:i:s', strtotime($item->pubDate));
        $icerikHam = (string)$item->description;
        $icerik = trim(strip_tags($icerikHam));
        if (empty($icerik)) $icerik = $icerikHam;

        // Görsel çekimi
        $gorsel_url = '';
        if (isset($item->imageUrl)) {
            $gorsel_url = (string)$item->imageUrl;
        }
        if (empty($gorsel_url) && preg_match('/<img.*?src=["\'](.*?)["\']/', $icerikHam, $matches)) {
            $gorsel_url = $matches[1];
        }

        if (empty($gorsel_url)) {
            $gorsel_url = 'https://images.pexels.com/photos/518543/pexels-photo-518543.jpeg?auto=compress&cs=tinysrgb&w=800';
        }

        // Aynı başlık ve kategori var mı kontrol et
        $check = $conn->prepare("SELECT id, gorsel_url, icerik FROM haberler WHERE baslik = ? AND kategori = ?");
        $check->bind_param("ss", $baslik, $kategori);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows == 0) {
            // Yeni haber ekle
            $stmt = $conn->prepare("INSERT INTO haberler (baslik, kategori, icerik, gorsel_url, tarih) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $baslik, $kategori, $icerik, $gorsel_url, $tarih);
            $stmt->execute();
            $stmt->close();
        } else {
            $row = $result->fetch_assoc();
            if (empty($row['gorsel_url']) || empty($row['icerik'])) {
                $stmt = $conn->prepare("UPDATE haberler SET gorsel_url = ?, icerik = ? WHERE id = ?");
                $stmt->bind_param("ssi", $gorsel_url, $icerik, $row['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
        $check->close();
    }
}

// URL routing ve sayfa kontrolü
$viewing_article = false;
$article_id = null;
$article = null;

$request_uri = $_SERVER['REQUEST_URI'];
$url_path = parse_url($request_uri, PHP_URL_PATH);

// Haber detay sayfası kontrolü
if (preg_match('/\/haber\/(\d+)/', $url_path, $matches)) {
    $viewing_article = true;
    $article_id = $matches[1];
    
    $stmt = $conn->prepare("SELECT * FROM haberler WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $article = $result->fetch_assoc();
    } else {
        header("Location: /");
        exit();
    }
    $stmt->close();
}

// Kategori belirleme - DÜZELTME: Tam kategori eşleştirmesi
$category_map = [
    'son-dakika' => 'Son Dakika',
    'ekonomi' => 'Ekonomi',
    'spor' => 'Spor',
    'gundem' => 'Gündem',
    'teknoloji' => 'Teknoloji'
];

$active_category = isset($_GET['category']) ? $_GET['category'] : 'son-dakika';

// Geçersiz kategori kontrolü
if (!isset($category_map[$active_category])) {
    $active_category = 'son-dakika';
}

$category_title = $category_map[$active_category];

// DÜZELTME: Kategori haberleri - her kategori için özel filtreleme
$category_query = "";
$db_category = $category_map[$active_category];
$category_query = "WHERE kategori = '" . $conn->real_escape_string($db_category) . "'";

echo "<!-- Aktif kategori: $active_category, DB Kategori: $db_category -->\n";

$news_query = "SELECT * FROM haberler $category_query ORDER BY id DESC LIMIT 25";
$news_result = $conn->query($news_query);
$category_news = [];

if ($news_result && $news_result->num_rows > 0) {
    while ($news = $news_result->fetch_assoc()) {
        $category_news[] = $news;
    }
}

echo "<!-- Bulunan haber sayısı: " . count($category_news) . " -->\n";

// Slider için öne çıkan haberler - sadece seçili kategoriden
$featured_query = "SELECT * FROM haberler $category_query ORDER BY id DESC LIMIT 5";
$featured_result = $conn->query($featured_query);
$featured_news = [];

if ($featured_result && $featured_result->num_rows > 0) {
    while ($news = $featured_result->fetch_assoc()) {
        $featured_news[] = $news;
    }
}

// Admin işlemleri
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_news':
                $baslik = $_POST['baslik'];
                $kategori = $_POST['kategori'];
                $icerik = $_POST['icerik'];
                $gorsel_url = $_POST['gorsel_url'];
                $tarih = date('Y-m-d H:i:s');
                
                $stmt = $conn->prepare("INSERT INTO haberler (baslik, kategori, icerik, gorsel_url, tarih) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $baslik, $kategori, $icerik, $gorsel_url, $tarih);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'delete_news':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM haberler WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

// Base URL fonksiyonu
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

$base_url = getBaseUrl();

// Debug: Veritabanındaki kategorileri kontrol et
$debug_query = "SELECT DISTINCT kategori, COUNT(*) as sayi FROM haberler GROUP BY kategori ORDER BY kategori";
$debug_result = $conn->query($debug_query);
echo "<!-- Veritabanındaki kategoriler: ";
if ($debug_result && $debug_result->num_rows > 0) {
    while ($debug_row = $debug_result->fetch_assoc()) {
        echo "'" . $debug_row['kategori'] . "' (" . $debug_row['sayi'] . " haber), ";
    }
}
echo " -->\n";

// Ekonomi kategorisi özel kontrol
$ekonomi_check = $conn->query("SELECT COUNT(*) as sayi FROM haberler WHERE kategori = 'Ekonomi'");
$ekonomi_count = $ekonomi_check->fetch_assoc()['sayi'];
echo "<!-- Ekonomi kategorisinde toplam $ekonomi_count haber var -->\n";
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Minnak Haber - Güncel haberler, son dakika gelişmeleri">
    <meta name="keywords" content="haber, güncel haberler, son dakika, Türkiye haberleri">
    <title><?php echo $viewing_article ? htmlspecialchars($article['baslik']) . ' - Minnak Haber' : 'Minnak Haber - Güncel Haberler'; ?></title>
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

      :root {
        --primary-color: #cc0000;
        --secondary-color: #2563eb;
        --text-primary: #1f2937;
        --text-secondary: #4b5563;
        --bg-primary: #ffffff;
        --bg-secondary: #f3f4f6;
        --card-bg: #ffffff;
        --header-bg: #ffffff;
        --border-color: #e5e7eb;
        --transition: all 0.3s ease;
      }

      [data-theme="dark"] {
        --text-primary: #f3f4f6;
        --text-secondary: #d1d5db;
        --bg-primary: #111827;
        --bg-secondary: #1f2937;
        --card-bg: #1f2937;
        --header-bg: #111827;
        --border-color: #374151;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: 'Inter', sans-serif;
        color: var(--text-primary);
        line-height: 1.6;
        background-color: var(--bg-primary);
        transition: var(--transition);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      .header {
        background-color: var(--header-bg);
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid var(--border-color);
        transition: var(--transition);
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
        transition: var(--transition);
        padding: 0.5rem;
        border-radius: 0.375rem;
      }

      .nav-menu a:hover {
        color: var(--primary-color);
      }

      .nav-menu a.active {
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
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
        transition: var(--transition);
        width: 40px;
        height: 40px;
      }

      .theme-toggle:hover {
        background-color: var(--bg-secondary);
      }

      .theme-toggle svg {
        width: 20px;
        height: 20px;
      }

      .info-bar {
        background: var(--bg-secondary);
        padding: 0.75rem;
        font-size: 0.875rem;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
        transition: var(--transition);
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

      .main-content {
        max-width: 1280px;
        width: 100%;
        margin: 2rem auto;
        padding: 0 1rem;
        flex: 1;
        animation: fadeIn 0.5s ease-in-out;
      }

      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }

      .breaking-news {
        background: var(--primary-color);
        color: white;
        padding: 0.5rem 1rem;
        margin-bottom: 2rem;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        overflow: hidden;
      }

      .breaking-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        background-color: rgba(255, 255, 255, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        white-space: nowrap;
        margin-right: 1rem;
      }

      .breaking-content {
        overflow: hidden;
        flex: 1;
      }

      .news-text {
        display: inline-block;
        animation: slide-in 0.5s ease forwards;
        white-space: nowrap;
      }

      @keyframes slide-in {
        0% { 
          transform: translateX(100%);
          opacity: 0;
        }
        100% { 
          transform: translateX(0);
          opacity: 1;
        }
      }

      .hero-slider {
        margin-bottom: 2rem;
        position: relative;
      }

      .slider-container {
        position: relative;
        width: 100%;
        height: 500px;
        border-radius: 0.5rem;
        overflow: hidden;
      }

      .slides {
        display: flex;
        height: 100%;
        transition: transform 0.5s ease;
        will-change: transform;
      }

      .slide {
        min-width: 100%;
        position: relative;
        cursor: pointer;
        flex-shrink: 0;
      }

      .slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .slide-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 2rem;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
        color: white;
      }

      .slide-content h3 {
        font-size: 2rem;
        margin-bottom: 1rem;
        transition: transform 0.3s ease;
      }

      .slide:hover .slide-content h3 {
        transform: translateY(-5px);
      }

      .news-category {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: var(--primary-color);
        color: white;
        border-radius: 1rem;
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
      }

      .slider-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.3s ease;
        z-index: 10;
      }

      .slider-arrow:hover {
        background: rgba(0, 0, 0, 0.8);
      }

      .slider-arrow.prev {
        left: 20px;
      }

      .slider-arrow.next {
        right: 20px;
      }

      .slider-arrow svg {
        width: 24px;
        height: 24px;
      }

      .slider-pagination {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 10;
      }

      .pagination-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .pagination-dot.active {
        background: white;
        transform: scale(1.2);
      }

      .section-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--primary-color);
        padding-left: 1rem;
        transition: var(--transition);
      }

      .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
      }

      .news-card {
        background: var(--card-bg);
        border-radius: 0.5rem;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        border: 1px solid var(--border-color);
        height: 100%;
        display: flex;
        flex-direction: column;
      }

      .news-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }

      .news-card-image {
        height: 200px;
        overflow: hidden;
      }

      .news-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
      }

      .news-card:hover .news-card-image img {
        transform: scale(1.05);
      }

      .news-card-content {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex: 1;
      }

      .news-card h3 {
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
        color: var(--text-primary);
        transition: var(--transition);
      }

      .news-card:hover h3 {
        color: var(--primary-color);
      }

      .news-card p {
        color: var(--text-secondary);
        margin-bottom: 1rem;
      }

      .news-card-footer {
        margin-top: auto;
        display: flex;
        justify-content: flex-end;
      }

      .read-more {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: var(--primary-color);
        font-size: 0.875rem;
        font-weight: 500;
      }

      .footer {
        background: var(--bg-secondary);
        color: var(--text-primary);
        padding: 3rem 1rem 1.5rem;
        margin-top: 4rem;
        border-top: 1px solid var(--border-color);
        transition: var(--transition);
      }

      .footer-container {
        max-width: 1280px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
      }

      .footer-section h4 {
        font-size: 1.25rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
        position: relative;
        display: inline-block;
      }

      .footer-section h4::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 40px;
        height: 3px;
        background: var(--primary-color);
      }

      .footer-section p {
        color: var(--text-secondary);
      }

      .footer-links {
        list-style: none;
        padding: 0;
      }

      .footer-links li {
        margin-bottom: 0.75rem;
      }

      .footer-links a {
        color: var(--text-secondary);
        text-decoration: none;
        transition: var(--transition);
      }

      .footer-links a:hover {
        color: var(--primary-color);
        padding-left: 5px;
      }

      .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
      }

      .social-links a {
        color: var(--text-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--card-bg);
        transition: var(--transition);
      }

      .social-links a:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-3px);
      }

      .footer-bottom {
        max-width: 1280px;
        margin: 2rem auto 0;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
        text-align: center;
        color: var(--text-secondary);
        font-size: 0.875rem;
      }

      .news-section {
        margin-bottom: 3rem;
      }

      .news-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
      }

      .view-all {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
      }

      .view-all:hover {
        opacity: 0.8;
      }

      /* Single Article Styles */
      .article-container {
        max-width: 800px;
        margin: 0 auto;
        background: var(--card-bg);
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
      }

      .article-image {
        width: 100%;
        height: 400px;
      }

      .article-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .article-content {
        padding: 2rem;
      }

      .article-header {
        margin-bottom: 1.5rem;
      }

      .article-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
      }

      .article-date {
        color: var(--text-secondary);
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
      }

      .article-title {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
      }

      .article-text {
        color: var(--text-primary);
        line-height: 1.8;
        margin-bottom: 2rem;
      }

      .article-text p {
        margin-bottom: 1.5rem;
      }

      .article-footer {
        border-top: 1px solid var(--border-color);
        padding-top: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .article-tags {
        display: flex;
        gap: 0.5rem;
      }

      .article-tag {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
      }

      .share-buttons {
        display: flex;
        gap: 0.5rem;
      }

      .share-button {
        color: var(--text-primary);
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--bg-secondary);
        transition: var(--transition);
      }

      .share-button:hover {
        background: var(--primary-color);
        color: white;
      }

      .back-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-primary);
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 1.5rem;
        transition: var(--transition);
      }

      .back-button:hover {
        color: var(--primary-color);
      }

      .related-articles {
        margin-top: 3rem;
      }

      .related-articles h2 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
      }

      .debug-info {
        background: #f0f0f0;
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 0.5rem;
        font-family: monospace;
        font-size: 0.875rem;
        border: 1px solid #ddd;
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

        .breaking-news {
          flex-direction: column;
          align-items: flex-start;
          gap: 0.5rem;
        }
        
        .breaking-label {
          margin-right: 0;
        }
        
        .news-text {
          white-space: normal;
        }

        .slider-container {
          height: 300px;
        }

        .slide-content h3 {
          font-size: 1.5rem;
        }

        .section-title {
          font-size: 1.5rem;
        }

        .footer {
          padding: 2rem 1rem 1rem;
        }
        
        .footer-container {
          grid-template-columns: 1fr;
        }
        
        .article-title {
          font-size: 1.5rem;
        }
        
        .article-image {
          height: 250px;
        }
        
        .article-content {
          padding: 1.5rem;
        }
      }
    </style>
  </head>
  <body>
    <header class="header">
      <div class="header-container">
        <a href="<?php echo $base_url; ?>/" class="logo">MINNAK HABER</a>
        <nav class="nav-menu">
          <a href="<?php echo $base_url; ?>/?category=son-dakika" class="<?php echo ($active_category === 'son-dakika') ? 'active' : ''; ?>">Son Dakika</a>
          <a href="<?php echo $base_url; ?>/?category=gundem" class="<?php echo ($active_category === 'gundem') ? 'active' : ''; ?>">Gündem</a>
          <a href="<?php echo $base_url; ?>/?category=ekonomi" class="<?php echo ($active_category === 'ekonomi') ? 'active' : ''; ?>">Ekonomi</a>
          <a href="<?php echo $base_url; ?>/?category=teknoloji" class="<?php echo ($active_category === 'teknoloji') ? 'active' : ''; ?>">Teknoloji</a>
          <a href="<?php echo $base_url; ?>/?category=spor" class="<?php echo ($active_category === 'spor') ? 'active' : ''; ?>">Spor</a>
        </nav>
        <button class="theme-toggle" id="themeToggle" aria-label="Tema değiştir" type="button">
          <svg class="theme-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </button>
      </div>
    </header>

    <div class="info-bar">
      <div class="info-container">
        <div id="doviz">Döviz kurları yükleniyor...</div>
        <div id="hava">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
          </svg>
          <span>İstanbul: Parçalı Bulutlu, 18°C</span>
        </div>
      </div>
    </div>

    <main class="main-content">
      <?php if (!$viewing_article): ?>
      <!-- Homepage Content -->
      <div class="breaking-news">
        <div class="breaking-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>SON DAKİKA</span>
        </div>
        <div class="breaking-content">
          <div class="news-text" id="breakingNewsText"></div>
        </div>
      </div>

      <?php if (count($featured_news) > 0): ?>
      <div class="hero-slider">
        <div class="slider-container">
          <div class="slides" id="slides">
            <?php foreach ($featured_news as $index => $news): ?>
            <div class="slide" onclick="window.location.href='<?php echo $base_url; ?>/haber/<?php echo $news['id']; ?>'">
              <img src="<?php echo htmlspecialchars($news['gorsel_url']); ?>" alt="<?php echo htmlspecialchars($news['baslik']); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
              <div class="slide-content">
                <span class="news-category"><?php echo htmlspecialchars($news['kategori']); ?></span>
                <h3><?php echo htmlspecialchars($news['baslik']); ?></h3>
                <p><?php echo substr(strip_tags($news['icerik']), 0, 150) . (strlen($news['icerik']) > 150 ? '...' : ''); ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          
          <?php if (count($featured_news) > 1): ?>
          <button class="slider-arrow prev" id="prevSlide" aria-label="Önceki haber">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
          </button>
          <button class="slider-arrow next" id="nextSlide" aria-label="Sonraki haber">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </button>
          
          <div class="slider-pagination" id="sliderPagination">
            <?php foreach ($featured_news as $index => $news): ?>
            <button class="pagination-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                    onclick="goToSlide(<?php echo $index; ?>)"
                    aria-label="Haber <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <section class="news-section">
        <div class="news-section-header">
          <h2 class="section-title"><?php echo strtoupper($category_title); ?> HABERLERİ</h2>
          <a href="<?php echo $base_url; ?>/tum-haberler?category=<?php echo $active_category; ?>" class="view-all">
            Tümünü Gör
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>
        
        <div class="news-grid" id="categoryNews">
          <?php if (count($category_news) > 0): ?>
            <?php foreach ($category_news as $news): ?>
            <div class="news-card" onclick="window.location.href='<?php echo $base_url; ?>/haber/<?php echo $news['id']; ?>'">
              <div class="news-card-image">
                <img src="<?php echo htmlspecialchars($news['gorsel_url']); ?>" alt="<?php echo htmlspecialchars($news['baslik']); ?>" loading="lazy">
              </div>
              <div class="news-card-content">
                <span class="news-category"><?php echo htmlspecialchars($news['kategori']); ?></span>
                <h3><?php echo htmlspecialchars($news['baslik']); ?></h3>
                <p><?php echo substr(strip_tags($news['icerik']), 0, 150) . (strlen($news['icerik']) > 150 ? '...' : ''); ?></p>
                <div class="news-card-footer">
                  <span class="read-more">
                    Devamını Oku
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                  </span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="debug-info">
              <strong>Debug Bilgisi:</strong><br>
              Aktif Kategori: <?php echo $active_category; ?><br>
              DB Kategori: <?php echo $db_category; ?><br>
              SQL Sorgusu: <?php echo $news_query; ?><br>
              Bulunan Haber Sayısı: <?php echo count($category_news); ?><br>
              <br>
              <strong>Veritabanındaki Kategoriler:</strong><br>
              <?php
                $debug_result2 = $conn->query("SELECT DISTINCT kategori, COUNT(*) as sayi FROM haberler GROUP BY kategori ORDER BY kategori");
                if ($debug_result2 && $debug_result2->num_rows > 0) {
                    while ($debug_row2 = $debug_result2->fetch_assoc()) {
                        echo "- '" . htmlspecialchars($debug_row2['kategori']) . "' (" . $debug_row2['sayi'] . " haber)<br>";
                    }
                } else {
                    echo "Veritabanında hiç haber bulunamadı.";
                }
              ?>
            </div>
            <p>Bu kategoride henüz haber bulunmamaktadır.</p>
          <?php endif; ?>
        </div>
      </section>

      <?php else: ?>
      <!-- Single Article Page -->
      <div class="article-container">
        <a href="<?php echo $base_url; ?>/" class="back-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Geri Dön
        </a>
        
        <div class="article-image">
          <img src="<?php echo htmlspecialchars($article['gorsel_url']); ?>" alt="<?php echo htmlspecialchars($article['baslik']); ?>">
        </div>
        
        <div class="article-content">
          <div class="article-header">
            <div class="article-meta">
              <span class="news-category"><?php echo htmlspecialchars($article['kategori']); ?></span>
              <span class="article-date">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?php echo date('d.m.Y', strtotime($article['tarih'] ?? date('Y-m-d'))); ?>
              </span>
            </div>
            
            <h1 class="article-title"><?php echo htmlspecialchars($article['baslik']); ?></h1>
          </div>
          
          <div class="article-text">
            <?php
              $paragraphs = explode("\n", $article['icerik']);
              foreach ($paragraphs as $paragraph) {
                if (trim($paragraph) != '') {
                  echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                }
              }
            ?>
          </div>
          
          <div class="article-footer">
            <div class="article-tags">
              <span class="article-tag"><?php echo htmlspecialchars($article['kategori']); ?></span>
              <span class="article-tag">Türkiye</span>
            </div>
            
            <div class="share-buttons">
              <button class="share-button" onclick="shareOnFacebook()" aria-label="Facebook'ta Paylaş">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                </svg>
              </button>
              <button class="share-button" onclick="shareOnTwitter()" aria-label="Twitter'da Paylaş">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>
                </svg>
              </button>
              <button class="share-button" onclick="shareOnWhatsApp()" aria-label="Whatsapp'ta Paylaş">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <div class="related-articles">
        <h2>İlgili Haberler</h2>
        
        <div class="news-grid">
          <?php
            $related_category = $conn->real_escape_string($article['kategori']);
            $article_id = intval($article['id']);
            $related_query = "SELECT * FROM haberler WHERE kategori = '$related_category' AND id != $article_id ORDER BY id DESC LIMIT 3";
            $related_result = $conn->query($related_query);
            
            if ($related_result && $related_result->num_rows > 0) {
              while ($related_news = $related_result->fetch_assoc()) {
                echo '
                <div class="news-card" onclick="window.location.href=\'' . $base_url . '/haber/' . $related_news['id'] . '\'">
                  <div class="news-card-image">
                    <img src="' . htmlspecialchars($related_news['gorsel_url']) . '" alt="' . htmlspecialchars($related_news['baslik']) . '" loading="lazy">
                  </div>
                  <div class="news-card-content">
                    <span class="news-category">' . htmlspecialchars($related_news['kategori']) . '</span>
                    <h3>' . htmlspecialchars($related_news['baslik']) . '</h3>
                    <p>' . substr(strip_tags($related_news['icerik']), 0, 150) . (strlen($related_news['icerik']) > 150 ? '...' : '') . '</p>
                    <div class="news-card-footer">
                      <span class="read-more">
                        Devamını Oku
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      </span>
                    </div>
                  </div>
                </div>
                ';
              }
            } else {
              echo '<p>İlgili haber bulunamadı.</p>';
            }
          ?>
        </div>
      </div>
      <?php endif; ?>
    </main>

    <footer class="footer">
      <div class="footer-container">
        <div class="footer-section">
          <h4>Hakkımızda</h4>
          <p>Minnak Haber, güncel ve tarafsız habercilik anlayışıyla sizlere hizmet vermektedir.</p>
        </div>
        <div class="footer-section">
          <h4>Hızlı Bağlantılar</h4>
          <ul class="footer-links">
            <li><a href="/kunye">Künye</a></li>
            <li><a href="/iletisim">İletişim</a></li>
            <li><a href="/gizlilik">Gizlilik Politikası</a></li>
            <li><a href="/sartlar">Kullanım Şartları</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Bizi Takip Edin</h4>
          <div class="social-links">
            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
              </svg>
            </a>
            <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>
              </svg>
            </a>
            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
              </svg>
            </a>
            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="Youtube">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <span id="currentYear"></span> Minnak Haber. Tüm hakları saklıdır.</p>
      </div>
    </footer>

    <script>
      // Initialize page
      document.addEventListener('DOMContentLoaded', function() {
        // Set current year
        document.getElementById('currentYear').textContent = new Date().getFullYear();
        
        // Initialize theme
        initializeTheme();
        
        // Initialize slider
        initializeSlider();
        
        // Initialize breaking news
        initializeBreakingNews();
        
        // Initialize exchange rates
        fetchExchangeRates();
        setInterval(fetchExchangeRates, 60000);
      });

      // Theme functionality
      function initializeTheme() {
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const themeIcon = themeToggle.querySelector('.theme-icon');
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        // Theme toggle event
        themeToggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          const currentTheme = html.getAttribute('data-theme') || 'light';
          const newTheme = currentTheme === 'light' ? 'dark' : 'light';
          
          html.setAttribute('data-theme', newTheme);
          localStorage.setItem('theme', newTheme);
          updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
          if (theme === 'dark') {
            themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
          } else {
            themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
          }
        }
      }

      // Slider functionality
      function initializeSlider() {
        const slides = document.getElementById('slides');
        const prevButton = document.getElementById('prevSlide');
        const nextButton = document.getElementById('nextSlide');
        const pagination = document.getElementById('sliderPagination');
        
        if (!slides || !prevButton || !nextButton) return;
        
        const slideElements = slides.querySelectorAll('.slide');
        const slideCount = slideElements.length;
        
        if (slideCount <= 1) return;
        
        let currentSlide = 0;
        let sliderInterval;
        
        function updateSlider() {
          const translateX = -currentSlide * 100;
          slides.style.transform = `translateX(${translateX}%)`;
          
          // Update pagination
          if (pagination) {
            const dots = pagination.querySelectorAll('.pagination-dot');
            dots.forEach((dot, index) => {
              dot.classList.toggle('active', index === currentSlide);
            });
          }
        }
        
        function nextSlide() {
          currentSlide = (currentSlide + 1) % slideCount;
          updateSlider();
        }
        
        function prevSlide() {
          currentSlide = (currentSlide - 1 + slideCount) % slideCount;
          updateSlider();
        }
        
        // Global function for pagination
        window.goToSlide = function(index) {
          if (index >= 0 && index < slideCount) {
            currentSlide = index;
            updateSlider();
          }
        };
        
        // Event listeners
        prevButton.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          prevSlide();
        });
        
        nextButton.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          nextSlide();
        });
        
        // Auto-advance slider
        function startAutoSlide() {
          sliderInterval = setInterval(nextSlide, 5000);
        }
        
        function stopAutoSlide() {
          clearInterval(sliderInterval);
        }
        
        // Start auto-slide
        startAutoSlide();
        
        // Pause on hover
        const sliderContainer = slides.closest('.slider-container');
        if (sliderContainer) {
          sliderContainer.addEventListener('mouseenter', stopAutoSlide);
          sliderContainer.addEventListener('mouseleave', startAutoSlide);
        }
        
        // Pause when page is not visible
        document.addEventListener('visibilitychange', function() {
          if (document.hidden) {
            stopAutoSlide();
          } else {
            startAutoSlide();
          }
        });
      }

      // Breaking news functionality
      function initializeBreakingNews() {
        const breakingNewsItems = [
          'İstanbul\'da metro seferlerinde aksama yaşanıyor.',
          'Dolar kuru son 1 ayın en düşük seviyesine geriledi.',
          'Meteoroloji\'den Ege bölgesi için fırtına uyarısı.',
          'Milli takım kadrosu açıklandı.',
          'Yeni ekonomik paket açıklandı.',
          'Üniversite sınavı tarihleri belli oldu.'
        ];
        
        const breakingNewsText = document.getElementById('breakingNewsText');
        if (!breakingNewsText) return;
        
        let currentIndex = 0;
        
        function updateBreakingNews() {
          breakingNewsText.style.opacity = '0';
          
          setTimeout(() => {
            breakingNewsText.textContent = breakingNewsItems[currentIndex];
            breakingNewsText.style.opacity = '1';
            currentIndex = (currentIndex + 1) % breakingNewsItems.length;
          }, 500);
        }
        
        // Initialize
        updateBreakingNews();
        
        // Update every 5 seconds
        setInterval(updateBreakingNews, 5000);
      }

      // Exchange rates functionality
      let previousRates = {};
      
      async function fetchExchangeRates() {
        const dovizElement = document.getElementById("doviz");
        if (!dovizElement) return;
        
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
            const isUp = parseFloat(current) > parseFloat(previous[currency]);
            const iconClass = isUp ? 'currency-up' : 'currency-down';
            const pathData = isUp 
              ? 'M23 6 13.5 15.5 8.5 10.5 1 18M17 6h6v6' 
              : 'M23 18 13.5 8.5 8.5 13.5 1 6M17 18h6v-6';
            
            return `<svg class="${iconClass}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="${pathData}"/></svg>`;
          };

          dovizElement.innerHTML = `
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
          dovizElement.innerHTML = "Döviz kurları şu anda görüntülenemiyor.";
        }
      }

      // Share functions
      function shareOnFacebook() {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&t=${title}`, '_blank', 'width=600,height=400');
      }

      function shareOnTwitter() {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
      }

      function shareOnWhatsApp() {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);
        window.open(`https://wa.me/?text=${title} ${url}`, '_blank');
      }
    </script>
  </body>
</html>
<?php $conn->close(); ?>