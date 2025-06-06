-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jun 06, 2025 at 07:29 PM
-- Server version: 10.4.13-MariaDB
-- PHP Version: 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meeting_system`
--
DROP DATABASE IF EXISTS `meeting_system`;
CREATE DATABASE IF NOT EXISTS `meeting_system` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `meeting_system`;

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
CREATE TABLE IF NOT EXISTS `attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر رکورد حضور',
  `member_id` int(11) NOT NULL COMMENT 'آیدی عضو (foreign key)',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه (foreign key)',
  `check_in` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان ورود به جلسه با دقت میلی‌ثانیه',
  `check_out` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان خروج از جلسه',
  `current_status` enum('in','out') DEFAULT NULL COMMENT 'وضعیت فعلی: داخل یا خارج از جلسه',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم رکورد',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='جدول ردیابی حضور و غیاب اعضا در جلسات';

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `member_id`, `meeting_id`, `check_in`, `check_out`, `current_status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-06-06 09:27:16.078', '2025-06-06 09:42:10.843', 'out', 0, '2025-04-25 11:09:24.006', '2025-06-06 09:42:10.843'),
(2, 1, 1, '2025-06-01 14:39:36.000', NULL, 'in', 0, '2025-06-01 14:39:36.393', NULL),
(3, 1, 1, '2025-06-01 14:39:46.000', NULL, 'in', 0, '2025-06-01 14:39:46.230', NULL),
(4, 6, 1, '2025-06-06 09:00:31.827', '2025-06-06 09:17:14.529', 'out', 0, '2025-06-01 15:20:28.941', '2025-06-06 09:17:14.529'),
(5, 6, 1, '2025-06-01 15:20:32.000', NULL, 'in', 0, '2025-06-01 15:20:32.168', NULL),
(6, 5, 1, '2025-06-06 11:13:48.658', '2025-06-06 10:42:26.525', 'in', 0, '2025-06-01 15:20:47.198', '2025-06-06 11:13:48.658'),
(7, 2, 1, '2025-06-06 09:42:58.072', '2025-06-06 10:42:38.710', 'out', 0, '2025-06-01 15:20:58.826', '2025-06-06 10:42:38.710'),
(8, 6, 1, '2025-06-01 15:24:01.000', '2025-06-06 13:18:26.739', 'out', 0, '2025-06-01 15:24:01.862', '2025-06-06 13:18:26.739'),
(9, 2, 1, '2025-06-01 15:24:22.000', NULL, 'in', 0, '2025-06-01 15:24:22.257', NULL),
(10, 1, 1, '2025-06-01 15:26:32.000', NULL, 'in', 0, '2025-06-01 15:26:32.469', NULL),
(11, 2, 1, '2025-06-01 15:37:40.000', NULL, 'in', 0, '2025-06-01 15:37:40.174', NULL),
(12, 1, 1, '2025-06-01 15:37:47.000', NULL, 'in', 0, '2025-06-01 15:37:47.157', NULL),
(13, 2, 1, '2025-06-01 15:46:01.000', NULL, 'in', 0, '2025-06-01 15:46:01.612', NULL),
(14, 1, 1, '2025-06-01 16:06:49.000', NULL, 'in', 0, '2025-06-01 16:06:49.835', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `board_members`
--

DROP TABLE IF EXISTS `board_members`;
CREATE TABLE IF NOT EXISTS `board_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر عضو هیئت رئیسه',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `member_id` int(11) NOT NULL COMMENT 'آیدی عضو (foreign key)',
  `role` enum('youngest','oldest','average_age','secretary','typist','other') NOT NULL COMMENT 'نقش عضو در هیئت رئیسه',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم رکورد',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='جدول اعضای هیئت رئیسه جلسات';

-- --------------------------------------------------------

--
-- Table structure for table `geo_fences`
--

DROP TABLE IF EXISTS `geo_fences`;
CREATE TABLE IF NOT EXISTS `geo_fences` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر حصار',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `name` varchar(100) NOT NULL COMMENT 'نام توصیفی حصار',
  `coordinates` text NOT NULL COMMENT 'مختصات جغرافیایی حصار به فرمت JSON',
  `radius` decimal(10,2) DEFAULT NULL COMMENT 'شعاع حصار (در صورت دایره‌ای بودن)',
  `fence_type` enum('polygon','circle') NOT NULL COMMENT 'نوع حصار: چندضلعی یا دایره',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم حصار',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='جدول حصارهای جغرافیایی برای جلسات';

--
-- Dumping data for table `geo_fences`
--

INSERT INTO `geo_fences` (`id`, `meeting_id`, `name`, `coordinates`, `radius`, `fence_type`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 1, '                                                                        tset                        ', '35.71347277847191,51.41378402709962', '600.00', 'circle', 1, '2025-04-25 08:41:25.299', '2025-04-25 09:52:29.061'),
(2, 1, 'test2', '[[{\"lat\":35.707753481100816,\"lng\":51.44193649291993},{\"lat\":35.706777217297066,\"lng\":51.457042694091804},{\"lat\":35.6947821433995,\"lng\":51.45292282104493}]]', NULL, 'polygon', 1, '2025-04-25 08:42:02.558', '2025-04-25 09:31:46.992'),
(3, 1, 'tset', '35.71347277847191,51.41378402709962', '10.00', 'circle', 1, '2025-04-25 09:26:07.053', '2025-04-25 09:27:36.157'),
(4, 1, 'tset', '35.71347277847191,51.41378402709962', '600.00', 'circle', 1, '2025-04-25 09:26:42.679', '2025-04-25 09:27:38.904'),
(5, 1, 'tset', '[[{\"lat\":35.69589804035018,\"lng\":51.43695831298828},{\"lat\":35.69520060658579,\"lng\":51.4541244506836},{\"lat\":35.679018431155946,\"lng\":51.45463943481446},{\"lat\":35.67887891537303,\"lng\":51.437644958496094}]]', NULL, 'polygon', 1, '2025-04-25 10:06:23.377', '2025-04-25 10:12:16.823'),
(6, 1, 'tset', '[[{\"lat\":35.69589804035018,\"lng\":51.43695831298828},{\"lat\":35.69520060658579,\"lng\":51.4541244506836},{\"lat\":35.679018431155946,\"lng\":51.45463943481446},{\"lat\":35.67887891537303,\"lng\":51.437644958496094}]]', NULL, 'polygon', 1, '2025-04-25 10:06:23.386', '2025-04-25 11:25:31.921'),
(7, 1, '111', '[35.71151895805695,51.40554428100586]', '2252.00', 'circle', 1, '2025-04-25 10:07:19.726', '2025-04-25 10:53:28.938'),
(8, 1, '111', '[35.71151895805695,51.40554428100586]', '2252.00', 'circle', 1, '2025-04-25 10:07:19.726', '2025-04-25 10:12:22.344'),
(9, 1, 'QAZ', '[[{\"lat\":35.706777217297066,\"lng\":51.34099960327149},{\"lat\":35.71765490971248,\"lng\":51.34099960327149},{\"lat\":35.71765490971248,\"lng\":51.3636589050293},{\"lat\":35.706777217297066,\"lng\":51.3636589050293}]]', NULL, 'polygon', 1, '2025-04-25 10:52:44.298', '2025-04-25 11:25:33.767'),
(10, 1, 'MASHAD', '[[{\"lat\":36.19109562773304,\"lng\":59.40650939941407},{\"lat\":36.428597054790316,\"lng\":59.40650939941407},{\"lat\":36.428597054790316,\"lng\":59.729232788085945},{\"lat\":36.19109562773304,\"lng\":59.729232788085945}]]', NULL, 'polygon', 1, '2025-04-25 11:26:25.396', '2025-05-02 15:16:47.691'),
(11, 1, 'mashhad', '{\"lat\":36.416336522888805,\"lng\":59.34059143066407}', '17430.00', 'circle', 1, '2025-04-25 15:34:09.805', '2025-04-25 15:34:33.671'),
(12, 1, 'mashad_m', '[[{\"lat\":36.22057532575037,\"lng\":59.44976806640626},{\"lat\":36.403263984137844,\"lng\":59.44976806640626},{\"lat\":36.403263984137844,\"lng\":59.72579956054688},{\"lat\":36.22057532575037,\"lng\":59.72579956054688}]]', NULL, 'polygon', 1, '2025-04-25 15:35:02.698', '2025-05-02 15:41:15.279'),
(13, 1, 'mashad_d', '{\"lat\":36.37978042961818,\"lng\":59.47792053222657}', '13219.00', 'circle', 1, '2025-04-25 15:35:53.586', '2025-05-02 15:41:13.420'),
(14, 1, 'حصار چند ضلعی', '[[{\"lat\":36.358338787621186,\"lng\":59.47611808776856},{\"lat\":36.36193200582579,\"lng\":59.47440147399903},{\"lat\":36.361241015208876,\"lng\":59.46985244750977},{\"lat\":36.36725242807071,\"lng\":59.469766616821296},{\"lat\":36.37229612881725,\"lng\":59.47809219360352},{\"lat\":36.368150645283926,\"lng\":59.48693275451661},{\"lat\":36.36055001845665,\"lng\":59.48967933654786},{\"lat\":36.35612749396259,\"lng\":59.48598861694337}]]', NULL, 'polygon', 0, '2025-05-02 15:44:50.779', '2025-05-02 19:02:10.862'),
(15, 1, 'حصار مثلث', '[[{\"lat\":36.358135089194874,\"lng\":59.47465896606446},{\"lat\":36.37167792742674,\"lng\":59.48341369628907},{\"lat\":36.358757005844865,\"lng\":59.48993682861329}]]', NULL, 'polygon', 0, '2025-05-02 15:49:51.382', '2025-05-02 15:53:35.720'),
(16, 1, 'حصار مستطیل', '[[{\"lat\":36.360489116587736,\"lng\":59.47508811950684},{\"lat\":36.365533255744076,\"lng\":59.47508811950684},{\"lat\":36.365533255744076,\"lng\":59.49405670166016},{\"lat\":36.360489116587736,\"lng\":59.49405670166016}]]', NULL, 'polygon', 0, '2025-05-02 15:50:16.425', '2025-05-02 19:02:05.313'),
(17, 1, 'حصار دایره ای', '{\"lat\":36.36124523219857,\"lng\":59.48611736297608}', '769.00', 'circle', 0, '2025-05-02 15:51:03.826', '2025-05-04 19:17:25.243');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
CREATE TABLE IF NOT EXISTS `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر جلسه',
  `title` varchar(255) NOT NULL COMMENT 'عنوان جلسه یا مجمع',
  `type` enum('annual','extraordinary','other') NOT NULL COMMENT 'نوع جلسه: عادی سالانه، فوق‌العاده، سایر',
  `province` varchar(100) NOT NULL COMMENT 'استان برگزاری جلسه',
  `address` text NOT NULL COMMENT 'آدرس دقیق محل برگزاری',
  `start_time` datetime NOT NULL COMMENT 'زمان شروع جلسه',
  `end_time` datetime DEFAULT NULL COMMENT 'زمان پایان جلسه',
  `status` enum('pending','active','finished') NOT NULL DEFAULT 'pending' COMMENT 'وضعیت جلسه: در انتظار، در حال برگزاری، پایان یافته',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم جلسه',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='جدول اطلاعات جلسات و مجامع';

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `title`, `type`, `province`, `address`, `start_time`, `end_time`, `status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'تست', 'annual', 'مشهد', 'ادرس تست', '2025-04-25 11:48:41', '2025-04-25 11:48:46', 'active', 0, '2025-04-25 09:18:41.000', '2025-04-25 10:18:41.000');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
CREATE TABLE IF NOT EXISTS `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر عضو',
  `first_name` varchar(100) NOT NULL COMMENT 'نام عضو',
  `last_name` varchar(100) NOT NULL COMMENT 'نام خانوادگی عضو',
  `national_code` varchar(10) NOT NULL COMMENT 'کد ملی عضو (منحصر به فرد)',
  `gender` tinyint(1) DEFAULT NULL COMMENT 'جنسیت 1 اقا 2 خانم',
  `mobile` varchar(11) NOT NULL COMMENT 'شماره موبایل عضو',
  `membership_number` varchar(20) NOT NULL COMMENT 'شماره عضویت در سازمان',
  `birth_date` date NOT NULL COMMENT 'تاریخ تولد عضو',
  `membership_expiry` date NOT NULL COMMENT 'تاریخ اعتبار عضویت',
  `photo_path` varchar(255) DEFAULT NULL COMMENT 'مسیر ذخیره عکس عضو',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم عضو',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد با دقت میلی‌ثانیه',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `national_code` (`national_code`),
  UNIQUE KEY `membership_number` (`membership_number`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='جدول اصلی اطلاعات اعضای سازمان';

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `first_name`, `last_name`, `national_code`, `gender`, `mobile`, `membership_number`, `birth_date`, `membership_expiry`, `photo_path`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'qq', 'ww', '1111111111', 1, '11111111111', '1111111111', '2013-04-01', '2025-04-30', 'aaaaaa', 0, '2025-04-24 17:07:58.000', '2025-06-06 16:20:31.299'),
(2, 'qq1', 'ww1', '1111111110', 2, '11111111110', '1111111110', '2010-04-02', '2025-04-30', 'bbbbb', 0, '2025-04-24 17:07:58.000', '2025-06-06 16:20:38.211'),
(5, 'qq2', 'ww2', '1111111112', 1, '11111111112', '1111111112', '2012-04-03', '2025-04-30', 'cccc', 0, '2025-04-24 17:07:58.000', '2025-06-06 16:20:45.266'),
(6, 'qq3', 'ww3', '1111111113', 1, '11111111113', '1111111113', '2015-04-04', '2025-04-30', 'aaaaaa', 0, '2025-04-24 17:07:58.000', '2025-06-06 16:21:03.963'),
(11, 'qq4', 'ww4', '1111111114', 2, '11111111114', '1111111114', '2014-04-05', '2025-04-30', 'aaaaaa', 0, '2025-04-24 17:07:58.000', '2025-06-06 16:21:07.929'),
(12, 'qq5', 'ww5', '1111111115', 2, '11111111115', '1111111115', '2014-04-05', '2025-04-30', 'aaaaaa', 0, '2025-04-24 17:07:58.000', '2025-06-06 16:21:07.929');

-- --------------------------------------------------------

--
-- Table structure for table `minutes`
--

DROP TABLE IF EXISTS `minutes`;
CREATE TABLE IF NOT EXISTS `minutes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر صورتجلسه',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `content` text NOT NULL COMMENT 'محتویات صورتجلسه',
  `file_path` varchar(255) DEFAULT NULL COMMENT 'مسیر ذخیره فایل PDF صورتجلسه',
  `approval_status` enum('draft','approved','rejected') NOT NULL DEFAULT 'draft' COMMENT 'وضعیت تأیید صورتجلسه',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم صورتجلسه',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='جدول صورتجلسات نهایی';

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر سوال',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه مرتبط (foreign key)',
  `title` text NOT NULL COMMENT 'متن سوال رأی‌گیری',
  `description` text DEFAULT NULL COMMENT 'توضیحات تکمیلی سوال',
  `status` enum('pending','active','finished') NOT NULL DEFAULT 'pending' COMMENT 'وضعیت سوال: در انتظار، فعال، پایان یافته',
  `start_time` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان شروع رأی‌گیری',
  `end_time` timestamp(3) NULL DEFAULT NULL COMMENT 'زمان پایان رأی‌گیری',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم سوال',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  `updated_at` timestamp(3) NULL DEFAULT NULL ON UPDATE current_timestamp(3) COMMENT 'تاریخ و زمان به‌روزرسانی رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='جدول سوالات رأی‌گیری در جلسات';

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title_fa` varchar(100) NOT NULL COMMENT 'نام تنظیم به فارسی',
  `key_name` varchar(100) NOT NULL COMMENT 'نام انگلیسی/کلید تنظیم',
  `description` text DEFAULT NULL COMMENT 'توضیحات',
  `used_in` varchar(100) DEFAULT NULL COMMENT 'در چه صفحه‌ای استفاده شده',
  `setting_value` varchar(100) NOT NULL COMMENT 'مقدار تنظیم',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `title_fa`, `key_name`, `description`, `used_in`, `setting_value`, `is_deleted`, `created_at`) VALUES
(1, 'تایید ورود مجدد', 'modal_confirm_checkin_again', 'در صورت ورود مجدد عضو به جلسه، آیا مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(2, 'تایید خروج بدون ورود', 'modal_confirm_checkout_without_checkin', 'اگر عضو بدون ثبت ورود بخواهد خروج ثبت کند، آیا مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(3, 'تایید خروج مجدد', 'modal_confirm_checkout_again', 'در صورت خروج مجدد عضو، آیا مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(4, 'تایید صدور برگه رای در وضعیت غیرعادی', 'modal_confirm_vote_paper', 'اگر ورود/خروج عضو غیرعادی باشد، آیا برای صدور برگه رای مدال تایید نمایش داده شود؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(5, 'اجازه فقط به ادمین برای ورود مجدد', 'admin_only_checkin_again', 'آیا فقط ادمین اجازه ثبت ورود مجدد داشته باشد؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(6, 'اجازه فقط به ادمین برای خروج بدون ورود', 'admin_only_checkout_without_checkin', 'آیا فقط ادمین اجازه ثبت خروج بدون ورود را داشته باشد؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(7, 'اجازه فقط به ادمین برای خروج مجدد', 'admin_only_checkout_again', 'آیا فقط ادمین اجازه ثبت خروج مجدد را داشته باشد؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(8, 'اجازه فقط به ادمین برای صدور برگه رای غیرعادی', 'admin_only_vote_paper_abnormal', 'آیا فقط ادمین اجازه صدور برگه رای در وضعیت غیرعادی را داشته باشد؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(9, 'پاکسازی لیست و فوکوس بعد از عملیات', 'clear_list_and_focus_search_after_action', 'بعد از ثبت حضور/غیاب یا صدور برگه رای، لیست اعضا پاک شود و فوکوس به جستجو برگردد.', 'index.php', '1', 0, '2025-06-06 14:33:01.548'),
(10, 'عرض برگه رای (میلیمتر)', 'vote_paper_width_mm', 'عرض برگه رای به میلیمتر جهت چاپ', 'member_action.php,index.php', '80', 0, '2025-06-06 14:33:01.548'),
(11, 'حالت صدور برگه رای', 'vote_paper_mode', 'حالت صدور برگه رای: system (تولید توسط سیستم) یا preprinted (برگه‌های چاپی با بارکد از قبل)', 'member_action.php,index.php', 'system', 0, '2025-06-06 14:33:01.548'),
(12, 'فعال‌سازی اسکن بارکد برگه رای', 'enable_vote_barcode_scan', 'آیا کادر اسکن بارکد در مدال چاپ برگه رای نمایش داده شود؟', 'index.php,member_action.php', '1', 0, '2025-06-06 14:33:01.548'),
(13, 'تولید و نمایش بارکد روی برگه رای', 'enable_vote_barcode_generation', 'آیا بارکد برای برگه رای تولید و روی آن نمایش داده شود؟', 'member_action.php,index.php', '1', 0, '2025-06-06 14:33:01.548'),
(14, 'اعتبارسنجی بارکد در حالت سیستمی', 'enable_vote_barcode_validation', 'در حالت system اگر فعال باشد کد اسکن شده باید دقیقا با بارکد برگه رای مطابقت داشته باشد.', 'index.php,member_action.php', '1', 0, '2025-06-06 14:33:01.548'),
(15, 'بررسی یکتا بودن سریال برگه رای', 'check_vote_serial_unique', 'در موقع صدور برگه رای سیستمی، یکتا بودن سریال بررسی شود و از ایجاد سریال تکراری جلوگیری گردد.', 'member_action.php', '1', 0, '2025-06-06 14:33:01.548');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE IF NOT EXISTS `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید اصلی و یکتا برای هر رأی',
  `question_id` int(11) NOT NULL COMMENT 'آیدی سوال مرتبط (foreign key)',
  `member_id` int(11) NOT NULL COMMENT 'آیدی عضو (foreign key)',
  `vote` enum('yes','no') NOT NULL COMMENT 'رأی عضو: موافق یا مخالف',
  `vote_time` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'زمان ثبت رأی با دقت میلی‌ثانیه',
  `location_status` enum('in','out') NOT NULL COMMENT 'وضعیت موقعیت عضو هنگام رأی دادن',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم رأی',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='جدول آراء اعضا در سوالات رأی‌گیری';

-- --------------------------------------------------------

--
-- Table structure for table `votes_log`
--

DROP TABLE IF EXISTS `votes_log`;
CREATE TABLE IF NOT EXISTS `votes_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `admin_username` varchar(100) NOT NULL,
  `attendance_status` enum('in','out','none') NOT NULL COMMENT 'وضعیت حضور وقتی برگه داده شد',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_confirmed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'آیا کاربر تأیید کرده بود؟',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `votes_log`
--

INSERT INTO `votes_log` (`id`, `member_id`, `admin_username`, `attendance_status`, `issued_at`, `is_confirmed`) VALUES
(1, 6, 'demo', 'out', '2025-06-06 10:39:47', 1),
(2, 6, 'demo', 'out', '2025-06-06 10:56:52', 1),
(3, 6, 'demo', 'out', '2025-06-06 10:57:11', 1),
(4, 6, 'demo', 'out', '2025-06-06 10:57:19', 1),
(5, 12, 'demo', 'none', '2025-06-06 15:10:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `vote_papers`
--

DROP TABLE IF EXISTS `vote_papers`;
CREATE TABLE IF NOT EXISTS `vote_papers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'کلید یکتا برای هر برگه رأی',
  `meeting_id` int(11) NOT NULL COMMENT 'آیدی جلسه',
  `barcode` varchar(64) NOT NULL COMMENT 'کد یکتا (بارکد) برگه',
  `is_issued` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'آیا به عضوی تخصیص داده شده',
  `issued_to_user_id` int(11) DEFAULT NULL COMMENT 'آیدی کاربر تحویل دهنده',
  `issued_to_member_id` int(11) DEFAULT NULL COMMENT 'آیدی عضو تحویل گیرنده',
  `issued_at` timestamp NULL DEFAULT NULL COMMENT 'زمان صدور/تحویل',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT '1 به معنی حذف نرم رأی',
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3) COMMENT 'تاریخ و زمان ایجاد رکورد',
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `meeting_id` (`meeting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='برگه‌های رأی یکتا با بارکد';

--
-- Dumping data for table `vote_papers`
--

INSERT INTO `vote_papers` (`id`, `meeting_id`, `barcode`, `is_issued`, `issued_to_user_id`, `issued_to_member_id`, `issued_at`, `is_deleted`, `created_at`) VALUES
(1, 1, 'SN-00000006-1408-83', 1, 1, 6, '2025-06-06 10:38:27', 0, '2025-06-06 14:08:27.507'),
(2, 1, 'SN-00000006-1409-94', 1, 1, 6, '2025-06-06 10:39:47', 0, '2025-06-06 14:09:47.359'),
(3, 1, 'SN-00000006-1426-71', 1, 1, 6, '2025-06-06 10:56:52', 0, '2025-06-06 14:26:52.136'),
(4, 1, 'SN-00000006-1427-32', 1, 1, 6, '2025-06-06 10:57:11', 0, '2025-06-06 14:27:11.061'),
(5, 1, 'SN-00000006-1427-51', 1, 1, 6, '2025-06-06 10:57:19', 0, '2025-06-06 14:27:19.736'),
(6, 1, 'SN-00000012-1840-81', 1, 1, 12, '2025-06-06 15:10:40', 0, '2025-06-06 18:40:40.179');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `votes_log`
--
ALTER TABLE `votes_log`
  ADD CONSTRAINT `votes_log_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
