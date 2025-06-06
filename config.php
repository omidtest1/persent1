<?php
$host = 'localhost';
$dbname = 'meeting_system';
$username = 'root';
$password = '';
$port = 3307;
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// بارگذاری تنظیمات از دیتابیس
$settings = [];
$stmt = $pdo->prepare("SELECT * FROM settings WHERE is_deleted=0");
$stmt->execute();
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['key_name']] = $row['setting_value'];
}
function get_setting($key, $default=null) {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}

$config = [
    'modal_confirm_checkin_again' => boolval(get_setting('modal_confirm_checkin_again', true)),
    'modal_confirm_checkout_without_checkin' => boolval(get_setting('modal_confirm_checkout_without_checkin', true)),
    'modal_confirm_checkout_again' => boolval(get_setting('modal_confirm_checkout_again', true)),
    'modal_confirm_vote_paper' => boolval(get_setting('modal_confirm_vote_paper', true)),
    'admin_only_checkin_again' => boolval(get_setting('admin_only_checkin_again', true)),
    'admin_only_checkout_without_checkin' => boolval(get_setting('admin_only_checkout_without_checkin', true)),
    'admin_only_checkout_again' => boolval(get_setting('admin_only_checkout_again', true)),
    'admin_only_vote_paper_abnormal' => boolval(get_setting('admin_only_vote_paper_abnormal', true)),
    'clear_list_and_focus_search_after_action' => boolval(get_setting('clear_list_and_focus_search_after_action', true)),
    'vote_paper_width_mm' => intval(get_setting('vote_paper_width_mm', 80)),
    'vote_paper_mode' => get_setting('vote_paper_mode', 'system'),
    'enable_vote_barcode_scan' => boolval(get_setting('enable_vote_barcode_scan', true)),
    'enable_vote_barcode_generation' => boolval(get_setting('enable_vote_barcode_generation', true)),
    'enable_vote_barcode_validation' => boolval(get_setting('enable_vote_barcode_validation', true)),
    'check_vote_serial_unique' => boolval(get_setting('check_vote_serial_unique', true)),
];
?>