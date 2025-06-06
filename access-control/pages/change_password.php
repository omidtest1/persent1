<?php
// ------------------------------
// تغییر رمز عبور توسط کاربر
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';
require_once __DIR__.'/../user_meta.php';

if (!ac_user_id()) header('Location: login.php');

$msg = '';
$force = isset($_GET['force']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    if (strlen($pass) < 6) {
        $msg = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        db_query("UPDATE users SET password=? WHERE id=?", [$hash, ac_user_id()]);
        set_user_meta(ac_user_id(), 'must_change_password', 0);
        $msg = 'رمز عبور با موفقیت تغییر یافت.';
        if ($force) header('Location: dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>تغییر رمز عبور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width:500px">
    <h4>تغییر رمز عبور</h4>
    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="password">رمز عبور جدید:</label>
            <input type="password" name="password" class="form-control" id="password" required minlength="6">
        </div>
        <button class="btn btn-primary w-100">تغییر رمز</button>
    </form>
    <a href="dashboard.php" class="btn btn-secondary mt-4">بازگشت به داشبورد</a>
</div>
</body>
</html>