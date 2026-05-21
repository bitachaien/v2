<template>
  <van-popup
    :show="show"
    @update:show="$emit('update:show', $event)"
    position="left"
    :style="{ width: '75%', height: '100%', background: 'transparent' }"
    class="sidebar-popup"
  >
    <div class="sidebar-container">
      
      <div class="user-card glass-panel">
        <div class="user-info-row">
          <div class="avatar-box">
            <img :src="userInfo.avatar || defaultAvatar" alt="Avatar" class="avatar-img" />
            <div class="avatar-glow"></div>
          </div>
          <div class="info-text">
            <div class="name-row">
              <span class="username">{{ userInfo.username || 'Guest' }}</span>
              <div class="level-badge">LV.{{ userInfo.level || 1 }}</div>
            </div>
            <div class="balance-row">
              <span class="currency">¥</span>
              <span class="amount">{{ formatMoney(userInfo.balance || 0) }}</span>
            </div>
            <div class="status-row">Lãi/lỗ hôm nay {{ userInfo.todayPL || '+0.00' }}</div>
          </div>
        </div>

        <div class="action-buttons-row">
          <div class="action-btn btn-recharge" @click="handleAction('recharge')">
            <div class="icon-circle"><van-icon name="gold-coin-o" /></div>
            <span>Nạp tiền</span>
          </div>
          <div class="action-btn btn-withdraw" @click="handleAction('withdraw')">
            <div class="icon-circle"><van-icon name="balance-list-o" /></div>
            <span>Rút tiền</span>
          </div>
          <div class="action-btn btn-transfer" @click="handleAction('transfer')">
            <div class="icon-circle"><van-icon name="exchange" /></div>
            <span>Chuyển khoản</span>
          </div>
        </div>
      </div>

      
      <div class="menu-scroll-area">
        
        <div class="menu-group">
          <div class="group-title">Khu vực ví</div>
          <div class="menu-list">
            <div class="menu-item" @click="onMenuClick('game-hall')">
              <div class="menu-icon"><van-icon name="apps-o" /></div>
              <span class="menu-text">Sảnh game</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
            <div class="menu-item" @click="onMenuClick('hemai')">
              <div class="menu-icon"><van-icon name="friends-o" /></div>
              <span class="menu-text">Sảnh mua chung</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
            <div class="menu-item" @click="onMenuClick('yuebao')">
              <div class="menu-icon"><van-icon name="gem-o" /></div>
              <span class="menu-text">Tiết kiệm</span>
              <div class="badge-dot"></div>
            </div>
            <div class="menu-item" @click="onMenuClick('funds')">
              <div class="menu-icon"><van-icon name="bill-o" /></div>
              <span class="menu-text">Lịch sử giao dịch</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
            <div class="menu-item" @click="onMenuClick('report')">
              <div class="menu-icon"><van-icon name="chart-trending-o" /></div>
              <span class="menu-text">Báo cáo lãi/lỗ</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
          </div>
        </div>

        
        <div class="menu-group">
          <div class="group-title">Trung tâm cá nhân</div>
          <div class="menu-list">
            <div class="menu-item" @click="onMenuClick('profile')">
              <div class="menu-icon"><van-icon name="user-o" /></div>
              <span class="menu-text">Tài khoản</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
            <div class="menu-item" @click="onMenuClick('records')">
              <div class="menu-icon"><van-icon name="todo-list-o" /></div>
              <span class="menu-text">Lịch sử chơi</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
            <div class="menu-item" @click="onMenuClick('activity')">
              <div class="menu-icon"><van-icon name="gift-o" /></div>
              <span class="menu-text">Khuyến mãi</span>
              <van-icon name="hot-o" class="menu-status hot" />
            </div>
            <div class="menu-item" @click="onMenuClick('notice')">
              <div class="menu-icon"><van-icon name="bullhorn-o" /></div>
              <span class="menu-text">Thông báo</span>
              <van-icon name="arrow" class="menu-arrow" />
            </div>
          </div>
        </div>
      </div>

      
      <div class="bottom-fixed-area glass-panel-top">
        <div class="menu-group service-group">
          <div class="group-title">Dịch vụ</div>
          <div class="menu-list">
            <div class="menu-item small" @click="onMenuClick('service')">
              <div class="menu-icon"><van-icon name="service-o" /></div>
              <span class="menu-text">Liên hệ CSKH</span>
            </div>
            <div class="menu-item small" @click="onMenuClick('help')">
              <div class="menu-icon"><van-icon name="question-o" /></div>
              <span class="menu-text">Trợ giúp</span>
            </div>
            <div class="menu-item small" @click="onMenuClick('refresh')">
              <div class="menu-icon"><van-icon name="replay" /></div>
              <span class="menu-text">Làm mới</span>
            </div>
            <div class="menu-item logout" @click="onMenuClick('logout')">
              <div class="menu-icon"><van-icon name="revoke" /></div>
              <span class="menu-text">Đăng xuất</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </van-popup>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const defaultAvatar = '/assets/images/common/rui-face.png';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  userInfo: {
    type: Object,
    default: () => ({
      username: 'Momo_User',
      level: 3,
      balance: 128500.50,
      todayPL: '+1,250.00'
    })
  }
});

const emit = defineEmits(['update:show', 'menu-click', 'action-click']);

const onMenuClick = (key) => {
  emit('menu-click', key);

};

const handleAction = (type) => {
  emit('action-click', type);
};

const formatMoney = (val) => {
  return Number(val).toFixed(2);
};
</script>

<style scoped>

.sidebar-container {
  width: 100%;
  height: 100%;
  background: linear-gradient(180deg, #05070E 0%, #0B0F18 100%);
  
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  box-shadow: 5px 0 20px rgba(0,0,0,0.5);
}

.glass-panel {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  box-shadow: 0 4px 24px rgba(0,0,0,0.2);
}

.glass-panel-top {
  background: rgba(11, 15, 24, 0.95);
  backdrop-filter: blur(15px);
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.user-card {
  margin: calc(10px + env(safe-area-inset-top)) 12px 12px 12px;
  padding: 16px;
  border-radius: 18px;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}

.user-info-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.avatar-box {
  position: relative;
  width: 56px; height: 56px;
}
.avatar-img {
  width: 100%; height: 100%;
  border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.1);
  object-fit: cover;
}
.avatar-glow {
  position: absolute; inset: -4px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(63,209,255,0.3) 0%, transparent 70%);
  z-index: -1;
  animation: pulse 3s infinite;
}

.info-text { flex: 1; display: flex; flex-direction: column; gap: 4px; }

.name-row { display: flex; align-items: center; gap: 8px; }
.username { font-size: 16px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
.level-badge {
  background: linear-gradient(90deg, #EAC26E 0%, #FFD700 100%);
  color: #111;
  font-size: 10px;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 4px;
}

.balance-row { display: flex; align-items: baseline; color: #EAC26E; line-height: 1; }
.currency { font-size: 14px; margin-right: 2px; }
.amount { font-size: 22px; font-weight: 700; font-family: 'Orbitron', sans-serif; }

.status-row { font-size: 11px; color: #8C93A8; }

.action-buttons-row {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.action-btn {
  flex: 1;
  background: rgba(255,255,255,0.05);
  border-radius: 12px;
  padding: 8px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.2s;
}
.action-btn:active { transform: scale(0.95); background: rgba(255,255,255,0.08); }

.icon-circle {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  background: rgba(0,0,0,0.2);
}
.action-btn span { font-size: 12px; color: #A9B4D8; }

.btn-recharge .icon-circle { color: #3CE7A2; box-shadow: 0 0 10px rgba(60,231,162,0.15); }
.btn-withdraw .icon-circle { color: #EAC26E; box-shadow: 0 0 10px rgba(234,194,110,0.15); }
.btn-transfer .icon-circle { color: #3FD1FF; box-shadow: 0 0 10px rgba(63,209,255,0.15); }

.menu-scroll-area {
  flex: 1;
  overflow-y: auto;
  padding: 0 12px 20px 12px;
  
  scrollbar-width: none; 
}
.menu-scroll-area::-webkit-scrollbar { display: none; }

.menu-group { margin-bottom: 24px; }
.group-title {
  font-size: 12px;
  color: #5E657A;
  margin-bottom: 10px;
  padding-left: 8px;
  position: relative;
  display: flex; align-items: center;
}
.group-title::before {
  content: ''; position: absolute; left: 0;
  width: 3px; height: 12px;
  background: #EAC26E;
  border-radius: 2px;
}

.menu-list { display: flex; flex-direction: column; gap: 8px; }

.menu-item {
  display: flex;
  align-items: center;
  padding: 12px;
  border-radius: 12px;
  background: rgba(255,255,255,0.02);
  border: 1px solid transparent;
  transition: all 0.2s;
  cursor: pointer;
}

.menu-item:active, .menu-item.active {
  background: rgba(234, 194, 110, 0.05);
  border-color: rgba(234, 194, 110, 0.1);
}

.menu-icon {
  width: 24px; height: 24px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  color: #fff;
  margin-right: 12px;
  filter: drop-shadow(0 0 4px rgba(255,255,255,0.2));
}

.menu-text { flex: 1; font-size: 14px; color: #F5F7FF; font-weight: 500; }

.menu-arrow { font-size: 14px; color: #5E657A; }

.badge-dot { width: 6px; height: 6px; background: #FF4D6A; border-radius: 50%; box-shadow: 0 0 5px #FF4D6A; }
.menu-status.hot { color: #FF4D6A; font-size: 14px; }

.bottom-fixed-area {
  padding: 16px 12px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
}
.service-group { margin-bottom: 0; }

.menu-item.small {
  padding: 10px 12px;
  background: transparent;
}
.menu-item.logout {
  margin-top: 8px;
  background: rgba(255, 77, 106, 0.05);
  border: 1px solid rgba(255, 77, 106, 0.15);
}
.logout .menu-text, .logout .menu-icon { color: #FF4D6A; }

@keyframes pulse {
  0% { opacity: 0.5; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.1); }
  100% { opacity: 0.5; transform: scale(1); }
}
</style>
