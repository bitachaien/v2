#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script dịch SQL từ tiếng Trung (Phồn thể & Giản thể) sang tiếng Việt
Dành cho hệ thống cá cược trực tuyến tại Việt Nam
"""

import re
import os
import sys
from pathlib import Path

# Từ điển dịch chuyên ngành cá cược/casino
GAMBLING_TERMS = {
    # Hoạt động khuyến mãi
    '活动': 'Hoạt động',
    '優惠': 'Khuyến mãi',
    '优惠': 'Khuyến mãi',
    '彩金': 'Tiền thưởng',
    '奖金': 'Tiền thưởng',
    '獎金': 'Tiền thưởng',
    '红包': 'Phong bì đỏ',
    '紅包': 'Phong bì đỏ',
    '礼包': 'Gói quà',
    '禮包': 'Gói quà',
    
    # Giao dịch
    '充值': 'Nạp tiền',
    '存款': 'Nạp tiền',
    '提现': 'Rút tiền',
    '提款': 'Rút tiền',
    '转账': 'Chuyển khoản',
    '轉賬': 'Chuyển khoản',
    '余额': 'Số dư',
    '餘額': 'Số dư',
    '金额': 'Số tiền',
    '金額': 'Số tiền',
    
    # Cược và game
    '投注': 'Đặt cược',
    '下注': 'Đặt cược',
    '有效投注': 'Cược hợp lệ',
    '流水': 'Doanh thu cược',
    '打码': 'Vòng cược',
    '返水': 'Hoàn trả',
    '返點': 'Hoàn điểm',
    '返点': 'Hoàn điểm',
    '洗码': 'Hoàn cược',
    '佣金': 'Hoa hồng',
    '傭金': 'Hoa hồng',
    
    # Thành viên
    '会员': 'Thành viên',
    '會員': 'Thành viên',
    '用户': 'Người dùng',
    '用戶': 'Người dùng',
    '玩家': 'Người chơi',
    '账号': 'Tài khoản',
    '賬號': 'Tài khoản',
    '密码': 'Mật khẩu',
    '密碼': 'Mật khẩu',
    '登录': 'Đăng nhập',
    '登錄': 'Đăng nhập',
    '注册': 'Đăng ký',
    '註冊': 'Đăng ký',
    
    # VIP và cấp bậc
    '等级': 'Cấp độ',
    '等級': 'Cấp độ',
    '级别': 'Cấp bậc',
    '級別': 'Cấp bậc',
    '晋级': 'Thăng cấp',
    '晉級': 'Thăng cấp',
    '升级': 'Nâng cấp',
    '俸禄': 'Lương',
    '俸祿': 'Lương',
    '周俸禄': 'Lương tuần',
    '月俸禄': 'Lương tháng',
    
    # Thời gian
    '时间': 'Thời gian',
    '時間': 'Thời gian',
    '日期': 'Ngày',
    '每日': 'Hàng ngày',
    '每周': 'Hàng tuần',
    '每月': 'Hàng tháng',
    
    # Trạng thái
    '状态': 'Trạng thái',
    '狀態': 'Trạng thái',
    '成功': 'Thành công',
    '失败': 'Thất bại',
    '失敗': 'Thất bại',
    '待审核': 'Chờ duyệt',
    '已审核': 'Đã duyệt',
    '已取消': 'Đã hủy',
    
    # Khác
    '客服': 'CSKH',
    '在线客服': 'CSKH trực tuyến',
    '公告': 'Thông báo',
    '消息': 'Tin nhắn',
    '通知': 'Thông báo',
    '内容': 'Nội dung',
    '內容': 'Nội dung',
    '标题': 'Tiêu đề',
    '標題': 'Tiêu đề',
    '描述': 'Mô tả',
    '详情': 'Chi tiết',
    '詳情': 'Chi tiết',
    '规则': 'Quy tắc',
    '規則': 'Quy tắc',
    '条款': 'Điều khoản',
    '條款': 'Điều khoản',
    '说明': 'Hướng dẫn',
    '說明': 'Hướng dẫn',
}

# Tên riêng cần giữ nguyên hoặc chuyển sang pinyin
PROPER_NAMES = {
    # Tên người (ví dụ)
    '李玉群': 'Li Yuqun',
    '张三': 'Zhang San',
    '李四': 'Li Si',
    
    # Tên địa danh Trung Quốc
    '江苏': 'Giang Tô',
    '江蘇': 'Giang Tô',
    '广西': 'Quảng Tây',
    '廣西': 'Quảng Tây',
    '吉林': 'Cát Lâm',
    '湖北': 'Hồ Bắc',
    '河北': 'Hà Bắc',
    '安徽': 'An Huy',
    '北京': 'Bắc Kinh',
    '上海': 'Thượng Hải',
    
    # Tên ngân hàng
    '工商银行': 'Ngân hàng Công Thương',
    '工商銀行': 'Ngân hàng Công Thương',
    '建设银行': 'Ngân hàng Xây dựng',
    '建設銀行': 'Ngân hàng Xây dựng',
    '农业银行': 'Ngân hàng Nông nghiệp',
    '農業銀行': 'Ngân hàng Nông nghiệp',
    '招商银行': 'Ngân hàng Merchants',
    '招商銀行': 'Ngân hàng Merchants',
    '交通银行': 'Ngân hàng Communications',
    '交通銀行': 'Ngân hàng Communications',
    '民生银行': 'Ngân hàng Minsheng',
    '民生銀行': 'Ngân hàng Minsheng',
    
    # Tên công ty/thương hiệu
    '支付宝': 'Alipay',
    '支付寶': 'Alipay',
    '微信': 'WeChat',
    '微信支付': 'WeChat Pay',
    'QQ': 'QQ',
    'Telegram': 'Telegram',
    
    # Tên game
    'PG': 'PG',
    'CQ9': 'CQ9',
    'JDB': 'JDB',
    'PP': 'PP',
    'FC': 'FC',
}

# Từ điển dịch chung
COMMON_TRANSLATIONS = {
    # Số và đơn vị
    '元': 'đồng',
    '块': 'đồng',
    '分': 'xu',
    '角': 'hào',
    '万': 'vạn',
    '萬': 'vạn',
    '亿': 'tỷ',
    '億': 'tỷ',
    '千': 'nghìn',
    '百': 'trăm',
    '十': 'mươi',
    
    # Thời gian
    '年': 'năm',
    '月': 'tháng',
    '日': 'ngày',
    '号': 'ngày',
    '號': 'ngày',
    '周': 'tuần',
    '天': 'ngày',
    '小时': 'giờ',
    '小時': 'giờ',
    '分钟': 'phút',
    '分鐘': 'phút',
    '秒': 'giây',
    
    # Động từ
    '是': 'là',
    '有': 'có',
    '没有': 'không có',
    '沒有': 'không có',
    '可以': 'có thể',
    '需要': 'cần',
    '必须': 'phải',
    '必須': 'phải',
    '请': 'vui lòng',
    '請': 'vui lòng',
    
    # Tính từ
    '新': 'mới',
    '旧': 'cũ',
    '舊': 'cũ',
    '大': 'lớn',
    '小': 'nhỏ',
    '多': 'nhiều',
    '少': 'ít',
    '高': 'cao',
    '低': 'thấp',
    '好': 'tốt',
    '坏': 'xấu',
    '壞': 'xấu',
    
    # Liên từ
    '和': 'và',
    '或': 'hoặc',
    '但': 'nhưng',
    '因为': 'vì',
    '因為': 'vì',
    '所以': 'nên',
    '如果': 'nếu',
    '那么': 'thì',
    '那麼': 'thì',
}

def translate_text(text):
    """
    Dịch văn bản từ tiếng Trung sang tiếng Việt
    Ưu tiên: Tên riêng > Thuật ngữ chuyên ngành > Từ thông dụng
    """
    if not text or not isinstance(text, str):
        return text
    
    result = text
    
    # Bước 1: Xử lý tên riêng trước
    for chinese, vietnamese in PROPER_NAMES.items():
        result = result.replace(chinese, vietnamese)
    
    # Bước 2: Xử lý thuật ngữ chuyên ngành
    for chinese, vietnamese in GAMBLING_TERMS.items():
        result = result.replace(chinese, vietnamese)
    
    # Bước 3: Xử lý từ thông dụng
    for chinese, vietnamese in COMMON_TRANSLATIONS.items():
        result = result.replace(chinese, vietnamese)
    
    return result

def translate_sql_file(input_file, output_file=None):
    """
    Dịch file SQL từ tiếng Trung sang tiếng Việt
    """
    if output_file is None:
        output_file = input_file.replace('.sql', '_vi.sql')
    
    try:
        with open(input_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Dịch nội dung
        translated_content = translate_text(content)
        
        # Ghi file đầu ra
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(translated_content)
        
        print(f"✅ Đã dịch: {input_file} -> {output_file}")
        return True
        
    except Exception as e:
        print(f"❌ Lỗi khi dịch {input_file}: {str(e)}")
        return False

def main():
    """
    Hàm chính - dịch tất cả file SQL trong thư mục database
    """
    database_dir = Path(__file__).parent
    sql_files = list(database_dir.glob('*.sql'))
    
    if not sql_files:
        print("Không tìm thấy file SQL nào trong thư mục database")
        return
    
    print(f"Tìm thấy {len(sql_files)} file SQL")
    print("Bắt đầu dịch...\n")
    
    success_count = 0
    for sql_file in sql_files:
        # Bỏ qua file đã dịch
        if '_vi.sql' in sql_file.name:
            continue
        
        if translate_sql_file(str(sql_file)):
            success_count += 1
    
    print(f"\n✅ Hoàn thành! Đã dịch {success_count}/{len(sql_files)} file")

if __name__ == '__main__':
    main()

# Made with Bob
