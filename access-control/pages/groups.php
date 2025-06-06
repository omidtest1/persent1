<?php
// ------------------------------
// مدیریت گروه‌های چندلایه (درختی) کاربران با parent_id
// ------------------------------
// افزودن، حذف، و مدیریت ساختار گروه درختی + عضویت کاربران

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_users'))
    die(lang('access_denied'));

global $config;
$table = $config['tables']['groups'];
$user_table = $config['tables']['users'];
$gu_table = $config['tables']['group_users'];

$msg = '';

function getGroupsTree($parent_id = null, $all = null) {
    global $table;
    if ($all === null) {
        $all = db_query("SELECT * FROM `$table` ORDER BY id")->fetchAll();
    }
    $tree = [];
    foreach ($all as $g) {
        if ($g['parent_id'] == $parent_id) {
            $g['children'] = getGroupsTree($g['id'], $all);
            $tree[] = $g;
        }
    }
    return $tree;
}

// افزودن گروه جدید
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $label = trim($_POST['label']);
    $parent_id = $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
    if ($name) {
        db_query("INSERT IGNORE INTO `$table` (name,label,parent_id) VALUES (?,?,?)", [$name, $label, $parent_id]);
        $msg = 'گروه جدید افزوده شد.';
    }
}

// حذف گروه
if (isset($_GET['del'])) {
    $gid = intval($_GET['del']);
    if ($gid) {
        db_query("DELETE FROM `$table` WHERE id=?", [$gid]);
        db_query("DELETE FROM `$gu_table` WHERE group_id=?", [$gid]);
        // توجه: اگر گروه والد حذف شود، فرزندان orphan می‌شوند
        $msg = 'گروه حذف شد.';
    }
}

// افزودن کاربر به گروه
if (isset($_GET['add_user'])) {
    $gid = intval($_GET['add_user']);
    $uid = intval($_GET['user']);
    db_query("INSERT IGNORE INTO `$gu_table` (group_id, user_id) VALUES (?,?)", [$gid, $uid]);
    $msg = 'کاربر به گروه افزوده شد.';
}

// حذف کاربر از گروه
if (isset($_GET['del_user'])) {
    $gid = intval($_GET['del_user']);
    $uid = intval($_GET['user']);
    db_query("DELETE FROM `$gu_table` WHERE group_id=? AND user_id=?", [$gid, $uid]);
    $msg = 'کاربر از گروه حذف شد.';
}

$users = db_query("SELECT * FROM `$user_table`")->fetchAll();
$groups_tree = getGroupsTree();
$all_groups = db_query("SELECT * FROM `$table`")->fetchAll();

function printGroupTree($tree, $users, $gu_table, $user_table, $level = 0) {
    foreach ($tree as $g) {
        $members = db_query("SELECT u.id, u.username FROM `$gu_table` gu JOIN `$user_table` u ON gu.user_id=u.id WHERE gu.group_id=?", [$g['id']])->fetchAll();
        ?>
        <li>
            <span style="margin-right:<?= $level*12 ?>px"><b><?= htmlspecialchars($g['name']) ?></b>
                <?php if ($g['label']): ?> <span class="text-muted"><?= htmlspecialchars($g['label']) ?></span><?php endif;?>
            </span>
            <div class="d-inline">
                <a href="?del=<?= $g['id'] ?>" onclick="return confirm('حذف شود؟')" class="btn btn-danger btn-sm">حذف گروه</a>
                <form method="get" class="d-inline">
                    <input type="hidden" name="add_user" value="<?= $g['id'] ?>">
                    <select name="user" class="form-select form-select-sm d-inline" style="width:auto;display:inline-block;">
                        <?php foreach($users as $u): ?>
                            <?php if (!in_array($u['id'], array_column($members, 'id'))): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach;?>
                    </select>
                    <button class="btn btn-outline-primary btn-sm">افزودن کاربر</button>
                </form>
            </div>
            <!-- اعضا -->
            <div>
                <?php foreach($members as $m): ?>
                    <span class="badge bg-secondary">
                        <?= htmlspecialchars($m['username']) ?>
                        <a href="?del_user=<?= $g['id'] ?>&user=<?= $m['id'] ?>" class="text-danger ms-2" title="حذف" onclick="return confirm('حذف این کاربر از گروه؟')">×</a>
                    </span>
                <?php endforeach;?>
            </div>
            <?php if(!empty($g['children'])): ?>
                <ul>
                    <?php printGroupTree($g['children'], $users, $gu_table, $user_table, $level+1); ?>
                </ul>
            <?php endif;?>
        </li>
    <?php }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت گروه‌های چندلایه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>مدیریت گروه‌های چندلایه (درختی)</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <input type="hidden" name="add" value="1">
        <div class="col">
            <input type="text" name="name" class="form-control" placeholder="نام گروه (انگلیسی)" required>
        </div>
        <div class="col">
            <input type="text" name="label" class="form-control" placeholder="عنوان فارسی">
        </div>
        <div class="col">
            <select name="parent_id" class="form-select">
                <option value="">بدون والد (ریشه)</option>
                <?php foreach($all_groups as $ag): ?>
                    <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['name']) ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-success">افزودن گروه</button>
        </div>
    </form>
    <ul>
        <?php printGroupTree($groups_tree, $users, $gu_table, $user_table); ?>
    </ul>
    <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
</div>
</body>
</html>