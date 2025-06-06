<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/GeoFence.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('درخواست نامعتبر');
    }
    
    if (empty($_POST['fence_id'])) {
        throw new Exception('شناسه حصار الزامی است');
    }
    
    $geoFence = new GeoFence();
    $success = $geoFence->deleteFence((int)$_POST['fence_id']);
    
    if ($success) {
        $response = ['success' => true, 'message' => 'حصار با موفقیت حذف شد'];
    } else {
        throw new Exception('خطا در حذف حصار');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>