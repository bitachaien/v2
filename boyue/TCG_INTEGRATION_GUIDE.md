# Hướng Dẫn Tích Hợp TCG Game Platform

## Tổng Quan

Tài liệu này mô tả chi tiết cách tích hợp TCG Game Platform vào hệ thống. TCG cung cấp các loại game:
- **Slot Games** (Product Type 1)
- **Live Casino** (Product Type 2)
- **Fishing Games** (Product Type 3)

## Cấu Trúc File

```
boyue/
├── app/
│   ├── helpers/
│   │   └── TcgCryptoHelper.php          # Helper mã hóa SHA256
│   ├── service/
│   │   ├── TcgService.php               # Service chính TCG API
│   │   ├── TcgFtpService.php            # Service đồng bộ FTP
│   │   └── GamePlatformService.php      # Đã tích hợp TCG
│   └── controller/
│       └── api/GameController.php       # API endpoints
├── process/
│   ├── TcgTransferRetry.php             # Process retry transfer
│   └── TcgBetCollector.php              # Process thu thập bet records
├── config/
│   └── process.php                      # Đã đăng ký TCG processes
└── .env.example                         # Cấu hình mẫu

database/
└── tcg_integration.sql                  # Database migration
```

## Cài Đặt

### 1. Cấu Hình Database

Chạy migration SQL:

```bash
cd /www/wwwroot/okwink6/database
mysql -u by -p by < tcg_integration.sql
```

Migration này sẽ:
- Thêm TCG platform vào `caipiao_game_platform`
- Tạo bảng `caipiao_tcg_member` (ánh xạ user với TCG)
- Tạo bảng `caipiao_tcg_ftp_sync` (lịch sử FTP sync)
- Tạo bảng `caipiao_tcg_transfer_queue` (queue retry transfer)
- Thêm games TCG vào `caipiao_game`

### 2. Cấu Hình Environment

Thêm vào file `boyue/.env`:

```env
# TCG Game Platform Configuration
TCG_API_URL=http://www.connect4play.com/doBusiness.do
TCG_MERCHANT_CODE=ewiinvndk
TCG_DES_KEY=u2nkrkQV
TCG_SHA256_KEY=ld1AN3saSuowR7wb
TCG_USERNAME_PREFIX=14b_

# TCG FTP Configuration
TCG_FTP_HOST=123.51.167.66
TCG_FTP_PORT=21
TCG_FTP_USER=ewiinvndk
TCG_FTP_PASS=a123456
TCG_FTP_SYNC_INTERVAL=300
```

### 3. Khởi Động Lại Server

```bash
cd /www/wwwroot/okwink6/boyue
php start.php restart
```

## Kiến Trúc Hệ Thống

### Flow Chơi Game

```
User Request → GameController
    ↓
GamePlatformService.getGameUrl()
    ↓
TcgService.getOrCreateMember()
    ↓
TcgService.processTransferWithRetry() (chuyển tiền vào game)
    ↓
TcgService.launchGame()
    ↓
Return Game URL → User
```

### Flow Transfer với Retry

```
Transfer Request
    ↓
TcgService.processTransferWithRetry()
    ↓
Lưu vào caipiao_tcg_transfer_queue
    ↓
Thực hiện transfer
    ↓
Success? → Lưu vào caipiao_game_transfer
    ↓
Failed? → TcgTransferRetry Process sẽ retry
```

### Flow Đồng Bộ Bet Records

```
TcgBetCollector Process (chạy mỗi 5 phút)
    ↓
TcgFtpService.syncBetRecords()
    ↓
Kết nối FTP → Download CSV
    ↓
Parse CSV → Lưu vào caipiao_game_bet
    ↓
Cập nhật caipiao_tcg_ftp_sync
```

## API Endpoints

### 1. Launch Game

**Endpoint:** `GET /app/api/game/launch`

**Parameters:**
- `platform`: TCG
- `game_id`: tcg_lobby_slot | tcg_lobby_live | tcg_lobby_fishing
- `device`: mobile | desktop (optional)

**Response:**
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "url": "https://tcg-game-url.com/..."
  }
}
```

### 2. Get Balance

**Endpoint:** `GET /app/api/game/balance`

**Parameters:**
- `platform`: TCG

**Response:**
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "balance": 1000.00
  }
}
```

### 3. Transfer In/Out

**Endpoint:** `POST /app/api/game/transfer`

**Parameters:**
- `platform`: TCG
- `type`: in | out
- `amount`: 100.00

**Response:**
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "order_no": "IN_1234567890_123456",
    "status": "success"
  }
}
```

## TCG API Methods

### TcgService Methods

```php
// Tạo hoặc lấy TCG member
$tcgService->getOrCreateMember($user);

// Lấy số dư
$tcgService->getBalance($tcgUsername);

// Chuyển tiền vào game
$tcgService->transferIn($tcgUsername, $amount, $txid);

// Rút tiền từ game
$tcgService->transferOut($tcgUsername, $amount, $txid);

// Khởi chạy game
$tcgService->launchGame($tcgUsername, $productType, $platform, $gameId);

// Kiểm tra trạng thái transfer
$tcgService->checkTransfer($txid);

// Xử lý transfer với retry
$tcgService->processTransferWithRetry($uid, $type, $amount);

// Lấy game URL
$tcgService->getGameUrl($user, $productType, $gameId);
```

### TcgFtpService Methods

```php
// Liệt kê files trên FTP
$ftpService->listFiles('/');

// Download file từ FTP
$ftpService->downloadFile($remoteFile, $localFile);

// Đồng bộ bet records
$ftpService->syncBetRecords('2024-01-15');
```

### TcgCryptoHelper Methods

```php
// Tạo SHA256 hash
TcgCryptoHelper::sha256($data);

// Tạo chữ ký
TcgCryptoHelper::generateSign($params, $key);

// Verify chữ ký
TcgCryptoHelper::verifySign($params, $receivedSign, $key);

// Mã hóa DES
TcgCryptoHelper::desEncrypt($data, $key);

// Giải mã DES
TcgCryptoHelper::desDecrypt($data, $key);

// Tạo transaction ID
TcgCryptoHelper::generateTransactionId('IN');

// Kiểm tra response thành công
TcgCryptoHelper::isSuccessResponse($response);
```

## Background Processes

### 1. TcgTransferRetry

**Chức năng:** Retry các transfer thất bại

**Cấu hình:** Chạy mỗi 30 giây

**Logic:**
- Lấy các transfer có status = 'pending' và retry_count < max_retry
- Thực hiện lại transfer
- Nếu thành công: cập nhật status = 'success'
- Nếu thất bại: tăng retry_count, schedule retry tiếp theo (exponential backoff)
- Nếu hết số lần retry: đánh dấu status = 'failed'

### 2. TcgBetCollector

**Chức năng:** Thu thập bet records từ FTP

**Cấu hình:** Chạy mỗi 5 phút (configurable via TCG_FTP_SYNC_INTERVAL)

**Logic:**
- Kết nối FTP server
- Download file CSV của ngày hôm qua và hôm nay
- Parse CSV và lưu vào `caipiao_game_bet`
- Cập nhật `caipiao_tcg_ftp_sync`

## Database Schema

### caipiao_tcg_member

```sql
CREATE TABLE `caipiao_tcg_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `tcg_username` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uid` (`uid`),
  UNIQUE KEY `idx_tcg_username` (`tcg_username`)
);
```

### caipiao_tcg_transfer_queue

```sql
CREATE TABLE `caipiao_tcg_transfer_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL,
  `uid` int(11) NOT NULL,
  `tcg_username` varchar(50) NOT NULL,
  `type` varchar(10) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `retry_count` int(11) DEFAULT '0',
  `max_retry` int(11) DEFAULT '3',
  `status` varchar(20) DEFAULT 'pending',
  `error_message` text,
  `next_retry_at` int(11) DEFAULT '0',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`)
);
```

### caipiao_tcg_ftp_sync

```sql
CREATE TABLE `caipiao_tcg_ftp_sync` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_date` date NOT NULL,
  `total_records` int(11) DEFAULT '0',
  `processed_records` int(11) DEFAULT '0',
  `failed_records` int(11) DEFAULT '0',
  `status` varchar(20) DEFAULT 'pending',
  `error_message` text,
  `started_at` int(11) DEFAULT '0',
  `completed_at` int(11) DEFAULT '0',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filename` (`filename`)
);
```

## Troubleshooting

### 1. Transfer Failed

**Kiểm tra:**
- Log file: `boyue/runtime/logs/webman.log`
- Bảng `caipiao_tcg_transfer_queue` để xem lỗi
- Verify credentials trong `.env`

**Giải pháp:**
- TcgTransferRetry process sẽ tự động retry
- Hoặc gọi lại API transfer

### 2. FTP Sync Failed

**Kiểm tra:**
- FTP credentials trong `.env`
- Kết nối FTP: `telnet 123.51.167.66 21`
- Bảng `caipiao_tcg_ftp_sync` để xem lỗi

**Giải pháp:**
- Kiểm tra firewall
- Verify FTP credentials
- Chạy manual sync: `$ftpService->syncBetRecords('2024-01-15')`

### 3. Game Launch Failed

**Kiểm tra:**
- User có TCG member chưa?
- User có đủ số dư không?
- TCG API có hoạt động không?

**Giải pháp:**
- Tạo TCG member: `$tcgService->getOrCreateMember($user)`
- Nạp tiền vào game: `$tcgService->transferIn(...)`
- Kiểm tra log TCG API response

## Testing

### Test Transfer

```php
$tcgService = new \app\service\TcgService();
$user = Db::table('caipiao_user')->where('id', 1)->first();

// Test transfer in
$result = $tcgService->processTransferWithRetry($user->id, 'IN', 100);
var_dump($result);

// Test transfer out
$result = $tcgService->processTransferWithRetry($user->id, 'OUT', 50);
var_dump($result);
```

### Test Game Launch

```php
$tcgService = new \app\service\TcgService();
$user = Db::table('caipiao_user')->where('id', 1)->first();

// Launch slot lobby
$result = $tcgService->getGameUrl($user, 1, '');
var_dump($result);

// Launch live casino
$result = $tcgService->getGameUrl($user, 2, '');
var_dump($result);
```

### Test FTP Sync

```php
$ftpService = new \app\service\TcgFtpService();

// List files
$files = $ftpService->listFiles('/');
var_dump($files);

// Sync yesterday's bets
$result = $ftpService->syncBetRecords(date('Y-m-d', strtotime('-1 day')));
var_dump($result);
```

## Monitoring

### Kiểm Tra Processes

```bash
# Xem tất cả processes
ps aux | grep php

# Kiểm tra TCG processes
ps aux | grep tcg
```

### Kiểm Tra Logs

```bash
# Xem log real-time
tail -f boyue/runtime/logs/webman.log

# Tìm TCG logs
grep "TCG" boyue/runtime/logs/webman.log
```

### Kiểm Tra Database

```sql
-- Kiểm tra TCG members
SELECT * FROM caipiao_tcg_member ORDER BY created_at DESC LIMIT 10;

-- Kiểm tra transfer queue
SELECT * FROM caipiao_tcg_transfer_queue WHERE status = 'pending';

-- Kiểm tra FTP sync
SELECT * FROM caipiao_tcg_ftp_sync ORDER BY created_at DESC LIMIT 10;

-- Kiểm tra bet records
SELECT * FROM caipiao_game_bet WHERE platform = 'TCG' ORDER BY created_at DESC LIMIT 10;
```

## Production Checklist

- [ ] Cập nhật credentials thật trong `.env`
- [ ] Chạy database migration
- [ ] Test transfer IN/OUT
- [ ] Test game launch
- [ ] Test FTP sync
- [ ] Kiểm tra processes đang chạy
- [ ] Setup monitoring alerts
- [ ] Backup database trước khi deploy
- [ ] Test trên staging environment trước
- [ ] Document API cho frontend team

## Support

Nếu gặp vấn đề, kiểm tra:
1. Log files trong `boyue/runtime/logs/`
2. Database tables: `caipiao_tcg_*`
3. Process status: `ps aux | grep tcg`
4. TCG API documentation

## Changelog

### Version 1.0.0 (2024-01-15)
- Initial TCG integration
- Support Slot, Live Casino, Fishing games
- Transfer with retry logic
- FTP bet record sync
- Background processes for automation