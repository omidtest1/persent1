<?php
/**
 * صفحه ورود دستی مختصات حصار جغرافیایی
 * Manual coordinates entry page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/classes/GeoFence.php';

// دریافت لیست جلسات فعال
$meetings = getActiveMeetings();

// اگر فرم ارسال شده باشد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $geoFence = new GeoFence();
    
    $data = [
        'meeting_id' => $_POST['meeting_id'],
        'name' => $_POST['name'],
        'fence_type' => $_POST['fence_type'],
        'coordinates' => $_POST['coordinates'],
        'radius' => $_POST['fence_type'] === 'circle' ? $_POST['radius'] : null
    ];
    
    if ($geoFence->createFence($data)) {
        $success = "حصار با موفقیت ایجاد شد.";
    } else {
        $error = "خطا در ایجاد حصار. لطفاً مجدداً تلاش نمایید.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود دستی مختصات حصار</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@5.3.0/dist/css/bootstrap-rtl.min.css">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title">ورود دستی مختصات حصار جغرافیایی</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="meetingSelect" class="form-label">جلسه</label>
                                <select class="form-select" id="meetingSelect" name="meeting_id" required>
                                    <option value="">-- انتخاب جلسه --</option>
                                    <?php foreach ($meetings as $meeting): ?>
                                        <option value="<?= $meeting['id'] ?>">
                                            <?= htmlspecialchars($meeting['title']) ?> (<?= date('Y-m-d', strtotime($meeting['start_time'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fenceName" class="form-label">نام حصار</label>
                                <input type="text" class="form-control" id="fenceName" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">نوع حصار</label>
                                <select class="form-select" id="fenceType" name="fence_type" required>
                                    <option value="polygon">چندضلعی</option>
                                    <option value="circle">دایره</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="circleRadiusContainer">
                                <label for="radius" class="form-label">شعاع دایره (متر)</label>
                                <input type="number" class="form-control" id="radius" name="radius" min="10">
                            </div>
                            
                            <div class="mb-3" id="polygonCoordsContainer">
                                <label for="coordinates" class="form-label">مختصات چندضلعی (JSON)</label>
                                <textarea class="form-control" id="coordinates" name="coordinates" rows="5" required></textarea>
                                <small class="text-muted">مثال برای چندضلعی: [[35.6892,51.3890],[35.6892,51.3890],[35.6892,51.3890]]</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">ذخیره حصار</button>
                                <a href="index.php" class="btn btn-outline-secondary">بازگشت به نقشه</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // نمایش/پنهان کردن فیلدهای مربوط به نوع حصار
            $('#fenceType').change(function() {
                if ($(this).val() === 'circle') {
                    $('#circleRadiusContainer').show();
                    $('#polygonCoordsContainer').hide();
                } else {
                    $('#circleRadiusContainer').hide();
                    $('#polygonCoordsContainer').show();
                }
            }).trigger('change');
        });
    </script>
</body>
</html>