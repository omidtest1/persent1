<?php
// ایمپورت اتصال دیتابیس از config.php
require_once 'config.php';

/**
 * دریافت تمام حصارهای فعال با اعتبارسنجی داده‌ها
 * @return array آرایه‌ای از حصارهای معتبر
 */
function getActiveGeoFences2() {
    global $pdo; // استفاده از اتصال دیتابیس ایجاد شده در config.php
    
    try {
        // دریافت تمام حصارهای فعال
        $stmt = $pdo->query("SELECT * FROM geo_fences WHERE is_deleted = 0");
        $fences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // فیلتر کردن حصارهای نامعتبر
        return array_filter($fences, function($fence) {
            // بررسی وجود مختصات
            if (empty($fence['coordinates'])) {
                return false;
            }
            
            // دیکد کردن JSON
            $decoded = json_decode($fence['coordinates'], true);
            
            // اگر داده دارای سطح بیشتری باشد (مانند [[{lat:x, lng:y}, ...]])
            if ($decoded && is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                $decoded = $decoded[0];
            }
            
            // اعتبارسنجی بر اساس نوع حصار
            if ($fence['fence_type'] === 'circle') {
                // برای حصارهای دایره‌ای: lat, lng و radius باید معتبر باشند
                return isset($decoded['lat']) && 
                       isset($decoded['lng']) && 
                       is_numeric($decoded['lat']) && 
                       is_numeric($decoded['lng']) && 
                       (!empty($fence['radius']) && is_numeric($fence['radius']));
            } else {
                // برای حصارهای چندضلعی: حداقل ۳ نقطه با فرمت {lat:x, lng:y}
                return is_array($decoded) && 
                       count($decoded) >= 3 && 
                       array_reduce($decoded, function($valid, $point) {
                           return $valid && 
                                  is_array($point) && 
                                  isset($point['lat']) && 
                                  isset($point['lng']) && 
                                  is_numeric($point['lat']) && 
                                  is_numeric($point['lng']);
                       }, true);
            }
        });
    } catch (PDOException $e) {
        // ثبت خطا در فایل لگ
        error_log('خطا در دریافت حصارها: ' . $e->getMessage());
        return [];
    }
}


























// دریافت تمام حصارهای فعال با اعتبارسنجی داده‌ها
function getActiveGeoFences1() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM geo_fences WHERE is_deleted = 0");
        $fences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_filter($fences, function($fence) {
            if (empty($fence['coordinates'])) return false;
            
            $decoded = json_decode($fence['coordinates'], true);
            if ($decoded && is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                $decoded = $decoded[0];
            }

            if ($fence['fence_type'] === 'circle') {
                return isset($decoded['lat']) && 
                       isset($decoded['lng']) && 
                       is_numeric($decoded['lat']) && 
                       is_numeric($decoded['lng']) && 
                       (!empty($fence['radius']) && is_numeric($fence['radius']));
            } else {
                return is_array($decoded) && 
                       count($decoded) >= 3 && 
                       array_reduce($decoded, function($valid, $point) {
                           return $valid && 
                                  is_array($point) && 
                                  isset($point['lat']) && 
                                  isset($point['lng']) && 
                                  is_numeric($point['lat']) && 
                                  is_numeric($point['lng']);
                       }, true);
            }
        });
    } catch (PDOException $e) {
        error_log('خطا در دریافت حصارها: ' . $e->getMessage());
        return [];
    }
}

// بررسی وضعیت کاربر در حصارها (برای حالت بدون نقشه)
function checkUserInGeofences1($latitude, $longitude) {
    $fences = getActiveGeoFences();
    $result = [];

    foreach ($fences as $fence) {
        try {
            $rawCoordinates = json_decode($fence['coordinates'], true);
            
            if ($fence['fence_type'] === 'circle') {
                $distance = calculateDistance(
                    $latitude, $longitude,
                    $rawCoordinates['lat'], $rawCoordinates['lng']
                );
                if ($distance <= $fence['radius']) {
                    $result[] = $fence['name'];
                }
            } else {
                $convertedCoords = convertCoordinates($rawCoordinates);
                if (count($convertedCoords) >= 3 && isPointInPolygon([$latitude, $longitude], $convertedCoords)) {
                    $result[] = $fence['name'];
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $result;
}

// تابع محاسبه فاصله جغرافیایی (برای دایره)
function calculateDistance3($lat1, $lon1, $lat2, $lon2) {
    $R = 6371e3; // شعاع زمین بر حسب متر
    $φ1 = deg2rad($lat1);
    $φ2 = deg2rad($lat2);
    $Δφ = deg2rad($lat2 - $lat1);
    $Δλ = deg2rad($lon2 - $lon1);

    $a = sin($Δφ/2) * sin($Δφ/2) +
         cos($φ1) * cos($φ2) *
         sin($Δλ/2) * sin($Δλ/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $R * $c; // فاصله بر حسب متر
}

// تبدیل مختصات (استفاده شده در checkUserInGeofences)
function convertCoordinates4($coords) {
    if (!is_array($coords)) return [];
    
    if (isset($coords[0]) && is_array($coords[0])) {
        $coords = $coords[0];
    }
    
    return array_map(function($coord) {
        if (isset($coord['lat'], $coord['lng']) && is_numeric($coord['lat']) && is_numeric($coord['lng'])) {
            return [(float)$coord['lat'], (float)$coord['lng']];
        }
        return null;
    }, array_filter($coords));
}

// الگوریتم Ray Casting برای بررسی وجود نقطه در چندضلعی
function isPointInPolygon5($point, $polygon) {
    $x = $point[1]; $y = $point[0];
    $inside = false;
    
    for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
        $xi = $polygon[$i][1]; $yi = $polygon[$i][0];
        $xj = $polygon[$j][1]; $yj = $polygon[$j][0];
        
        $intersect = ((($yi > $y) != ($yj > $y)) && 
                      ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi));
        
        if ($intersect) $inside = !$inside;
    }
    
    return $inside;
}





 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
  
// دریافت تمام حصارهای فعال با اعتبارسنجی داده‌ها
function getActiveGeoFences() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM geo_fences WHERE is_deleted = 0");
        $fences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_filter($fences, function($fence) {
            if (empty($fence['coordinates'])) return false;
            
            $decoded = json_decode($fence['coordinates'], true);
            if ($decoded && is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                $decoded = $decoded[0];
            }

            if ($fence['fence_type'] === 'circle') {
                return isset($decoded['lat']) && 
                       isset($decoded['lng']) && 
                       is_numeric($decoded['lat']) && 
                       is_numeric($decoded['lng']) && 
                       (!empty($fence['radius']) && is_numeric($fence['radius']));
            } else {
                return is_array($decoded) && 
                       count($decoded) >= 3 && 
                       array_reduce($decoded, function($valid, $point) {
                           return $valid && 
                                  is_array($point) && 
                                  isset($point['lat']) && 
                                  isset($point['lng']) && 
                                  is_numeric($point['lat']) && 
                                  is_numeric($point['lng']);
                       }, true);
            }
        });
    } catch (PDOException $e) {
        error_log('خطا در دریافت حصارها: ' . $e->getMessage());
        return [];
    }
}

// بررسی وضعیت کاربر در حصارها (برای حالت بدون نقشه)
function checkUserInGeofences($latitude, $longitude) {
    $fences = getActiveGeoFences();
    $result = [];

    foreach ($fences as $fence) {
        try {
            $rawCoordinates = json_decode($fence['coordinates'], true);
            
            if ($fence['fence_type'] === 'circle') {
                $distance = calculateDistance(
                    $latitude, $longitude,
                    $rawCoordinates['lat'], $rawCoordinates['lng']
                );
                if ($distance <= $fence['radius']) {
                    $result[] = $fence['name'];
                }
            } else {
                $convertedCoords = convertCoordinates($rawCoordinates);
                if (count($convertedCoords) >= 3 && isPointInPolygon([$latitude, $longitude], $convertedCoords)) {
                    $result[] = $fence['name'];
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $result;
}

// تابع محاسبه فاصله جغرافیایی (برای دایره)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371e3;
    $φ1 = deg2rad($lat1);
    $φ2 = deg2rad($lat2);
    $Δφ = deg2rad($lat2 - $lat1);
    $Δλ = deg2rad($lon2 - $lon1);

    $a = sin($Δφ/2)*sin($Δφ/2) + cos($φ1)*cos($φ2)*sin($Δλ/2)*sin($Δλ/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// تبدیل مختصات (استفاده شده در checkUserInGeofences)
function convertCoordinates($coords) {
    if (!is_array($coords)) return [];
    
    if (isset($coords[0]) && is_array($coords[0])) {
        $coords = $coords[0];
    }
    
    return array_map(function($coord) {
        if (isset($coord['lat'], $coord['lng']) && is_numeric($coord['lat']) && is_numeric($coord['lng'])) {
            return [(float)$coord['lat'], (float)$coord['lng']];
        }
        return null;
    }, array_filter($coords));
}

// الگوریتم Ray Casting برای بررسی وجود نقطه در چندضلعی
function isPointInPolygon($point, $polygon) {
    $x = $point[1]; $y = $point[0];
    $inside = false;
    
    for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
        $xi = $polygon[$i][1]; $yi = $polygon[$i][0];
        $xj = $polygon[$j][1]; $yj = $polygon[$j][0];
        $intersect = ((($yi > $y) != ($yj > $y)) && 
                     ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi));
        if ($intersect) $inside = !$inside;
    }
    return $inside;
}
?>

 