-- GSC+ Game Categories and Platforms Management Tables
-- Created: 2026-05-21
-- Purpose: Admin management for game categories and supported platforms

-- Game Categories Table
CREATE TABLE IF NOT EXISTS `wa_gscplus_game_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Category ID',
  `code` varchar(50) NOT NULL COMMENT 'Category Code (e.g., NO_HU, CASINO)',
  `name` varchar(100) NOT NULL COMMENT 'Category Name',
  `gsc_type` varchar(50) NOT NULL COMMENT 'GSC+ Game Type (e.g., SLOT, LIVE_CASINO)',
  `description` varchar(255) DEFAULT NULL COMMENT 'Category Description',
  `icon` varchar(255) DEFAULT NULL COMMENT 'Category Icon',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Display Order',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Status: 1=Active, 0=Inactive',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Time',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Time',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='GSC+ Game Categories';

-- Game Platforms Table
CREATE TABLE IF NOT EXISTS `wa_gscplus_platforms` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Platform ID',
  `category_id` int(11) NOT NULL COMMENT 'Category ID',
  `code` varchar(50) NOT NULL COMMENT 'Platform Code (e.g., PG, JILI)',
  `name` varchar(100) NOT NULL COMMENT 'Platform Name',
  `product_code` varchar(50) NOT NULL COMMENT 'GSC+ Product Code',
  `pt_percentage` decimal(5,2) DEFAULT 0.00 COMMENT 'PT Percentage',
  `logo` varchar(255) DEFAULT NULL COMMENT 'Platform Logo URL',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Display Order',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Status: 1=Active, 0=Inactive',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Time',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Time',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_category_code` (`category_id`, `code`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_platform_category` FOREIGN KEY (`category_id`) REFERENCES `wa_gscplus_game_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='GSC+ Game Platforms';

-- Insert default game categories
INSERT INTO `wa_gscplus_game_categories` (`id`, `code`, `name`, `gsc_type`, `description`, `sort_order`, `status`) VALUES
(1, 'NO_HU', 'Slot Games (Nổ Hũ)', 'SLOT', 'Slot Games', 1, 1),
(2, 'CASINO', 'Live Casino', 'LIVE_CASINO', 'Live Casino Games', 2, 1),
(3, 'BAN_CA', 'Fishing Games (Bắn Cá)', 'FISHING', 'Fishing Games', 3, 1),
(4, 'GAME_BAI', 'Table Games (Game Bài)', 'TABLE_GAME', 'Table Games', 4, 1),
(5, 'THE_THAO', 'Sportsbook (Thể Thao)', 'SPORTSBOOK', 'Sports Betting', 5, 1),
(6, 'DA_GA', 'Cockfighting (Đá Gà)', 'COCKFIGHTING', 'Cockfighting', 6, 1),
(7, 'XO_SO', 'Lottery (Xổ Số)', 'LOTTERY', 'Lottery Games', 7, 1),
(8, 'E_SPORTS', 'E-Sports', 'E_SPORTS', 'E-Sports Betting', 8, 1),
(9, 'POKER', 'Poker', 'POKER', 'Poker Games', 9, 1);

-- Insert default platforms for SLOT Games (NO_HU)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(1, 'PG', 'PG Soft', '1002', 6.00, 1, 1),
(1, 'JILI', 'JILI', '1011', 8.00, 2, 1),
(1, 'JDB', 'JDB', '1013', 8.00, 3, 1),
(1, 'PP', 'Pragmatic Play', '1001', 8.00, 4, 1),
(1, 'FC', 'FaChai', '1014', 8.00, 5, 1),
(1, 'CQ9', 'CQ9', '1015', 8.00, 6, 1),
(1, 'SPADE', 'Spade Gaming', '1016', 9.00, 7, 1),
(1, 'JOKER', 'Joker', '1017', 7.00, 8, 1),
(1, 'HABANERO', 'Habanero', '1018', 8.00, 9, 1),
(1, 'KA', 'KA Gaming', '1019', 8.00, 10, 1),
(1, 'NAGA', 'Naga Games', '1020', 7.00, 11, 1);

-- Insert default platforms for LIVE CASINO (CASINO)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(2, 'DG', 'Dream Gaming', '1021', 7.00, 1, 1),
(2, 'SEXY', 'Sexy Baccarat', '1022', 8.00, 2, 1),
(2, 'AG', 'Asia Gaming', '1023', 7.00, 3, 1),
(2, 'BB', 'Big Gaming', '1024', 7.00, 4, 1),
(2, 'SA', 'SA Gaming', '1025', 7.00, 5, 1),
(2, 'WM', 'WM Casino', '1026', 7.00, 6, 1),
(2, 'EVO', 'Evolution Gaming', '1027', 10.00, 7, 1),
(2, 'PRETTY', 'Pretty Gaming', '1028', 9.00, 8, 1);

-- Insert default platforms for FISHING Games (BAN_CA)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(3, 'JILI', 'JILI', '1011', 8.00, 1, 1),
(3, 'JDB', 'JDB', '1013', 8.00, 2, 1),
(3, 'CQ9', 'CQ9', '1015', 8.00, 3, 1),
(3, 'SPADE', 'Spade Gaming', '1016', 9.00, 4, 1);

-- Insert default platforms for TABLE GAMES (GAME_BAI)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(4, 'V8', 'V8 Poker', '1029', 8.00, 1, 1),
(4, 'KY', 'KaiYuan Gaming', '1030', 9.00, 2, 1),
(4, 'LEG', 'LeGaming', '1031', 9.00, 3, 1);

-- Insert default platforms for SPORTSBOOK (THE_THAO)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(5, 'SABA', 'SABA Sports', '1032', 11.00, 1, 1),
(5, 'CMD', 'CMD368', '1033', 12.00, 2, 1),
(5, 'SBO', 'SBO Sports', '1034', 11.00, 3, 1),
(5, 'BTI', 'BTI Sports', '1035', 10.00, 4, 1),
(5, 'IM', 'IM Sports', '1036', 12.00, 5, 1);

-- Insert default platforms for COCKFIGHTING (DA_GA)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(6, 'SV388', 'SV388', '1037', 12.00, 1, 1),
(6, 'WS168', 'WS168', '1038', 12.00, 2, 1);

-- Insert default platforms for LOTTERY (XO_SO)
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(7, 'VR', 'VR Lottery', '1039', 7.00, 1, 1),
(7, 'GW', 'GW Lottery', '1040', 10.00, 2, 1),
(7, 'TCG', 'TCG Lottery', '1041', 7.00, 3, 1);

-- Insert default platforms for E-SPORTS
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(8, 'TF', 'TF Gaming', '1042', 10.00, 1, 1);

-- Insert default platforms for POKER
INSERT INTO `wa_gscplus_platforms` (`category_id`, `code`, `name`, `product_code`, `pt_percentage`, `sort_order`, `status`) VALUES
(9, 'SPRIBE', 'Spribe', '1043', 7.00, 1, 1);

-- Add admin menu items for Game Categories management
INSERT INTO `wa_rules` (`title`, `icon`, `key`, `pid`, `href`, `type`, `weight`, `created_at`, `updated_at`) 
SELECT 'GSC+ Games', 'layui-icon-component', 'gscplus_games', 0, '', 0, 100, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `wa_rules` WHERE `key` = 'gscplus_games');

SET @parent_id = (SELECT `id` FROM `wa_rules` WHERE `key` = 'gscplus_games' LIMIT 1);

INSERT INTO `wa_rules` (`title`, `icon`, `key`, `pid`, `href`, `type`, `weight`, `created_at`, `updated_at`) 
SELECT 'Game Categories', 'layui-icon-template-1', 'gscplus_categories', @parent_id, '/app/admin/gscplus/category', 1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `wa_rules` WHERE `key` = 'gscplus_categories');

INSERT INTO `wa_rules` (`title`, `icon`, `key`, `pid`, `href`, `type`, `weight`, `created_at`, `updated_at`) 
SELECT 'Game Platforms', 'layui-icon-template', 'gscplus_platforms', @parent_id, '/app/admin/gscplus/platform', 1, 2, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `wa_rules` WHERE `key` = 'gscplus_platforms');