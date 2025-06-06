<?php
// ------------------------------
// صفحه مدیریت ماتریس دسترسی کاربران و گروه‌ها به مجوزها (درختی و آبشاری)
// ------------------------------

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
$rp_tbl     = $config['tables']['role_permissions'];
$gp_tbl     = $config['tables']['group_permissions'];

// --- ابزارهای درختی گروه‌ها و دسته‌بندی‌ها --- //

// گروه‌های کاربران (درختی)
function getGroupsTree($parent_id = null, $all = null) {
    global $group_tbl;
    if ($all === null) $all = db_query("SELECT * FROM `$group_tbl` ORDER BY id")->fetchAll();
    $tree = [];
    foreach ($all as $g) {
        if ($g['parent_id'] == $parent_id) {
            $g['children'] = getGroupsTree($g['id'], $all);
            $tree[] = $g;
        }
    }
    return $tree;
}

// دسته بندی مجوزها (درختی)
function getCatsTree($parent_id = null, $all = null) {
    global $cat_tbl;
    if ($all === null) $all = db_query("SELECT * FROM `$cat_tbl` ORDER BY id")->fetchAll();
    $tree = [];
    foreach ($all as $c) {
        if ($c['parent_id'] == $parent_id) {
            $c['children'] = getCatsTree($c['id'], $all);
            $tree[] = $c;
        }
    }
    return $tree;
}

// --- داده‌ها --- //
$users = db_query("SELECT * FROM `$user_tbl` ORDER BY username")->fetchAll();
$groups_tree = getGroupsTree();
$all_groups = db_query("SELECT * FROM `$group_tbl`")->fetchAll();

$cats_tree = getCatsTree();
$all_cats = db_query("SELECT * FROM `$cat_tbl`")->fetchAll();

// مجوزهای هر دسته
$permissions = db_query("SELECT * FROM `$perm_tbl`")->fetchAll();
$cat_perms = [];
foreach ($permissions as $p) {
    $cat_perms[$p['category_id']][] = $p;
}

// اعضای هر گروه
$group_members = [];
foreach ($all_groups as $g) {
    $members = db_query("SELECT user_id FROM `$gu_tbl` WHERE group_id=?", [$g['id']])->fetchAll(PDO::FETCH_COLUMN);
    $group_members[$g['id']] = $members;
}

// --- انتخاب‌ها --- //
$selected_user    = $_GET['user'] ?? '';
$selected_group   = $_GET['usergroup'] ?? '';
$selected_perm    = $_GET['perm'] ?? '';
$selected_cat     = $_GET['permcat'] ?? '';
$action           = $_POST['action'] ?? '';
$msg = '';
$status_msg = '';
$success = false;

// --- اعطا/حذف مجوز --- //
if ($_SERVER['REQUEST_METHOD']==='POST' && $action && ($selected_user||$selected_group) && ($selected_perm||$selected_cat)) {
    // لیست مجوزها
    $perm_ids = [];
    if ($selected_perm) {
        $perm_ids[] = intval($selected_perm);
    } elseif($selected_cat) {
        foreach($cat_perms[$selected_cat]??[] as $p) $perm_ids[] = $p['id'];
    }

    // کاربر
    if ($selected_user) {
        $user = db_query("SELECT * FROM `$user_tbl` WHERE id=?", [$selected_user])->fetch();
        $role_id = db_query("SELECT id FROM `".$config['tables']['roles']."` WHERE name=?", [$user['role']])->fetchColumn();
        foreach ($perm_ids as $pid) {
            if ($action==='grant') {
                $has = db_query("SELECT id FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $pid])->fetch();
                if (!$has) db_query("INSERT INTO `$rp_tbl` (role_id,permission_id) VALUES (?,?)", [$role_id, $pid]);
            } else {
                db_query("DELETE FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $pid]);
            }
        }
        $msg = ($action==='grant'?'مجوز اعطا شد.':'مجوز حذف شد.');
        $success = true;
    }
    // گروه کاربری
    else if ($selected_group) {
        foreach ($perm_ids as $pid) {
            if ($action==='grant') {
                db_query("INSERT IGNORE INTO `$gp_tbl` (group_id,permission_id) VALUES (?,?)", [$selected_group, $pid]);
            } else {
                db_query("DELETE FROM `$gp_tbl` WHERE group_id=? AND permission_id=?", [$selected_group, $pid]);
            }
        }
        $msg = ($action==='grant'?'مجوز به گروه اعطا شد.':'مجوز از گروه حذف شد.');
        $success = true;
    }
}

// --- بررسی وضعیت دسترسی --- //
if (($selected_user||$selected_group) && ($selected_perm||$selected_cat)) {
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

    // کاربر
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

    // گروه کاربری
    if ($selected_group) {
        $group = db_query("SELECT * FROM `$group_tbl` WHERE id=?", [$selected_group])->fetch();
        $gname = $group['name'];
        // همه اعضای گروه
        $members = $group_members[$selected_group] ?? [];
        $not_have = [];
        foreach ($perm_ids as $i=>$pid) {
            $per_name = $perm_names[$i];
            $no_users = [];
            foreach($members as $uid) {
                if (!user_has_permission($uid, $permissions[array_search($pid, array_column($permissions,'id'))]['name']))
                    $no_users[] = db_query("SELECT username FROM `$user_tbl` WHERE id=?", [$uid])->fetchColumn();
            }
            if (count($no_users) === count($members)) {
                // هیچکس ندارد
                $not_have[] = "کل اعضای گروه به مجوز <b>$per_name</b> دسترسی ندارند";
            } elseif ($no_users) {
                $not_have[] = "کاربران ".implode(', ', $no_users)." به مجوز <b>$per_name</b> دسترسی ندارند";
            }
        }
        if (!$not_have)
            $status_msg = "<span class='text-success'>تمام اعضای گروه <b>$gname</b> به این مجوز(ها) دسترسی دارند.</span>";
        else
            $status_msg = "<span class='text-danger'>".implode('<br>',$not_have)."</span>";
    }
}

// --- توابع نمایش درختی --- //

function printGroupTree($tree, $level = 0, $selected_id = '', $prefix = '') {
    foreach ($tree as $g) {
        $sel = $selected_id==$g['id']?'checked':'';
        echo "<li style='margin-right:".($level*12)."px'>";
        echo "<label><input type='radio' name='group_user' value='{$g['id']}' id='group{$g['id']}' $sel onclick=\"window.location='?usergroup={$g['id']}'\"> ";
        echo htmlspecialchars($g['name']);
        if ($g['label']) echo " <span class='text-muted'>(".htmlspecialchars($g['label']).")</span>";
        echo "</label>";
        if (!empty($g['children'])) {
            echo "<ul>";
            printGroupTree($g['children'], $level+1, $selected_id, $prefix.'--');
            echo "</ul>";
        }
        echo "</li>";
    }
}

function printUserList($users, $selected_id = '') {
    foreach ($users as $u) {
        $sel = $selected_id==$u['id']?'checked':'';
        echo "<li>";
        echo "<label><input type='radio' name='user' value='{$u['id']}' id='user{$u['id']}' $sel onclick=\"window.location='?user={$u['id']}'\"> ";
        echo htmlspecialchars($u['username']);
        echo "</label></li>";
    }
}

function printCatsTree($tree, $level = 0, $selected_id = '', $selected_perm = '') {
    global $cat_perms;
    foreach ($tree as $c) {
        $sel = $selected_id==$c['id']?'checked':'';
        echo "<li style='margin-right:".($level*12)."px'>";
        echo "<label><input type='radio' name='permcat' value='{$c['id']}' id='cat{$c['id']}' $sel onclick=\"window.location='?".buildPermQuery(['permcat'=>$c['id']])."'\"> ";
        echo htmlspecialchars($c['label']?:$c['name']);
        echo "</label>";
        if (!empty($cat_perms[$c['id']])) {
            echo "<ul>";
            foreach($cat_perms[$c['id']] as $p) {
                $selp = $selected_perm==$p['id']?'checked':'';
                echo "<li><label><input type='radio' name='perm' value='{$p['id']}' id='perm{$p['id']}' $selp onclick=\"window.location='?".buildPermQuery(['perm'=>$p['id']])."'\"> ".htmlspecialchars($p['label']?:$p['name'])."</label></li>";
            }
            echo "</ul>";
        }
        if (!empty($c['children'])) {
            echo "<ul>";
            printCatsTree($c['children'], $level+1, $selected_id, $selected_perm);
            echo "</ul>";
        }
        echo "</li>";
    }
}

function buildPermQuery($arr) {
    $query = [];
    if (isset($_GET['user'])) $query['user'] = $_GET['user'];
    if (isset($_GET['usergroup'])) $query['usergroup'] = $_GET['usergroup'];
    if (isset($arr['permcat'])) $query['permcat'] = $arr['permcat'];
    if (isset($arr['perm'])) $query['perm'] = $arr['perm'];
    return http_build_query($query);
}

?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ماتریس مدیریت دسترسی کاربران/گروه‌ها به مجوزها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .tree ul { list-style: none; margin:0; padding-right:20px;}
        .tree li { margin-bottom: 7px;}
        .tree label { cursor:pointer; }
        .selected { background: #d0e7fd !important; }
        .side-box { background: #f8fafd; border-radius: 12px; border:1px solid #e7e7e7;}
        .side-title { background: #1976d2; color: #fff; border-radius: 8px 8px 0 0; font-size:1.1em; padding:9px 12px;}
    </style>
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-4 text-primary">ماتریس مدیریت دسترسی کاربران و گروه‌ها به مجوزها</h4>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($status_msg): ?><div class="alert alert-<?= $success?'success':'warning' ?>"><?= $status_msg ?></div><?php endif; ?>
    <div class="row">
        <!-- ستون راست: کاربران و گروه‌ها -->
        <div class="col-md-4">
            <div class="side-box mb-3">
                <div class="side-title">انتخاب کاربر یا گروه کاربری</div>
                <div class="p-3 tree">
                    <b>کاربران</b>
                    <ul>
                        <?php printUserList($users, $selected_user); ?>
                    </ul>
                    <hr>
                    <b>گروه‌های کاربران (درختی)</b>
                    <ul>
                        <?php printGroupTree($groups_tree, 0, $selected_group); ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- ستون وسط: دکمه های عملیات -->
        <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
            <form method="post" class="mb-3 w-100">
                <input type="hidden" name="action" value="grant">
                <button class="btn btn-success w-100 mb-2" <?= (!$selected_user && !$selected_group) || (!$selected_perm && !$selected_cat) ? 'disabled' : '' ?>>اعطای این مجوز</button>
            </form>
            <form method="post" class="mb-3 w-100">
                <input type="hidden" name="action" value="revoke">
                <button class="btn btn-danger w-100" <?= (!$selected_user && !$selected_group) || (!$selected_perm && !$selected_cat) ? 'disabled' : '' ?>>گرفتن این مجوز</button>
            </form>
        </div>
        <!-- ستون چپ: مجوزها و دسته‌بندی‌ها -->
        <div class="col-md-6">
            <div class="side-box mb-3">
                <div class="side-title">انتخاب مجوز یا گروه مجوز (دسته‌بندی درختی)</div>
                <div class="p-3 tree">
                    <ul>
                        <?php printCatsTree($cats_tree, 0, $selected_cat, $selected_perm); ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-4">بازگشت به داشبورد</a>
</div>
</body>
</html>