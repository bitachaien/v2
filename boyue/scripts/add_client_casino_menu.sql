SET @db_name = DATABASE();
SET @has_yzz_menu_link = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'yzz_menus' AND COLUMN_NAME = 'link'
);
SET @sql_yzz_menu_link = IF(
  @has_yzz_menu_link = 0,
  "ALTER TABLE `yzz_menus` ADD COLUMN `link` varchar(255) DEFAULT NULL COMMENT '外部链接' AFTER `component`",
  'SELECT 1'
);
PREPARE stmt_yzz_menu_link FROM @sql_yzz_menu_link;
EXECUTE stmt_yzz_menu_link;
DEALLOCATE PREPARE stmt_yzz_menu_link;

SET @has_yzz_menu_iframe = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'yzz_menus' AND COLUMN_NAME = 'isIframe'
);
SET @sql_yzz_menu_iframe = IF(
  @has_yzz_menu_iframe = 0,
  "ALTER TABLE `yzz_menus` ADD COLUMN `isIframe` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否内嵌' AFTER `link`",
  'SELECT 1'
);
PREPARE stmt_yzz_menu_iframe FROM @sql_yzz_menu_iframe;
EXECUTE stmt_yzz_menu_iframe;
DEALLOCATE PREPARE stmt_yzz_menu_iframe;

INSERT INTO `yzz_menus` (`id`,`pid`,`name`,`title`,`icon`,`path`,`component`,`link`,`isIframe`,`type`,`sort`,`status`,`created_at`,`updated_at`)
SELECT 1026,0,'FrontendCasinoXoso','Giao diện casino xoso','ri:computer-line','/frontend-casino-xoso','Layout',NULL,0,0,96,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `yzz_menus` WHERE `id` = 1026 OR `name` = 'FrontendCasinoXoso');

INSERT INTO `yzz_menus` (`id`,`pid`,`name`,`title`,`icon`,`path`,`component`,`link`,`isIframe`,`type`,`sort`,`status`,`created_at`,`updated_at`)
SELECT 1027,1026,'ClientCasinoControl','Client Casino','ri:apps-2-line','/outside/iframe/client-casino','','http://localhost:3001/',1,1,1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `yzz_menus` WHERE `id` = 1027 OR `name` = 'ClientCasinoControl');
