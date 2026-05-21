--
-- Script tạo 5 người dùng thử nghiệm cho Kiểm Thử Đa Người Dùng GSC+
-- Ngân sách: 5,000 VND2 (5 người dùng × 1,000 VND2)
--

-- Tạo 5 người dùng thử nghiệm trong bảng caipiao_member
INSERT INTO `caipiao_member` 
(`parentid`, `fanshuifee`, `groupid`, `jinjijilu`, `username`, `nickname`, `proxy`, `isnb`, 
 `email`, `phone`, `userbankname`, `password`, `tradepassword`, `sex`, `balance`, `xima`, 
 `fandian`, `tel`, `face`, `loginip`, `device_id`, `last_ip`, `iparea`, `regtime`, `regip`, 
 `source`, `logintime`, `loginsource`, `onlinetime`, `islock`, `is_robot`, `birthday`, 
 `record`, `is_rebet`, `yebmoney`, `money`, `yebtime`, `yeblixi`, `dyebmoney`, `ngbalance`, 
 `google_secret`, `google_bind`, `security_question`, `security_answer`, `settings`) 
VALUES 
-- Người dùng 1: test_user_001 - Kiểm thử Số dư & Giao dịch (1,000 VND2)
(0, 0.00, 1, 1, 'test_user_001', 'Người Dùng Thử 001', 0, 1, 
 'test001@gscplus.test', '0900000001', 'Người Dùng Thử 001', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- mật khẩu: password
 'e10adc3949ba59abbe56e057f20f883e', -- mật khẩu giao dịch: 123456
 1, 1000.000, 0.000, '', '', NULL, '127.0.0.1', NULL, NULL, 'Môi Trường Thử Nghiệm', 
 UNIX_TIMESTAMP(), '127.0.0.1', 'Thiết Lập Thử Nghiệm GSC+', UNIX_TIMESTAMP(), 'api', 0, 0, 1, 
 '1990-01-01', 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0.00, '', 0, '', '', 
 JSON_OBJECT('test_purpose', 'balance_transaction', 'budget_vnd2', 1000)),

-- Người dùng 2: test_user_002 - Kiểm thử Khởi chạy Game & Đặt cược (1,000 VND2)
(0, 0.00, 1, 1, 'test_user_002', 'Người Dùng Thử 002', 0, 1, 
 'test002@gscplus.test', '0900000002', 'Người Dùng Thử 002', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'e10adc3949ba59abbe56e057f20f883e', 
 1, 1000.000, 0.000, '', '', NULL, '127.0.0.1', NULL, NULL, 'Môi Trường Thử Nghiệm', 
 UNIX_TIMESTAMP(), '127.0.0.1', 'Thiết Lập Thử Nghiệm GSC+', UNIX_TIMESTAMP(), 'api', 0, 0, 1, 
 '1990-01-01', 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0.00, '', 0, '', '', 
 JSON_OBJECT('test_purpose', 'game_launch_betting', 'budget_vnd2', 1000)),

-- Người dùng 3: test_user_003 - Kiểm thử Xử lý Lỗi (1,000 VND2)
(0, 0.00, 1, 1, 'test_user_003', 'Người Dùng Thử 003', 0, 1, 
 'test003@gscplus.test', '0900000003', 'Người Dùng Thử 003', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'e10adc3949ba59abbe56e057f20f883e', 
 1, 1000.000, 0.000, '', '', NULL, '127.0.0.1', NULL, NULL, 'Môi Trường Thử Nghiệm', 
 UNIX_TIMESTAMP(), '127.0.0.1', 'Thiết Lập Thử Nghiệm GSC+', UNIX_TIMESTAMP(), 'api', 0, 0, 1, 
 '1990-01-01', 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0.00, '', 0, '', '', 
 JSON_OBJECT('test_purpose', 'error_handling', 'budget_vnd2', 1000)),

-- Người dùng 4: test_user_004 - Kiểm thử Tính Idempotency & Điều kiện Race (1,000 VND2)
(0, 0.00, 1, 1, 'test_user_004', 'Người Dùng Thử 004', 0, 1, 
 'test004@gscplus.test', '0900000004', 'Người Dùng Thử 004', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'e10adc3949ba59abbe56e057f20f883e', 
 1, 1000.000, 0.000, '', '', NULL, '127.0.0.1', NULL, NULL, 'Môi Trường Thử Nghiệm', 
 UNIX_TIMESTAMP(), '127.0.0.1', 'Thiết Lập Thử Nghiệm GSC+', UNIX_TIMESTAMP(), 'api', 0, 0, 1, 
 '1990-01-01', 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0.00, '', 0, '', '', 
 JSON_OBJECT('test_purpose', 'idempotency_race_condition', 'budget_vnd2', 1000)),

-- Người dùng 5: test_user_005 - Kiểm thử Mô phỏng Sản xuất (1,000 VND2)
(0, 0.00, 1, 1, 'test_user_005', 'Người Dùng Thử 005', 0, 1, 
 'test005@gscplus.test', '0900000005', 'Người Dùng Thử 005', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'e10adc3949ba59abbe56e057f20f883e', 
 1, 1000.000, 0.000, '', '', NULL, '127.0.0.1', NULL, NULL, 'Môi Trường Thử Nghiệm', 
 UNIX_TIMESTAMP(), '127.0.0.1', 'Thiết Lập Thử Nghiệm GSC+', UNIX_TIMESTAMP(), 'api', 0, 0, 1, 
 '1990-01-01', 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0.00, '', 0, '', '', 
 JSON_OBJECT('test_purpose', 'production_simulation', 'budget_vnd2', 1000));

-- Tạo bảng số dư người chơi cho GSC+ (nếu chưa có)
CREATE TABLE IF NOT EXISTS `caipiao_gscplus_player_balances` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT COMMENT 'ID chính',
  `player_account` VARCHAR(100) NOT NULL COMMENT 'Tên tài khoản người chơi',
  `uid` INT(11) NOT NULL COMMENT 'ID người dùng trong hệ thống',
  `currency` VARCHAR(10) NOT NULL DEFAULT 'VND2' COMMENT 'Loại tiền tệ',
  `balance` DECIMAL(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư hiện tại',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_player_currency` (`player_account`, `currency`),
  KEY `idx_uid` (`uid`),
  KEY `idx_currency` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GSC+ Số dư người chơi';

-- Khởi tạo số dư cho 5 người dùng thử nghiệm
INSERT INTO `caipiao_gscplus_player_balances` 
(`player_account`, `uid`, `currency`, `balance`) 
SELECT 
  m.username,
  m.id,
  'VND2',
  1000.00
FROM caipiao_member m
WHERE m.username IN ('test_user_001', 'test_user_002', 'test_user_003', 'test_user_004', 'test_user_005')
ON DUPLICATE KEY UPDATE balance = 1000.00;

-- Hiển thị kết quả
SELECT 
  m.id,
  m.username,
  m.nickname,
  m.balance as system_balance,
  gpb.balance as gscplus_balance,
  gpb.currency,
  m.settings->>'$.test_purpose' as test_purpose,
  m.settings->>'$.budget_vnd2' as budget_vnd2
FROM caipiao_member m
LEFT JOIN caipiao_gscplus_player_balances gpb ON m.username = gpb.player_account
WHERE m.username LIKE 'test_user_%'
ORDER BY m.username;

-- Được tạo bởi Bob

-- Made with Bob
