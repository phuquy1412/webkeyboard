<?php
/**
 * API Endpoint để check order status (dùng cho polling)
 * JavaScript sẽ gọi endpoint này mỗi 1 giây để kiểm tra DB sync
 */
header('Content-Type: application/json');

include_once 'config/database.php';

try {
    $orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;

    if (!$orderId) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing orderId parameter'
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Query order status từ DB
    $query = "SELECT status FROM orders WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Order not found',
            'orderId' => $orderId
        ]);
        exit;
    }

    // Return order status
    http_response_code(200);
    echo json_encode([
        'status' => $order['status'],
        'orderId' => $orderId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
