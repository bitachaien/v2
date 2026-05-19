-- Script cập nhật COMMENT cho các bảng hiện có
-- Tạo bởi: Translation Script
-- Ngày: 2026-05-16

-- Cập nhật bảng wa_admin_roles
ALTER TABLE `wa_admin_roles` COMMENT='Bảng vai trò quản trị viên';
ALTER TABLE `wa_admin_roles` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính';
ALTER TABLE `wa_admin_roles` MODIFY COLUMN `role_id` int(11) NOT NULL COMMENT 'ID vai trò';
ALTER TABLE `wa_admin_roles` MODIFY COLUMN `admin_id` int(11) NOT NULL COMMENT 'ID quản trị viên';

-- Cập nhật bảng wa_admins
ALTER TABLE `wa_admins` COMMENT='Bảng quản trị viên';
ALTER TABLE `wa_admins` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID';
ALTER TABLE `wa_admins` MODIFY COLUMN `username` varchar(32) NOT NULL COMMENT 'Tên đăng nhập';
ALTER TABLE `wa_admins` MODIFY COLUMN `nickname` varchar(40) NOT NULL COMMENT 'Biệt danh';
ALTER TABLE `wa_admins` MODIFY COLUMN `password` varchar(255) NOT NULL COMMENT 'Mật khẩu';
ALTER TABLE `wa_admins` MODIFY COLUMN `avatar` varchar(255) DEFAULT '/app/admin/avatar.png' COMMENT 'Ảnh đại diện';
ALTER TABLE `wa_admins` MODIFY COLUMN `email` varchar(100) DEFAULT NULL COMMENT 'Email';
ALTER TABLE `wa_admins` MODIFY COLUMN `mobile` varchar(16) DEFAULT NULL COMMENT 'Số điện thoại';
ALTER TABLE `wa_admins` MODIFY COLUMN `created_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo';
ALTER TABLE `wa_admins` MODIFY COLUMN `updated_at` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật';
ALTER TABLE `wa_admins` MODIFY COLUMN `login_at` datetime DEFAULT NULL COMMENT 'Thời gian đăng nhập';
ALTER TABLE `wa_admins` MODIFY COLUMN `status` tinyint(4) DEFAULT NULL COMMENT 'Trạng thái vô hiệu hóa';

-- Cập nhật bảng wa_options
ALTER TABLE `wa_options` COMMENT='Bảng tùy chọn';
ALTER TABLE `wa_options` MODIFY COLUMN `name` varchar(128) NOT NULL COMMENT 'Khóa';
ALTER TABLE `wa_options` MODIFY COLUMN `value` longtext NOT NULL COMMENT 'Giá trị';
ALTER TABLE `wa_options` MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT '2022-08-15 00:00:00' COMMENT 'Thời gian tạo';
ALTER TABLE `wa_options` MODIFY COLUMN `updated_at` datetime NOT NULL DEFAULT '2022-08-15 00:00:00' COMMENT 'Thời gian cập nhật';

-- Cập nhật bảng wa_roles
ALTER TABLE `wa_roles` COMMENT='Vai trò quản trị viên';
ALTER TABLE `wa_roles` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính';
ALTER TABLE `wa_roles` MODIFY COLUMN `name` varchar(80) NOT NULL COMMENT 'Nhóm vai trò';
ALTER TABLE `wa_roles` MODIFY COLUMN `rules` text COMMENT 'Quyền hạn';
ALTER TABLE `wa_roles` MODIFY COLUMN `created_at` datetime NOT NULL COMMENT 'Thời gian tạo';
ALTER TABLE `wa_roles` MODIFY COLUMN `updated_at` datetime NOT NULL COMMENT 'Thời gian cập nhật';
ALTER TABLE `wa_roles` MODIFY COLUMN `pid` int(10) unsigned DEFAULT NULL COMMENT 'Cấp cha';

-- Cập nhật bảng wa_rules
ALTER TABLE `wa_rules` COMMENT='Quy tắc quyền hạn';
ALTER TABLE `wa_rules` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính';
ALTER TABLE `wa_rules` MODIFY COLUMN `title` varchar(255) NOT NULL COMMENT 'Tiêu đề';
ALTER TABLE `wa_rules` MODIFY COLUMN `icon` varchar(255) DEFAULT NULL COMMENT 'Biểu tượng';
ALTER TABLE `wa_rules` MODIFY COLUMN `key` varchar(255) NOT NULL COMMENT 'Định danh';
ALTER TABLE `wa_rules` MODIFY COLUMN `pid` int(10) unsigned DEFAULT '0' COMMENT 'Menu cấp trên';
ALTER TABLE `wa_rules` MODIFY COLUMN `created_at` datetime NOT NULL COMMENT 'Thời gian tạo';
ALTER TABLE `wa_rules` MODIFY COLUMN `updated_at` datetime NOT NULL COMMENT 'Thời gian cập nhật';
ALTER TABLE `wa_rules` MODIFY COLUMN `href` varchar(255) DEFAULT NULL COMMENT 'url';
ALTER TABLE `wa_rules` MODIFY COLUMN `type` int(11) NOT NULL DEFAULT '1' COMMENT 'Loại';
ALTER TABLE `wa_rules` MODIFY COLUMN `weight` int(11) DEFAULT '0' COMMENT 'Thứ tự sắp xếp';

-- Cập nhật bảng wa_uploads
ALTER TABLE `wa_uploads` COMMENT='Tệp đính kèm';
ALTER TABLE `wa_uploads` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính';
ALTER TABLE `wa_uploads` MODIFY COLUMN `name` varchar(128) NOT NULL COMMENT 'Tên';
ALTER TABLE `wa_uploads` MODIFY COLUMN `url` varchar(255) NOT NULL COMMENT 'Tệp tin';
ALTER TABLE `wa_uploads` MODIFY COLUMN `admin_id` int(11) DEFAULT NULL COMMENT 'Quản trị viên';
ALTER TABLE `wa_uploads` MODIFY COLUMN `file_size` int(11) NOT NULL COMMENT 'Kích thước tệp';
ALTER TABLE `wa_uploads` MODIFY COLUMN `mime_type` varchar(255) NOT NULL COMMENT 'Loại mime';
ALTER TABLE `wa_uploads` MODIFY COLUMN `image_width` int(11) DEFAULT NULL COMMENT 'Chiều rộng ảnh';
ALTER TABLE `wa_uploads` MODIFY COLUMN `image_height` int(11) DEFAULT NULL COMMENT 'Chiều cao ảnh';
ALTER TABLE `wa_uploads` MODIFY COLUMN `ext` varchar(128) NOT NULL COMMENT 'Phần mở rộng';
ALTER TABLE `wa_uploads` MODIFY COLUMN `storage` varchar(255) NOT NULL DEFAULT 'local' COMMENT 'Vị trí lưu trữ';
ALTER TABLE `wa_uploads` MODIFY COLUMN `created_at` date DEFAULT NULL COMMENT 'Thời gian tải lên';
ALTER TABLE `wa_uploads` MODIFY COLUMN `category` varchar(128) DEFAULT NULL COMMENT 'Danh mục';
ALTER TABLE `wa_uploads` MODIFY COLUMN `updated_at` date DEFAULT NULL COMMENT 'Thời gian cập nhật';

-- Cập nhật bảng wa_users
ALTER TABLE `wa_users` COMMENT='Bảng người dùng';
ALTER TABLE `wa_users` MODIFY COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính';
ALTER TABLE `wa_users` MODIFY COLUMN `username` varchar(32) NOT NULL COMMENT 'Tên đăng nhập';
ALTER TABLE `wa_users` MODIFY COLUMN `nickname` varchar(40) NOT NULL COMMENT 'Biệt danh';
ALTER TABLE `wa_users` MODIFY COLUMN `password` varchar(255) NOT NULL COMMENT 'Mật khẩu';
ALTER TABLE `wa_users` MODIFY COLUMN `sex` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'Giới tính';
ALTER TABLE `wa_users` MODIFY COLUMN `avatar` varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện';
ALTER TABLE `wa_users` MODIFY COLUMN `email` varchar(128) DEFAULT NULL COMMENT 'Email';
ALTER TABLE `wa_users` MODIFY COLUMN `mobile` varchar(16) DEFAULT NULL COMMENT 'Số điện thoại';
ALTER TABLE `wa_users` MODIFY COLUMN `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Cấp độ';
ALTER TABLE `wa_users` MODIFY COLUMN `birthday` date DEFAULT NULL COMMENT 'Ngày sinh';
ALTER TABLE `wa_users` MODIFY COLUMN `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư (VNĐ)';
ALTER TABLE `wa_users` MODIFY COLUMN `score` int(11) NOT NULL DEFAULT '0' COMMENT 'Điểm tích lũy';
ALTER TABLE `wa_users` MODIFY COLUMN `last_time` datetime DEFAULT NULL COMMENT 'Thời gian đăng nhập';
ALTER TABLE `wa_users` MODIFY COLUMN `last_ip` varchar(50) DEFAULT NULL COMMENT 'IP đăng nhập';
ALTER TABLE `wa_users` MODIFY COLUMN `join_time` datetime DEFAULT NULL COMMENT 'Thời gian đăng ký';
ALTER TABLE `wa_users` MODIFY COLUMN `join_ip` varchar(50) DEFAULT NULL COMMENT 'IP đăng ký';
ALTER TABLE `wa_users` MODIFY COLUMN `token` varchar(50) DEFAULT NULL COMMENT 'token';
ALTER TABLE `wa_users` MODIFY COLUMN `created_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo';
ALTER TABLE `wa_users` MODIFY COLUMN `updated_at` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật';
ALTER TABLE `wa_users` MODIFY COLUMN `role` int(11) NOT NULL DEFAULT '1' COMMENT 'Vai trò';
ALTER TABLE `wa_users` MODIFY COLUMN `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Trạng thái vô hiệu hóa';
