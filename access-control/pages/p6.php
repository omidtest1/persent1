<?php
// ------------------------------
// مدیریت و اعطای مجوز به کاربر یا گروه - نسخه آبشاری و حرفه‌ای
// ------------------------------

require_once __DIR__.'/../access-init.php';
require_once __DIR__.'/../i18n.php';

if (!ac_user_id() || !user_has_permission(ac_user_id(), 'manage_permissions'))
    die(lang('access_denied'));

global $config;

// جداول
$user_tbl   = $config['tables']['users'];
$group_tbl  = $config['tables']['groups'];
$gu_tbl     = $config['tables']['group_users'];
$perm_tbl   = $config['tables']['permissions'];
$cat_tbl    = $config['tables']['permission_categories'];
$rp_tbl     = $config['tables']['role_permissions'];
$gp_tbl     = $config['tables']['group_permissions'];

// --- توابع کمکی برای ساختار درختی گروه کاربران و دسته‌بندی مجوزها ---
function getTree($table, $parent_id = null, $all = null) {
    if ($all === null)
        $all = db_query("SELECT * FROM `$table` ORDER BY id")->fetchAll();
    $tree = [];
    foreach ($all as $item) {
        if ($item['parent_id'] == $parent_id) {
            $item['children'] = getTree($table, $item['id'], $all);
            $tree[] = $item;
        }
    }
    return $tree;
}
function printUserGroupTree($tree, $selected_user, $selected_group, $level = 0) {
    global $gu_tbl, $user_tbl;
    foreach ($tree as $g) {
        echo '<li>';
        echo '<label>';
        echo '<input type="radio" name="entity" value="group_'.$g['id'].'" '.($selected_group==$g['id']?'checked':'').' onclick="selectEntity(\'group\', ' . $g['id'] . ')">';
        echo '<b>'.htmlspecialchars($g['name']).'</b>';
        if ($g['label']) echo ' <span class="text-muted">'.htmlspecialchars($g['label']).'</span>';
        echo '</label>';
        // اعضای گروه
        $members = db_query("SELECT u.id, u.username FROM `$gu_tbl` gu JOIN `$user_tbl` u ON gu.user_id=u.id WHERE gu.group_id=?", [$g['id']])->fetchAll();
        if ($members) {
            echo '<ul>';
            foreach($members as $u) {
                echo '<li style="margin-right:15px">
                    <label>
                        <input type="radio" name="entity" value="user_'.$u['id'].'" '.($selected_user==$u['id']?'checked':'').' onclick="selectEntity(\'user\', ' . $u['id'] . ')">
                        '.htmlspecialchars($u['username']).'
                    </label>
                </li>';
            }
            echo '</ul>';
        }
        // زیرگروه‌ها
        if (!empty($g['children'])) {
            echo '<ul>';
            printUserGroupTree($g['children'], $selected_user, $selected_group, $level+1);
            echo '</ul>';
        }
        echo '</li>';
    }
}
function printPermissionCatTree($tree, $permissions_by_cat, $selected_perm, $selected_cat, $level = 0) {
    foreach ($tree as $c) {
        echo '<li>';
        echo '<label>';
        echo '<input type="radio" name="perm_entity" value="cat_'.$c['id'].'" '.($selected_cat==$c['id']?'checked':'').' onclick="selectPerm(\'cat\', '.$c['id'].')">';
        echo '<b>'.htmlspecialchars($c['label']?:$c['name']).'</b>';
        echo '</label>';
        // مجوزهای این دسته
        if (!empty($permissions_by_cat[$c['id']])) {
            echo '<ul>';
            foreach($permissions_by_cat[$c['id']] as $p) {
                echo '<li style="margin-right:15px">
                    <label>
                        <input type="radio" name="perm_entity" value="perm_'.$p['id'].'" '.($selected_perm==$p['id']?'checked':'').' onclick="selectPerm(\'perm\', '.$p['id'].')">
                        '.htmlspecialchars($p['label']?:$p['name']).'
                    </label>
                </li>';
            }
            echo '</ul>';
        }
        // زیرمجموعه‌ها
        if (!empty($c['children'])) {
            echo '<ul>';
            printPermissionCatTree($c['children'], $permissions_by_cat, $selected_perm, $selected_cat, $level+1);
            echo '</ul>';
        }
        echo '</li>';
    }
}

// --- مقدار انتخاب فعلی (GET/POST) ---
$selected_type = '';  // user|group
$selected_id   = 0;
if (isset($_GET['entity'])) {
    if (strpos($_GET['entity'], 'user_') === 0) {
        $selected_type = 'user';
        $selected_id = intval(substr($_GET['entity'], 5));
    } elseif (strpos($_GET['entity'], 'group_') === 0) {
        $selected_type = 'group';
        $selected_id = intval(substr($_GET['entity'], 6));
    }
}
$selected_perm_type = '';
$selected_perm_id   = 0;
if (isset($_GET['perm_entity'])) {
    if (strpos($_GET['perm_entity'], 'perm_') === 0) {
        $selected_perm_type = 'perm';
        $selected_perm_id = intval(substr($_GET['perm_entity'], 5));
    } elseif (strpos($_GET['perm_entity'], 'cat_') === 0) {
        $selected_perm_type = 'cat';
        $selected_perm_id = intval(substr($_GET['perm_entity'], 4));
    }
}

// --- عملیات اعطا/گرفتن مجوز ---
$msg = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $entity = $_POST['entity'] ?? '';
    $perm_entity = $_POST['perm_entity'] ?? '';

    // تعیین نوع و آیدی
    $type = ''; $id = 0;
    if (strpos($entity, 'user_') === 0) {
        $type = 'user';
        $id = intval(substr($entity, 5));
    } elseif (strpos($entity, 'group_') === 0) {
        $type = 'group';
        $id = intval(substr($entity, 6));
    }
    $perm_type = ''; $pid = 0;
    if (strpos($perm_entity, 'perm_') === 0) {
        $perm_type = 'perm';
        $pid = intval(substr($perm_entity, 5));
    } elseif (strpos($perm_entity, 'cat_') === 0) {
        $perm_type = 'cat';
        $pid = intval(substr($perm_entity, 4));
    }
    // استخراج مجوزها
    $perm_ids = [];
    if ($perm_type === 'perm') {
        $perm_ids[] = $pid;
    } elseif ($perm_type === 'cat') {
        $cat_perms = db_query("SELECT id FROM `$perm_tbl` WHERE category_id=?", [$pid])->fetchAll(PDO::FETCH_COLUMN);
        $perm_ids = $cat_perms ?: [];
    }
    if ($type === 'user') {
        // نقش کاربر را پیدا کن
        $user = db_query("SELECT * FROM `$user_tbl` WHERE id=?", [$id])->fetch();
        if ($user) {
            $role_id = db_query("SELECT id FROM ".$config['tables']['roles']." WHERE name=?", [$user['role']])->fetchColumn();
            foreach($perm_ids as $permid) {
                if ($_POST['action'] == "grant") {
                    db_query("INSERT IGNORE INTO `$rp_tbl` (role_id,permission_id) VALUES (?,?)", [$role_id, $permid]);
                } else {
                    db_query("DELETE FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $permid]);
                }
            }
            $msg = ($_POST['action'] == "grant" ? "مجوزها اعطا شد." : "مجوزها حذف شد.");
        }
    } elseif ($type === 'group') {
        foreach($perm_ids as $permid) {
            if ($_POST['action'] == "grant") {
                db_query("INSERT IGNORE INTO `$gp_tbl` (group_id,permission_id) VALUES (?,?)", [$id, $permid]);
            } else {
                db_query("DELETE FROM `$gp_tbl` WHERE group_id=? AND permission_id=?", [$id, $permid]);
            }
        }
        $msg = ($_POST['action'] == "grant" ? "مجوزها به گروه اعطا شد." : "مجوزها از گروه حذف شد.");
    }
    // پس از عملیات، رفرش جهت آپدیت سریع
    header("Location:  ?entity=$entity&perm_entity=$perm_entity&msg=".urlencode($msg));
    exit;
}

// --- وضعیت دسترسی آنلاین و برخط (در لحظه) ---
$status_msgs = [];
if ($selected_type && $selected_id && $selected_perm_type && $selected_perm_id) {
    // استخراج کاربران هدف
    $target_users = [];
    if ($selected_type == 'user') {
        $target_users[] = $selected_id;
    } elseif ($selected_type == 'group') {
        $target_users = db_query("SELECT user_id FROM `$gu_tbl` WHERE group_id=?", [$selected_id])->fetchAll(PDO::FETCH_COLUMN);
    }
    // استخراج مجوزهای هدف
    $target_perms = [];
    if ($selected_perm_type == 'perm') {
        $perm = db_query("SELECT * FROM `$perm_tbl` WHERE id=?", [$selected_perm_id])->fetch();
        if ($perm) $target_perms[] = $perm;
    } elseif ($selected_perm_type == 'cat') {
        $target_perms = db_query("SELECT * FROM `$perm_tbl` WHERE category_id=?", [$selected_perm_id])->fetchAll();
    }
    $no_access = [];
    foreach($target_users as $uid) {
        $user = db_query("SELECT * FROM `$user_tbl` WHERE id=?", [$uid])->fetch();
        if (!$user) continue;
        $username = htmlspecialchars($user['username']);
        foreach ($target_perms as $p) {
            if (!user_has_permission($uid, $p['name'])) {
                $no_access[] = "کاربر <b>$username</b> به مجوز <b>".htmlspecialchars($p['label']?:$p['name'])."</b> دسترسی ندارد.";
            }
        }
    }
    if (!$target_users) {
        $status_msgs[] = "<span class='text-danger'>هیچ کاربری در این گروه وجود ندارد.</span>";
    } elseif (!$target_perms) {
        $status_msgs[] = "<span class='text-danger'>هیچ مجوزی در این دسته وجود ندارد.</span>";
    } elseif (!$no_access) {
        $status_msgs[] = "<span class='text-success'>تمام کاربران انتخاب شده به تمام مجوزهای انتخاب شده دسترسی دارند.</span>";
    } else {
        foreach ($no_access as $msg) $status_msgs[] = "<span class='text-danger'>$msg</span>";
    }
}

// --- داده‌های صفحه: گروه‌ها و کاربران و مجوزها و دسته‌ها ---
$groups_tree = getTree($group_tbl);
$cats_tree   = getTree($cat_tbl);
$permissions = db_query("SELECT * FROM `$perm_tbl`")->fetchAll();
$permissions_by_cat = [];
foreach($permissions as $p) {
    $permissions_by_cat[$p['category_id']][] = $p;
}
$all_users = db_query("SELECT * FROM `$user_tbl`")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>اعطای مجوز به کاربر یا گروه (آبشاری)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .tree ul { list-style: none; padding-right: 20px; }
        .tree li { margin-bottom: 7px; }
        .tree label { cursor: pointer; }
        .selected { background: #d0e7fd !important; }
        .side-card { min-height: 400px; }
    </style>
    <script>
        function selectEntity(type, id) {
            let param = type === 'user' ? 'user_' + id : 'group_' + id;
            let url = new URL(window.location.href);
            url.searchParams.set('entity', param);
            window.location = url.toString();
        }
        function selectPerm(type, id) {
            let param = type === 'perm' ? 'perm_' + id : 'cat_' + id;
            let url = new URL(window.location.href);
            url.searchParams.set('perm_entity', param);
            window.location = url.toString();
        }
        function setEntityForm() {
            var checked = document.querySelector('input[name="entity"]:checked');
            if (checked) document.getElementById('entity_form').value = checked.value;
            var checkedPerm = document.querySelector('input[name="perm_entity"]:checked');
            if (checkedPerm) document.getElementById('perm_entity_form').value = checkedPerm.value;
        }
    </script>
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-3">مدیریت اعطای مجوز به کاربر یا گروه (آبشاری)</h4>
    <?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
    <?php foreach($status_msgs as $s): ?><div class="alert alert-info"><?= $s ?></div><?php endforeach; ?>
    <div class="row">
        <!-- سمت راست: آبشاری گروه و کاربران -->
        <div class="col-md-5">
            <div class="card side-card mb-3">
                <div class="card-header bg-primary text-white">کاربران و گروه‌های کاربران</div>
                <div class="card-body tree" style="max-height: 430px; overflow-y:auto;">
                    <ul>
                        <?php printUserGroupTree($groups_tree, ($selected_type=='user'?$selected_id:0), ($selected_type=='group'?$selected_id:0)); ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- سمت چپ: آبشاری دسته و مجوزها -->
        <div class="col-md-5">
            <div class="card side-card mb-3">
                <div class="card-header bg-primary text-white">دسته‌ها و مجوزها</div>
                <div class="card-body tree" style="max-height: 430px; overflow-y:auto;">
                    <ul>
                        <?php printPermissionCatTree($cats_tree, $permissions_by_cat, ($selected_perm_type=='perm'?$selected_perm_id:0), ($selected_perm_type=='cat'?$selected_perm_id:0)); ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- عملیات -->
        <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
            <form method="post" onsubmit="setEntityForm()">
                <input type="hidden" name="entity" id="entity_form" value="<?= htmlspecialchars($_GET['entity']??'') ?>">
                <input type="hidden" name="perm_entity" id="perm_entity_form" value="<?= htmlspecialchars($_GET['perm_entity']??'') ?>">
                <button name="action" value="grant" class="btn btn-success w-100 mb-2"
                    <?= (!$selected_type||!$selected_id||!$selected_perm_type||!$selected_perm_id) ? 'disabled':'' ?>>
                    اعطای این مجوز
                </button>
                <button name="action" value="revoke" class="btn btn-danger w-100"
                    <?= (!$selected_type||!$selected_id||!$selected_perm_type||!$selected_perm_id) ? 'disabled':'' ?>>
                    گرفتن مجوز
                </button>
            </form>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-4">بازگشت به داشبورد</a>
</div>
</body>
</html>