<?php
session_start();

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true) {
        header("Location: ../admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

include_once '../config/database.php';
include_once '../models/User.php';

$message = '';

// Only process form data if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $user->email = $_POST['email'];
        
        $stmt = $user->findByEmail();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kiểm tra mật khẩu
            if (isset($row['password']) && password_verify($_POST['password'], $row['password'])) {
                // Success: Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['is_admin'] = (bool)$row['is_admin'];

                // Redirect based on admin status
                if ($_SESSION['is_admin']) {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                // Failure: Incorrect password or user not found
                $message = "Sai mật khẩu hoặc email không tồn tại nhá.";
            }
        } else {
            // Failure: Incorrect password or user not found
            $message = "Sai mật khẩu hoặc email không tồn tạiiiiii.";
        }
    } else {
        // Failure: Fields were empty
        $message = "Vui lòng nhập đầy đủ email và mật khẩu.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Keyboard Shop</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 400px; }
        h1 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #3498db; color: white; padding: 0.7rem; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 1rem; }
        .btn:hover { background: #2980b9; }
        .message { text-align: center; margin-bottom: 1rem; color: #e74c3c; }
        .register-link { text-align: center; margin-top: 1rem; }
        .register-link a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Đăng nhập</h1>
        <?php if ($message): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Đăng nhập</button>
        </form>
        <div class="register-link">
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
        </div>
    </div>
</body>
</html>