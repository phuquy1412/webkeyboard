<?php
session_start();

// Bảo vệ trang, chỉ admin mới có quyền truy cập
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../public/login.php");
    exit;
}

include_once '../config/database.php';
include_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$action = $_GET['action'] ?? 'view'; // Mặc định là xem danh sách
$id = $_GET['id'] ?? null;
$error = null;

// Xử lý yêu cầu POST (Thêm & Sửa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gán dữ liệu từ form vào đối tượng product
    $product->name = $_POST['name'];
    $product->price = $_POST['price'];
    $product->old_price = $_POST['old_price'] ?? 0;
    $product->category = $_POST['category'];
    $product->switch_type = $_POST['switch_type'];
    $product->stock = $_POST['stock'];
    $product->description = $_POST['description'];

    // Mặc định lấy URL từ post, sẽ được ghi đè nếu có file upload
    $product->image_url = $_POST['image_url'] ?? '';

    if ($action === 'create') {
        // Xử lý upload file ảnh
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            $target_dir = "../img/";
            // Tạo một tên file an toàn để tránh trùng lặp hoặc ký tự đặc biệt
            $safe_filename = basename($_FILES["image_file"]["name"]);
            $target_file = $target_dir . $safe_filename;
            
            // Di chuyển file đã upload
            if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
                // Lưu đường dẫn tương đối vào database
                $product->image_url = '/doanweb/img/' . $safe_filename;
            } else {
                $error = "Đã xảy ra lỗi khi upload file.";
                // Cân nhắc dừng lại tại đây nếu upload ảnh là bắt buộc
            }
        }
        
        if (!$error && $product->create()) {
            header("Location: index.php");
            exit;
        } else {
            $error = $error ?? "Không thể tạo sản phẩm.";
        }
    } elseif ($action === 'edit') {
        $product->id = $_POST['id'];
        $product->image_url = $_POST['image_url']; // Giữ nguyên logic cho sửa
        if ($product->update()) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Không thể cập nhật sản phẩm.";
        }
    }
}

// Xử lý Xóa
if ($action === 'delete' && $id) {
    // TODO: Nên xóa file ảnh cũ khỏi server trước khi xóa record DB
    $product->id = $id;
    if ($product->delete()) {
        header("Location: index.php");
        exit;
    } else {
        die('Không thể xóa sản phẩm.');
    }
}

// Lấy dữ liệu cho form Sửa
$product_data = null;
if ($action === 'edit' && $id) {
    $product_data = $product->getById($id);
}

// Đặt tiêu đề cho trang
$page_title = 'Thêm sản phẩm mới';
if ($action === 'edit') {
    $page_title = 'Sửa sản phẩm';
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; }
        h1 { margin-bottom: 1.5rem; text-align: center; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.7rem; border: 1px solid #ccc; border-radius: 4px; }
        .form-actions { margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .btn { background: #3498db; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #2980b9; }
        .back-link { color: #333; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .error { color: #e74c3c; margin-bottom: 1rem; background: #fdd; padding: 1rem; border: 1px solid #fbb; border-radius: 4px; }
        .current-image { margin-top: 10px; }
        .current-image img { max-width: 150px; height: auto; border: 1px solid #ddd; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $page_title; ?></h1>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="products.php?action=<?php echo $action; ?><?php if($id) echo '&id='.$id; ?>" method="POST" enctype="multipart/form-data">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $product_data['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Tên sản phẩm</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product_data['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="price">Giá</label>
                <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product_data['price'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="old_price">Giá cũ (tùy chọn)</label>
                <input type="number" id="old_price" name="old_price" value="<?php echo htmlspecialchars($product_data['old_price'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="category">Danh mục</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($product_data['category'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="switch_type">Loại Switch</label>
                <input type="text" id="switch_type" name="switch_type" value="<?php echo htmlspecialchars($product_data['switch_type'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Tồn kho</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product_data['stock'] ?? '0'); ?>" required>
            </div>

            <?php if ($action === 'edit'): ?>
                <div class="form-group">
                    <label for="image_url">Đường dẫn hình ảnh</label>
                    <input type="text" id="image_url" name="image_url" value="<?php echo htmlspecialchars($product_data['image_url'] ?? ''); ?>">
                    <?php if (!empty($product_data['image_url'])): ?>
                        <div class="current-image">
                            <p>Ảnh hiện tại:</p>
                            <img src="../<?php echo htmlspecialchars($product_data['image_url']); ?>" alt="Ảnh sản phẩm">
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="image_file">Hình ảnh sản phẩm</label>
                    <input type="file" id="image_file" name="image_file" accept="image/*">
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($product_data['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions">
                <a href="index.php" class="back-link">Quay lại danh sách</a>
                <button type="submit" class="btn"><?php echo $action === 'edit' ? 'Cập nhật' : 'Thêm mới'; ?></button>
            </div>
        </form>
    </div>
</body>
</html>
