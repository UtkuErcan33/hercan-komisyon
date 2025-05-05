<?php
session_start();
require_once "config.php";

// Kullanıcı girişi yapılmamışsa veya admin ise ana sayfaya yönlendir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "customer"){
    header("location: index.php");
    exit;
}

// Sepetten ürün silme
if(isset($_POST['remove_from_cart'])) {
    $cart_id = $_POST['cart_id'];
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['id']);
        mysqli_stmt_execute($stmt);
    }
}

// Siparişi tamamlama
if(isset($_POST['place_order'])) {
    // Toplam tutarı hesapla
    $total = 0;
    $cart_items = [];
    $stock_error = false;
    
    $sql = "SELECT c.*, p.price, p.name, p.weight, p.stock_quantity FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Stok kontrolü
            if($row['quantity'] > $row['stock_quantity']) {
                $stock_error = true;
                $error_msg = "Üzgünüz, {$row['name']} ürününden yeterli stok bulunmamaktadır. Mevcut stok: {$row['stock_quantity']} kg";
                break;
            }
            $total += $row['price'] * $row['quantity'];
            $cart_items[] = $row;
        }
    }
    
    if ($total > 0 && !$stock_error) {
        // Siparişi oluştur
        $sql = "INSERT INTO orders (user_id, total_amount) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "id", $_SESSION['id'], $total);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
            
            // Sipariş detaylarını ekle ve stoktan düş
            foreach ($cart_items as $item) {
                $total_weight = $item['quantity'] * $item['weight'];
                
                // Stoktan düş
                $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                if ($stmt2 = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt2, "di", $item['quantity'], $item['product_id']);
                    mysqli_stmt_execute($stmt2);
                }
                
                // Sipariş detayını ekle
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price, total_weight) 
                        VALUES (?, ?, ?, ?, ?)";
                if ($stmt3 = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt3, "iiidd", $order_id, $item['product_id'], 
                                         $item['quantity'], $item['price'], $total_weight);
                    mysqli_stmt_execute($stmt3);
                }
            }
            
            // Sepeti temizle
            $sql = "DELETE FROM cart WHERE user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
                mysqli_stmt_execute($stmt);
            }
            
            // Siparişler sayfasına yönlendir
            header("location: orders.php");
            exit;
        }
    }
}

// Sepetteki ürünleri getir
$cart_items = [];
$total = 0;

$sql = "SELECT c.*, p.name, p.price, p.image_url FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $cart_items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim - Hercan Komisyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #15803d;
        }
        
        .navbar-custom {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
        }
        
        .login-btn {
            background-color: var(--primary-color);
            color: white !important;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .login-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .cart-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf"></i> Hercan Komisyon
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle login-btn" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION["display_name"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Sepetim
                            </a></li>
                            <li><a class="dropdown-item" href="orders.php">
                                <i class="fas fa-box"></i> Siparişlerim
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Sepetim</h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Sepetiniz boş.
                <a href="index.php" class="alert-link">Alışverişe başlayın</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Fiyat</th>
                            <th>Adet</th>
                            <th>Toplam</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="cart-img me-3">
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td>₺<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_from_cart" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Toplam:</strong></td>
                            <td><strong>₺<?php echo number_format($total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="text-end mt-4">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Alışverişe Devam Et
                </a>
                <form method="post" class="d-inline">
                    <button type="submit" name="place_order" class="btn btn-primary">
                        <i class="fas fa-check"></i> Siparişi Tamamla
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 