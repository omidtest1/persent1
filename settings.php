<?php
require_once 'config.php';

// عملیات ذخیره تنظیمات
if (isset($_POST['save_settings'])) {
    foreach ($_POST['setting'] as $id => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value=? WHERE id=?");
        $stmt->execute([$value, $id]);
    }
    header("Location: settings.php?success=1");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM settings WHERE is_deleted=0 ORDER BY id ASC");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تنظیمات سامانه</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f5f8fc; font-family: Vazirmatn, Tahoma, sans-serif; }
        .settings-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #0001; padding: 25px; margin: 30px auto; max-width: 900px; }
        .setting-title { font-size: 1.1rem; font-weight: 600; color: #333; }
        .setting-desc { color: #777; font-size: 0.98rem; margin-bottom: 3px;}
        .setting-key { color: #1976d2; font-size: 0.98rem;}
        .setting-row { border-bottom: 1px solid #e0e0e0; padding:18px 0 10px 0;}
        @media (max-width: 600px) {
            .settings-card { padding: 10px; }
            .setting-row { padding:10px 0 8px 0;}
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="settings-card mt-4">
            <div class="d-flex align-items-center mb-4">
                <i class="fa fa-cogs text-primary" style="font-size:2rem"></i>
                <h3 class="mx-2 my-0">تنظیمات اصلی سامانه</h3>
            </div>
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">تنظیمات با موفقیت ذخیره شد.</div>
            <?php endif; ?>
            <form method="post" class="needs-validation" novalidate>
                <?php foreach ($settings as $s): ?>
                <div class="row setting-row align-items-center">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <span class="setting-title"><?= htmlspecialchars($s['title_fa']) ?></span><br>
                        <span class="setting-key">(<?= htmlspecialchars($s['key_name']) ?>)</span>
                        <?php if($s['description']): ?>
                            <div class="setting-desc"><?= nl2br(htmlspecialchars($s['description'])) ?></div>
                        <?php endif; ?>
                        <?php if($s['used_in']): ?>
                            <div class="text-info" style="font-size:0.91rem">صفحه: <?= htmlspecialchars($s['used_in']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-md-8">
                        <?php
                        // نمایش checkbox برای تنظیمات بولی
                        if (in_array(strtolower($s['setting_value']), ['0','1','true','false'])) {
                            $checked = ($s['setting_value']=='1' || strtolower($s['setting_value'])=='true') ? 'checked' : '';
                            echo "<input type='hidden' name='setting[$s[id]]' value='0'>";
                            echo "<input type='checkbox' class='form-check-input me-2' name='setting[$s[id]]' value='1' id='set$s[id]' $checked>";
                            echo "<label for='set$s[id]' class='me-2'>فعال</label>";
                        } else {
                            // ورودی متنی
                            echo "<input type='text' class='form-control' name='setting[$s[id]]' value=\"".htmlspecialchars($s['setting_value'])."\">";
                        }
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="mt-4 text-center">
                    <button class="btn btn-success px-5 py-2" name="save_settings" type="submit"><i class="fa fa-save"></i> ذخیره تنظیمات</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>