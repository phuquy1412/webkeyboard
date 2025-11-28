<?php
session_start();
include_once '../config/database.php';
include_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Keyboard Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .cart-item { background: white; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .item-info { flex: 1; }
        .item-controls { display: flex; align-items: center; gap: 1rem; }
        .quantity-control { display: flex; gap: 0.5rem; align-items: center; }
        .quantity-control button { padding: 0.3rem 0.6rem; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .quantity-control button:hover { background: #2980b9; }
        .remove-btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .remove-btn:hover { background: #c0392b; }
        .checkout-btn { background: #27ae60; color: white; padding: 1rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .checkout-btn:hover { background: #229954; }
        .cart-empty { text-align: center; padding: 2rem; background: white; border-radius: 8px; }
        .cart-summary { background: white; padding: 2rem; border-radius: 8px; text-align: right; margin-top: 2rem; }
        .summary-line { display: flex; justify-content: flex-end; margin-bottom: 1rem; }
        .summary-label { margin-right: 1rem; }
        .total-price { font-size: 1.5rem; font-weight: bold; color: #e74c3c; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Keyboard Shop</div>
            <div class="nav-links">
                <a href="index.php">Trang chủ</a>
                <a href="products.php">Sản phẩm</a>
                <a href="cart.php">Giỏ hàng</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="#">Xin chào, <?php echo htmlspecialchars($_SESSION['full_name']); ?></a>
                    <a href="logout.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="register.php">Đăng ký</a>
                    <a href="login.php">Đăng nhậppp</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Giỏ hàng</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            ⚠️ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>
        
        <div id="cart-items"></div>
        <div id="cart-summary" style="display: none;">
            <div class="cart-summary">
                <div class="summary-line">
                    <span class="summary-label">Tổng cộng:</span>
                    <span class="total-price" id="total-price">0₫</span>
                </div>
                <!-- Form checkout ẩn -->
                <form id="checkoutForm" method="POST" action="checkout.php" style="display: none;">
                    <input type="hidden" id="cartData" name="cart" value="">
                    <input type="hidden" id="totalData" name="totalAmount" value="">
                </form>
                <button class="checkout-btn" onclick="checkout()">Thanh toán</button>
            </div>
        </div>
    </div>

    <script>
        // Quản lý giỏ hàng với localStorage
        function getCart() {
            return JSON.parse(localStorage.getItem('cart')) || {};
        }

        function saveCart(cart) {
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        function addToCart(productId, quantity = 1) {
            let cart = getCart();
            if (cart[productId]) {
                cart[productId] += quantity;
            } else {
                cart[productId] = quantity;
            }
            saveCart(cart);
            loadCart();
        }

        function removeFromCart(productId) {
            let cart = getCart();
            delete cart[productId];
            saveCart(cart);
            loadCart();
        }

        function updateQuantity(productId, quantity) {
            if (quantity <= 0) {
                removeFromCart(productId);
            } else {
                let cart = getCart();
                cart[productId] = quantity;
                saveCart(cart);
                loadCart();
            }
        }

        function loadCart() {
            let cart = getCart();
            let cartItems = document.getElementById('cart-items');
            let cartSummary = document.getElementById('cart-summary');
            cartItems.innerHTML = '';

            if (Object.keys(cart).length === 0) {
                cartItems.innerHTML = '<div class="cart-empty"><p>Giỏ hàng trống</p><a href="index.php">Tiếp tục mua sắm</a></div>';
                cartSummary.style.display = 'none';
                return;
            }

            let totalPrice = 0;
            let promises = [];

            // Lấy thông tin sản phẩm từ API
            for (let productId in cart) {
                let promise = fetch(`get_product.php?id=${productId}`)
                    .then(response => response.json())
                    .then(product => {
                        const quantity = cart[productId];
                        const itemTotal = product.price * quantity;
                        totalPrice += itemTotal;

                        const item = document.createElement('div');
                        item.className = 'cart-item';
                        item.innerHTML = `
                            <div class="item-info">
                                <h3>${product.name}</h3>
                                <p>Giá: ${new Intl.NumberFormat('vi-VN').format(product.price)}₫</p>
                                <p>Loại: ${product.category}</p>
                            </div>
                            <div class="item-controls">
                                <div class="quantity-control">
                                    <button onclick="updateQuantity(${productId}, ${quantity - 1})">-</button>
                                    <span style="min-width: 30px; text-align: center;">${quantity}</span>
                                    <button onclick="updateQuantity(${productId}, ${quantity + 1})">+</button>
                                </div>
                                <div>Thành tiền: ${new Intl.NumberFormat('vi-VN').format(itemTotal)}₫</div>
                                <button class="remove-btn" onclick="removeFromCart(${productId})">Xóa</button>
                            </div>
                        `;
                        cartItems.appendChild(item);
                    });
                promises.push(promise);
            }

            // Cập nhật tổng cộng khi tất cả sản phẩm được tải
            Promise.all(promises).then(() => {
                document.getElementById('total-price').textContent = new Intl.NumberFormat('vi-VN').format(totalPrice) + '₫';
                cartSummary.style.display = 'block';
            });
        }

        function checkout() {
            let cart = getCart();
            console.log('=== CHECKOUT DEBUG ===');
            console.log('Cart object:', cart);
            console.log('Cart keys:', Object.keys(cart));
            
            if (Object.keys(cart).length === 0) {
                alert('Giỏ hàng trống!');
                return;
            }
            
            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Vui lòng đăng nhập để thanh toán');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            // Tính tổng tiền bằng cách gọi API get_product.php cho từng sản phẩm
            let totalAmount = 0;
            let productIds = Object.keys(cart);
            let completed = 0;
            
            console.log('Starting to fetch product prices for', productIds.length, 'products');
            
            // Tạo promises để lấy giá từ server
            let pricePromises = productIds.map(productId => {
                return fetch(`get_product.php?id=${productId}`)
                    .then(response => response.json())
                    .then(product => {
                        let quantity = cart[productId];
                        let itemTotal = parseFloat(product.price) * quantity;
                        totalAmount += itemTotal;
                        completed++;
                        console.log(`Product ${productId}: ${product.name} - Price: ${product.price} x ${quantity} = ${itemTotal}`);
                        console.log(`Progress: ${completed}/${productIds.length}`);
                        return itemTotal;
                    })
                    .catch(error => {
                        console.error(`Error fetching product ${productId}:`, error);
                        return 0;
                    });
            });
            
            // Khi tất cả giá được tải, submit form
            Promise.all(pricePromises).then(() => {
                console.log('All product prices loaded');
                console.log('Total amount calculated:', totalAmount);
                
                if (totalAmount <= 0) {
                    alert('Không thể tính tổng tiền. Vui lòng thử lại.');
                    console.error('Total amount is 0 or negative');
                    return;
                }
                
                // Set hidden form fields
                document.getElementById('cartData').value = JSON.stringify(cart);
                document.getElementById('totalData').value = totalAmount;
                
                console.log('Form fields set:');
                console.log('Cart JSON:', document.getElementById('cartData').value);
                console.log('Total:', document.getElementById('totalData').value);
                console.log('Submitting form now...');
                
                // Submit form
                document.getElementById('checkoutForm').submit();
            }).catch(error => {
                console.error('Error during checkout:', error);
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            });
        }

        loadCart();
    </script>
</body>
</html>