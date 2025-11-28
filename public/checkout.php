<?php
/**
 * Checkout Page - Giỏ hàng -> Thanh toán
 * Kết nối với MoMo payment
 */
session_start();
include_once '../config/database.php';
include_once '../models/Product.php';
include_once '../models/User.php'; // Include User model

// Kiểm tra user đã login chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$user = new User($db); // Create User object

// Lấy thông tin user hiện tại
$userData = $user->findById($_SESSION['user_id']);
if (!$userData) {
    // Xử lý trường hợp không tìm thấy user, có thể redirect hoặc báo lỗi
    die("Lỗi: Không tìm thấy thông tin người dùng.");
}


// Lấy dữ liệu giỏ hàng và lưu vào SESSION để không bị mất khi redirect
if (isset($_POST['cart']) && isset($_POST['totalAmount'])) {
    $_SESSION['checkout_cart'] = json_decode($_POST['cart'], true);
    $_SESSION['checkout_total'] = floatval($_POST['totalAmount']);
}

// Lấy dữ liệu từ session
$cart = isset($_SESSION['checkout_cart']) ? $_SESSION['checkout_cart'] : [];
$totalAmount = isset($_SESSION['checkout_total']) ? $_SESSION['checkout_total'] : 0;


// Debug logging
error_log("\n========================================");
error_log("=== CHECKOUT.PHP RECEIVED POST DATA ===");
error_log("========================================");
error_log("POST vars: " . print_r($_POST, true));
error_log("Cart JSON string: " . (isset($_POST['cart']) ? $_POST['cart'] : 'NOT SET'));
error_log("Cart decoded: " . json_encode($cart));
error_log("TotalAmount string: " . (isset($_POST['totalAmount']) ? $_POST['totalAmount'] : 'NOT SET'));
error_log("TotalAmount as int: " . $totalAmount);
error_log("Cart is empty? " . (empty($cart) ? 'YES' : 'NO'));
error_log("TotalAmount is 0? " . ($totalAmount == 0 ? 'YES' : 'NO'));
error_log("========================================\n");

// Nếu giỏ hàng trống, redirect về cart
if (empty($cart) || $totalAmount == 0) {
    error_log("ERROR: Redirecting to cart.php - Cart empty or amount 0");
    $_SESSION['error'] = 'Giỏ hàng trống. Vui lòng thêm sản phẩm.';
    header("Location: cart.php");
    exit;
}

// Lấy thông tin chi tiết sản phẩm trong giỏ
$cartItems = [];
foreach ($cart as $productId => $quantity) {
    $productData = $product->getById($productId);
    if ($productData) {
        $cartItems[] = [
            'product_id' => $productId,
            'name' => $productData['name'],
            'price' => $productData['price'],
            'quantity' => $quantity,
            'total' => $productData['price'] * $quantity
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Keyboard Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .checkout-wrapper { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .order-summary { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .payment-methods { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .order-item { border-bottom: 1px solid #eee; padding: 1rem 0; }
        .order-item:last-child { border-bottom: none; }
        .order-item-name { font-weight: bold; }
        .order-item-price { color: #e74c3c; }
        .order-total { font-size: 1.5rem; font-weight: bold; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #333; }
        .payment-method { margin-bottom: 1rem; padding: 1rem; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; transition: all 0.3s; }
        .payment-method:hover { border-color: #3498db; background: #f9f9f9; }
        .payment-method input[type="radio"] { margin-right: 0.5rem; }
        .payment-method.selected { border-color: #3498db; background: #e3f2fd; }
        .btn { padding: 0.7rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; transition: all 0.3s; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-block { width: 100%; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) {
            .checkout-wrapper { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Keyboard Shop</div>
            <div class="nav-links">
                <a href="index.php">Trang chủ</a>
                <a href="cart.php">Quay lại giỏ hàng</a>
                <a href="#"><?php echo htmlspecialchars($_SESSION['full_name']); ?></a>
                <a href="logout.php">Đăng xuất</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Thanh toán đơn hàng</h1>

        <div class="checkout-wrapper">
            <!-- Form đơn hàng -->
            <div>
                <div class="order-summary">
                    <h2>Thông tin giao hàng</h2>
                    <form id="checkoutForm" method="POST" action="process_payment.php">
                        <div class="form-group">
                            <label for="fullname">Họ và tên *</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Số điện thoại *</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Địa chỉ giao hàng *</label>
                            <textarea id="address" name="address" required><?php echo htmlspecialchars($userData['address']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="note">Ghi chú đơn hàng (tuỳ chọn)</label>
                            <textarea id="note" name="note"></textarea>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="cart" value='<?php echo json_encode($cart); ?>'>
                        <input type="hidden" name="totalAmount" value="<?php echo $totalAmount; ?>">
                        <input type="hidden" id="paymentMethod" name="paymentMethod" value="momo_wallet">

                        <h2 style="margin-top: 2rem; margin-bottom: 1rem;">Chọn phương thức thanh toán</h2>
                        
                        <div class="payment-methods">
                            <!-- MoMo E-Wallet -->
                            <label class="payment-method selected" onclick="selectPaymentMethod('momo_wallet')">
                                <input type="radio" name="payment" value="momo_wallet" checked>
                                <strong>💳 MoMo E-Wallet</strong>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Thanh toán bằng ví MoMo</p>
                            </label>

                            <!-- Cash on Delivery -->
                            <label class="payment-method" onclick="selectPaymentMethod('cod')">
                                <input type="radio" name="payment" value="cod">
                                <strong>💰 Thanh toán khi nhận hàng</strong>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Trả tiền khi nhận hàng</p>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 2rem;">
                            Tiếp tục thanh toán
                        </button>
                        <a href="cart.php" class="btn btn-secondary btn-block" style="margin-top: 0.5rem; text-align: center; text-decoration: none;">
                            Quay lại
                        </a>
                    </form>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div>
                <div class="order-summary">
                    <h2>Chi tiết đơn hàng</h2>
                    
                    <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="order-item-name">
                            <?php echo htmlspecialchars($item['name']); ?> 
                            <span style="color: #666;">x<?php echo $item['quantity']; ?></span>
                        </div>
                        <div class="order-item-price">
                            <?php echo number_format($item['total'], 0, ',', '.'); ?>₫
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="order-total">
                        Tổng cộng: <?php echo number_format($totalAmount, 0, ',', '.'); ?>₫
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectPaymentMethod(method) {
            document.getElementById('paymentMethod').value = method;
            
            // Update UI
            const methods = document.querySelectorAll('.payment-method');
            methods.forEach(m => m.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Update radio button
            document.querySelector('input[value="' + method + '"]').checked = true;
        }

        // Validate form trước khi submit
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const fullname = document.getElementById('fullname').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();

            if (!fullname || !phone || !email || !address) {
                alert('Vui lòng điền đầy đủ các trường bắt buộc');
                e.preventDefault();
                return false;
            }

            // Validate phone (10-11 số)
            const phoneDigits = phone.replace(/[^\d]/g, '');
            if (phoneDigits.length < 10 || phoneDigits.length > 11) {
                alert('Số điện thoại phải có 10-11 chữ số');
                e.preventDefault();
                return false;
            }

            // Validate email
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Email không hợp lệ');
                e.preventDefault();
                return false;
            }

            // Log để debug
            console.log('Form data:');
            console.log('Fullname:', fullname);
            console.log('Phone:', phone);
            console.log('Email:', email);
            console.log('Address:', address);
            console.log('Cart:', document.querySelector('input[name="cart"]').value);
            console.log('Total:', document.querySelector('input[name="totalAmount"]').value);
            console.log('Payment Method:', document.getElementById('paymentMethod').value);
            
            // Form hợp lệ, tiếp tục submit
            return true;
        });
    </script>
</body>
</html>

