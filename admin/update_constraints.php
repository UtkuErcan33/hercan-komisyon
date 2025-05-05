<?php
session_start();
require_once "../config.php";

// Sadece admin erişebilir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "admin"){
    header("location: ../index.php");
    exit;
}

$queries = [
    // Direct Sales tablosunu oluştur
    "CREATE TABLE IF NOT EXISTS direct_sales (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )",
    
    // Order items için cascade delete
    "ALTER TABLE order_items DROP FOREIGN KEY IF EXISTS order_items_ibfk_2",
    "ALTER TABLE order_items ADD CONSTRAINT order_items_ibfk_2 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE"
];

$success = true;
$messages = [];

foreach ($queries as $query) {
    if (!mysqli_query($conn, $query)) {
        $success = false;
        $messages[] = "Hata: " . mysqli_error($conn);
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Güncelleme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container mt-5">
        <div class="card">
            <div class="card-body">
                <?php if ($success): ?>
                    <h3 class="text-success">Veritabanı başarıyla güncellendi!</h3>
                    <p>Direct sales tablosu oluşturuldu ve foreign key kısıtlamaları güncellendi. Artık ürünleri güvenle silebilirsiniz.</p>
                <?php else: ?>
                    <h3 class="text-danger">Güncelleme sırasında hata oluştu!</h3>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-danger"><?php echo $message; ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-primary">Panele Dön</a>
            </div>
        </div>
    </div>
</body>
</html> 