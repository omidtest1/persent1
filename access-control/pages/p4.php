<?php
// ------------------------------
// مدیریت ماتریس اعطا/سلب مجوز کاربران و گروه‌ها (درختی و آبشاری + چک‌باکس)
// ریسپانسیو و زیبا + تایید عملیات در مودال
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
$users_by_id = [];
foreach($users as $u) $users_by_id[$u['id']] = $u;
$groups_tree = getGroupsTree();
$all_groups = db_query("SELECT * FROM `$group_tbl`")->fetchAll();
$groups_by_id = [];
foreach($all_groups as $g) $groups_by_id[$g['id']] = $g;

$cats_tree = getCatsTree();
$all_cats = db_query("SELECT * FROM `$cat_tbl`")->fetchAll();

// مجوزهای هر دسته
$permissions = db_query("SELECT * FROM `$perm_tbl`")->fetchAll();
$permissions_by_id = [];
foreach($permissions as $p) $permissions_by_id[$p['id']] = $p;
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

// --- انتخاب‌ها (POST) --- //
$selected_users = [];
$selected_groups = [];
if (!empty($_POST['user_ids'])) {
    foreach((array)$_POST['user_ids'] as $uid) if(isset($users_by_id[$uid])) $selected_users[] = $uid;
}
if (!empty($_POST['group_ids'])) {
    foreach((array)$_POST['group_ids'] as $gid) if(isset($groups_by_id[$gid])) $selected_groups[] = $gid;
}
$selected_perms = [];
$selected_perm_cats = [];
if (!empty($_POST['perm_ids'])) {
    foreach((array)$_POST['perm_ids'] as $pid) if(isset($permissions_by_id[$pid])) $selected_perms[] = $pid;
}
if (!empty($_POST['cat_ids'])) {
    foreach((array)$_POST['cat_ids'] as $cid) if(isset($cat_perms[$cid])) $selected_perm_cats[] = $cid;
}
$action = $_POST['action'] ?? '';
$msg = '';
$status_msg = '';
$success = false;

// --- عملیات اعطا/حذف مجوز --- //
if ($_SERVER['REQUEST_METHOD']==='POST' && $action && isset($_POST['confirm_action'])) {
    if ((count($selected_users) || count($selected_groups)) && (count($selected_perms) || count($selected_perm_cats))) {
        // لیست همه مجوزها
        $all_perm_ids = $selected_perms;
        foreach($selected_perm_cats as $catid) foreach($cat_perms[$catid] as $p) $all_perm_ids[] = $p['id'];
        $all_perm_ids = array_unique($all_perm_ids);

        // کاربران تکی:
        foreach($selected_users as $uid) {
            $user = $users_by_id[$uid];
            $role_id = db_query("SELECT id FROM `".$config['tables']['roles']."` WHERE name=?", [$user['role']])->fetchColumn();
            foreach ($all_perm_ids as $pid) {
                if ($action==='grant') {
                    $has = db_query("SELECT id FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $pid])->fetch();
                    if (!$has) db_query("INSERT INTO `$rp_tbl` (role_id,permission_id) VALUES (?,?)", [$role_id, $pid]);
                } else {
                    db_query("DELETE FROM `$rp_tbl` WHERE role_id=? AND permission_id=?", [$role_id, $pid]);
                }
            }
        }
        // گروه‌ها:
        foreach($selected_groups as $gid) {
            foreach ($all_perm_ids as $pid) {
                if ($action==='grant') {
                    db_query("INSERT IGNORE INTO `$gp_tbl` (group_id,permission_id) VALUES (?,?)", [$gid, $pid]);
                } else {
                    db_query("DELETE FROM `$gp_tbl` WHERE group_id=? AND permission_id=?", [$gid, $pid]);
                }
            }
        }
        $msg = ($action==='grant'?'مجوز اعطا شد.':'مجوز حذف شد.');
        $success = true;
    }
}

// --- بررسی وضعیت دسترسی --- //
$access_status = "";
if (count($selected_users) || count($selected_groups)) {
    $all_perm_ids = $selected_perms;
    foreach($selected_perm_cats as $catid) foreach($cat_perms[$catid] as $p) $all_perm_ids[] = $p['id'];
    $all_perm_ids = array_unique($all_perm_ids);
    if ($all_perm_ids) {
        $status_lines = [];
        // تک تک کاربران
        foreach ($selected_users as $uid) {
            $user = $users_by_id[$uid];
            $lacks = [];
            foreach($all_perm_ids as $pid) {
                $p = $permissions_by_id[$pid];
                if (!user_has_permission($uid, $p['name'])) $lacks[] = $p['label'] ?: $p['name'];
            }
            if (!$lacks)
                $status_lines[] = "<li class='text-success mb-2'><b>کاربر ".htmlspecialchars($user['username'])."</b> به همه مجوزهای انتخابی دسترسی دارد.</li>";
            else
                $status_lines[] = "<li class='text-danger mb-2'><b>کاربر ".htmlspecialchars($user['username'])."</b> به این مجوزها دسترسی ندارد: <b>".implode(', ',$lacks)."</b></li>";
        }
        // تک تک گروه‌ها
        foreach($selected_groups as $gid) {
            $g = $groups_by_id[$gid];
            $members = $group_members[$gid] ?? [];
            if (!$members) {
                $status_lines[] = "<li class='text-warning mb-2'>گروه <b>".htmlspecialchars($g['name'])."</b> بدون عضو است.</li>";
                continue;
            }
            foreach($all_perm_ids as $pid) {
                $p = $permissions_by_id[$pid];
                $lack = [];
                foreach($members as $uid) {
                    if (!user_has_permission($uid, $p['name'])) $lack[] = $users_by_id[$uid]['username'];
                }
                if (!$lack)
                    $status_lines[] = "<li class='text-success mb-2'>کل اعضای گروه <b>".htmlspecialchars($g['name'])."</b> به مجوز <b>".htmlspecialchars($p['label']?:$p['name'])."</b> دسترسی دارند.</li>";
                else
                    $status_lines[] = "<li class='text-danger mb-2'>در گروه <b>".htmlspecialchars($g['name'])."</b> کاربران ".implode(', ',array_map('htmlspecialchars',$lack))." به مجوز <b>".htmlspecialchars($p['label']?:$p['name'])."</b> دسترسی ندارند.</li>";
            }
        }
        if ($status_lines) {
            $access_status = "<ul class='mb-2 ps-3'>$status_lines[0]";
            for($i=1;$i<count($status_lines);$i++) $access_status .= $status_lines[$i];
            $access_status .= "</ul>";
        }
    }
}

// --- توابع نمایش درختی با چک‌باکس --- //

function printGroupTreeCheckbox($tree, $group_members, $users_by_id, $selected_users, $selected_groups, $level = 0) {
    foreach ($tree as $g) {
        $gid = $g['id'];
        $checked = in_array($gid, $selected_groups) ? 'checked' : '';
        echo "<li class='mb-2' style='margin-right:".($level*12)."px'>";
        echo "<label class='fw-bold'><input type='checkbox' name='group_ids[]' value='$gid' class='form-check-input group-chk mx-1' $checked> ";
        echo htmlspecialchars($g['name']);
        if ($g['label']) echo " <span class='text-muted'>(".htmlspecialchars($g['label']).")</span>";
        echo "</label>";
        // اعضای گروه
        $members = $group_members[$gid] ?? [];
        if ($members) {
            echo "<ul class='list-unstyled'>";
            foreach($members as $uid) {
                $u = $users_by_id[$uid];
                $ch = in_array($uid, $selected_users) ? 'checked' : '';
                echo "<li style='margin-right:18px'>";
                echo "<label><input type='checkbox' name='user_ids[]' value='$uid' class='form-check-input user-chk mx-1' $ch> ";
                echo htmlspecialchars($u['username']);
                echo "</label></li>";
            }
            echo "</ul>";
        }
        if (!empty($g['children'])) {
            echo "<ul class='list-unstyled'>";
            printGroupTreeCheckbox($g['children'], $group_members, $users_by_id, $selected_users, $selected_groups, $level+1);
            echo "</ul>";
        }
        echo "</li>";
    }
}

function printUserListCheckbox($users, $selected_users) {
    foreach ($users as $u) {
        $ch = in_array($u['id'], $selected_users) ? 'checked' : '';
        echo "<li>";
        echo "<label><input type='checkbox' name='user_ids[]' value='{$u['id']}' class='form-check-input user-chk mx-1' $ch> ";
        echo htmlspecialchars($u['username']);
        echo "</label></li>";
    }
}

function printCatsTreeCheckbox($tree, $cat_perms, $selected_perms, $selected_perm_cats, $level = 0) {
    foreach ($tree as $c) {
        $cid = $c['id'];
        $checked = in_array($cid, $selected_perm_cats) ? 'checked' : '';
        echo "<li style='margin-right:".($level*12)."px'>";
        echo "<label class='fw-bold'><input type='checkbox' name='cat_ids[]' value='$cid' class='form-check-input cat-chk mx-1' $checked> ";
        echo htmlspecialchars($c['label']?:$c['name']);
        echo "</label>";
        if (!empty($cat_perms[$cid])) {
            echo "<ul class='list-unstyled'>";
            foreach($cat_perms[$cid] as $p) {
                $ch = in_array($p['id'], $selected_perms) ? 'checked' : '';
                echo "<li><label><input type='checkbox' name='perm_ids[]' value='{$p['id']}' class='form-check-input perm-chk mx-1' $ch> ".htmlspecialchars($p['label']?:$p['name'])."</label></li>";
            }
            echo "</ul>";
        }
        if (!empty($c['children'])) {
            echo "<ul class='list-unstyled'>";
            printCatsTreeCheckbox($c['children'], $cat_perms, $selected_perms, $selected_perm_cats, $level+1);
            echo "</ul>";
        }
        echo "</li>";
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ماتریس مدیریت مجوزها - پیشرفته</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background:#f3f5fa;}
        .panel-main { background: #fff; border-radius: 18px; box-shadow:0 6px 28px #0001; padding:40px 25px; margin-top:40px;}
        .side-title { background: #1976d2; color: #fff; border-radius: 8px 8px 0 0; font-size:1.08em; padding:10px 18px;}
        .tree ul { list-style: none; margin:0; padding-right:10px;}
        .tree li { margin-bottom: 7px; }
        .tree label { cursor:pointer; }
        .panel-actions { min-height: 170px; display: flex; flex-direction: column; align-items: stretch; justify-content: center;}
        .status-box {
            background: linear-gradient(90deg,#e3f3fa 0,#f5edfc 100%);
            border-radius: 11px; border:1.5px solid #dce1f4;
            padding: 20px 24px; margin-bottom: 30px; font-size:1.02em;
            box-shadow:0 4px 16px #0001;
        }
        .status-box ul {margin-bottom:0;}
        @media (max-width:900px) {
            .panel-main {padding:18px 3vw;}
        }
        @media (max-width:768px) {
            .panel-main {margin-top:10px; padding:10px 0;}
            .status-box {font-size:.98em;}
        }
        @media (max-width:600px) {
            .panel-main {box-shadow:none; border-radius:0;}
        }
        @media (max-width:990px) {
            .side-title {font-size:1em;}
        }
    </style>
</head>
<body>
<div class="container panel-main">
    <h4 class="mb-4 text-primary">ماتریس مدیریت اعطا و سلب مجوز (پیشرفته)</h4>
    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($access_status): ?>
        <div class="status-box">
            <b class="mb-2 d-block">وضعیت دسترسی انتخابی:</b>
            <?= $access_status ?>
        </div>
    <?php endif; ?>
    <form method="post" class="row g-2" id="permForm">
        <!-- ستون راست: کاربران و گروه‌ها -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="side-title mb-0">کاربران و گروه‌ها</div>
            <div class="p-3 pb-2 bg-light rounded-bottom shadow-sm tree" style="min-height:200px;max-height:400px;overflow:auto;">
                <b class="text-secondary">همه کاربران (تکی):</b>
                <ul class="mb-2">
                    <?php printUserListCheckbox($users, $selected_users); ?>
                </ul>
                <hr>
                <b class="text-secondary">گروه‌های کاربران:</b>
                <ul>
                    <?php printGroupTreeCheckbox($groups_tree, $group_members, $users_by_id, $selected_users, $selected_groups); ?>
                </ul>
            </div>
        </div>
        <!-- ستون وسط: دکمه های عملیات -->
        <div class="col-lg-2 col-md-12 d-flex flex-column align-items-center panel-actions mb-4">
            <button type="button" class="btn btn-success mb-3 py-2" style="font-size:1.09em" onclick="confirmAction('grant')">اعطای این مجوزها</button>
            <button type="button" class="btn btn-danger py-2" style="font-size:1.09em" onclick="confirmAction('revoke')">سلب این مجوزها</button>
            <input type="hidden" name="action" id="actionType" value="">
            <input type="hidden" name="confirm_action" value="1">
        </div>
        <!-- ستون چپ: مجوزها و دسته‌بندی‌ها -->
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="side-title mb-0">مجوزها و دسته‌بندی‌ها</div>
            <div class="p-3 pb-2 bg-light rounded-bottom shadow-sm tree" style="min-height:200px;max-height:400px;overflow:auto;">
                <ul>
                    <?php printCatsTreeCheckbox($cats_tree, $cat_perms, $selected_perms, $selected_perm_cats); ?>
                </ul>
            </div>
        </div>
    </form>
    <a href="dashboard.php" class="btn btn-secondary mt-3">بازگشت به داشبورد</a>
</div>

<!-- Modal تایید عملیات -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="confirmModalLabel">تایید عملیات</h5>
      <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="بستن"></button>
    </div>
    <div class="modal-body" id="confirmModalBody">
      آیا مطمئن هستید این عملیات انجام شود؟
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">خیر</button>
      <button type="button" class="btn btn-primary" id="modalSubmitBtn">بله، انجام شود</button>
    </div>
  </div></div>
</div>

<!-- اسکریپت انتخاب گروه/کاربر والد و تایید عملیات -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // با زدن هر گروه، تمام اعضای آن گروه هم انتخاب شوند و برعکس
    document.querySelectorAll('.group-chk').forEach(function(groupChk) {
        groupChk.addEventListener('change', function() {
            var list = this.closest('li');
            if (!list) return;
            list.querySelectorAll('.user-chk').forEach(function(userChk){
                userChk.checked = groupChk.checked;
            });
        });
    });

    function confirmAction(type) {
        var form = document.getElementById('permForm');
        var uCount = form.querySelectorAll('input.user-chk:checked').length;
        var gCount = form.querySelectorAll('input.group-chk:checked').length;
        var pCount = form.querySelectorAll('input.perm-chk:checked').length;
        var cCount = form.querySelectorAll('input.cat-chk:checked').length;
        if ((uCount+gCount)<1 || (pCount+cCount)<1) {
            alert('یک کاربر یا گروه کاربری و یک مجوز یا دسته مجوز باید انتخاب شود.');
            return;
        }
        var msg = (type==='grant') ?
          'آیا مطمئن هستید این مجوز(ها) برای کاربر(ان) یا گروه(های) انتخابی فعال شود؟' :
          'آیا مطمئن هستید این مجوز(ها) از کاربر(ان) یا گروه(های) انتخابی حذف شود؟';
        document.getElementById('actionType').value = type;
        var modal = new bootstrap.Modal(document.getElementById('confirmModal'), {});
        document.getElementById('confirmModalBody').innerText = msg;
        document.getElementById('modalSubmitBtn').onclick = function() {
            modal.hide();
            form.submit();
        };
        modal.show();
    }
</script>
</body>
</html>