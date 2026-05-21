--
-- Thay đổi bảng `caipiao_game_platform` để hỗ trợ Ví Liền Mạch GSC+
-- Thêm các cột mới cho loại ví và loại nhà cung cấp
--

-- Thêm cột wallet_type để phân biệt giữa ví chuyển khoản và ví liền mạch
ALTER TABLE `caipiao_game_platform` 
ADD COLUMN `wallet_type` ENUM('transfer', 'seamless') NOT NULL DEFAULT 'transfer' 
COMMENT 'Loại ví: transfer=chuyển khoản, seamless=ví liền mạch' 
AFTER `api_provider`;

-- Thêm cột provider_type để xác định nhà cung cấp game
ALTER TABLE `caipiao_game_platform` 
ADD COLUMN `provider_type` VARCHAR(50) DEFAULT NULL 
COMMENT 'Loại nhà cung cấp: bbin, gscplus, ng, wg, v.v.' 
AFTER `wallet_type`;

-- Thêm chỉ mục cho wallet_type để cải thiện hiệu suất truy vấn
ALTER TABLE `caipiao_game_platform` 
ADD KEY `idx_wallet_type` (`wallet_type`);

-- Thêm chỉ mục cho provider_type
ALTER TABLE `caipiao_game_platform` 
ADD KEY `idx_provider_type` (`provider_type`);

-- Thêm chỉ mục kết hợp cho các truy vấn phổ biến
ALTER TABLE `caipiao_game_platform` 
ADD KEY `idx_type_wallet` (`type`, `wallet_type`);

--
-- Ví dụ: Cập nhật nền tảng BBIN hiện có để sử dụng ví liền mạch
-- Bỏ comment và chỉnh sửa theo nhu cầu
--

-- UPDATE `caipiao_game_platform` 
-- SET `wallet_type` = 'seamless', `provider_type` = 'bbin' 
-- WHERE `code` = 'BBIN';

--
-- Ví dụ: Chèn cấu hình nền tảng GSC+
-- Bỏ comment và chỉnh sửa với giá trị thực tế
--

-- INSERT INTO `caipiao_game_platform` 
-- (`code`, `name`, `type`, `kind`, `status`, `hot`, `recommend`, `sort`, 
--  `api_url`, `api_key`, `api_secret`, `created_at`, `updated_at`, 
--  `api_provider`, `wallet_type`, `provider_type`) 
-- VALUES 
-- ('GSCPLUS', 'GSC+', 'slot', 'third', 'online', 1, 1, 100, 
--  'https://api.gscplus.com', 'YOUR_OPERATOR_CODE', 'YOUR_SECRET_KEY', 
--  UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSCPLUS', 'seamless', 'gscplus');

-- Made with Bob
