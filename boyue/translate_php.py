#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script tự động dịch các message/comment tiếng Trung trong PHP sang tiếng Việt
Dành cho hệ thống cá cược trực tuyến tại Việt Nam
"""

import os
import re
import sys

# Từ điển dịch thuật ngữ cờ bạc/cá cược
GAMBLING_TERMS = {
    # Giao dịch tài chính
    '充值': 'Nạp tiền',
    '提现': 'Rút tiền',
    '提款': 'Rút tiền',
    '取款': 'Rút tiền',
    '存款': 'Nạp tiền',
    '转账': 'Chuyển khoản',
    '余额': 'Số dư',
    '金额': 'Số tiền',
    '手续费': 'Phí giao dịch',
    '返水': 'Hoàn trả',
    '佣金': 'Hoa hồng',
    '红利': 'Tiền thưởng',
    '奖金': 'Tiền thưởng',
    '优惠': 'Khuyến mãi',
    '活动': 'Hoạt động',
    '流水': 'Vòng cược',
    '打码': 'Vòng cược',
    
    # Cược và game
    '投注': 'Đặt cược',
    '下注': 'Đặt cược',
    '有效投注': 'Cược hợp lệ',
    '中奖': 'Trúng thưởng',
    '派奖': 'Trả thưởng',
    '开奖': 'Mở thưởng',
    '彩票': 'Xổ số',
    '游戏': 'Trò chơi',
    '电子': 'Điện tử',
    '真人': 'Live Casino',
    '体育': 'Thể thao',
    '棋牌': 'Bài',
    '捕鱼': 'Bắn cá',
    '老虎机': 'Slot',
    '百家乐': 'Baccarat',
    '轮盘': 'Roulette',
    
    # Tài khoản
    '用户': 'Người dùng',
    '会员': 'Thành viên',
    '账号': 'Tài khoản',
    '密码': 'Mật khẩu',
    '登录': 'Đăng nhập',
    '注册': 'Đăng ký',
    '退出': 'Đăng xuất',
    '实名': 'Thực danh',
    '认证': 'Xác thực',
    '绑定': 'Liên kết',
    
    # VIP và cấp độ
    '等级': 'Cấp độ',
    '升级': 'Thăng cấp',
    '晋级': 'Thăng cấp',
    '会员等级': 'Cấp VIP',
    '周俸禄': 'Lương tuần',
    '月俸禄': 'Lương tháng',
    
    # Đại lý
    '代理': 'Đại lý',
    '推广': 'Giới thiệu',
    '邀请': 'Mời',
    '下级': 'Cấp dưới',
    '团队': 'Đội nhóm',
    
    # Trạng thái
    '成功': 'Thành công',
    '失败': 'Thất bại',
    '待审核': 'Chờ duyệt',
    '审核中': 'Đang duyệt',
    '已通过': 'Đã duyệt',
    '已拒绝': 'Đã từ chối',
    '处理中': 'Đang xử lý',
    '已完成': 'Đã hoàn thành',
    '已取消': 'Đã hủy',
    '进行中': 'Đang diễn ra',
    
    # Thông báo
    '通知': 'Thông báo',
    '消息': 'Tin nhắn',
    '公告': 'Công bố',
    '提示': 'Lưu ý',
    '警告': 'Cảnh báo',
    '错误': 'Lỗi',
    
    # Thời gian
    '今日': 'Hôm nay',
    '昨日': 'Hôm qua',
    '本周': 'Tuần này',
    '本月': 'Tháng này',
    '时间': 'Thời gian',
    '日期': 'Ngày',
    
    # Hành động
    '提交': 'Gửi',
    '确认': 'Xác nhận',
    '取消': 'Hủy',
    '删除': 'Xóa',
    '修改': 'Sửa',
    '查询': 'Tra cứu',
    '搜索': 'Tìm kiếm',
    '刷新': 'Làm mới',
    '保存': 'Lưu',
    '添加': 'Thêm',
    
    # Khác
    '客服': 'CSKH',
    '银行卡': 'Thẻ ngân hàng',
    '支付宝': 'Alipay',
    '微信': 'WeChat',
    '记录': 'Lịch sử',
    '详情': 'Chi tiết',
    '设置': 'Cài đặt',
    '帮助': 'Trợ giúp',
    '规则': 'Quy tắc',
    '条款': 'Điều khoản',
}

# Các cụm từ phổ biến
COMMON_PHRASES = {
    '获取成功': 'Lấy dữ liệu thành công',
    '获取失败': 'Lấy dữ liệu thất bại',
    '操作成功': 'Thao tác thành công',
    '操作失败': 'Thao tác thất bại',
    '请先登录': 'Vui lòng đăng nhập',
    '参数错误': 'Tham số không hợp lệ',
    '数据不存在': 'Dữ liệu không tồn tại',
    '权限不足': 'Không đủ quyền',
    '请求失败': 'Yêu cầu thất bại',
    '系统错误': 'Lỗi hệ thống',
    '余额不足': 'Số dư không đủ',
    '金额不能为空': 'Số tiền không được để trống',
    '密码错误': 'Mật khẩu sai',
    '用户不存在': 'Người dùng không tồn tại',
    '账号已存在': 'Tài khoản đã tồn tại',
    '验证码错误': 'Mã xác thực sai',
    '验证码已过期': 'Mã xác thực đã hết hạn',
}

def translate_text(text):
    """Dịch text từ tiếng Trung sang tiếng Việt"""
    if not text or not any('\u4e00' <= char <= '\u9fff' for char in text):
        return text
    
    result = text
    
    # Dịch cụm từ trước (ưu tiên cao hơn)
    for cn, vi in COMMON_PHRASES.items():
        result = result.replace(cn, vi)
    
    # Dịch từng từ
    for cn, vi in GAMBLING_TERMS.items():
        result = result.replace(cn, vi)
    
    # Dịch các từ còn sót
    additional_terms = {
        '获取': 'Lấy',
        '方式': 'phương thức',
        '数据': 'dữ liệu',
        '支付': 'Thanh toán',
        '不存在': 'không tồn tại',
        '已关闭': 'đã đóng',
        '或': 'hoặc',
    }
    
    for cn, vi in additional_terms.items():
        result = result.replace(cn, vi)
    
    return result

def process_php_file(filepath):
    """Xử lý một file PHP"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        changes = 0
        
        # Pattern cho string trong PHP
        patterns = [
            # Single quoted strings: 'text'
            (r"'([^']*[\u4e00-\u9fff][^']*)'", lambda m: f"'{translate_text(m.group(1))}'"),
            # Double quoted strings: "text"
            (r'"([^"]*[\u4e00-\u9fff][^"]*)"', lambda m: f'"{translate_text(m.group(1))}"'),
            # Array keys: ['key' => 'value']
            (r"'([^']*[\u4e00-\u9fff][^']*)'\s*=>", lambda m: f"'{translate_text(m.group(1))}' =>"),
            # Comments: // text
            (r'//\s*([^\n]*[\u4e00-\u9fff][^\n]*)', lambda m: f'// {translate_text(m.group(1))}'),
            # Multi-line comments: /* text */
            (r'/\*([^*]*[\u4e00-\u9fff][^*]*)\*/', lambda m: f'/*{translate_text(m.group(1))}*/'),
        ]
        
        for pattern, replacement in patterns:
            new_content = re.sub(pattern, replacement, content)
            if new_content != content:
                changes += 1
                content = new_content
        
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True, changes
        
        return False, 0
        
    except Exception as e:
        print(f"❌ Lỗi xử lý {filepath}: {str(e)}")
        return False, 0

def process_directory(directory):
    """Xử lý tất cả file PHP trong thư mục"""
    total_files = 0
    translated_files = 0
    total_changes = 0
    
    print(f"🔍 Đang quét thư mục: {directory}")
    
    for root, dirs, files in os.walk(directory):
        # Bỏ qua các thư mục không cần thiết
        dirs[:] = [d for d in dirs if d not in ['vendor', 'node_modules', '.git', 'runtime']]
        
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                total_files += 1
                
                success, changes = process_php_file(filepath)
                if success:
                    translated_files += 1
                    total_changes += changes
                    print(f"✅ Đã dịch: {filepath} ({changes} thay đổi)")
    
    print(f"\n📊 Tổng kết:")
    print(f"   - Tổng số file PHP: {total_files}")
    print(f"   - Đã dịch: {translated_files}")
    print(f"   - Tổng thay đổi: {total_changes}")
    
    return translated_files > 0

if __name__ == '__main__':
    if len(sys.argv) > 1:
        directory = sys.argv[1]
    else:
        directory = './app'
    
    if not os.path.exists(directory):
        print(f"❌ Thư mục không tồn tại: {directory}")
        sys.exit(1)
    
    print("=" * 60)
    print("🚀 BẮT ĐẦU DỊCH BACKEND PHP")
    print("=" * 60)
    
    success = process_directory(directory)
    
    print("\n" + "=" * 60)
    if success:
        print("✅ HOÀN THÀNH!")
    else:
        print("ℹ️  Không có file nào cần dịch")
    print("=" * 60)

# Made with Bob
