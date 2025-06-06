<?php
// ------------------------------
// صفحه ورود کاربران به سیستم مدیریت دسترسی
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';
require_once __DIR__.'/../audit_log.php';
require_once __DIR__.'/../user_meta.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_id = login_user($username, $password);
    if ($user_id) {
        log_event($user_id, 'login', 'ورود موفق');
        $must_change = get_user_meta($user_id, 'must_change_password');
        if ($must_change == 1) {
            header('Location: change_password.php?force=1');
            exit;
        }
        header('Location: dashboard.php');
        exit;
    } else {
        $error = lang('access_denied');
    }
}

/**
 * ورود کاربر و مقداردهی سشن
 * @param string $username
 * @param string $password
 * @return int|false شناسه کاربر یا false
 */
function login_user($username, $password) {
    global $config;
    $table = $config['tables']['users'];
    $stmt = ac_get_db()->prepare("SELECT * FROM `$table` WHERE username=? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return $user['id'];
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title><?= lang('login') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; }
        .login-box { max-width: 370px; margin: 100px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 24px #0002; padding: 36px 24px;}
    </style>
</head>
<body>
<div class="login-box">
    <h3 class="mb-3 text-center"><?= lang('login') ?></h3>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label><?= lang('username') ?>:</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label><?= lang('password') ?>:</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100"><?= lang('login') ?></button>
    </form>
</div>
</body>
</html>