<?php
// ------------------------------
// مدیریت کاربران (نمایش، افزودن، حذف، تغییر نقش)
// ------------------------------

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_users'))
    die(lang('access_denied'));

global $config;
$table = $config['tables']['users'];
$role_table = $config['tables']['roles'];

$msg = '';

// افزودن کاربر جدید
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    if ($username && $password && $role) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        db_query("INSERT IGNORE INTO `$table` (username,password,email,role) VALUES (?,?,?,?)", [$username, $hash, $email, $role]);
        $msg = 'کاربر جدید افزوده شد.';
    }
}

// حذف کاربر
if (isset($_GET['del'])) {
    $uid = intval($_GET['del']);
    db_query("DELETE FROM `$table` WHERE id=?", [$uid]);
    $msg = 'کاربر حذف شد.';
}

// تغییر نقش کاربر
if (isset($_GET['change_role'])) {
    $uid = intval($_GET['change_role']);
    $role = trim($_GET['role']);
    db_query("UPDATE `$table` SET role=? WHERE id=?", [$role, $uid]);
    $msg = 'نقش کاربر تغییر یافت.';
}

$users = db_query("SELECT * FROM `$table` ORDER BY id DESC")->fetchAll();
$roles = db_query("SELECT * FROM `$role_table`")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت کاربران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>مدیریت کاربران</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <input type="hidden" name="add_user" value="1">
        <div class="col">
            <input type="text" name="username" class="form-control" placeholder="نام کاربری" required>
        </div>
        <div class="col">
            <input type="password" name="password" class="form-control" placeholder="رمز عبور" required>
        </div>
        <div class="col">
            <input type="email" name="email" class="form-control" placeholder="ایمیل">
        </div>
        <div class="col">
            <select name="role" class="form-select" required>
                <option value="">نقش</option>
                <?php foreach($roles as $r): ?>
                    <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['label']) ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-success">افزودن کاربر</button>
        </div>
    </form>
    <table class="table table-bordered table-sm">
        <thead><tr>
            <th>#</th><th>نام کاربری</th><th>ایمیل</th><th>نقش</th><th>تاریخ ایجاد</th><th>عملیات</th>
        </tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <form method="get" class="d-inline">
                        <input type="hidden" name="change_role" value="<?= $u['id'] ?>">
                        <select name="role" class="form-select form-select-sm d-inline" style="width:auto;display:inline-block;">
                            <?php foreach($roles as $r): ?>
                                <option value="<?= htmlspecialchars($r['name']) ?>"<?= $u['role']==$r['name']?' selected':'' ?>>
                                    <?= htmlspecialchars($r['label']) ?>
                                </option>
                            <?php endforeach;?>
                        </select>
                        <button class="btn btn-outline-primary btn-sm">تغییر</button>
                    </form>
                </td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <?php if ($u['username'] !== 'admin'): ?>
                        <a href="?del=<?= $u['id'] ?>" onclick="return confirm('حذف شود؟')" class="btn btn-danger btn-sm">حذف</a>
                    <?php endif;?>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
</div>
</body>
</html>