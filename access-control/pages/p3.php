<?php
require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_permissions'))
    die(lang('access_denied'));

global $config;
$user_table = $config['tables']['users'];
$group_table = $config['tables']['groups'];
$gu_table = $config['tables']['group_users'];
$perm_table = $config['tables']['permissions'];
$cat_table = $config['tables']['permission_categories'];
$role_perm_table = $config['tables']['role_permissions'];
$group_perm_table = $config['tables']['group_permissions'];

// دریافت داده‌ها
$users = db_query("SELECT * FROM `$user_table` ORDER BY id")->fetchAll();
$groups = db_query("SELECT * FROM `$group_table` ORDER BY id")->fetchAll();
$user_groups = [];
foreach(db_query("SELECT * FROM `$gu_table`")->fetchAll() as $row) {
    $user_groups[$row['user_id']][] = $row['group_id'];
}
$cats = db_query("SELECT * FROM `$cat_table`")->fetchAll();
$permissions = db_query("SELECT * FROM `$perm_table` ORDER BY category_id, id")->fetchAll();
$perms_by_cat = [];
foreach($permissions as $p) $perms_by_cat[$p['category_id']][] = $p;

// پردازش انتخاب‌ها
$selected_user = isset($_GET['user']) ? intval($_GET['user']) : 0;
$selected_group = isset($_GET['usergroup']) ? intval($_GET['usergroup']) : 0;
$selected_perm = isset($_GET['perm']) ? intval($_GET['perm']) : 0;
$selected_cat = isset($_GET['permcat']) ? intval($_GET['permcat']) : 0;

// عملیات اعطا/پس‌گیری
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_type = $_POST['target_type'];
    $target_id   = intval($_POST['target_id']);
    $perm_type   = $_POST['perm_type'];
    $perm_id     = intval($_POST['perm_id']);
    $cat_id      = intval($_POST['cat_id']);
    $action      = $_POST['action'];
    // تعیین مجوزها
    $perm_ids = [];
    if ($perm_type === 'perm' && $perm_id) $perm_ids = [$perm_id];
    if ($perm_type === 'cat' && $cat_id)   $perm_ids = array_column(db_query("SELECT id FROM `$perm_table` WHERE category_id=?", [$cat_id])->fetchAll(), 'id');
    if (!$perm_ids) $err = 'هیچ مجوزی انتخاب نشده است.';
    else {
        // اعطا یا گرفتن مجوز برای کاربر
        if ($target_type === 'user' && $target_id) {
            foreach ($perm_ids as $pid) {
                $role_id = db_query("SELECT id FROM roles WHERE name=(SELECT role FROM `$user_table` WHERE id=?)", [$target_id])->fetchColumn();
                if ($action === 'grant') {
                    db_query("INSERT IGNORE INTO `$role_perm_table` (role_id, permission_id) VALUES (?,?)", [$role_id, $pid]);
                } else {
                    db_query("DELETE FROM `$role_perm_table` WHERE role_id=? AND permission_id=?", [$role_id, $pid]);
                }
            }
            $msg = 'عملیات با موفقیت انجام شد.';
        }
        // اعطا یا گرفتن مجوز برای گروه کاربری
        if ($target_type === 'group' && $target_id) {
            foreach ($perm_ids as $pid) {
                if ($action === 'grant') {
                    db_query("INSERT IGNORE INTO `$group_perm_table` (group_id, permission_id, can_select) VALUES (?,?,1)", [$target_id, $pid]);
                } else {
                    db_query("DELETE FROM `$group_perm_table` WHERE group_id=? AND permission_id=?", [$target_id, $pid]);
                }
            }
            $msg = 'عملیات با موفقیت انجام شد.';
        }
    }
}

// بررسی وضعیت دسترسی برای نمایش پیام
$status_msg = '';
if ($selected_user || $selected_group) {
    // تعیین مجموعه مجوزها
    $perm_ids = [];
    $perm_title = '';
    if ($selected_perm) {
        $perm_ids = [$selected_perm];
        $perm_title = db_query("SELECT label FROM `$perm_table` WHERE id=?", [$selected_perm])->fetchColumn();
    } elseif ($selected_cat) {
        $perm_ids = array_column(db_query("SELECT id FROM `$perm_table` WHERE category_id=?", [$selected_cat])->fetchAll(), 'id');
        $perm_title = db_query("SELECT label FROM `$cat_table` WHERE id=?", [$selected_cat])->fetchColumn();
    }
    // بررسی دسترسی کاربر
    if ($selected_user && $perm_ids) {
        $uid = $selected_user;
        $role = db_query("SELECT role FROM `$user_table` WHERE id=?", [$uid])->fetchColumn();
        $role_id = db_query("SELECT id FROM roles WHERE name=?", [$role])->fetchColumn();
        $user_group_ids = $user_groups[$uid] ?? [];
        $lack = [];
        foreach ($perm_ids as $pid) {
            // نقش
            $has = db_query("SELECT id FROM `$role_perm_table` WHERE role_id=? AND permission_id=?", [$role_id, $pid])->fetch();
            // گروه
            $g_has = false;
            if ($user_group_ids) {
                $in = implode(',', array_map('intval', $user_group_ids));
                $g_has = db_query("SELECT id FROM `$group_perm_table` WHERE group_id IN ($in) AND permission_id=? AND can_select=1", [$pid])->fetch();
            }
            if (!$has && !$g_has)
                $lack[] = $pid;
        }
        if (!$lack)
            $status_msg = "<div class='alert alert-success'>کاربر انتخابی به همه مجوز(ها)ی انتخاب شده دسترسی دارد.</div>";
        else {
            // اگر حتی یکی نداشت
            $names = implode(', ', array_map(function($pid) use($perm_table) {
                return db_query("SELECT label FROM `$perm_table` WHERE id=?", [$pid])->fetchColumn();
            }, $lack));
            $status_msg = "<div class='alert alert-danger'>کاربر به این مجوزها دسترسی ندارد: $names</div>";
        }
    }
    // بررسی دسترسی گروه
    if ($selected_group && $perm_ids) {
        $gid = $selected_group;
        $lack = [];
        foreach ($perm_ids as $pid) {
            $has = db_query("SELECT id FROM `$group_perm_table` WHERE group_id=? AND permission_id=? AND can_select=1", [$gid, $pid])->fetch();
            if (!$has) $lack[] = $pid;
        }
        if (!$lack)
            $status_msg = "<div class='alert alert-success'>گروه انتخابی به همه مجوز(ها)ی انتخاب شده دسترسی دارد.</div>";
        else {
            $names = implode(', ', array_map(function($pid) use($perm_table) {
                return db_query("SELECT label FROM `$perm_table` WHERE id=?", [$pid])->fetchColumn();
            }, $lack));
            $status_msg = "<div class='alert alert-danger'>گروه به این مجوزها دسترسی ندارد: $names</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>اعطای/گرفتن مجوز به کاربر یا گروه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .tree-list {max-height: 350px; overflow-y: auto; background: #f7fafd; border-radius: 7px; padding: 12px;}
        .tree-list ul {list-style: none; padding-left: 1rem;}
        .tree-list li {margin-bottom: 7px;}
        .tree-list .group {color:#1976d2;font-weight: bold;}
        .tree-list .user {color:#444;}
        .tree-list .permgroup {color:#388e3c;font-weight: bold;}
        .tree-list .perm {color:#333;}
    </style>
</head>
<body>
<div class="container mt-5">
    <h4 class="mb-4">اعطای/گرفتن مجوز به کاربر یا گروه کاربران</h4>
    <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

    <div class="row">
        <!-- ستون کاربران و گروه‌ها -->
        <div class="col-md-4">
            <div class="tree-list border mb-3">
                <b>کاربران و گروه‌ها:</b>
                <ul>
                    <li class="group">گروه‌ها:
                        <ul>
                            <?php foreach($groups as $g): ?>
                                <li>
                                    <a href="?usergroup=<?= $g['id'] ?>"<?= $selected_group==$g['id']?' style="font-weight:bold"':'' ?>>
                                        <?= htmlspecialchars($g['label'] ?: $g['name']) ?>
                                    </a>
                                    <ul>
                                        <?php foreach($users as $u):
                                            if (in_array($g['id'], $user_groups[$u['id']] ?? [])): ?>
                                            <li class="user">
                                                <a href="?user=<?= $u['id'] ?>"<?= $selected_user==$u['id']?' style="font-weight:bold"':'' ?>>
                                                    <?= htmlspecialchars($u['username']) ?>
                                                </a>
                                            </li>
                                        <?php endif; endforeach;?>
                                    </ul>
                                </li>
                            <?php endforeach;?>
                        </ul>
                    </li>
                    <li class="group">کاربران (خارج از گروه):
                        <ul>
                            <?php foreach($users as $u):
                                $has_group = !empty($user_groups[$u['id']]);
                                if (!$has_group): ?>
                                <li class="user">
                                    <a href="?user=<?= $u['id'] ?>"<?= $selected_user==$u['id']?' style="font-weight:bold"':'' ?>>
                                        <?= htmlspecialchars($u['username']) ?>
                                    </a>
                                </li>
                            <?php endif; endforeach;?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <!-- ستون گروه مجوزها و مجوزها -->
        <div class="col-md-4">
            <div class="tree-list border mb-3">
                <b>گروه مجوزها و مجوزها:</b>
                <ul>
                    <?php foreach($cats as $cat): ?>
                    <li class="permgroup">
                        <a href="?<?= $selected_user ? "user=$selected_user" : "usergroup=$selected_group" ?>&permcat=<?= $cat['id'] ?>"<?= $selected_cat==$cat['id']?' style="font-weight:bold"':'' ?>>
                            <?= htmlspecialchars($cat['label'] ?: $cat['name']) ?>
                        </a>
                        <ul>
                            <?php foreach($perms_by_cat[$cat['id']]??[] as $p): ?>
                            <li class="perm">
                                <a href="?<?= $selected_user ? "user=$selected_user" : "usergroup=$selected_group" ?>&perm=<?= $p['id'] ?>"<?= $selected_perm==$p['id']?' style="font-weight:bold"':'' ?>>
                                    <?= htmlspecialchars($p['label'] ?: $p['name']) ?>
                                </a>
                            </li>
                            <?php endforeach;?>
                        </ul>
                    </li>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
        <!-- ستون عملیات و نمایش وضعیت -->
        <div class="col-md-4">
            <div class="mb-3">
                <b>وضعیت:</b>
                <?= $status_msg ?>
            </div>
            <?php if(($selected_user || $selected_group) && ($selected_perm || $selected_cat)): ?>
            <form method="post" class="mb-2">
                <input type="hidden" name="target_type" value="<?= $selected_user?'user':'group' ?>">
                <input type="hidden" name="target_id" value="<?= $selected_user?:$selected_group ?>">
                <input type="hidden" name="perm_type" value="<?= $selected_perm?'perm':'cat' ?>">
                <input type="hidden" name="perm_id" value="<?= $selected_perm ?>">
                <input type="hidden" name="cat_id" value="<?= $selected_cat ?>">
                <button name="action" value="grant" class="btn btn-success mb-2 w-100">اعطای مجوز</button>
                <button name="action" value="revoke" class="btn btn-danger w-100">گرفتن مجوز</button>
            </form>
            <?php else: ?>
                <div class="alert alert-warning">لطفاً ابتدا کاربر یا گروه و سپس مجوز یا گروه مجوز را انتخاب کنید.</div>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-secondary mt-4 w-100">بازگشت به داشبورد</a>
        </div>
    </div>
</div>
</body>
</html>