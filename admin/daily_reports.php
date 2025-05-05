<?php
session_start();
require_once "../config.php";

// Sadece admin erişebilir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Tarih seçimi yapılmamışsa bugünün tarihini al
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Günlük rapor verilerini getir
$sql = "SELECT 
        COUNT(CASE WHEN status != 'cancelled' THEN 1 END) as total_orders,
        SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as daily_total,
        SUM(CASE WHEN status != 'cancelled' AND payment_status = 'paid' THEN total_amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN status != 'cancelled' AND payment_status = 'unpaid' THEN total_amount ELSE 0 END) as total_unpaid,
        COUNT(CASE WHEN status != 'cancelled' AND payment_status = 'paid' THEN 1 END) as paid_orders,
        COUNT(CASE WHEN status != 'cancelled' AND payment_status = 'unpaid' THEN 1 END) as unpaid_orders,
        SUM(CASE WHEN status != 'cancelled' THEN (SELECT SUM(total_weight) FROM order_items WHERE order_id = orders.id) ELSE 0 END) as total_weight
        FROM orders 
        WHERE DATE(created_at) = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $selected_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$report = mysqli_fetch_assoc($result);

// Günün siparişlerini getir (iptal edilenler en sonda gösterilecek)
$sql = "SELECT o.*, u.display_name, 
        (SELECT SUM(total_weight) FROM order_items WHERE order_id = o.id) as order_weight,
        GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as products
        FROM orders o 
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.created_at) = ?
        GROUP BY o.id
        ORDER BY o.status = 'cancelled', o.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $selected_date);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}

// Ödeme durumunu güncelle
if(isset($_POST['update_payment'])) {
    $order_id = $_POST['order_id'];
    $payment_status = $_POST['payment_status'];
    
    $sql = "UPDATE orders SET payment_status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $payment_status, $order_id);
        mysqli_stmt_execute($stmt);
        
        // Sayfayı yenile
        header("Location: daily_reports.php?date=" . $selected_date);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Günlük Rapor - <?php echo $selected_date; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .summary-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-title {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .summary-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        .summary-detail {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        .paid-amount {
            color: #198754;
        }
        .unpaid-amount {
            color: #dc3545;
        }
        .back-button {
            text-decoration: none;
            color: #666;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-button:hover {
            color: #333;
        }
        .back-button i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>

    <div class="container my-5">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo date('d.m.Y', strtotime($selected_date)); ?> Tarihli Rapor</h2>
            <form class="d-flex">
                <input type="date" name="date" value="<?php echo $selected_date; ?>" class="form-control me-2">
                <button type="submit" class="btn btn-primary">Göster</button>
            </form>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-title">Toplam Sipariş</div>
                    <div class="summary-value"><?php echo $report['total_orders']; ?></div>
                    <div class="summary-detail">
                        <span class="text-success"><?php echo $report['paid_orders']; ?> Ödenen</span> / 
                        <span class="text-danger"><?php echo $report['unpaid_orders']; ?> Ödenmeyen</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-title">Toplam Tutar</div>
                    <div class="summary-value">₺<?php echo number_format($report['daily_total'], 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-title">Ödenen Tutar</div>
                    <div class="summary-value paid-amount">₺<?php echo number_format($report['total_paid'], 2); ?></div>
                    <div class="summary-detail">
                        Toplam tutarın <?php echo $report['daily_total'] > 0 ? number_format(($report['total_paid'] / $report['daily_total']) * 100, 1) : 0; ?>%'si
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-title">Ödenmeyen Tutar</div>
                    <div class="summary-value unpaid-amount">₺<?php echo number_format($report['total_unpaid'], 2); ?></div>
                    <div class="summary-detail">
                        Toplam tutarın <?php echo $report['daily_total'] > 0 ? number_format(($report['total_unpaid'] / $report['daily_total']) * 100, 1) : 0; ?>%'si
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title h5 mb-0">Günün Siparişleri</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Ürünler</th>
                                <th>Tutar</th>
                                <th>Kilo</th>
                                <th>Durum</th>
                                <th>Ödeme Durumu</th>
                                <th>Saat</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['display_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['products']); ?></td>
                                <td>₺<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo number_format($order['order_weight'], 2); ?> kg</td>
                                <td>
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
                                </td>
                                <td>
                                    <form method="post" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="payment_status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                            <option value="unpaid" <?php echo $order['payment_status'] == 'unpaid' ? 'selected' : ''; ?>>Ödenmedi</option>
                                            <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Ödendi</option>
                                        </select>
                                        <input type="hidden" name="update_payment" value="1">
                                    </form>
                                </td>
                                <td><?php echo date('H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="../orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Bu tarihte sipariş bulunmamaktadır.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 