<?php
// ------------------------------
// هسته سشن و کنترل دسترسی مرکزی
// ------------------------------

// فعال‌سازی سشن
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// بارگذاری تنظیمات و ابزارهای پایگاه داده
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';

/**
 * دریافت شناسه کاربر جاری
 * @return int|null
 */
function ac_user_id() {
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
}

/**
 * دریافت اطلاعات کاربر جاری
 * @return array|null
 */
function current_user() {
    global $config;
    $uid = ac_user_id();
    if (!$uid) return null;
    $table = $config['tables']['users'];
    $stmt = ac_get_db()->prepare("SELECT * FROM `$table` WHERE id=?");
    $stmt->execute([$uid]);
    return $stmt->fetch();
}

/**
 * بررسی مجوز کاربر جاری
 * @param int $user_id
 * @param string $permission
 * @return bool
 */
function user_has_permission($user_id, $permission) {
    global $config;
    if (!$user_id) return false;
    $users_table = $config['tables']['users'];
    $roles_table = $config['tables']['roles'];
    $role_perm_table = $config['tables']['role_permissions'];
    $perm_table = $config['tables']['permissions'];
    // 1. بررسی نقش و مجوز نقش
    $user = db_query("SELECT * FROM `$users_table` WHERE id=?", [$user_id])->fetch();
    if (!$user) return false;
    $role = $user['role'];
    $role_id = db_query("SELECT id FROM `$roles_table` WHERE name=?", [$role])->fetchColumn();
    $perm_id = db_query("SELECT id FROM `$perm_table` WHERE name=?", [$permission])->fetchColumn();
    if ($role_id && $perm_id) {
        $has = db_query("SELECT id FROM `$role_perm_table` WHERE role_id=? AND permission_id=?", [$role_id, $perm_id])->fetch();
        if ($has) return true;
    }
    // 2. بررسی مجوز گروهی (سطح select)
    $groups = db_query("SELECT group_id FROM group_users WHERE user_id=?", [$user_id])->fetchAll(PDO::FETCH_COLUMN);
    if ($groups && $perm_id) {
        $in = implode(',', array_map('intval', $groups));
        $sql = "SELECT id FROM group_permissions WHERE group_id IN ($in) AND permission_id=? AND can_select=1";
        $res = db_query($sql, [$perm_id])->fetch();
        if ($res) return true;
    }
    return false;
}