<?php
require_once 'config.php';
$query = isset($_POST['query']) ? trim($_POST['query']) : '';
if(strlen($query) < 2){ echo ''; exit; }
$stmt = $pdo->prepare("SELECT id FROM meetings WHERE status='active' AND is_deleted=0 ORDER BY start_time DESC LIMIT 1");
$stmt->execute();
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$meeting){ echo '<div class="alert alert-danger">هیچ جلسه فعالی یافت نشد.</div>'; exit; }
$meeting_id = $meeting['id'];
$stmt = $pdo->prepare("
    SELECT * FROM members
    WHERE is_deleted=0
      AND (
        first_name LIKE :q OR
        last_name LIKE :q OR
        national_code LIKE :q OR
        membership_number LIKE :q
      )
    ORDER BY id DESC
    LIMIT 20
");
$stmt->execute(['q' => "%$query%"]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(!$members){ echo '<div class="alert alert-info">عضوی با مشخصات وارد شده یافت نشد.</div>'; exit; }
$member_ids = array_column($members, 'id');
$in_query = implode(',', array_fill(0, count($member_ids), '?'));
$stmt = $pdo->prepare("
    SELECT * FROM attendances
    WHERE meeting_id=? AND member_id IN ($in_query) AND is_deleted=0
");
$stmt->execute(array_merge([$meeting_id], $member_ids));
$attendances = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $att){
    $attendances[$att['member_id']] = $att;
}
echo '<div class="table-responsive"><table class="table align-middle table-bordered table-hover text-center bg-white">';
echo '<thead class="table-primary"><tr>
        <th>عکس</th>
        <th>نام</th>
        <th>نام خانوادگی</th>
        <th>کد ملی</th>
        <th>شماره عضویت</th>
        <th>ورود</th>
        <th>خروج</th>
        <th>برگه رأی</th>
        <th>جزئیات</th>
    </tr></thead><tbody>';
foreach($members as $m){
    $att = $attendances[$m['id']] ?? null;
    $pic = ($m['photo_path'] && file_exists("memberPic/{$m['photo_path']}")) ? "memberPic/{$m['photo_path']}" : "memberPic/default.png";
    echo '<tr>';
    echo '<td><img src="'.$pic.'" data-full="'.$pic.'" class="member-photo-thumb" tabindex="0" alt="عکس عضو"></td>';
    echo '<td>'.htmlspecialchars($m['first_name']).'</td>';
    echo '<td>'.htmlspecialchars($m['last_name']).'</td>';
    echo '<td>'.htmlspecialchars($m['national_code']).'</td>';
    echo '<td>'.htmlspecialchars($m['membership_number']).'</td>';
    $btn_in = ($att && $att['current_status']=='in') ? 'btn-secondary' : 'btn-success';
    $btn_out = (!$att || $att['current_status']=='out') ? 'btn-secondary' : 'btn-danger';
    echo '<td>
        <button class="btn '.$btn_in.' action-btn op-btn" data-id="'.$m['id'].'" data-action="checkin">
            <i class="fa fa-door-open"></i> ورود
        </button>
    </td>';
    echo '<td>
        <button class="btn '.$btn_out.' action-btn op-btn" data-id="'.$m['id'].'" data-action="checkout">
            <i class="fa fa-door-closed"></i> خروج
        </button>
    </td>';
    echo '<td>
        <button class="btn btn-warning action-btn op-btn" data-id="'.$m['id'].'" data-action="vote_paper">
            <i class="fa fa-file-alt"></i> دریافت برگه رأی
        </button>
    </td>';
    echo '<td>
        <button class="btn btn-info action-btn op-btn" data-id="'.$m['id'].'" data-action="details">
            <i class="fa fa-list"></i> جزئیات
        </button>
    </td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
?>