<template>
  <div class="v5-quick">
    <div class="auth-btns" v-if="!isLogin">
      <div class="btn login" @click="openAuth('login')>Đăng nhập</div>
      <div class="btn reg" @click="openAuth('register')">Đăng ký</div>
    </div>

    <div class="user-info-panel" v-else>
      <div class="u-row top">
        <div class="level-badge">V{{ userInfo.groupid || 1 }}</div>
        <span class="username">{{ userInfo.username || 'User' }}</span>
        <van-icon name="orders-o" class="copy-icon" @click="copyText(userInfo.username)" />
      </div>
      <div class="u-row bottom">
        <img src="/assets/img/USDT.avif" class="coin-img" />
        <div class="balance-box">
          <span class="balance">{{ userInfo.balance || '0.00' }}</span>
          <img src="/assets/img/comm_icon_sx1.svg" class="refresh-icon" :class="{ spinning: refreshing }" @click="refreshBalance" />
        </div>
      </div>
    </div>

    <div class="quick-icons">
      <div class="q-item" @click="go('/service')">
        <img src="/assets/img/icon_dt_1kf.avif" class="q-icon" />
        <span>CSKH</span>
      </div>
      <div class="q-item" @click="go('/vip')">
        <img src="/assets/img/icon_dt_1vip.avif" class="q-icon" />
        <span>VIP</span>
      </div>
      <div class="q-item" @click="go('/about')">
        <img src="/assets/img/icon_dt_1app.avif" class="q-icon" />
        <span>Giới thiệu</span>
      </div>
      <div class="q-item" @click="showMore = true">
        <img src="/assets/img/icon_dt_1gd.avif" class="q-icon" />
        <span>Thêm</span>
      </div>
    </div>
    
    <div v-if="showMore" class="custom-popup-mask" @click="showMore = false"></div>
    <div v-if="showMore" class="custom-popup">
      <div class="popup-arrow"></div>
      <div class="more-menu-grid">
        <div class="menu-item" v-for="(item, index) in moreItems" :key="index" @click="handleMenuClick(item)">
          <img v-if="item.iconImg" :src="item.iconImg" class="menu-icon-img" />
          <van-icon v-else :name="item.icon" size="24" color="#009688" />
          <span>{{ item.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { getUserInfo, isLoggedIn } from '@/utils/auth'
import { checkFundPasswordAndNavigate } from '@/utils/withdrawCheck'
import { authApi } from '@/api/auth'

const router = useRouter()
const emit = defineEmits(['open-auth'])
const showMore = ref(false)
const isLogin = ref(false)
const userInfo = ref({})
const refreshing = ref(false)

onMounted(() => {
  checkLoginStatus()
})

const checkLoginStatus = async () => {
  isLogin.value = isLoggedIn()
  if (isLogin.value) {
    userInfo.value = getUserInfo() || {}
    try {
      const res = await authApi.getProfile()
      if (res.code === 0 && res.data?.user) {
        userInfo.value = {
          ...userInfo.value,
          ...res.data.user,
          groupid: res.data.user.groupid || userInfo.value.groupid || 1
        }
      }
    } catch (e) {}
  }
}

const refreshBalance = async () => {
  refreshing.value = true
  try {
    const res = await authApi.getProfile()
    if (res.code === 0 && res.data?.user) {
      userInfo.value.balance = res.data.user.balance || 0
      showToast('Làm mới thành công')
    }
  } catch (e) {
    showToast('Làm mới thất bại')
  } finally {
    refreshing.value = false
  }
}

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    showToast('Sao chép thành công')
  }).catch(() => {
    showToast('Sao chép thất bại')
  })
}

const openAuth = (type) => {
  emit('open-auth', type)
}

const go = (path) => router.push(path)

const moreItems = [
  { name: 'Lãi suất', iconImg: '/assets/img/icon_dt_1yeb.avif', path: '/interest' },
  { name: 'Chia sẻ', iconImg: '/assets/img/icon_dt_1tg.avif', path: '/member/invite' },
  { name: 'Rút tiền', iconImg: '/assets/img/icon_dt_1tx.avif', path: '/payment/withdraw' },
  { name: 'Nạp tiền', iconImg: '/assets/img/icon_dt_1cz.avif', path: '/payment/deposit' },
  { name: 'Sự kiện', iconImg: '/assets/img/icon_dt_1yh.avif', path: '/activity' },
  { name: 'Hoàn trả', iconImg: '/assets/img/icon_dt_1fs.avif', path: '/cashback' },
  { name: 'Trợ giúp', iconImg: '/assets/img/icon_dt_1yjfq.avif', path: '/help' },
  { name: 'Bảo mật', iconImg: '/assets/img/icon_dt_1aqzx.avif', path: '/security' },
  { name: 'Cá nhân', iconImg: '/assets/img/icon_dt_1sz.avif', path: '/member' },
  { name: 'Chi tiết', iconImg: '/assets/img/icon_dt_1zhmx.avif', path: '/account/bill' },
  { name: 'Lịch sử', iconImg: '/assets/img/icon_dt_1tzjl.avif', path: '/member/history' },
  { name: 'Hôm nay', iconImg: '/assets/img/icon_dt_1grbb.avif', path: '/account/today-stats' },
  { name: 'Chờ nhận', iconImg: '/assets/img/icon_dt_1jl.avif', path: '/pending' },
  { name: 'Chuyển đổi', iconImg: '/assets/img/icon_dt_1txgl.avif', path: '/account/transfer' },
  { name: 'Đổi MK', iconImg: '/assets/img/icon_dt_1yuyuan.avif', path: '/account/change-password' },
  { name: 'Điểm danh', iconImg: '/assets/img/icon_dt_1jh.avif', path: '/lottery/signin' },
  { name: 'LS nhận', iconImg: '/assets/img/icon_dt_1lqjl.avif', path: '/reward-record' },
  { name: 'Khôi phục', iconImg: '/assets/img/icon_dt_1ljfx.avif', path: '/member/recover-balance' },
  { name: 'Chat', iconImg: '/assets/img/icon_dt_1zj.avif', path: '/im' },
  { name: 'Về chúng tôi', iconImg: '/assets/img/icon_dt_1sc.avif', path: '/about' }
]

const handleMenuClick = async (item) => {
  showMore.value = false
  if (item.path === '/payment/withdraw' || item.path === '/withdraw/manage') {
    const options = item.path === '/withdraw/manage' ? { query: { active: '1' } } : {}
    await checkFundPasswordAndNavigate(router, options)
    return
  }
  if (item.path) router.push(item.path)
}
</script>

<style lang="scss" scoped>
.v5-quick {
  display: flex;
  padding: 12px;
  background: transparent;
  align-items: center;
  position: relative;
}

.auth-btns {
  flex: 0 0 38%;
  display: flex;
  gap: 12px;
  margin-right: 12px;
}

.btn {
  flex: 1;
  height: 36px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 500;

  &.login {
    border: 1px solid #26A17B;
    color: #26A17B;
    background: rgba(255, 255, 255, 0.8);
  }

  &.reg {
    background: #26A17B;
    color: #fff;
    box-shadow: 0 2px 6px rgba(0, 150, 136, 0.3);
  }
}

.user-info-panel {
  flex: 0 0 38%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  margin-right: 12px;
  gap: 4px;
}

.u-row {
  display: flex;
  align-items: center;
  gap: 8px;

  &.top {
    margin-bottom: 2px;
  }
}

.level-badge {
  background: #26A17B;
  color: #fff;
  font-size: 10px;
  padding: 0 4px;
  border-radius: 3px;
  height: 16px;
  display: flex;
  align-items: center;
}

.username {
  font-size: 14px;
  font-weight: 700;
  color: #333;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.copy-icon {
  color: #26A17B;
  cursor: pointer;
  font-size: 14px;
}

.coin-img {
  width: 20px;
  height: 20px;
  object-fit: contain;
}

.balance-box {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
}

.balance {
  font-size: 16px;
  color: #FFAA09;
  font-weight: 700;
}

.refresh-icon {
  width: 16px;
  height: 16px;
  cursor: pointer;
  margin-top: 1px;

  &.spinning {
    animation: spin 1s linear infinite;
  }
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

.quick-icons {
  flex: 1;
  display: flex;
  justify-content: flex-end;
  gap: 15px;
}

.q-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;

  span {
    font-size: 12px;
    color: #555;
    font-weight: 500;
  }
}

.q-icon {
  width: 32px;
  height: 28px;
  object-fit: contain;
}

.custom-popup-mask {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

.custom-popup {
  position: absolute;
  top: 70px;
  right: 12px;
  width: 320px;
  max-width: 95vw;
  height: auto;
  max-height: 400px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  overflow: hidden;
}

.popup-arrow {
  position: absolute;
  top: -6px;
  right: 16px;
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid #fff;
}

.more-menu-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  padding: 10px 5px 15px 5px;
  background: transparent;
  max-height: 400px;
  overflow-y: auto;
  align-content: flex-start;
  gap: 8px 2px;
}

.menu-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 8px 4px;
  min-height: 65px;
  gap: 6px;
  cursor: pointer;
  transition: background 0.2s;

  &:active {
    background: rgba(38, 161, 123, 0.1);
    border-radius: 6px;
  }

  span {
    font-size: 11px;
    color: #333;
    text-align: center;
    line-height: 1.3;
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: auto;
    min-height: 28px;
  }
}

.menu-icon-img {
  width: 28px;
  height: 28px;
  object-fit: contain;
  flex-shrink: 0;
}
</style>
