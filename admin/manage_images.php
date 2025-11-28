<?php
session_start();

// Bảo vệ trang, chỉ admin mới có quyền truy cập
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../public/login.php");
    exit;
}

$image_dir = '../img/';
$images = glob($image_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quản lý hình ảnh</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 90%; margin: 2rem auto; padding: 2rem; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; }
        h1 { margin-bottom: 1.5rem; text-align: center; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .image-card { border: 1px solid #ddd; border-radius: 4px; padding: 1rem; text-align: center; }
        .image-card img { max-width: 100%; height: auto; margin-bottom: 1rem; }
        .image-card .delete-btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .image-card .delete-btn:hover { background: #c0392b; }
        .back-link { display: inline-block; margin-bottom: 1rem; color: #333; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">&larr; Quay lại trang Admin</a>
        <h1>Quản lý hình ảnh</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <div class="image-grid">
            <?php if (empty($images)): ?>
                <p>Không có hình ảnh nào trong thư mục.</p>
            <?php else: ?>
                <?php foreach ($images as $image): ?>
                    <div class="image-card">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo basename($image); ?>">
                        <p><?php echo basename($image); ?></p>
                        <a href="delete_image.php?filename=<?php echo urlencode(basename($image)); ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa ảnh này không?');">Xóa</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
