-- =====================================================
-- TCG Game Platform Integration
-- =====================================================

-- Thêm TCG platform vào bảng caipiao_game_platform
INSERT INTO `caipiao_game_platform` 
(`code`, `name`, `slot_name`, `live_name`, `fishing_name`, `type`, `kind`, `icon`, `mobile_icon`, `banner`, `mobile_banner`, `status`, `hot`, `recommend`, `sort`, `api_url`, `api_key`, `api_secret`, `api_provider`, `created_at`, `updated_at`) 
VALUES 
('TCG', 'TCG Games', 'TCG Slot', 'TCG Live', 'TCG Fishing', 'slot', 'third', '/admin/common/tcg-icon.png', '/admin/common/tcg-mobile-icon.png', '/admin/common/tcg-banner.png', '/admin/common/tcg-mobile-banner.png', 'online', 1, 1, 100, 'http://www.connect4play.com/doBusiness.do', 'ewiinvndk', 'ld1AN3saSuowR7wb', 'TCG', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
`name` = VALUES(`name`),
`api_url` = VALUES(`api_url`),
`api_key` = VALUES(`api_key`),
`api_secret` = VALUES(`api_secret`),
`api_provider` = VALUES(`api_provider`),
`updated_at` = UNIX_TIMESTAMP();

-- =====================================================
-- Bảng ánh xạ user với TCG member
-- =====================================================
DROP TABLE IF EXISTS `caipiao_tcg_member`;
CREATE TABLE `caipiao_tcg_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'ID người dùng hệ thống',
  `tcg_username` varchar(50) NOT NULL COMMENT 'Username TCG (có prefix)',
  `status` tinyint(1) DEFAULT '1' COMMENT 'Trạng thái: 1=active, 0=inactive',
  `created_at` int(11) NOT NULL COMMENT 'Thời gian tạo',
  `updated_at` int(11) DEFAULT '0' COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uid` (`uid`),
  UNIQUE KEY `idx_tcg_username` (`tcg_username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng ánh xạ user với TCG member';

-- =====================================================
-- Bảng lưu lịch sử FTP sync
-- =====================================================
DROP TABLE IF EXISTS `caipiao_tcg_ftp_sync`;
CREATE TABLE `caipiao_tcg_ftp_sync` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL COMMENT 'Tên file CSV',
  `file_date` date NOT NULL COMMENT 'Ngày của file',
  `total_records` int(11) DEFAULT '0' COMMENT 'Tổng số bản ghi',
  `processed_records` int(11) DEFAULT '0' COMMENT 'Số bản ghi đã xử lý',
  `failed_records` int(11) DEFAULT '0' COMMENT 'Số bản ghi lỗi',
  `status` varchar(20) DEFAULT 'pending' COMMENT 'Trạng thái: pending, processing, completed, failed',
  `error_message` text COMMENT 'Thông báo lỗi nếu có',
  `started_at` int(11) DEFAULT '0' COMMENT 'Thời gian bắt đầu xử lý',
  `completed_at` int(11) DEFAULT '0' COMMENT 'Thời gian hoàn thành',
  `created_at` int(11) NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filename` (`filename`),
  KEY `idx_file_date` (`file_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lịch sử đồng bộ FTP TCG';

-- =====================================================
-- Bảng queue cho transfer retry
-- =====================================================
DROP TABLE IF EXISTS `caipiao_tcg_transfer_queue`;
CREATE TABLE `caipiao_tcg_transfer_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL COMMENT 'Mã đơn transfer',
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `tcg_username` varchar(50) NOT NULL COMMENT 'Username TCG',
  `type` varchar(10) NOT NULL COMMENT 'Loại: IN hoặc OUT',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền',
  `retry_count` int(11) DEFAULT '0' COMMENT 'Số lần retry',
  `max_retry` int(11) DEFAULT '3' COMMENT 'Số lần retry tối đa',
  `status` varchar(20) DEFAULT 'pending' COMMENT 'Trạng thái: pending, processing, success, failed',
  `error_message` text COMMENT 'Thông báo lỗi',
  `next_retry_at` int(11) DEFAULT '0' COMMENT 'Thời gian retry tiếp theo',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`),
  KEY `idx_uid` (`uid`),
  KEY `idx_status` (`status`),
  KEY `idx_next_retry` (`next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Queue retry transfer TCG';

-- =====================================================
-- Thêm games TCG vào bảng caipiao_game
-- =====================================================
INSERT INTO `caipiao_game`
(`platform`, `game_id`, `name`, `type`, `status`, `hot`, `new`, `sort`, `icon`, `cover`, `created_at`, `updated_at`)
VALUES
('TCG', 'TCG_SLOT_LOBBY', 'TCG Slot', 'slot', 'online', 1, 0, 1, '/admin/common/tcg-slot.png', '/admin/common/tcg-slot.png', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TCG', 'TCG_LIVE_LOBBY', 'TCG Live Casino', 'live', 'online', 1, 0, 2, '/admin/common/tcg-live.png', '/admin/common/tcg-live.png', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TCG', 'TCG_FISHING_LOBBY', 'TCG Fishing', 'fishing', 'online', 1, 0, 3, '/admin/common/tcg-fishing.png', '/admin/common/tcg-fishing.png', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`status` = VALUES(`status`),
`updated_at` = UNIX_TIMESTAMP();

-- =====================================================
-- Cập nhật .env configuration (chạy thủ công)
-- =====================================================
-- Thêm vào file boyue/.env:
-- TCG_API_URL=http://www.connect4play.com/doBusiness.do
-- TCG_MERCHANT_CODE=ewiinvndk
-- TCG_DES_KEY=u2nkrkQV
-- TCG_SHA256_KEY=ld1AN3saSuowR7wb
-- TCG_USERNAME_PREFIX=14b_
-- TCG_FTP_HOST=123.51.167.66
-- TCG_FTP_USER=ewiinvndk
-- TCG_FTP_PASS=a123456
-- TCG_FTP_SYNC_INTERVAL=300

-- Made with Bob
