<?php
/**
 * تنظیمات پایه سیستم
 * Base configuration for the geo-fencing system
 */

// نمایش خطاها (در محیط توسعه فعال باشد)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تنظیمات امنیتی
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// تنظیمات زمانzone
date_default_timezone_set('Asia/Tehran');

// مسیرهای پایه
//define('BASE_URL', 'http://localhost/geo-fencing');
//define('BASE_PATH', __DIR__ . '/..');

// در فایل includes/config.php
define('BASE_URL', 'http://localhost/aaa/map');
define('BASE_PATH', __DIR__ . '/../..'); // با توجه به ساختار پوشه‌ها

?>
