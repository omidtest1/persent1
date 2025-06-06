<?php
// ------------------------------
// داشبورد هوشمند ماژول مدیریت دسترسی
// ------------------------------
// این صفحه پس از ورود نمایش داده می‌شود و منو و راهنمای کاربر را فراهم می‌کند.

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';
require_once __DIR__.'/../user_meta.php';
require_once __DIR__.'/../audit_log.php';

// بررسی ورود کاربر
if (!ac_user_id()) header('Location: login.php');

$user = current_user();
if (!$user) header('Location: login.php');

// تابع بررسی مجوز برای نمایش منو
function can($permission) {
    return user_has_permission(ac_user_id(), $permission);
}

// منوهای ماژول بر اساس مجوز
$menus = [
    ['title' => 'مدیریت کاربران',      'url' => 'users.php',             'perm' => 'manage_users'],
    ['title' => 'مدیریت نقش‌ها',      'url' => 'roles.php',             'perm' => 'manage_roles'],
    ['title' => 'مدیریت مجوزها',      'url' => 'permissions.php',        'perm' => 'manage_permissions'],
    ['title' => 'دسته‌بندی مجوزها',   'url' => 'permission_categories.php','perm' => 'manage_permissions'],
    ['title' => 'مدیریت گروه‌ها',     'url' => 'groups.php',             'perm' => 'manage_users'],
    ['title' => 'درخواست‌های دسترسی', 'url' => 'access_requests.php',    'perm' => 'manage_users'],
    ['title' => 'درخواست دسترسی جدید','url' => 'request_access.php',     'perm' => 'view_dashboard'],
    ['title' => 'متای کاربران',       'url' => 'user_meta.php',          'perm' => 'manage_users'],
    ['title' => 'لاگ سیستم',          'url' => 'logs.php',               'perm' => 'view_logs'],
    ['title' => 'ورود دو مرحله‌ای',   'url' => '2fa.php',                'perm' => 'view_dashboard'],
    ['title' => 'API سیستم',          'url' => 'api.php',                'perm' => 'view_dashboard'],
];

$integration_guide = <<<HTML
<h5 class="mt-4 mb-2 text-primary">راهنمای استفاده از ماژول در صفحات پروژه شما</h5>
<ul>
    <li>در ابتدای هر صفحه‌ای که باید کنترل دسترسی شود، این خط را اضافه کنید:
        <pre class="bg-light border rounded p-2 mb-2">require_once __DIR__.'/access-control/access-init.php';</pre>
    </li>
    <li>
        برای محافظت صفحه بر اساس مجوز:
        <pre class="bg-light border rounded p-2 mb-2">if (!user_has_permission(ac_user_id(), 'نام_مجوز')) die('دسترسی غیرمجاز');</pre>
    </li>
    <li>برای مثال، محافظت از صفحه محصولات فقط برای مدیران:
        <pre class="bg-light border rounded p-2 mb-2">
require_once __DIR__.'/access-control/access-init.php';
if (!user_has_permission(ac_user_id(), 'manage_products')) die('دسترسی غیرمجاز');
        </pre>
    </li>
    <li>
        برای تعریف مجوز و نقش جدید:
        <ol>
            <li>از منوی سمت راست، "مدیریت نقش‌ها" و "مدیریت مجوزها" را انتخاب کنید.</li>
            <li>مجوزهای لازم را بسازید و به نقش‌ها متصل کنید.</li>
            <li>در بخش "مدیریت کاربران"، نقش مناسب را به کاربران تخصیص دهید.</li>
        </ol>
    </li>
    <li>برای افزودن دسترسی به صفحات پروژه خود، کافیست مجوز بسازید و همان نام را در کنترل دسترسی صفحه استفاده کنید.</li>
</ul>
HTML;

$dev_guide = <<<HTML
<h5 class="mt-4 mb-2 text-primary">نکات توسعه و قابلیت‌های هوشمند ماژول</h5>
<ul>
    <li>امکان افزودن هر نوع فیلد دلخواه به کاربران از بخش "متای کاربران"</li>
    <li>امکان ثبت و مشاهده لاگ تمام تغییرات و رخدادها در ماژول</li>
    <li>امکان اتصال به API برای دریافت لیست کاربران و نقش‌ها جهت اپلیکیشن موبایل یا سایر سیستم‌ها</li>
    <li>ورود دو مرحله‌ای (2FA) برای ارتقاء امنیت کاربران</li>
    <li>امکان ثبت درخواست دسترسی جدید توسط کاربران و تایید/رد توسط مدیران</li>
    <li>تمامی بخش‌ها به راحتی قابل توسعه و شخصی‌سازی هستند.</li>
</ul>
HTML;
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>داشبورد مدیریت دسترسی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f3f6fa;}
        .dash-box { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 3px 18px #0002; padding: 42px 36px;}
        .sidebar-menu { background: #f5f7fa; border-radius: 10px; min-height: 450px;}
        .sidebar-menu ul { list-style: none; padding: 0;}
        .sidebar-menu li { margin-bottom: 14px;}
        .sidebar-menu a { display: block; padding: 10px 16px; border-radius: 7px; color: #333; text-decoration: none; transition: background .2s;}
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #1976d2; color: #fff;}
        .user-box { background: #e3eaf5; border-radius: 8px; padding: 14px 10px; margin-bottom: 20px;}
    </style>
</head>
<body>
<div class="dash-box row">
    <!-- منوی سمت راست -->
    <div class="col-md-3 sidebar-menu">
        <div class="user-box text-center mb-3">
            <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
            <div class="text-muted">نقش: <?= htmlspecialchars($user['role']) ?></div>
        </div>
        <ul>
            <?php foreach ($menus as $m): if (can($m['perm'])): ?>
                <li><a href="<?= htmlspecialchars($m['url']) ?>"><?= htmlspecialchars($m['title']) ?></a></li>
            <?php endif; endforeach;?>
            <li class="mt-4"><a href="logout.php" class="text-danger">خروج</a></li>
        </ul>
    </div>
    <!-- محتوای داشبورد -->
    <div class="col-md-9">
        <h2 class="mb-3">داشبورد مدیریت دسترسی</h2>
        <div class="alert alert-info mb-3">
            به سامانه مدیریت نقش، مجوز و دسترسی خوش آمدید.
        </div>

        <h5 class="mt-3 mb-1">عملکرد سریع:</h5>
        <ul>
            <?php if (can('manage_users')): ?>
                <li>برای افزودن یا ویرایش کاربران <b><a href="users.php">اینجا کلیک کنید</a></b></li>
            <?php endif;?>
            <?php if (can('manage_roles')): ?>
                <li>برای افزودن یا ویرایش نقش‌ها <b><a href="roles.php">اینجا کلیک کنید</a></b></li>
            <?php endif;?>
            <?php if (can('manage_permissions')): ?>
                <li>برای افزودن یا ویرایش مجوزها <b><a href="permissions.php">اینجا کلیک کنید</a></b></li>
            <?php endif;?>
            <?php if (can('view_logs')): ?>
                <li>برای مشاهده لاگ عملیات <b><a href="logs.php">اینجا کلیک کنید</a></b></li>
            <?php endif;?>
            <li>برای فعالسازی یا بررسی 2FA <b><a href="2fa.php">اینجا کلیک کنید</a></b></li>
        </ul>

        <!-- راهنمای استفاده و توسعه ماژول -->
        <?= $integration_guide ?>
        <?= $dev_guide ?>

        <div class="alert alert-secondary mt-4">
            <b>توجه:</b> برای هر بخش پروژه خود که نیاز به کنترل دسترسی دارد، کافیست یک مجوز جدید بسازید و آن را به نقش دلخواه متصل نمایید. سپس در ابتدای فایل همان صفحه دو خط زیر را قرار دهید:
            <pre class="bg-light border rounded p-2 mb-2">
require_once __DIR__.'/access-control/access-init.php';
if (!user_has_permission(ac_user_id(), 'your_permission_name')) die('دسترسی غیرمجاز');
            </pre>
        </div>
    </div>
</div>
</body>
</html>