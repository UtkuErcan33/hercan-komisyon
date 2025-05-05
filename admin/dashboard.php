<?php
session_start();

// Kullanıcı giriş yapmamışsa veya admin değilse ana sayfaya yönlendir
if(!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "admin"){
    header("location: /web-sitesi/login.php");
    exit;
}

require_once "../config.php";

// Ürün silme işlemi
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $sql = "DELETE FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_POST['product_id']);
        mysqli_stmt_execute($stmt);
    }
}

// Ürün ekleme işlemi
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $weight = trim($_POST['weight']);
    $image_url = trim($_POST['image_url']);
    
    $sql = "INSERT INTO products (name, description, price, weight, image_url) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssdds", $name, $description, $price, $weight, $image_url);
        mysqli_stmt_execute($stmt);
    }
}

// Ürün güncelleme işlemi
if (isset($_POST['update_product'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $image_url = trim($_POST['image_url']);
    
    $sql = "UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssdsi", $name, $description, $price, $image_url, $id);
        mysqli_stmt_execute($stmt);
    }
}

// Stok ekleme işlemi
if (isset($_POST['add_stock'])) {
    $product_id = $_POST['product_id'];
    $add_quantity = trim($_POST['add_quantity']);
    
    $sql = "UPDATE products SET stock_quantity = COALESCE(stock_quantity, 0) + ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "di", $add_quantity, $product_id);
        if(mysqli_stmt_execute($stmt)) {
            $success_msg = "Stok başarıyla güncellendi.";
        } else {
            $error_msg = "Stok güncellenirken bir hata oluştu.";
        }
    }
}

// Stok düşme işlemi
if (isset($_POST['reduce_stock'])) {
    $product_id = $_POST['product_id'];
    $reduce_quantity = trim($_POST['reduce_quantity']);
    
    // Önce mevcut stoku kontrol et
    $check_sql = "SELECT stock_quantity FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $current_stock = mysqli_fetch_assoc($result)['stock_quantity'];
        
        if ($current_stock >= $reduce_quantity) {
            $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "di", $reduce_quantity, $product_id);
                if(mysqli_stmt_execute($stmt)) {
                    $success_msg = "Stok başarıyla düşüldü.";
                } else {
                    $error_msg = "Stok güncellenirken bir hata oluştu.";
                }
            }
        } else {
            $error_msg = "Yetersiz stok! Mevcut stok: " . number_format($current_stock, 2) . " kg";
        }
    }
}

// Sipariş durumu güncelleme
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Sipariş iptal ediliyorsa, stok miktarlarını geri ekle
    if ($status === 'cancelled') {
        // Önce sipariş ürünlerini getir
        $items_sql = "SELECT oi.product_id, oi.quantity FROM order_items oi WHERE oi.order_id = ?";
        if ($stmt = mysqli_prepare($conn, $items_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $items_result = mysqli_stmt_get_result($stmt);
            
            // Her ürün için stok miktarını geri ekle
            while ($item = mysqli_fetch_assoc($items_result)) {
                $update_stock_sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                if ($stmt2 = mysqli_prepare($conn, $update_stock_sql)) {
                    mysqli_stmt_bind_param($stmt2, "di", $item['quantity'], $item['product_id']);
                    mysqli_stmt_execute($stmt2);
                }
            }
        }
    }
    
    // Sipariş durumunu güncelle
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['status_message'] = "Sipariş durumu başarıyla güncellendi!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Gün sonu raporu oluşturma
if (isset($_POST['create_daily_report'])) {
    // Bugünün teslim edilmiş siparişlerini getir
    $sql = "SELECT COUNT(*) as total_orders, 
            SUM(total_amount) as total_amount,
            (SELECT SUM(total_weight) FROM order_items oi 
             JOIN orders o ON oi.order_id = o.id 
             WHERE DATE(o.created_at) = CURDATE() 
             AND o.status = 'delivered') as total_weight
            FROM orders 
            WHERE DATE(created_at) = CURDATE() 
            AND status = 'delivered'";
    
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    if($row['total_orders'] > 0) {
        // Önce bugün için mevcut bir rapor var mı kontrol et
        $check_sql = "SELECT * FROM daily_reports WHERE DATE(report_date) = CURDATE()";
        $check_result = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($check_result) > 0) {
            // Mevcut raporu güncelle
            $sql = "UPDATE daily_reports 
                    SET total_orders = total_orders + ?, 
                        total_amount = total_amount + ?, 
                        total_weight = total_weight + ?
                    WHERE DATE(report_date) = CURDATE()";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "idd", 
                    $row['total_orders'], 
                    $row['total_amount'],
                    $row['total_weight']
                );
                mysqli_stmt_execute($stmt);
                $success_msg = "Gün sonu raporu başarıyla güncellendi.";
            }
        } else {
            // Yeni rapor oluştur
            $sql = "INSERT INTO daily_reports (report_date, total_orders, total_amount, total_weight) 
                    VALUES (CURDATE(), ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "idd", 
                    $row['total_orders'], 
                    $row['total_amount'],
                    $row['total_weight']
                );
                mysqli_stmt_execute($stmt);
                $success_msg = "Gün sonu raporu başarıyla oluşturuldu.";
            }
        }
    } else {
        $error_msg = "Bugün için teslim edilmiş sipariş bulunmuyor.";
    }
}

// Ürünleri getir
$products = [];
$sql = "SELECT * FROM products ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// İstatistikleri getir
$stats = [];

// Toplam sipariş sayısı
$sql = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
        SUM(total_amount) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount
        FROM orders";
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);

// Son 5 siparişi getir
$sql = "SELECT o.*, u.display_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
$recent_orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $recent_orders[] = $row;
}

// Toplam müşteri sayısı
$sql = "SELECT COUNT(*) as total_customers FROM users WHERE user_type = 'customer'";
$result = mysqli_query($conn, $sql);
$customer_stats = mysqli_fetch_assoc($result);

// Siparişleri getir
$orders = [];
$sql = "SELECT o.*, u.display_name, u.email,
        (SELECT SUM(total_weight) FROM order_items WHERE order_id = o.id) as total_weight
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $sql);
while ($order = mysqli_fetch_assoc($result)) {
    // Sipariş ürünlerini getir
    $items_sql = "SELECT oi.*, p.name, p.image_url 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
    if ($stmt = mysqli_prepare($conn, $items_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $order['id']);
        mysqli_stmt_execute($stmt);
        $items_result = mysqli_stmt_get_result($stmt);
        
        $items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        $order['items'] = $items;
    }
    $orders[] = $order;
}

// Serbest Satış Bölümü
if (isset($_POST['make_sale'])) {
    $product_id = $_POST['sale_product_id'];
    $quantity = $_POST['sale_quantity'];
    $sale_price = $_POST['sale_price'];
    
    // Stok kontrolü
    $check_sql = "SELECT stock_quantity, name FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        
        if ($product['stock_quantity'] >= $quantity) {
            // Yeterli stok var, önce sipariş kaydı oluştur
            $total_amount = $quantity * $sale_price;
            
            // Sipariş oluştur (admin kullanıcısı ile)
            $create_order_sql = "INSERT INTO orders (user_id, total_amount, status, notes) VALUES (?, ?, 'approved', 'Serbest Satış')";
            if ($stmt = mysqli_prepare($conn, $create_order_sql)) {
                mysqli_stmt_bind_param($stmt, "id", $_SESSION['id'], $total_amount);
                if(mysqli_stmt_execute($stmt)) {
                    $order_id = mysqli_insert_id($conn);
                    
                    // Sipariş detayını ekle
                    $create_order_item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, total_weight) VALUES (?, ?, ?, ?, ?)";
                    if ($stmt = mysqli_prepare($conn, $create_order_item_sql)) {
                        $total_weight = $quantity;
                        mysqli_stmt_bind_param($stmt, "iiddd", $order_id, $product_id, $quantity, $sale_price, $total_weight);
                        mysqli_stmt_execute($stmt);
                    }
                    
                    // Stoktan düş
                    $update_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                    if ($stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($stmt, "di", $quantity, $product_id);
                        if(mysqli_stmt_execute($stmt)) {
                            // Satış kaydını tut
                            $sale_sql = "INSERT INTO direct_sales (product_id, quantity, price, sale_date) VALUES (?, ?, ?, NOW())";
                            if ($stmt = mysqli_prepare($conn, $sale_sql)) {
                                mysqli_stmt_bind_param($stmt, "idd", $product_id, $quantity, $sale_price);
                                if(mysqli_stmt_execute($stmt)) {
                                    // Günlük rapora ekle
                                    $check_report_sql = "SELECT id FROM daily_reports WHERE DATE(report_date) = CURDATE()";
                                    $check_result = mysqli_query($conn, $check_report_sql);
                                    
                                    if(mysqli_num_rows($check_result) > 0) {
                                        // Mevcut raporu güncelle
                                        $update_report_sql = "UPDATE daily_reports 
                                                            SET direct_sales_count = direct_sales_count + 1,
                                                                direct_sales_amount = direct_sales_amount + ?,
                                                                direct_sales_weight = direct_sales_weight + ?
                                                            WHERE DATE(report_date) = CURDATE()";
                                        if ($stmt = mysqli_prepare($conn, $update_report_sql)) {
                                            mysqli_stmt_bind_param($stmt, "dd", $total_amount, $quantity);
                                            mysqli_stmt_execute($stmt);
                                        }
                                    } else {
                                        // Yeni rapor oluştur
                                        $create_report_sql = "INSERT INTO daily_reports 
                                                            (report_date, total_orders, total_amount, total_weight,
                                                             direct_sales_count, direct_sales_amount, direct_sales_weight)
                                                            VALUES (CURDATE(), 0, 0, 0, 1, ?, ?)";
                                        if ($stmt = mysqli_prepare($conn, $create_report_sql)) {
                                            mysqli_stmt_bind_param($stmt, "dd", $total_amount, $quantity);
                                            mysqli_stmt_execute($stmt);
                                        }
                                    }
                                    
                                    $success_msg = "Satış başarıyla gerçekleştirildi.";
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $error_msg = "Yetersiz stok! {$product['name']} için mevcut stok: " . number_format($product['stock_quantity'], 2) . " kg";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hercan Komisyon</title>
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
            padding-top: 80px; /* Header için boşluk */
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

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info i {
            font-size: 3rem;
            color: #16a34a;
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

        .user-info .details h1 {
            margin: 0;
            font-size: 2rem;
            color: #1f2937;
        }

        .user-info .details p {
            margin: 0;
            color: #6b7280;
            font-size: 1.1rem;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #b91c1c;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.2);
            color: white;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2em;
            color: #16a34a;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 1.1em;
        }

        .products-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            animation: card-appear 0.6s ease-out 0.8s both;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 1.8rem;
        }

        .add-product-btn {
            background: #16a34a;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .add-product-btn:hover {
            background: #15803d;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(22, 163, 74, 0.2);
            color: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: rgba(249, 250, 251, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            animation: card-appear 0.6s ease-out both;
        }

        .product-card:nth-child(3n+1) { animation-delay: 1.0s; }
        .product-card:nth-child(3n+2) { animation-delay: 1.2s; }
        .product-card:nth-child(3n+3) { animation-delay: 1.4s; }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-details h3 {
            margin: 0 0 0.75rem 0;
            color: #1f2937;
            font-size: 1.25rem;
        }

        .product-details p {
            margin: 0 0 1.25rem 0;
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .product-price {
            font-weight: 700;
            color: #16a34a;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .product-stock mb-3 {
            margin-bottom: 1rem;
        }

        .stock-actions mb-3 {
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.75rem;
        }

        .edit-btn, .delete-btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .edit-btn {
            background: #3b82f6;
            color: white;
        }

        .edit-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        .delete-btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
            animation: modal-appear 0.3s ease-out;
        }

        @keyframes modal-appear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .modal-header h3 {
            margin: 0;
            color: #1f2937;
            font-size: 1.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.3s;
        }

        .close-modal:hover {
            color: #1f2937;
            transform: rotate(90deg);
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .form-group input, .form-group textarea {
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1);
            transform: translateY(-2px);
        }

        .save-btn {
            background: #16a34a;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }

        .save-btn:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(22, 163, 74, 0.2);
        }

        .orders-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            margin-bottom: 2rem;
            animation: card-appear 0.6s ease-out 0.8s both;
        }

        .order-card {
            background: rgba(249, 250, 251, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            background: rgba(248, 249, 250, 0.9);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(233, 236, 239, 0.5);
        }

        .order-items {
            padding: 1.5rem;
        }

        .item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .item-img:hover {
            transform: scale(1.1);
        }

        .status-select {
            padding: 0.5rem;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            background: white;
            width: 100%;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .status-select:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
            outline: none;
        }

        .status-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            color: #4b5563;
            font-weight: 600;
            border: none;
        }

        .table td {
            vertical-align: middle;
            border: none;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .orders-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .orders-section h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.5em;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            background-color: transparent;
        }

        th, td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .actions {
            white-space: nowrap;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 2px;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .fas {
            margin-right: 5px;
        }

        .order-products {
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .order-products .card {
            transition: transform 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .order-products .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .order-products .card-img-top {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .order-products .card-body {
            padding: 15px;
        }

        .order-products .card-title {
            font-size: 1rem;
            margin-bottom: 10px;
            color: #333;
        }

        .order-products .card-text {
            font-size: 0.9rem;
            color: #666;
        }

        tr.order-details-row {
            background-color: #f8f9fa;
        }

        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: white;
        }

        .btn-info:hover {
            background-color: #31d2f2;
            border-color: #25cff2;
            color: white;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>

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

    <div class="dashboard-container">
        <div class="welcome-section">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <div class="details">
                    <h1>Hoş Geldiniz, <?php echo htmlspecialchars($_SESSION["display_name"]); ?></h1>
                    <p><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                </div>
            </div>
            <a href="/web-sitesi/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Toplam Sipariş</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Bekleyen Sipariş</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                <div class="stat-label">Tamamlanan Sipariş</div>
            </div>
        </div>

        <!-- Siparişler Bölümü -->
        <div class="orders-section">
            <div class="section-header d-flex justify-content-between align-items-center">
                <h2>Sipariş Yönetimi</h2>
                <div class="text-end mb-4">
                    <form method="post" class="d-inline" onsubmit="return confirm('Gün sonu raporu oluşturmak istediğinizden emin misiniz? Bu işlem onaylanmış siparişleri teslim edildi olarak işaretleyecek ve günlük rapora ekleyecektir.');">
                        <button type="submit" name="create_daily_report" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Gün Sonu Raporu Oluştur
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Bekleyen Siparişler -->
            <div class="pending-orders mb-4">
                <h3 class="mb-3">Bekleyen Siparişler</h3>
                <?php if (isset($_SESSION['status_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['status_message'];
                        unset($_SESSION['status_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Toplam Tutar</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT o.*, u.display_name 
                                    FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    WHERE o.status = 'pending' 
                                    ORDER BY o.created_at DESC";
                            $result = mysqli_query($conn, $sql);
                            while ($order = mysqli_fetch_assoc($result)):
                            ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['display_name']); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2); ?> ₺</td>
                                <td>
                                    <?php
                                    $status_text = [
                                        'pending' => 'Bekliyor',
                                        'approved' => 'Onaylandı',
                                        'shipping' => 'Kargoda',
                                        'delivered' => 'Teslim Edildi',
                                        'cancelled' => 'İptal Edildi'
                                    ];
                                    echo $status_text[$order['status']];
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="actions">
                                    <button type="button" class="btn btn-info mb-2" onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i> Ürünleri Göster
                                    </button>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" name="update_status" class="btn btn-success">
                                            <i class="fas fa-check"></i> Onayla
                                        </button>
                                    </form>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Bu siparişi iptal etmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" name="update_status" class="btn btn-danger">
                                            <i class="fas fa-times"></i> İptal Et
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="orderDetails_<?php echo $order['id']; ?>" style="display: none;">
                                <td colspan="6">
                                    <div class="order-products p-3">
                                        <div class="row">
                                            <?php
                                            // Sipariş ürünlerini getir
                                            $items_sql = "SELECT oi.*, p.name, p.image_url FROM order_items oi 
                                                        JOIN products p ON oi.product_id = p.id 
                                                        WHERE oi.order_id = ?";
                                            $items_stmt = mysqli_prepare($conn, $items_sql);
                                            mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
                                            mysqli_stmt_execute($items_stmt);
                                            $items_result = mysqli_stmt_get_result($items_stmt);
                                            
                                            while ($item = mysqli_fetch_assoc($items_result)): ?>
                                                <div class="col-md-3 mb-3">
                                                    <div class="card h-100">
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                             class="card-img-top" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             style="height: 150px; object-fit: cover;">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                            <p class="card-text">
                                                                Miktar: <?php echo $item['quantity']; ?><br>
                                                                Fiyat: ₺<?php echo number_format($item['price'], 2); ?><br>
                                                                Toplam: ₺<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Onaylanmış Siparişler -->
            <div class="orders-section">
                <h2>Onaylanmış Siparişler</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Ürünler</th>
                                <th>Toplam Tutar</th>
                                <th>Toplam Kilo</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT o.*, u.display_name, 
                                    (SELECT SUM(total_weight) FROM order_items WHERE order_id = o.id) as total_weight
                                    FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    WHERE o.status != 'pending' 
                                    ORDER BY o.created_at DESC";
                            $result = mysqli_query($conn, $sql);
                            while ($order = mysqli_fetch_assoc($result)):
                                // Sipariş ürünlerini getir
                                $items_sql = "SELECT oi.*, p.name FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?";
                                $items_stmt = mysqli_prepare($conn, $items_sql);
                                mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
                                mysqli_stmt_execute($items_stmt);
                                $items_result = mysqli_stmt_get_result($items_stmt);
                                $items = [];
                                while ($item = mysqli_fetch_assoc($items_result)) {
                                    $items[] = $item['name'] . ' (x' . $item['quantity'] . ')';
                                }
                            ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['display_name']); ?></td>
                                <td><?php echo htmlspecialchars(implode(", ", $items)); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2); ?> ₺</td>
                                <td><?php echo number_format($order['total_weight'], 2); ?> kg</td>
                                <td>
                                    <?php
                                    $status_text = [
                                        'pending' => 'Bekliyor',
                                        'approved' => 'Onaylandı',
                                        'delivered' => 'Teslim Edildi',
                                        'cancelled' => 'İptal Edildi'
                                    ];
                                    echo $status_text[$order['status']];
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="actions">
                                    <?php if ($order['status'] == 'approved'): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="delivered">
                                            <button type="submit" name="update_status" class="btn btn-success">
                                                <i class="fas fa-truck"></i> Teslim Edildi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="products-section">
            <div class="section-header">
                <h2>Ürün Yönetimi</h2>
                <button class="add-product-btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Yeni Ürün Ekle
                </button>
            </div>
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-details">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="product-price">₺<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-stock mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-muted">Stok Durumu:</strong>
                                <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?> rounded-pill">
                                    <?php echo number_format($product['stock_quantity'], 2); ?> kg
                                </span>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo min(($product['stock_quantity'] / 100) * 100, 100); ?>%" 
                                     aria-valuenow="<?php echo $product['stock_quantity']; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <div class="stock-actions mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <form method="post" class="stock-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-plus-circle text-success"></i>
                                            </span>
                                            <input type="number" name="add_quantity" 
                                                   class="form-control" 
                                                   placeholder="Ekle" 
                                                   step="0.01" min="0.01" 
                                                   required>
                                            <span class="input-group-text bg-light">kg</span>
                                            <button type="submit" name="add_stock" class="btn btn-success">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-6">
                                    <form method="post" class="stock-form" onsubmit="return confirm('Stoktan düşmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-minus-circle text-danger"></i>
                                            </span>
                                            <input type="number" name="reduce_quantity" 
                                                   class="form-control" 
                                                   placeholder="Düş" 
                                                   step="0.01" min="0.01" 
                                                   max="<?php echo $product['stock_quantity']; ?>"
                                                   required>
                                            <span class="input-group-text bg-light">kg</span>
                                            <button type="submit" name="reduce_stock" class="btn btn-danger">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="product-actions">
                            <button class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                <i class="fas fa-edit"></i> Düzenle
                            </button>
                            <form method="post" style="flex: 1;" onsubmit="return confirm('Bu ürünü silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="delete-btn">
                                    <i class="fas fa-trash"></i> Sil
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Serbest Satış Bölümü -->
        <div class="products-section">
            <div class="section-header">
                <h2>Serbest Satış</h2>
            </div>
            
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" id="saleForm">
                                <div class="mb-3">
                                    <label class="form-label">Ürün Seçin</label>
                                    <select name="sale_product_id" class="form-select form-select-lg" required onchange="updatePrice(this)">
                                        <option value="">Ürün seçin...</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-price="<?php echo $product['price']; ?>"
                                                    data-stock="<?php echo $product['stock_quantity']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?> 
                                                (Stok: <?php echo number_format($product['stock_quantity'], 2); ?> kg)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Miktar (kg)</label>
                                    <input type="number" name="sale_quantity" class="form-control form-control-lg" 
                                           step="0.01" min="0.01" required onchange="calculateTotal()">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Birim Fiyat (₺/kg)</label>
                                    <input type="number" name="sale_price" id="salePrice" class="form-control form-control-lg" 
                                           step="0.01" min="0.01" required onchange="calculateTotal()">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Toplam Tutar</label>
                                    <div class="form-control form-control-lg bg-light" id="totalAmount">₺0.00</div>
                                </div>
                                
                                <button type="submit" name="make_sale" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-cash-register"></i> Satışı Tamamla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yeni Ürün Ekle</h3>
                <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form class="modal-form" method="post">
                <div class="form-group">
                    <label>Ürün Adı</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Açıklama</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Fiyat (₺)</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Kilo</label>
                    <input type="number" name="weight" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Resim URL</label>
                    <input type="url" name="image_url" required>
                </div>
                <button type="submit" name="add_product" class="save-btn">Ürün Ekle</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ürün Düzenle</h3>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form class="modal-form" method="post">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Ürün Adı</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Açıklama</label>
                    <textarea name="description" id="edit_description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Fiyat (₺)</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Resim URL</label>
                    <input type="url" name="image_url" id="edit_image_url" required>
                </div>
                <button type="submit" name="update_product" class="save-btn">Değişiklikleri Kaydet</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function openEditModal(product) {
            const modal = document.getElementById('editModal');
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_image_url').value = product.image_url;
            modal.classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Modal dışına tıklandığında kapatma
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        function toggleOrderDetails(orderId) {
            const detailsRow = document.getElementById('orderDetails_' + orderId);
            const currentDisplay = detailsRow.style.display;
            detailsRow.style.display = currentDisplay === 'none' ? 'table-row' : 'none';
        }

        function updatePrice(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const price = selectedOption.dataset.price;
            document.getElementById('salePrice').value = price;
            calculateTotal();
        }

        function calculateTotal() {
            const quantity = document.querySelector('input[name="sale_quantity"]').value;
            const price = document.getElementById('salePrice').value;
            const total = (quantity * price).toFixed(2);
            document.getElementById('totalAmount').textContent = `₺${total}`;
        }

        // Form gönderilmeden önce stok kontrolü
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            const selectElement = document.querySelector('select[name="sale_product_id"]');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const availableStock = parseFloat(selectedOption.dataset.stock);
            const requestedQuantity = parseFloat(document.querySelector('input[name="sale_quantity"]').value);
            
            if (requestedQuantity > availableStock) {
                e.preventDefault();
                alert(`Yetersiz stok! Mevcut stok: ${availableStock} kg`);
            }
        });
    </script>
</body>
</html> 