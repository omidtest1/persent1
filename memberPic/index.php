<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سامانه حضور و غیاب مجمع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f2f5fa; font-family: Vazirmatn, Tahoma, sans-serif; }
        .member-photo-thumb { width: 300px; height: 400px; object-fit: cover; border-radius: 10px; cursor: pointer; border: 2px solid #ddd; background: #fff; }
        .member-photo-thumb:focus, .member-photo-thumb:hover { box-shadow: 0 0 0 5px #007bff55; }
        .search-bar { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px #0001; margin-bottom: 30px; }
        .modal-lg-portrait { max-width: 450px; }
        .table > :not(:first-child) { border-top: 2px solid #dee2e6; }
        .op-btn:focus { outline: 3px solid #ff9800 !important; outline-offset: 2px; z-index: 2; }
        .row-selected td { background: #e3f2fd !important; }
        .row-selected .op-btn { box-shadow: 0 0 0 2px #1976d2; }
        .modal-footer .btn-cancel { background: #eee; border: 1px solid #bbb; }
        .modal-footer .btn-cancel:focus { outline: 2px solid #F44336; }
        .photo-modal-img { max-width:100%;max-height:78vh;border-radius:10px; box-shadow:0 0 16px #0004; }
        .details-photo-link.btn { font-size: 15px; padding: 6px 12px; }
        input#barcodeInput { font-size:1.3em; direction:ltr; text-align:center; max-width:220px; margin:auto; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bwip-js@4.6.0/dist/bwip-js-min.js"></script>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4 text-center text-primary">سامانه حضور و غیاب مجمع</h2>
        <div class="search-bar">
            <form id="searchForm" class="row g-3 align-items-center" autocomplete="off">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="نام، نام خانوادگی، کد ملی یا شماره عضویت یا بارکد را وارد کنید" autofocus autocomplete="off">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> جستجو</button>
                </div>
            </form>
        </div>
        <div id="membersTableContainer"></div>
    </div>
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg-portrait">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">عکس عضو</h5>
            <button type="button" class="btn-close modal-photo-close" data-bs-dismiss="modal" aria-label="بستن"></button>
          </div>
          <div class="modal-body text-center">
            <img id="modalPhoto" src="" class="photo-modal-img" alt="عکس عضو">
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmModalTitle"></h5>
            <button type="button" class="btn-close modal-confirm-close" data-bs-dismiss="modal" aria-label="بستن"></button>
          </div>
          <div class="modal-body" id="confirmModalBody"></div>
          <div class="modal-footer" id="confirmModalFooter"></div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="votePaperModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg-portrait">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">برگه رأی</h5>
            <button type="button" class="btn-close modal-vote-close" data-bs-dismiss="modal" aria-label="بستن"></button>
          </div>
          <div class="modal-body" id="votePaperContent" style="background:#fff;"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" id="printVotePaperBtn"><i class="fa fa-print"></i> چاپ برگه رأی</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        var clearListAndFocusAfterAction = <?php
          require 'config.php';
          echo $config['clear_list_and_focus_search_after_action'] ? 'true' : 'false';
        ?>;
        var votePaperWidthMM = <?php echo isset($config['vote_paper_width_mm']) ? intval($config['vote_paper_width_mm']) : 80; ?>;
        var votePaperMode = "<?php echo $config['vote_paper_mode'] ?>";

        var openModalsStack = [];
        $(document).on('show.bs.modal', '.modal', function() { openModalsStack.push(this); });
        $(document).on('hidden.bs.modal', '.modal', function() {
            var idx = openModalsStack.indexOf(this);
            if(idx > -1) openModalsStack.splice(idx, 1);
        });

        $("#searchInput").focus();

        $('#searchForm').on('submit', function(e){
            e.preventDefault();
            let q = $('#searchInput').val().trim();
            if(q.length < 2) {
                alert("لطفا حداقل دو حرف وارد کنید");
                return;
            }
            searchMembers(q);
        });
        $('#searchInput').on('input', function(e){
            let val = $(this).val().trim();
            if(/^[0-9]{10}$/.test(val)) {
                searchMembers(val);
            }
        });
        function searchMembers(q){
            $.ajax({
                url: 'search_members.php',
                method: 'POST',
                data: {query: q},
                success: function(res){
                    $('#membersTableContainer').html(res);
                    selectRow(0);
                },
                error: function(){
                    $('#membersTableContainer').html('<div class="alert alert-danger">خطا در ارتباط با سرور</div>');
                }
            });
        }
        $('#searchInput').on('keydown', function(e){
            if(e.key === "Enter") {
                e.preventDefault();
                let q = $('#searchInput').val().trim();
                if(q.length > 1) searchMembers(q);
            }
        });

        // نمایش عکس بزرگ
        $(document).on('click keydown', '.member-photo-thumb, .details-photo-link', function(e){
            if(e.type==="click" || (e.type==="keydown" && (e.key==="Enter" || e.key===" "))) {
                let src = $(this).attr('data-full');
                $('#modalPhoto').attr('src', src);
                let modalPhoto = new bootstrap.Modal(document.getElementById('photoModal'));
                modalPhoto.show();
                setTimeout(function(){$('.modal-photo-close:visible').focus();}, 300);
            }
        });

        // دکمه‌های عملیاتی
        $(document).on('click', '.action-btn', function(){
            let member_id = $(this).data('id');
            let action = $(this).data('action');
            $.ajax({
                url: 'member_action.php',
                method: 'POST',
                data: { member_id, action },
                dataType: 'json',
                success: function(res){
                    // اگر حالت صدور برگه یا اسکن بود مدال ثبت را باز کن
                    if(res.confirm_modal && (action==='vote_paper')) {
                        $('#confirmModalTitle').text(res.modal_title);
                        $('#confirmModalBody').html(res.modal_body);
                        $('#confirmModalFooter').html(res.modal_footer);
                        let modalC = new bootstrap.Modal(document.getElementById('confirmModal'));
                        modalC.show();
                        setTimeout(function(){
                            $('#barcodeInput').focus();
                        }, 350);

                        // اگر سیستم تولید می‌کند بارکد را با bwip-js نمایش بده
                        if(res.vote_mode === 'system') {
                            setTimeout(function(){
                                let barcode = $('#voteBarcodeCanvas').attr('data-barcode');
                                if(barcode && window.BWIPJS) {
                                    try {
                                        BWIPJS.toCanvas('voteBarcodeCanvas', {
                                            bcid:        'code128',
                                            text:        barcode,
                                            scale:       2,
                                            height:      12,
                                            includetext: false
                                        });
                                    } catch(e){}
                                }
                                $('#barcodeInput').focus();
                            }, 600);
                        }
                        return;
                    }
                    // حالت نمایش برگه چاپی
                    if(res.show_vote_paper) {
                        $('#votePaperContent').html(res.vote_paper_html);
                        $('#votePaperModal').modal('show');
                        $('#printVotePaperBtn').off('click').on('click', function(){
                            printVotePaper();
                            $.ajax({ url: 'member_action.php', method:'POST', data: { member_id, action:'log_vote_paper_print' } });
                        });
                        setTimeout(function(){$('.modal-vote-close:visible').focus();}, 300);
                        return;
                    }

                    if(res.confirm_modal) {
                        $('#confirmModalTitle').text(res.modal_title);
                        $('#confirmModalBody').html(res.modal_body);
                        $('#confirmModalFooter').html("<button class='btn btn-cancel' data-bs-dismiss='modal'>انصراف</button> " + res.modal_footer);
                        let modalC = new bootstrap.Modal(document.getElementById('confirmModal'));
                        modalC.show();
                        setTimeout(function(){
                            $('#confirmModal').find('button, [tabindex="0"], a').filter(':visible').first().focus();
                        }, 400);
                    } else {
                        if(res.refresh) {
                            if(clearListAndFocusAfterAction && action!=='details') {
                                $('#membersTableContainer').empty();
                                $('#searchInput').val('').focus();
                            } else {
                                $('#searchForm').submit();
                            }
                        }
                        if(res.message) {
                            alert(res.message);
                        }
                    }
                },
                error: function(){
                    alert("خطا در ارتباط با سرور");
                }
            });
        });

        // ثبت اسکن یا تایید برگه رأی
        $(document).on('click', '.modal-assign-barcode-btn', function(){
            var member_id = $(this).data('id');
            var barcode = $(this).data('barcode') || $('#barcodeInput').val().trim();
            if(!barcode) {
                $('#barcodeError').text("بارکد را وارد کنید");
                $('#barcodeInput').focus();
                return;
            }
            $.ajax({
                url: 'member_action.php',
                method: 'POST',
                data: { member_id, action:'assign_vote_paper', barcode: barcode },
                dataType: 'json',
                success: function(res){
                    if(res.message) $('#barcodeError').text(res.message);
                    if(res.refresh) {
                        $('#confirmModal').modal('hide');
                        $('#membersTableContainer').empty();
                        $('#searchInput').val('').focus();
                    }
                }
            });
        });
        // با اینتر روی اینپوت بارکد هم ثبت شود
        $(document).on('keydown', '#barcodeInput', function(e){
            if(e.key==="Enter") {
                $('.modal-assign-barcode-btn').click();
            }
        });

        $(document).on('shown.bs.modal', '#confirmModal', function(){
            var $barcodeInput = $('#barcodeInput');
            if($barcodeInput.length) setTimeout(function(){ $barcodeInput.focus(); }, 50);
        });

        $(document).on('keydown', '#confirmModal', function(e){
            let $items = $('#confirmModal').find('button, [tabindex="0"], a, input').filter(':visible');
            let idx = $items.index(document.activeElement);
            if(e.key === "Tab") {
                e.preventDefault();
                if(e.shiftKey) idx = (idx - 1 + $items.length) % $items.length;
                else idx = (idx + 1) % $items.length;
                $items.eq(idx).focus();
            }
            if(e.key === "ArrowDown" || e.key === "ArrowRight") {
                e.preventDefault(); idx = (idx + 1) % $items.length; $items.eq(idx).focus();
            }
            if(e.key === "ArrowUp" || e.key === "ArrowLeft") {
                e.preventDefault(); idx = (idx - 1 + $items.length) % $items.length; $items.eq(idx).focus();
            }
            if((e.key==="Enter" || e.key===" ") && $(document.activeElement).hasClass('details-photo-link')) {
                $(document.activeElement).trigger('click');
            }
        });

        let selectedRowIdx = 0, selectedBtnIdx = 0;
        function selectRow(idx){
            let rows = $('#membersTableContainer tbody tr');
            if(!rows.length) return;
            idx = (idx+rows.length)%rows.length;
            rows.removeClass('row-selected');
            $(rows[idx]).addClass('row-selected');
            selectedRowIdx = idx;
            selectedBtnIdx = 0;
            let btns = $(rows[idx]).find('.action-btn');
            btns.eq(0).focus();
        }
        function selectBtn(btnIdx){
            let rows = $('#membersTableContainer tbody tr');
            if(!rows.length) return;
            let btns = $(rows[selectedRowIdx]).find('.action-btn');
            btnIdx = (btnIdx+btns.length)%btns.length;
            btns.eq(btnIdx).focus();
            selectedBtnIdx = btnIdx;
        }
        $(document).on('keydown', function(e){
            if(openModalsStack.length) return;
            let rows = $('#membersTableContainer tbody tr');
            if(!rows.length) return;
            if(['ArrowDown','ArrowUp','ArrowLeft','ArrowRight','Enter'].includes(e.key)) e.preventDefault();
            if(e.key==="ArrowDown") selectRow(selectedRowIdx+1);
            if(e.key==="ArrowUp") selectRow(selectedRowIdx-1);
            if(e.key==="ArrowLeft") selectBtn(selectedBtnIdx+1);
            if(e.key==="ArrowRight") selectBtn(selectedBtnIdx-1);
            if(e.key==="Enter") {
                let btns = $(rows[selectedRowIdx]).find('.action-btn');
                btns.eq(selectedBtnIdx).click();
            }
        });

        $(document).on('keydown', function(e){
            if(e.key === "Escape") {
                if(openModalsStack.length) {
                    var topModal = openModalsStack[openModalsStack.length - 1];
                    $(topModal).modal('hide');
                    e.preventDefault();
                    e.stopPropagation();
                } else {
                    $('#searchInput').val('').focus();
                    $('#membersTableContainer').empty();
                }
            }
        });
        $(document).on('click', function(e){
            if(!$(e.target).is('#searchInput')) $('#searchInput').focus();
        });

        function printVotePaper() {
            let printContents = document.getElementById('votePaperContent').innerHTML;
            let widthMM = votePaperWidthMM;
            let printWindow = window.open('', '', 'width=350,height=500');
            printWindow.document.write('<html><head><title>چاپ برگه رأی</title>');
            printWindow.document.write(
                '<style>body{direction:rtl;font-family:tahoma;font-size:12pt;margin:0;padding:0;} ' +
                '.vote-paper{width:'+widthMM+'mm;border:1px solid #000;padding:5px;}' +
                'table{border-collapse:collapse;width:100%;} td,th{border:1px solid #222;font-size:10pt;padding:1px 3px;text-align:center;}</style>'
            );
            printWindow.document.write('</head><body>' + printContents + '</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    </script>
</body>
</html>