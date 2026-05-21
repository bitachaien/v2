# GSC+ Database Migration Guide

## Tổng quan
Tài liệu này hướng dẫn triển khai database schema cho tích hợp GSC+ Seamless Wallet API v2.0.6.

## Các file migration đã tạo

### 1. caipiao_gscplus_transactions.sql
**Mục đích:** Lưu trữ tất cả giao dịch ví liền mạch từ GSC+

**Cấu trúc chính:**
- `transaction_id` - ID giao dịch duy nhất từ GSC+
- `uid` - ID người dùng trong hệ thống
- `action` - Loại giao dịch (withdraw/deposit)
- `amount` - Số tiền giao dịch
- `before_balance` / `after_balance` - Số dư trước/sau giao dịch
- `payload` - Dữ liệu JSON gốc từ GSC+

**Indexes tối ưu:**
- `idx_transaction_id` (UNIQUE) - Đảm bảo không trùng lặp
- `idx_uid` - Truy vấn theo người dùng
- `idx_wager_code` - Liên kết với cược
- `idx_uid_created` - Composite index cho báo cáo

### 2. caipiao_gscplus_bet_data.sql
**Mục đích:** Lưu dữ liệu cược chi tiết từ pushbetdata callback

**Cấu trúc chính:**
- `wager_code` - Mã cược duy nhất (UNIQUE)
- `uid` - ID người dùng
- `product_code` / `game_code` - Mã sản phẩm và game
- `bet_amount` / `valid_bet_amount` - Số tiền cược
- `prize_amount` - Số tiền thắng
- `wager_status` - Trạng thái cược

**Indexes tối ưu:**
- `idx_wager_code` (UNIQUE) - Đảm bảo không trùng lặp
- `idx_uid_product` - Composite index cho thống kê
- `idx_uid_settled` - Composite index cho báo cáo thanh toán

### 3. caipiao_gscplus_config.sql
**Mục đích:** Cấu hình API GSC+

**Cấu trúc chính:**
- `operator_code` - Mã operator từ GSC+
- `secret_key` - Secret key (cần mã hóa)
- `api_url` - URL API GSC+
- `callback_url` - URL callback của hệ thống
- `currency` - Loại tiền tệ (CNY, VND, etc.)
- `status` - Trạng thái active/inactive

### 4. caipiao_game_platform_alter_gscplus.sql
**Mục đích:** Cập nhật bảng game platform để hỗ trợ seamless wallet

**Thay đổi:**
- Thêm cột `wallet_type` - Phân biệt transfer/seamless wallet
- Thêm cột `provider_type` - Loại nhà cung cấp (bbin, gscplus, etc.)
- Thêm indexes cho hiệu suất truy vấn

## Thứ tự thực thi Migration

```bash
# 1. Tạo bảng cấu hình GSC+
mysql -u username -p database_name < caipiao_gscplus_config.sql

# 2. Tạo bảng giao dịch
mysql -u username -p database_name < caipiao_gscplus_transactions.sql

# 3. Tạo bảng dữ liệu cược
mysql -u username -p database_name < caipiao_gscplus_bet_data.sql

# 4. Cập nhật bảng game platform
mysql -u username -p database_name < caipiao_game_platform_alter_gscplus.sql
```

## Cấu hình sau khi Migration

### 1. Cập nhật thông tin GSC+ Config
```sql
UPDATE caipiao_gscplus_config 
SET 
    operator_code = 'YOUR_ACTUAL_OPERATOR_CODE',
    secret_key = 'YOUR_ENCRYPTED_SECRET_KEY',
    api_url = 'https://actual-gsc-api-url.com',
    callback_url = 'https://0.0.0.0:8788/api/gscplus/callback'
WHERE id = 1;
```

### 2. Cập nhật Game Platform (nếu thay thế BBIN)
```sql
-- Chuyển BBIN sang seamless wallet
UPDATE caipiao_game_platform 
SET 
    wallet_type = 'seamless',
    provider_type = 'gscplus'
WHERE code = 'BBIN';
```

### 3. Hoặc thêm GSC+ mới
```sql
INSERT INTO caipiao_game_platform 
(code, name, type, kind, status, wallet_type, provider_type, 
 api_url, api_key, api_secret, created_at, updated_at) 
VALUES 
('GSCPLUS', 'GSC+', 'slot', 'third', 'online', 'seamless', 'gscplus',
 'https://api.gscplus.com', 'operator_code', 'secret_key', 
 UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

## Kiểm tra sau Migration

### 1. Kiểm tra bảng đã tạo
```sql
SHOW TABLES LIKE 'caipiao_gscplus%';
```

### 2. Kiểm tra indexes
```sql
SHOW INDEX FROM caipiao_gscplus_transactions;
SHOW INDEX FROM caipiao_gscplus_bet_data;
SHOW INDEX FROM caipiao_game_platform;
```

### 3. Kiểm tra cấu hình
```sql
SELECT * FROM caipiao_gscplus_config;
SELECT code, name, wallet_type, provider_type 
FROM caipiao_game_platform 
WHERE wallet_type = 'seamless';
```

## Tối ưu hiệu suất

### Indexes đã được tối ưu cho:
1. **Truy vấn theo người dùng** - `idx_uid`
2. **Truy vấn theo thời gian** - `idx_created_at`, `idx_settled_at`
3. **Truy vấn kết hợp** - `idx_uid_created`, `idx_uid_product`, `idx_uid_settled`
4. **Đảm bảo tính duy nhất** - `idx_transaction_id`, `idx_wager_code`

### Monitoring queries
```sql
-- Kiểm tra số lượng giao dịch theo người dùng
SELECT uid, COUNT(*) as total_transactions 
FROM caipiao_gscplus_transactions 
GROUP BY uid 
ORDER BY total_transactions DESC 
LIMIT 10;

-- Kiểm tra tổng cược theo sản phẩm
SELECT product_code, 
       COUNT(*) as total_bets,
       SUM(bet_amount) as total_bet_amount,
       SUM(prize_amount) as total_prize_amount
FROM caipiao_gscplus_bet_data 
GROUP BY product_code;
```

## Rollback (nếu cần)

```sql
-- Xóa các bảng GSC+ (cẩn thận!)
DROP TABLE IF EXISTS caipiao_gscplus_bet_data;
DROP TABLE IF EXISTS caipiao_gscplus_transactions;
DROP TABLE IF EXISTS caipiao_gscplus_config;

-- Xóa các cột đã thêm vào game_platform
ALTER TABLE caipiao_game_platform 
DROP COLUMN provider_type,
DROP COLUMN wallet_type,
DROP INDEX idx_wallet_type,
DROP INDEX idx_provider_type,
DROP INDEX idx_type_wallet;
```

## Ghi chú quan trọng

1. **Backup trước khi migration:** Luôn backup database trước khi chạy migration
2. **Secret key:** Cần mã hóa secret_key trước khi lưu vào database
3. **Callback URL:** Đảm bảo callback URL có thể truy cập từ GSC+ servers
4. **Timezone:** Timestamps từ GSC+ là milliseconds, cần convert khi hiển thị
5. **JSON payload:** Lưu trữ dữ liệu gốc để debug và audit

## Bước tiếp theo

Sau khi hoàn thành database migration, tiếp tục với:
- Giai đoạn 2: Triển khai callback endpoints (balance, withdraw, deposit, pushbetdata)
- Giai đoạn 3: Triển khai API client để gọi GSC+ services
- Giai đoạn 4: Testing và monitoring

## Liên hệ hỗ trợ

Nếu gặp vấn đề trong quá trình migration, vui lòng tham khảo:
- GSC+ API Documentation: `GSC+Seamless_Wallet_API_v2.0.6EN.md`
- Webman Framework Documentation