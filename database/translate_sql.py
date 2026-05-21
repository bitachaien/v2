#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SQL Database Translation Script
Translates Chinese text to Vietnamese in SQL files while preserving:
- HTML code
- Logs
- INSERT INTO data structure
- CREATE TABLE structure
- Image paths
- Data formats
"""

import re
import os
import sys

# Chinese to Vietnamese translation dictionary
TRANSLATIONS = {
    # Common database terms
    '表结构': 'Cấu trúc bảng',
    '表': 'bảng',
    '数据': 'dữ liệu',
    '导出': 'Xuất',
    '插入': 'Chèn',
    '主键': 'Khóa chính',
    '外键': 'Khóa ngoại',
    '索引': 'Chỉ mục',
    '自动增长': 'Tự động tăng',
    '默认': 'Mặc định',
    '非空': 'Không null',
    '唯一': 'Duy nhất',
    
    # Status and common fields
    '状态': 'Trạng thái',
    '类型': 'Loại',
    '名称': 'Tên',
    '标题': 'Tiêu đề',
    '内容': 'Nội dung',
    '描述': 'Mô tả',
    '备注': 'Ghi chú',
    '说明': 'Giải thích',
    '时间': 'Thời gian',
    '日期': 'Ngày',
    '创建时间': 'Thời gian tạo',
    '更新时间': 'Thời gian cập nhật',
    '修改时间': 'Thời gian sửa đổi',
    '删除时间': 'Thời gian xóa',
    '开始时间': 'Thời gian bắt đầu',
    '结束时间': 'Thời gian kết thúc',
    '登录时间': 'Thời gian đăng nhập',
    '注册时间': 'Thời gian đăng ký',
    
    # User related
    '用户': 'Người dùng',
    '会员': 'Thành viên',
    '管理员': 'Quản trị viên',
    '用户名': 'Tên người dùng',
    '密码': 'Mật khẩu',
    '昵称': 'Biệt danh',
    '真实姓名': 'Họ tên thật',
    '姓名': 'Họ tên',
    '手机': 'Điện thoại',
    '手机号': 'Số điện thoại',
    '电话': 'Điện thoại',
    '邮箱': 'Email',
    '地址': 'Địa chỉ',
    '性别': 'Giới tính',
    '年龄': 'Tuổi',
    '生日': 'Sinh nhật',
    '头像': 'Ảnh đại diện',
    
    # Financial terms
    '金额': 'Số tiền',
    '余额': 'Số dư',
    '充值': 'Nạp tiền',
    '提现': 'Rút tiền',
    '提款': 'Rút tiền',
    '转账': 'Chuyển khoản',
    '交易': 'Giao dịch',
    '订单': 'Đơn hàng',
    '支付': 'Thanh toán',
    '收入': 'Thu nhập',
    '支出': 'Chi tiêu',
    '佣金': 'Hoa hồng',
    '返水': 'Hoàn trả',
    '返点': 'Hoàn cược',
    '红利': 'Tiền thưởng',
    '奖金': 'Tiền thưởng',
    '中奖': 'Trúng thưởng',
    '投注': 'Cược',
    '下注': 'Đặt cược',
    '流水': 'Doanh thu',
    '盈利': 'Lợi nhuận',
    '亏损': 'Thua lỗ',
    
    # Lottery related
    '彩票': 'Xổ số',
    '彩种': 'Loại xổ số',
    '期号': 'Số kỳ',
    '开奖': 'Mở thưởng',
    '开奖号码': 'Số trúng thưởng',
    '开奖时间': 'Thời gian mở thưởng',
    '开奖结果': 'Kết quả mở thưởng',
    '玩法': 'Cách chơi',
    '赔率': 'Tỷ lệ',
    '奖金': 'Tiền thưởng',
    '中奖金额': 'Số tiền trúng thưởng',
    '投注金额': 'Số tiền cược',
    '投注内容': 'Nội dung cược',
    '投注号码': 'Số cược',
    
    # Agent related
    '代理': 'Đại lý',
    '上级': 'Cấp trên',
    '下级': 'Cấp dưới',
    '推广': 'Quảng bá',
    '推广链接': 'Link quảng bá',
    '推广码': 'Mã quảng bá',
    '团队': 'Đội nhóm',
    '层级': 'Cấp độ',
    
    # System related
    '系统': 'Hệ thống',
    '配置': 'Cấu hình',
    '设置': 'Cài đặt',
    '参数': 'Tham số',
    '选项': 'Tùy chọn',
    '功能': 'Chức năng',
    '模块': 'Mô-đun',
    '权限': 'Quyền',
    '角色': 'Vai trò',
    '分组': 'Nhóm',
    '分类': 'Danh mục',
    '标签': 'Thẻ',
    '排序': 'Sắp xếp',
    '顺序': 'Thứ tự',
    '等级': 'Cấp độ',
    '级别': 'Cấp bậc',
    
    # Actions
    '启用': 'Kích hoạt',
    '禁用': 'Vô hiệu hóa',
    '删除': 'Xóa',
    '编辑': 'Chỉnh sửa',
    '修改': 'Sửa đổi',
    '添加': 'Thêm',
    '新增': 'Thêm mới',
    '查看': 'Xem',
    '审核': 'Kiểm duyệt',
    '通过': 'Đã duyệt',
    '拒绝': 'Từ chối',
    '待审核': 'Chờ duyệt',
    '处理中': 'Đang xử lý',
    '已处理': 'Đã xử lý',
    '已完成': 'Đã hoàn thành',
    '已取消': 'Đã hủy',
    '失败': 'Thất bại',
    '成功': 'Thành công',
    
    # Common words
    '是': 'Có',
    '否': 'Không',
    '开': 'Bật',
    '关': 'Tắt',
    '正常': 'Bình thường',
    '异常': 'Bất thường',
    '有效': 'Có hiệu lực',
    '无效': 'Không hiệu lực',
    '可用': 'Khả dụng',
    '不可用': 'Không khả dụng',
    '显示': 'Hiển thị',
    '隐藏': 'Ẩn',
    '公开': 'Công khai',
    '私密': 'Riêng tư',
    '全部': 'Tất cả',
    '部分': 'Một phần',
    '其他': 'Khác',
    '未知': 'Không rõ',
    '无': 'Không có',
    '空': 'Trống',
    
    # Specific terms
    '内网IP': 'IP nội bộ',
    '版注册': 'đăng ký',
    '代理开户': 'Đại lý mở tài khoản',
    '系统': 'Hệ thống',
    '机器人': 'Robot',
    '内部': 'Nội bộ',
    '普通': 'Thường',
    '锁定': 'Khóa',
    '解锁': 'Mở khóa',
    '在线': 'Trực tuyến',
    '离线': 'Ngoại tuyến',
    '登录': 'Đăng nhập',
    '退出': 'Đăng xuất',
    '注册': 'Đăng ký',
    '绑定': 'Liên kết',
    '解绑': 'Hủy liên kết',
    '验证': 'Xác thực',
    '认证': 'Chứng thực',
    '安全': 'Bảo mật',
    '问题': 'Câu hỏi',
    '答案': 'Câu trả lời',
    '设备': 'Thiết bị',
    '来源': 'Nguồn',
    '记录': 'Bản ghi',
    '日志': 'Nhật ký',
    '错误': 'Lỗi',
    '警告': 'Cảnh báo',
    '信息': 'Thông tin',
    '消息': 'Tin nhắn',
    '通知': 'Thông báo',
    '公告': 'Công bố',
    '新闻': 'Tin tức',
    '活动': 'Hoạt động',
    '任务': 'Nhiệm vụ',
    '奖励': 'Phần thưởng',
    '礼物': 'Quà tặng',
    '优惠': 'Ưu đãi',
    '折扣': 'Giảm giá',
    '积分': 'Điểm',
    '经验': 'Kinh nghiệm',
    '等级': 'Cấp độ',
    '升级': 'Nâng cấp',
    '降级': 'Hạ cấp',
    '冻结': 'Đóng băng',
    '解冻': 'Mở băng',
    '限制': 'Giới hạn',
    '限额': 'Hạn mức',
    '最小': 'Tối thiểu',
    '最大': 'Tối đa',
    '最低': 'Thấp nhất',
    '最高': 'Cao nhất',
    '单笔': 'Đơn lẻ',
    '单日': 'Hàng ngày',
    '每日': 'Mỗi ngày',
    '每月': 'Mỗi tháng',
    '每周': 'Mỗi tuần',
    '每年': 'Mỗi năm',
    '总计': 'Tổng cộng',
    '合计': 'Tổng',
    '小计': 'Tổng phụ',
    '数量': 'Số lượng',
    '次数': 'Số lần',
    '人数': 'Số người',
    '比例': 'Tỷ lệ',
    '百分比': 'Phần trăm',
    '倍数': 'Bội số',
    '倍率': 'Tỷ lệ bội',
}

def translate_comment(text):
    """Translate COMMENT content"""
    if not text or text.strip() == '':
        return text
    
    # Don't translate if it's already in Vietnamese or English
    if any(vn_word in text for vn_word in ['Số', 'Thời', 'Người', 'Tên', 'Mã', 'ID']):
        return text
    
    result = text
    # Sort by length (longest first) to avoid partial replacements
    for cn, vn in sorted(TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True):
        result = result.replace(cn, vn)
    
    return result

def translate_chinese_names(text):
    """Translate Chinese names in INSERT statements"""
    # This is a simple approach - in production, you'd use a proper translation API
    # For now, we'll keep Chinese names as they might be actual user data
    return text

def process_sql_file(filepath):
    """Process a single SQL file"""
    print(f"Processing: {filepath}")
    
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
    except Exception as e:
        print(f"Error reading {filepath}: {e}")
        return False
    
    # Translate COMMENT fields
    def replace_comment(match):
        comment_text = match.group(1)
        translated = translate_comment(comment_text)
        return f"COMMENT '{translated}'"
    
    content = re.sub(r"COMMENT\s+'([^']*)'", replace_comment, content)
    content = re.sub(r'COMMENT\s+"([^"]*)"', lambda m: f'COMMENT \'{translate_comment(m.group(1))}\'', content)
    
    # Translate table comments at the end of CREATE TABLE
    content = re.sub(
        r"COMMENT\s*=\s*'([^']*)'",
        lambda m: f"COMMENT='{translate_comment(m.group(1))}'",
        content
    )
    
    # Write back
    try:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"✓ Completed: {filepath}")
        return True
    except Exception as e:
        print(f"Error writing {filepath}: {e}")
        return False

def main():
    """Main function"""
    # Get the database directory
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Get all SQL files
    sql_files = [f for f in os.listdir(script_dir) if f.endswith('.sql')]
    
    if not sql_files:
        print("No SQL files found in the database directory")
        return
    
    print(f"Found {len(sql_files)} SQL files to process")
    print("=" * 60)
    
    success_count = 0
    fail_count = 0
    
    for sql_file in sorted(sql_files):
        filepath = os.path.join(script_dir, sql_file)
        if process_sql_file(filepath):
            success_count += 1
        else:
            fail_count += 1
    
    print("=" * 60)
    print(f"Translation completed!")
    print(f"Success: {success_count} files")
    print(f"Failed: {fail_count} files")

if __name__ == '__main__':
    main()

# Made with Bob
