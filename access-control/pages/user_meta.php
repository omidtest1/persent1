<?php
// ------------------------------
// مدیریت متای کاربران (اطلاعات جانبی کاربران)
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';
require_once __DIR__.'/../user_meta.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_users'))
    die(lang('access_denied'));

global $config;
$users = db_query("SELECT * FROM `".$config['tables']['users']."`")->fetchAll();

$msg = '';
// ثبت یا ویرایش متا
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = intval($_POST['user_id']);
    $key = trim($_POST['meta_key']);
    $val = trim($_POST['meta_value']);
    set_user_meta($uid, $key, $val);
    $msg = 'متا ذخیره شد.';
}

?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>متای کاربران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>مدیریت متای کاربران</h4>
    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <div class="col">
            <select name="user_id" class="form-select" required>
                <option value="">کاربر</option>
                <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col">
            <input type="text" name="meta_key" class="form-control" placeholder="کلید" required>
        </div>
        <div class="col">
            <input type="text" name="meta_value" class="form-control" placeholder="مقدار" required>
        </div>
        <div class="col-auto">
            <button class="btn btn-success">ذخیره</button>
        </div>
    </form>
    <table class="table table-bordered table-sm">
        <thead><tr>
            <th>کاربر</th><th>کلید</th><th>مقدار</th>
        </tr></thead>
        <tbody>
        <?php
        foreach($users as $u):
            $metas = db_query("SELECT * FROM user_meta WHERE user_id=?", [$u['id']])->fetchAll();
            foreach($metas as $m):
                ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($m['meta_key']) ?></td>
                    <td><?= htmlspecialchars($m['meta_value']) ?></td>
                </tr>
            <?php endforeach;
        endforeach;?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
</div>
</body>
</html>