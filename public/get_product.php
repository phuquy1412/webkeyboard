<?php
/**
 * API endpoint để lấy thông tin sản phẩm theo ID
 */
header('Content-Type: application/json');
include_once '../config/database.php';
include_once '../models/Product.php';

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }

    $productId = intval($_GET['id']);
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    $product = new Product($db);
    $productData = $product->getById($productId);

    if ($productData === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    echo json_encode($productData);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_product.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>