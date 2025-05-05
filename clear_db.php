<?php
session_start();
require_once "config.php";

// Sadece admin erişebilir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "admin"){
    header("location: index.php");
    exit;
}

// Veritabanını temizle
$queries = [
    "SET FOREIGN_KEY_CHECKS = 0",
    "TRUNCATE TABLE order_items",
    "TRUNCATE TABLE orders",
    "TRUNCATE TABLE cart",
    "TRUNCATE TABLE products",
    "TRUNCATE TABLE daily_reports",
    "TRUNCATE TABLE users",
    "SET FOREIGN_KEY_CHECKS = 1",
    "INSERT INTO users (display_name, email, password, user_type) VALUES ('Admin', 'admin@hercankomisyon.com', '123456', 'admin')",
    "INSERT INTO products (name, description, price, weight, image_url) VALUES 
    ('Domates', 'Taze ve kaliteli domates', 15.90, 1.00, 'https://images.unsplash.com/photo-1546470427-1ec0a9b0115d?w=500&q=80'),
    ('Salatalık', 'Taze ve kaliteli salatalık', 12.90, 0.50, 'https://images.unsplash.com/photo-1589621316382-008455b857cd?w=500&q=80'),
    ('Biber', 'Taze ve kaliteli biber', 18.90, 0.25, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=500&q=80'),
    ('Patlıcan', 'Taze ve kaliteli patlıcan', 16.90, 0.75, 'https://images.unsplash.com/photo-1528826007177-f38517ce0a30?w=500&q=80')"
];

$success = true;
$error_message = '';

foreach ($queries as $query) {
    if (!mysqli_query($conn, $query)) {
        $success = false;
        $error_message .= mysqli_error($conn) . "\n";
    }
}

// Oturumu sonlandır
session_destroy();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Temizleme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body">
                <?php if ($success): ?>
                    <h3 class="text-success">Veritabanı başarıyla temizlendi!</h3>
                    <p>Tüm veriler silindi ve varsayılan kayıtlar eklendi.</p>
                    <p>Lütfen tekrar giriş yapın:</p>
                    <ul>
                        <li>E-posta: admin@hercankomisyon.com</li>
                        <li>Şifre: 123456</li>
                    </ul>
                <?php else: ?>
                    <h3 class="text-danger">Hata oluştu!</h3>
                    <pre><?php echo htmlspecialchars($error_message); ?></pre>
                <?php endif; ?>
                <a href="login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        </div>
    </div>
</body>
</html> 