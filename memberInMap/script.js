// متغیرهای سراسری - اولیه‌سازی مقدار اولیه
let map = null; // فقط در صورت فعال بودن نقشه مقداردهی می‌شود
let fenceLayers = []; // لایه‌های حصارها
const fenceColors = ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF']; // رنگ‌های حصارها

// تابع تبدیل مختصات از فرمت JSON دیتابیس به فرمت Leaflet
function convertCoordinates(coords) {
    // بررسی معتبر بودن داده‌ها
    if (!coords || !Array.isArray(coords)) return [];

    // در صورت وجود سطح بیشتری از آرایه، آن را ساده‌سازی کنید
    if (coords.length > 0 && Array.isArray(coords[0])) {
        coords = coords[0];
    }

    // فیلتر کردن نقاط نامعتبر و تبدیل به فرمت [lat, lng]
    return coords
        .filter(coord => 
            coord && 
            typeof coord.lat === 'number' && 
            typeof coord.lng === 'number' && 
            !isNaN(coord.lat) && 
            !isNaN(coord.lng)
        )
        .map(coord => [coord.lat, coord.lng]);
}

// بررسی نقطه در دایره (الگوریتم هاورسین)
function isPointInCircle(point, center, radius) {
    const R = 6371e3; // شعاع زمین بر حسب متر
    const φ1 = point[0] * Math.PI / 180;
    const φ2 = center[0] * Math.PI / 180;
    const Δφ = (center[0] - point[0]) * Math.PI / 180;
    const Δλ = (center[1] - point[1]) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c <= radius;
}

// بررسی نقطه در چندضلعی (الگوریتم Ray Casting)
function isPointInPolygon(point, polygonPoints) {
    if (!polygonPoints || polygonPoints.length < 3) return false;

    let x = point[1], y = point[0];
    let inside = false;

    for (let i = 0, j = polygonPoints.length - 1; i < polygonPoints.length; j = i++) {
        const xi = polygonPoints[i][1], yi = polygonPoints[i][0];
        const xj = polygonPoints[j][1], yj = polygonPoints[j][0];
        const intersect = ((yi > y) !== (yj > y)) && 
                          (x < (xj - xi) * (y - yi) / (yj - yi) + xi);

        if (intersect) inside = !inside;
    }

    return inside;
}

// رسم حصارها - فقط در صورت فعال بودن نقشه اجرا می‌شود
function drawGeoFences() {
    // در صورت غیرفعال بودن نقشه، تابع متوقف می‌شود
    if (!map) return;

    // حذف لایه‌های قبلی
    fenceLayers.forEach(layer => map.removeLayer(layer));
    fenceLayers = [];

    if (!geofencesData || !Array.isArray(geofencesData)) {
        console.error('داده‌های حصارها نامعتبر هستند');
        return;
    }

    geofencesData.forEach((fence, index) => {
        if (!fence || !fence.coordinates) {
            console.warn(`حصار شماره ${index} دارای داده‌های نامعتبر است`);
            return;
        }

        try {
            const rawCoordinates = JSON.parse(fence.coordinates);

            if (fence.fence_type === 'circle') {
                // رسم دایره فقط اگر مختصات معتبر باشد
                if (!rawCoordinates.lat || !rawCoordinates.lng || isNaN(rawCoordinates.lat) || isNaN(rawCoordinates.lng)) {
                    console.warn(`حصار "${fence.name}" مختصات دایره نامعتبر دارد`);
                    return;
                }

                const circle = L.circle([rawCoordinates.lat, rawCoordinates.lng], {
                    radius: parseFloat(fence.radius),
                    color: fenceColors[index % fenceColors.length],
                    fillOpacity: 0.2
                }).addTo(map);

                circle.bindPopup(`<strong>${fence.name}</strong><br>دایره`);
                fenceLayers.push(circle);
            } else {
                // رسم چندضلعی
                const convertedCoords = convertCoordinates(rawCoordinates);

                if (convertedCoords.length < 3) {
                    console.warn(`حصار "${fence.name}" حداقل به 3 نقطه معتبر نیاز دارد`);
                    return;
                }

                // اطمینان از بسته بودن چندضلعی
                if (convertedCoords[0][0] !== convertedCoords[convertedCoords.length-1][0] || 
                    convertedCoords[0][1] !== convertedCoords[convertedCoords.length-1][1]) {
                    convertedCoords.push(convertedCoords[0]);
                }

                const polygon = L.polygon(convertedCoords, {
                    color: fenceColors[index % fenceColors.length],
                    fillOpacity: 0.2
                }).addTo(map);

                polygon.bindPopup(`<strong>${fence.name}</strong><br>چندضلعی<br>تعداد نقاط: ${convertedCoords.length}`);
                fenceLayers.push(polygon);
            }
        } catch (error) {
            console.error(`خطا در پردازش حصار "${fence.name}":`, error);
        }
    });
}

document.getElementById('toggleMap')?.addEventListener('change', function() {
    const showMap = this.checked;
    document.cookie = `showMapClient=${showMap}; path=/; max-age=${60 * 60 * 24 * 30}`; // 30 روز
    location.reload();
});
// بررسی موقعیت کاربر
function checkLocation() {
    const statusElement = document.getElementById('status');
    statusElement.innerHTML = 'در حال تعیین موقعیت...'; // استفاده از innerHTML برای خط‌بندی

    if (!navigator.geolocation) {
        showModal('noGpsModal');
        statusElement.innerHTML = 'این دستگاه از موقعیت مکانی پشتیبانی نمی‌کند';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            // پاک کردن موقعیت قبلی فقط در صورت فعال بودن نقشه
            if (window.userMarker && map) {
                map.removeLayer(window.userMarker);
                map.removeLayer(window.userCircle);
            }

            // مختصات جدید
            const latlng = [position.coords.latitude, position.coords.longitude];

            // ایجاد مارکر کاربر فقط در صورت فعال بودن نقشه
            if (map) {
                window.userMarker = L.marker(latlng, {
                    icon: L.divIcon({
                        className: 'user-icon',
                        html: '<div class="user-marker"><i class="bi bi-geo-alt-fill"></i></div>'
                    })
                }).addTo(map)
                    .bindPopup(`موقعیت فعلی شما<br>Lat: ${latlng[0].toFixed(6)}<br>Lng: ${latlng[1].toFixed(6)}`)
                    .openPopup();

                // دایره دقت موقعیت
                window.userCircle = L.circle(latlng, {
                    radius: position.coords.accuracy,
                    color: '#2563eb',
                    fillOpacity: 0.2
                }).addTo(map);

                // مرکز کردن نقشه روی موقعیت کاربر
                map.setView(latlng, 16);
            }

            // بررسی وجود در حصارها
            let fencesIn = []; // آرایه برای ذخیره نام حصارهایی که کاربر درون آن است

            if (!geofencesData || !Array.isArray(geofencesData)) {
                console.error('داده‌های حصارها نامعتبر هستند');
                statusElement.innerHTML = 'داده‌های حصارها نامعتبر هستند';
                return;
            }

            for (const fence of geofencesData) {
                if (!fence || !fence.coordinates) {
                    console.warn('حصار دارای داده‌های نامعتبر است');
                    continue;
                }

                let isInside = false;

                try {
                    const rawCoordinates = JSON.parse(fence.coordinates);

                    if (fence.fence_type === 'circle') {
                        if (rawCoordinates.lat && rawCoordinates.lng) {
                            isInside = isPointInCircle(
                                latlng, 
                                [rawCoordinates.lat, rawCoordinates.lng], 
                                parseFloat(fence.radius)
                            );
                        }
                    } else {
                        const convertedCoords = convertCoordinates(rawCoordinates);

                        if (convertedCoords.length >= 3) {
                            if (convertedCoords[0][0] !== convertedCoords[convertedCoords.length-1][0] || 
                                convertedCoords[0][1] !== convertedCoords[convertedCoords.length-1][1]) {
                                convertedCoords.push(convertedCoords[0]);
                            }

                            isInside = isPointInPolygon(latlng, convertedCoords);
                        }
                    }

                    if (isInside) {
                        fencesIn.push(fence.name); // اضافه کردن نام حصار به آرایه
                    }
                } catch (error) {
                    console.error(`خطا در بررسی حصار "${fence.name}":`, error);
                }
            }

            // نمایش وضعیت
            if (fencesIn.length > 0) {
                statusElement.innerHTML = `شما در حصارهای زیر هستید:<br><strong>${fencesIn.join('</strong><br><strong>')}</strong>`;
                statusElement.style.color = '#15803d';
            } else {
                statusElement.innerHTML = 'شما در هیچ حصاری نیستید';
                statusElement.style.color = '#b91c1c';
            }
        },
        function(error) { 
		
		
		handleLocationError(error);
            
            // تلاش مجدد برای خطاهای قابل رفع
            if (autoRetry && (error.code === error.POSITION_UNAVAILABLE || error.code === error.TIMEOUT)) {
                setTimeout(() => {
                    checkLocation(false); // فقط یک بار دوباره امتحان کن
                }, 5000);
            }
			
			
			
           /*
            console.error('خطا در تعیین موقعیت:', error);
			switch(error.code) {
                case error.PERMISSION_DENIED:
                    showModal('gpsPermissionModal');
                    statusElement.innerHTML = 'دسترسی به موقعیت مکانی رد شد';
                    statusElement.style.color = '#b91c1c';
                    break;

                case error.POSITION_UNAVAILABLE:
                    showModal('gpsDisabledModal');
                    statusElement.innerHTML = 'موقعیت در دسترس نیست';
                    statusElement.style.color = '#b91c1c';
                    break;

                case error.TIMEOUT:
                    showModal('networkErrorModal');
                    statusElement.innerHTML = 'دریافت موقعیت به پایان رسید';
                    statusElement.style.color = '#b91c1c';
                    break;

                default:
                    showModal('generalErrorModal');
                    statusElement.innerHTML = 'نمی‌توان موقعیت شما را تعیین کرد';
                    statusElement.style.color = '#b91c1c';
            }*/
        },
        {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

// تابع نمایش مدال
function showModal(modalId) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

// تابع پنهان کردن تمام مدال‌ها
function hideAllErrorModals() {
    const modals = ['gpsPermissionModal', 'gpsDisabledModal', 'noGpsModal', 'networkErrorModal', 'generalErrorModal'];
    modals.forEach(id => {
        const modalEl = document.getElementById(id);
        if (modalEl && bootstrap.Modal.getInstance(modalEl)) {
            bootstrap.Modal.getInstance(modalEl).hide();
        }
    });
}

// چک کردن وجود عنصر نقشه قبل از ایجاد آن
document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('map');
    if (mapElement) {
        // ایجاد نقشه فقط اگر عنصر نقشه موجود باشد
        map = L.map('map').setView([32.4279, 53.6880], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }

    // رسم حصارها
    drawGeoFences();

    // بررسی موقعیت اولیه
    checkLocation();

    // دکمه تازه‌سازی
    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        checkLocation();
    });

    // تازه‌سازی خودکار هر 10 دقیقه
    setInterval(checkLocation, 10 * 60 * 1000);
});







// نمایش نوتیفیکیشن موقت در بالای صفحه
function showToast(message, type = 'danger') {
    const toastEl = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;
    toastEl.classList.remove('bg-danger', 'bg-warning', 'bg-success');
    toastEl.classList.add(`bg-${type}`);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

// نمایش مدال خطا
function showModal(modalId) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

// پنهان کردن تمام مدال‌ها
function hideAllErrorModals() {
    ['gpsPermissionModal', 'gpsDisabledModal', 'noGpsModal', 'networkErrorModal', 'generalErrorModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && bootstrap.Modal.getInstance(modal)) {
            bootstrap.Modal.getInstance(modal).hide();
        }
    });
}

// مدیریت خطاها و نمایش مناسب
function handleLocationError(error) {
    console.error('خطا در تعیین موقعیت:', error);

    switch(error.code) {
        case error.PERMISSION_DENIED:
            showModal('gpsPermissionModal');
            showToast('دسترسی به موقعیت مکانی رد شد', 'danger');
            break;

        case error.POSITION_UNAVAILABLE:
            showModal('gpsDisabledModal');
            showToast('موقعیت در دسترس نیست', 'warning');
            break;

        case error.TIMEOUT:
            showModal('networkErrorModal');
            showToast('دریافت موقعیت به پایان رسید', 'danger');
            break;

        default:
            showModal('generalErrorModal');
            showToast('نمی‌توان موقعیت شما را تعیین کرد', 'danger');
    }
}