<?php
session_start();
include_once '../config/database.php';
include_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$products = $product->getAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keyboard Shop - Trang chủ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; padding: 2rem; }
        .product-card { background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .product-image { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; }
        .product-name { font-weight: bold; margin: 0.5rem 0; }
        .product-price { color: #e74c3c; font-weight: bold; }
        .product-old-price { color: #999; text-decoration: line-through; margin-right: 0.5rem; }
        .btn { background: #3498db; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Keyboard Shop</div>
            <div class="nav-links">
                <a href="index.php">Trang chủ</a>
                <a href="products.php">Sản phẩm</a>
                <a href="assignments.php">Bài tập</a>
                <a href="cart.php">Giỏ hàng</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <a href="../admin/index.php">Admin</a>
                    <?php endif; ?>
                    <a href="#">Xin chào, <?php echo htmlspecialchars($_SESSION['full_name']); ?></a>
                    <a href="logout.php">Đăng xuất</a>
                <?php else: ?>
                    <a href="register.php">Đăng ký</a>
                    <a href="login.php">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="products">
        <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
        <div class="product-card">
            <?php if ($row['image_url']): ?>
                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
            <?php else: ?>
                <div class="product-image" style="background: #ddd; display: flex; align-items: center; justify-content: center;">
                    No Image
                </div>
            <?php endif; ?>
            
            <div class="product-name"><?php echo htmlspecialchars($row['name']); ?></div>
            <div class="product-price">
                <?php if ($row['old_price']): ?>
                    <span class="product-old-price"><?php echo number_format($row['old_price']); ?>₫</span>
                <?php endif; ?>
                <?php echo number_format($row['price']); ?>₫
            </div>
            <div class="product-category">Loại: <?php echo htmlspecialchars($row['category']); ?></div>
            <div class="product-switch">Switch: <?php echo htmlspecialchars($row['switch_type']); ?></div>
            
            <button class="btn" onclick="addToCart(<?php echo $row['id']; ?>)">Thêm vào giỏ</button>
        </div>
        <?php endwhile; ?>
    </div>

    <script>
        function addToCart(productId) {
            let cart = JSON.parse(localStorage.getItem('cart')) || {};
            if (cart[productId]) {
                cart[productId] += 1;
            } else {
                cart[productId] = 1;
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            alert('Đã thêm vào giỏ hàng!');
        }
    </script>
</body>
</html>