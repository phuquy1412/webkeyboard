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
    if (!mkdir($labs_dir, 0755, true)) {
        die("Thư mục 'labs' không tồn tại và không thể tự động tạo. Vui lòng tạo thư mục 'public/labs'.");
    }
}

$current_path = '';
if (isset($_GET['path'])) {
    $current_path = $_GET['path'];
}

// Security: Basic sanitization and validation
$current_path = trim(str_replace('..', '', $current_path), '/');
$full_path = $labs_dir . $current_path;

$real_labs_path = realpath($labs_dir);
$real_full_path = realpath($full_path);

if ($real_full_path === false || strpos($real_full_path, $real_labs_path) !== 0 || !is_dir($real_full_path)) {
    die('Đường dẫn không hợp lệ.');
}

$page_title = 'Danh sách bài tập';
if (!empty($current_path)) {
    $page_title = 'Thư mục: ' . htmlspecialchars($current_path);
}

$items = [];
$scan_result = scandir($full_path);
$files_and_dirs = array_diff($scan_result, ['.', '..']);

foreach ($files_and_dirs as $item_name) {
    $item_path_inside_labs = ltrim($current_path . '/' . $item_name, '/');
    $full_item_path = $labs_dir . $item_path_inside_labs;
    $is_dir = is_dir($full_item_path);

    $items[] = [
        'name' => $item_name,
        'path' => $is_dir ? 'assignments.php?path=' . urlencode($item_path_inside_labs) : $full_item_path,
        'is_dir' => $is_dir,
    ];
}

$parent_path = dirname($current_path);
if ($parent_path === '.') {
    $parent_path = '';
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
        .navbar { background: #333; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; padding: 0.5rem 1rem; }
        .navbar a:hover { background: #555; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; }
        h1 { text-align: center; margin-bottom: 1.5rem; }
        ul { list-style: none; padding: 0; }
        li { background: #eee; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;}
        li a { text-decoration: none; color: #333; font-weight: bold; }
        li a:hover { color: #007bff; }
        .back-link { display: inline-block; margin-bottom: 1rem; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .item-type { color: #666; font-size: 0.9em; }
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
        <h1><?php echo $page_title; ?></h1>

        <?php if (!empty($current_path)): ?>
            <a href="assignments.php?path=<?php echo urlencode($parent_path); ?>" class="back-link">&laquo; Quay lại</a>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <p>Thư mục này trống.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($item['path']); ?>" <?php if (!$item['is_dir']) echo 'target="_blank"'; ?>>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <span class="item-type"><?php echo $item['is_dir'] ? 'Thư mục' : 'Tệp tin'; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
