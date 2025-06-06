<?php
require_once 'config.php';

$n = isset($_GET['n']) && intval($_GET['n']) > 0 ? intval($_GET['n']) : 20;

 $only_present ='1';
 if(!empty($_GET['only_present']) && isset($_GET['only_present']) ){ 
	$only_present =$_GET['only_present'] ;
 }/*else if( empty($_GET['only_present']) && !isset($_GET['only_present']) ){  
	$only_present ='1';
 }*/
 
// آخرین جلسه فعال
$stmt = $pdo->prepare("SELECT id FROM meetings WHERE status='active' AND is_deleted=0 ORDER BY start_time DESC LIMIT 1");
$stmt->execute();
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);
$meeting_id = $meeting ? $meeting['id'] : 0;

// آمار کلی اعضا و به تفکیک جنسیت
$total_members = $pdo->query("SELECT COUNT(*) FROM members WHERE is_deleted=0")->fetchColumn();
$total_male = $pdo->query("SELECT COUNT(*) FROM members WHERE is_deleted=0 AND gender='1'")->fetchColumn();
$total_female = $pdo->query("SELECT COUNT(*) FROM members WHERE is_deleted=0 AND gender='2'")->fetchColumn();

// آمار حاضرین و غایبین و حاضرین به تفکیک جنسیت
$total_present = $total_present_male = $total_present_female = 0;
$total_absent = $total_absent_male = $total_absent_female = 0;
$present_members = [];

if ($meeting_id) {
    // فقط اعضای حاضر در جلسه (آخرین وضعیتشان 'in')
    $sql = "SELECT m.id, m.first_name, m.last_name, m.birth_date, m.gender, 
                   TIMESTAMPDIFF(YEAR, m.birth_date, CURDATE()) as age
            FROM members m
            JOIN (
                SELECT a1.member_id, a1.current_status
                FROM attendances a1
                INNER JOIN (
                    SELECT member_id, MAX(id) as max_id
                    FROM attendances
                    WHERE meeting_id=? AND is_deleted=0
                    GROUP BY member_id
                ) a2 ON a1.member_id = a2.member_id AND a1.id = a2.max_id
                WHERE a1.meeting_id=? AND a1.is_deleted=0
            ) last_att ON last_att.member_id = m.id
            WHERE m.is_deleted=0 AND last_att.current_status='in' AND m.birth_date IS NOT NULL
            ORDER BY m.birth_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meeting_id, $meeting_id]);
    $present_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // آمار تعداد حاضرین به تفکیک جنسیت
    foreach ($present_members as $m) {
        if ($m['gender'] == '1') $total_present_male++;
        elseif ($m['gender'] == '2') $total_present_female++;
    }
    $total_present = count($present_members);

    // غایبین = کل - حاضرین
    $total_absent = $total_members - $total_present;
    $total_absent_male = $total_male - $total_present_male;
    $total_absent_female = $total_female - $total_present_female;
}

// -- انتخاب لیست مرجع برای محاسبات (کل اعضا یا فقط حاضرین)
if ($only_present=='1' && $meeting_id) {
    $base_members = $present_members;
} else {
    // کل اعضا با تولد معتبر
    $sql = "SELECT id, first_name, last_name, birth_date, gender, TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) as age
            FROM members
            WHERE is_deleted=0 AND birth_date IS NOT NULL
            ORDER BY birth_date ASC";
    $base_members = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// جوان‌ترین n نفر (همه، آقا، خانم)
$youngest_all = $base_members;
usort($youngest_all, function($a,$b){ return strtotime($b['birth_date']) - strtotime($a['birth_date']); });
$youngest_all = array_slice($youngest_all, 0, $n);

$youngest_male = array_filter($base_members, function($m){ return $m['gender']=='1'; });
$youngest_male = array_values($youngest_male);
usort($youngest_male, function($a,$b){ return strtotime($b['birth_date']) - strtotime($a['birth_date']); });
$youngest_male = array_slice($youngest_male, 0, $n);

$youngest_female = array_filter($base_members, function($m){ return $m['gender']=='2'; });
$youngest_female = array_values($youngest_female);
usort($youngest_female, function($a,$b){ return strtotime($b['birth_date']) - strtotime($a['birth_date']); });
$youngest_female = array_slice($youngest_female, 0, $n);

// مسن‌ترین n نفر (همه، آقا، خانم)
$oldest_all = $base_members;
usort($oldest_all, function($a,$b){ return strtotime($a['birth_date']) - strtotime($b['birth_date']); });
$oldest_all = array_slice($oldest_all, 0, $n);

$oldest_male = array_filter($base_members, function($m){ return $m['gender']=='1'; });
$oldest_male = array_values($oldest_male);
usort($oldest_male, function($a,$b){ return strtotime($a['birth_date']) - strtotime($b['birth_date']); });
$oldest_male = array_slice($oldest_male, 0, $n);

$oldest_female = array_filter($base_members, function($m){ return $m['gender']=='2'; });
$oldest_female = array_values($oldest_female);
usort($oldest_female, function($a,$b){ return strtotime($a['birth_date']) - strtotime($b['birth_date']); });
$oldest_female = array_slice($oldest_female, 0, $n);

// لیست n نفر وسط لیست (میانگین سنی به معنای نفرات وسط لیست سن)
$middle_all = [];
$middle_male = [];
$middle_female = [];
$sorted_all = $base_members;
usort($sorted_all, function($a,$b){ return strtotime($a['birth_date']) - strtotime($b['birth_date']); });
$total = count($sorted_all);
if($total > 0) {
    $mid_count = min($n, $total);
    $mid_center = intval($total/2);
    $start = max(0, $mid_center - intval($mid_count/2));
    $middle_all = array_slice($sorted_all, $start, $mid_count);
}

// لیست n نفر وسط آقایان
$sorted_male = array_values(array_filter($sorted_all, function($m){ return $m['gender']=='1'; }));
$total_m = count($sorted_male);
if($total_m > 0) {
    $mid_count = min($n, $total_m);
    $mid_center = intval($total_m/2);
    $start = max(0, $mid_center - intval($mid_count/2));
    $middle_male = array_slice($sorted_male, $start, $mid_count);
}

// لیست n نفر وسط خانم‌ها
$sorted_female = array_values(array_filter($sorted_all, function($m){ return $m['gender']=='2'; }));
$total_f = count($sorted_female);
if($total_f > 0) {
    $mid_count = min($n, $total_f);
    $mid_center = intval($total_f/2);
    $start = max(0, $mid_center - intval($mid_count/2));
    $middle_female = array_slice($sorted_female, $start, $mid_count);
}

// اعضایی که جلسه را ترک کرده‌اند و بازنگشته‌اند
$left_members = [];
if ($meeting_id) {
    $sql = "SELECT m.first_name, m.last_name, m.birth_date 
        FROM members m
        JOIN attendances a ON a.member_id = m.id AND a.meeting_id = ? AND a.is_deleted=0
        WHERE m.is_deleted=0 AND a.current_status='out'
        AND NOT EXISTS (
            SELECT 1 FROM attendances a2 
              WHERE a2.member_id = m.id 
                AND a2.meeting_id = ? 
                AND a2.is_deleted=0 
                AND a2.current_status='in'
                AND a2.id > a.id
        )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meeting_id, $meeting_id]);
    $left_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// تعداد برگه‌های رای توضیع شده
$vote_papers_count = 0;
if ($meeting_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vote_papers WHERE meeting_id=? AND is_issued=1 AND is_deleted=0");
    $stmt->execute([$meeting_id]);
    $vote_papers_count = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش آماری اعضا و حضور و غیاب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#e3f0ff 0,#f6f6ff 100%) !important; font-size: 1.15rem;}
        .report-box { background:#fff;border-radius:18px;box-shadow:0 4px 32px #6ab2ff29;padding:36px;max-width:1000px;margin:35px auto; }
        .stat-title { color:#1976d2;font-size:1.27rem;margin-bottom:8px;font-weight: bold;}
        .stat-list { list-style-type:none;padding:0; font-size:1.1rem;}
        .stat-list li { padding:4px 0; }
        .age-box {border:1px solid #e0e0e0;border-radius:13px;background:#fafdfe;padding:18px;min-height:200px;}
        .present-card {background: linear-gradient(90deg,#e1ffe5,#d5fff4);}
        .absent-card {background: linear-gradient(90deg,#fff4f2,#ffe1e1);}
        .info-card {background: linear-gradient(90deg,#e5e9fa,#d3ebff);}
        .vote-card {background: linear-gradient(90deg,#fffed9,#e1f7d9);}
        .list-btns {margin-bottom:10px;}
        .list-btns button {margin-left:8px;margin-bottom:4px;}
        .member-list-modal .modal-dialog {max-width:460px;}
        .member-list-modal .modal-body {max-height:65vh;overflow-y:auto;}
        .member-list-modal .list-group-item {font-size:1.15rem;display:flex;justify-content:space-between;}
        .member-list-modal .birth {color:#1976d2;font-size:1rem;}
        .member-list-modal .modal-title {font-size:1.19rem;}
        .card-header {font-size:1.12rem;font-weight:bold;}
        .highlight {color: #fff; background-color: #1976d2; padding: 2px 7px; border-radius: 7px;}
        @media (max-width:600px) {
            .report-box{padding:8px;}
            .age-box {padding:7px;}
            .stat-list {font-size:0.99rem;}
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="report-box">
        <h3 class="mb-4 text-center text-primary" style="font-size:2rem;">گزارش آماری اعضا و حضور/غیاب جلسه</h3>
       
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div class="card info-card shadow">
                    <div class="card-header bg-transparent">آمار کلی اعضا</div>
                    <ul class="stat-list card-body">
                        <li><b>کل اعضا:</b> <?= $total_members ?></li>
                        <li><b>آقا:</b> <?= $total_male ?></li>
                        <li><b>خانم:</b> <?= $total_female ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card present-card shadow">
                    <div class="card-header bg-transparent">حاضرین جلسه</div>
                    <ul class="stat-list card-body">
                        <li><b>کل حاضرین:</b> <?= $total_present ?></li>
                        <li><b>آقا:</b> <?= $total_present_male ?></li>
                        <li><b>خانم:</b> <?= $total_present_female ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card absent-card shadow">
                    <div class="card-header bg-transparent">غایبین جلسه</div>
                    <ul class="stat-list card-body">
                        <li><b>کل غایبین:</b> <?= $total_absent ?></li>
                        <li><b>آقا:</b> <?= $total_absent_male ?></li>
                        <li><b>خانم:</b> <?= $total_absent_female ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card vote-card shadow">
                    <div class="card-header bg-transparent">آمار برگه‌های رأی</div>
                    <ul class="stat-list card-body">
                        <li><b>تعداد برگه‌های رأی توضیع شده:</b> <span class="text-success"><?= $vote_papers_count ?></span></li>
                    </ul>
                    <div class="card-body pt-0 pb-1">
                        <b>اعضای ترک‌کننده جلسه:</b> <span class="text-danger"><?= count($left_members) ?></span>
                        <button class="btn btn-outline-info btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalLeftMembers"><i class="fa fa-list"></i> مشاهده</button>
                    </div>
                </div>
            </div>
        </div>
 <form class="row g-2 mb-4 align-items-center" method="get">
            <div class="col-auto">
                <label for="n" class="form-label" style="font-size:1.05rem">تعداد افراد در لیست‌ها:</label>
            </div><BR/>
            <div class="col-auto">
                <input type="number" name="n" id="n" value="<?= $n ?>" class="form-control" min="1" style="width:110px;font-size:1.2rem">
            </div>
            <div class="col-auto">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox"   id="chk_only_present" <?= $only_present=='1' ? "checked" : "" ?> onchange="submitReportForm()"  >
                  <label class="form-check-label" for="chk_only_present" style="font-size:1.1rem;">
                    گرفتن لیست جوان‌ترین  ، سن وسط ، و مسن‌ترین فقط از اعضای <span class="highlight">حاضر در جلسه</span> ؟
                  </label>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary px-4">نمایش</button>
            </div>
        </form>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="age-box shadow">
                    <div class="stat-title">جوان‌ترین <?= $n ?> نفر</div>
                    <div class="list-btns">
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('youngest_all')">همه</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('youngest_male')">آقا</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('youngest_female')">خانم</button>
                    </div>
                    <ol style="padding-right:16px;font-size:1.09rem" id="list_youngest">
                        <?php foreach($youngest_all as $m): ?>
                            <li><?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?> <span class="text-secondary">(<?= $m['birth_date'] ?>)</span></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <div class="col-md-4">
                <div class="age-box shadow">
                    <div class="stat-title"> <?= $n ?> نفر وسط لیست سنی</div>
                    <div class="list-btns">
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('middle_all')">همه</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('middle_male')">آقا</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('middle_female')">خانم</button>
                    </div>
                    <ol style="padding-right:16px;font-size:1.09rem" id="list_middle">
                        <?php foreach($middle_all as $m): ?>
                            <li><?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?> <span class="text-secondary">(<?= $m['birth_date'] ?>)</span></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <div class="col-md-4">
                <div class="age-box shadow">
                    <div class="stat-title">مسن‌ترین <?= $n ?> نفر</div>
                    <div class="list-btns">
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('oldest_all')">همه</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('oldest_male')">آقا</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="showMemberList('oldest_female')">خانم</button>
                    </div>
                    <ol style="padding-right:16px;font-size:1.09rem" id="list_oldest">
                        <?php foreach($oldest_all as $m): ?>
                            <li><?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?> <span class="text-secondary">(<?= $m['birth_date'] ?>)</span></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 mb-2">
            <a href="index.php" class="btn btn-outline-primary px-5 py-2" style="font-size:1.15rem"><i class="fa fa-arrow-right"></i> بازگشت به صفحه اصلی</a>
        </div>
    </div>

    <!-- MODALS -->
    <div class="modal fade member-list-modal" id="modalMembers" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title" id="modalMembersTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
          </div>
          <div class="modal-body" id="modalMembersBody"></div>
        </div>
      </div>
    </div>
    <div class="modal fade member-list-modal" id="modalLeftMembers" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">اعضایی که جلسه را ترک کرده‌اند و بازنگشته‌اند</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
          </div>
          <div class="modal-body">
            <?php if(count($left_members)): ?>
                <ul class="list-group">
                  <?php foreach($left_members as $m): ?>
                    <li class="list-group-item"><?= htmlspecialchars($m['first_name']." ".$m['last_name']) ?>
                        <span class="birth"><?= htmlspecialchars($m['birth_date']) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-danger text-center">هیچ عضوی جلسه را ترک نکرده است.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var youngest_all = <?= json_encode($youngest_all) ?>;
    var youngest_male = <?= json_encode($youngest_male) ?>;
    var youngest_female = <?= json_encode($youngest_female) ?>;
    var oldest_all = <?= json_encode($oldest_all) ?>;
    var oldest_male = <?= json_encode($oldest_male) ?>;
    var oldest_female = <?= json_encode($oldest_female) ?>;
    var middle_all = <?= json_encode($middle_all) ?>;
    var middle_male = <?= json_encode($middle_male) ?>;
    var middle_female = <?= json_encode($middle_female) ?>;

    function showMemberList(type){
        var arr = window[type] || [];
        var title = '';
        if(type==='youngest_all') title='جوان‌ترین‌ها';
        if(type==='youngest_male') title='جوان‌ترین آقایان';
        if(type==='youngest_female') title='جوان‌ترین خانم‌ها';
        if(type==='oldest_all') title='مسن‌ترین‌ها';
        if(type==='oldest_male') title='مسن‌ترین آقایان';
        if(type==='oldest_female') title='مسن‌ترین خانم‌ها';
        if(type==='middle_all') title='افراد وسط لیست سنی';
        if(type==='middle_male') title='افراد وسط لیست سنی (آقا)';
        if(type==='middle_female') title='افراد وسط لیست سنی (خانم)';
        var html = '';
        if(arr.length) {
            html += '<ul class="list-group">';
            for(var i=0;i<arr.length;i++)
                html += '<li class="list-group-item">'+arr[i].first_name+' '+arr[i].last_name+'<span class="birth">'+arr[i].birth_date+'</span></li>';
            html += '</ul>';
        } else {
            html = '<div class="text-danger text-center">موردی یافت نشد.</div>';
        }
        document.getElementById('modalMembersTitle').innerText = title;
        document.getElementById('modalMembersBody').innerHTML = html;
        var modal = new bootstrap.Modal(document.getElementById('modalMembers'));
        modal.show();
    }
	
	
	
	
	
function submitReportForm(extra = {}) {
	
	//submitReportForm({'custom_param':'myvalue'})
	//"submitReportForm({'foo':'bar', 'abc':'123'})"
	
    var form = document.forms[0]; // یا document.getElementById('id-form') اگر id دادی
    // مقادیر اضافی را اضافه کن
   /* for (const key in extra) {
        let hidden = form.querySelector('input[name="'+key+'"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = key;
            form.appendChild(hidden);
        }
        hidden.value = extra[key];
    }*/
	var check=0;  
	
	
	var checkBox = document.getElementById("chk_only_present");
	if (checkBox.checked == true){
           check=1;
          //  console.log("چک باکس تیک خورده");
  } else {
           check=2;  
     //console.log("چک باکس تیک برداشته شد");
  }
  
  		let hidden = form.querySelector('input[name="only_present"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'only_present';
            form.appendChild(hidden);
        }
        hidden.value = check;
 
  form.submit();
}
	
	
	
</script>
</body>
</html>