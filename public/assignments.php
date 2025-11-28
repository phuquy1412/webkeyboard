<?php
session_start();

// Yêu cầu đăng nhập để xem trang này
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$labs_dir = 'labs/';
// Thư mục 'labs' cần được tạo trong thư mục 'public'
if (!is_dir($labs_dir)) {
    // Cố gắng tạo thư mục nếu chưa tồn tại
    if (!mkdir($labs_dir, 0755, true)) {
        die("Thư mục 'labs' không tồn tại và không thể tự động tạo. Vui lòng tạo thư mục 'public/labs'.");
    }
}

$files = scandir($labs_dir);
// Lọc bỏ '.' và '..'
$files = array_diff($files, ['.', '..']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài tập</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
        .navbar { background: #333; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; padding: 0.5rem 1rem; }
        .navbar a:hover { background: #555; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; }
        h1 { text-align: center; margin-bottom: 1.5rem; }
        ul { list-style: none; padding: 0; }
        li { background: #eee; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; }
        li a { text-decoration: none; color: #333; font-weight: bold; }
        li a:hover { color: #007bff; }
    </style>
</head>
<body>
    <div class="navbar">
        <div>
            <a href="index.php">Trang chủ</a>
            <a href="products.php">Sản phẩm</a>
            <a href="assignments.php">Bài tập</a>
        </div>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="admin">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Đăng xuất</a>
            <?php else: ?>
                <a href="login.php">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h1>Danh sách bài tập</h1>
        <?php if (empty($files)): ?>
            <p>Hiện tại chưa có bài tập nào.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li>
                        <a href="<?php echo $labs_dir . htmlspecialchars($file); ?>" target="_blank">
                            <?php echo htmlspecialchars($file); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
