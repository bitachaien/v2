<template>
  <div class="login-device">
    <van-nav-bar
      title="Thiết bị đăng nhập"
      left-arrow
      @click-left="onClickLeft"
      class="custom-nav"
    />

    <div class="user-info-card">
      <div class="logo-area">
        <img src="/assets/images/common/logo.png" alt="logo" class="site-logo" />
      </div>
      <div class="user-details">
        <div class="user-id">
          <span>ID: {{ userInfo.id }}</span>
          <van-icon name="description" class="copy-icon" @click="copyText(userInfo.id)" />
        </div>
        <div class="user-account">Tài khoản: {{ userInfo.username }}</div>
      </div>
    </div>

    <div class="auto-logout-setting" @click="showTimeoutPicker = true">
      <span class="label">Tự động đăng xuất<span class="sub-label">(thời gian rảnh)</span></span>
      <span class="value">{{ timeoutText }} <van-icon name="arrow" /></span>
    </div>

    <div class="device-list">
      <div v-for="device in deviceList" :key="device.id" class="device-card">
        <div class="device-header">
          <van-icon :name="device.os_type === 'Windows' ? 'desktop-o' : 'phone-o'" class="device-type-icon" />
          <span class="device-label">{{ device.is_current ? 'Thiết bị hiện tại' : 'Thiết bị lịch sử' }}</span>
          <van-icon v-if="device.is_current" name="passed" class="check-icon" />
        </div>

        <div class="device-info">
          <div class="info-row">
            <span class="info-label">Ứng dụng</span>
            <span class="info-value">{{ device.client_version || 'H5' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Loại trình duyệt</span>
            <span class="info-value">{{ device.browser_type || 'Không rõ' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Hệ điều hành</span>
            <span class="info-value">{{ device.os_type || 'Không rõ' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Phiên bản hệ thống</span>
            <span class="info-value">{{ device.os_version || 'Không rõ' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Thương hiệu</span>
            <span class="info-value">{{ device.device_brand || 'Không rõ' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Mẫu thiết bị</span>
            <span class="info-value">{{ device.device_model || 'Không rõ' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Khu vực IP</span>
            <span class="info-value ip-value">{{ device.ip_region || device.ip || 'Không rõ' }}</span>
          </div>
          <div class="info-row" v-if="device.last_login_at">
            <span class="info-label">Thời gian đăng nhập</span>
            <span class="info-value">{{ formatTime(device.last_login_at) }}</span>
          </div>
        </div>
      </div>

      <van-empty v-if="!loading && deviceList.length === 0" description="Chưa có thiết bị đăng nhập" />
    </div>

    <van-action-sheet
      v-model:show="showTimeoutPicker"
      :actions="timeoutOptions"
      cancel-text="Hủy"
      @select="onSelectTimeout"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import request from '@/api/request'

const router = useRouter()
const loading = ref(false)
const showTimeoutPicker = ref(false)
const selectedTimeout = ref(365)

const userInfo = ref({ id: '', username: '' })
const deviceList = ref([])

const timeoutOptions = [
  { name: '30 phút', value: 0.02 },
  { name: '1 giờ', value: 0.04 },
  { name: '1 ngày', value: 1 },
  { name: '7 ngày', value: 7 },
  { name: '30 ngày', value: 30 },
  { name: '1 năm', value: 365 }
]

const timeoutText = computed(() => {
  const opt = timeoutOptions.find(o => o.value === selectedTimeout.value)
  return opt ? opt.name : '1 năm'
})

const loadUserInfo = () => {
  const cached = localStorage.getItem('userInfo')
  if (cached) {
    try {
      const info = JSON.parse(cached)
      userInfo.value.id = info.id || info.userId || ''
      userInfo.value.username = info.username || info.account || ''
    } catch (e) {}
  }
}

const loadSettings = async () => {
  try {
    const res = await request.get('/v1/user/settings')
    if (res.code === 0 && res.data?.autoLogoutDays) {
      selectedTimeout.value = res.data.autoLogoutDays
    }
  } catch (e) {}
}

const loadDeviceList = async () => {
  loading.value = true
  try {
    const res = await request.get('/v1/user/devices')
    if (res.code === 0 && res.data) {
      deviceList.value = res.data.list || []
    }
  } catch (e) {}
  loading.value = false
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  const h = String(date.getHours()).padStart(2, '0')
  const min = String(date.getMinutes()).padStart(2, '0')
  const s = String(date.getSeconds()).padStart(2, '0')
  return `${y}/${m}/${d} ${h}:${min}:${s}`
}

const onClickLeft = () => router.go(-1)

const copyText = (text) => {
  if (!text) return
  navigator.clipboard.writeText(String(text)).then(() => {
    showToast('Sao chép thành công')
  }).catch(() => {
    showToast('Sao chép thất bại')
  })
}

const onSelectTimeout = async (action) => {
  selectedTimeout.value = action.value
  showTimeoutPicker.value = false
  try {
    await request.post('/v1/user/settings', { key: 'autoLogoutDays', value: action.value })
    showToast('Đã lưu cài đặt')
  } catch (e) {
    showToast('Lưu thất bại')
  }
}

onMounted(() => {
  loadUserInfo()
  loadSettings()
  loadDeviceList()
})
</script>

<style scoped>
.login-device {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 20px;
}
.custom-nav { background: #fff; }
:deep(.van-nav-bar__title) { font-weight: 500; font-size: 17px; }
.user-info-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background: #fff;
  margin-bottom: 1px;
}
.logo-area { margin-right: 12px; }
.site-logo { width: 50px; height: 50px; object-fit: contain; }
.user-details { flex: 1; }
.user-id { display: flex; align-items: center; font-size: 15px; color: #333; margin-bottom: 4px; }
.copy-icon { margin-left: 6px; color: #26A17B; font-size: 16px; cursor: pointer; }
.user-account { font-size: 14px; color: #666; }
.auto-logout-setting {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: #fff;
  margin-bottom: 12px;
}
.label { font-size: 15px; color: #26A17B; font-weight: 500; }
.sub-label { color: #999; font-weight: normal; }
.value { font-size: 14px; color: #26A17B; display: flex; align-items: center; gap: 4px; }
.device-list { padding: 0 12px; }
.device-card { background: #fff; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
.device-header { display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f5f5f5; }
.device-type-icon { font-size: 20px; color: #26A17B; margin-right: 8px; }
.device-label { font-size: 15px; color: #333; font-weight: 500; }
.check-icon { margin-left: 8px; color: #26A17B; font-size: 18px; }
.device-info { padding: 12px 16px; }
.info-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #f9f9f9; }
.info-row:last-child { border-bottom: none; }
.info-label { font-size: 14px; color: #666; flex-shrink: 0; }
.info-value { font-size: 14px; color: #333; text-align: right; word-break: break-all; max-width: 60%; }
.ip-value { font-size: 12px; line-height: 1.4; }
</style>
