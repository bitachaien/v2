<template>
  <div class="xy28-help-page">
    
    <div class="header">
      <div class="back-btn" @click="goBack">
        <van-icon name="arrow-left" />
      </div>
      <div class="title">桌投说明</div>
    </div>
    
    
    <div class="content">
      
      <div class="help-section">
        <div class="section-num">1.</div>
        <div class="section-text">点击眼睛图标，可开启或关闭咪牌功能。</div>
      </div>
      <div class="demo-image demo-header">
        <div class="mock-header">
          <div class="mock-title-row">
            <span class="mock-lottery-name">加拿大28-2.0(2000)</span>
            <span class="mock-arrow">▲</span>
            <van-icon name="eye-o" class="mock-eye" />
            <van-icon name="bars" class="mock-menu" />
          </div>
          <div class="mock-info-row">
            <div class="mock-left">
              <div class="mock-issue">第<em>324534623</em>期开奖</div>
              <div class="mock-countdown">00:33</div>
            </div>
            <div class="mock-right">
              <div class="mock-balance-label">账户余额</div>
              <div class="mock-balance">515,223.00</div>
            </div>
          </div>
        </div>
      </div>
      
      
      <div class="help-section">
        <div class="section-num">2.</div>
        <div class="section-text">下注时间段，点击筹码可选择相应投注金额，也可以点击设置选择适合自己的筹码。</div>
      </div>
      <div class="demo-chips">
        <div class="chip">¥1</div>
        <div class="chip active">¥5</div>
        <div class="chip">¥10</div>
        <div class="chip">¥100</div>
        <div class="chip">¥500</div>
        <div class="chip">梭哈</div>
        <div class="chip setting"><van-icon name="setting-o" /></div>
      </div>
      
      
      <div class="help-section">
        <div class="section-num">3.</div>
        <div class="section-text">选择筹码后，点击玩法可进行下注，每点击一次增加一个筹码金额。每个玩法下方都会显示此玩法的下注总额。</div>
      </div>
      <div class="demo-bets">
        <div class="bet-row">
          <div class="bet-card">
            <div class="bet-main">
              <span class="label">大</span>
              <span class="odds">1.96</span>
            </div>
            <div class="bet-footer">
              <span>👤 47</span>
              <span>💰 572041</span>
            </div>
          </div>
          <div class="bet-card">
            <div class="bet-main">
              <span class="label">小</span>
              <span class="odds">1.96</span>
            </div>
            <div class="bet-footer">
              <span>👤 96</span>
              <span>💰 624100</span>
            </div>
          </div>
        </div>
        <div class="bet-row">
          <div class="bet-card selected">
            <div class="bet-main">
              <span class="label highlight">单</span>
              <span class="amount-badge">¥ 500</span>
            </div>
            <div class="bet-footer">
              <span>👤 112</span>
              <span>💰 1072041</span>
            </div>
          </div>
          <div class="bet-card">
            <div class="bet-main">
              <span class="label">小</span>
              <span class="odds">1.96</span>
            </div>
            <div class="bet-footer">
              <span>👤 96</span>
              <span>💰 624100</span>
            </div>
          </div>
        </div>
        <div class="bet-row small">
          <div class="bet-card mini"><span class="label">大单</span><span class="odds">1.96</span></div>
          <div class="bet-card mini"><span class="label">大双</span><span class="odds">1.96</span></div>
          <div class="bet-card mini"><span class="label">小单</span><span class="odds">1.96</span></div>
          <div class="bet-card mini"><span class="label">小双</span><span class="odds">1.96</span></div>
        </div>
      </div>
      
      
      <div class="help-section">
        <div class="section-num">4.</div>
        <div class="section-text">点击下注统计，可查看本期的下注详情，或对本期的下注进行撤单/修改。</div>
      </div>
      <div class="demo-footer">
        <div class="summary-bar">
          <span>已投<em>5</em>单，共<em>120.00</em>元</span>
          <van-icon name="arrow" />
        </div>
        <div class="btn-clear">清空</div>
        <div class="btn-confirm">确认投注</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function goBack() {
  router.back()
}
</script>

<style lang="less" scoped>
.xy28-help-page {
  min-height: 100vh;
  background: #fff;
  
  .header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-bottom: 1px solid #eee;
    z-index: 100;
    
    .back-btn {
      position: absolute;
      left: 12px;
      font-size: 20px;
      color: #333;
    }
    
    .title {
      font-size: 17px;
      font-weight: 600;
      color: #333;
    }
  }
  
  .content {
    padding: 60px 16px 24px;
    
    .help-section {
      display: flex;
      gap: 8px;
      margin-bottom: 12px;
      
      .section-num {
        font-size: 14px;
        color: #333;
        font-weight: 500;
      }
      
      .section-text {
        flex: 1;
        font-size: 14px;
        color: #333;
        line-height: 1.5;
      }
    }
    
    .demo-header {
      margin-bottom: 24px;
      
      .mock-header {
        background: linear-gradient(135deg, #1a237e, #303f9f);
        border-radius: 8px;
        padding: 12px;
        color: #fff;
        
        .mock-title-row {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          margin-bottom: 12px;
          
          .mock-lottery-name {
            color: #00e5ff;
            font-size: 16px;
            font-weight: 600;
          }
          
          .mock-arrow {
            color: #00e5ff;
            font-size: 12px;
          }
          
          .mock-eye {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 6px;
            border-radius: 6px;
            font-size: 18px;
          }
          
          .mock-menu {
            font-size: 18px;
          }
        }
        
        .mock-info-row {
          display: flex;
          justify-content: space-between;
          
          .mock-left {
            .mock-issue {
              font-size: 12px;
              em { color: #00e5ff; font-style: normal; }
            }
            .mock-countdown {
              display: inline-block;
              margin-top: 4px;
              padding: 4px 12px;
              background: rgba(0,0,0,0.3);
              border-radius: 4px;
              font-size: 14px;
              font-weight: 600;
            }
          }
          
          .mock-right {
            text-align: right;
            .mock-balance-label { font-size: 12px; opacity: 0.8; }
            .mock-balance { font-size: 18px; font-weight: 600; color: #00e5ff; }
          }
        }
      }
    }
    
    .demo-chips {
      display: flex;
      gap: 8px;
      padding: 12px;
      background: #f5f5f5;
      border-radius: 8px;
      margin-bottom: 24px;
      overflow-x: auto;
      
      .chip {
        flex-shrink: 0;
        padding: 8px 16px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 13px;
        color: #333;
        
        &.active { background: #1e88e5; border-color: #1e88e5; color: #fff; }
        &.setting { padding: 8px 12px; }
      }
    }
    
    .demo-bets {
      background: #f5f5f5;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 24px;
      
      .bet-row {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        
        &.small .bet-card { flex: 1; }
        
        .bet-card {
          flex: 1;
          background: #fff;
          border: 1px solid #e0e0e0;
          border-radius: 6px;
          padding: 12px;
          text-align: center;
          
          &.selected { border-color: #1e88e5; }
          &.mini { padding: 8px; }
          
          .bet-main {
            margin-bottom: 8px;
            .label { display: block; font-size: 16px; font-weight: 600; color: #333; }
            .label.highlight { color: #1e88e5; }
            .odds { display: block; font-size: 12px; color: #999; }
            .amount-badge {
              display: inline-block;
              padding: 2px 8px;
              background: #1e88e5;
              border-radius: 4px;
              color: #fff;
              font-size: 12px;
              margin-top: 4px;
            }
          }
          
          .bet-footer {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #999;
          }
        }
      }
    }
    
    .demo-footer {
      display: flex;
      gap: 10px;
      align-items: center;
      padding: 12px;
      background: #f5f5f5;
      border-radius: 8px;
      
      .summary-bar {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        em { color: #1e88e5; font-style: normal; font-weight: 600; }
      }
      
      .btn-clear {
        padding: 10px 16px;
        background: #e0e0e0;
        border-radius: 6px;
        font-size: 13px;
        color: #666;
      }
      
      .btn-confirm {
        padding: 10px 20px;
        background: linear-gradient(90deg, #01d1ff, #0aabff);
        border-radius: 6px;
        font-size: 13px;
        color: #fff;
        font-weight: 500;
      }
    }
  }
}
</style>
