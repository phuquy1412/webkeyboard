<?php
/**
 * MoMo Payment Integration - E-Wallet
 * Khởi tạo giao dịch thanh toán với MoMo
 */
session_start();
include_once 'config/database.php';

// Kiểm tra order ID
$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;

if (!$orderId || !$amount) {
    die("Thông tin đơn hàng không hợp lệ");
}

// Tạo một ID đơn hàng duy nhất để gửi cho MoMo, tránh bị trùng lặp khi test
$momoOrderId = time() . "-" . $orderId; 

// MoMo Configuration (TEST MODE)
$partnerCode = 'MOMOBKUN20180529';
$accessKey = 'klm05TvNBzhg7h7j';
$secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
$endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';

// Detect current host and build URLs dynamically
$currentScheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Build redirect and IPN URLs based on current host
if (strpos($currentHost, 'localhost') !== false || strpos($currentHost, '127.0.0.1') !== false) {
    // Local development
    $baseUrl = "http://localhost/doanweb/public";
} else {
    // Production (InfinityFree or other host)
    $baseUrl = $currentScheme . "://" . $currentHost;
}

// Request parameters
$requestId = time() . "";
$requestType = "captureWallet";
$orderInfo = "Thanh toán đơn hàng #" . $orderId;
// Giữ lại orderId gốc trong extraData để xử lý khi MoMo redirect về
$extraData = base64_encode(json_encode(['orderId' => $orderId])); 
$redirectUrl = $baseUrl . "/momo_return.php?orderId=" . $orderId;
$ipnUrl = $baseUrl . "/momo_ipn.php";
$lang = 'vi';

// Tạo signature - SỬ DỤNG $momoOrderId
$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . 
           "&ipnUrl=" . $ipnUrl . "&orderId=" . $momoOrderId . "&orderInfo=" . $orderInfo . 
           "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . 
           "&requestId=" . $requestId . "&requestType=" . $requestType;

$signature = hash_hmac("sha256", $rawHash, $secretKey);

// Prepare request data - SỬ DỤNG $momoOrderId
$data = array(
    'partnerCode' => $partnerCode,
    'partnerName' => "Keyboard Shop",
    'storeId' => "KeyboardShop",
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $momoOrderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'lang' => $lang,
    'extraData' => $extraData,
    'requestType' => $requestType,
    'signature' => $signature
);

// Send request to MoMo
function execPostRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    // !!! Bỏ qua xác minh SSL cho môi trường DEV (KHÔNG DÙNG TRÊN PRODUCTION) !!!
    // !!! Bypass SSL verification for DEV environment (DO NOT USE IN PRODUCTION) !!!
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($result === false) {
        $curlError = curl_error($ch);
        curl_close($ch);
        return array('result' => null, 'httpCode' => $httpCode, 'error' => $curlError);
    }
    
    curl_close($ch);
    
    return array('result' => $result, 'httpCode' => $httpCode, 'error' => null);
}

// Execute payment request
$response = execPostRequest($endpoint, $data);

// Check for cURL errors
if ($response['error'] !== null) {
    $_SESSION['error'] = "Lỗi kết nối đến MoMo: " . $response['error'];
    header("Location: checkout.php");
    exit;
}

$jsonResult = json_decode($response['result'], true);

// Log request/response chi tiết
error_log("=== MoMo Payment Request ===");
error_log("Request Data: " . json_encode($data, JSON_PRETTY_PRINT));
error_log("=== MoMo Payment Response ===");
error_log("HTTP Code: " . $response['httpCode']);
error_log("Response Body: " . $response['result']);
error_log("Decoded Response: " . json_encode($jsonResult, JSON_PRETTY_PRINT));

// In ra màn hình cho debug (xóa sau khi test xong)
echo "<pre>";
echo "=== DEBUG MoMo Response ===\n";
echo "HTTP Code: " . $response['httpCode'] . "\n";
echo "Response Status: " . (isset($jsonResult['status']) ? $jsonResult['status'] : 'N/A') . "\n";
echo "Response Message: " . (isset($jsonResult['message']) ? $jsonResult['message'] : 'N/A') . "\n";
echo "Full Response:\n";
echo json_encode($jsonResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n=== END DEBUG ===\n";
echo "</pre>";

// Redirect to MoMo payment page or show error
if (isset($jsonResult['payUrl']) && !empty($jsonResult['payUrl'])) {
    header('Location: ' . $jsonResult['payUrl']);
    exit;
} else {
    // Error handling
    $status = isset($jsonResult['status']) ? $jsonResult['status'] : 'unknown';
    $error = isset($jsonResult['message']) ? $jsonResult['message'] : "Không nhận được phản hồi từ MoMo";
    $fullResponse = json_encode($jsonResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $_SESSION['error'] = "Lỗi thanh toán MoMo (Status: " . $status . "): " . $error . "\n\nChi tiết:\n" . $fullResponse;
    
    // In ra lỗi và yêu cầu người dùng quay lại
    echo "<pre>";
    echo "Lỗi thanh toán:\n";
    echo $_SESSION['error'];
    echo "\n\n<a href='checkout.php'>← Quay lại giỏ hàng</a>";
    echo "</pre>";
    exit;
}
?>
