<?php


require_once 'functions.php';

// بررسی وضعیت نمایش نقشه
$showMapServer = isMapVisible(); // از سمت سرور
$showMapClient = isset($_COOKIE['showMapClient']) ? $_COOKIE['showMapClient'] === 'true' : true;
$showMap = $showMapServer && $showMapClient;
// دریافت حصارهای فعال از functions.php
// دریافت حصارها
$geofences = getActiveGeoFences();
?>


<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ردیابی حصار جغرافیایی</title>
    
    <!-- Leaflet CSS برای نمایش نقشه -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    
    <!-- Bootstrap CSS برای طراحی واکنش‌گرا -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- استایل شخصی -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- کانتینر اصلی با چیدمان شبکه‌ای -->
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- نقشه -->
             <!-- نقشه (نمایش شرطی) -->
            <?php if ($showMap): ?>
            <div class="col-12 map-container">
                <div id="map"></div>
            </div>
            <?php endif; ?>
            
            <!-- نوار وضعیت -->
            <div class="col-12 status-bar">
                <div class="container py-3">
                    <div class="row justify-content-center">
                        <div class="col-md-6 text-center">
                            <!-- پیغام وضعیت -->
                            <h4 id="status" class="status-text">در حال بارگذاری موقعیت...</h4>
                            
                            <!-- دکمه تازه‌سازی -->
                            <button id="refreshBtn" class="btn btn-primary mt-3">تازه‌سازی موقعیت</button>
							
							
                            <!-- چک‌باکس کنترل نمایش نقشه -->
                            <?php if ($showMapServer): ?>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="toggleMap"<?= $showMapClient ? ' checked' : '' ?>>
                                <label class="form-check-label" for="toggleMap">
                                    نمایش نقشه
                                </label>
                            </div>
                            <?php endif; ?>
							
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



































<!-- Modal های مدیریت خطا -->
<!-- Modal: عدم دسترسی به GPS -->
<!-- Modal: سنسور GPS غیرفعال -->
<!-- Modal: دستگاه بدون GPS -->
<!-- Modal: خطای سرور یا شبکه -->
<!-- Modal: خطای عمومی -->
 
 
 <!-- Modal: دسترسی به موقعیت رد شد -->
<div class="modal fade" id="gpsPermissionModal" tabindex="-1" aria-labelledby="gpsPermissionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="gpsPermissionLabel">دسترسی به موقعیت مکانی</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-geo-alt-slash display-4 text-danger mb-3"></i>
                <p class="lead">دسترسی به موقعیت مکانی رد شد</p>
                <p>لطفاً از منوی تنظیمات مرورگر یا دستگاه، دسترسی موقعیت را فعال کنید.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="checkLocation()">تلاش مجدد</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: سنسور GPS غیرفعال -->
<div class="modal fade" id="gpsDisabledModal" tabindex="-1" aria-labelledby="gpsDisabledLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="gpsDisabledLabel">سنسور GPS غیرفعال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-power display-4 text-warning mb-3"></i>
                <p class="lead">سنسور موقعیت مکانی غیرفعال است</p>
                <p>لطفاً سنسور موقعیت دستگاه خود را فعال کنید.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="checkLocation()">تلاش مجدد</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: دستگاه بدون GPS -->
<div class="modal fade" id="noGpsModal" tabindex="-1" aria-labelledby="noGpsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="noGpsLabel">بدون قابلیت GPS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-ban display-4 text-secondary mb-3"></i>
                <p class="lead">این دستگاه از موقعیت مکانی پشتیبانی نمی‌کند</p>
                <p>لطفاً از یک دستگاه دیگر استفاده کنید.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: خطای شبکه -->
<div class="modal fade" id="networkErrorModal" tabindex="-1" aria-labelledby="networkErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="networkErrorLabel">خطای شبکه</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-wifi-off display-4 text-dark mb-3"></i>
                <p class="lead">مشکلی در اتصال به اینترنت وجود دارد</p>
                <p>لطفاً اتصال خود را بررسی کنید و دوباره امتحان کنید.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="checkLocation()">تلاش مجدد</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: خطای عمومی -->
<div class="modal fade" id="generalErrorModal" tabindex="-1" aria-labelledby="generalErrorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="generalErrorLabel">خطای ناشناخته</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle display-4 text-secondary mb-3"></i>
                <p class="lead">خطایی رخ داده است</p>
                <p>لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="checkLocation()">تلاش مجدد</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نوتیفیکیشن موقت در بالای صفحه -->
<div id="toast" class="toast align-items-center text-white bg-danger border-0 position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
        <div class="toast-body" id="toastMessage"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>































    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- داده‌های حصارها در قالب متغیر JS -->
    <script>
        // داده‌های حصارها به فرمت JSON به JS منتقل می‌شوند
        const geofencesData = <?= json_encode($geofences) ?>;
    </script>
    
    <!-- اسکریپت اصلی برنامه -->
    <script src="script.js"></script>
</body>
</html>