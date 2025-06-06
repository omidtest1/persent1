INSERT INTO `settings` (`title_fa`, `key_name`, `description`, `used_in`, `setting_value`)
VALUES
-- حضور و غیاب
('تایید ورود مجدد', 'modal_confirm_checkin_again', 'در صورت ورود مجدد عضو به جلسه، آیا مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1'),
('تایید خروج بدون ورود', 'modal_confirm_checkout_without_checkin', 'اگر عضو بدون ثبت ورود بخواهد خروج ثبت کند، آیا مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1'),
('تایید خروج مجدد', 'modal_confirm_checkout_again', 'در صورت خروج مجدد عضو، آیا مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1'),
('تایید صدور برگه رای در وضعیت غیرعادی', 'modal_confirm_vote_paper', 'اگر ورود/خروج عضو غیرعادی باشد، آیا برای صدور برگه رای مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1'),
('اجازه فقط به ادمین برای ورود مجدد', 'admin_only_checkin_again', 'آیا فقط ادمین اجازه ثبت ورود مجدد داشته باشد؟', 'member_action.php,index.php', '1'),
('اجازه فقط به ادمین برای خروج بدون ورود', 'admin_only_checkout_without_checkin', 'آیا فقط ادمین اجازه ثبت خروج بدون ورود را داشته باشد؟', 'member_action.php,index.php', '1'),
('اجازه فقط به ادمین برای خروج مجدد', 'admin_only_checkout_again', 'آیا فقط ادمین اجازه ثبت خروج مجدد را داشته باشد؟', 'member_action.php,index.php', '1'),
('اجازه فقط به ادمین برای صدور برگه رای غیرعادی', 'admin_only_vote_paper_abnormal', 'آیا فقط ادمین اجازه صدور برگه رای در وضعیت غیرعادی را داشته باشد؟', 'member_action.php,index.php', '1'),
('پاکسازی لیست و فوکوس بعد از عملیات', 'clear_list_and_focus_search_after_action', 'بعد از ثبت حضور/غیاب یا صدور برگه رای، لیست اعضا پاک شود و فوکوس به جستجو برگردد.', 'index.php', '1'),

-- برگه رای و بارکد
('عرض برگه رای (میلیمتر)', 'vote_paper_width_mm', 'عرض برگه رای به میلیمتر جهت چاپ', 'member_action.php,index.php', '80'),
('حالت صدور برگه رای', 'vote_paper_mode', 'حالت صدور برگه رای: system (تولید توسط سیستم) یا preprinted (برگه‌های چاپی با بارکد از قبل)', 'member_action.php,index.php', 'system'),
('فعال‌سازی اسکن بارکد برگه رای', 'enable_vote_barcode_scan', 'آیا کادر اسکن بارکد در مدال چاپ برگه رای نمایش داده شود؟', 'index.php,member_action.php', '1'),
('تولید و نمایش بارکد روی برگه رای', 'enable_vote_barcode_generation', 'آیا بارکد برای برگه رای تولید و روی آن نمایش داده شود؟', 'member_action.php,index.php', '1'),
('اعتبارسنجی بارکد در حالت سیستمی', 'enable_vote_barcode_validation', 'در حالت system اگر فعال باشد کد اسکن شده باید دقیقا با بارکد برگه رای مطابقت داشته باشد.', 'index.php,member_action.php', '1'),
('بررسی یکتا بودن سریال برگه رای', 'check_vote_serial_unique', 'در موقع صدور برگه رای سیستمی، یکتا بودن سریال بررسی شود و از ایجاد سریال تکراری جلوگیری گردد.', 'member_action.php', '1');