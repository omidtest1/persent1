<?php
// ------------------------------
// مدیریت نقش‌ها (افزودن، حذف، تغییر عنوان)
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_roles'))
    die(lang('access_denied'));

global $config;
$table = $config['tables']['roles'];

$msg = '';
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $label = trim($_POST['label']);
    if ($name) {
        db_query("INSERT IGNORE INTO `$table` (name,label) VALUES (?,?)", [$name, $label]);
        $msg = 'نقش جدید افزوده شد.';
    }
}
if (isset($_GET['del'])) {
    $rid = intval($_GET['del']);
    if ($rid) {
        db_query("DELETE FROM `$table` WHERE id=?", [$rid]);
        $msg = 'نقش حذف شد.';
    }
}
if (isset($_GET['edit'])) {
    $rid = intval($_GET['edit']);
    $label = trim($_GET['label']);
    db_query("UPDATE `$table` SET label=? WHERE id=?", [$label, $rid]);
    $msg = 'عنوان نقش ویرایش شد.';
}
$roles = db_query("SELECT * FROM `$table` ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت نقش‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>مدیریت نقش‌ها</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <input type="hidden" name="add" value="1">
        <div class="col">
            <input type="text" name="name" class="form-control" placeholder="نام نقش (انگلیسی)" required>
        </div>
        <div class="col">
            <input type="text" name="label" class="form-control" placeholder="عنوان فارسی">
        </div>
        <div class="col-auto">
            <button class="btn btn-success">افزودن نقش</button>
        </div>
    </form>
    <table class="table table-bordered table-sm">
        <thead><tr>
            <th>#</th><th>نام</th><th>برچسب</th><th>عملیات</th>
        </tr></thead>
        <tbody>
        <?php foreach($roles as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td>
                    <form method="get" class="d-inline">
                        <input type="hidden" name="edit" value="<?= $r['id'] ?>">
                        <input type="text" name="label" value="<?= htmlspecialchars($r['label']) ?>" class="form-control form-control-sm d-inline" style="width:auto;display:inline-block;">
                        <button class="btn btn-outline-primary btn-sm">ذخیره</button>
                    </form>
                </td>
                <td>
                    <?php if ($r['name'] !== 'admin'): ?>
                        <a href="?del=<?= $r['id'] ?>" onclick="return confirm('حذف شود؟')" class="btn btn-danger btn-sm">حذف</a>
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