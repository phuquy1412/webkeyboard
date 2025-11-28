<?php
session_start();
include_once 'config/database.php';
include_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (
        !empty($_POST['full_name']) &&
        !empty($_POST['email']) &&
        !empty($_POST['password'])
    ) {
        $user->full_name = $_POST['full_name'];
        $user->email = $_POST['email'];
        $user->password = $_POST['password'];
        $user->phone = $_POST['phone'] ?? '';
        $user->address = $_POST['address'] ?? '';

        if ($user->emailExists()) {
            $message = "Email đã tồn tại. Vui lòng chọn email khác.";
        } else {
            if ($user->create()) {
                $message = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
                // Redirect to login page after a short delay
                header("refresh:2;url=login.php");
            } else {
                $message = "Đã có lỗi xảy ra. Vui lòng thử lại.";
            }
        }
    } else {
        $message = "Vui lòng điền đầy đủ các trường bắt buộc.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - Keyboard Shop</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .register-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 400px; }
        h1 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #3498db; color: white; padding: 0.7rem; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 1rem; }
        .btn:hover { background: #2980b9; }
        .message { text-align: center; margin-bottom: 1rem; color: #e74c3c; }
        .login-link { text-align: center; margin-top: 1rem; }
        .login-link a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Đăng ký tài khoản</h1>
        <?php if ($message): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="full_name">Họ và tên *</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu *</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <input type="text" id="address" name="address">
            </div>
            <button type="submit" class="btn">Đăng ký</button>
        </form>
        <div class="login-link">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
    </div>
</body>
</html>
