<?php
// ------------------------------
// توابع ثبت و دریافت لاگ عملیات سیستم مدیریت دسترسی
// ------------------------------
require_once __DIR__.'/db.php';

/**
 * ثبت رخداد (لاگ) جدید
 * @param int $user_id شناسه کاربر
 * @param string $action عملیات
 * @param string $detail توضیح
 */
function log_event($user_id, $action, $detail) {
    global $config;
    if (empty($config['audit_log_enabled'])) return;
    $table = $config['tables']['audit_log'];
    db_query("INSERT INTO `$table` (user_id, action, detail, log_time) VALUES (?, ?, ?, NOW())", [$user_id, $action, $detail]);
}