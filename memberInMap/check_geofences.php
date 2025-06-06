<?php
header('Content-Type: application/json');

require_once 'functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$latitude = $data['lat'] ?? null;
$longitude = $data['lng'] ?? null;

if ($latitude === null || $longitude === null) {
    echo json_encode(['error' => 'مختصات نامعتبر']);
    exit;
}

$result = checkUserInGeofences($latitude, $longitude);
echo json_encode(['inFences' => $result]);
?>