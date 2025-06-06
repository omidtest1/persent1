<?php
// ------------------------------
// فرم درخواست دسترسی جدید توسط کاربر
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id()) die(lang('access_denied'));

global $config;
$table = $config['tables']['access_request'];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perm = trim($_POST['permission'] ?? '');
    if ($perm) {
        db_query("INSERT INTO `$table` (user_id, requested_permission, status, created_at) VALUES (?, ?, 'pending', NOW())",
            [ac_user_id(), $perm]);
        $msg = 'درخواست شما ثبت شد.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>درخواست دسترسی جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:500px">
    <h4>درخواست دسترسی جدید</h4>
    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="mb-3">
        <label for="perm" class="mb-2">شرح مجوز یا صفحه موردنیاز:</label>
        <input type="text" id="perm" name="permission" class="form-control mb-2" placeholder="مثال: گزارش فروش">
        <button class="btn btn-primary w-100">ثبت درخواست</button>
    </form>
    <a href="dashboard.php" class="btn btn-secondary">بازگشت به داشبورد</a>
</div>
</body>
</html>