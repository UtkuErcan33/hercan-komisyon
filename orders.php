<?php
session_start();
require_once "config.php";

// Kullanıcı girişi yapılmamışsa ana sayfaya yönlendir
if(!isset($_SESSION["loggedin"])){
    header("location: index.php");
    exit;
}

// Siparişleri getir
$orders = [];
$sql = "";

if($_SESSION["user_type"] === "admin") {
    // Admin tüm siparişleri görür
    $sql = "SELECT o.*, u.display_name, u.email FROM orders o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
} else {
    // Müşteri kendi siparişlerini görür
    $sql = "SELECT o.* FROM orders o 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
}

$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    // Sipariş detaylarını getir
    $order_items = [];
    $sql = "SELECT oi.*, p.name, p.image_url FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?";
    if ($stmt2 = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt2, "i", $row['id']);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        while ($item = mysqli_fetch_assoc($result2)) {
            $order_items[] = $item;
        }
    }
    $row['items'] = $order_items;
    $orders[] = $row;
}

// Sipariş durumunu güncelle (sadece admin)
if($_SESSION["user_type"] === "admin" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : 'unpaid';
    
    $sql = "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssi", $status, $payment_status, $order_id);
        mysqli_stmt_execute($stmt);
        header("location: orders.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparişler - Hercan Komisyon</title>
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
        
        .order-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .order-header {
            background-color: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-items {
            padding: 1rem;
        }
        
        .item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipping {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php if($_SESSION["user_type"] === "admin"): ?>
        <?php include "admin/header.php"; ?>
    <?php else: ?>
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
    <?php endif; ?>

    <div class="container my-5">
        <h2 class="mb-4">
            <?php echo $_SESSION["user_type"] === "admin" ? "Tüm Siparişler" : "Siparişlerim"; ?>
        </h2>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo $_SESSION["user_type"] === "admin" ? 
                    "Henüz sipariş bulunmuyor." : 
                    "Henüz siparişiniz bulunmuyor."; ?>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card order-card">
                    <div class="order-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 me-3">Sipariş #<?php echo $order['id']; ?></h5>
                                <?php if(isset($order['display_name'])): ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($order['display_name']); ?>)</small>
                                <?php endif; ?>
                            </div>
                            <?php if($_SESSION["user_type"] === "admin"): ?>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <div class="d-flex gap-2">
                                    <div>
                                        <label class="form-label small mb-0">Sipariş Durumu</label>
                                        <select name="status" class="form-select form-select-sm" style="min-width: 120px;">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                                            <option value="approved" <?php echo $order['status'] == 'approved' ? 'selected' : ''; ?>>Onaylandı</option>
                                            <option value="shipping" <?php echo $order['status'] == 'shipping' ? 'selected' : ''; ?>>Kargoda</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Teslim Edildi</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label small mb-0">Ödeme Durumu</label>
                                        <select name="payment_status" class="form-select form-select-sm" style="min-width: 120px;">
                                            <option value="unpaid" <?php echo $order['payment_status'] == 'unpaid' ? 'selected' : ''; ?>>Ödenmedi</option>
                                            <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Ödendi</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-end">
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Güncelle</button>
                                    </div>
                                </div>
                            </form>
                            <?php else: ?>
                            <div>
                                <span class="badge bg-<?php 
                                    echo $order['status'] == 'pending' ? 'warning' : 
                                        ($order['status'] == 'approved' ? 'info' : 
                                        ($order['status'] == 'shipping' ? 'primary' : 
                                        ($order['status'] == 'delivered' ? 'success' : 'danger')));
                                ?>">
                                    <?php 
                                    echo $order['status'] == 'pending' ? 'Beklemede' : 
                                        ($order['status'] == 'approved' ? 'Onaylandı' : 
                                        ($order['status'] == 'shipping' ? 'Kargoda' : 
                                        ($order['status'] == 'delivered' ? 'Teslim Edildi' : 'İptal Edildi')));
                                    ?>
                                </span>
                                <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?> ms-2">
                                    <?php echo $order['payment_status'] == 'paid' ? 'Ödendi' : 'Ödenmedi'; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-items">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Fiyat</th>
                                        <th>Adet</th>
                                        <th>Kilo</th>
                                        <th class="text-end">Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         class="item-img me-3">
                                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                </div>
                                            </td>
                                            <td>₺<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($item['total_weight'], 2); ?> kg</td>
                                            <td class="text-end">
                                                ₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end"><strong>Toplam Kilo:</strong></td>
                                        <td class="text-end">
                                            <?php
                                            $total_weight = array_sum(array_column($order['items'], 'total_weight'));
                                            echo number_format($total_weight, 2);
                                            ?> kg
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="text-end"><strong>Toplam Tutar:</strong></td>
                                        <td class="text-end">
                                            <strong>₺<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 