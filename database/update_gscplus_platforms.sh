#!/bin/bash
# Update GSC+ Product Codes and PT Percentages
# Keep all existing data (IDs, names, images, positions)
# Source: GSC_PLUS_GAME_LAUNCH_API.md

set -e

DB_USER="by"
DB_PASS="by"
DB_NAME="by"

echo "==================================="
echo "GSC+ Platform Update Script"
echo "Source: GSC_PLUS_GAME_LAUNCH_API.md"
echo "==================================="
echo ""

# Backup first
echo "Step 1: Creating backup..."
mkdir -p database/backups
mysqldump -u${DB_USER} -p${DB_PASS} ${DB_NAME} caipiao_game_category caipiao_game_platform > database/backups/gscplus_update_backup_$(date +%Y%m%d_%H%M%S).sql

echo "Step 2: Adding gsc_type column to categories..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
-- Add gsc_type column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'caipiao_game_category'
    AND COLUMN_NAME = 'gsc_type');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE caipiao_game_category ADD COLUMN gsc_type VARCHAR(50) DEFAULT NULL COMMENT "GSC+ game type" AFTER code',
    'SELECT "Column gsc_type already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
EOF

echo "Step 3: Updating game categories with GSC+ types..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
-- Update categories with GSC+ types
UPDATE caipiao_game_category SET gsc_type = 'SLOT' WHERE code = 'slot';
UPDATE caipiao_game_category SET gsc_type = 'LIVE_CASINO' WHERE code = 'live';
UPDATE caipiao_game_category SET gsc_type = 'FISHING' WHERE code = 'fish';
UPDATE caipiao_game_category SET gsc_type = 'TABLE_GAME' WHERE code = 'chess';
UPDATE caipiao_game_category SET gsc_type = 'SPORTSBOOK' WHERE code = 'sport';
UPDATE caipiao_game_category SET gsc_type = 'LOTTERY' WHERE code = 'lottery';
UPDATE caipiao_game_category SET gsc_type = 'E_SPORTS' WHERE code = 'esport';
EOF

echo "Step 4: Adding product_code and pt_percent columns if not exist..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
-- Add product_code column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'caipiao_game_platform'
    AND COLUMN_NAME = 'product_code');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE caipiao_game_platform ADD COLUMN product_code INT(11) DEFAULT NULL COMMENT "GSC+ product code" AFTER name',
    'SELECT "Column product_code already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add pt_percent column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'caipiao_game_platform'
    AND COLUMN_NAME = 'pt_percent');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE caipiao_game_platform ADD COLUMN pt_percent DECIMAL(5,2) DEFAULT 0.00 COMMENT "PT percentage" AFTER product_code',
    'SELECT "Column pt_percent already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
EOF

echo "Step 5: Updating existing platforms with GSC+ codes..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
-- SLOT platforms
UPDATE caipiao_game_platform SET product_code = 1002, pt_percent = 6.00, api_provider = 'GSC' WHERE code = 'PG';
UPDATE caipiao_game_platform SET product_code = 1011, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'JILI';
UPDATE caipiao_game_platform SET product_code = 1013, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'JDB' AND type = 'slot';
UPDATE caipiao_game_platform SET product_code = 1001, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'PP';
UPDATE caipiao_game_platform SET product_code = 1014, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'FC' AND type = 'slot';
UPDATE caipiao_game_platform SET product_code = 1015, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'CQ9' AND type = 'slot';
UPDATE caipiao_game_platform SET product_code = 1017, pt_percent = 7.00, api_provider = 'GSC' WHERE code = 'JOKER';
UPDATE caipiao_game_platform SET product_code = 1019, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'KA';

-- LIVE CASINO platforms
UPDATE caipiao_game_platform SET product_code = 1021, pt_percent = 7.00, api_provider = 'GSC' WHERE code = 'DG';
UPDATE caipiao_game_platform SET product_code = 1023, pt_percent = 7.00, api_provider = 'GSC' WHERE code = 'AG';
UPDATE caipiao_game_platform SET product_code = 1026, pt_percent = 7.00, api_provider = 'GSC' WHERE code = 'WM';
UPDATE caipiao_game_platform SET product_code = 1027, pt_percent = 10.00, api_provider = 'GSC' WHERE code = 'EVO';

-- TABLE GAMES platforms
UPDATE caipiao_game_platform SET product_code = 1029, pt_percent = 8.00, api_provider = 'GSC' WHERE code = 'V8';
UPDATE caipiao_game_platform SET product_code = 1030, pt_percent = 9.00, api_provider = 'GSC' WHERE code = 'KY';
UPDATE caipiao_game_platform SET product_code = 1031, pt_percent = 9.00, api_provider = 'GSC' WHERE code = 'LEG';

-- SPORTSBOOK platforms
UPDATE caipiao_game_platform SET product_code = 1032, pt_percent = 11.00, api_provider = 'GSC' WHERE code = 'SABA';
UPDATE caipiao_game_platform SET product_code = 1033, pt_percent = 12.00, api_provider = 'GSC' WHERE code = 'CMD';
UPDATE caipiao_game_platform SET product_code = 1034, pt_percent = 11.00, api_provider = 'GSC' WHERE code = 'SBO';
UPDATE caipiao_game_platform SET product_code = 1035, pt_percent = 10.00, api_provider = 'GSC' WHERE code = 'BTI';
UPDATE caipiao_game_platform SET product_code = 1036, pt_percent = 12.00, api_provider = 'GSC' WHERE code = 'IM';

-- LOTTERY platforms
UPDATE caipiao_game_platform SET product_code = 1039, pt_percent = 7.00, api_provider = 'GSC' WHERE code = 'VR';

-- E-SPORTS platforms
UPDATE caipiao_game_platform SET product_code = 1042, pt_percent = 10.00, api_provider = 'GSC' WHERE code = 'TF';
EOF

echo "Step 6: Inserting new GSC+ platforms..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
-- Insert new SLOT platforms (if not exist)
INSERT IGNORE INTO caipiao_game_platform (id, code, name, product_code, pt_percent, type, kind, status, sort, created_at, updated_at, api_provider)
VALUES 
(206, 'SPADE', 'Spade Gaming', 1016, 9.00, 'slot', 'third', 'online', 206, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(208, 'HABANERO', 'Habanero', 1018, 8.00, 'slot', 'third', 'online', 208, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(210, 'NAGA', 'Naga Games', 1020, 7.00, 'slot', 'third', 'online', 210, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC');

-- Insert new LIVE CASINO platforms (if not exist)
INSERT IGNORE INTO caipiao_game_platform (id, code, name, product_code, pt_percent, type, kind, status, sort, created_at, updated_at, api_provider)
VALUES 
(301, 'SEXY', 'Sexy Baccarat', 1022, 8.00, 'live', 'third', 'online', 301, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(303, 'BB', 'Big Gaming', 1024, 7.00, 'live', 'third', 'online', 303, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(304, 'SA', 'SA Gaming', 1025, 7.00, 'live', 'third', 'online', 304, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(307, 'PRETTY', 'Pretty Gaming', 1028, 9.00, 'live', 'third', 'online', 307, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC');

-- Insert FISHING platforms (if not exist)
INSERT IGNORE INTO caipiao_game_platform (id, code, name, product_code, pt_percent, type, kind, status, sort, created_at, updated_at, api_provider)
VALUES 
(315, 'JILI_FISH', 'JILI', 1011, 8.00, 'fish', 'third', 'online', 315, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(316, 'JDB_FISH_GSC', 'JDB', 1013, 8.00, 'fish', 'third', 'online', 316, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(317, 'CQ9_FISH_GSC', 'CQ9', 1015, 8.00, 'fish', 'third', 'online', 317, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(318, 'SPADE_FISH', 'Spade Gaming', 1016, 9.00, 'fish', 'third', 'online', 318, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC');

-- Insert COCKFIGHTING platforms (if not exist)
INSERT IGNORE INTO caipiao_game_platform (id, code, name, product_code, pt_percent, type, kind, status, sort, created_at, updated_at, api_provider)
VALUES 
(500, 'SV388', 'SV388', 1037, 12.00, 'cockfight', 'third', 'online', 500, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(501, 'WS168', 'WS168', 1038, 12.00, 'cockfight', 'third', 'online', 501, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC');

-- Insert new LOTTERY platforms (if not exist)
INSERT IGNORE INTO caipiao_game_platform (id, code, name, product_code, pt_percent, type, kind, status, sort, created_at, updated_at, api_provider)
VALUES 
(601, 'GW', 'GW Lottery', 1040, 10.00, 'lottery', 'third', 'online', 601, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC'),
(602, 'TCG', 'TCG Lottery', 1041, 7.00, 'lottery', 'third', 'online', 602, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC');

-- Insert POKER platform (if not exist)
INSERT IGNORE INTO caipiao_game_platform (id, code, name, product_code, pt_percent, type, kind, status, sort, created_at, updated_at, api_provider)
VALUES 
(800, 'SPRIBE', 'Spribe', 1043, 7.00, 'poker', 'third', 'online', 800, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'GSC');
EOF

echo ""
echo "Step 7: Verifying updates..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
SELECT 
    'Categories with GSC+ types' as 'Check',
    COUNT(*) as 'Count'
FROM caipiao_game_category 
WHERE gsc_type IS NOT NULL

UNION ALL

SELECT 
    'Platforms with GSC+ codes',
    COUNT(*)
FROM caipiao_game_platform 
WHERE product_code IS NOT NULL

UNION ALL

SELECT 
    'GSC+ API Provider',
    COUNT(*)
FROM caipiao_game_platform 
WHERE api_provider = 'GSC';
EOF

echo ""
echo "Step 8: Platform breakdown by type..."
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} <<'EOF'
SELECT 
    type as 'Type',
    COUNT(*) as 'Total',
    SUM(CASE WHEN api_provider='GSC' THEN 1 ELSE 0 END) as 'GSC+',
    SUM(CASE WHEN api_provider='NG' THEN 1 ELSE 0 END) as 'NG',
    SUM(CASE WHEN product_code IS NOT NULL THEN 1 ELSE 0 END) as 'With Code'
FROM caipiao_game_platform 
GROUP BY type 
ORDER BY type;
EOF

echo ""
echo "==================================="
echo "✓ Update completed successfully!"
echo "==================================="
echo ""
echo "Summary:"
echo "  ✓ All existing data preserved (IDs, names, images, positions)"
echo "  ✓ GSC+ product codes added to existing platforms"
echo "  ✓ PT percentages updated"
echo "  ✓ New GSC+ platforms inserted"
echo "  ✓ api_provider set to 'GSC' for GSC+ platforms"
echo ""
echo "Next steps:"
echo "  1. Verify data: mysql -uby -pby by -e 'SELECT * FROM caipiao_game_platform WHERE api_provider=\"GSC\" LIMIT 10'"
echo "  2. Test game launch: ./test_game_launch.sh"
echo "  3. Check GSC+ config: curl http://0.0.0.0:8788/api/v1/gscplus/config"