<?php
// ------------------------------
// توابع متای کاربر (ذخیره/بازیابی اطلاعات جانبی)
// ------------------------------
require_once __DIR__.'/db.php';

/**
 * گرفتن متا برای یک کاربر
 * @param int $user_id
 * @param string $key
 * @return mixed|null
 */
function get_user_meta($user_id, $key) {
    global $config;
    $tbl = $config['tables']['user_meta'];
    $r = db_query("SELECT meta_value FROM `$tbl` WHERE user_id=? AND meta_key=?", [$user_id, $key])->fetch();
    return $r ? $r['meta_value'] : null;
}

/**
 * ثبت یا ویرایش متا برای کاربر
 * @param int $user_id
 * @param string $key
 * @param mixed $value
 */
function set_user_meta($user_id, $key, $value) {
    global $config;
    $tbl = $config['tables']['user_meta'];
    if (get_user_meta($user_id, $key) !== null) {
        db_query("UPDATE `$tbl` SET meta_value=? WHERE user_id=? AND meta_key=?", [$value, $user_id, $key]);
    } else {
        db_query("INSERT INTO `$tbl` (user_id, meta_key, meta_value) VALUES (?,?,?)", [$user_id, $key, $value]);
    }
}