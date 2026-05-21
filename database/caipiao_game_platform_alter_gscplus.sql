--
-- Alter table `caipiao_game_platform` to support GSC+ Seamless Wallet
-- Add new columns for wallet type and provider type
--

-- Add wallet_type column to distinguish between transfer and seamless wallet
ALTER TABLE `caipiao_game_platform` 
ADD COLUMN `wallet_type` ENUM('transfer', 'seamless') NOT NULL DEFAULT 'transfer' 
COMMENT 'Loại ví: transfer=chuyển khoản, seamless=ví liền mạch' 
AFTER `api_provider`;

-- Add provider_type column to identify the game provider
ALTER TABLE `caipiao_game_platform` 
ADD COLUMN `provider_type` VARCHAR(50) DEFAULT NULL 
COMMENT 'Loại nhà cung cấp: bbin, gscplus, ng, wg, etc.' 
AFTER `wallet_type`;

-- Add index for wallet_type for better query performance
ALTER TABLE `caipiao_game_platform` 
ADD KEY `idx_wallet_type` (`wallet_type`);

-- Add index for provider_type
ALTER TABLE `caipiao_game_platform` 
ADD KEY `idx_provider_type` (`provider_type`);

-- Add composite index for common queries
ALTER TABLE `caipiao_game_platform` 
ADD KEY `idx_type_wallet` (`type`, `wallet_type`);

--
-- Example: Update existing BBIN platform to use seamless wallet
-- Uncomment and modify as needed
--

-- UPDATE `caipiao_game_platform` 
-- SET `wallet_type` = 'seamless', `provider_type` = 'bbin' 
-- WHERE `code` = 'BBIN';

--
-- Example: Insert GSC+ platform configuration
-- Uncomment and modify with actual values
--

-- INSERT INTO `caipiao_game_platform` 
-- (`code`, `name`, `type`, `kind`, `status`, `hot`, `recommend`, `sort`, 
--  `api_url`, `api_key`, `api_secret`, `created_at`, `updated_at`, 
--  `api_provider`, `wallet_type`, `provider_type`) 
-- VALUES 
-- ('GSCPLUS', 'GSC+', 'slot', 'third', 'online', 1, 1, 100, 
--  'https://api.gscplus.com', 'YOUR_OPERATOR_CODE', 'YOUR_SECRET_KEY', 
--  UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSCPLUS', 'seamless', 'gscplus');