<?php
/**
 * Trang quản lý sản phẩm (Admin)
 */
session_start();

// Bảo vệ trang admin - chỉ cho phép admin truy cập
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit;
}

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
    <title>Admin - Keyboard Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header { background: #333; color: white; padding: 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; }
        .nav-links a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .admin-actions { margin-bottom: 2rem; }
        .btn { background: #3498db; color: white; padding: 0.7rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th { background: #333; color: white; padding: 1rem; text-align: left; font-weight: bold; }
        td { padding: 1rem; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        .actions { display: flex; gap: 0.5rem; }
        .actions a { padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-size: 0.9rem; }
        .edit-link { background: #3498db; color: white; }
        .edit-link:hover { background: #2980b9; }
        .delete-link { background: #e74c3c; color: white; }
        .delete-link:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Keyboard Shop - Admin</div>
            <div class="nav-links">
                <a href="../index.php">Trang chủ</a>
                <a href="#">Xin chào, <?php echo htmlspecialchars($_SESSION['full_name']); ?></a>
                <a href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Quản lý sản phẩm</h1>
        
        <div class="admin-actions">
            <a href="products.php?action=create" class="btn">+ Thêm sản phẩm mới</a>
            <a href="manage_images.php" class="btn">Quản lý hình ảnh</a>
        </div>
        
        <table border="1" style="width: 100%; margin-top: 1rem;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên sản phẩm</th>
                    <th>Giá</th>
                    <th>Danh mục</th>
                    <th>Switch</th>
                    <th>Tồn kho</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo number_format($row['price'], 0, ',', '.'); ?>₫</td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['switch_type']); ?></td>
                    <td><?php echo $row['stock']; ?></td>
                    <td>
                        <div class="actions">
                            <a href="products.php?action=edit&id=<?php echo $row['id']; ?>" class="edit-link">Sửa</a>
                            <a href="products.php?action=delete&id=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">Xóa</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>