<?php
// تنظیمات دیتابیس
$host = 'localhost';      // هاست دیتابیس
$dbname = 'meeting_system'; // نام دیتابیس
$username = 'root';       // نام کاربری دیتابیس
$password = '';           // رمز عبور دیتابیس (در محیط تولید باید پر شود)
$port = 3307;             // پورت دیتابیس (معمولاً 3306)

// ایجاد اتصال PDO
try {
    // اتصال به دیتابیس با استفاده از UTF8MB4 برای پشتیبانی از کاراکترهای خاص
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // تنظیم مد خطا به Exception برای اشکال‌زدایی
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // در صورت شکست اتصال، پیغام خطا نمایش داده می‌شود
    die("اتصال به دیتابیس ناموفق بود: " . $e->getMessage());
}





// تابع تستی برای فیلد showMap (بعداً از دیتابیس خوانده می‌شود)
function isMapVisible() {
    // فعلاً یک مقدار تستی برمی‌گردانیم
	//return false; 
    return true; // true: نقشه نمایش داده شود | false: نقشه نمایش داده نشود
}







?>