<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/classes/GeoFence.php';

$meetings = getActiveMeetings();
$selectedMeetingId = $_GET['meeting_id'] ?? ($meetings[0]['id'] ?? null);
$fences = [];

if ($selectedMeetingId) {
    $geoFence = new GeoFence();
    $fences = $geoFence->getFencesByMeeting($selectedMeetingId);
    
    // آماده کردن داده‌های حصارها برای JavaScript
    $fencesData = [];
    foreach ($fences as $fence) {
        $fencesData[] = [
            'id' => $fence['id'],
            'type' => $fence['fence_type'],
            'name' => addslashes($fence['name']),
            'coords' => json_decode($fence['coordinates'], true),
            'radius' => isset($fence['radius']) ? (float)$fence['radius'] : null
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم حصار جغرافیایی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@5.3.0/dist/css/bootstrap-rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <style>
        body { font-family: 'Vazir', Tahoma; background-color: #f8f9fa; overflow: hidden; }
        #map { height: 100vh; width: 100%; z-index: 1; }
        .sidebar { background-color: #f8f9fa; height: 100vh; overflow-y: auto; padding: 15px; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; border: none; }
        .card-header { border-radius: 10px 10px 0 0 !important; font-weight: bold; }
        .fence-list { max-height: 300px; overflow-y: auto; }
        .map-controls { position: absolute; top: 10px; right: 10px; z-index: 1000; }
        .draw-control-btn { background-color: #0d6efd; color: white; border: none; border-radius: 5px; padding: 8px 15px; cursor: pointer; }
        .leaflet-control-container .leaflet-left { right: 0; left: auto; }
        .leaflet-control-container .leaflet-right { left: 0; right: auto; }
        @media (max-width: 768px) {
            .sidebar { height: auto; max-height: 40vh; }
            #map { height: 60vh; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 col-lg-3 sidebar">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title"><i class="bi bi-pencil-square"></i> ترسیم حصار جدید</h5>
                    </div>
                    <div class="card-body">
                        <form id="fenceForm">
                            <div class="mb-3">
                                <label for="meetingSelect" class="form-label">جلسه</label>
                                <select class="form-select" id="meetingSelect" name="meeting_id" required>
                                    <option value="">-- انتخاب جلسه --</option>
                                    <?php foreach ($meetings as $meeting): ?>
                                        <option value="<?= $meeting['id'] ?>" <?= $selectedMeetingId == $meeting['id'] ? 'selected' : '' ?>>
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
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary active draw-control" data-type="polygon">
                                        <i class="bi bi-pentagon"></i> چندضلعی
                                    </button>
                                    <button type="button" class="btn btn-outline-primary draw-control" data-type="circle">
                                        <i class="bi bi-circle"></i> دایره
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3 circle-radius" style="display: none;">
                                <label for="radius" class="form-label">شعاع (متر)</label>
                                <input type="number" class="form-control" id="radius" name="radius" min="10">
                            </div>
                            <input type="hidden" id="coordinates" name="coordinates">
                            <input type="hidden" id="fenceType" name="fence_type" value="polygon">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> ذخیره حصار
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-collection"></i> حصارهای موجود</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggleAllFences" checked>
                            <label class="form-check-label" for="toggleAllFences">نمایش همه</label>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($fences)): ?>
                            <div class="list-group fence-list">
                                <?php foreach ($fences as $fence): ?>
                                    <div class="list-group-item list-group-item-action fence-item" 
                                         data-fence-id="<?= $fence['id'] ?>" 
                                         data-fence-type="<?= $fence['fence_type'] ?>"
                                         data-coordinates='<?= htmlspecialchars($fence['coordinates'], ENT_QUOTES, 'UTF-8') ?>'
                                         data-radius="<?= $fence['radius'] ?? '' ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input fence-toggle" type="checkbox" checked>
                                                <label class="form-check-label"><?= htmlspecialchars($fence['name']) ?></label>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?= $fence['fence_type'] == 'circle' ? 'warning' : 'success' ?> me-2">
                                                    <?= $fence['fence_type'] == 'circle' ? 'دایره' : 'چندضلعی' ?>
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary edit-fence" title="ویرایش">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-fence" title="حذف">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                هیچ حصاری برای این جلسه تعریف نشده است
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8 col-lg-9 p-0 position-relative">
                <div id="map"></div>
                <div class="map-controls">
                    <button id="toggleDraw" class="draw-control-btn">
                        <i class="bi bi-pencil"></i> رسم حصار
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    
    <script>
    // متغیرهای global
    let map, drawnItems, drawControl;
    let isDrawing = false;
    const fenceLayers = {};
    const fencesData = <?= isset($fencesData) ? json_encode($fencesData, JSON_UNESCAPED_UNICODE) : '[]' ?>;

    // تابع مقداردهی اولیه نقشه
    function initMap() {
        if (map) { map.off(); map.remove(); }
        
      //  map = L.map('map').setView([35.6892, 51.3890], 13);///تهران
	//	map = L.map('map').setView([32.4279, 53.6880], 5);//ایران
		map = L.map('map').setView([36.3619, 59.4832], 13);//رحمانیه
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        
        drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);
        
        drawControl = new L.Control.Draw({
            draw: {
                polygon: { shapeOptions: { color: '#3388ff' } },
                circle: { shapeOptions: { color: '#3388ff' } },
                marker: false, polyline: false
            },
            edit: { featureGroup: drawnItems }
        });
        
        loadExistingFences();
    }

    // تابع بارگذاری حصارهای موجود
    function loadExistingFences() {
        fencesData.forEach(fence => {
            try {
                let layer;
                if (fence.type === 'circle' && fence.radius) {
                    layer = L.circle(fence.coords, {
                        radius: fence.radius,
                        color: '#ff7800',
                        fillColor: '#ff7800',
                        fillOpacity: 0.2
                    });
                } else {
                    layer = L.polygon(fence.coords, {
                        color: '#3388ff',
                        fillColor: '#3388ff',
                        fillOpacity: 0.2
                    });
                }
                
                layer.bindPopup(fence.name);
                layer.addTo(map);
                fenceLayers[fence.id] = layer;
            } catch (e) {
                console.error('Error loading fence:', e);
            }
        });
    }

    // رویدادها
    $(document).ready(function() {
        initMap();
        
        $('.draw-control').click(function() {
            $('.draw-control').removeClass('active');
            $(this).addClass('active');
            $('#fenceType').val($(this).data('type'));
            $('.circle-radius').toggle($(this).data('type') === 'circle');
        });

        $('#toggleDraw').click(function() {
            isDrawing = !isDrawing;
            $(this).toggleClass('active', isDrawing);
            isDrawing ? map.addControl(drawControl) : map.removeControl(drawControl);
        });

        map.on('draw:created', function(e) {
            const layer = e.layer;
            drawnItems.addLayer(layer);
            
            if (e.layerType === 'circle') {
                $('#coordinates').val(JSON.stringify(layer.getLatLng()));
                $('#radius').val(Math.round(layer.getRadius()));
            } else {
                $('#coordinates').val(JSON.stringify(layer.getLatLngs()));
            }
            $('#fenceType').val(e.layerType === 'circle' ? 'circle' : 'polygon');
        });

        $(document).on('change', '.fence-toggle', function() {
            const fenceId = $(this).closest('.fence-item').data('fence-id');
            if (this.checked) {
                map.addLayer(fenceLayers[fenceId]);
            } else {
                map.removeLayer(fenceLayers[fenceId]);
            }
        });

        $('#toggleAllFences').change(function() {
            $('.fence-toggle').prop('checked', this.checked).trigger('change');
        });

        $(document).on('click', '.delete-fence', function(e) {
            e.stopPropagation();
            const fenceId = $(this).closest('.fence-item').data('fence-id');
            if (confirm('آیا از حذف این حصار مطمئن هستید؟')) {
                $.post('api/delete_fence.php', { fence_id: fenceId }, function(response) {
                    if (response.success) {
                        map.removeLayer(fenceLayers[fenceId]);
                        delete fenceLayers[fenceId];
                        $(`.fence-item[data-fence-id="${fenceId}"]`).remove();
                        if ($('.fence-item').length === 0) {
                            $('.fence-list').html('<div class="p-3 text-center text-muted">هیچ حصاری برای این جلسه تعریف نشده است</div>');
                        }
                    } else {
                        alert('خطا: ' + response.message);
                    }
                }, 'json');
            }
        });

        $(document).on('click', '.edit-fence', function(e) {
            e.stopPropagation();
            const fenceItem = $(this).closest('.fence-item');
            const fenceId = fenceItem.data('fence-id');
            
            fenceLayers[fenceId].setStyle({ color: '#ff0000', fillColor: '#ff0000' });
            
            $('#fenceName').val(fenceItem.find('.form-check-label').text().trim());
            $('#fenceType').val(fenceItem.data('fence-type'));
            $('.draw-control[data-type="' + fenceItem.data('fence-type') + '"]').click();
            
            if (fenceItem.data('fence-type') === 'circle') {
                $('#radius').val(fenceItem.data('radius'));
            }
            
            $('#coordinates').val(fenceItem.data('coordinates'));
            $('#fenceForm').data('edit-mode', true).data('fence-id', fenceId)
                .find('button[type="submit"]')
                .html('<i class="bi bi-save"></i> ذخیره تغییرات')
                .removeClass('btn-primary').addClass('btn-warning');
        });

        $('#fenceForm').submit(function(e) {
            e.preventDefault();
            if (!$('#coordinates').val()) {
                alert('لطفاً ابتدا یک حصار روی نقشه رسم کنید');
                return;
            }
            
            const formData = $(this).serialize();
            const url = $(this).data('edit-mode') ? 'api/update_fence.php' : 'api/save_fence.php';
            
            $.post(url, formData, function(response) {
                if (response.success) {
                    alert($('#fenceForm').data('edit-mode') ? 'تغییرات ذخیره شد' : 'حصار جدید ذخیره شد');
                    location.reload();
                } else {
                    alert('خطا: ' + response.message);
                }
            }, 'json');
        });

        $('#meetingSelect').change(function() {
            if ($(this).val()) {
                window.location.href = 'index.php?meeting_id=' + $(this).val();
            }
        });
    });
    </script>
</body>
</html>