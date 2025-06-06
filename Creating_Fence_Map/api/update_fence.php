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
    
    $requiredFields = ['fence_id', 'meeting_id', 'name', 'coordinates', 'fence_type'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("فیلد {$field} الزامی است");
        }
    }
    
    // اعتبارسنجی مختصات
    $coordinates = $_POST['coordinates'];
    if ($_POST['fence_type'] === 'circle') {
        $coords = json_decode($coordinates, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $coords = array_map('floatval', explode(',', $coordinates));
        }
        $coordinates = json_encode($coords);
    } else {
        if (!json_decode($coordinates)) {
            throw new Exception("فرمت مختصات نامعتبر است");
        }
    }
    
    $geoFence = new GeoFence();
    $success = $geoFence->updateFence([
        'id' => (int)$_POST['fence_id'],
        'meeting_id' => (int)$_POST['meeting_id'],
        'name' => trim($_POST['name']),
        'coordinates' => $coordinates,
        'fence_type' => $_POST['fence_type'],
        'radius' => $_POST['fence_type'] === 'circle' ? (float)$_POST['radius'] : null
    ]);
    
    if ($success) {
        $response = ['success' => true, 'message' => 'تغییرات با موفقیت ذخیره شد'];
    } else {
        throw new Exception('خطا در به‌روزرسانی حصار');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>