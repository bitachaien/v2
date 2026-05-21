#!/bin/bash

echo "=========================================="
echo "DATABASE IMPORT SCRIPT"
echo "Import translated SQL files to database"
echo "=========================================="

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Database configuration - Read from environment or use defaults
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-df}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD:-}"

# Directories
SQL_DIR="/www/wwwroot/okwink6/database"
BACKUP_DIR="/www/wwwroot/okwink6/database/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

echo -e "${BLUE}Database Configuration:${NC}"
echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo ""

# Function to execute MySQL command
mysql_exec() {
    if [ -z "$DB_PASS" ]; then
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$@"
    else
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$@"
    fi
}

# Test database connection
echo -e "${BLUE}Step 1: Testing database connection...${NC}"
if mysql_exec -e "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    echo "Please check your database credentials in boyue/.env or config/database.php"
    exit 1
fi

# Backup current database
echo -e "\n${BLUE}Step 2: Backing up current database...${NC}"
echo "Backup file: $BACKUP_FILE"

if [ -z "$DB_PASS" ]; then
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null
else
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null
fi

if [ $? -eq 0 ] && [ -s "$BACKUP_FILE" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo -e "${GREEN}✓ Backup completed successfully (Size: $BACKUP_SIZE)${NC}"
else
    echo -e "${RED}✗ Backup failed${NC}"
    exit 1
fi

# Count SQL files
SQL_FILES=$(find "$SQL_DIR" -maxdepth 1 -name "*.sql" -type f | wc -l)
echo -e "\n${BLUE}Step 3: Found $SQL_FILES SQL files to import${NC}"

# Ask for confirmation
echo -e "\n${YELLOW}WARNING: This will replace all tables in database '$DB_NAME'${NC}"
echo -e "${YELLOW}Backup saved to: $BACKUP_FILE${NC}"
read -p "Do you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${RED}Import cancelled by user${NC}"
    exit 0
fi

# Import SQL files
echo -e "\n${BLUE}Step 4: Importing SQL files...${NC}"
SUCCESS_COUNT=0
FAIL_COUNT=0
FAILED_FILES=()

# Disable foreign key checks during import
mysql_exec "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=0;" 2>/dev/null

for sql_file in "$SQL_DIR"/*.sql; do
    # Skip backup files and this script
    if [[ "$sql_file" == *"/backups/"* ]] || [[ "$sql_file" == *"import_database"* ]]; then
        continue
    fi
    
    filename=$(basename "$sql_file")
    echo -n "  Importing $filename... "
    
    if mysql_exec "$DB_NAME" < "$sql_file" 2>/dev/null; then
        echo -e "${GREEN}✓${NC}"
        ((SUCCESS_COUNT++))
    else
        echo -e "${RED}✗${NC}"
        ((FAIL_COUNT++))
        FAILED_FILES+=("$filename")
    fi
done

# Re-enable foreign key checks
mysql_exec "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=1;" 2>/dev/null

# Summary
echo -e "\n=========================================="
echo -e "${BLUE}IMPORT SUMMARY${NC}"
echo -e "=========================================="
echo -e "Total files: $SQL_FILES"
echo -e "${GREEN}Successful: $SUCCESS_COUNT${NC}"
echo -e "${RED}Failed: $FAIL_COUNT${NC}"

if [ $FAIL_COUNT -gt 0 ]; then
    echo -e "\n${RED}Failed files:${NC}"
    for file in "${FAILED_FILES[@]}"; do
        echo "  - $file"
    done
    echo -e "\n${YELLOW}Some files failed to import. Check the errors above.${NC}"
    echo -e "${YELLOW}You can restore from backup: $BACKUP_FILE${NC}"
else
    echo -e "\n${GREEN}All files imported successfully!${NC}"
fi

# Verify tables
echo -e "\n${BLUE}Step 5: Verifying database tables...${NC}"
TABLE_COUNT=$(mysql_exec "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
TABLE_COUNT=$((TABLE_COUNT - 1)) # Subtract header line
echo -e "Total tables in database: ${GREEN}$TABLE_COUNT${NC}"

echo -e "\n=========================================="
echo -e "${GREEN}DATABASE IMPORT COMPLETED${NC}"
echo -e "=========================================="
echo -e "Backup location: ${BLUE}$BACKUP_FILE${NC}"
echo -e "\nTo restore from backup if needed:"
echo -e "  mysql -u$DB_USER -p $DB_NAME < $BACKUP_FILE"
echo -e "=========================================="

# Made with Bob
