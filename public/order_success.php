<?php
/**
 * Order Success Page - Trang thành công cho COD
 */
session_start();
include_once '../config/database.php';

$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

if (!$orderId) {
    die("Không tìm thấy đơn hàng");
}

// Lấy thông tin order
$database = new Database();
$db = $database->getConnection();

$query = "SELECT o.*, u.email FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Đơn hàng không tồn tại");
}

// Get order items
$query = "SELECT oi.*, p.name FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng đã tạo - Keyboard Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; }
        .success-box { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 2rem; text-align: center; }
        .success-icon { font-size: 3rem; margin-bottom: 1rem; }
        .success-text { font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; color: #27ae60; }
        .order-details { text-align: left; background: #f9f9f9; padding: 1.5rem; border-radius: 4px; margin: 2rem 0; }
        .order-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .order-row:last-child { border-bottom: none; }
        .order-label { font-weight: bold; }
        .order-value { color: #666; }
        .order-items { margin: 1rem 0; }
        .item-row { padding: 0.5rem 0; }
        .total-row { background: white; padding: 1rem; border-top: 2px solid #333; margin-top: 1rem; font-size: 1.2rem; font-weight: bold; }
        .btn { padding: 0.7rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin: 0.5rem; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .note { background: #e8f5e9; border-left: 4px solid #27ae60; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Keyboard Shop</div>
            <div class="nav-links">
                <a href="index.php">Trang chủ</a>
                <a href="logout.php">Đăng xuất</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="success-box">
            <div class="success-icon">✅</div>
            <div class="success-text">Đơn hàng đã được tạo thành công!</div>

            <div class="order-details">
                <h2 style="margin-bottom: 1rem;">Thông tin đơn hàng</h2>

                <div class="order-row">
                    <span class="order-label">Mã đơn hàng:</span>
                    <span class="order-value">#<?php echo $orderId; ?></span>
                </div>

                <div class="order-row">
                    <span class="order-label">Tên khách hàng:</span>
                    <span class="order-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>

                <div class="order-row">
                    <span class="order-label">Số điện thoại:</span>
                    <span class="order-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                </div>

                <div class="order-row">
                    <span class="order-label">Địa chỉ giao hàng:</span>
                    <span class="order-value"><?php echo htmlspecialchars($order['customer_address']); ?></span>
                </div>

                <div class="order-row">
                    <span class="order-label">Phương thức thanh toán:</span>
                    <span class="order-value">Thanh toán khi nhận hàng (COD)</span>
                </div>

                <div class="order-row">
                    <span class="order-label">Trạng thái:</span>
                    <span class="order-value" style="color: #f39c12;"><strong>Chờ xác nhận</strong></span>
                </div>

                <div class="order-row">
                    <span class="order-label">Ngày tạo:</span>
                    <span class="order-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
            </div>

            <h3>Chi tiết sản phẩm</h3>
            <div class="order-items">
                <?php foreach ($orderItems as $item): ?>
                <div class="item-row">
                    <strong><?php echo htmlspecialchars($item['name']); ?></strong> 
                    x<?php echo $item['quantity']; ?> 
                    = <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>₫
                </div>
                <?php endforeach; ?>
                <div class="total-row">
                    Tổng: <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫
                </div>
            </div>

            <div class="note">
                ✅ <strong>Cảm ơn bạn đã đặt hàng!</strong><br>
                Chúng tôi sẽ liên hệ với bạn để xác nhận đơn hàng và thỏa thuận thời gian giao hàng. 
                Vui lòng kiểm tra email để nhận thông báo cập nhật.
            </div>

            <div style="margin-top: 2rem;">
                <a href="index.php" class="btn btn-primary">← Tiếp tục mua sắm</a>
            </div>
        </div>
    </div>
</body>
</html>
