<?php
session_start();
require_once "config.php";

// Ürünleri veritabanından çek
$products = [];
$sql = "SELECT * FROM products ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Sepete ürün ekleme işlemi
if(isset($_POST['add_to_cart']) && isset($_SESSION['loggedin']) && $_SESSION['user_type'] === 'customer') {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $user_id = $_SESSION['id'];
    
    // Stok kontrolü yap
    $stock_check_sql = "SELECT stock_quantity FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $stock_check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        
        if ($product && $product['stock_quantity'] >= $quantity) {
            // Yeterli stok var, sepete ekle
            $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "iii", $user_id, $product_id, $quantity);
                if(mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "Ürün sepete eklendi.";
                } else {
                    $_SESSION['error_message'] = "Ürün sepete eklenirken bir hata oluştu.";
                }
            }
        } else {
            $_SESSION['error_message'] = "Üzgünüz, yeterli stok bulunmamaktadır.";
        }
    }
}

// Başarı ve hata mesajlarını göster
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hercan Komisyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }

        :root {
            --primary-color: #16a34a;
            --primary-dark: #15803d;
        }
        
        .navbar-custom {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-link {
            color: #1f2937 !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .login-btn {
            background-color: var(--primary-color);
            color: white !important;
            padding: 0.5rem 1.5rem !important;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.3s;
            margin-left: 1rem;
        }
        
        .login-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .hero-section {
            height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-bottom: 0;
            background: url('banner.jpg') no-repeat center center;
            background-size: cover;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .hero-content {
            max-width: 800px;
            padding: 2rem;
            animation: fade-in 1s ease-out;
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            color: #1f2937;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: #6b7280;
            font-size: 1.1rem;
        }

        .product-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-img {
            height: 200px;
            object-fit: cover;
        }

        .map-section {
            padding: 5rem 0;
            background: #f9fafb;
        }

        .map-container {
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .contact-section {
            padding: 5rem 0;
            background: white;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .contact-text h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #1f2937;
        }

        .contact-text p {
            margin: 0;
            color: #6b7280;
        }

        footer {
            background: #1f2937;
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .footer-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .footer-content p {
            margin: 0;
            color: #9ca3af;
        }

        .quantity-input {
            width: 80px;
        }

        .section-padding {
            padding: 5rem 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-leaf"></i> Hercan Komisyon
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Ürünler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">İletişim</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle login-btn" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION["display_name"]); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if($_SESSION["user_type"] === "admin"): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">
                                        <i class="fas fa-cog"></i> Yönetim Paneli
                                    </a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="cart.php">
                                        <i class="fas fa-shopping-cart"></i> Sepetim
                                    </a></li>
                                    <li><a class="dropdown-item" href="orders.php">
                                        <i class="fas fa-box"></i> Siparişlerim
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link login-btn" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Hercan Komisyon</h1>
            <p>D BLOK NO:3</p>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="section-padding">
        <div class="container">
            <div class="section-title">
                <h2>Ürünlerimiz</h2>
                <p>En taze ve kaliteli ürünler sizin için özenle seçiliyor.</p>
            </div>

            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4">
                    <div class="card product-card">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top product-img" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="stock-status mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-muted">Stok Durumu:</strong>
                                    <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?> rounded-pill">
                                        <?php echo $product['stock_quantity'] > 0 ? number_format($product['stock_quantity'], 2) . ' kg' : 'Stokta Yok'; ?>
                                    </span>
                                </div>
                                <?php if($product['stock_quantity'] > 0): ?>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo min(($product['stock_quantity'] / 100) * 100, 100); ?>%" 
                                             aria-valuenow="<?php echo $product['stock_quantity']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-price mb-3">
                                <span class="fs-4 fw-bold text-success">₺<?php echo number_format($product['price'], 2); ?></span>
                                <small class="text-muted">/kg</small>
                            </div>
                            
                            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                                <?php if($_SESSION["user_type"] === "customer"): ?>
                                    <?php if($product['stock_quantity'] > 0): ?>
                                        <form method="post" class="quantity-form">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text bg-light">
                                                    <i class="fas fa-weight-hanging"></i>
                                                </span>
                                                <input type="number" 
                                                       class="form-control form-control-lg" 
                                                       id="quantity_<?php echo $product['id']; ?>" 
                                                       name="quantity" 
                                                       min="0.1" 
                                                       max="<?php echo $product['stock_quantity']; ?>" 
                                                       step="0.1" 
                                                       placeholder="Miktar"
                                                       required
                                                       style="border-right: none;">
                                                <span class="input-group-text bg-light">kg</span>
                                            </div>
                                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100">
                                                <i class="fas fa-cart-plus me-2"></i> Sepete Ekle
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg w-100" disabled>
                                            <i class="fas fa-times-circle me-2"></i> Stokta Yok
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i> Sipariş için Giriş Yapın
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section-padding" style="background: #f9fafb;">
        <div class="container">
            <div class="section-title">
                <h2>Hakkımızda</h2>
                <p>Kaliteli hizmet ve güvenilir alışverişin adresi</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="text-center">
                        <p class="mb-4">
                            Hercan Komisyon olarak, 20 yılı aşkın tecrübemizle sizlere en taze ve kaliteli ürünleri
                            sunmaktan gurur duyuyoruz. Müşteri memnuniyetini ön planda tutarak, güvenilir ve hızlı
                            hizmet anlayışımızla çalışmalarımızı sürdürüyoruz.
                        </p>
                        <p>
                            Sebze ve meyve komisyonculuğunda Ankara'nın önde gelen firmalarından biri olarak,
                            ürünlerimizi özenle seçiyor ve sizlere en uygun fiyatlarla ulaştırıyoruz.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <div class="section-title">
                <h2>Bizi Ziyaret Edin</h2>
                <p>Mağazamız Ankara'nın merkezi konumunda sizlere hizmet vermektedir.</p>
            </div>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3060.4799649847845!2d32.85656661744384!3d39.92033099999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d34f190a9cea8f%3A0x611b35cd3fc8c189!2sK%C4%B1z%C4%B1lay%2C%20Ankara!5e0!3m2!1str!2str!4v1647789456789!5m2!1str!2str" 
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="section-title">
                <h2>İletişim</h2>
                <p>Sorularınız için bize ulaşabilirsiniz.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Adres</h3>
                                <p>Kızılay Mahallesi, Atatürk Bulvarı No: 123, Çankaya/Ankara</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Telefon</h3>
                                <p>+90 (312) 123 45 67</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <h3>E-posta</h3>
                                <p>info@hercankomisyon.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Çalışma Saatleri</h3>
                                <p>Pazartesi - Cumartesi: 08:00 - 20:00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <p>&copy; 2024 Hercan Komisyon. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
