<?php
session_start();



/**
 * Process Payment - Xử lý giao dịch thanh toán
 * Điều hướng tới MoMo hoặc lưu order (COD)
 */
include_once 'config/database.php';
include_once 'models/Product.php';

// Log request
error_log("=== CHECKOUT REQUEST ===");
error_log("POST data: " . json_encode($_POST));

// Kiểm tra user đã login
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    header("Location: login.php");
    exit;
}

// Lấy dữ liệu từ checkout form
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
$paymentMethod = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : 'momo_wallet';
$cart = isset($_SESSION['checkout_cart']) ? $_SESSION['checkout_cart'] : [];
$totalAmount = isset($_SESSION['checkout_total']) ? floatval($_SESSION['checkout_total']) : 0;

error_log("Fullname: " . $fullname);
error_log("Payment Method: " . $paymentMethod);
error_log("Cart items: " . count($cart));
error_log("Total Amount: " . $totalAmount);

// Validate dữ liệu
if (empty($fullname) || empty($phone) || empty($email) || empty($address)) {
    error_log("Validation failed - missing required fields");
    $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc";
    header("Location: checkout.php");
    exit;
}

if (empty($cart) || $totalAmount == 0) {
    error_log("Validation failed - empty cart or zero amount");
    $_SESSION['error'] = "Giỏ hàng không hợp lệ";
    header("Location: cart.php");
    exit;
}

// Kết nối database
try {
    $database = new Database();
    $db = $database->getConnection();

    // Bắt đầu transaction
    $db->beginTransaction();

    // Tạo đơn hàng
    $query = "INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, total_amount, status) 
              VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$_SESSION['user_id'], $fullname, $phone, $address, $totalAmount]);
    
    if (!$result) {
        throw new Exception("Tạo đơn hàng thất bại");
    }
    
    $orderId = $db->lastInsertId();
    error_log("Order created: ID=" . $orderId);
    
    // Thêm order items
    $product = new Product($db);
    foreach ($cart as $productId => $quantity) {
        $productData = $product->getById($productId);
        if ($productData) {
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$orderId, $productId, $quantity, $productData['price']]);
            
            if (!$result) {
                throw new Exception("Thêm order item thất bại");
            }
        }
    }

    // Commit transaction
    $db->commit();
    error_log("Transaction committed successfully");
    
    // Xử lý theo phương thức thanh toán
    if ($paymentMethod == 'momo_wallet') {
        error_log("Redirecting to MoMo payment");
        // Redirect tới MoMo payment
        header("Location: momo_payment.php?orderId=" . $orderId . "&amount=" . $totalAmount);
        exit;
    } else {
        // COD - ghi nhận đơn hàng, redirect về trang thành công
        error_log("COD order - redirecting to success page");
        $_SESSION['success'] = "Đơn hàng đã được tạo. Mã đơn: #" . $orderId;
        
        header("Location: order_success.php?orderId=" . $orderId);
    }
    
} catch (Exception $e) {
    error_log("ERROR in process_payment.php: " . $e->getMessage());
    $db->rollBack();
    $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    header("Location: checkout.php");
    exit;
}
?>
