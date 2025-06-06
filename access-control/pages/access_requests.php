<?php
// ------------------------------
// مدیریت درخواست‌های دسترسی کاربران (توسط مدیر)
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_users'))
    die(lang('access_denied'));

global $config;
$table = $config['tables']['access_request'];
$user_table = $config['tables']['users'];

$msg = '';

// تایید درخواست
if (isset($_GET['approve'])) {
    $rid = intval($_GET['approve']);
    $req = db_query("SELECT * FROM `$table` WHERE id=?", [$rid])->fetch();
    if ($req && $req['status'] === 'pending') {
        db_query("UPDATE `$table` SET status='approved' WHERE id=?", [$rid]);
        // اتصال مجوز به کاربر (مثلا به گروه یا نقش مناسب! بسته به سیاست شما)
        // اینجا فقط وضعیت را تغییر می‌دهیم
        $msg = 'درخواست تایید شد.';
    }
}

// رد درخواست
if (isset($_GET['reject'])) {
    $rid = intval($_GET['reject']);
    $req = db_query("SELECT * FROM `$table` WHERE id=?", [$rid])->fetch();
    if ($req && $req['status'] === 'pending') {
        db_query("UPDATE `$table` SET status='rejected' WHERE id=?", [$rid]);
        $msg = 'درخواست رد شد.';
    }
}

$reqs = db_query("SELECT r.*, u.username FROM `$table` r JOIN `$user_table` u ON r.user_id=u.id ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>درخواست‌های دسترسی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>درخواست‌های دسترسی</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <table class="table table-bordered table-sm">
        <thead><tr>
            <th>#</th>
            <th>کاربر</th>
            <th>درخواست</th>
            <th>وضعیت</th>
            <th>زمان</th>
            <th>عملیات</th>
        </tr></thead>
        <tbody>
        <?php foreach($reqs as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td><?= htmlspecialchars($r['requested_permission']) ?></td>
                <td>
                    <?php
                    if ($r['status'] === 'pending')
                        echo '<span class="badge bg-warning text-dark">در انتظار</span>';
                    elseif ($r['status'] === 'approved')
                        echo '<span class="badge bg-success">تایید شده</span>';
                    else
                        echo '<span class="badge bg-danger">رد شده</span>';
                    ?>
                </td>
                <td><?= htmlspecialchars($r['created_at']) ?></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                        <a href="?approve=<?= $r['id'] ?>" class="btn btn-success btn-sm">تایید</a>
                        <a href="?reject=<?= $r['id'] ?>" class="btn btn-danger btn-sm">رد</a>
                    <?php else: ?>
                        -
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