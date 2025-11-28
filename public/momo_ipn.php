<?php
/**
 * MoMo IPN Handler - Nhận thông báo thanh toán từ MoMo
 * IPN = Instant Payment Notification
 */
header("Content-Type: application/json");

include_once '../config/database.php';

$secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

if (!empty($_POST)) {
    try {
        // Lấy dữ liệu từ MoMo
        $partnerCode = isset($_POST["partnerCode"]) ? $_POST["partnerCode"] : "";
        $orderId = isset($_POST["orderId"]) ? $_POST["orderId"] : "";
        $requestId = isset($_POST["requestId"]) ? $_POST["requestId"] : "";
        $amount = isset($_POST["amount"]) ? $_POST["amount"] : "";
        $orderInfo = isset($_POST["orderInfo"]) ? $_POST["orderInfo"] : "";
        $orderType = isset($_POST["orderType"]) ? $_POST["orderType"] : "";
        $transId = isset($_POST["transId"]) ? $_POST["transId"] : "";
        $resultCode = isset($_POST["resultCode"]) ? $_POST["resultCode"] : "";
        $message = isset($_POST["message"]) ? $_POST["message"] : "";
        $payType = isset($_POST["payType"]) ? $_POST["payType"] : "";
        $responseTime = isset($_POST["responseTime"]) ? $_POST["responseTime"] : "";
        $extraData = isset($_POST["extraData"]) ? $_POST["extraData"] : "";
        $m2signature = isset($_POST["signature"]) ? $_POST["signature"] : "";

        // Verify signature
        $rawHash = "accessKey=klm05TvNBzhg7h7j&amount=" . $amount . "&extraData=" . $extraData . 
                   "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                   "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . 
                   "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                   "&resultCode=" . $resultCode . "&transId=" . $transId;

        $partnerSignature = hash_hmac("sha256", $rawHash, $secretKey);

        // Log IPN
        error_log("=== MoMo IPN ===");
        error_log("Order ID: " . $orderId);
        error_log("Result Code: " . $resultCode);
        error_log("Trans ID: " . $transId);
        error_log("Signature Match: " . ($m2signature === $partnerSignature ? "YES" : "NO"));

        // Validate signature
        if ($m2signature !== $partnerSignature) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Chữ ký không hợp lệ'
            ]);
            exit;
        }

        // Kết nối database
        $database = new Database();
        $db = $database->getConnection();

        // Xác định ID đơn hàng nội bộ (local order id)
        // Trong request tới MoMo, chúng ta đã gửi `extraData` = base64_encode(json_encode(['orderId' => $orderId]))
        // và `orderId` gửi tới MoMo có thể là dạng "$momoOrderId" (time-orderId).
        // Ở đây cố gắng lấy id gốc từ `extraData`, nếu không thì tách số phía sau dấu '-' từ `orderId`.
        $localOrderId = null;
        if (!empty($extraData)) {
            $decoded = base64_decode($extraData, true);
            if ($decoded !== false) {
                $parsed = json_decode($decoded, true);
                if (is_array($parsed) && isset($parsed['orderId'])) {
                    $localOrderId = intval($parsed['orderId']);
                }
            }
        }

        // Nếu chưa có, cố gắng tách phần số cuối của orderId (ví dụ "1600000000-123" -> 123)
        if (!$localOrderId) {
            if (preg_match('/-(\d+)$/', $orderId, $m)) {
                $localOrderId = intval($m[1]);
            } elseif (ctype_digit($orderId)) {
                $localOrderId = intval($orderId);
            }
        }

        if (!$localOrderId) {
            error_log("IPN Error: Không xác định được order_id cục bộ từ extraData hoặc orderId: extraData={$extraData} orderId={$orderId}");
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Không xác định được order_id'
            ]);
            exit;
        }

        // Cập nhật trạng thái order
        if ($resultCode == '0') {
            // Payment successful
            $status = 'confirmed';
            $paymentStatus = 'success';
        } else {
            // Payment failed
            $status = 'cancelled';
            $paymentStatus = 'failed';
        }

        // Update order status using localOrderId
        $query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $localOrderId]);

        // Log payment info (gán order_id là localOrderId)
        $query = "INSERT INTO payment_logs (order_id, provider, trans_id, amount, status, response) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $localOrderId,
            'momo',
            $transId,
            $amount,
            $paymentStatus,
            json_encode($_POST)
        ]);

        // Return success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'IPN processed',
            'orderId' => $orderId,
            'paymentStatus' => $paymentStatus
        ]);

    } catch (Exception $e) {
        error_log("IPN Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
?>
