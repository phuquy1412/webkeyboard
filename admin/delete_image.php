<?php
session_start();

// Bảo vệ trang, chỉ admin mới có quyền truy cập
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Nếu không phải admin, không làm gì cả hoặc chuyển hướng
    header("Location: ../login.php");
    exit;
}

// Kiểm tra xem filename có được cung cấp không
if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    $_SESSION['message'] = 'Tên file không hợp lệ.';
    $_SESSION['message_type'] = 'error';
    header('Location: manage_images.php');
    exit;
}

$filename = basename($_GET['filename']);
$image_path = '../img/' . $filename;

// Kiểm tra an toàn: đảm bảo file nằm trong thư mục img
if (realpath($image_path) === false || strpos(realpath($image_path), realpath('../img')) !== 0) {
    $_SESSION['message'] = 'Lỗi: Cố gắng truy cập file không hợp lệ.';
    $_SESSION['message_type'] = 'error';
    header('Location: manage_images.php');
    exit;
}

// Xóa file
if (file_exists($image_path)) {
    if (unlink($image_path)) {
        $_SESSION['message'] = 'Đã xóa ảnh ' . htmlspecialchars($filename) . ' thành công.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Không thể xóa ảnh. Vui lòng kiểm tra quyền truy cập.';
        $_SESSION['message_type'] = 'error';
    }
} else {
    $_SESSION['message'] = 'File không tồn tại.';
    $_SESSION['message_type'] = 'error';
}

header('Location: manage_images.php');
exit;
