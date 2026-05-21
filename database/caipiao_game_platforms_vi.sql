-- Bảng Nền Tảng Trò Chơi
-- Lưu trữ thông tin nền tảng trò chơi cho tích hợp GSC+

CREATE TABLE IF NOT EXISTS `caipiao_game_platforms` (
  `id` int(11) NOT NULL COMMENT 'ID nền tảng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục (khóa ngoại)',
  `code` varchar(50) NOT NULL COMMENT 'Mã nền tảng (PG, JILI, JDB, v.v.)',
  `name` varchar(100) NOT NULL COMMENT 'Tên nền tảng',
  `product_code` int(11) NOT NULL COMMENT 'Mã sản phẩm GSC+',
  `pt_percent` decimal(5,2) DEFAULT 0.00 COMMENT 'Phần trăm PT',
  `icon` varchar(255) DEFAULT NULL COMMENT 'URL biểu tượng nền tảng',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Trạng thái: 1=Hoạt động, 0=Không hoạt động',
  `remarks` varchar(255) DEFAULT NULL COMMENT 'Ghi chú/Chú thích',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_category_code` (`category_id`, `code`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_platform_category` FOREIGN KEY (`category_id`) REFERENCES `caipiao_game_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Nền Tảng Trò Chơi';

-- Chèn nền tảng SLOT (Danh mục 1: NO_HU)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(200, 1, 'PG', 'PG Soft', 1002, 6.00, 1, 1),
(201, 1, 'JILI', 'JILI', 1011, 8.00, 2, 1),
(202, 1, 'JDB', 'JDB', 1013, 8.00, 3, 1),
(203, 1, 'PP', 'Pragmatic Play', 1001, 8.00, 4, 1),
(204, 1, 'FC', 'FaChai', 1014, 8.00, 5, 1),
(205, 1, 'CQ9', 'CQ9', 1015, 8.00, 6, 1),
(206, 1, 'SPADE', 'Spade Gaming', 1016, 9.00, 7, 1),
(207, 1, 'JOKER', 'Joker', 1017, 7.00, 8, 1),
(208, 1, 'HABANERO', 'Habanero', 1018, 8.00, 9, 1),
(209, 1, 'KA', 'KA Gaming', 1019, 8.00, 10, 1),
(210, 1, 'NAGA', 'Naga Games', 1020, 7.00, 11, 1);

-- Chèn nền tảng CASINO TRỰC TIẾP (Danh mục 2: CASINO)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(300, 2, 'DG', 'Dream Gaming', 1021, 7.00, 1, 1),
(301, 2, 'SEXY', 'Sexy Baccarat', 1022, 8.00, 2, 1),
(302, 2, 'AG', 'Asia Gaming', 1023, 7.00, 3, 1),
(303, 2, 'BB', 'Big Gaming', 1024, 7.00, 4, 1),
(304, 2, 'SA', 'SA Gaming', 1025, 7.00, 5, 1),
(305, 2, 'WM', 'WM Casino', 1026, 7.00, 6, 1),
(306, 2, 'EVO', 'Evolution Gaming', 1027, 10.00, 7, 1),
(307, 2, 'PRETTY', 'Pretty Gaming', 1028, 9.00, 8, 1);

-- Chèn nền tảng BẮN CÁ (Danh mục 3: BAN_CA)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(315, 3, 'JILI', 'JILI', 1011, 8.00, 1, 1),
(316, 3, 'JDB', 'JDB', 1013, 8.00, 2, 1),
(317, 3, 'CQ9', 'CQ9', 1015, 8.00, 3, 1),
(318, 3, 'SPADE', 'Spade Gaming', 1016, 9.00, 4, 1);

-- Chèn nền tảng TRÒ CHƠI BÀI (Danh mục 4: GAME_BAI)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(369, 4, 'V8', 'V8 Poker', 1029, 8.00, 1, 1),
(370, 4, 'KY', 'KaiYuan Gaming', 1030, 9.00, 2, 1),
(371, 4, 'LEG', 'LeGaming', 1031, 9.00, 3, 1);

-- Chèn nền tảng THỂ THAO (Danh mục 5: THE_THAO)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(400, 5, 'SABA', 'SABA Sports', 1032, 11.00, 1, 1),
(401, 5, 'CMD', 'CMD368', 1033, 12.00, 2, 1),
(402, 5, 'SBO', 'SBO Sports', 1034, 11.00, 3, 1),
(403, 5, 'BTI', 'BTI Sports', 1035, 10.00, 4, 1),
(404, 5, 'IM', 'IM Sports', 1036, 12.00, 5, 1);

-- Chèn nền tảng ĐÁ GÀ (Danh mục 6: DA_GA)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(500, 6, 'SV388', 'SV388', 1037, 12.00, 1, 1),
(501, 6, 'WS168', 'WS168', 1038, 12.00, 2, 1);

-- Chèn nền tảng XỔ SỐ (Danh mục 7: XO_SO)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(600, 7, 'VR', 'VR Lottery', 1039, 7.00, 1, 1),
(601, 7, 'GW', 'GW Lottery', 1040, 10.00, 2, 1),
(602, 7, 'TCG', 'TCG Lottery', 1041, 7.00, 3, 1);

-- Chèn nền tảng THỂ THAO ĐIỆN TỬ (Danh mục 8: E_SPORTS)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(700, 8, 'TF', 'TF Gaming', 1042, 10.00, 1, 1);

-- Chèn nền tảng POKER (Danh mục 9: POKER)
INSERT INTO `caipiao_game_platforms` (`id`, `category_id`, `code`, `name`, `product_code`, `pt_percent`, `sort_order`, `status`) VALUES
(800, 9, 'SPRIBE', 'Spribe', 1043, 7.00, 1, 1);

-- Made with Bob
