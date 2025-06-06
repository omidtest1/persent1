<?php
// ------------------------------
// مدیریت ورود دو مرحله‌ای (2FA) برای کاربر فعلی
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';
require_once __DIR__.'/../user_meta.php';

if (!ac_user_id()) die(lang('access_denied'));

$user_id = ac_user_id();
$key = get_user_meta($user_id, '2fa_secret');
$msg = '';

// غیرفعال/فعال‌سازی دستی (نمونه ساده، بدون QR و اپلیکیشن)
if (isset($_POST['disable'])) {
    set_user_meta($user_id, '2fa_secret', '');
    $key = '';
    $msg = 'ورود دو مرحله‌ای غیرفعال شد.';
}
if (isset($_POST['enable'])) {
    $code = bin2hex(random_bytes(8));
    set_user_meta($user_id, '2fa_secret', $code);
    $key = $code;
    $msg = 'کلید ورود دو مرحله‌ای شما: ' . htmlspecialchars($code);
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ورود دو مرحله‌ای</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:500px">
    <h4>مدیریت ورود دو مرحله‌ای</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
    <?php if($key): ?>
        <div class="alert alert-success">
            ورود دو مرحله‌ای فعال است.<br>
            کلید شما: <b><?= htmlspecialchars($key) ?></b>
        </div>
        <form method="post">
            <button name="disable" class="btn btn-danger w-100">غیرفعال‌سازی 2FA</button>
        </form>
    <?php else: ?>
        <form method="post">
            <button name="enable" class="btn btn-primary w-100">فعال‌سازی ورود دو مرحله‌ای</button>
        </form>
    <?php endif;?>
    <a href="dashboard.php" class="btn btn-secondary mt-4">بازگشت به داشبورد</a>
</div>
</body>
</html>