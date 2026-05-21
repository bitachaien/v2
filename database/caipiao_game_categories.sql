-- Game Categories Table
-- Stores game category information for GSC+ integration

CREATE TABLE IF NOT EXISTS `caipiao_game_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Category ID',
  `code` varchar(50) NOT NULL COMMENT 'Category code (NO_HU, CASINO, etc.)',
  `name` varchar(100) NOT NULL COMMENT 'Category name',
  `gsc_type` varchar(50) NOT NULL COMMENT 'GSC+ game type (SLOT, LIVE_CASINO, etc.)',
  `description` varchar(255) DEFAULT NULL COMMENT 'Category description',
  `icon` varchar(255) DEFAULT NULL COMMENT 'Category icon URL',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Display order',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Status: 1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created time',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated time',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Game Categories';

-- Insert default categories
INSERT INTO `caipiao_game_categories` (`id`, `code`, `name`, `gsc_type`, `description`, `sort_order`, `status`) VALUES
(1, 'NO_HU', 'Nổ Hũ', 'SLOT', 'Slot Games', 1, 1),
(2, 'CASINO', 'Casino', 'LIVE_CASINO', 'Live Casino', 2, 1),
(3, 'BAN_CA', 'Bắn Cá', 'FISHING', 'Fishing Games', 3, 1),
(4, 'GAME_BAI', 'Game Bài', 'TABLE_GAME', 'Table Games', 4, 1),
(5, 'THE_THAO', 'Thể Thao', 'SPORTSBOOK', 'Sportsbook', 5, 1),
(6, 'DA_GA', 'Đá Gà', 'COCKFIGHTING', 'Cockfighting', 6, 1),
(7, 'XO_SO', 'Xổ Số', 'LOTTERY', 'Lottery', 7, 1),
(8, 'E_SPORTS', 'E-Sports', 'E_SPORTS', 'E-Sports', 8, 1),
(9, 'POKER', 'Poker', 'POKER', 'Poker', 9, 1);