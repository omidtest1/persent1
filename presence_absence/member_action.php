<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$force = isset($_POST['force']) ? intval($_POST['force']) : 0;
$barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';
$current_user = isset($_SESSION['user']) ? $_SESSION['user'] : ['username'=>'demo','role'=>'admin','id'=>1];
$is_admin = $current_user && $current_user['role'] === 'admin';

$stmt = $pdo->prepare("SELECT id FROM meetings WHERE status='active' AND is_deleted=0 ORDER BY start_time DESC LIMIT 1");
$stmt->execute();
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$meeting){
    echo json_encode(['message'=>'هیچ جلسه فعالی وجود ندارد.','refresh'=>false]);
    exit;
}
$meeting_id = $meeting['id'];

$stmt = $pdo->prepare("SELECT * FROM members WHERE id=? AND is_deleted=0");
$stmt->execute([$member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$member){
    echo json_encode(['message'=>'عضو مورد نظر یافت نشد!','refresh'=>false]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM attendances WHERE member_id=? AND meeting_id=? AND is_deleted=0");
$stmt->execute([$member_id, $meeting_id]);
$att = $stmt->fetch(PDO::FETCH_ASSOC);

function member_fullname($m) {
    return htmlspecialchars($m['first_name'].' '.$m['last_name']);
}
function member_photo($m) {
    $photo = ($m['photo_path'] && file_exists("memberPic/{$m['photo_path']}")) ? "memberPic/{$m['photo_path']}" : "memberPic/default.png";
    return $photo;
}

// عملیات ورود
if($action === "checkin") {
    if($att && $att['current_status']=='in') {
        if($config['modal_confirm_checkin_again'] && !$force) {
            $photo = member_photo($member);
            echo json_encode([
                'confirm_modal'=>true,
                'modal_title'=>"تایید ثبت ورود مجدد",
                'modal_body'=>"<div class='mb-2'><b>{$member['first_name']} {$member['last_name']}</b> قبلاً ورود ثبت شده.<br>آیا مطمئن هستید مجدد ورود ثبت شود؟</div>
                               <img src='$photo' style='max-width:100px;border-radius:10px;'>",
                'modal_footer'=>"<button class='btn btn-success modal-confirm-btn' data-id='$member_id' data-action='checkin' data-force='1'>تایید ثبت ورود مجدد</button>"
            ]);
            exit;
        }
        if($config['admin_only_checkin_again'] && !$is_admin) {
            echo json_encode(['message'=>'فقط ادمین می‌تواند ورود مجدد ثبت کند!','refresh'=>false]);
            exit;
        }
    }
    if($att) {
        $stmt = $pdo->prepare("UPDATE attendances SET check_in=NOW(3), current_status='in', is_deleted=0, updated_at=NOW(3) WHERE id=?");
        $stmt->execute([$att['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendances (member_id, meeting_id, check_in, current_status, is_deleted, created_at) VALUES (?,?,?,?,0,NOW(3))");
        $stmt->execute([$member_id, $meeting_id, date('Y-m-d H:i:s.u'), 'in']);
    }
    echo json_encode(['message'=>"ورود با موفقیت ثبت شد",'refresh'=>true]);
    exit;
}

// عملیات خروج
if($action === "checkout") {
    if(!$att || $att['current_status']!='in') {
        if($config['modal_confirm_checkout_without_checkin'] && !$force) {
            echo json_encode([
                'confirm_modal'=>true,
                'modal_title'=>"تایید ثبت خروج بدون ورود",
                'modal_body'=>"عضو <b>".member_fullname($member)."</b> هنوز ورود ثبت نکرده. آیا می‌خواهید خروج ثبت شود؟",
                'modal_footer'=>"<button class='btn btn-danger modal-confirm-btn' data-id='$member_id' data-action='checkout' data-force='1'>تایید ثبت خروج</button>"
            ]);
            exit;
        }
        if($config['admin_only_checkout_without_checkin'] && !$is_admin) {
            echo json_encode(['message'=>'فقط ادمین می‌تواند خروج بدون ورود ثبت کند!','refresh'=>false]);
            exit;
        }
    }
    if($att && $att['current_status']=='out') {
        if($config['modal_confirm_checkout_again'] && !$force) {
            echo json_encode([
                'confirm_modal'=>true,
                'modal_title'=>"تایید ثبت خروج مجدد",
                'modal_body'=>"عضو <b>".member_fullname($member)."</b> قبلاً خروج ثبت شده. آیا می‌خواهید مجدداً خروج ثبت شود؟",
                'modal_footer'=>"<button class='btn btn-danger modal-confirm-btn' data-id='$member_id' data-action='checkout' data-force='1'>تایید ثبت خروج مجدد</button>"
            ]);
            exit;
        }
        if($config['admin_only_checkout_again'] && !$is_admin) {
            echo json_encode(['message'=>'فقط ادمین می‌تواند خروج مجدد ثبت کند!','refresh'=>false]);
            exit;
        }
    }
    if($att) {
        $stmt = $pdo->prepare("UPDATE attendances SET check_out=NOW(3), current_status='out', updated_at=NOW(3) WHERE id=?");
        $stmt->execute([$att['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendances (member_id, meeting_id, check_out, current_status, is_deleted, created_at) VALUES (?,?,?,?,0,NOW(3))");
        $stmt->execute([$member_id, $meeting_id, date('Y-m-d H:i:s.u'), 'out']);
    }
    echo json_encode(['message'=>"خروج با موفقیت ثبت شد",'refresh'=>true]);
    exit;
}

// دریافت برگه رای (مدال و تولید بارکد)
if($action === "vote_paper") {
    $attendance_status = ($att ? $att['current_status'] : 'none');
    $mode = $config['vote_paper_mode'];

    // شمارش ورود و خروج
    $stmt = $pdo->prepare("SELECT COUNT(*) as in_count FROM attendances WHERE member_id=? AND meeting_id=? AND current_status='in' AND is_deleted=0");
    $stmt->execute([$member_id, $meeting_id]);
    $in_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) as out_count FROM attendances WHERE member_id=? AND meeting_id=? AND current_status='out' AND is_deleted=0");
    $stmt->execute([$member_id, $meeting_id]);
    $out_count = $stmt->fetchColumn();

    // وضعیت غیرعادی
    $err_msg = '';
    $abnormal = false;
    if($in_count == 0 && $out_count == 0) {
        $err_msg = "برای این عضو هیچ ورود یا خروجی ثبت نشده! آیا مطمئن هستید می‌خواهید برگه رأی صادر کنید؟";
        $abnormal = true;
    } elseif($in_count > 0 && $in_count == $out_count) {
        $err_msg = "این عضو خروج ثبت کرده است و اکنون در جلسه نیست. آیا مطمئن هستید می‌خواهید برگه رأی صادر کنید؟";
        $abnormal = true;
    } elseif($in_count > $out_count + 1) {
        $err_msg = "تعداد ورودهای این عضو بیشتر از خروج‌هاست! لطفا بررسی کنید.";
        $abnormal = true;
    } elseif($out_count > $in_count) {
        $err_msg = "تعداد خروج‌های این عضو بیشتر از ورودهاست! لطفا بررسی کنید.";
        $abnormal = true;
    }

    if($abnormal && $config['modal_confirm_vote_paper'] && !$force) {
        echo json_encode([
            'confirm_modal'=>true,
            'modal_title'=>"تایید صدور برگه رأی",
            'modal_body'=>$err_msg,
            'modal_footer'=>"<button class='btn btn-warning modal-confirm-btn' data-id='$member_id' data-action='vote_paper' data-force='1'>تایید صدور برگه رأی</button>"
        ]);
        exit;
    }
    if($abnormal && $config['admin_only_vote_paper_abnormal'] && !$is_admin) {
        echo json_encode(['message'=>'فقط ادمین می‌تواند برگه رأی با وضعیت غیرعادی صادر کند!','refresh'=>false]);
        exit;
    }

    // حالت برگه از قبل چاپ شده
    if($mode === 'preprinted') {
        $body = "<div class='mb-3'><b>لطفا بارکد برگه رأی را اسکن کنید و تایید بزنید:</b></div>
        <input type='text' class='form-control' id='barcodeInput' autofocus autocomplete='off' style='font-size:1.3em;direction:ltr;text-align:center;max-width:220px;margin:auto;' placeholder='بارکد را اسکن کنید'>
        <div class='mt-3' id='barcodeError' style='color:#d32f2f;'></div>
        ";
        $footer = "<button class='btn btn-secondary btn-cancel' data-bs-dismiss='modal'>انصراف</button>
                   <button class='btn btn-success modal-assign-barcode-btn' data-id='$member_id'>ثبت برگه رأی</button>";
        echo json_encode([
            'confirm_modal'=>true,
            'modal_title'=>"ثبت برگه رأی به عضو",
            'modal_body'=>$body,
            'modal_footer'=>$footer,
            'vote_mode'=>'preprinted'
        ]);
        exit;
    }

    // حالت تولید و چاپ سیستم
    if($mode === 'system') {
        $serial = "SN-".str_pad($member_id,8,'0',STR_PAD_LEFT)."-".date('Hi')."-".rand(10,99);
        $barcode = $serial;
        $stmt = $pdo->prepare("INSERT INTO vote_papers (meeting_id, barcode, is_issued, issued_to_user_id, issued_to_member_id, issued_at, is_deleted) VALUES (?,?,?,?,?,?,0)");
        $stmt->execute([
            $meeting_id, $barcode, 1, $current_user['id'], $member_id, date('Y-m-d H:i:s')
        ]);
        $stmt = $pdo->prepare("INSERT INTO votes_log (member_id, admin_username, attendance_status, issued_at, is_confirmed) VALUES (?,?,?,?,1)");
        $attendance_status = ($att ? $att['current_status'] : 'none');
        $stmt->execute([$member_id, $current_user['username'], $attendance_status, date('Y-m-d H:i:s')]);

        $vote_paper_html = '
        <div class="vote-paper" style="width:'.$config['vote_paper_width_mm'].'mm;border:1px solid #222;padding:6px;">
            <div style="text-align:center;">
                <img src="logo.png" style="max-width:60px;"><br>
                <b>برگه رأی مجمع</b>
            </div>
            <hr>
            <div><b>نام:</b> '.htmlspecialchars($member['first_name']).'</div>
            <div><b>نام خانوادگی:</b> '.htmlspecialchars($member['last_name']).'</div>
            <div><b>کد ملی:</b> '.htmlspecialchars($member['national_code']).'</div>
            <div><b>شماره عضویت:</b> '.htmlspecialchars($member['membership_number']).'</div>
            <div><b>شماره سریال:</b> '.$serial.'</div>
            <div class="barcode-wrap" style="margin:10px 0;text-align:center;">
                <canvas id="voteBarcodeCanvas" data-barcode="'.$barcode.'"></canvas>
                <div style="font-size:10pt;letter-spacing:2px">'.$barcode.'</div>
            </div>
            <table border="1" style="width:100%;margin:10px 0;font-size:10pt">
                <tr><td>ردیف</td><td>رأی</td><td>توضیحات</td></tr>
                <tr><td>1</td><td></td><td></td></tr>
                <tr><td>2</td><td></td><td></td></tr>
                <tr><td>3</td><td></td><td></td></tr>
                <tr><td>4</td><td></td><td></td></tr>
                <tr><td>5</td><td></td><td></td></tr>
            </table>
            <div style="font-size:9pt;text-align:center">لطفاً فقط با خودکار آبی یا مشکی پر شود</div>
        </div>';
        echo json_encode([
            'show_vote_paper'=>true,
            'vote_paper_html'=>$vote_paper_html
        ]);
        exit;
    }
}

// ثبت اسکن بارکد برگه رأی برای عضو (فقط preprinted)
if($action === "assign_vote_paper") {
    if(!$barcode) {
        echo json_encode(['message'=>"بارکد را وارد کنید",'refresh'=>false]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM vote_papers WHERE barcode=? AND meeting_id=? AND is_deleted=0");
    $stmt->execute([$barcode, $meeting_id]);
    $vote_paper = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$vote_paper) {
        echo json_encode(['message'=>"برگه رأی با این بارکد وجود ندارد.",'refresh'=>false]);
        exit;
    }
    if($vote_paper['is_issued'] && $vote_paper['issued_to_member_id'] && $vote_paper['issued_to_member_id'] != $member_id) {
        echo json_encode(['message'=>"این برگه رأی قبلاً به عضو دیگری داده شده است!",'refresh'=>false]);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE vote_papers SET is_issued=1, issued_to_user_id=?, issued_to_member_id=?, issued_at=NOW() WHERE id=?");
    $stmt->execute([$current_user['id'], $member_id, $vote_paper['id']]);
    $stmt = $pdo->prepare("INSERT INTO votes_log (member_id, admin_username, attendance_status, issued_at, is_confirmed) VALUES (?,?,?,?,1)");
    $attendance_status = ($att ? $att['current_status'] : 'none');
    $stmt->execute([$member_id, $current_user['username'], $attendance_status, date('Y-m-d H:i:s')]);

    $vote_paper_html = '
    <div class="text-center">
        <h5 class="text-success">برگه رأی با موفقیت ثبت شد!</h5>
        <div style="font-size:16px;margin:10px 0">بارکد: <b>'.$barcode.'</b></div>
    </div>';
    echo json_encode([
        'show_vote_paper'=>true,
        'vote_paper_html'=>$vote_paper_html
    ]);
    exit;
}

if($action === "log_vote_paper_print") {
    echo json_encode(['message'=>'چاپ ثبت شد.','refresh'=>false]);
    exit;
}

// جزئیات عضو و حضور و غیاب و آرای صادره
if($action === "details") {
    // حضور و غیاب‌های ثبت شده
    $stmt = $pdo->prepare("SELECT * FROM attendances WHERE member_id=? AND is_deleted=0 ORDER BY created_at DESC");
    $stmt->execute([$member_id]);
    $all_atts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // لاگ برگه‌های رأی
    $stmt = $pdo->prepare("SELECT * FROM votes_log WHERE member_id=? ORDER BY issued_at DESC");
    $stmt->execute([$member_id]);
    $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // آخرین برگه رأی
    $stmt = $pdo->prepare("SELECT * FROM vote_papers WHERE issued_to_member_id=? AND meeting_id=? AND is_deleted=0");
    $stmt->execute([$member_id, $meeting_id]);
    $vote_paper = $stmt->fetch(PDO::FETCH_ASSOC);

    $current_status = 'غایب';
    $last_att = null;
    if(count($all_atts)) $last_att = $all_atts[0];
    if($last_att && $last_att['current_status']=='in') $current_status = 'حاضر';

    $vote_status = ($vote_paper ? "دریافت کرده است (بارکد: ".htmlspecialchars($vote_paper['barcode']).")" : "دریافت نکرده است");
    $photo = member_photo($member);
    $photo_btn = "<button class='btn btn-outline-primary details-photo-link' tabindex='0' data-full='$photo'><i class='fa fa-image'></i> نمایش عکس عضو</button>";

    $body = "<div style='font-size:16px'><b>نام و نام خانوادگی:</b> ".member_fullname($member)."</div>";
    $body .= "<div class='mt-2'>$photo_btn</div>";
    $body .= "<div class='mt-3'><b>وضعیت فعلی:</b> $current_status</div>";
    $body .= "<div><b>وضعیت برگه رأی:</b> $vote_status</div>";
    $body .= "<hr><b>لیست ورود و خروج‌ها:</b><ul>";
    foreach($all_atts as $att_i) {
        $body .= "<li>ورود: ".($att_i['check_in'] ? $att_i['check_in'] : '-')." | خروج: ".($att_i['check_out'] ? $att_i['check_out'] : '-')." | وضعیت: ".($att_i['current_status']=='in'?'حاضر':'غایب')."</li>";
    }
    $body .= "</ul>";
    $body .= "<hr><b>لیست برگه‌های رأی:</b><ul>";
    foreach($votes as $v) {
        $body .= "<li>توسط: ".htmlspecialchars($v['admin_username'])." | وضعیت حضور: ".$v['attendance_status']." | زمان: ".$v['issued_at']."</li>";
    }
    $body .= "</ul>";

    echo json_encode([
        'confirm_modal'=>true,
        'modal_title'=>"جزئیات عضو",
        'modal_body'=>$body,
        'modal_footer'=>"<button class='btn btn-secondary' data-bs-dismiss='modal'>بستن</button>"
    ]);
    exit;
}

echo json_encode(['message'=>'عملیات نامعتبر!','refresh'=>false]);
exit;
?>