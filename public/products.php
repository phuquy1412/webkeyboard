<?php
/**
 * Trang danh sách sản phẩm
 */
session_start();
include_once 'config/database.php';
include_once 'models/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// Lọc theo danh mục nếu có
$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : null;

if ($category) {
    $products = $product->getByCategory($category);
    $pageTitle = "Danh mục: " . $category;
} else {
    $products = $product->getAll();
    $pageTitle = "Tất cả sản phẩm";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Keyboard Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .nav-links a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .filters { background: white; padding: 1rem; margin-bottom: 2rem; border-radius: 8px; }
        .filter-group { display: flex; gap: 1rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.7rem 1.5rem; border: 2px solid #3498db; background: white; color: #3498db; border-radius: 4px; cursor: pointer; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: #3498db; color: white; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .product-card { background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .product-image { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; }
        .product-name { font-weight: bold; margin: 0.5rem 0; font-size: 1.1rem; }
        .product-category { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .product-price { color: #e74c3c; font-weight: bold; font-size: 1.2rem; margin: 0.5rem 0; }
        .product-old-price { color: #999; text-decoration: line-through; margin-right: 0.5rem; }
        .product-switch { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1rem; }
        .btn { background: #3498db; color: white; padding: 0.7rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: all 0.3s; }
        .btn:hover { background: #2980b9; }
        .no-products { text-align: center; padding: 2rem; background: white; border-radius: 8px; }
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
                        <a href="admin/index.php">Admin</a>
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

    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        
        <!-- Bộ lọc danh mục -->
        <div class="filters">
            <h3>Lọc theo danh mục:</h3>
            <div class="filter-group">
                <a href="products.php" class="filter-btn <?php echo !$category ? 'active' : ''; ?>">Tất cả</a>
                <a href="products.php?category=Gaming" class="filter-btn <?php echo $category === 'Gaming' ? 'active' : ''; ?>">Gaming</a>
                <a href="products.php?category=Văn phòng" class="filter-btn <?php echo $category === 'Văn phòng' ? 'active' : ''; ?>">Văn phòng</a>
                <a href="products.php?category=Cơ" class="filter-btn <?php echo $category === 'Cơ' ? 'active' : ''; ?>">Cơ</a>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="products">
            <?php
            $productCount = 0;
            while ($row = $products->fetch(PDO::FETCH_ASSOC)):
                $productCount++;
            ?>
            <div class="product-card">
                <?php if ($row['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                <?php else: ?>
                    <div class="product-image" style="background: #ddd; display: flex; align-items: center; justify-content: center; color: #999;">
                        Không có ảnh
                    </div>
                <?php endif; ?>
                
                <div class="product-name"><?php echo htmlspecialchars($row['name']); ?></div>
                <div class="product-category">Loại: <?php echo htmlspecialchars($row['category']); ?></div>
                <div class="product-price">
                    <?php if ($row['old_price']): ?>
                        <span class="product-old-price"><?php echo number_format($row['old_price'], 0, ',', '.'); ?>₫</span>
                    <?php endif; ?>
                    <?php echo number_format($row['price'], 0, ',', '.'); ?>₫
                </div>
                <div class="product-switch">Switch: <?php echo htmlspecialchars($row['switch_type']); ?></div>
                
                <button class="btn" onclick="addToCart(<?php echo $row['id']; ?>)">Thêm vào giỏ</button>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if ($productCount === 0): ?>
        <div class="no-products">
            <h2>Không tìm thấy sản phẩm</h2>
            <p><a href="products.php">Xem tất cả sản phẩm</a></p>
        </div>
        <?php endif; ?>
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
