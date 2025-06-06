<?php
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_permissions'))
    die(lang('access_denied'));

global $config;

$user_tbl   = $config['tables']['users'];
$group_tbl  = $config['tables']['groups'];
$gu_tbl     = $config['tables']['group_users'];
$perm_tbl   = $config['tables']['permissions'];
$cat_tbl    = $config['tables']['permission_categories'];
$gp_tbl     = $config['tables']['group_permissions'];
$rp_tbl     = $config['tables']['role_permissions'];

// --- دریافت کاربران و گروه‌های کاربری ---
$users = db_query("SELECT * FROM `$user_tbl` ORDER BY username")->fetchAll();
$user_groups = db_query("SELECT g.*, gu.user_id FROM `$group_tbl` g LEFT JOIN `$gu_tbl` gu ON g.id=gu.group_id")->fetchAll();
$group_members = [];
foreach ($user_groups as $g) {
    if ($g['user_id']) $group_members[$g['id']][] = $g['user_id'];
}
$groups = db_query("SELECT * FROM `$group_tbl`")->fetchAll();

// --- دریافت گروه‌های مجوز و مجوزها ---
$cats = db_query("SELECT * FROM `$cat_tbl` ORDER BY id")->fetchAll();
$permissions = db_query("SELECT * FROM `$perm_tbl`")->fetchAll();
$cat_perms = [];
foreach ($permissions as $p) {
    $cat_perms[$p['category_id']][] = $p;
}

// مقدار انتخابی از فرم
$selected_user    = $_GET['user'] ?? '';
$selected_group   = $_GET['usergroup'] ?? '';
$selected_perm    = $_GET['perm'] ?? '';
$selected_cat     = $_GET['permcat'] ?? '';
$action           = $_POST['action'] ?? '';
$msg = '';
$warns = [];
$success = false;

// --- عملیات اعطا یا گرفتن مجوز ---
if ($_SERVER['REQUEST_METHOD']==='POST' && $action && ($selected_user||$selected_group) && ($selected_perm||$selected_cat)) {
    // انتخاب هدف: کاربر یا گروه کاربری
    $target_type = $selected_user ? 'user' : 'group'; // فقط یکی انتخاب می‌شود
    $target_id   = $selected_user ?: $selected_group;

    // انتخاب مجوز(ها)
    $perm_ids = [];
    if ($selected_perm) {
        $perm_ids[] = intval($selected_perm);
    } elseif($selected_cat) {
        foreach($cat_perms[$selected_cat]??[] as $p) $perm_ids[] = $p['id'];
    }

    // اعطا یا گرفتن مجوز برای کاربر یا گروه
    if ($target_type==='user') {
        foreach ($perm_ids as $pid) {
            $perm = db_query("SELECT * FROM `$perm_tbl` WHERE id=?", [$pid])->fetch();
            if (!$perm) continue;
            // گرفتن نقش فعلی کاربر
            $user = db_query("SELECT * FROM `$user_tbl` WHERE id=?", [$target_id])->fetch();
            if (!$user) continue;
            $role_id = db_query("SELECT id FROM `".$config['tables']['roles']."` WHERE name=?", [$user['role']])->fetchColumn();
            if ($action==='grant') {
                // اعطای مجوز به نقش اگر نداشت
                $has = db_query("SELECT id FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $pid])->fetch();
                if (!$has) db_query("INSERT INTO `$rp_tbl` (role_id,permission_id) VALUES (?,?)", [$role_id, $pid]);
            } else {
                db_query("DELETE FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $pid]);
            }
        }
        $msg = ($action==='grant'?'مجوز اعطا شد.':'مجوز حذف شد.');
        $success = true;
    } else { // group
        foreach ($perm_ids as $pid) {
            if ($action==='grant') {
                db_query("INSERT IGNORE INTO `$gp_tbl` (group_id,permission_id) VALUES (?,?)", [$target_id, $pid]);
            } else {
                db_query("DELETE FROM `$gp_tbl` WHERE group_id=? AND permission_id=?", [$target_id, $pid]);
            }
        }
        $msg = ($action==='grant'?'مجوز به گروه اعطا شد.':'مجوز از گروه حذف شد.');
        $success = true;
    }
}

// --- بررسی وضعیت فعلی دسترسی ---
$status_msg = '';
if (($selected_user||$selected_group) && ($selected_perm||$selected_cat)) {
    // محاسبه لیست مجوزهای انتخاب شده
    $perm_ids = [];
    $perm_names = [];
    if ($selected_perm) {
        $perm = db_query("SELECT * FROM `$perm_tbl` WHERE id=?", [$selected_perm])->fetch();
        if ($perm) {
            $perm_ids[] = $perm['id'];
            $perm_names[] = $perm['label'] ?: $perm['name'];
        }
    } elseif($selected_cat) {
        foreach($cat_perms[$selected_cat]??[] as $p) {
            $perm_ids[] = $p['id'];
            $perm_names[] = $p['label'] ?: $p['name'];
        }
    }

    // بررسی کاربر
    if ($selected_user) {
        $user = db_query("SELECT * FROM `$user_tbl` WHERE id=?", [$selected_user])->fetch();
        $user_name = $user['username'];
        $not_have = [];
        foreach ($perm_ids as $i=>$pid) {
            if (!user_has_permission($selected_user, $permissions[array_search($pid, array_column($permissions,'id'))]['name']))
                $not_have[] = $perm_names[$i];
        }
        if (!$not_have)
            $status_msg = "<span class='text-success'>کاربر <b>$user_name</b> به این مجوز(ها) دسترسی دارد.</span>";
        else
            $status_msg = "<span class='text-danger'>کاربر <b>$user_name</b> به این مجوز(ها) دسترسی ندارد: <b>".implode(', ',$not_have)."</b></span>";
    }
    // بررسی گروه کاربری
    if ($selected_group) {
        $group = db_query("SELECT * FROM `$group_tbl` WHERE id=?", [$selected_group])->fetch();
        $gname = $group['name'];
        $no = [];
        foreach ($perm_ids as $i=>$pid) {
            $has = db_query("SELECT id FROM `$gp_tbl` WHERE group_id=? AND permission_id=?", [$selected_group, $pid])->fetch();
            if (!$has) $no[] = $perm_names[$i];
        }
        if (!$no)
            $status_msg = "<span class='text-success'>گروه <b>$gname</b> به این مجوز(ها) دسترسی دارد.</span>";
        else
            $status_msg = "<span class='text-danger'>گروه <b>$gname</b> به این مجوز(ها) دسترسی ندارد: <b>".implode(', ',$no)."</b></span>";
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
        .tree ul { list-style: none; margin:0; padding-right:20px;}
        .tree li { margin-bottom: 7px;}
        .tree label { cursor:pointer; }
        .selected { background: #d0e7fd !important; }
    </style>
    <script>
        function selectOnly(group, id) {
            document.querySelectorAll('input[name="'+group+'"]').forEach(el=>el.checked=false);
            document.getElementById(id).checked=true;
        }
    </script>
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-4">اعطای مجوز به کاربر یا گروه خاص</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($status_msg): ?><div class="alert alert-<?= $success?'success':'warning' ?>"><?= $status_msg ?></div><?php endif; ?>
    <div class="row">
        <!-- ستون راست: کاربران و گروه‌ها -->
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">انتخاب کاربر یا گروه کاربران</div>
                <div class="card-body tree">
                    <b>کاربران</b>
                    <ul>
                        <?php foreach($users as $u): ?>
                            <li>
                                <label>
                                    <input type="radio" name="user" value="<?= $u['id'] ?>" id="user<?= $u['id'] ?>" <?= $selected_user==$u['id']?'checked':'' ?> onclick="window.location='?user=<?= $u['id'] ?>'">
                                    <?= htmlspecialchars($u['username']) ?>
                                </label>
                                <?php
                                    $mygroups = db_query("SELECT g.* FROM `$group_tbl` g JOIN `$gu_tbl` gu ON g.id=gu.group_id WHERE gu.user_id=?", [$u['id']])->fetchAll();
                                    if ($mygroups): ?>
                                    <ul>
                                        <?php foreach($mygroups as $g): ?>
                                            <li><span class="text-muted small">عضو گروه: <?= htmlspecialchars($g['name']) ?></span></li>
                                        <?php endforeach;?>
                                    </ul>
                                <?php endif;?>
                            </li>
                        <?php endforeach;?>
                    </ul>
                    <b class="mt-3">گروه‌های کاربران</b>
                    <ul>
                        <?php foreach($groups as $g): ?>
                            <li>
                                <label>
                                    <input type="radio" name="usergroup" value="<?= $g['id'] ?>" id="group<?= $g['id'] ?>" <?= $selected_group==$g['id']?'checked':'' ?> onclick="window.location='?usergroup=<?= $g['id'] ?>'">
                                    <?= htmlspecialchars($g['name']) ?>
                                </label>
                                <?php
                                $members = db_query("SELECT u.username FROM `$gu_tbl` gu JOIN `$user_tbl` u ON gu.user_id=u.id WHERE gu.group_id=?", [$g['id']])->fetchAll();
                                if ($members): ?>
                                    <ul>
                                        <?php foreach($members as $m): ?>
                                            <li><span class="text-muted small"><?= htmlspecialchars($m['username']) ?></span></li>
                                        <?php endforeach;?>
                                    </ul>
                                <?php endif;?>
                            </li>
                        <?php endforeach;?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- ستون چپ: گروه‌های مجوز و مجوزها -->
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">انتخاب مجوز یا گروه مجوز</div>
                <div class="card-body tree">
                    <b>گروه‌های مجوز</b>
                    <ul>
                        <?php foreach($cats as $c): ?>
                            <li>
                                <label>
                                    <input type="radio" name="permcat" value="<?= $c['id'] ?>" id="cat<?= $c['id'] ?>" <?= $selected_cat==$c['id']?'checked':'' ?> onclick="window.location='?<?= $selected_user?'user='.$selected_user:($selected_group?'usergroup='.$selected_group:'') ?>&permcat=<?= $c['id'] ?>'">
                                    <?= htmlspecialchars($c['label'] ?: $c['name']) ?>
                                </label>
                                <?php if(!empty($cat_perms[$c['id']])): ?>
                                    <ul>
                                        <?php foreach($cat_perms[$c['id']] as $p): ?>
                                            <li>
                                                <label>
                                                    <input type="radio" name="perm" value="<?= $p['id'] ?>" id="perm<?= $p['id'] ?>" <?= $selected_perm==$p['id']?'checked':'' ?> onclick="window.location='?<?= $selected_user?'user='.$selected_user:($selected_group?'usergroup='.$selected_group:'') ?>&perm=<?= $p['id'] ?>'">
                                                    <?= htmlspecialchars($p['label'] ?: $p['name']) ?>
                                                </label>
                                            </li>
                                        <?php endforeach;?>
                                    </ul>
                                <?php endif;?>
                            </li>
                        <?php endforeach;?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- بخش عملیات مرکزی -->
        <div class="col-md-2 d-flex flex-column align-items-center">
            <form method="post" class="mb-3 w-100">
                <input type="hidden" name="action" value="grant">
                <button class="btn btn-success w-100 mb-2" <?= (!$selected_user && !$selected_group) || (!$selected_perm && !$selected_cat) ? 'disabled' : '' ?>>اعطای مجوز</button>
            </form>
            <form method="post" class="mb-3 w-100">
                <input type="hidden" name="action" value="revoke">
                <button class="btn btn-danger w-100" <?= (!$selected_user && !$selected_group) || (!$selected_perm && !$selected_cat) ? 'disabled' : '' ?>>گرفتن مجوز</button>
            </form>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-4">بازگشت به داشبورد</a>
</div>
</body>
</html>