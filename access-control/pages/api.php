<?php
// ------------------------------
// API ساده: خروجی JSON لیست کاربران و نقش‌ها (نمونه برای توسعه)
// ------------------------------
require_once __DIR__.'/../access-init.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'view_dashboard'))
    die(json_encode(['error'=>'access denied']));

header('Content-Type: application/json; charset=utf-8');
global $config;
$users = db_query("SELECT id, username, email, role FROM `".$config['tables']['users']."`")->fetchAll();
$roles = db_query("SELECT name, label FROM `".$config['tables']['roles']."`")->fetchAll();
echo json_encode(['users'=>$users, 'roles'=>$roles]);