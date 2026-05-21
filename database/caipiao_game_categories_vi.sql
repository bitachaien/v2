-- Bảng Danh Mục Trò Chơi
-- Lưu trữ thông tin danh mục trò chơi cho tích hợp GSC+

CREATE TABLE IF NOT EXISTS `caipiao_game_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID danh mục',
  `code` varchar(50) NOT NULL COMMENT 'Mã danh mục (NO_HU, CASINO, v.v.)',
  `name` varchar(100) NOT NULL COMMENT 'Tên danh mục',
  `gsc_type` varchar(50) NOT NULL COMMENT 'Loại trò chơi GSC+ (SLOT, LIVE_CASINO, v.v.)',
  `description` varchar(255) DEFAULT NULL COMMENT 'Mô tả danh mục',
  `icon` varchar(255) DEFAULT NULL COMMENT 'URL biểu tượng danh mục',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Trạng thái: 1=Hoạt động, 0=Không hoạt động',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Danh Mục Trò Chơi';

-- Chèn các danh mục mặc định
INSERT INTO `caipiao_game_categories` (`id`, `code`, `name`, `gsc_type`, `description`, `sort_order`, `status`) VALUES
(1, 'NO_HU', 'Nổ Hũ', 'SLOT', 'Trò chơi Slot', 1, 1),
(2, 'CASINO', 'Casino', 'LIVE_CASINO', 'Casino Trực Tiếp', 2, 1),
(3, 'BAN_CA', 'Bắn Cá', 'FISHING', 'Trò chơi Bắn Cá', 3, 1),
(4, 'GAME_BAI', 'Game Bài', 'TABLE_GAME', 'Trò chơi Bài', 4, 1),
(5, 'THE_THAO', 'Thể Thao', 'SPORTSBOOK', 'Cá cược Thể thao', 5, 1),
(6, 'DA_GA', 'Đá Gà', 'COCKFIGHTING', 'Đá Gà', 6, 1),
(7, 'XO_SO', 'Xổ Số', 'LOTTERY', 'Xổ Số', 7, 1),
(8, 'E_SPORTS', 'E-Sports', 'E_SPORTS', 'Thể thao Điện tử', 8, 1),
(9, 'POKER', 'Poker', 'POKER', 'Poker', 9, 1);

-- Made with Bob
