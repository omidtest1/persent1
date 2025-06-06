<?php
// ------------------------------
// مدیریت دسته‌بندی چندلایه (درختی) مجوزها با parent_id
// ------------------------------
// افزودن، حذف، و نمایش ساختار درختی دسته‌بندی مجوزها

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_permissions'))
    die(lang('access_denied'));

global $config;
$table = $config['tables']['permission_categories'];

$msg = '';

function getCatsTree($parent_id = null, $all = null) {
    global $table;
    if ($all === null) {
        $all = db_query("SELECT * FROM `$table` ORDER BY id")->fetchAll();
    }
    $tree = [];
    foreach ($all as $c) {
        if ($c['parent_id'] == $parent_id) {
            $c['children'] = getCatsTree($c['id'], $all);
            $tree[] = $c;
        }
    }
    return $tree;
}

// افزودن دسته جدید
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $label = trim($_POST['label']);
    $parent_id = $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
    if ($name) {
        db_query("INSERT IGNORE INTO `$table` (name,label,parent_id) VALUES (?,?,?)", [$name, $label, $parent_id]);
        $msg = 'دسته‌بندی جدید افزوده شد.';
    }
}

// حذف دسته
if (isset($_GET['del'])) {
    $cid = intval($_GET['del']);
    if ($cid) {
        db_query("DELETE FROM `$table` WHERE id=?", [$cid]);
        // توجه: اگر دسته والد حذف شود، فرزندان orphan می‌شوند
        $msg = 'دسته حذف شد.';
    }
}

$cats_tree = getCatsTree();
$all_cats = db_query("SELECT * FROM `$table`")->fetchAll();

function printCatTree($tree, $level = 0) {
    foreach ($tree as $c) {
        ?>
        <li>
            <span style="margin-right:<?= $level*12 ?>px"><b><?= htmlspecialchars($c['name']) ?></b>
                <?php if ($c['label']): ?> <span class="text-muted"><?= htmlspecialchars($c['label']) ?></span><?php endif;?>
            </span>
            <a href="?del=<?= $c['id'] ?>" onclick="return confirm('حذف شود؟')" class="btn btn-danger btn-sm">حذف دسته</a>
            <?php if(!empty($c['children'])): ?>
                <ul>
                    <?php printCatTree($c['children'], $level+1); ?>
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
    <title>دسته‌بندی چندلایه مجوزها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>مدیریت دسته‌بندی چندلایه (درختی) مجوزها</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <input type="hidden" name="add" value="1">
        <div class="col">
            <input type="text" name="name" class="form-control" placeholder="نام دسته" required>
        </div>
        <div class="col">
            <input type="text" name="label" class="form-control" placeholder="عنوان فارسی">
        </div>
        <div class="col">
            <select name="parent_id" class="form-select">
                <option value="">بدون والد (ریشه)</option>
                <?php foreach($all_cats as $ac): ?>
                    <option value="<?= $ac['id'] ?>"><?= htmlspecialchars($ac['label'] ?: $ac['name']) ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-success">افزودن دسته</button>
        </div>
    </form>
    <ul>
        <?php printCatTree($cats_tree); ?>
    </ul>
    <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
</div>
</body>
</html>