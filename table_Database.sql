-- ##############################
--نحوه وصل شدن به دیتابس و جداول زیر 
--$host = 'localhost'; $dbname = 'meeting_system'; $username = 'root'; $password = ''; $port = 3307;
--$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
-- ##############################

CREATE DATABASE meeting_system;

-- ##############################
-- # جدول اعضا (members)
-- # ذخیره اطلاعات اعضای سازمان
-- ##############################
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر عضو',
  `first_name` varchar(100) NOT NULL COMMENT 'نام عضو',
  `last_name` varchar(100) NOT NULL COMMENT 'نام خانوادگی عضو',
  `national_code` varchar(10) NOT NULL COMMENT 'کد ملی عضو (منحصر به فرد)',
  `mobile` varchar(11) NOT NULL COMMENT 'شماره موبایل عضو',
  `membership_number` varchar(20) NOT NULL COMMENT 'شماره عضویت در سازمان',
  `birth_date` date NOT NULL COMMENT 'تاریخ تولد عضو',
  `membership_expiry` date NOT NULL COMMENT 'تاریخ اعتبار عضویت',
  `photo_path` varchar(255) DEFAULT NULL COMMENT 'مسیر ذخیره عکس عضو',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم عضو',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد با دقت میلی‌ثانیه',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `national_code` (`national_code`),
  UNIQUE KEY `membership_number` (`membership_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول اصلی اطلاعات اعضای سازمان';

-- ##############################
-- # جدول جلسات (meetings)
-- # ذخیره اطلاعات جلسات و مجامع
-- ##############################
CREATE TABLE `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر جلسه',
  `title` varchar(255) NOT NULL COMMENT 'عنوان جلسه یا مجمع',
  `type` enum('annual','extraordinary','other') NOT NULL COMMENT 'نوع جلسه: عادی سالانه، فوق‌العاده، سایر',
  `province` varchar(100) NOT NULL COMMENT 'استان برگزاری جلسه',
  `address` text NOT NULL COMMENT 'آدرس دقیق محل برگزاری',
  `start_time` datetime NOT NULL COMMENT 'زمان شروع جلسه',
  `end_time` datetime DEFAULT NULL COMMENT 'زمان پایان جلسه',
  `status` enum('pending','active','finished') NOT NULL DEFAULT 'pending' COMMENT 'وضعیت جلسه: در انتظار، در حال برگزاری، پایان یافته',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم جلسه',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول اطلاعات جلسات و مجامع';

-- ##############################
-- # جدول حصارهای جغرافیایی (geo_fences)
-- # ذخیره محدوده‌های جغرافیایی هر جلسه
-- ##############################
CREATE TABLE `geo_fences` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر حصار',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `name` varchar(100) NOT NULL COMMENT 'نام توصیفی حصار',
  `coordinates` text NOT NULL COMMENT 'مختصات جغرافیایی حصار به فرمت JSON',
  `radius` decimal(10,2) DEFAULT NULL COMMENT 'شعاع حصار (در صورت دایره‌ای بودن)',
  `fence_type` enum('polygon','circle') NOT NULL COMMENT 'نوع حصار: چندضلعی یا دایره',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم حصار',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `geo_fences_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول حصارهای جغرافیایی برای جلسات';

-- ##############################
-- # جدول حضور و غیاب (attendances)
-- # ردیابی حضور اعضا در جلسات
-- ##############################
CREATE TABLE `attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر رکورد حضور',
  `member_id` int(11) NOT NULL COMMENT 'آیدی عضو (foreign key)',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه (foreign key)',
  `check_in` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان ورود به جلسه با دقت میلی‌ثانیه',
  `check_out` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان خروج از جلسه',
  `current_status` enum('in','out') DEFAULT NULL COMMENT 'وضعیت فعلی: داخل یا خارج از جلسه',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم رکورد',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_meeting` (`member_id`,`meeting_id`),
  KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `attendances_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `attendances_ibfk_2` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول ردیابی حضور و غیاب اعضا در جلسات';

-- ##############################
-- # جدول سوالات رأی‌گیری (questions)
-- # ذخیره سوالات مطرح شده در جلسات
-- ##############################
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر سوال',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `title` text NOT NULL COMMENT 'متن سوال رأی‌گیری',
  `description` text DEFAULT NULL COMMENT 'توضیحات تکمیلی سوال',
  `status` enum('pending','active','finished') NOT NULL DEFAULT 'pending' COMMENT 'وضعیت سوال: در انتظار، فعال، پایان یافته',
  `start_time` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان شروع رأی‌گیری',
  `end_time` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان پایان رأی‌گیری',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم سوال',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول سوالات رأی‌گیری در جلسات';

-- ##############################
-- # جدول آراء (votes)
-- # ذخیره رأی‌های اعضا
-- ##############################
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر رأی',
  `question_id` int(11) NOT NULL COMMENT 'آیدی سوال مرتبط (foreign key)',
  `member_id` int(11) NOT NULL COMMENT 'آیدی عضو (foreign key)',
  `vote` enum('yes','no') NOT NULL COMMENT 'رأی عضو: موافق یا مخالف',
  `vote_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'زمان ثبت رأی با دقت میلی‌ثانیه',
  `location_status` enum('in','out') NOT NULL COMMENT 'وضعیت موقعیت عضو هنگام رأی دادن',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم رأی',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_member` (`question_id`,`member_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول آراء اعضا در سوالات رأی‌گیری';

-- ##############################
-- # جدول لاگ آراء (votes)
-- # این جدول تمامی بارهایی که برگه رأی به یک عضو داده شده را ثبت می‌کند.
-- ##############################
CREATE TABLE `votes_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `admin_username` varchar(100) NOT NULL,
  `attendance_status` enum('in','out','none') NOT NULL COMMENT 'وضعیت حضور وقتی برگه داده شد',
  `issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'آیا کاربر تأیید کرده بود؟',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `votes_log_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ##############################
-- # جدول هیئت رئیسه (board_members)
-- # ذخیره اطلاعات هیئت رئیسه هر جلسه
-- ##############################
CREATE TABLE `board_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر عضو هیئت رئیسه',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `member_id` int(11) NOT NULL COMMENT 'آیدی عضو (foreign key)',
  `role` enum('youngest','oldest','average_age','secretary','typist','other') NOT NULL COMMENT 'نقش عضو در هیئت رئیسه',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم رکورد',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `board_members_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`),
  CONSTRAINT `board_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول اعضای هیئت رئیسه جلسات';

-- ##############################
-- # جدول صورتجلسات (minutes)
-- # ذخیره صورتجلسات نهایی
-- ##############################
CREATE TABLE `minutes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر صورتجلسه',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `content` text NOT NULL COMMENT 'محتویات صورتجلسه',
  `file_path` varchar(255) DEFAULT NULL COMMENT 'مسیر ذخیره فایل PDF صورتجلسه',
  `approval_status` enum('draft','approved','rejected') NOT NULL DEFAULT 'draft' COMMENT 'وضعیت تأیید صورتجلسه',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم صورتجلسه',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `minutes_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول صورتجلسات نهایی';

 
CREATE TABLE `vote_papers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید یکتا برای هر برگه رأی',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه',
  `barcode` varchar(64) NOT NULL COMMENT 'کد یکتا (بارکد) برگه',
  `is_issued` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'آیا به عضوی تخصیص داده شده',
  `issued_to_user_id` int(11) DEFAULT NULL COMMENT 'آیدی کاربر تحویل دهنده',
  `issued_to_member_id` int(11) DEFAULT NULL COMMENT 'آیدی عضو تحویل گیرنده',
  `issued_at` timestamp NULL DEFAULT NULL COMMENT 'زمان صدور/تحویل', 
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم رأی',
  `created_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `vote_papers_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='برگه‌های رأی یکتا با بارکد';



CREATE TABLE `settings` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title_fa` VARCHAR(100) NOT NULL COMMENT 'نام تنظیم به فارسی',
  `key_name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'نام انگلیسی/کلید تنظیم',
  `description` TEXT COMMENT 'توضیحات',
  `used_in` VARCHAR(100) COMMENT 'در چه صفحه‌ای استفاده شده',
  `setting_value` VARCHAR(100) NOT NULL COMMENT 'مقدار تنظیم',
  `is_deleted` TINYINT(1) DEFAULT '0' COMMENT '1 به معنی حذف نرم',
  `created_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'تاریخ و زمان ایجاد رکورد'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;