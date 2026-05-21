<template>
  <div class="member-new-page">
    <div class="header-wrapper">
      <div class="nav-bar">
        <van-icon name="arrow-left" class="nav-icon" @click="router.back()" />
        <div class="nav-right">
          <div class="nav-icon-wrapper" @click="go('/yue-bao/service')">
            <img src="/assets/img/style_3_icon_top_kf.svg" class="nav-icon-img" />
          </div>
          <div class="nav-icon-wrapper" @click="go('/userCenter/notice')">
            <img 
              :src="unreadCount > 0 ? '/assets/img/style_3_icon_top_xx.svg' : '/assets/img/style_3_icon_top_xxyd.svg'" 
              class="nav-icon-img" 
            />
            <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
          </div>
        </div>
      </div>

<div class="vip-card" @click="go('/vip')">
        <div class="vip-circle-area">
          <div class="vip-badge-wrapper">
            <img :src="getBgIcon(currentVipLevel)" class="vip-outer-ring" />
            <div class="vip-badge-img-container">
               <img :src="getBadgeIcon(currentVipLevel)" class="vip-badge-img" />
               <span class="vip-level-num">{{ currentVipLevel }}</span>
            </div>
          </div>
        </div>
        <div class="vip-info">
          <div class="vip-next-tip">
            Cách <span class="bold">VIP {{ currentVipLevel + 1 }}</span> còn cần cược <span class="bold">{{ nextLevelBet }}</span>
          </div>
          <div class="vip-progress-bar">
            <span class="label">Thăng cấp</span>
            <div class="progress-track">
              <div class="progress-fill" :style="{ width: vipProgress + '%' }"></div>
              <span class="progress-text">{{ formatAmount(currentBet) }}/{{ nextLevelBet }}</span>
            </div>
          </div>
        </div>
        <van-icon name="arrow" class="vip-arrow" />
      </div>

      <div class="user-info-section">
        <div class="avatar-box" @click="triggerAvatarUpload">
          <img :src="getFaceUrl(userinfo.face)" class="avatar-img" />
          <div class="edit-icon">
            <van-icon name="edit" />
          </div>
          <input 
            type="file" 
            ref="avatarInput" 
            accept="image/*" 
            style="display: none" 
            @change="handleAvatarChange"
          />
        </div>
        <div class="user-details">
          <div class="name-row">
            <span class="username">{{ userinfo.username || 'Guest' }}</span>
            <van-icon name="orders-o" class="copy-icon" @click.stop="copyText(userinfo.username)" />
          </div>
          <div class="id-row">
            ID: {{ userinfo.id || '-' }} <van-icon name="orders-o" class="copy-icon" @click.stop="copyText(userinfo.id)" />
          </div>
        </div>
        <div class="balance-box">
          <img src="/assets/img/USDT.avif" class="coin-icon" />
          <span class="balance-num">{{ formattedBalance }}</span>
          <img src="/assets/img/comm_icon_sx.svg" class="refresh-icon" :class="{ spinning: loading }" @click.stop="refreshBalance" />
        </div>
      </div>

      <div class="quick-actions">
        <div class="action-item" @click="go('/payment/withdraw')">
          <div class="icon-wrapper">
             <img src="/assets/img/style_3_icon_mid_tx.svg" class="action-icon" />
          </div>
          <span>Rút tiền</span>
        </div>
        <div class="action-item divider-left divider-right" @click="showDepositPopup = true">
          <div class="icon-wrapper-center">
            <img src="/assets/img/gif_profile_style3.webp" class="action-icon-center" />
          </div>
          <span>Nạp tiền</span>
        </div>
        <div class="action-item" @click="go('/interest')">
          <div class="icon-wrapper">
            <img src="/assets/img/style_3_icon_mid_lxb.svg" class="action-icon" />
          </div>
          <span>Lãi suất</span>
        </div>
      </div>
    </div>

    <div class="menu-list">
      <van-cell-group inset>
        <van-cell title="Khôi phục số dư" is-link @click="go('/member/recover-balance')">
          <template #icon><img src="/assets/img/style_3_icon_list_zhye.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Hồ sơ của tôi" is-link value="Chi tiết, Cược, Báo cáo" @click="go('/member/records')">
          <template #icon><img src="/assets/img/style_3_icon_list_zhmx.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Quản lý rút tiền" is-link @click="go('/payment/withdraw?active=1')">
          <template #icon><img src="/assets/img/style_3_icon_list_txgl.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Chia sẻ kiếm tiền" is-link value="Thu nhập triệu đô không còn là mơ" value-class="green-text" @click="go('/invite')">
          <template #icon><img src="/assets/img/style_3_icon_list_fxzq.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Trung tâm bảo mật" is-link @click="go('/security')">
          <template #icon><img src="/assets/img/style_3_icon_list_aqzx.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Chọn ngôn ngữ" is-link value="Tiếng Việt" @click="showToast('Chưa mở')">
          <template #icon><img src="/assets/img/style_3_icon_list_xzyy.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Câu hỏi thường gặp" is-link @click="go('/userCenter/help')">
          <template #icon><img src="/assets/img/style_3_icon_list_cjwt.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Phản hồi có thưởng" is-link @click="go('/notice?tab=feedback')">
          <template #icon><img src="/assets/img/style_3_icon_list_yjfk.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Thiết bị đăng nhập" is-link @click="go('/security/devices')">
          <template #icon><img src="/assets/img/style_3_icon_list_dlsb.svg" class="menu-icon-green" /></template>
        </van-cell>
        <van-cell title="Đăng xuất an toàn" is-link @click="handleLogout">
          <template #icon><img src="/assets/img/style_3_icon_list_aqtc.svg" class="menu-icon-green" /></template>
        </van-cell>
      </van-cell-group>
    </div>
    
    <DepositPopup v-model:show="showDepositPopup" />
  </div>
</template>

<script setup>
defineOptions({ name: 'Member' })

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showDialog, showConfirmDialog, showLoadingToast, showSuccessToast, closeToast } from 'vant'
import { vipApi } from '@/api/vip'
import { authApi } from '@/api/auth'
import { uploadFile } from '@/api/im'
import { logout, getUserInfo } from '@/utils/auth'
import { checkFundPasswordAndNavigate } from '@/utils/withdrawCheck'
import { useUserStore } from '@/stores/user'
import DepositPopup from '@/components/deposit/DepositPopup.vue'

const userStore = useUserStore()

const router = useRouter()
const loading = ref(false)

const unreadCount = ref(0)
const showDepositPopup = ref(false)

const userinfo = ref({
  username: '',
  id: '',
  balance: 0,
  face: '',
  vipLevel: 0
})

const currentVipLevel = ref(1)
const nextLevelBet = ref('0.00')
const vipProgress = ref(0)
const currentBet = ref(0)

const getBgIcon = (level) => {
  const lv = Number(level) || 1
  if (lv >= 10) return '/assets/img/color10.avif'
  return `/assets/img/color${lv}.avif`
}

const getBadgeIcon = (level) => {
  const lv = Number(level) || 1
  if (lv >= 10) {
    return '/assets/img/img_dj10.avif'
  }
  return `/assets/img/img_dj${lv - 1}.avif`
}

const go = async (path) => {
  if (path === '/payment/withdraw' || path.startsWith('/payment/withdraw?')) {
    const query = {}
    if (path.includes('active=1')) {
      query.active = '1'
    }
    await checkFundPasswordAndNavigate(router, { query })
    return
  }
  router.push(path)
}

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    showToast('Sao chép thành công')
  }).catch(() => {
    showToast('Sao chép thất bại')
  })
}

const refreshBalance = async () => {
  if (loading.value) return
  loading.value = true
  
  try {
    const res = await authApi.getProfile()
    if (res.code === 0 && res.data?.user) {
      userinfo.value.balance = parseFloat(res.data.user.balance || 0)
      showToast('Làm mới thành công')
    }
  } catch (error) {
    showToast('Làm mới thất bại')
  } finally {
    setTimeout(() => { loading.value = false }, 1000)
  }
}

const handleLogout = () => {
  showConfirmDialog({
    title: 'Thông báo',
    message: 'Bạn có chắc muốn đăng xuất?',
  })
    .then(async () => {
      const toast = showLoadingToast({ message: 'Đang đăng xuất...', forbidClick: true })
      try {
        await logout({ router, logoutApi: authApi.logout, redirectUrl: '/home-new' })
      } catch {
      } finally {
        toast.close()
      }
    })
    .catch(() => {
    })
}

const API_HOST = import.meta.env.VITE_PROXY_TARGET || ''

const getFaceUrl = (face) => {
  const localAvatar = localStorage.getItem('userAvatar')
  if (localAvatar) {
    if (localAvatar.startsWith('http') || localAvatar.startsWith('data:')) {
      return localAvatar
    }
    return API_HOST + localAvatar
  }
  if (face) {
    if (face.startsWith('http') || face.startsWith('data:')) return face
    return API_HOST + face
  }
  return '/assets/img/img_ntx4.avif'
}

const avatarInput = ref(null)

const triggerAvatarUpload = () => {
  avatarInput.value?.click()
}

const handleAvatarChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  if (!file.type.startsWith('image/')) {
    showToast('Vui lòng chọn file ảnh')
    return
  }
  
  if (file.size > 2 * 1024 * 1024) {
    showToast('Kích thước ảnh không được vượt quá 2MB')
    return
  }
  
  showLoadingToast({ message: 'Đang tải lên...', forbidClick: true })
  
  try {
    const formData = new FormData()
    formData.append('file', file)
    
    const res = await uploadFile(formData)
    const url = res.data?.url
    
    closeToast()
    
    if (url) {
      userinfo.value.face = url
      showSuccessToast('Đã cập nhật ảnh đại diện')
      localStorage.setItem('userAvatar', url)
    } else {
      showToast('Tải lên thất bại')
    }
  } catch {
    closeToast()
    showToast('Tải lên thất bại')
  }
  
  e.target.value = ''
}

const formattedBalance = computed(() => {
  const val = Number(userinfo.value.balance) || 0
  return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

const formatAmount = (num) => {
  if (!num) return '0.00'
  return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const loadVipData = async () => {
  try {
    const configRes = await vipApi.getLevelConfigs()
    const levels = configRes?.data || []
    
    const infoRes = await vipApi.getRewardInfo()
    if (infoRes?.code === 0 && infoRes.data) {
      const d = infoRes.data
      currentVipLevel.value = Number(d.currentLevelId) || 1
      currentBet.value = Number(d.totalBetting) || 0
      
      const nextLevelIndex = currentVipLevel.value
      if (levels[nextLevelIndex]) {
        const nextLevelRequired = levels[nextLevelIndex].cumulativeRequired || 0
        nextLevelBet.value = formatAmount(nextLevelRequired)
        
        if (nextLevelRequired > 0) {
          vipProgress.value = Math.min((currentBet.value / nextLevelRequired) * 100, 100)
        } else {
          vipProgress.value = 0
        }
      }
    }
  } catch {
  }
}

const loadData = async () => {
  try {
    const localUser = getUserInfo()
    if (localUser?.groupid) {
      currentVipLevel.value = localUser.groupid
    }
    
    const res = await authApi.getProfile()
    if (res.code === 0 && res.data?.user) {
      const user = res.data.user
      userinfo.value = {
        ...userinfo.value,
        username: user.username,
        id: user.id || '',
        balance: parseFloat(user.balance || 0),
        face: user.face || user.avatar || '',
        vipLevel: user.groupid || 0
      }
      currentVipLevel.value = user.groupid || currentVipLevel.value
    }
    
    await loadVipData()
  } catch {
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.member-new-page {
  min-height: 100%;
  background-color: #f7f8fa;
  padding-bottom: 20px;
  overflow-y: auto;
  height: 100%;
  font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
}

.header-wrapper {
  background-color: #3EB5A0;
  background-image: url('/assets/img/style_3_topbg1.avif');
  background-repeat: no-repeat;
  background-position: top center;
  background-size: 100% 100%;
  padding-top: 27px;
  padding-bottom: 10px;
  position: relative;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}

.nav-bar {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  box-sizing: border-box;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 10px 0 10px;
  color: #fff;
  z-index: 10;
}
.nav-icon {
  font-size: 22px;
}
.nav-icon-wrapper {
  position: relative;
  width: 27.52px;
  height: 27.52px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.nav-icon-img {
  width: 27.52px;
  height: 27.52px;
  display: block;
}
.unread-badge {
  position: absolute;
  top: -4px;
  right: -6px;
  background-color: #f44336;
  color: white;
  font-size: 10px;
  padding: 1px 4px;
  border-radius: 10px;
  min-width: 14px;
  text-align: center;
  line-height: 12px;
  border: 1px solid #fff;
}
.nav-right {
  display: flex;
  gap: 15px;
}

.vip-card {
  cursor: pointer;
  margin: 18px auto 0;
  height: 82px;
  width: calc(100% - 24px);
  max-width: 430px;
  background: url('/assets/img/style_3_vipbg.avif') no-repeat center;
  background-size: 100% 100%;
  border-radius: 12px;
  display: flex;
  align-items: center;
  padding: 0 10px;
  position: relative;
  color: #5d4037;
  box-shadow: 0 8px 16px rgba(0,0,0,0.15);
  margin-bottom: 5px;
  z-index: 1;
}

.vip-circle-area {
  width: 70px;
  flex-shrink: 0;
  display: flex;
  justify-content: center;
  margin-left: 10px;
}
.vip-badge-wrapper {
  position: relative;
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.vip-outer-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  object-fit: contain;
}
.vip-badge-img-container {
  position: relative;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}
.vip-badge-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.vip-level-num {
  position: absolute;
  top: 48%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 20px;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0,0,0,0.3);
  font-family: 'Arial', sans-serif;
}

.vip-info {
  flex: 1;
  margin-left: 20px;
  font-size: 12px;
}
.vip-next-tip {
  margin-bottom: 8px;
  color: #5d4037;
  font-size: 14px;
}
.bold { font-weight: bold; }

.vip-progress-bar {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8px;
}
.vip-progress-bar .label {
  color: #8d6e63;
  font-size: 10px;
  white-space: nowrap;
}
.progress-track {
  flex: 1;
  height: 11px;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 6px;
  position: relative;
  overflow: hidden;
  max-width: 227px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.progress-fill {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  background: linear-gradient(90deg, #f7971e, #ffd200);
  border-radius: 6px;
  transition: width 0.3s ease;
}
.progress-text {
  position: absolute;
  font-size: 10px;
  line-height: 11px;
  color: #5d4037;
  font-weight: bold;
  width: 100%;
  text-align: center;
  top: 0;
  bottom: 0;
}

.vip-arrow {
  position: absolute;
  right: 15px;
  top: 30%;
  transform: translateY(-50%);
  color: #8d6e63;
}

.user-info-section {
  padding: 10px 15px 0;
  display: flex;
  align-items: center;
  color: #fff;
  position: relative;
  z-index: 2;
}

.avatar-box {
  position: relative;
  width: 50.45px;
  height: 50.45px;
  margin-right: 12px;
}
.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 12px;
  object-fit: cover;
}
.edit-icon {
  position: absolute;
  bottom: 0;
  right: -2px;
  background: #fff;
  color: #009688;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #eee;
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.name-row {
  display: flex;
  align-items: center;
  font-size: 16px;
  font-weight: bold;
}
.username { margin-right: 5px; }
.id-row {
  font-size: 12px;
  opacity: 0.9;
  display: flex;
  align-items: center;
}
.copy-icon { margin-left: 6px; cursor: pointer; opacity: 0.8; }

.balance-box {
  display: flex;
  align-items: center;
}
.coin-icon {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 6px;
  object-fit: contain;
}
.balance-num {
  font-size: 24px;
  font-weight: bold;
  margin-right: 8px;
  margin-left: 2px;
}
.refresh-icon {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.quick-actions {
  display: flex;
  justify-content: space-around;
  padding: 5px 15px 10px;
  margin-bottom: 5px;
}
.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #fff;
  flex: 1;
  position: relative;
  cursor: pointer;
}
.icon-wrapper {
  width: 30px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
}
.action-icon {
  width: 30px;
  height: 26px;
  object-fit: contain;
}
.icon-wrapper-center {
  width: 30px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
}
.action-icon-center {
  width: 30px;
  height: 34px;
  object-fit: contain;
}
.divider-right::after {
  content: '';
  position: absolute;
  right: 0;
  top: 10px;
  bottom: 10px;
  width: 1px;
  background: rgba(255,255,255,0.3);
}
.divider-left::before {
  content: '';
  position: absolute;
  left: 0;
  top: 10px;
  bottom: 10px;
  width: 1px;
  background: rgba(255,255,255,0.3);
}

.menu-list {
  background: transparent;
  padding-top: 0;
  position: relative;
  z-index: 2;
  margin-top: 0;
  padding-left: 15px;
  padding-right: 15px;
}
:deep(.van-cell-group--inset) {
  margin: 0;
  background: transparent;
}
:deep(.van-cell) {
  align-items: center;
  padding: 12px 15px;
  font-size: 14px;
  margin-bottom: 8px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
  min-height: 46px;
}
:deep(.van-cell::after) {
  display: none;
}

.menu-icon-green {
  width: 24px;
  height: 24px;
  margin-right: 10px;
  display: block;
  object-fit: contain;
}
.menu-icon-vant {
  font-size: 22px;
  margin-right: 10px;
}
.green { color: #009688; }

.green-text {
  color: #009688;
}
</style>
