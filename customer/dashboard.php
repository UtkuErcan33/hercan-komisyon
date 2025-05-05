<?php
session_start();
require_once "../config.php";

// Kullanıcı girişi yapılmamışsa veya admin ise ana sayfaya yönlendir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "customer"){
    header("location: ../index.php");
    exit;
}

// Siparişleri getir
$orders = [];
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
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
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - Hercan Komisyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #a8e6cf, #dcedc1);
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .floating-fruits {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            top: 0;
            left: 0;
        }

        .fruit {
            position: absolute;
            opacity: 0.6;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: #16a34a !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .container {
            position: relative;
            z-index: 2;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            margin-bottom: 2rem;
            animation: card-appear 0.6s ease-out;
        }

        @keyframes card-appear {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
            animation: card-appear 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.2s; }
        .stat-card:nth-child(2) { animation-delay: 0.4s; }
        .stat-card:nth-child(3) { animation-delay: 0.6s; }

        .stat-icon {
            font-size: 2.5rem;
            color: #16a34a;
            margin-bottom: 1rem;
            animation: icon-spin 1s ease-out;
        }

        @keyframes icon-spin {
            from {
                transform: rotate(-180deg) scale(0);
            }
            to {
                transform: rotate(0) scale(1);
            }
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 1rem;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            animation: card-appear 0.6s ease-out;
            animation-fill-mode: both;
        }

        .order-card:nth-child(1) { animation-delay: 0.8s; }
        .order-card:nth-child(2) { animation-delay: 1.0s; }
        .order-card:nth-child(3) { animation-delay: 1.2s; }
        .order-card:nth-child(4) { animation-delay: 1.4s; }
        .order-card:nth-child(5) { animation-delay: 1.6s; }

        .order-header {
            background: rgba(248, 249, 250, 0.9);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(233, 236, 239, 0.5);
        }

        .order-items {
            padding: 1.5rem;
        }

        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            animation: badge-appear 0.3s ease-out;
        }

        @keyframes badge-appear {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .action-buttons {
            display: flex;
            gap: 1.5rem;
            margin-top: 2rem;
            animation: card-appear 0.6s ease-out 1.8s both;
        }

        .action-button {
            flex: 1;
            padding: 1.25rem;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            backdrop-filter: blur(10px);
        }

        .primary-button {
            background: #16a34a;
            color: white;
        }

        .primary-button:hover {
            background: #15803d;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(22, 163, 74, 0.2);
        }

        .secondary-button {
            background: rgba(255, 255, 255, 0.9);
            color: #4b5563;
        }

        .secondary-button:hover {
            background: rgba(255, 255, 255, 1);
            color: #1f2937;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .item-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .item-img:hover {
            transform: scale(1.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: #4b5563;
            border: none;
        }

        .table td {
            border: none;
            vertical-align: middle;
        }

        .btn-outline-success {
            border-color: #16a34a;
            color: #16a34a;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-success:hover {
            background: #16a34a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }
    </style>
</head>
<body>
    <div class="floating-fruits">
        <?php
        $fruits = ['🍎', '🍐', '🍊', '🍋', '🍇', '🍉', '🍓', '🥝'];
        for($i = 0; $i < 15; $i++) {
            $fruit = $fruits[array_rand($fruits)];
            $delay = rand(0, 10000) / 1000;
            $duration = rand(10000, 20000) / 1000;
            $size = rand(20, 40);
            $left = rand(0, 100);
            echo "<div class='fruit' style='
                left: {$left}%;
                font-size: {$size}px;
                animation: float {$duration}s infinite linear;
                animation-delay: {$delay}s;
            '>{$fruit}</div>";
        }
        ?>
    </div>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-leaf"></i> Hercan Komisyon
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-success" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION["display_name"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../cart.php">
                                <i class="fas fa-shopping-cart"></i> Sepetim
                            </a></li>
                            <li><a class="dropdown-item" href="../orders.php">
                                <i class="fas fa-box"></i> Siparişlerim
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="welcome-card">
            <h1 class="mb-4">Hoş Geldiniz, <?php echo htmlspecialchars($_SESSION["display_name"]); ?></h1>
            <p class="text-muted mb-0">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($_SESSION["email"]); ?>
            </p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value">
                    <?php echo count($orders); ?>
                </div>
                <div class="stat-label">Toplam Sipariş</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value">
                    <?php 
                    $pending = array_filter($orders, function($order) {
                        return $order['status'] === 'pending';
                    });
                    echo count($pending);
                    ?>
                </div>
                <div class="stat-label">Bekleyen Sipariş</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">
                    <?php 
                    $completed = array_filter($orders, function($order) {
                        return $order['status'] === 'delivered';
                    });
                    echo count($completed);
                    ?>
                </div>
                <div class="stat-label">Tamamlanan Sipariş</div>
            </div>
        </div>

        <h2 class="mb-4">Son Siparişleriniz</h2>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Henüz siparişiniz bulunmuyor.
            </div>
        <?php else: ?>
            <?php 
            // Son 5 siparişi göster
            $recent_orders = array_slice($orders, 0, 5);
            foreach ($recent_orders as $order): 
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>Sipariş No:</strong> #<?php echo $order['id']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Tarih:</strong> 
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Toplam:</strong> 
                                ₺<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php
                                    switch($order['status']) {
                                        case 'pending':
                                            echo '<i class="fas fa-clock"></i> Onay Bekliyor';
                                            break;
                                        case 'approved':
                                            echo '<i class="fas fa-check"></i> Onaylandı';
                                            break;
                                        case 'shipping':
                                            echo '<i class="fas fa-truck"></i> Siparişiniz Yolda';
                                            break;
                                        case 'delivered':
                                            echo '<i class="fas fa-box-check"></i> Sipariş Tamamlandı';
                                            break;
                                        case 'cancelled':
                                            echo '<i class="fas fa-times"></i> İptal Edildi';
                                            break;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="order-items">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Fiyat</th>
                                        <th>Adet</th>
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
                                            <td class="text-end">
                                                ₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($orders) > 5): ?>
                <div class="text-center">
                    <a href="../orders.php" class="btn btn-outline-success">
                        <i class="fas fa-list"></i> Tüm Siparişleri Görüntüle
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="../index.php" class="action-button primary-button">
                <i class="fas fa-shopping-basket"></i>
                Alışverişe Devam Et
            </a>
            <a href="../cart.php" class="action-button secondary-button">
                <i class="fas fa-shopping-cart"></i>
                Sepetim
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 