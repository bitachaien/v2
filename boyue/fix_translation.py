#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script to fix remaining translation issues in PHP files
"""

import os
import re
import sys

# Dictionary of incorrect translations to correct translations
FIXES = {
    # Mixed Chinese-Vietnamese
    'Người dùng名': 'Tên người dùng',
    'Mật khẩu不能为空': 'Mật khẩu không được để trống',
    '已被禁用': 'đã bị khóa',
    '平台列表': 'danh sách nền tảng',
    '服务器Lỗi': 'Lỗi máy chủ',
    'LấyNạp tiền': 'Lấy nạp tiền',
    'LấyRút tiền': 'Lấy rút tiền',
    'LấySố dư': 'Lấy số dư',
    'Thanh toánphương thức': 'Phương thức thanh toán',
    '配置Thất bại': 'cấu hình thất bại',
    'Lịch sửThất bại': 'lịch sử thất bại',
    'Trò chơi平台': 'nền tảng trò chơi',
    
    # Spacing issues
    'LấyThanh toán': 'Lấy thanh toán',
    'LấyTrò chơi': 'Lấy trò chơi',
    'LấyNạp': 'Lấy nạp',
    'LấyRút': 'Lấy rút',
    'LấySố': 'Lấy số',
    'Lấydữ liệu': 'Lấy dữ liệu',
    'Đăng nhậpThành công': 'Đăng nhập thành công',
    'phương thứcThất bại': 'phương thức thất bại',
    'phương thứckhông tồn tại': 'phương thức không tồn tại',
    'phương thứchoặc': 'phương thức hoặc',
    'Tài khoản已被': 'Tài khoản đã bị',
    
    # Chinese characters remaining
    '需4-16位': 'cần từ 4-16 ký tự',
    '只能包含字母和数字': 'chỉ được chứa chữ cái và số',
    '已存在': 'đã tồn tại',
    '不可用': 'không khả dụng',
    '账户': 'tài khoản',
    '不能为空': 'không được để trống',
    '为2-12字母与数字hoặc中文的字符': 'từ 2-12 ký tự chữ cái, số hoặc tiếng Việt',
    '已被Đăng ký': 'đã được đăng ký',
    '波色配置': 'cấu hình màu sắc',
    '请选择': 'Vui lòng chọn',
    
    # More spacing fixes
    'Lấy波色': 'Lấy màu sắc',
    'LấyLịch sử': 'Lấy lịch sử',
    'Lịch sử': 'lịch sử',
}

def fix_file(filepath):
    """Fix translation issues in a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        changes_made = False
        
        # Apply all fixes
        for wrong, correct in FIXES.items():
            if wrong in content:
                content = content.replace(wrong, correct)
                changes_made = True
        
        # Write back if changes were made
        if changes_made:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            return True
        
        return False
        
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        return False

def process_directory(directory):
    """Process all PHP files in directory recursively"""
    fixed_count = 0
    total_count = 0
    
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                total_count += 1
                
                if fix_file(filepath):
                    fixed_count += 1
                    print(f"✓ Fixed: {filepath}")
    
    return fixed_count, total_count

if __name__ == '__main__':
    # Process boyue/app directory
    print("Processing boyue/app directory...")
    app_fixed, app_total = process_directory('app')
    
    # Process boyue/plugin directory
    print("\nProcessing boyue/plugin directory...")
    plugin_fixed, plugin_total = process_directory('plugin')
    
    # Process boyue/process directory
    print("\nProcessing boyue/process directory...")
    process_fixed, process_total = process_directory('process')
    
    # Summary
    total_fixed = app_fixed + plugin_fixed + process_fixed
    total_files = app_total + plugin_total + process_total
    
    print("\n" + "="*60)
    print(f"SUMMARY:")
    print(f"  app/     : {app_fixed}/{app_total} files fixed")
    print(f"  plugin/  : {plugin_fixed}/{plugin_total} files fixed")
    print(f"  process/ : {process_fixed}/{process_total} files fixed")
    print(f"  TOTAL    : {total_fixed}/{total_files} files fixed")
    print("="*60)

# Made with Bob
