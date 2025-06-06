<?php
// ------------------------------
// صفحه مشاهده لاگ‌های سیستم (admin)
// امکانات: مشاهده لیست لاگ‌ها، جستجوی تعداد دلخواه، بازگشت به داشبورد و منوی دسترسی سریع
// ------------------------------

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';
require_once __DIR__.'/../audit_log.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'view_logs')) {
    die(lang('access_denied'));
}

// تعداد لاگ‌های قابل نمایش (پیش‌فرض 200، فقط عدد صحیح و امن)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;
if ($limit < 1 || $limit > 1000) $limit = 200;

function get_audit_logs_safe($limit) {
    global $config;
    $table = $config['tables']['audit_log'];
    $db = ac_get_db();
    $stmt = $db->prepare("SELECT * FROM `$table` ORDER BY id DESC LIMIT :lim");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

$logs = get_audit_logs_safe($limit);

// تعریف منوی کناری بر اساس مجوز کاربر
$user = current_user();
function can($permission) { return user_has_permission(ac_user_id(), $permission);}
$menus = [
    ['title'=>'داشبورد', 'url'=>'dashboard.php', 'perm'=>'view_dashboard'],
    ['title'=>'مدیریت کاربران', 'url'=>'users.php', 'perm'=>'manage_users'],
    ['title'=>'مدیریت نقش‌ها', 'url'=>'roles.php', 'perm'=>'manage_roles'],
    ['title'=>'مدیریت مجوزها', 'url'=>'permissions.php', 'perm'=>'manage_permissions'],
    ['title'=>'مدیریت گروه‌ها', 'url'=>'groups.php', 'perm'=>'manage_users'],
    ['title'=>'درخواست‌های دسترسی', 'url'=>'access_requests.php', 'perm'=>'manage_users'],
    ['title'=>'درخواست دسترسی جدید', 'url'=>'request_access.php', 'perm'=>'view_dashboard'],
    ['title'=>'متای کاربران', 'url'=>'user_meta.php', 'perm'=>'manage_users'],
    ['title'=>'لاگ سیستم', 'url'=>'logs.php', 'perm'=>'view_logs'],
    ['title'=>'ورود دو مرحله‌ای', 'url'=>'2fa.php', 'perm'=>'view_dashboard'],
    ['title'=>'API سیستم', 'url'=>'api.php', 'perm'=>'view_dashboard'],
];
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>لاگ‌های سیستم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fc;}
        .panel-box { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 3px 18px #0002; padding: 38px 30px;}
        .sidebar-menu { background: #f5f7fa; border-radius: 10px; min-height: 450px;}
        .sidebar-menu ul { list-style: none; padding: 0;}
        .sidebar-menu li { margin-bottom: 14px;}
        .sidebar-menu a { display: block; padding: 10px 16px; border-radius: 7px; color: #333; text-decoration: none; transition: background .2s;}
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #1976d2; color: #fff;}
        .user-box { background: #e3eaf5; border-radius: 8px; padding: 14px 10px; margin-bottom: 20px;}
    </style>
</head>
<body>
<div class="panel-box row">
    <!-- منوی کناری سمت راست -->
    <div class="col-md-3 sidebar-menu">
        <div class="user-box text-center mb-3">
            <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
            <div class="text-muted">نقش: <?= htmlspecialchars($user['role']) ?></div>
        </div>
        <ul>
            <?php foreach ($menus as $m): if (can($m['perm'])): ?>
                <li><a href="<?= htmlspecialchars($m['url']) ?>"<?= $m['url']=='logs.php'?' class="active"':'' ?>><?= htmlspecialchars($m['title']) ?></a></li>
            <?php endif; endforeach;?>
            <li class="mt-4"><a href="logout.php" class="text-danger">خروج</a></li>
        </ul>
    </div>
    <!-- بخش اصلی نمایش لاگ‌ها -->
    <div class="col-md-9">
        <h4 class="mb-3">لاگ‌های سیستم</h4>
        <!-- فرم انتخاب تعداد لاگ -->
        <form method="get" class="mb-3 row g-2">
            <div class="col-auto">
                <input name="limit" type="number" min="1" max="1000" value="<?= $limit ?>" class="form-control" style="width:100px" />
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary">نمایش</button>
            </div>
            <div class="col align-self-center text-muted">
                نمایش آخرین <b><?= $limit ?></b> رخداد سیستم
            </div>
        </form>
        <!-- جدول لاگ -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>عملیات</th>
                        <th>توضیح</th>
                        <th>زمان</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td>
                            <?php
                            if (!empty($log['user_id'])) {
                                $u = ac_get_db()->prepare("SELECT username FROM users WHERE id=?");
                                $u->execute([$log['user_id']]);
                                $u = $u->fetch();
                                echo htmlspecialchars($u ? $u['username'] : 'سیستم');
                            } else {
                                echo 'سیستم';
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['detail']) ?></td>
                        <td><?= htmlspecialchars($log['log_time']) ?></td>
                    </tr>
                <?php endforeach; if(!$logs): ?>
                    <tr>
                        <td colspan="5" class="text-center">لاگی یافت نشد.</td>
                    </tr>
                <?php endif;?>
                </tbody>
            </table>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
    </div>
</div>
</body>
</html>