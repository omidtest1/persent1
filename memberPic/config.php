<?php
$host = 'localhost';
$dbname = 'meeting_system';
$username = 'root';
$password = '';
$port = 3307;
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$config = [
    'modal_confirm_checkin_again' => true,
    'modal_confirm_checkout_without_checkin' => true,
    'modal_confirm_checkout_again' => true,
    'modal_confirm_vote_paper' => true,
    'admin_only_checkin_again' => true,
    'admin_only_checkout_without_checkin' => true,
    'admin_only_checkout_again' => true,
    'admin_only_vote_paper_abnormal' => true,
    'clear_list_and_focus_search_after_action' => true,
    'vote_paper_width_mm' => 80,
    'vote_paper_mode' => 'preprinted',
];
?>