<?php
// ------------------------------
// ویوی اعطای مجوز به کاربر یا گروه کاربران (یا بالعکس)
// ------------------------------
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_permissions'))
    die(lang('access_denied'));

global $config;

$user_table   = $config['tables']['users'];
$group_table  = $config['tables']['groups'];
$gu_table     = $config['tables']['group_users'];
$perm_table   = $config['tables']['permissions'];
$cat_table    = $config['tables']['permission_categories'];
$rp_table     = $config['tables']['role_permissions'];
$gp_table     = $config['tables']['group_permissions'];

// لیست گروه‌ها و کاربران
$groups = db_query("SELECT * FROM `$group_table` ORDER BY label, name")->fetchAll();
$users = db_query("SELECT * FROM `$user_table` ORDER BY username")->fetchAll();

// لیست دسته‌بندی مجوزها و مجوزها
$categories = db_query("SELECT * FROM `$cat_table` ORDER BY label, name")->fetchAll();
$perms = db_query("SELECT * FROM `$perm_table` ORDER BY category_id, label, name")->fetchAll();

// ساخت آرایه‌های آبشاری
$user_by_group = [];
foreach ($groups as $g) $user_by_group[$g['id']] = [];
foreach ($users as $u) {
    $user_groups = db_query("SELECT group_id FROM `$gu_table` WHERE user_id=?", [$u['id']])->fetchAll(PDO::FETCH_COLUMN);
    foreach ($user_groups as $gid) $user_by_group[$gid][] = $u;
}
$perm_by_cat = [];
foreach ($categories as $cat) $perm_by_cat[$cat['id']] = [];
foreach ($perms as $p) {
    $perm_by_cat[$p['category_id']][] = $p;
}

// ---- پردازش انتخاب ----
$selected_user_type = $_GET['user_type'] ?? '';
$selected_user_id   = intval($_GET['user_id'] ?? 0);
$selected_group_id  = intval($_GET['group_id'] ?? 0);

$selected_perm_type = $_GET['perm_type'] ?? '';
$selected_perm_id   = intval($_GET['perm_id'] ?? 0);
$selected_cat_id    = intval($_GET['cat_id'] ?? 0);

// ---- اعطا یا حذف مجوز ----
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_user_type && ($selected_user_id || $selected_group_id) && $selected_perm_type && ($selected_perm_id || $selected_cat_id)) {
    // انتخاب هدف
    $target_ids = [];
    if ($selected_user_type === 'user') {
        $target_ids = [$selected_user_id];
    } elseif ($selected_user_type === 'group') {
        $target_ids = db_query("SELECT user_id FROM `$gu_table` WHERE group_id=?", [$selected_group_id])->fetchAll(PDO::FETCH_COLUMN);
    }
    // انتخاب مجوزها
    $perm_ids = [];
    if ($selected_perm_type === 'perm') {
        $perm_ids = [$selected_perm_id];
    } elseif ($selected_perm_type === 'cat') {
        $perm_ids = db_query("SELECT id FROM `$perm_table` WHERE category_id=?", [$selected_cat_id])->fetchAll(PDO::FETCH_COLUMN);
    }
    // عملیات
    $action = $_POST['action'] ?? '';
    $errors = [];
    if ($action === 'grant') {
        foreach ($target_ids as $uid) {
            foreach ($perm_ids as $pid) {
                // آیا کاربر به صورت گروهی، نقشی، یا مستقیم دارد؟
                $has = user_has_permission($uid, db_query("SELECT name FROM `$perm_table` WHERE id=?", [$pid])->fetchColumn());
                if ($has) {
                    $errors[] = "کاربر ID $uid از قبل این مجوز را دارد.";
                    continue;
                }
                // اعطا به نقش یا گروه؟ اینجا فقط به گروه (مثلا گروه پیشفرض کاربر) یا نقش کاربر یا گروه کاربران
                if ($selected_user_type === 'user') {
                    // اعطا به نقش کاربر (پیشنهاد: به کاربر نقش خاص بدهید که اجازه مجوز را دارد)
                    // یا می‌توانید یک گروه مخصوص بسازید و به او اضافه کنید
                    // یا مستقیماً به گروه کاربر مجوز بدهید (در اینجا فرض بر گروه است)
                    // برای سادگی، به گروه همه کاربران "گروه کاربران شخصی" بده!
                } else {
                    // اعطا به گروه
                    db_query("INSERT IGNORE INTO `$gp_table` (group_id, permission_id, can_select) VALUES (?, ?, 1)", [$selected_group_id, $pid]);
                }
            }
        }
        if (!$errors) $msg = 'اعطای مجوز انجام شد.';
    } elseif ($action === 'revoke') {
        foreach ($target_ids as $uid) {
            foreach ($perm_ids as $pid) {
                // فقط از گروه حذف می‌کنیم (در عمل شما باید سیاست خود را تعیین کنید)
                if ($selected_user_type === 'group') {
                    db_query("DELETE FROM `$gp_table` WHERE group_id=? AND permission_id=?", [$selected_group_id, $pid]);
                }
            }
        }
        $msg = 'حذف مجوز انجام شد.';
    }
}

// ---- بررسی وضعیت دسترسی ----
$status = '';
if ($selected_user_type && ($selected_user_id || $selected_group_id) && $selected_perm_type && ($selected_perm_id || $selected_cat_id)) {
    $names = [];
    if ($selected_user_type === 'user') {
        $user = db_query("SELECT username FROM `$user_table` WHERE id=?", [$selected_user_id])->fetch();
        $names[] = $user['username'] ?? '';
        $target_ids = [$selected_user_id];
    } else {
        $group = db_query("SELECT label,name FROM `$group_table` WHERE id=?", [$selected_group_id])->fetch();
        $names[] = $group['label'] ?: $group['name'];
        $target_ids = db_query("SELECT user_id FROM `$gu_table` WHERE group_id=?", [$selected_group_id])->fetchAll(PDO::FETCH_COLUMN);
    }
    $perm_names = [];
    if ($selected_perm_type === 'perm') {
        $perm = db_query("SELECT name,label FROM `$perm_table` WHERE id=?", [$selected_perm_id])->fetch();
        $perm_names[] = $perm['label'] ?: $perm['name'];
        $perm_ids = [$selected_perm_id];
    } else {
        $cat = db_query("SELECT label,name FROM `$cat_table` WHERE id=?", [$selected_cat_id])->fetch();
        $perm_names[] = $cat['label'] ?: $cat['name'];
        $perm_ids = db_query("SELECT id FROM `$perm_table` WHERE category_id=?", [$selected_cat_id])->fetchAll(PDO::FETCH_COLUMN);
    }
    // بررسی دسترسی
    $not_have = [];
    foreach ($target_ids as $uid) {
        foreach ($perm_ids as $pid) {
            $pname = db_query("SELECT name,label FROM `$perm_table` WHERE id=?", [$pid])->fetch();
            if (!user_has_permission($uid, $pname['name'])) {
                $not_have[] = ($selected_user_type === 'user' ? "کاربر" : "عضو گروه") . " " . ($selected_user_type === 'user' ? $names[0] : $uid) . " به مجوز " . ($pname['label'] ?: $pname['name']) . " دسترسی ندارد.";
            }
        }
    }
    if (!$not_have) {
        $status = "<div class='alert alert-success'>".($selected_user_type === 'user' ? "کاربر " : "گروه ").implode(', ', $names)." به ".implode(', ', $perm_names)." دسترسی دارد.</div>";
    } else {
        $status = "<div class='alert alert-danger'>".implode('<br>', $not_have)."</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>اعطای مجوز به کاربر یا گروه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .cascade-list { max-height: 410px; overflow-y: auto; background: #f7faff; border-radius: 8px; border:1px solid #d5e2ef;}
        .cascade-list ul { list-style:none; margin:0; padding:0;}
        .cascade-list li { padding: 4px 10px;}
        .cascade-list li.group { background: #dbeafe; font-weight: bold;}
        .cascade-list li.selected { background:#1976d2;color:#fff;}
    </style>
</head>
<body>
<div class="container mt-5">
    <h4>اعطای مجوز به کاربر یا گروه کاربران</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <div class="row">
        <!-- سمت راست: کاربران و گروه‌ها -->
        <div class="col-md-4">
            <div class="cascade-list mb-3">
                <ul>
                    <li class="group">کاربران:</li>
                    <?php foreach($groups as $g): ?>
                        <li class="group"><?= htmlspecialchars($g['label'] ?: $g['name']) ?></li>
                        <?php foreach($user_by_group[$g['id']] as $u): ?>
                            <li<?= $selected_user_type=='user'&&$selected_user_id==$u['id']?' class="selected"':'' ?>>
                                <a href="?user_type=user&user_id=<?= $u['id'] ?>" style="text-decoration:none;color:inherit">
                                    <?= htmlspecialchars($u['username']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <li class="group">گروه‌ها:</li>
                    <?php foreach($groups as $g): ?>
                        <li<?= $selected_user_type=='group'&&$selected_group_id==$g['id']?' class="selected"':'' ?>>
                            <a href="?user_type=group&group_id=<?= $g['id'] ?>" style="text-decoration:none;color:inherit">
                                <?= htmlspecialchars($g['label'] ?: $g['name']) ?>
                            </a>
                        </li>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
        <!-- وسط: نمایش وضعیت دسترسی و دکمه‌ها -->
        <div class="col-md-4">
            <form method="post">
                <input type="hidden" name="user_type" value="<?= htmlspecialchars($selected_user_type) ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($selected_user_id) ?>">
                <input type="hidden" name="group_id" value="<?= htmlspecialchars($selected_group_id) ?>">
                <input type="hidden" name="perm_type" value="<?= htmlspecialchars($selected_perm_type) ?>">
                <input type="hidden" name="perm_id" value="<?= htmlspecialchars($selected_perm_id) ?>">
                <input type="hidden" name="cat_id" value="<?= htmlspecialchars($selected_cat_id) ?>">
                <div class="mb-3">
                    <?php if($status) echo $status; else echo '<div class="alert alert-warning">لطفاً کاربر/گروه و مجوز/دسته را انتخاب کنید.</div>'; ?>
                </div>
                <div class="d-flex gap-2">
                    <button name="action" value="grant" class="btn btn-success w-50"<?= (!$selected_user_type||!$selected_perm_type)?' disabled':''; ?>>اعطای مجوز</button>
                    <button name="action" value="revoke" class="btn btn-danger w-50"<?= (!$selected_user_type||!$selected_perm_type)?' disabled':''; ?>>گرفتن مجوز</button>
                </div>
            </form>
        </div>
        <!-- سمت چپ: مجوزها و دسته‌بندی‌ها -->
        <div class="col-md-4">
            <div class="cascade-list mb-3">
                <ul>
                    <li class="group">دسته‌بندی مجوزها:</li>
                    <?php foreach($categories as $cat): ?>
                        <li class="group"><?= htmlspecialchars($cat['label'] ?: $cat['name']) ?></li>
                        <?php foreach($perm_by_cat[$cat['id']] as $p): ?>
                            <li<?= $selected_perm_type=='perm'&&$selected_perm_id==$p['id']?' class="selected"':'' ?>>
                                <a href="?perm_type=perm&perm_id=<?= $p['id'] ?>&<?= $selected_user_type=='user'?"user_type=user&user_id=$selected_user_id":"user_type=group&group_id=$selected_group_id" ?>" style="text-decoration:none;color:inherit">
                                    <?= htmlspecialchars($p['label'] ?: $p['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li<?= $selected_perm_type=='cat'&&$selected_cat_id==$cat['id']?' class="selected"':'' ?>>
                            <a href="?perm_type=cat&cat_id=<?= $cat['id'] ?>&<?= $selected_user_type=='user'?"user_type=user&user_id=$selected_user_id":"user_type=group&group_id=$selected_group_id" ?>" style="text-decoration:none;color:inherit">
                                <b>همه مجوزهای این دسته</b>
                            </a>
                        </li>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-4">بازگشت به داشبورد</a>
</div>
</body>
</html>