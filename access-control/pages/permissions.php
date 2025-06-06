<?php
// ------------------------------
// مدیریت مجوزها + اتصال/حذف مجوز به/از نقش یا گروه + دسته‌بندی و عملیات مجاز
// ------------------------------
// این صفحه برای ایجاد، حذف و اتصال مجوزها به نقش و گروه با سطوح عملیاتی است.

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_permissions'))
    die(lang('access_denied'));

global $config;
$perm_table = $config['tables']['permissions'];
$role_table = $config['tables']['roles'];
$group_table = $config['tables']['groups'];
$rp_table = $config['tables']['role_permissions'];
$gp_table = $config['tables']['group_permissions'];
$cat_table = $config['tables']['permission_categories'];

$msg = '';
// افزودن مجوز جدید
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $label = trim($_POST['label']);
    $category_id = intval($_POST['category_id']);
    if ($name) {
        db_query("INSERT IGNORE INTO `$perm_table` (name,label,category_id) VALUES (?,?,?)", [$name, $label, $category_id]);
        $msg = 'مجوز جدید افزوده شد.';
    }
}
// حذف مجوز
if (isset($_GET['del'])) {
    $pid = intval($_GET['del']);
    if ($pid) {
        db_query("DELETE FROM `$perm_table` WHERE id=?", [$pid]);
        $msg = 'مجوز حذف شد.';
    }
}
// اتصال مجوز به نقش
if (isset($_GET['connect_role'])) {
    $pid = intval($_GET['connect_role']);
    $rid = intval($_GET['role']);
    db_query("INSERT IGNORE INTO `$rp_table` (role_id,permission_id) VALUES (?,?)", [$rid, $pid]);
    $msg = 'مجوز به نقش افزوده شد.';
}
// قطع اتصال مجوز از نقش
if (isset($_GET['disconnect_role'])) {
    $pid = intval($_GET['disconnect_role']);
    $rid = intval($_GET['role']);
    db_query("DELETE FROM `$rp_table` WHERE role_id=? AND permission_id=?", [$rid, $pid]);
    $msg = 'مجوز از نقش حذف شد.';
}
// اتصال مجوز به گروه با سطوح عملیات
if (isset($_GET['connect_group'])) {
    $pid = intval($_GET['connect_group']);
    $gid = intval($_GET['group']);
    $ops = ['can_select','can_insert','can_update','can_delete'];
    $vals = [];
    foreach($ops as $op) $vals[$op] = isset($_GET[$op]) ? 1 : 0;
    db_query("INSERT IGNORE INTO `$gp_table` (group_id,permission_id,can_select,can_insert,can_update,can_delete) VALUES (?,?,?,?,?,?)",
        [$gid, $pid, $vals['can_select'], $vals['can_insert'], $vals['can_update'], $vals['can_delete']]
    );
    $msg = 'مجوز به گروه با عملیات انتخابی افزوده شد.';
}
// قطع اتصال مجوز از گروه
if (isset($_GET['disconnect_group'])) {
    $pid = intval($_GET['disconnect_group']);
    $gid = intval($_GET['group']);
    db_query("DELETE FROM `$gp_table` WHERE group_id=? AND permission_id=?", [$gid, $pid]);
    $msg = 'مجوز از گروه حذف شد.';
}

$permissions = db_query("SELECT p.*, c.name as cat_name FROM `$perm_table` p LEFT JOIN `$cat_table` c ON p.category_id=c.id ORDER BY p.id DESC")->fetchAll();
$roles = db_query("SELECT * FROM `$role_table`")->fetchAll();
$groups = db_query("SELECT * FROM `$group_table`")->fetchAll();
$cats = db_query("SELECT * FROM `$cat_table`")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت مجوزها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .mini {font-size: 0.9rem;}
    </style>
</head>
<body>
<div class="container mt-5">
    <h4>مدیریت مجوزها</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <input type="hidden" name="add" value="1">
        <div class="col">
            <input type="text" name="name" class="form-control" placeholder="نام مجوز (انگلیسی)" required>
        </div>
        <div class="col">
            <input type="text" name="label" class="form-control" placeholder="برچسب (فارسی)">
        </div>
        <div class="col">
            <select name="category_id" class="form-select">
                <option value="">دسته‌بندی</option>
                <?php foreach($cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['label'] ?: $c['name']) ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-success">افزودن</button>
        </div>
    </form>
    <table class="table table-bordered table-sm">
        <thead><tr>
            <th>#</th><th>نام</th><th>برچسب</th><th>دسته</th><th>عملیات</th>
        </tr></thead>
        <tbody>
        <?php foreach($permissions as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['label']) ?></td>
                <td><?= htmlspecialchars($p['cat_name']) ?></td>
                <td>
                    <a href="?del=<?= $p['id'] ?>" onclick="return confirm('حذف شود؟')" class="btn btn-danger btn-sm">حذف</a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    <h5 class="mt-5">اتصال مجوزها به نقش‌ها</h5>
    <?php foreach($roles as $role): ?>
        <div class="border rounded p-2 mb-3">
            <b>نقش: <?= htmlspecialchars($role['name']) ?> (<?= htmlspecialchars($role['label']) ?>)</b>
            <ul class="list-inline mt-2 mini">
                <?php foreach($permissions as $perm):
                    $connected = db_query("SELECT id FROM `$rp_table` WHERE role_id=? AND permission_id=?", [$role['id'], $perm['id']])->fetch();
                    ?>
                    <li class="list-inline-item m-1">
                        <?= htmlspecialchars($perm['name']) ?>
                        <?php if($connected): ?>
                            <a href="?disconnect_role=<?= $perm['id'] ?>&role=<?= $role['id'] ?>" class="btn btn-warning btn-sm">حذف از نقش</a>
                        <?php else: ?>
                            <a href="?connect_role=<?= $perm['id'] ?>&role=<?= $role['id'] ?>" class="btn btn-primary btn-sm">اتصال به نقش</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <h5 class="mt-5">اتصال مجوزها به گروه‌ها با سطوح عملیات</h5>
    <?php foreach($groups as $group): ?>
        <div class="border rounded p-2 mb-3">
            <b>گروه: <?= htmlspecialchars($group['name']) ?> (<?= htmlspecialchars($group['label']) ?>)</b>
            <ul class="list-inline mt-2 mini">
                <?php foreach($permissions as $perm):
                    $connected = db_query("SELECT * FROM `$gp_table` WHERE group_id=? AND permission_id=?", [$group['id'], $perm['id']])->fetch();
                    ?>
                    <li class="list-inline-item m-1">
                        <?= htmlspecialchars($perm['name']) ?>
                        <?php if($connected): ?>
                            <span class="badge bg-info text-dark">S:<?= $connected['can_select'] ?> I:<?= $connected['can_insert'] ?> U:<?= $connected['can_update'] ?> D:<?= $connected['can_delete'] ?></span>
                            <a href="?disconnect_group=<?= $perm['id'] ?>&group=<?= $group['id'] ?>" class="btn btn-warning btn-sm">حذف از گروه</a>
                        <?php else: ?>
                            <form method="get" class="d-inline-flex align-items-center">
                                <input type="hidden" name="connect_group" value="<?= $perm['id'] ?>">
                                <input type="hidden" name="group" value="<?= $group['id'] ?>">
                                <label class="mx-1"><input type="checkbox" name="can_select" checked>S</label>
                                <label class="mx-1"><input type="checkbox" name="can_insert">I</label>
                                <label class="mx-1"><input type="checkbox" name="can_update">U</label>
                                <label class="mx-1"><input type="checkbox" name="can_delete">D</label>
                                <button class="btn btn-primary btn-sm mx-1">اتصال</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
    <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
</div>
</body>
</html>