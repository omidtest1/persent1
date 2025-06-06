/**
 * اسکریپت مدیریت نقشه و ترسیم حصار
 * Map and drawing functionality
 */

$(document).ready(function() {
    // تنظیمات اولیه نقشه
    const map = L.map('map').setView([35.6892, 51.3890], 13); // مختصات تهران
    
    // افزودن لایه نقشه
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // کنترلر رسم اشکال
    const drawControl = new L.Control.Draw({
        draw: {
            polygon: {
                shapeOptions: {
                    color: '#3388ff',
                    fillColor: '#3388ff',
                    fillOpacity: 0.2
                },
                allowIntersection: false,
                showArea: true,
                metric: true
            },
            circle: {
                shapeOptions: {
                    color: '#3388ff',
                    fillColor: '#3388ff',
                    fillOpacity: 0.2
                },
                showRadius: true,
                metric: true
            },
            rectangle: {
                shapeOptions: {
                    color: '#3388ff',
                    fillColor: '#3388ff',
                    fillOpacity: 0.2
                },
                showArea: true,
                metric: true
            },
            marker: false,
            circlemarker: false,
            polyline: false
        },
        edit: {
            featureGroup: new L.FeatureGroup()
        }
    });
    
    // گروه لایه‌های رسم شده
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);
    
    // تغییر نوع حصار
    $('.draw-polygon').click(function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        $('#fenceType').val('polygon');
        $('.circle-radius').hide();
    });
    
    $('.draw-circle').click(function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        $('#fenceType').val('circle');
        $('.circle-radius').show();
    });
    
    $('.draw-rectangle').click(function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        $('#fenceType').val('polygon'); // مستطیل هم به عنوان چندضلعی ذخیره می‌شود
        $('.circle-radius').hide();
    });
    
    // رویداد رسم شکل
    map.on('draw:created', function(e) {
        const type = e.layerType;
        const layer = e.layer;
        
        // ذخیره مختصات در فیلد مخفی
        if (type === 'circle') {
            const center = layer.getLatLng();
            const radius = layer.getRadius();
            $('#coordinates').val(JSON.stringify([center.lat, center.lng]));
            $('#radius').val(Math.round(radius));
        } else {
            const coords = layer.getLatLngs();
            $('#coordinates').val(JSON.stringify(coords));
        }
        
        // افزودن لایه به نقشه
        drawnItems.addLayer(layer);
    });
    
    // ارسال فرم
    $('#fenceForm').submit(function(e) {
        e.preventDefault();
        
        if (!$('#coordinates').val()) {
            alert('لطفاً ابتدا یک حصار روی نقشه رسم کنید');
            return;
        }
        
        const formData = {
            meeting_id: $('#meetingSelect').val(),
            name: $('#fenceName').val(),
            fence_type: $('#fenceType').val(),
            coordinates: $('#coordinates').val(),
            radius: $('#radius').val()
        };
        
        $.ajax({
            url: 'api/save_fence.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    alert('حصار با موفقیت ذخیره شد');
                    window.location.reload();
                } else {
                    alert('خطا: ' + response.message);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    });
    
    // تغییر جلسه انتخابی
    $('#meetingSelect').change(function() {
        if ($(this).val()) {
            window.location.href = 'index.php?meeting_id=' + $(this).val();
        }
    });
    
	/*
    // بارگذاری حصارهای موجود روی نقشه
    <?php if (!empty($fences)): ?>
        <?php foreach ($fences as $fence): ?>
            (function() {
                const fence = <?= json_encode($fence) ?>;
                try {
                    const coords = JSON.parse(fence.coordinates);
                    
                    if (fence.fence_type === 'circle') {
                        const circle = L.circle([coords[0], coords[1]], {
                            radius: fence.radius,
                            color: '#3388ff',
                            fillColor: '#3388ff',
                            fillOpacity: 0.2
                        }).addTo(map);
                        circle.bindPopup(`<b>${fence.name}</b><br>نوع: دایره`);
                    } else {
                        const polygon = L.polygon(coords, {
                            color: '#3388ff',
                            fillColor: '#3388ff',
                            fillOpacity: 0.2
                        }).addTo(map);
                        polygon.bindPopup(`<b>${fence.name}</b><br>نوع: چندضلعی`);
                    }
                } catch (e) {
                    console.error('Error parsing fence coordinates:', e);
                }
            })();
        <?php endforeach; ?>
    <?php endif; ?>*/
	
});