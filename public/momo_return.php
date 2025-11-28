<?php
/**
 * MoMo Return Page - Trang kết quả thanh toán
 * Hiển thị kết quả khi user quay về từ MoMo
 * Giải pháp D: Kết hợp resultCode từ URL + IPN callback
 */
session_start();
include_once '../config/database.php';

// Lấy dữ liệu từ MoMo redirect
$resultCode = isset($_GET['resultCode']) ? intval($_GET['resultCode']) : -1;
echo "resultCode la $resultCode";
$extraData = isset($_GET['extraData']) ? $_GET['extraData'] : null;

// Parse extraData để lấy orderId
if (!$extraData) {
    die("Không tìm thấy dữ liệu thanh toán");
}

$decoded = base64_decode($extraData, true);
if ($decoded === false) {
    die("Dữ liệu thanh toán không hợp lệ");
}

$extraDataArray = json_decode($decoded, true);
if (!isset($extraDataArray['orderId'])) {
    die("Không tìm thấy mã đơn hàng");
}

$orderId = intval($extraDataArray['orderId']);

if (!$orderId) {
    die("Không tìm thấy đơn hàng");
}

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// **GIẢI PHÁP D - BẬT 1: Update DB ngay dựa vào resultCode từ MoMo**
// resultCode = 0 => thanh toán thành công, update ngay không chờ IPN
if ($resultCode == 0) {
    try {
        $query = "UPDATE orders SET status = 'confirmed' WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$orderId]);
        error_log("Order $orderId status updated to 'confirmed' (via resultCode)");
    } catch (Exception $e) {
        error_log("Error updating order $orderId: " . $e->getMessage());
    }
}

// Lấy thông tin order từ database
$query = "SELECT o.*, u.email FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Đơn hàng không tồn tạiii");
}

// **GIẢI PHÁP D - BƯỚC 2: Dựa vào resultCode để hiển thị (không query DB)**
// resultCode từ MoMo là source of truth
if ($resultCode == 0) {
    $paymentStatus = 'success';
    $statusText = 'Thanh toán thành công';
    $statusColor = '#27ae60';
    $statusIcon = '✅';
    $shouldPoll = false; // Không cần polling vì đã thành công
} elseif ($resultCode == -1) {
    // resultCode không được gửi (fallback: sử dụng DB status)
    $paymentStatus = $order['status'] == 'confirmed' ? 'success' : 'pending';
    $statusText = $order['status'] == 'confirmed' ? 'Thanh toán thành công' : 'Đang kiểm tra kết quả...';
    $statusColor = $order['status'] == 'confirmed' ? '#27ae60' : '#f39c12';
    $statusIcon = $order['status'] == 'confirmed' ? '✅' : '⏳';
    $shouldPoll = ($order['status'] != 'confirmed'); // Poll nếu vẫn pending
} else {
    // resultCode khác 0 = thanh toán thất bại
    $paymentStatus = 'failed';
    $statusText = 'Thanh toán không thành công';
    $statusColor = '#e74c3c';
    $statusIcon = '❌';
    $shouldPoll = false; // Không poll nếu đã thất bại
}

// Get order items
$query = "SELECT oi.*, p.name FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug logging
error_log("=== MoMo Return ===");
error_log("OrderId: $orderId");
error_log("ResultCode: $resultCode");
error_log("Order Status (DB): " . $order['status']);
error_log("Payment Status (Display): $paymentStatus");
error_log("Should Poll: " . ($shouldPoll ? 'true' : 'false'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán - Keyboard Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; }
        .result-box { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 2rem; text-align: center; }
        .status-icon { font-size: 3rem; margin-bottom: 1rem; }
        .status-text { font-size: 1.5rem; font-weight: bold; margin-bottom: 2rem; }
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
        .success { color: #27ae60; }
        .pending { color: #f39c12; }
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
        <div class="result-box">
            <div class="status-icon">
                <?php echo $statusIcon; ?>
            </div>

            <div class="status-text <?php echo $paymentStatus; ?>">
                <?php echo $statusText; ?>
            </div>

            <?php if ($shouldPoll): ?>
            <div class="note" style="background: #e3f2fd; border-left-color: #2196F3;">
                ⏳ <strong>Đang đồng bộ hóa thông tin...</strong><br>
                Vui lòng chờ trong giây lát, chúng tôi sẽ cập nhật kết quả cho bạn.
            </div>
            <?php endif; ?>

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
                    <span class="order-label">Trạng thái:</span>
                    <span class="order-value <?php echo $paymentStatus; ?>" id="status-display"><?php echo ucfirst($order['status']); ?></span>
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

            <?php if ($paymentStatus == 'success'): ?>
            <div class="note">
                ✅ <strong>Cảm ơn bạn đã mua hàng!</strong><br>
                Chúng tôi sẽ sớm xác nhận và giao hàng cho bạn. Kiểm tra email để nhận thông báo cập nhật.
            </div>
            <script>
                // Xóa cart vì thanh toán thành công
                localStorage.removeItem("cart");
            </script>
            <?php elseif ($paymentStatus == 'failed'): ?>
            <div class="note" style="background: #ffebee; border-left-color: #f44336;">
                ❌ <strong>Thanh toán không thành công.</strong><br>
                Vui lòng thử lại hoặc liên hệ với chúng tôi để được hỗ trợ.
            </div>
            <?php else: ?>
            <div class="note" style="background: #fff3cd; border-left-color: #f39c12;">
                ⏳ <strong>Đơn hàng của bạn đang chờ xử lý.</strong><br>
                Trang này sẽ tự cập nhật khi kết quả thanh toán được xác nhận.
            </div>
            <?php endif; ?>

            <div style="margin-top: 2rem;">
                <a href="index.php" class="btn btn-primary">← Tiếp tục mua sắm</a>
            </div>
        </div>
    </div>

    <!-- **GIẢI PHÁP D - BƯỚC 3: JavaScript Polling để check DB sync** -->
    <script>
        // Chỉ poll nếu shouldPoll = true (khi status vẫn pending)
        const shouldPoll = <?php echo json_encode($shouldPoll); ?>;
        const orderId = <?php echo json_encode($orderId); ?>;
        const maxRetries = 10;
        let retries = 0;
        let pollingInterval = null;

        function checkOrderStatus() {
            fetch(`check_order_status.php?orderId=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Poll attempt ' + (retries + 1) + ':', data);

                    // Nếu status đã update thành 'confirmed'
                    if (data.status === 'confirmed') {
                        // Update UI
                        document.querySelector('.status-text').textContent = 'Thanh toán thành công';
                        document.querySelector('.status-icon').textContent = '✅';
                        document.querySelector('.status-text').classList.remove('pending', 'failed');
                        document.querySelector('.status-text').classList.add('success');
                        document.getElementById('status-display').textContent = 'confirmed';
                        
                        // Xóa notice "Đang đồng bộ"
                        const syncNote = document.querySelector('[style*="e3f2fd"]');
                        if (syncNote) syncNote.style.display = 'none';

                        // Xóa cart
                        localStorage.removeItem("cart");

                        // Dừng polling
                        clearInterval(pollingInterval);
                        console.log('Order confirmed! Polling stopped.');
                    }

                    retries++;
                })
                .catch(error => {
                    console.error('Error checking order status:', error);
                    retries++;
                });
        }

        // Chỉ bắt đầu polling nếu cần
        if (shouldPoll) {
            console.log('Starting polling for order ' + orderId);
            // Poll mỗi 1 giây, tối đa 10 lần (10 giây)
            pollingInterval = setInterval(() => {
                if (retries >= maxRetries) {
                    clearInterval(pollingInterval);
                    console.log('Polling stopped: max retries reached');
                    return;
                }
                checkOrderStatus();
            }, 1000);

            // Chạy ngay lần đầu tiên
            checkOrderStatus();
        } else {
            console.log('No polling needed - payment already confirmed or failed');
        }
    </script>
</body>
</html>
